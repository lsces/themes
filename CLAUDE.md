# Themes Package — Developer Notes

## Top navbar menu
Each package self-registers via `$gBitSystem->registerAppMenu()` in its `bit_setup_inc.php`.
Rendered by `kernel/templates/top_bar.tpl` — no built-in role gate at the dropdown level.
Config switches (stored in kernel_config, set via Themes > Admin > Menus):
- `menu_$pkg = 'n'` — disable dropdown for all users
- `${pkg}_menu_text` — custom dropdown label
- `${pkg}_menu_position` — sort position
For role-based visibility, override `top_bar.tpl` in `config/themes/merg/kernel/`.

## CSS load order
`BitThemes::loadStyleData()` loads CSS in this order:
1. Package CSS (around position 300) — each package's `html_head_inc.tpl`
2. `themes/css/config.css` — position 300 (default); canonical floaticon/icon/actionicon rules
3. `themes/css/base.css` — position 301 (added 2026-08-11); generic site-chrome layer
   (`.dropdown-submenu` nested-menu CSS, floaticon/icon rules, spacing utilities). Loads
   unconditionally for every site — do NOT rely on a site theme's own `@import` for this content,
   that's exactly the gap that caused the myhomecloud bug below.
4. Style CSS (`getStyleCssFile()`, position 998) — the active theme's main CSS
5. Browser CSS (`getBrowserStyleCssFile()`, position 999)

Site-specific CSS lives in `/etc/webstack/domains/{site}/themes/{site}/{site}.css` and is
the active theme file for that site (position 998). Site theme images go in
`themes/{site}/images/` within that domain dir — referenced via `{$gBitThemes->getStyleUrl()}images/`.

**`.floaticon` / `.icon` audit note** — `themes/css/config.css` defines `.floaticon { float:right }`
at position 300. Site theme CSS at position 998 **wins** over this. If a site theme has
`.icon { float:left }` (common in older themes for sprite icon layout), it breaks `.floaticon`
by causing child icons to float left and collapse the container. **Fix**: strip the bare
`.icon { float:left }` from the site CSS — do not scope it or patch it elsewhere.

**`base.css` is still directly `@import`ed by some non-live theme presets** (the 4 legacy
Bootstrap-2-era presets: cerulean, journal, slate, bootstrap under `/etc/webstack/site-config/themes/`)
and by `wiki/templates/slideshow.tpl` (a standalone overlay outside the normal cascade) — don't delete
`base.css` itself, only ever add to it. `themes/css_lib.php`'s legacy `@import`-flattener also
special-cases and never inlines `base.css` — treat it as permanent, load-bearing infrastructure.

## Asset locations
- **Site-specific images** — `/etc/webstack/domains/{site}/themes/{site}/images/`; referenced
  in templates as `{$gBitThemes->getStyleUrl()}images/filename.ext`
- **Cross-site org assets** (RDM logo, cookie-consent images) — `util/images/`; referenced
  as `{$smarty.const.UTIL_PKG_URL}images/filename.ext` or `/util/images/filename.ext` in CSS
- **Fonts** — `util/fonts/{family}/`; referenced as `/util/fonts/...` in `@font-face` CSS
- `config/css/`, `config/js/`, `config/fonts/`, `config/images/` are all dead and removed
  from servers — do not recreate them

## Smarty notes
- `{tr}...{/tr}` for translation in templates; `KernelTools::tra()` in PHP
- `tra` is NOT a Smarty modifier — `"string"|tra` will throw a compiler error
- `{form}` block plugin auto-injects `<input type="hidden" name="tk">` (CSRF ticket)
- **`form-search` hides Bootstrap submit buttons** — Bootstrap 2 `.form-search` suppresses
  the submit control. Use `class="minifind"` alone on floaticon filter forms; never add `form-search`.
- `{strip}` removes whitespace between HTML tags; keep `&bull;` separators inside
  valid `<li>` elements to avoid detachment
- **`{textarea}` / CKEditor** — omit `id`; the plugin defaults to `LIBERTY_TEXT_AREA` which
  is what CKEditor searches for. Passing a custom `id` breaks CKEditor attachment.
  Do NOT wrap `{textarea}` in an outer `form-group`/`{formlabel}`/`{forminput}` — it
  provides its own via `edit_textarea.tpl`. Pass `label="..."` directly to `{textarea}`.
  Default field name is `edit`; PHP reads `$_POST['edit']`. Do not pass `name=` unless
  you also change the PHP to match. PHP must call `invokeServices('content_edit_function')`
  for CKEditor to load.
- Per-site footer scripts belong in `kernel/footer_inc.tpl`, NOT `kernel/footer.tpl`.
  `footer_inc.tpl` is picked up by the `mAuxFiles` loop in `html.tpl` reliably.
  `footer.tpl` as a theme override only loads if the active style matches exactly —
  fragile and easy to miss.
- **`theme_setup_inc.php`** — optional site-specific PHP loaded by `themes/includes/bit_setup_inc.php`
  if `CONFIG_PKG_PATH.'theme_setup_inc.php'` exists. Use it to call `$gBitThemes->loadJavascript()`
  for JS that is only needed on one site (e.g. roundabout on rainbowdigitalmedia, haccordion on medw).
  Source lives in `/etc/webstack/domains/{site}/theme_setup_inc.php`; `setup-site-links.sh` symlinks
  it into `config/theme_setup_inc.php` automatically if present.

## Module / Layout system
Modules are placed in layout areas via the `THEMES_LAYOUTS` table:
- `LAYOUT_AREA` — column position: `t` (top), `l` (left), `r` (right), `b` (bottom)
- `POS` — sort order within the column
- `MODULE_RSRC` — the Smarty template to render, e.g. `bitpackage:kernel/mod_top_banner.tpl`
- `ROLES` — role_id that can see this module (empty = all users); use `-1` for anonymous-only

The 't' column is rendered inside `<header id="bw-main-header">` via `displayLayoutColumn('t')`.
Bootstrap grid columns (`col-sm-*`) inside modules MUST be wrapped in a `clearfix` parent;
without it, subsequent modules in the same column float up and overlay earlier ones.

Managed via Admin > Themes > Layout. Template includes resolve via `bitpackage:` prefix.

Column visibility is controlled by feature flags in `kernel_config` checked in `BitThemes::loadLayout()`:
- `{display_mode}_hide_{area}_col` (e.g. `edit_hide_left_col`)
- `{package}_hide_{area}_col`
- `{package}_{display_mode}_hide_{area}_col`
The old hardcoded `display_mode != 'edit'` guard in `html.tpl` has been removed — columns now always
follow these flags. All flags off = columns show on all pages including edit pages.

## Standard content header pattern (merg theme)
The merg theme applies `background-color: #5da7e1` to every `<header>` element. The standard
pattern for display pages uses a semantic `<header>` with floaticons and breadcrumbs as
separate included templates (matching the stock assembly pattern):

```html
<header>
    {include file="bitpackage:pkg/foo_icons_inc.tpl"}   {* floaticons — float right *}
    <h1>Title</h1>
    {include file="bitpackage:pkg/foo_breadcrumb_inc.tpl"}   {* <small> trail *}
</header>
```

- `foo_icons_inc.tpl` — contains `<div class="floaticon">` with action icons (edit, delete, etc.)
- `foo_breadcrumb_inc.tpl` — contains `<small>` with `&rsaquo;`-separated ancestor links
- Use `<header>` (semantic element), never `<div class="header">` — the latter gets no blue background
- Do not use a separate `<div class="gallerybar">` or `<nav>` breadcrumb bar above the page;
  integrate breadcrumbs into `<small>` inside `<header>` instead

## pdfjs local patch (themes/js/pdfjs-5.2.133/web/viewer.mjs)
Standard pdfjs handles `#search=term` in the URL hash by highlighting matches silently but
does NOT open the findbar UI. A local patch restores that behaviour. Find the block:

```javascript
if (params.has("search")) {
    const query = params.get("search").replaceAll('"', ""),
```

And insert before the `this.eventBus.dispatch("findfromurlhash", ...)` call:

```javascript
if (query) {
    PDFViewerApplication.findBar.open();
    PDFViewerApplication.findBar.findField.value = query;
}
```

**Re-apply this patch after any pdfjs version upgrade.**

## Icon sets (tango vs tango5)
`util/iconsets/tango/` is the default iconset; `tango5/` is a richer superset. The `{biticon}`
plugin searches the active style first, then falls back to `tango` — it does NOT fall back to
`tango5`. Any icon used in a template that only exists in `tango5/scalable/` must be copied to
`tango/scalable/` too, otherwise it silently goes missing on sites using the tango default.
After copying, add the icon name and purpose to `$iconUsage` in `themes/icon_browser.php`.

## Site-specific theme overrides (/etc/webstack/domains)
Each vhosted site has its theme overrides at `/etc/webstack/domains/{site}/themes/{site}/`.
These are symlinked into each site's `config/themes/{site}` — e.g. on servers:
```
/srv/website/merg/config/themes/merg -> /etc/webstack/domains/merg/themes/merg
```
**Stale as of 2026-08-11** — desktop used to work this way (single shared `bitweaver5/` root,
switched between sites via `switch-site.sh`), but is now genuinely multi-site like the servers:
each `/srv/website/{site}/config/themes/{site}` is its own symlink to
`/etc/webstack/domains/{site}/themes/{site}`, same as the server example above. See
`reference_desktop_site_architecture` memory.

Typical contents: `kernel/` (top_bar.tpl, top_banner.tpl, bot_bar.tpl, etc.), `images/`, site CSS, favicon.
Any template in this path overrides the package default via Smarty's `bitpackage:` resource lookup.
Never edit the `config/themes/` path directly; edit the source in `/etc/webstack/domains/`.

## The `force` theme tier — shared-across-all-sites templates (in use since 2026-08-15)

`themes/smartyplugins/ResourceBitpackage.php`'s `getTplLocations()` checks locations in this
order, returning the first that exists:

1. `config/themes/force/<package>/<subdir><template>` and `config/themes/force/<subdir><template>`
   — **checked before the site theme override**, not between it and the generic default. This
   means a `force` template wins even over a genuine per-site override — it's a hard override
   tier, not a fallback.
2. `<active-style-path>/<package>/<subdir><template>` — the site theme override (the usual
   `config/themes/{site}/...` path described above).
3. the package's own default template (e.g. `kernel/templates/bot_bar.tpl`).

`CONFIG_PKG_PATH` (and so `themes/force/`) is per-site, same as every other `config/` path — so a
genuinely shared "force" template needs the same symlink-fan-out `config/themes/BlueSky` already
uses: `config/themes/force` in every site directory symlinked straight to one shared location,
**`/etc/webstack/site-config/themes/force/`**. Not automated by `setup-site-links.sh` (that script
doesn't touch `config/themes/*` at all, `BlueSky`'s symlink is likewise manual) — wire up new sites
by hand: `ln -sfn /etc/webstack/site-config/themes/force /srv/website/<site>/config/themes/force`.

**First real use**: `bot_bar.tpl` (the RDM-branding footer bar) was hand-copied nearly identically
into 11 site theme dirs, with real accidental drift between copies (`http://` vs `https://`,
`rainbowdigitalmedia.co.uk` vs `.uk`, some missing a `hidden-xs` class or the "LSCES Server
Information" link) — same shape of bug as the `base.css`/`.dropdown-submenu` duplication fixed
2026-08-11. Consolidated into `site-config/themes/force/kernel/bot_bar.tpl`, all 11 per-site
copies deleted. Future edits to this bar happen once, in that one file, and apply everywhere.

**Deploying a `force` template change needs a manual Smarty cache clear** — `/etc/webstack/scripts/
clear-template-cache.sh` on each affected server. The usual "Smarty auto-recompiles changed .tpl
via mtime, no manual clear needed" rule (see Session notes below / Claude memory
`project_smarty_cache`) only covers editing a template that's already resolving from that same
path — it does **not** cover changing *which* file resolves (adding/removing an override, adding a
new symlinked tier like this one). Confirmed the hard way: srv9 kept serving stale menu content
after the `git pull` for this exact reason, until the cache was cleared by hand.

## Session notes

### 2026-08-11 — myhomecloud floaticon/dropdown bug; base.css made generic; colourstrap retired
Second thread, same day as the entry below. Started from a live report: myhomecloud's floaticons
rendered present-but-invisible (yet clickable) and the Administration nested dropdown-submenu never
folded out. Root-caused to two separate gaps, both in `myhomecloud.css`/the wider per-site CSS
pattern:
1. **`.dropdown-submenu` nested-dropdown CSS was never generic** — it was hand-copied byte-for-byte
   into 8 site theme CSS files (`lsces`, `garage-press`, `graham-ovenden`, `phpsurgery`, `rdmcloud`,
   `merg`, `rainbowdigitalmedia`, `medw`) plus `BlueSky.css`, and `myhomecloud.css` simply never got
   the copy-paste. Fixed at the root: `.dropdown-submenu` now lives once in `themes/css/base.css`,
   and `base.css` loads unconditionally for every site at CSS position 301 (new `BitThemes.php` call,
   right after `config.css`) instead of relying on a per-site `@import` opt-in — see CSS load order
   above. The 8 duplicate copies removed; the now-redundant `@import base.css` also removed from the
   4 sites that had it (`medw`, `merg`, `rainbowdigitalmedia`, `timedb`) and from the 4 non-live
   legacy Bootstrap-2 presets (`cerulean`, `journal`, `slate`, `bootstrap`).
2. **`myhomecloud.css`'s own `.floaticon img.icon { padding:0 20px; font-size:18pt; }`** — wildly
   oversized vs the canonical `padding:0 5px 0 0` in config.css/base.css, pushed icons out of the
   floaticon container's visible area while they stayed in-flow and clickable. Confirmed live by
   disabling the rule in devtools before removing it. A duplicate of the generic
   cursor/text-decoration rule was also stripped from the same file.

**Colourstrap retired alongside this**: the standalone `/etc/webstack/site-config/themes/colourstrap/`
theme (full legacy sprite-icon PNG set, ~500 files) deleted — confirmed dead, unused by any of the 9
live domains, referenced by nothing. `BlueSky.css`'s own separate hacked `css/colourstrap.css` copy
(user: "should never have been reloaded in BlueSky") also removed, superseded by loading
`themes/css/bootstrap.css` directly. See `project_colourstrap_cleanup` memory for what's still open
(a dropdown colour regression and a `pkg_`-prefixed icon oversizing issue surfaced by this, both
deliberately deferred — "the themes need a good spring clean... probably a reason for a lot of the
overrides at one time").

Deployed: themes package + webstack repo, both to srv9 then srv10, confirmed live on both by the
user. A separate, unrelated find along the way: the 2026-08-11 desktop machine-awareness
(`IS_LIVE`/`BIT_CACHE_OBJECTS`/`$smarty_force_compile`) config_inc.php changes documented in the
top-level CLAUDE.md session log had been sitting completed-but-uncommitted in `/etc/webstack` since
that session — committed and pushed separately before tonight's actual CSS work, now live too.

### 2026-08-11 — Desktop theme/config catches up to June's server cleanup
Desktop's `config/` had never received the same cleanup the 2026-06-17/18 entry below describes
for servers — `css/`, `fonts/`, `images/`, `js/` (all confirmed dead there back in June) were
still sitting as real directories on desktop for all 9 domains, alongside a `bit_setup_inc.php`
real file. Removed, confirmed absent from srv10 first. Also fixed `config/themes/BlueSky` and
`config/themes/{site}` — both had been real, independently-drifted per-site copies instead of
symlinks (see "Stale as of 2026-08-11" note above and `project_theme_symlink_consolidation`
memory for full detail) — discarding the drifted copies and symlinking to the webstack source
incidentally fixed a real broken-CSS bug on `garage-press`/`graham-ovenden` (an absolute
filesystem path had ended up in a CSS `href`, so their stylesheets were never actually loading).

### 2026-06-22/23 — Per-site JS via theme_setup_inc.php; banner/footer tidies
- rainbowdigitalmedia scrolling banner restored: slide images moved to webstack theme `images/`;
  `mod_banner_rand.tpl` paths updated; roundabout JS load moved from global `bit_setup_inc.php`
  to site `theme_setup_inc.php` (was lost when `config/images/` was removed)
- `theme_setup_inc.php` pattern established: webstack domain dir holds the file,
  `setup-site-links.sh` auto-symlinks it into `config/`; medw and rainbowdigitalmedia both use it
- haccordion.js removed from global `bit_setup_inc.php`; moved to medw `theme_setup_inc.php`
- graham-ovenden `mod_banner_rand.tpl` removed (banner module unused)
- medw `kernel/footer.tpl` renamed to `footer_inc.tpl` for consistency

### 2026-06-17/18 — Theme/asset cleanup
- Site-specific images moved from `config/images/` to per-site webstack theme `images/` folders
- Cross-site org assets (RDM logo, cookie-consent) moved to `util/images/`
- All fonts moved to `util/fonts/`; malformed `@font-face` CSS fixed in graham-ovenden and garage-press
- `config/css/`, `config/js/`, `config/fonts/`, `config/images/` confirmed dead, removed from servers
- phpsurgery theme completed: `top_banner.tpl` (banner div with background-image logo),
  footer CSS to match lsces pattern (lightgrey), Bootstrap navbar replacing old Ink framework
- Navbar centering for anonymous visitors: `{if !$gBitUser->isRegistered()} navbar-center{/if}`
  added to `<ul class="nav navbar-nav">` in graham-ovenden and garage-press `top_bar.tpl`
- Gallery `img.thumb` fix in `themes/css/config.css`: `.fisheye-flow img.thumb { width:100%; height:auto; max-width:none; }` — preserves natural aspect ratios, overrides Bootstrap and per-site constraints
