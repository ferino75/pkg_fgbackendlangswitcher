# Changelog — plg_system_fgbackendlangswitcher

Versions are kept in sync with the `pkg_fgbackendlangswitcher` package
(even in a release where only the plugin, the module, or just the package
infrastructure changed).

## 1.2.15
- No plugin code change (version kept in sync with the package for the
  `<client>site</client>` fix in `updates.xml` — see the module's
  changelog for details).

## 1.2.14
- No plugin change (version kept in sync with the package for a
  debug-mode error detail improvement in the module).

## 1.2.13
- No plugin change (version kept in sync with the package for a
  documentation-only update in the module).

## 1.2.12
- **Rebrand.** Renamed to the FG series: element `backendlangswitcher`
  → `fgbackendlangswitcher`, namespace `Fero\Plugin\System\BackendLangSwitcher`
  → `FG\Plugin\System\Fgbackendlangswitcher`, extension class `BackendLangSwitcher`
  → `Fgbackendlangswitcher`, display name → "System - FG Backend LangSwitcher".
  Functionally identical to 1.2.11. Because the element name changed, this
  is a *new* extension as far as Joomla is concerned — installing it does
  **not** upgrade an existing `plg_system_backendlangswitcher` install;
  the old one must be uninstalled separately if present.

## 1.2.11
- No plugin change (version kept in sync with the package for the new
  package-level installer script that creates the module instance).

## 1.2.10
- No plugin change (version kept in sync with the package for hiding the
  module on the login screen).

## 1.2.9
- No plugin change (version kept in sync with the package for a template
  tweak in the module).

## 1.2.8
- No plugin change (version kept in sync with the package for a cosmetic
  fix in the module).

## 1.2.7
- No plugin change (version kept in sync with the package for a
  comment-only fix in the module).

## 1.2.6
- No plugin change (version kept in sync with the package for clean
  redirects on error in the module).

## 1.2.5
- No plugin change (version kept in sync with the package for a
  description-text fix in the module).

## 1.2.4
- Reverted the architectural change from 1.1.8: the plugin no longer
  references `mod_fgbackendlangswitcher`'s `FgbackendlangswitcherHelper`
  class directly. It has its own private `SESSION_KEY` and
  `DEFAULT_MARKER` constants, value-identical to the module's but without
  a runtime dependency on its class/namespace. Reason: this plugin runs on
  every admin request and must not be able to fatal-error just because the
  module is uninstalled, broken, or has a changed namespace.

## 1.2.3
- Fix (together with mod_fgbackendlangswitcher 1.2.3): the plugin now
  distinguishes the `DEFAULT_MARKER` session value from an ordinary
  language tag. When the session holds the marker (an explicit "Default"
  choice in temporary mode), the plugin uses `$app->get('language')` (the
  site's actual default admin language) instead of ending the request
  with whatever `admin_language` happens to be set on the user's profile.

## 1.2.2 – 1.2.0
- No plugin change.

## 1.1.9
- No plugin change.

## 1.1.8
- Removed the duplicated `SESSION_KEY` constant — referenced it directly
  from `mod_fgbackendlangswitcher`'s
  `FG\Module\Fgbackendlangswitcher\Administrator\Helper\FgbackendlangswitcherHelper::SESSION_KEY`
  instead of keeping its own copy. (Reverted in 1.2.4, see above.)

## 1.1.7 – 1.1.6
- No plugin change.

## 1.1.5
- Fixed a regression from 1.1.3: restored the explicit
  `Factory::$language` assignment during a temporary language switch.
  Several older core parts (e.g. the admin left-hand menu builder,
  `AdministratorMenuHelper`) still read the language via the legacy
  static `Factory::getLanguage()`, not `$app->getLanguage()` — without
  this assignment the menu stayed untranslated.

## 1.1.4
- On a temporary switch, the plugin now also loads the `joomla` language
  domain (core admin UI strings: menu, buttons, JYES/JNO/...), not just
  `lib_joomla`, same as on a normal application bootstrap.

## 1.1.3
- Removed the manual `Factory::$language = $language` assignment
  (assuming `$app->loadLanguage()` would set it internally on its own).
  Turned out to be a wrong assumption — regression fixed in 1.1.5.

## 1.1.2
- No plugin change (package update server added).

## 1.1.1
- Added an installer script (`script.php`) — the plugin now enables
  itself automatically on first install (`postflight`, `install`/
  `discover_install` only, never on update).

## 1.1.0
- First version: a system plugin on `onAfterInitialise` that, on a
  temporary (session) switch from mod_fgbackendlangswitcher, applies the
  chosen administrator language for the current request — the language
  stays in effect until logout, with no write to the user's profile.
