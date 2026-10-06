# Penn Alumni website: two paths to launch

*Draft for the platform decision · October 2026. Both options start from the same approved HTML
prototype, Section Library and sitemap.*

## At a glance

| | **A · Experience Cloud (LWR)** | **B · WordPress on Pantheon (ISC)** |
|---|---|---|
| Where it stands today | Spec complete (component library, requirements brief). Few components built. | Theme + all Section Library blocks built. 11 proof pages converted and running. |
| What's left before launch | Build ~15 LWCs, assemble pages in Experience Builder, Blackthorn path A/B spike, content load | Convert the remaining ~60 pages (scripted), finish the remaining *HTML-only* sections, Pantheon setup, Blackthorn allowlist, QA |
| Who builds it | Vendor (SOW) or a Salesforce developer | Penn Alumni + Claude; a WordPress developer only for review/hardening |
| Realistic for Nov 18? | At risk: depends on vendor start date and the Blackthorn spike | Achievable if ISC provisions the Pantheon site within ~2 weeks |
| Day-to-day editing | Experience Builder (drag components, edit properties) | WordPress editor (same components as blocks; Menus; Settings screen) |
| Who can edit without a developer | Content, yes; new section types need an LWC developer | Content, yes; new section types need a small theme change |
| Look & feel | Same stylesheet | Same stylesheet (verified: rendered pages match the prototype) |
| Events | Native Blackthorn components and Salesforce data in the page | Blackthorn's official embed (calendar + registration inside the page); styling inside the frame comes from Blackthorn's theme settings |
| Live Salesforce data in pages (event feeds, people lists) | Native | Embed now; a server-side Salesforce connection later if wanted |
| Forms | Screen Flow or Form Assembly | Form Assembly block |
| Logged-in / personalized experience | Possible later (same platform as Salesforce) | Not in scope; would need SSO work |
| Hosting, security, uptime | Salesforce | Pantheon via ISC (central support) |
| Ongoing cost | Salesforce licensing (already in place) + developer time for component changes | ISC/Pantheon hosting + occasional WordPress developer time |

## What each path is betting on

**A** bets that keeping everything inside Salesforce is worth a slower, developer-dependent build.
That pays off most if the site later needs logged-in, personalized features.

**B** bets that the site is mainly content plus event registration. It also bets that editing
independence and the November date matter more than native Salesforce data on the page.

## Risks to name honestly

- **B:** Blackthorn embed styling, and payments inside the frame (test a paid registration on an
  iPhone early); ISC/Pantheon onboarding time; staff directory, schedules and info boxes are still
  HTML-only in the prototype; two systems to keep visually aligned (site + Blackthorn).
- **A:** Vendor timeline and cost; Blackthorn Path A vs B still unresolved; every new section type is
  an LWC change.
- **Both:** Content approval speed. Redirects from iModules URLs aren't scoped yet. Use the same
  URLs (`docs/url-map.csv`) whichever path wins.

## Questions for ISC (decide B's feasibility)

1. Can we use a custom theme on Pantheon, and is there a Penn starter site (custom upstream)?
2. How long does it take to provision a site, and who approves it?
3. How do editors log in (PennKey)? Who handles DNS for alumni.upenn.edu?
4. Do we get Git access and a staging (Test) environment?
