# Penn Alumni Website — WordPress prototype

This is the **WordPress option** for the new alumni.upenn.edu, built alongside the Experience Cloud
option so the two can be compared. It is a separate copy. Nothing here changes
`Penn Alumni Website - Build`. That folder stays the source of truth for content and design.

**What it is:** a WordPress block theme (`penn-alumni/`) that ships the same Section Library as the
Experience Cloud spec, as WordPress blocks. It also includes 11 proof-slice pages converted from the
approved HTML prototype. Pages use the same stylesheet (`pennDesignTokens.css`, copied unchanged),
so they look the same as the prototype.

**What it needs:** WordPress 6.5+ and PHP 8.0+. It has **no third-party plugins**, so there is
nothing for ISC to license or approve beyond the theme itself.

---

## 1. See it (no hosting needed): WordPress Playground

WordPress Playground runs a complete WordPress site inside your browser from a link. It needs no
account, no install and no server. Anyone with the link gets their own copy. They can click around
the site *and* open the editor. Changes reset when the tab closes, which makes it a safe sandbox
for stakeholders.

**One-time setup.** Publish this folder to GitHub as a **public** repo named `Penn-Alumni-WordPress`:

1. Open this folder in VS Code. Click **Source Control**, then **Publish to GitHub**, then choose **Public**.
   (Or create the repo on github.com and push.)
2. Share this link:

```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/koconnor219/Penn-Alumni-WordPress/main/blueprint.json
```

The link reads `blueprint.json`. That file installs the theme from GitHub, imports the pages and
menus, and logs you in as an admin. The first load takes about 20–40 seconds. If you use a
different repo name, update the URL in both `blueprint.json` and the link.

**What to try in the editor:**
- Go to **Pages**, open **Homecoming Weekend**, and click any text to change it.
- Click a section. In the right sidebar, change its **Background** (White / Cream / Blue / Red).
- Click a card. Change its **Card style**, **Icon** or **Link**. Drag cards to reorder them.
- Choose **+** then **Patterns**, then **Penn · Sections**, to add an approved section.
- Choose **Pages**, then **Add New**. Pick **Reunion class page** to start a new reunion page from the template.
- Go to **Appearance → Menus** to edit the top navigation. Each top item's *Description* is the dropdown intro text; turn it on under **Screen Options**.
- Go to **Settings → Penn Alumni** for the footer contact block, the Blackthorn Org ID and the Form Assembly address.

## 2. What's in the proof slice

| Page | URL | Shows |
|---|---|---|
| Home | `/` | Video hero, stats, event feed, stories, MyPenn tiles, cards |
| Events Calendar | `/events/` | **Blackthorn embed block** (shows sample events until the Org ID is set) |
| Alumni Weekend | `/events/alumni-weekend/` | Long event page, schedule, school contacts |
| 25th Reunion | `/events/alumni-weekend/25th-reunion/` | **Reunion template**: one layout, reused for every class |
| Homecoming Weekend | `/events/homecoming/` | On-this-page nav, schedule, cards, people |
| Communities | `/communities/` | Landing page |
| Regional Clubs | `/communities/regional-clubs/` | **Club directory block**: 123 clubs from one data file, search + region filters |
| Learn & Network | `/learn/` | Landing page |
| Alumni Education | `/learn/alumni-education/` | Standard program page |
| About | `/about/` | Section nav, staff directory, publications, awards |
| Contact Us | `/about/contact/` | **Form Assembly block** |

URLs for every page in the sitemap are in `docs/url-map.csv`. Use the same URLs whichever platform
wins, so links don't break twice.

## 3. How the pieces map to the component library

| Component library (Experience Cloud) | WordPress block | Editor sets |
|---|---|---|
| paHeader / paFooter | Site Header / Site Footer | Appearance → Menus; Settings → Penn Alumni |
| paHero | **Page Hero** | Variant, photo or video, text + buttons |
| paSectionNav | **On-this-page Nav** | Nothing: built from each Section's *Nav label* |
| paContent / paCtaBand shell | **Section** | Background, compact spacing, nav label, anchor |
| paCardGrid + paCard | **Card Grid** + **Card** | Columns; card style, icon, image, link |
| paEventFeed | **Event Feed** | Layout, count, filter (event group/category) |
| Blackthorn registration/calendar | **Blackthorn Events Embed** | Event-group or event path |
| Forms (Screen Flow) | **Form Assembly Form** | Form number |
| paDirectory (clubs) | **Regional Club Directory** | Nothing: data in `penn-alumni/data/clubs.txt` |
| paAccordion | Core **Details** block | Question + answer |
| Text, headings, lists, buttons | Core blocks | Button styles: Red, Blue, Outline, White, Text link |

Editors can only insert Penn blocks and a short list of core blocks. Colors and fonts are locked to
the brand palette.

## 4. The two "custom connections"

**Blackthorn.** The *Blackthorn Events Embed* block uses Blackthorn's official advanced embed
(`embed.js`). It auto-resizes and runs registration inside the page. To switch it on:

1. Enter the Org ID under **Settings → Penn Alumni**.
2. Add the site's domains to Blackthorn's embed allowlist: the live domain plus the Pantheon dev and test domains.

It also pushes a `blackthorn_form_submitted` event to the analytics data layer when Blackthorn
reports a submitted form. Test that with a real registration.

The *Event Feed* block (homepage list, cards on program pages) uses sample data today. It is built
so a server-side Salesforce connection can be added later through one filter
(`pa_event_feed_items`) without changing any page. Until then, those spots could also just embed
Blackthorn.

**Form Assembly.** Enter Penn's Form Assembly address once in Settings (e.g.
`https://upenn.tfaforms.net`). Then each *Form Assembly Form* block only needs a form number. Form
fields and notifications are edited in Form Assembly, not on the website.

## 5. Updating content from the HTML prototype

The prototype stays the source. When a page there changes, run:

```
python3 tools/convert.py            # proof slice
python3 tools/convert.py --all      # every page in the sitemap
```

This rewrites `penn-alumni/content/`, the menus and the patterns. Then go to
**Tools → Penn Alumni Starter Content → Import / refresh** in WordPress. That replaces those pages,
so do it *before* editors start making changes in WordPress. After launch, WordPress becomes the
source and this step retires.

`docs/conversion-report.md` lists the sections that came across as *Custom HTML* blocks: schedules,
info boxes, the staff directory filter and similar. They look identical but can only be edited as
HTML. Each one is a candidate for its own block in the full build.

## 6. Moving to Pantheon (when ISC provisions a site)

1. Copy the `penn-alumni/` folder into the Pantheon site's `wp-content/themes/` (Git or SFTP on **Dev**), then activate it.
2. Run **Tools → Penn Alumni Starter Content** once (or `terminus wp <site>.dev -- eval 'pa_import_starter_content();'`).
3. Fill in **Settings → Penn Alumni**.
4. Promote Dev → Test → Live.

Code moves up and content moves down: theme changes go in on Dev, and page edits happen on Live.

## 7. Folder map

```
Penn Alumni Website - WordPress/
├── README.md               ← this file
├── blueprint.json          ← Playground link setup
├── docs/
│   ├── url-map.csv         ← every prototype page → its WordPress URL
│   ├── conversion-report.md
│   └── platform-comparison.md
├── tools/convert.py        ← HTML prototype → WordPress blocks (reads the Build folder, never writes to it)
├── _source-snapshot/       ← one-time copy of the Build folder used for this build (not in Git)
└── penn-alumni/            ← THE THEME (this is what goes on Pantheon)
    ├── theme.json          ← brand palette + fonts, editor guardrails
    ├── assets/css/pennDesignTokens.css   ← same file as the prototype
    ├── assets/css/wp-adapter.css         ← the small WordPress-only layer
    ├── assets/js/pa-site.js              ← same file as the prototype (nav, filters)
    ├── blocks/             ← the Penn blocks (block.json + render.php each)
    ├── patterns/           ← approved sections + page templates
    ├── templates/ parts/   ← page, home, search, 404; header, footer
    ├── data/               ← clubs.txt, menus.json, icons.json, sample-events.json
    ├── content/            ← converted pages (imported by Tools → Starter Content)
    └── inc/                ← setup, settings screen, importer
```
