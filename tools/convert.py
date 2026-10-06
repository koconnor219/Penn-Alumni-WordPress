#!/usr/bin/env python3
"""
HTML prototype  →  WordPress block content.

Reads the approved prototype pages from the sibling "Penn Alumni Website - Build/pages" folder
(never modifies them) and writes WordPress block markup to penn-alumni/content/pages/*.html,
plus penn-alumni/content/pages.json (title, URL, parent) that the theme's importer reads.

Each data-lwc section becomes the matching Penn block (penn/section, penn/hero, penn/grid,
penn/card, penn/event-feed …); text becomes core Paragraph / Heading / List / Buttons blocks with
the Section Library classes kept, so pennDesignTokens.css styles them unchanged. Anything that
has no clean block equivalent yet (forms, filters, schedules, data lists) becomes a Custom HTML
block — it still renders exactly like the prototype and is listed in docs/conversion-report.md.

Usage:  python3 tools/convert.py [--src "../Penn Alumni Website - Build"] [--all]
"""
import argparse, json, os, re, sys
from bs4 import BeautifulSoup, Comment, NavigableString, Tag

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
THEME = os.path.join(ROOT, 'penn-alumni')

# ── URL map: prototype file → WordPress URL. One place to decide URLs for BOTH builds. ──
URLS = {
  'index.html': '/',
  # Events (top nav "Events" goes straight to the calendar)
  '02-events-calendar.html': '/events/', '02-event-registration.html': '/events/register/',
  'events-alumni-weekend.html': '/events/alumni-weekend/', 'events-alumni-weekend-schedule.html': '/events/alumni-weekend/schedule/',
  'events-alumni-weekend-accommodations.html': '/events/alumni-weekend/accommodations/',
  'reunion-70th-75th.html': '/events/alumni-weekend/70th-75th-reunion/',
  '02-interior-homecoming.html': '/events/homecoming/', 'events-homecoming-schedule.html': '/events/homecoming/schedule/',
  'events-award-of-merit.html': '/events/homecoming/award-of-merit/', 'events-inspiring-impact.html': '/events/inspiring-impact/',
  'events-evening-with-penn.html': '/events/evening-with-penn/',
  # Communities
  '03-landing-communities.html': '/communities/', '02-interior-association-of-alumnae.html': '/communities/association-of-alumnae/',
  '02-interior-association-of-alumnae-board.html': '/communities/association-of-alumnae/leadership/',
  'directory-classes.html': '/communities/classes/', 'communities-class-leadership.html': '/communities/classes/leadership/',
  'communities-penn-first-plus.html': '/communities/penn-first-plus/', 'communities-penn-spectrum.html': '/communities/penn-spectrum/',
  'communities-penn-traditions.html': '/communities/penn-traditions/', 'directory-clubs.html': '/communities/regional-clubs/',
  'communities-shared-interest-groups.html': '/communities/shared-interest-groups/', 'communities-young-alumni.html': '/communities/young-alumni/',
  'communities-graduate-school-alumni.html': '/communities/graduate-school-alumni/',
  'communities-trustees-council-of-penn-women.html': '/communities/trustees-council-of-penn-women/',
  'spectrum-james-brister-society.html': '/communities/penn-spectrum/james-brister-society/',
  'spectrum-penn-leadershipq.html': '/communities/penn-spectrum/penn-leadershipq/',
  'spectrum-black-alumni-society.html': '/communities/penn-spectrum/black-alumni-society/',
  'spectrum-asian-alumni-network.html': '/communities/penn-spectrum/asian-alumni-network/',
  'spectrum-association-of-native-alumni.html': '/communities/penn-spectrum/association-of-native-alumni/',
  'spectrum-association-of-latino-alumni.html': '/communities/penn-spectrum/association-of-latino-alumni/',
  'spectrum-penngala.html': '/communities/penn-spectrum/penngala/',
  # Learn & Network
  '03-landing-learn-network.html': '/learn/', '02-interior-alumni-education.html': '/learn/alumni-education/',
  '02-interior-career.html': '/learn/career/', 'learn-career-archives.html': '/learn/career/archives/',
  'learn-ambassador-program.html': '/learn/ambassador-program/', '02-interior-alumni-travel.html': '/learn/travel/',
  '02-travel-tours-listing.html': '/learn/travel/tours/', '02-travel-tour-detail.html': '/learn/travel/tours/sample-tour/',
  'learn-travel-faqs.html': '/learn/travel/faqs/', 'learn-travel-sustainability.html': '/learn/travel/sustainability/',
  # Get Involved
  '02-interior-get-involved.html': '/get-involved/', 'involve-communities-groups.html': '/get-involved/communities-groups/',
  # About
  '02-interior-about.html': '/about/', 'about-governance.html': '/about/governance/', '02-interior-awards.html': '/about/awards/',
  'about-benefits.html': '/about/benefits/', 'about-quaker-gmail.html': '/about/quaker-gmail/',
  'about-quaker-gmail-privacy-policy.html': '/about/quaker-gmail/privacy-policy/', 'about-alumni-leadership.html': '/about/leadership/',
  'about-resources.html': '/about/resources/', 'about-faqs.html': '/about/faqs/', '05-contact.html': '/about/contact/',
  '05-search.html': '/?s=', '05-404.html': '/404/',
}
for n in (5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65):
    URLS[f'reunion-{n}th.html'] = f'/events/alumni-weekend/{n}th-reunion/'

# Pages in the proof slice (built + imported). Order = menu order within a parent.
SLICE = ['index.html', '02-events-calendar.html', 'events-alumni-weekend.html', 'reunion-25th.html', '02-interior-homecoming.html',
         '03-landing-communities.html', 'directory-clubs.html', '03-landing-learn-network.html', '02-interior-alumni-education.html',
         '02-interior-about.html', '05-contact.html']

# Page titles (menus, breadcrumbs, browser tab). Others fall back to the <title> text before "|".
TITLES = {'index.html': 'Home', '02-events-calendar.html': 'Events Calendar', 'events-alumni-weekend.html': 'Alumni Weekend',
          'reunion-25th.html': '25th Reunion · Class of 2002', '02-interior-homecoming.html': 'Homecoming Weekend',
          'directory-clubs.html': 'Regional Clubs', '02-interior-alumni-education.html': 'Alumni Education',
          '02-interior-about.html': 'About', '05-contact.html': 'Contact Us', '03-landing-learn-network.html': 'Learn & Network'}

# Section Library classes that become Custom HTML (no clean block equivalent yet in this prototype).
RAW_CLASSES = {'pa-filters', 'pa-results-count', 'pa-sched', 'pa-infobox', 'pa-facts', 'pa-timeline', 'pa-embed', 'pa-form-grid',
               'pa-linklist', 'pa-tabs', 'pa-quote', 'pa-hero-badges', 'pa-hero-chip', 'pa-dir-grid'}
RAW_TAGS = {'form', 'table', 'dl', 'figure', 'blockquote', 'iframe', 'svg', 'select', 'input', 'textarea', 'nav', 'time', 'video', 'button'}
INLINE = {'a', 'em', 'strong', 'b', 'i', 'br', 'span', 'small', 'sup', 'sub', 'code', 'abbr', 's', 'u', 'mark'}
BTN = {'pa-btn-primary': 'pa-primary', 'pa-btn-secondary': 'pa-secondary', 'pa-btn-ghost': 'pa-ghost', 'pa-btn-ghost-light': 'pa-ghost-light',
       'pa-btn-white': 'pa-white', 'pa-btn-text': 'pa-text'}

report = {}     # page → list of raw-html fallbacks
icons_found = {}


def attrs_json(d):
    """Serialize block attributes the way WordPress does (escapes that keep comments safe)."""
    s = json.dumps(d, ensure_ascii=False, separators=(',', ':'))
    return (s.replace('--', '\\u002d\\u002d').replace('<', '\\u003c').replace('>', '\\u003e')
             .replace('&', '\\u0026').replace('\\"', '\\u0022'))


def blk(name, attrs=None, inner=None):
    a = (' ' + attrs_json(attrs)) if attrs else ''
    if inner is None:
        return f'<!-- wp:{name}{a} /-->'
    return f'<!-- wp:{name}{a} -->\n{inner}\n<!-- /wp:{name} -->'


def classes(el):
    return el.get('class', []) if isinstance(el, Tag) else []


def fix_url(u, page):
    if not u:
        return u
    if u.startswith('../assets/'):
        return '{{theme}}/assets/' + u[len('../assets/'):]
    if u.startswith('mailto:') or u.startswith('tel:') or u.startswith('http') or u.startswith('#'):
        return u
    base, _, frag = u.partition('#')
    if base in URLS:
        return URLS[base] + (('#' + frag) if frag else '')
    if base.endswith('.html'):
        return '/' + base[:-5] + '/' + (('#' + frag) if frag else '')
    return u


def rewrite_urls(el, page):
    for t in ([el] if isinstance(el, Tag) else []) + (el.find_all(True) if isinstance(el, Tag) else []):
        for at in ('href', 'src'):
            if t.has_attr(at):
                t[at] = fix_url(t[at], page)
        if t.has_attr('style') and '../assets/' in t['style']:
            t['style'] = t['style'].replace('../assets/', '{{theme}}/assets/')
        if t.name == 'svg':
            m = [c for c in classes(t) if c.startswith('lucide-')]
            if m:
                icons_found[m[0][7:]] = ''.join(str(c) for c in t.contents).strip()


def inner_html(el):
    return ''.join(str(c) for c in el.contents).strip()


def is_inline_only(el):
    for d in el.descendants:
        if isinstance(d, Tag) and d.name not in INLINE:
            return False
        if isinstance(d, Tag) and d.name == 'a' and d.find(lambda x: isinstance(x, Tag) and x.name == 'a'):
            return False
    return True


def clean_inline(html):
    html = re.sub(r'<!--.*?-->', '', html, flags=re.S)
    html = re.sub(r'<span class="pa-sr-only">.*?</span>', '', html)
    return re.sub(r'\s+', ' ', html).strip()


def raw(el, page, why=''):
    report.setdefault(page, []).append(why or ' '.join(classes(el)) or el.name)
    html = re.sub(r'<!-- DYNAMIC:.*?-->', '', str(el), flags=re.S)
    return blk('html', None, html)


def para(html, cls=None):
    html = clean_inline(html)
    if not html:
        return ''
    c = f' class="{cls}"' if cls else ''
    return blk('paragraph', {'className': cls} if cls else None, f'<p{c}>{html}</p>')


def heading(el):
    lvl = int(el.name[1])
    cls = ' '.join(classes(el))
    a = {}
    if lvl != 2:
        a['level'] = lvl
    if el.get('id'):
        a['anchor'] = el['id']
    if cls:
        a['className'] = cls
    idattr = f' id="{el["id"]}"' if el.get('id') else ''
    return blk('heading', a or None, f'<h{lvl}{idattr} class="wp-block-heading{(" " + cls) if cls else ""}">{clean_inline(inner_html(el))}</h{lvl}>')


def buttons(anchors, extra_cls=None):
    out = []
    for a in anchors:
        style = next((BTN[c] for c in classes(a) if c in BTN), 'pa-primary')
        attrs = {'className': f'is-style-{style}'}
        tgt = ''
        if a.get('target') == '_blank':
            attrs['linkTarget'] = '_blank'
            attrs['rel'] = 'noopener'
            tgt = ' target="_blank" rel="noopener"'
        label = clean_inline(inner_html(a)).replace('<span aria-hidden="true">↗</span>', '↗')
        out.append(blk('button', attrs, f'<div class="wp-block-button is-style-{style}"><a class="wp-block-button__link wp-element-button" href="{a.get("href", "#")}"{tgt}>{label}</a></div>'))
    a = {'className': extra_cls} if extra_cls else None
    return blk('buttons', a, f'<div class="wp-block-buttons{(" " + extra_cls) if extra_cls else ""}">' + '\n'.join(out) + '</div>')


def group(children_blocks, cls, tag='div'):
    a = {}
    if tag != 'div':
        a['tagName'] = tag
    if cls:
        a['className'] = cls
    return blk('group', a or None, f'<{tag} class="wp-block-group{(" " + cls) if cls else ""}">' + '\n'.join(b for b in children_blocks if b) + f'</{tag}>')


def icon_of(el):
    svg = el.find('svg') if isinstance(el, Tag) else None
    if not svg:
        return ''
    m = [c for c in classes(svg) if c.startswith('lucide-')]
    return m[0][7:] if m else ''


def kids(el):
    return [c for c in el.children if not isinstance(c, Comment) and not (isinstance(c, NavigableString) and not c.strip())]


def conv_children(el, page):
    return [b for c in kids(el) for b in [conv(c, page)] if b]


def event_feed_from(el, layout):
    scope = el.find_parent(attrs={'data-filter': True}) or el.find_parent('section')
    filt = (scope.get('data-filter') if scope else '') or ''
    filt = re.sub(r'^.*?=\s*', '', filt)
    n = len(el.find_all('li')) if layout == 'list' else len([c for c in kids(el)])
    a = {'layout': layout, 'count': n or 3}
    if filt:
        a['filter'] = filt
    if layout == 'cards':
        m = [c for c in classes(el) if c.startswith('pa-grid--')]
        a['columns'] = int(m[0][-1]) if m else 3
    return blk('penn/event-feed', a)


def card(el, page):
    cls = classes(el)
    style = next((c[6:] for c in cls if c.startswith('pa-c--') and c != 'pa-c--center'), 'text')
    a = {'cardStyle': style}
    if el.name == 'a' and el.get('href'):
        a['href'] = el['href']
        if el.get('target') == '_blank':
            a['newTab'] = True
    if 'pa-c--center' in cls:
        a['center'] = True
    ic = el.find(class_='pa-c-icon')
    if ic is not None:
        name = icon_of(ic)
        if name:
            a['icon'] = name
        ic.decompose()
    media = el.find(class_='pa-c-media')
    if media is not None:
        img = media.find('img')
        if img is not None:
            a['imageUrl'] = img['src']
        tone = next((c[8:] for c in classes(media) if c.startswith('pa-tone-')), 'a')
        a['tone'] = tone
        badge = media.find(class_='pa-badge')
        if badge is not None:
            a['badge'] = badge.get_text(strip=True)
        media.decompose()
    for x in el.find_all(class_='pa-c-arrow'):
        x.decompose()
    body = el.find(class_='pa-c-body') or el
    inner = []
    for c in kids(body):
        if isinstance(c, NavigableString):
            inner.append(para(str(c)))
        elif c.name in ('h2', 'h3', 'h4'):
            inner.append(heading(c))
        elif is_inline_only(c):
            inner.append(para(inner_html(c), ' '.join(classes(c)) or None))
        else:
            inner.append(conv(c, page))
    return blk('penn/card', a, '\n'.join(b for b in inner if b))


def conv(el, page):
    """Convert one element (inside a section) to block markup."""
    if isinstance(el, Comment):
        return ''
    if isinstance(el, NavigableString):
        t = str(el).strip()
        return para(t) if t else ''
    cls = classes(el)
    tag = el.name

    if tag == 'p' and 'pa-proto-note' in cls and re.match(r'\s*Live (feed from )?Blackthorn', el.get_text()):
        return ''  # the Event Feed block prints its own note
    if el.get('style') and tag != 'section':
        return raw(el, page, 'inline style (background image)')
    if 'pa-embed' in cls and el.find('form') is not None:
        h = el.find(['h2', 'h3'])
        return blk('penn/form-assembly', {'title': h.get_text(strip=True) if h else 'Form'})  # Form Assembly replaces the mock form
    if tag == 'img' and not el.get('style'):
        src, alt = el.get('src', ''), el.get('alt', '')
        return blk('image', None, f'<figure class="wp-block-image"><img src="{src}" alt="{alt}"/></figure>')
    if el.get('data-filter-scope') is not None and el.find(class_='pa-ev-card'):
        return blk('penn/blackthorn', {'label': 'Events calendar'})  # Blackthorn's own calendar UI replaces the mock filters + cards
    if el.get('data-filter-scope') is not None or el.get('data-item') is not None or (set(cls) & RAW_CLASSES) or tag in RAW_TAGS:
        if tag == 'ul' and 'pa-ev-list' in cls:
            return event_feed_from(el, 'list')
        return raw(el, page)
    if tag == 'ul' and 'pa-ev-list' in cls:
        return event_feed_from(el, 'list')
    if 'pa-grid' in cls and el.find(class_='pa-ev-card'):
        return event_feed_from(el, 'cards')
    if 'pa-grid' in cls and el.find(class_='pa-c'):
        cols = next((int(c[-1]) for c in cls if c.startswith('pa-grid--')), 3)
        return blk('penn/grid', {'columns': cols}, '\n'.join(card(c, page) if 'pa-c' in classes(c) else conv(c, page) for c in kids(el)))
    if 'pa-c' in cls:
        return card(el, page)
    if 'pa-btn-row' in cls:
        return buttons([a for a in el.find_all('a') if 'pa-btn' in classes(a) or 'pa-btn-text' in classes(a)])
    if tag == 'a' and ('pa-btn' in cls or 'pa-btn-text' in cls):
        return buttons([el])
    if tag in ('h1', 'h2', 'h3', 'h4', 'h5', 'h6'):
        return heading(el) if is_inline_only(el) else raw(el, page, 'heading with icons')
    if tag == 'p':
        return para(inner_html(el), ' '.join(cls) or None) if is_inline_only(el) else raw(el, page, 'paragraph with icons')
    if tag in ('ul', 'ol'):
        if cls or not all(is_inline_only(li) for li in el.find_all('li')):
            return raw(el, page)
        items = '\n'.join(blk('list-item', None, f'<li>{clean_inline(inner_html(li))}</li>') for li in el.find_all('li', recursive=False))
        a = {'ordered': True} if tag == 'ol' else None
        return blk('list', a, f'<{tag} class="wp-block-list">{items}</{tag}>')
    if tag == 'details':
        summ = el.find('summary')
        body = el.find(class_='pa-acc-body')
        s_html = clean_inline(inner_html(summ)) if summ else ''
        body_blocks = conv_children(body, page) if body is not None and not is_inline_only(body) else [para(inner_html(body))] if body is not None else []
        return blk('details', None, f'<details class="wp-block-details"><summary>{s_html}</summary>' + '\n'.join(b for b in body_blocks if b) + '</details>')
    if 'pa-acc' in cls:
        return group([conv(d, page) for d in el.find_all('details', recursive=False)], 'pa-acc')
    # icon-only span/div → penn/icon
    if tag in ('span', 'div') and el.find('svg') and not el.get_text(strip=True):
        name = icon_of(el)
        if name:
            return blk('penn/icon', {'icon': name, 'className': ' '.join(cls)} if cls else {'icon': name})
    # linked box (tiles etc.)
    if tag == 'a':
        if is_inline_only(el):
            return para(str(el))
        a = {'href': el.get('href', '')}
        if el.get('target') == '_blank':
            a['newTab'] = True
        if cls:
            a['className'] = ' '.join(cls)
        inner = []
        for c in kids(el):
            if isinstance(c, Tag) and c.find('svg') and not c.get_text(strip=True):
                inner.append(conv(c, page))
            elif isinstance(c, Tag) and is_inline_only(c):
                inner.append(para(inner_html(c), ' '.join(classes(c)) or None))
            else:
                inner.append(conv(c, page))
        return blk('penn/link-box', a, '\n'.join(b for b in inner if b))
    if tag in ('div', 'span', 'section', 'article', 'header', 'aside', 'main', 'li'):
        if is_inline_only(el):
            return para(inner_html(el), ' '.join(cls) or None)
        return group(conv_children(el, page), ' '.join(cls) or None)
    return raw(el, page, f'<{tag}>')


def hero(sec, page):
    a = {'variant': sec.get('data-variant', 'page')}
    media = sec.find(class_='pa-hero-media')
    if media is not None:
        ifr = media.find('iframe')
        if ifr is not None:
            a['videoUrl'] = ifr['src']
        else:
            m = re.search(r"url\('?([^')]+)'?\)", media.get('style', ''))
            if m:
                a['mediaUrl'] = m.group(1)
    text = sec.find(class_='pa-hero-text')
    inner = []
    for c in kids(text):
        if isinstance(c, Tag) and 'pa-page-breadcrumb' in classes(c):
            inner.append(blk('penn/breadcrumbs'))
        else:
            inner.append(conv(c, page))
    return blk('penn/hero', a, '\n'.join(b for b in inner if b))


def section(sec, page):
    cls = classes(sec)
    if 'pa-hero' in cls:
        return hero(sec, page)
    if 'pa-secnav' in cls:
        return blk('penn/section-nav')
    if 'pa-block' not in cls:
        return conv(sec, page)
    a = {}
    bg = next((c[6:] for c in cls if c.startswith('pa-bg-')), 'white')
    if bg != 'white':
        a['bg'] = bg
    if 'pa-block--compact' in cls:
        a['compact'] = True
    if sec.get('id'):
        a['anchor'] = sec['id']
    if sec.get('data-nav-label'):
        a['navLabel'] = sec['data-nav-label']
    extra = [c for c in cls if not c.startswith('pa-bg-') and c not in ('pa-block', 'pa-block--compact')]
    inner_el = sec.find(class_='pa-block-inner', recursive=False)
    if sec.get('data-lwc') == 'paDirectory' and sec.get('data-source') == 'Club__c':
        return blk('penn/section', a, blk('penn/club-directory'))
    if inner_el is None:
        a['inner'] = False
        if extra:
            a['className'] = ' '.join(extra)
        return blk('penn/section', a, '\n'.join(conv_children(sec, page)))
    if extra:
        a['className'] = ' '.join(extra)
    inner_extra = [c for c in classes(inner_el) if c != 'pa-block-inner']
    blocks = conv_children(inner_el, page)
    if inner_extra:
        blocks = [group(blocks, ' '.join(inner_extra))]
    return blk('penn/section', a, '\n'.join(blocks))


def convert_page(path, fname):
    soup = BeautifulSoup(open(path, encoding='utf-8').read(), 'html.parser')
    title = (soup.title.string or fname).split('|')[0].strip()
    desc = (soup.find('meta', attrs={'name': 'description'}) or {}).get('content', '')
    main = soup.find('main')
    rewrite_urls(main, fname)
    for c in main.find_all(string=lambda s: isinstance(s, Comment)):
        c.extract()
    out = [section(s, fname) for s in kids(main) if isinstance(s, Tag)]
    return title, desc, '\n\n'.join(b for b in out if b) + '\n'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--src', default=os.path.join(os.path.dirname(ROOT), 'Penn Alumni Website - Build'))
    ap.add_argument('--all', action='store_true', help='convert every page, not only the proof slice')
    args = ap.parse_args()
    pages_dir = os.path.join(args.src, 'pages')
    files = sorted(f for f in os.listdir(pages_dir) if f.endswith('.html')) if args.all else SLICE
    os.makedirs(os.path.join(THEME, 'content', 'pages'), exist_ok=True)
    manifest = []
    for f in files:
        if f in ('component-gallery.html', 'design-system.html', '01-homepage-v1-with-calendar.html', '05-404.html', '05-search.html'):
            continue
        title, desc, content = convert_page(os.path.join(pages_dir, f), f)
        url = URLS.get(f, '/' + f[:-5] + '/')
        slug = 'home' if url == '/' else url.strip('/').split('/')[-1]
        parent = '' if url == '/' or url.count('/') <= 2 else '/' + '/'.join(url.strip('/').split('/')[:-1]) + '/'
        # page title for menus/breadcrumbs: prefer the hero H1 text
        soup = BeautifulSoup(content, 'html.parser')
        h1 = soup.find('h1')
        nice = re.sub(r'\s+', ' ', h1.get_text(' ', strip=True)) if h1 else title
        open(os.path.join(THEME, 'content', 'pages', slug + '.html'), 'w', encoding='utf-8').write(content)
        manifest.append({'source': f, 'title': TITLES.get(f) or title.replace('&amp;', '&'), 'slug': slug, 'url': url, 'parent': parent,
                         'file': slug + '.html', 'description': desc})
        print(f'{f:48s} → {url:45s} {len(report.get(f, []))} HTML blocks')
    manifest.sort(key=lambda m: m['url'].count('/'))
    json.dump(manifest, open(os.path.join(THEME, 'content', 'pages.json'), 'w'), indent=1, ensure_ascii=False)

    # Icons used by the pages → data/icons.json
    ip = os.path.join(THEME, 'data', 'icons.json')
    icons = json.load(open(ip))
    added = {k: re.sub(r'\s+', ' ', v) for k, v in icons_found.items() if k not in icons}
    icons.update(added)
    json.dump(icons, open(ip, 'w'), indent=0)

    # URL map + conversion report
    os.makedirs(os.path.join(ROOT, 'docs'), exist_ok=True)
    with open(os.path.join(ROOT, 'docs', 'url-map.csv'), 'w') as fh:
        fh.write('prototype_file,wordpress_url,in_proof_slice\n')
        for f in sorted(os.listdir(pages_dir)):
            if f.endswith('.html'):
                fh.write(f'{f},{URLS.get(f, "/" + f[:-5] + "/")},{"yes" if f in SLICE else ""}\n')
    with open(os.path.join(ROOT, 'docs', 'conversion-report.md'), 'w') as fh:
        fh.write('# Conversion report\n\nSections that came across as **Custom HTML** blocks (render exactly like the prototype; '
                 'editable as HTML only). Each is a candidate for a dedicated block in the full build.\n\n')
        for f in files:
            if report.get(f):
                fh.write(f'## {f} → {URLS.get(f, "")}\n\n' + ''.join(f'- `{r}`\n' for r in report[f]) + '\n')
    print(f'{len(manifest)} pages · {len(added)} icons added · report in docs/conversion-report.md')
    build_menus(args.src)
    write_patterns()


def build_menus(src):
    """components/header.html + footer.html → penn-alumni/data/menus.json (seeds Appearance → Menus)."""
    hdr = BeautifulSoup(open(os.path.join(src, 'components', 'header.html'), encoding='utf-8').read(), 'html.parser')
    ftr = BeautifulSoup(open(os.path.join(src, 'components', 'footer.html'), encoding='utf-8').read(), 'html.parser')
    def link(a):
        ext = a.get('target') == '_blank'
        return {'title': re.sub(r'\s*↗\s*$', '', a.get_text(' ', strip=True)).strip(), 'url': fix_url(a['href'], ''), 'newTab': ext}
    primary = []
    for li in hdr.select('.pa-nav-links > li'):
        intro = li.select_one('.pa-mega-intro')
        over = intro.select_one('a')
        primary.append({'title': li.select_one('.pa-nav-top').get_text(strip=True), 'url': fix_url(over['href'], ''),
                        'description': intro.select_one('p').get_text(strip=True), 'overviewLabel': over.get_text(strip=True),
                        'children': [link(a) for a in li.select('.pa-mega-links a')]})
    utility = [link(a) for a in hdr.select('.pa-utility-nav a')]
    cols = {}
    for col in ftr.select('.pa-footer-col'):
        t = col.select_one('.pa-footer-title').get_text(strip=True).lower()
        if t != 'contact':
            cols['footer-' + t] = [link(a) for a in col.find_all('a')]
    menus = {'primary': primary, 'utility': utility, **cols}
    json.dump(menus, open(os.path.join(THEME, 'data', 'menus.json'), 'w'), indent=1, ensure_ascii=False)
    print('menus.json:', {k: len(v) for k, v in menus.items()})



# ── Patterns: reusable sections + "start a new page from…" templates, cut from the converted pages ──
SECTION_PATTERNS = [  # slug, title, page file, top-level section index
    ('hero-page', 'Hero · photo band', 'alumni-education', 0),
    ('content-narrow', 'Text · heading left, copy right', 'alumni-education', 1),
    ('content-stats', 'Text + stats', 'home', 1),
    ('content-image', 'Text + image', 'alumni-education', 3),
    ('cards-image-3', 'Cards · 3 with images', 'alumni-education', 2),
    ('cards-icon-4', 'Cards · 4 with icons', 'home', 5),
    ('cards-row', 'Cards · rows with arrows', 'alumni-education', 5),
    ('event-feed', 'Events · upcoming (blue)', 'alumni-education', 4),
    ('events-calendar', 'Events · Blackthorn calendar', 'events', 2),
    ('people-contacts', 'People · contacts', '25th-reunion', 4),
    ('faq', 'FAQ accordion', '25th-reunion', 6),
    ('form', 'Form Assembly form', 'contact', 1),
    ('club-directory', 'Regional club directory', 'regional-clubs', 2),
    ('cta-red', 'Call to action · red band', '25th-reunion', 7),
    ('cta-split', 'Call to action · split, cream', 'alumni-education', 6),
]
PAGE_PATTERNS = [
    ('page-reunion', 'Reunion class page', '25th-reunion', 'Hero, on-this-page nav, welcome, class events, reunion team, stay connected, FAQ, class gift. Change the class year and names.'),
    ('page-interior', 'Program page (interior)', 'alumni-education', 'Hero, intro, program cards, feature, events, links, contact band.'),
    ('page-landing', 'Section landing page', 'communities', 'Hero, intro, three feature cards, list of groups, events, call to action.'),
]


def write_patterns():
    pdir = os.path.join(THEME, 'patterns')
    os.makedirs(pdir, exist_ok=True)
    php_url = "<?php echo esc_url( get_template_directory_uri() ); ?>"
    def body(s):
        return s.replace('{{theme}}', php_url)
    for slug, title, page, idx in SECTION_PATTERNS:
        parts = [p for p in open(os.path.join(THEME, 'content', 'pages', page + '.html')).read().split('\n\n') if p.strip()]
        open(os.path.join(pdir, slug + '.php'), 'w').write(
            f"<?php\n/**\n * Title: {title}\n * Slug: penn/{slug}\n * Categories: penn-sections\n * Description: From the {page} page.\n */\n?>\n" + body(parts[idx]) + '\n')
    for slug, title, page, desc in PAGE_PATTERNS:
        content = open(os.path.join(THEME, 'content', 'pages', page + '.html')).read()
        open(os.path.join(pdir, slug + '.php'), 'w').write(
            f"<?php\n/**\n * Title: {title}\n * Slug: penn/{slug}\n * Categories: penn-pages\n * Block Types: core/post-content\n * Post Types: page\n * Description: {desc}\n */\n?>\n" + body(content))
    print(f'patterns: {len(SECTION_PATTERNS)} sections + {len(PAGE_PATTERNS)} page templates')


if __name__ == '__main__':
    main()
