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
3. Style CSS (`getStyleCssFile()`, position 998) — the active theme's main CSS
4. Browser CSS (`getBrowserStyleCssFile()`, position 999)

Site-specific CSS lives in `/etc/webstack/domains/{site}/themes/{site}/{site}.css` and is
the active theme file for that site (position 998). Site theme images go in
`themes/{site}/images/` within that domain dir — referenced via `{$gBitThemes->getStyleUrl()}images/`.

**`.floaticon` / `.icon` audit note** — `themes/css/config.css` defines `.floaticon { float:right }`
at position 300. Site theme CSS at position 998 **wins** over this. If a site theme has
`.icon { float:left }` (common in older themes for sprite icon layout), it breaks `.floaticon`
by causing child icons to float left and collapse the container. **Fix**: strip the bare
`.icon { float:left }` from the site CSS — do not scope it or patch it elsewhere.

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
On the desktop, `bitweaver5/config/themes/` holds symlinks for ALL sites (since the desktop
switches between sites by changing the DB in `config_inc.php`).

Typical contents: `kernel/` (top_bar.tpl, top_banner.tpl, bot_bar.tpl, etc.), `images/`, site CSS, favicon.
Any template in this path overrides the package default via Smarty's `bitpackage:` resource lookup.
Never edit the `config/themes/` path directly; edit the source in `/etc/webstack/domains/`.

## Session notes

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
