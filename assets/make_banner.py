"""
FG Backend LangSwitcher banner (1200x525, JED format), matching the
established FG series banner layout (same as FG Admin Login Customizer /
FG Email Remover): coral "FG" + white title, italic subtitle, coral rule,
four bullet points, mark with drop shadow and a caption. All text is
baked to vector outlines.
"""

from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
import cairosvg

FONT_DIR = '/mnt/skills/examples/canvas-design/canvas-fonts'
BOLD = f'{FONT_DIR}/InstrumentSans-Bold.ttf'
REGULAR = f'{FONT_DIR}/InstrumentSans-Regular.ttf'
ITALIC = f'{FONT_DIR}/InstrumentSans-Italic.ttf'

_cache = {}


def _font(path):
    if path not in _cache:
        f = TTFont(path)
        _cache[path] = (f, f.getGlyphSet(), f['head'].unitsPerEm, f.getBestCmap())
    return _cache[path]


def text_path(string, font_path, size, x, y, letter_spacing=0.0):
    font, glyphs, upem, cmap = _font(font_path)
    scale = size / upem
    parts = []
    cursor = 0.0
    for ch in string:
        name = cmap.get(ord(ch))
        if name is None:
            cursor += size * 0.32
            continue
        glyph = glyphs[name]
        pen = SVGPathPen(glyphs)
        glyph.draw(pen)
        d = pen.getCommands()
        if d:
            tx = x + cursor
            parts.append(
                f'<path d="{d}" transform="translate({tx:.2f} {y:.2f}) '
                f'scale({scale:.5f} {-scale:.5f})"/>'
            )
        cursor += glyph.width * scale + letter_spacing
    return ''.join(parts), cursor


def text_width(string, font_path, size, letter_spacing=0.0):
    _, glyphs, upem, cmap = _font(font_path)
    scale = size / upem
    total = 0.0
    for ch in string:
        name = cmap.get(ord(ch))
        total += (glyphs[name].width * scale) if name else size * 0.32
        total += letter_spacing
    return total


W, H = 1200, 525

BG_TOP = '#0C2E4F'
BG_BOTTOM = '#1C5687'
CORAL = '#FF6B4A'
WHITE = '#FFFFFF'
MUTED = '#B9CFE4'
NAVY = '#17456F'

TX = 465

title_size = 44
fg_d, fg_w = text_path('FG', BOLD, title_size, TX, 105)
title_d, title_w = text_path(' Backend LangSwitcher', BOLD, title_size, TX + fg_w, 105)

sub_size = 22
sub_d, sub_w = text_path(
    'Switch your own admin language in one click \u2014 no permissions needed.',
    ITALIC, sub_size, TX, 147
)

rule_w = max(fg_w + title_w, sub_w)
rule_y = 169

bullets = [
    ('Temporary or permanent', 'session-only by default, or save it to your profile'),
    ('Dropdown or inline style', 'native Atum look, or plain badges anywhere else'),
    ('Zero setup', 'module instance and plugin enable themselves on install'),
    ('Joomla 5 & 6 native', 'PSR-4, DI container, a single self-hosted update channel'),
]

heading_size = 22
desc_size = 17
bullet_y0 = 223
bullet_step = 62

bullets_svg = []
for i, (head, desc) in enumerate(bullets):
    y = bullet_y0 + i * bullet_step
    head_d, _ = text_path(head, BOLD, heading_size, TX + 21, y)
    desc_d, _ = text_path(desc, REGULAR, desc_size, TX + 21, y + 27)
    bullets_svg.append(
        f'<circle cx="{TX + 5}" cy="{y - 7}" r="5" fill="{CORAL}"/>'
        f'<g fill="{WHITE}">{head_d}</g>'
        f'<g fill="{MUTED}">{desc_d}</g>'
    )
bullets_svg = '\n'.join(bullets_svg)

MARK_SIZE = 290
MARK_X, MARK_Y = 70, 90
s = MARK_SIZE / 512.0


def m(v):
    return v * s


def chip(cx, cy, half, rotation, fill, letter, letter_size, letter_color):
    lcx, lcy = m(cx), m(cy)
    lhalf = m(half)
    rx = m(half * 0.34)
    ld, lw = text_path(letter, BOLD, m(letter_size), 0, 0)
    lx = -lw / 2
    ly = m(letter_size) * 0.36
    return f'''
    <g transform="rotate({rotation} {lcx:.1f} {lcy:.1f})">
      <rect x="{lcx-lhalf:.1f}" y="{lcy-lhalf:.1f}" width="{lhalf*2:.1f}" height="{lhalf*2:.1f}"
            rx="{rx:.1f}" ry="{rx:.1f}" fill="{fill}"/>
      <g transform="translate({lcx+lx:.1f} {lcy+ly:.1f})" fill="{letter_color}">{ld}</g>
    </g>'''


mark = f'''
  <g filter="url(#markBlur)" opacity="0.4">
    <rect x="{MARK_X}" y="{MARK_Y + 14}" width="{MARK_SIZE}" height="{MARK_SIZE}"
          rx="{m(96):.1f}" ry="{m(96):.1f}" fill="#000000"/>
  </g>
  <g transform="translate({MARK_X} {MARK_Y})">
    <rect x="{m(10):.1f}" y="{m(10):.1f}" width="{m(492):.1f}" height="{m(492):.1f}"
          rx="{m(96):.1f}" ry="{m(96):.1f}" fill="url(#markBlue)"/>
    {chip(210, 210, 108, -7, WHITE, 'A', 150, '#0E3A5C')}
    {chip(305, 305, 108, 7, CORAL, 'Č', 150, WHITE)}
  </g>
'''

caption_size = 20
caption_text = 'Module + System plugin'
caption_w = text_width(caption_text, BOLD, caption_size, 0.4)
caption_d, _ = text_path(
    caption_text, BOLD, caption_size,
    MARK_X + MARK_SIZE / 2 - caption_w / 2, MARK_Y + MARK_SIZE + 32,
    letter_spacing=0.4
)

blobs = f'''
  <circle cx="1120" cy="60" r="180" fill="#FFFFFF" opacity="0.035"/>
  <circle cx="1180" cy="260" r="130" fill="#FFFFFF" opacity="0.045"/>
  <circle cx="60" cy="470" r="150" fill="#000000" opacity="0.08"/>
  <circle cx="980" cy="470" r="220" fill="#FFFFFF" opacity="0.03"/>
'''

svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{BG_TOP}"/>
      <stop offset="1" stop-color="{BG_BOTTOM}"/>
    </linearGradient>
    <linearGradient id="markBlue" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#081C30"/>
      <stop offset="1" stop-color="#123A5C"/>
    </linearGradient>
    <filter id="markBlur" x="-80%" y="-80%" width="260%" height="260%">
      <feGaussianBlur stdDeviation="26"/>
    </filter>
    <clipPath id="frame"><rect width="{W}" height="{H}"/></clipPath>
  </defs>

  <g clip-path="url(#frame)">
    <rect width="{W}" height="{H}" fill="url(#bg)"/>
    {blobs}

{mark}
    <g fill="{WHITE}">{caption_d}</g>

    <g fill="{CORAL}">{fg_d}</g>
    <g fill="{WHITE}">{title_d}</g>
    <g fill="{MUTED}" font-style="italic">{sub_d}</g>
    <rect x="{TX}" y="{rule_y}" width="{rule_w:.1f}" height="2" fill="{CORAL}" opacity="0.6"/>

{bullets_svg}
  </g>
</svg>
'''

with open('banner.svg', 'w', encoding='utf-8') as f:
    f.write(svg)

# ------------------------------------------------------------------
# PNG rendering: same PIL-composited shadow technique as the reference
# script (cairosvg's feGaussianBlur support is unreliable).
# ------------------------------------------------------------------
mark_no_shadow = mark.split("<g transform=", 1)
mark_no_shadow = "<g transform=" + mark_no_shadow[1]

from PIL import Image, ImageFilter

shadow_svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">
  <rect x="{MARK_X}" y="{MARK_Y + 14}" width="{MARK_SIZE}" height="{MARK_SIZE}"
        rx="{m(96):.1f}" ry="{m(96):.1f}" fill="#000000"/>
</svg>
'''
cairosvg.svg2png(bytestring=shadow_svg.encode('utf-8'), write_to='_shadow_solid.png',
                 output_width=W, output_height=H)

shadow = Image.open('_shadow_solid.png').convert('RGBA')
r, g, b, a = shadow.split()
a = a.filter(ImageFilter.GaussianBlur(radius=20))
a = a.point(lambda v: int(v * 0.40))
shadow = Image.merge('RGBA', (r, g, b, a))

content_only_svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">
  <defs>
    <linearGradient id="markBlue" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#081C30"/>
      <stop offset="1" stop-color="#123A5C"/>
    </linearGradient>
    <clipPath id="frame"><rect width="{W}" height="{H}"/></clipPath>
  </defs>
  <g clip-path="url(#frame)">
{mark_no_shadow}
    <g fill="{WHITE}">{caption_d}</g>

    <g fill="{CORAL}">{fg_d}</g>
    <g fill="{WHITE}">{title_d}</g>
    <g fill="{MUTED}" font-style="italic">{sub_d}</g>
    <rect x="{TX}" y="{rule_y}" width="{rule_w:.1f}" height="2" fill="{CORAL}" opacity="0.6"/>

{bullets_svg}
  </g>
</svg>
'''
cairosvg.svg2png(bytestring=content_only_svg.encode('utf-8'), write_to='_content.png',
                 output_width=W, output_height=H)
content = Image.open('_content.png').convert('RGBA')

bg_only_svg = f'''<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{BG_TOP}"/>
      <stop offset="1" stop-color="{BG_BOTTOM}"/>
    </linearGradient>
  </defs>
  <rect width="{W}" height="{H}" fill="url(#bg)"/>
  {blobs}
</svg>
'''
cairosvg.svg2png(bytestring=bg_only_svg.encode('utf-8'), write_to='_bgonly.png',
                 output_width=W, output_height=H)
bg_only = Image.open('_bgonly.png').convert('RGBA')

layered = Image.alpha_composite(bg_only, shadow)
layered = Image.alpha_composite(layered, content)
layered.convert('RGB').save('banner.png')

for tmp in ('_shadow_solid.png', '_content.png', '_bgonly.png'):
    import os
    os.remove(tmp)

print('banner.svg + banner.png OK (PIL-composited shadow)')
