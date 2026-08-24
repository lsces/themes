# Themes Package — Reference Manual

How the package actually works today. For the history of *why* — decisions, bugs found, wrong
turns — see `CLAUDE.md`'s dated session log instead; this file only tracks current behaviour.

## Top navbar menu

Each package self-registers via `$gBitSystem->registerAppMenu()` in its `bit_setup_inc.php`.
Rendered by `kernel/templates/top_bar.tpl` — no built-in role gate at the dropdown level.
Config switches (stored in `kernel_config`, set via Themes > Admin > Menus):
- `menu_$pkg = 'n'` — disable dropdown for all users
- `${pkg}_menu_text` — custom dropdown label
- `${pkg}_menu_position` — sort position

For role-based visibility, override `top_bar.tpl` in `config/themes/merg/kernel/`.

## CSS load order

`BitThemes::loadStyleData()` loads CSS in this order:
1. Package CSS (around position 300) — each package's `html_head_inc.tpl`
2. `themes/css/config.css` — position 300 (default); canonical floaticon/icon/actionicon rules
3. `themes/css/base.css` — position 301; generic site-chrome layer (`.dropdown-submenu`
   nested-menu CSS, floaticon/icon rules, spacing utilities). Loads unconditionally for every
   site — do NOT rely on a site theme's own `@import` for this content, that gap is what caused
   a real myhomecloud bug (see `CLAUDE.md`'s 2026-08-11 entry).
4. Style CSS (`getStyleCssFile()`, position 998) — the active theme's main CSS
5. Browser CSS (`getBrowserStyleCssFile()`, position 999)

Site-specific CSS lives in `/etc/webstack/domains/{site}/themes/{site}/{site}.css` and is the
active theme file for that site (position 998). Site theme images go in
`themes/{site}/images/` within that domain dir — referenced via
`{$gBitThemes->getStyleUrl()}images/`.

**`.floaticon` / `.icon` conflict**: `themes/css/config.css` defines `.floaticon { float:right
}` at position 300. Site theme CSS at position 998 **wins** over this. If a site theme has
`.icon { float:left }` (common in older themes for sprite icon layout), it breaks `.floaticon`
by causing child icons to float left and collapse the container. **Fix**: strip the bare
`.icon { float:left }` from the site CSS — do not scope it or patch it elsewhere.

**`base.css` is still directly `@import`ed by some non-live theme presets** (the 4 legacy
Bootstrap-2-era presets: cerulean, journal, slate, bootstrap under
`/etc/webstack/site-config/themes/`) and by `wiki/templates/slideshow.tpl` (a standalone
overlay outside the normal cascade) — don't delete `base.css` itself, only ever add to it.
`themes/css_lib.php`'s legacy `@import`-flattener also special-cases and never inlines
`base.css` — treat it as permanent, load-bearing infrastructure.

## Asset locations

- **Site-specific images** — `/etc/webstack/domains/{site}/themes/{site}/images/`; referenced
  in templates as `{$gBitThemes->getStyleUrl()}images/filename.ext`
- **Cross-site org assets** (RDM logo, cookie-consent images) — `util/images/`; referenced
  as `{$smarty.const.UTIL_PKG_URL}images/filename.ext` or `/util/images/filename.ext` in CSS
- **Fonts** — `util/fonts/{family}/`; referenced as `/util/fonts/...` in `@font-face` CSS
- `config/css/`, `config/js/`, `config/fonts/`, `config/images/` are all dead and removed from
  servers — do not recreate them

## Smarty notes

- `{tr}...{/tr}` for translation in templates; `KernelTools::tra()` in PHP
- `tra` is NOT a Smarty modifier — `"string"|tra` will throw a compiler error
- `{form}` block plugin auto-injects `<input type="hidden" name="tk">` (CSRF ticket)
- **`form-search` hides Bootstrap submit buttons** — Bootstrap 2 `.form-search` suppresses the
  submit control. Use `class="minifind"` alone on floaticon filter forms; never add
  `form-search`.
- `{strip}` removes whitespace between HTML tags; keep `&bull;` separators inside valid `<li>`
  elements to avoid detachment
- **`{textarea}` / CKEditor** — omit `id`; the plugin defaults to `LIBERTY_TEXT_AREA` which is
  what CKEditor searches for. Passing a custom `id` breaks CKEditor attachment. Do NOT wrap
  `{textarea}` in an outer `form-group`/`{formlabel}`/`{forminput}` — it provides its own via
  `edit_textarea.tpl`. Pass `label="..."` directly to `{textarea}`. Default field name is
  `edit`; PHP reads `$_POST['edit']`. Do not pass `name=` unless you also change the PHP to
  match. PHP must call `invokeServices('content_edit_function')` for CKEditor to load.
- **Delete confirmation — two conventions, pick by whether the confirm step needs extra input.**
  Plain `onclick="return confirm('...')"` on the link (or `{smartlink}`'s `ionclick` param, a
  direct pass-through to the same attribute) is the default: one click, a native browser popup,
  no extra page load. Used throughout kernel/theme admin UI (`kernel/templates/
  admin_menu_options.tpl`, `themes/templates/module_config_inc.tpl`/`module_config_role_inc.tpl`,
  `admin_layout_overview.tpl`) and, as of 2026-08-24, food/stock/contact's own record-delete
  icons. Reach for `$gBitSystem->confirmDialog()` (kernel `BitSystem.php` — full-page
  `kernel/confirm.tpl` render, a `cancel`/`confirm` request-param dance in the PHP handler)
  **only** when the confirmation itself needs to collect something beyond yes/no — the one
  legitimate live example is `stock/edit_assembly.php`'s delete, which asks whether to also
  recurse into sub-assemblies. Using `confirmDialog()` for a plain yes/no (found and fixed in
  `stock/edit_component.php`/`edit_movement.php` the same day) is unearned complexity: an extra
  full page load for no extra information. The opposite failure — no confirmation at all — is
  worse: `contact/edit.php`'s Delete Contact fired `expunge=1` immediately on click with neither
  pattern in place until fixed 2026-08-24.
- Per-site footer scripts belong in `kernel/footer_inc.tpl`, NOT `kernel/footer.tpl`.
  `footer_inc.tpl` is picked up by the `mAuxFiles` loop in `html.tpl` reliably. `footer.tpl` as
  a theme override only loads if the active style matches exactly — fragile and easy to miss.
- **`theme_setup_inc.php`** — optional site-specific PHP loaded by
  `themes/includes/bit_setup_inc.php` if `CONFIG_PKG_PATH.'theme_setup_inc.php'` exists. Use it
  to call `$gBitThemes->loadJavascript()` for JS that is only needed on one site (e.g.
  roundabout on rainbowdigitalmedia, haccordion on medw). Source lives in
  `/etc/webstack/domains/{site}/theme_setup_inc.php`; `setup-site-links.sh` symlinks it into
  `config/theme_setup_inc.php` automatically if present.

## Module / Layout system

Modules are placed in layout areas via the `THEMES_LAYOUTS` table:
- `LAYOUT_AREA` — column position: `t` (top), `l` (left), `r` (right), `b` (bottom)
- `POS` — sort order within the column
- `MODULE_RSRC` — the Smarty template to render, e.g. `bitpackage:kernel/mod_top_banner.tpl`
- `ROLES` — role_id that can see this module (empty = all users); use `-1` for anonymous-only

The 't' column is rendered inside `<header id="bw-main-header">` via
`displayLayoutColumn('t')`. Bootstrap grid columns (`col-sm-*`) inside modules MUST be wrapped
in a `clearfix` parent; without it, subsequent modules in the same column float up and overlay
earlier ones.

Managed via Admin > Themes > Layout. Template includes resolve via `bitpackage:` prefix.

Column visibility is controlled by feature flags in `kernel_config`, checked in
`BitThemes::loadLayout()`:
- `{display_mode}_hide_{area}_col` (e.g. `edit_hide_left_col`)
- `{package}_hide_{area}_col`
- `{package}_{display_mode}_hide_{area}_col`

All flags off = columns show on all pages including edit pages.

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

## pdfjs local patch (`themes/js/pdfjs-5.2.133/web/viewer.mjs`)

Standard pdfjs handles `#search=term` in the URL hash by highlighting matches silently but does
NOT open the findbar UI. A local patch restores that behaviour. Find the block:

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

## Site-specific theme overrides (`/etc/webstack/domains`)

Each vhosted site has its theme overrides at `/etc/webstack/domains/{site}/themes/{site}/`,
symlinked into that site's `config/themes/{site}` — same layout on desktop, srv9, and srv10:
```
/srv/website/merg/config/themes/merg -> /etc/webstack/domains/merg/themes/merg
```

Typical contents: `kernel/` (top_bar.tpl, top_banner.tpl, bot_bar.tpl, etc.), `images/`, site
CSS, favicon. Any template in this path overrides the package default via Smarty's
`bitpackage:` resource lookup. Never edit the `config/themes/` path directly; edit the source
in `/etc/webstack/domains/`.

## The `force` theme tier — shared-across-all-sites templates

`themes/smartyplugins/ResourceBitpackage.php`'s `getTplLocations()` checks locations in this
order, returning the first that exists:

1. `config/themes/force/<package>/<subdir><template>` and
   `config/themes/force/<subdir><template>` — **checked before the site theme override**, not
   between it and the generic default. A `force` template wins even over a genuine per-site
   override — it's a hard override tier, not a fallback.
2. `<active-style-path>/<package>/<subdir><template>` — the site theme override (the usual
   `config/themes/{site}/...` path described above).
3. the package's own default template (e.g. `kernel/templates/bot_bar.tpl`).

`CONFIG_PKG_PATH` (and so `themes/force/`) is per-site, same as every other `config/` path — so
a genuinely shared "force" template needs the same symlink-fan-out `config/themes/BlueSky`
already uses: `config/themes/force` in every site directory symlinked straight to one shared
location, **`/etc/webstack/site-config/themes/force/`**. Not automated by
`setup-site-links.sh` (that script doesn't touch `config/themes/*` at all, `BlueSky`'s symlink
is likewise manual) — wire up new sites by hand:
`ln -sfn /etc/webstack/site-config/themes/force /srv/website/<site>/config/themes/force`.

**Deploying a `force` template change needs a manual Smarty cache clear** —
`/etc/webstack/scripts/clear-template-cache.sh` on each affected server. The usual "Smarty
auto-recompiles a changed .tpl via mtime, no manual clear needed" rule only covers editing a
template that's already resolving from that same path — it does **not** cover changing *which*
file resolves (adding/removing an override, adding a new symlinked tier like this one).

## Known limitation / open questions

A dropdown colour regression and a `pkg_`-prefixed icon oversizing issue, both surfaced by the
2026-08-11 colourstrap retirement (see `CLAUDE.md`), remain deliberately deferred — "the themes
need a good spring clean... probably a reason for a lot of the overrides at one time."
