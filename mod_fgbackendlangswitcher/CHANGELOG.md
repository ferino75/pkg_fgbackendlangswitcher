# Changelog — mod_fgbackendlangswitcher

Versions are kept in sync with the `pkg_fgbackendlangswitcher` package
(even in a release where only the module, the companion plugin, or just
the package infrastructure changed).

## 1.2.20
- New logo and JED-style banner (`assets/logo.png`, `assets/banner.png`,
  1200×525), matching the established FG series banner layout: mark with
  soft drop shadow, coral "FG" + white title, italic subtitle, coral
  rule, four bullet points describing the actual features. The mark is a
  "translate" pictogram — two overlapping rounded chips, "A" and "Č"
  (Slovak diacritic), baked to vector path outlines (Instrument Sans, via
  fontTools) rather than live SVG text, avoiding font-substitution
  issues. Generation script included at `assets/make_banner.py`. README
  now leads with the banner instead of the small standalone logo.
  Replaces the swap-arrows icon design used in an earlier, never-released
  draft of this version. Assets/docs only, no functional code change.

## 1.2.19
- Documentation-only fix: the README version badge was a static,
  manually-typed number (stuck at 1.2.12 since the initial rebrand —
  never updated across 1.2.13–1.2.18). Replaced with a dynamic
  shields.io GitHub Release badge
  (`img.shields.io/github/v/release/...`) that reads the latest release
  tag automatically, so it can't go stale again regardless of future
  releases. No functional code change.

## 1.2.18
- The "Default" switch confirmation message now also shows the resolved
  language tag (e.g. "Default (administrator setting) → en-GB"), using
  the same `com_languages` "administrator" param lookup added to the
  plugin in 1.2.16 — purely for display; what actually gets stored
  (cleared profile param in permanent mode, the marker in temporary mode)
  is unchanged.

## 1.2.17
- Documentation-only fix: corrected the "Show 'Default' option" field
  description (en-GB + sk-SK). It previously said the reset option is
  "shown only when a personal language is set" and called the target
  "the site's default administrator language" — both imprecise. The
  option is also shown for a *temporary* selection with no persisted
  profile language (matching `hasCustomSelection()`'s actual logic since
  1.2.8), and the reset target is Joomla's administrator default, not a
  "site" setting (matching the terminology fix in 1.2.16). No functional
  change.

## 1.2.16
- Corrected the "Default" option's label text (was "Default (site
  setting)", now "Default (administrator setting)") — it resets to
  Joomla's administrator-client default language, not the site
  (frontend) default, and the previous wording implied the wrong
  source. See plg_system_fgbackendlangswitcher 1.2.16 for the matching
  functional fix (temporary mode was actually resolving to the wrong
  language).

## 1.2.15
- Fixed the Joomla **update server** (`updates.xml`), not the extension
  code itself: added a missing `<client>site</client>` tag to the
  `<update>` entry. Packages (like plugins) have `client_id = 0` in
  `#__extensions`. Without an explicit `<client>` tag, Joomla's update
  matching defaults to `client_id = 1` (administrator) and silently never
  matches this package — the site fetches `updates.xml` successfully
  (visible via an updated `last_check_timestamp`) but no update ever
  appears under System → Update → Extensions, with no error anywhere.
  Confirmed via `#__extensions.client_id = 0` for this package on a real
  install where a companion plugin (which already had `<client>site</client>`)
  updated correctly on the same server, ruling out network/hosting causes.

## 1.2.14
- When saving `admin_language` to the profile fails, and Joomla's Debug
  System (`$app->get('debug')`) is enabled, the enqueued error message
  now also shows the specific exception/validation detail (already
  logged since 1.1.9) — not just the generic message. Only shown when
  debug mode is explicitly on, which the admin already opted into.

## 1.2.13
- Documentation: added a "Compatibility" note (README, and a description
  on the "Display style" field in the module settings) explaining that
  the dropdown style is visually tailored to Joomla's default Atum admin
  template and may not look native in a third-party admin template — the
  inline style (plain Bootstrap badges) is the safer choice there. No
  functional code change.

## 1.2.12
- **Rebrand.** Renamed to the FG series: element `mod_backendlangswitcher`
  → `mod_fgbackendlangswitcher`, namespace `Fero\Module\BackendLangSwitcher`
  → `FG\Module\Fgbackendlangswitcher`, helper class `BackendLangSwitcherHelper`
  → `FgbackendlangswitcherHelper`, display name → "FG Backend LangSwitcher".
  Functionally identical to 1.2.11. Because the element name changed, this
  is a *new* extension as far as Joomla is concerned — installing it does
  **not** upgrade an existing `mod_backendlangswitcher` install; the old
  one must be uninstalled separately if present.

## 1.2.11
- The package (`pkg_fgbackendlangswitcher/script.php`, not the module's
  own code) now automatically creates a published instance of this module
  in the "status" position, visible on every admin page, on **first
  install only**. Does nothing on package update, and does nothing if an
  instance already exists (e.g. created manually before this version).

## 1.2.10
- Fix: the module no longer renders on the admin login screen
  (`/administrator` with no session). It used to appear there, but
  clicking a language silently did nothing — `processSwitch()` always
  no-ops for a guest (not-logged-in) identity, since there's no one to
  switch a language for. The dispatcher now skips `processSwitch()` and
  `getLanguages()` entirely for a guest identity, and the module doesn't
  render (via the template's existing `empty($languages)` guard).

## 1.2.9
- The active language in the dropdown is now a non-clickable `<span>`
  instead of an `<a>` (already the case in the inline style). Previously
  you could click the already-active language and re-trigger a switch —
  no visible change, but in temporary mode it needlessly created/refreshed
  the session value. Unified behaviour with the inline variant.

## 1.2.8
- Cosmetic: `hasCustomSelection()` no longer treats the bare
  `DEFAULT_MARKER` session value (with no profile language set) as a
  "custom selection". Previously, after clicking "Default" in temporary
  mode with no profile language, the "Default" menu entry stayed visible
  even though there was nothing left to override. No functional change.

## 1.2.7
- Comment-only fix: corrected an inaccurate note on `logError()` — the
  log file doesn't always go to `administrator/logs/`, but to whatever
  directory is configured as `log_path` (System → Global Configuration →
  Server → Log Path), which only defaults to `administrator/logs/`.

## 1.2.6
- Error branches in `processSwitch()` (invalid CSRF token, plugin
  disabled in temporary mode, language not installed, profile save
  failure) now do a clean redirect (`redirectClean()`) after showing the
  message, same as a successful switch. Previously `blswitch` and the
  CSRF token stayed in the URL, so refreshing the page could repeat the
  same (failed) operation or show the same message again. The silent
  guest/no-user check is unchanged (nothing is shown there).

## 1.2.5
- Fixed the module description (`MOD_FGBACKENDLANGSWITCHER_XML_DESCRIPTION`,
  both .ini and .sys.ini, en-GB and sk-SK) — since 1.1.0 the default
  behaviour is a temporary switch (`persist=0`), but the description still
  claimed the choice is always saved to `admin_language`. Text now matches
  actual default behaviour (temporary or permanent, depending on setting).

## 1.2.4
- Reverted the architectural change from 1.1.8: the plugin no longer
  references the module's class directly
  (`use Fero\Module\...\BackendLangSwitcherHelper`), and again has its own
  duplicated `SESSION_KEY` and `DEFAULT_MARKER` constants. Reason: the
  system plugin runs on every admin request (`onAfterInitialise`) — a hard
  dependency on another extension's class (which could be uninstalled,
  broken, or have a changed namespace) risks a fatal error before the
  admin panel even loads. One stable one-line constant is cheaper upkeep
  than that blast radius.

## 1.2.3
- Fix: choosing "Default (site setting)" in **temporary** mode now stores
  an explicit marker (`DEFAULT_MARKER`) in the session instead of simply
  clearing the key. Previously, if the user had a personal `admin_language`
  permanently set on their profile (e.g. `en-GB`) while the module was in
  temporary mode, choosing "Default" just cleared the session key — Joomla
  then fell back to the profile language (`en-GB`) on the next request,
  not the site's actual default admin language. The plugin
  (`plg_system_fgbackendlangswitcher` 1.2.3) now recognises the marker and
  explicitly switches to `$app->get('language')`.

## 1.2.2
- Removed the flag-display feature added in 1.2.0/1.2.1 — reverted to
  flag-free on request.

## 1.2.1
- Flags switched from Unicode emoji to real SVG files bundled with the
  module (`media/flags/4x3/`, MIT-licensed, flag-icons project). Reason:
  Windows deliberately does not render flag emoji as colour glyphs, only
  the raw country-code text.

## 1.2.0
- Added an optional flag next to the language name ("Show flag"
  parameter), originally via Unicode emoji derived from the tag's region
  code.

## 1.1.9
- On failure to save `admin_language` to the profile, the error is now
  logged via `Joomla\CMS\Log\Log` to a dedicated log file
  (`mod_fgbackendlangswitcher.php` in the configured log directory),
  independent of the site's global logging configuration.

## 1.1.8
- Removed the duplicated `SESSION_KEY` constant — the plugin referenced it
  directly from the module's helper instead of keeping its own copy.
  (Reverted in 1.2.4, see above.)

## 1.1.7
- The `PluginHelper::isEnabled()` check for temporary-mode switching is
  now cached per request (a `static` variable in the helper).

## 1.1.6
- Language list sorting switched from `strcoll()` (depends on the
  server's configured locale) to ICU `Collator` (locale-independent),
  falling back to `strcoll()` if `ext-intl` isn't available.

## 1.1.5
- Fixed a regression from 1.1.3: restored the explicit
  `Factory::$language` assignment in the system plugin during a temporary
  language switch — without it, the admin left-hand menu (mod_menu)
  stayed untranslated, since several older core parts still read the
  language via the legacy `Factory::getLanguage()`.

## 1.1.4
- The plugin now also loads the `joomla` language domain (core admin UI
  strings), not just `lib_joomla`, when applying a temporary switch.

## 1.1.3
- Removed (and later restored in 1.1.5) the manual
  `Factory::$language` assignment in the system plugin — turned out to be
  a regression, see 1.1.5.

## 1.1.2
- Added an update server (`<updateservers>` in the package manifest) for
  updates directly through the Joomla admin.

## 1.1.1
- The plugin gained an installer script (`script.php`) that automatically
  enables it on first install.

## 1.1.0
- Added a "Save permanently to profile" (`persist`) parameter. By default
  the language switch is now temporary (session-only, applied via the
  companion system plugin `plg_system_fgbackendlangswitcher`) — before
  this version the only option was permanently saving to the user's
  profile.

## 1.0.3
- Added a "Icon CSS class" (`icon`) parameter — the dropdown button's icon
  can now be changed without touching code.

## 1.0.2
- Dropdown markup reworked to match Atum's "header-item-content" pill
  style (same visual treatment as the other items in the status position).

## 1.0.1
- Fixed "Invalid field: Display style" on saving the module — removed the
  unsupported `validate="options"` attribute from the `style` field.

## 1.0.0
- First version: native Joomla 6 module (namespaced, service provider,
  `HelperFactory`, `ModuleDispatcherFactory`), dropdown/inline
  administrator language switcher, permanent save to `admin_language`.
