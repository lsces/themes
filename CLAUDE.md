# Themes Package — Developer Notes

Dated history: decisions, bugs found, why things are the way they are, open follow-ups. For the
current architecture/reference — CSS load order, Smarty conventions, the `force` template tier,
module/layout system — see `MANUAL.md` instead; this file only tracks how it got there.

## 2026-08-15 — `force` theme tier built, first real use: `bot_bar.tpl` consolidation

Built the `force` template tier (see `MANUAL.md`) to fix a real, recurring drift problem: the
RDM-branding footer bar (`bot_bar.tpl`) had been hand-copied nearly identically into 11 site
theme dirs, with real accidental drift between copies (`http://` vs `https://`,
`rainbowdigitalmedia.co.uk` vs `.uk`, some missing a `hidden-xs` class or the "LSCES Server
Information" link) — same shape of bug as the `base.css`/`.dropdown-submenu` duplication fixed
2026-08-11 below. Consolidated into `site-config/themes/force/kernel/bot_bar.tpl`, all 11
per-site copies deleted. Future edits to this bar happen once, in that one file, and apply
everywhere.

**Cache gotcha found the hard way**: srv9 kept serving stale menu content after the `git pull`
for this change, because changing *which* template resolves (adding a new symlinked tier) isn't
covered by Smarty's usual same-path auto-recompile-on-mtime behaviour — only cleared once the
template cache was cleared by hand. Now a documented step in `MANUAL.md`'s `force` tier section.

## 2026-08-11 — myhomecloud floaticon/dropdown bug; base.css made generic; colourstrap retired

Second thread, same day as the entry below. Started from a live report: myhomecloud's
floaticons rendered present-but-invisible (yet clickable) and the Administration nested
dropdown-submenu never folded out. Root-caused to two separate gaps, both in
`myhomecloud.css`/the wider per-site CSS pattern:

1. **`.dropdown-submenu` nested-dropdown CSS was never generic** — it was hand-copied
   byte-for-byte into 8 site theme CSS files (`lsces`, `garage-press`, `graham-ovenden`,
   `phpsurgery`, `rdmcloud`, `merg`, `rainbowdigitalmedia`, `medw`) plus `BlueSky.css`, and
   `myhomecloud.css` simply never got the copy-paste. Fixed at the root: `.dropdown-submenu` now
   lives once in `themes/css/base.css`, loading unconditionally for every site at CSS position
   301 instead of relying on a per-site `@import` opt-in — see `MANUAL.md`'s CSS load order
   section. The 8 duplicate copies removed; the now-redundant `@import base.css` also removed
   from the 4 sites that had it (`medw`, `merg`, `rainbowdigitalmedia`, `timedb`) and from the 4
   non-live legacy Bootstrap-2 presets (`cerulean`, `journal`, `slate`, `bootstrap`).
2. **`myhomecloud.css`'s own `.floaticon img.icon { padding:0 20px; font-size:18pt; }`** —
   wildly oversized vs the canonical `padding:0 5px 0 0` in config.css/base.css, pushed icons
   out of the floaticon container's visible area while they stayed in-flow and clickable.
   Confirmed live by disabling the rule in devtools before removing it. A duplicate of the
   generic cursor/text-decoration rule was also stripped from the same file.

**Colourstrap retired alongside this**: the standalone
`/etc/webstack/site-config/themes/colourstrap/` theme (full legacy sprite-icon PNG set, ~500
files) deleted — confirmed dead, unused by any of the 9 live domains, referenced by nothing.
`BlueSky.css`'s own separate hacked `css/colourstrap.css` copy (user: "should never have been
reloaded in BlueSky") also removed, superseded by loading `themes/css/bootstrap.css` directly.
See `project_colourstrap_cleanup` memory for what's still open (see `MANUAL.md`'s "Known
limitation" note) — "the themes need a good spring clean... probably a reason for a lot of the
overrides at one time."

Deployed: themes package + webstack repo, both to srv9 then srv10, confirmed live on both by the
user. A separate, unrelated find along the way: the 2026-08-11 desktop machine-awareness
(`IS_LIVE`/`BIT_CACHE_OBJECTS`/`$smarty_force_compile`) config_inc.php changes documented in the
top-level CLAUDE.md session log had been sitting completed-but-uncommitted in `/etc/webstack`
since that session — committed and pushed separately before tonight's actual CSS work, now live
too.

## 2026-08-11 — Desktop theme/config catches up to June's server cleanup

Desktop's `config/` had never received the same cleanup the 2026-06-17/18 entry below describes
for servers — `css/`, `fonts/`, `images/`, `js/` (all confirmed dead there back in June) were
still sitting as real directories on desktop for all 9 domains, alongside a `bit_setup_inc.php`
real file. Removed, confirmed absent from srv10 first. Also fixed `config/themes/BlueSky` and
`config/themes/{site}` — both had been real, independently-drifted per-site copies instead of
symlinks — discarding the drifted copies and symlinking to the webstack source incidentally
fixed a real broken-CSS bug on `garage-press`/`graham-ovenden` (an absolute filesystem path had
ended up in a CSS `href`, so their stylesheets were never actually loading). See
`project_theme_symlink_consolidation` memory for full detail; desktop is now genuinely
multi-site like the servers (`reference_desktop_site_architecture` memory).

## 2026-06-22/23 — Per-site JS via theme_setup_inc.php; banner/footer tidies

- rainbowdigitalmedia scrolling banner restored: slide images moved to webstack theme
  `images/`; `mod_banner_rand.tpl` paths updated; roundabout JS load moved from global
  `bit_setup_inc.php` to site `theme_setup_inc.php` (was lost when `config/images/` was
  removed)
- `theme_setup_inc.php` pattern established: webstack domain dir holds the file,
  `setup-site-links.sh` auto-symlinks it into `config/`; medw and rainbowdigitalmedia both use it
- haccordion.js removed from global `bit_setup_inc.php`; moved to medw `theme_setup_inc.php`
- graham-ovenden `mod_banner_rand.tpl` removed (banner module unused)
- medw `kernel/footer.tpl` renamed to `footer_inc.tpl` for consistency

## 2026-06-17/18 — Theme/asset cleanup

- Site-specific images moved from `config/images/` to per-site webstack theme `images/` folders
- Cross-site org assets (RDM logo, cookie-consent) moved to `util/images/`
- All fonts moved to `util/fonts/`; malformed `@font-face` CSS fixed in graham-ovenden and
  garage-press
- `config/css/`, `config/js/`, `config/fonts/`, `config/images/` confirmed dead, removed from
  servers
- phpsurgery theme completed: `top_banner.tpl` (banner div with background-image logo), footer
  CSS to match lsces pattern (lightgrey), Bootstrap navbar replacing old Ink framework
- Navbar centering for anonymous visitors: `{if !$gBitUser->isRegistered()}
  navbar-center{/if}` added to `<ul class="nav navbar-nav">` in graham-ovenden and
  garage-press `top_bar.tpl`
- Gallery `img.thumb` fix in `themes/css/config.css`:
  `.fisheye-flow img.thumb { width:100%; height:auto; max-width:none; }` — preserves natural
  aspect ratios, overrides Bootstrap and per-site constraints
