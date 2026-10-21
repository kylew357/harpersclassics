#!/usr/bin/env python3
"""Builds the static website into dist/ from the cars/ and sold/ folders.

GitHub Actions runs this on every push (see .github/workflows/pages.yml), so
adding or removing a car folder updates the live site. You can also run it
yourself:  python3 scripts/build_static.py   (Python 3.8+, no packages needed)
"""
import datetime
import html
import json
import os
import re
import shutil
import subprocess
from urllib.parse import quote

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, 'dist')
IMAGE_EXT = {'.jpg', '.jpeg', '.png', '.webp', '.gif', '.avif', '.svg'}
SPEC_RE = re.compile(r'^([A-Za-z][A-Za-z0-9 /]{0,24}):\s*(.+)$')

NAV = [
    ('home', 'index.html', 'Home'),
    ('inventory', 'inventory.html', 'Inventory'),
    ('sold', 'sold.html', 'Sold'),
    ('about', 'about.html', 'About'),
    ('contact', 'contact.html', 'Contact'),
]


# ---------------------------------------------------------------- helpers
def h(s):
    return html.escape(str(s), quote=True)


def natkey(s):
    return [int(t) if t.isdigit() else t.lower() for t in re.split(r'(\d+)', s)]


def slugify(s):
    return re.sub(r'[^a-z0-9]+', '-', s.lower()).strip('-') or 'car'


def url_path(*parts):
    return '/'.join(quote(p, safe='') for p in parts)


def phone_href(phone):
    return 'tel:' + re.sub(r'[^\d+]', '', phone)


def read_text(path):
    with open(path, encoding='utf-8-sig', errors='replace') as f:
        return f.read()


def write(rel, content):
    path = os.path.join(OUT, rel)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)


def load_site():
    cfg = {
        'name': 'My Classic Cars', 'tagline': '', 'headline': 'Classic cars, hand-picked.',
        'subheadline': '', 'since': '', 'phone': '', 'email': '', 'address': '',
        'hours': [], 'social': {},
    }
    path = os.path.join(ROOT, 'site.json')
    if os.path.isfile(path):
        try:
            cfg.update(json.loads(read_text(path)))
        except ValueError:
            print('WARNING: site.json is not valid JSON, using defaults')
    if not isinstance(cfg['hours'], list):
        cfg['hours'] = []
    if not isinstance(cfg['social'], dict):
        cfg['social'] = {}
    return cfg


SITE = load_site()


def spec_get(specs, key):
    for k, v in specs:
        if k.lower() == key:
            return v
    return ''


def about_paragraphs():
    path = os.path.join(ROOT, 'about.txt')
    raw = read_text(path).strip() if os.path.isfile(path) else ''
    if not raw:
        return ['Add your story to about.txt and it will show up here.']
    out = []
    for p in re.split(r'\n\s*\n', raw.replace('\r\n', '\n')):
        p = re.sub(r'\s*\n\s*', ' ', p).strip()
        if p:
            out.append(p)
    return out


# ---------------------------------------------------------------- cars
def parse_description(raw):
    raw = raw.replace('\r\n', '\n').replace('\r', '\n').strip()
    blocks = re.split(r'\n\s*\n', raw) if raw else []
    specs = []
    if blocks:
        parsed = []
        all_specs = True
        for line in blocks[0].split('\n'):
            m = SPEC_RE.match(line.strip())
            if m:
                parsed.append((m.group(1).strip(), m.group(2).strip()))
            else:
                all_specs = False
                break
        if all_specs and parsed:
            specs = parsed
            blocks = blocks[1:]
    paragraphs = []
    for b in blocks:
        p = re.sub(r'\s*\n\s*', ' ', b).strip()
        if p:
            paragraphs.append(p)
    short = paragraphs[0] if paragraphs else ''
    if len(short) > 200:
        short = re.sub(r'\s+\S*$', '', short[:197]).rstrip() + '…'
    return specs, paragraphs, short


def folder_time(rel_path):
    """When the folder was last changed (git commit time; file time as fallback)."""
    try:
        out = subprocess.run(
            ['git', 'log', '-1', '--format=%ct', '--', rel_path],
            cwd=ROOT, capture_output=True, text=True, check=True,
        ).stdout.strip()
        if out:
            return int(out)
    except Exception:
        pass
    return int(os.path.getmtime(os.path.join(ROOT, rel_path)))


def load_car(kind, name, slug):
    rel = kind + '/' + name
    path = os.path.join(ROOT, kind, name)
    files = [f for f in os.listdir(path) if not f.startswith('.') and os.path.isfile(os.path.join(path, f))]

    txts = sorted((f for f in files if f.lower().endswith('.txt')), key=natkey)
    specs, paragraphs, short = parse_description(read_text(os.path.join(path, txts[0])) if txts else '')

    images = sorted((f for f in files if os.path.splitext(f)[1].lower() in IMAGE_EXT), key=natkey)
    for i, f in enumerate(images):
        if i > 0 and os.path.splitext(f)[0].lower() == 'cover':
            images.insert(0, images.pop(i))
            break

    status = spec_get(specs, 'status')
    if not status and kind == 'sold':
        status = 'Sold'

    return {
        'kind': kind, 'name': name, 'slug': slug, 'images': images,
        'specs': specs, 'paragraphs': paragraphs, 'short': short,
        'price': spec_get(specs, 'price'), 'status': status,
        'time': folder_time(rel),
        'file': ('sold-' if kind == 'sold' else 'car-') + slug + '.html',
    }


def scan(kind):
    base = os.path.join(ROOT, kind)
    if not os.path.isdir(base):
        return []
    cars, used = [], set()
    for name in sorted(os.listdir(base), key=natkey):
        if name.startswith('.') or name.startswith('_') or not os.path.isdir(os.path.join(base, name)):
            continue
        slug, n = slugify(name), 2
        while slug in used:
            slug = '%s-%d' % (slugify(name), n)
            n += 1
        used.add(slug)
        cars.append(load_car(kind, name, slug))
    cars.sort(key=lambda c: (-c['time'], natkey(c['name'])))
    return cars


def img_url(car, filename):
    return url_path(car['kind'], car['name'], filename)


# ---------------------------------------------------------------- templates
def page(title, active, body, desc=None):
    s = SITE
    full_title = title if title == s['name'] else '%s | %s' % (title, s['name'])
    desc = s['tagline'] if desc is None else desc
    desc_tag = '<meta name="description" content="%s">\n' % h(desc) if desc else ''

    items = []
    for key, href, label in NAV:
        cls = ' class="active" aria-current="page"' if key == active else ''
        items.append('        <li><a href="%s"%s>%s</a></li>' % (href, cls, label))
    call = ''
    if s['phone']:
        call = '\n      <a class="btn btn-primary btn-sm header-call" href="%s">%s</a>' % (h(phone_href(s['phone'])), h(s['phone']))

    contact_items = []
    if s['phone']:
        contact_items.append('<li><a href="%s">%s</a></li>' % (h(phone_href(s['phone'])), h(s['phone'])))
    if s['email']:
        contact_items.append('<li><a href="mailto:%s">%s</a></li>' % (h(s['email']), h(s['email'])))
    if s['address']:
        contact_items.append('<li>%s</li>' % h(s['address']))
    for label, url in s['social'].items():
        if url:
            contact_items.append('<li><a href="%s" target="_blank" rel="noopener">%s</a></li>' % (h(url), h(label)))
    explore = ''.join('<li><a href="%s">%s</a></li>' % (href, label) for _, href, label in NAV)
    tagline = '<p class="muted-light">%s</p>' % h(s['tagline']) if s['tagline'] else ''

    return f'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{h(full_title)}</title>
{desc_tag}<meta property="og:title" content="{h(full_title)}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="index.html">{h(s['name'])}</a>
    <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false"><span></span><span></span><span></span></button>
    <nav class="nav" aria-label="Main">
      <ul>
{chr(10).join(items)}
      </ul>{call}
    </nav>
  </div>
</header>
<main id="main">
{body}
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <p class="footer-brand">{h(s['name'])}</p>
      {tagline}
    </div>
    <div>
      <p class="footer-title">Explore</p>
      <ul>{explore}</ul>
    </div>
    <div>
      <p class="footer-title">Get in touch</p>
      <ul>{''.join(contact_items)}</ul>
    </div>
  </div>
  <div class="wrap footer-bottom">&copy; {datetime.date.today().year} {h(s['name'])}</div>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
'''


def render_card(car):
    cover = car['images'][0] if car['images'] else None
    is_sold = car['kind'] == 'sold'

    chips = []
    for k, v in car['specs']:
        if k.lower() not in ('price', 'status', 'year') and len(chips) < 2:
            chips.append(v)
    search = ' '.join([car['name'], car['short']] + [v for _, v in car['specs']]).lower()

    if cover:
        thumb = '<img loading="lazy" src="%s" alt="%s">' % (h(img_url(car, cover)), h(car['name']))
    else:
        thumb = '<div class="no-photo">Photo coming soon</div>'
    badge = ''
    if car['status']:
        badge = '<span class="badge%s">%s</span>' % (' badge-dark' if is_sold else '', h(car['status']))
    chips_html = '<p class="chips">%s</p>' % ''.join('<span>%s</span>' % h(c) for c in chips) if chips else ''
    desc_html = '<p class="desc">%s</p>' % h(car['short']) if car['short'] else ''
    price = h(car['price']) if car['price'] else ('&nbsp;' if is_sold else 'Call for price')

    return f'''<a class="card{' is-sold' if is_sold else ''}" href="{car['file']}" data-search="{h(search)}">
  <div class="thumb">{thumb}{badge}</div>
  <div class="card-body">
    <h3>{h(car['name'])}</h3>
    {chips_html}
    {desc_html}
    <div class="card-foot">
      <span class="price">{price}</span>
      <span class="more">View details &rarr;</span>
    </div>
  </div>
</a>'''


def render_grid(cars, empty_html):
    if not cars:
        return '<p class="empty">%s</p>' % empty_html
    return '<div class="grid">\n%s\n</div>' % '\n'.join(render_card(c) for c in cars)


# ---------------------------------------------------------------- pages
def home_page(stock, sold):
    s = SITE
    about = about_paragraphs()
    hero_style = ''
    if os.path.isfile(os.path.join(ROOT, 'assets', 'hero.jpg')):
        hero_style = ' style="--hero:url(\'assets/hero.jpg\')"'
    eyebrow = '<p class="eyebrow">Since %s</p>' % h(s['since']) if s['since'] else ''
    sub = '<p class="hero-sub">%s</p>' % h(s['subheadline']) if s['subheadline'] else ''
    years = ''
    try:
        if s['since']:
            years = '<div><dt>%d+</dt><dd>Years collecting</dd></div>' % max(0, datetime.date.today().year - int(s['since']))
    except (TypeError, ValueError):
        pass

    return f'''<section class="hero"{hero_style}>
  <div class="wrap hero-inner">
    {eyebrow}
    <h1>{h(s['headline'])}</h1>
    {sub}
    <div class="hero-actions">
      <a class="btn btn-primary" href="inventory.html">View inventory</a>
      <a class="btn btn-ghost-light" href="contact.html">Contact us</a>
    </div>
    <dl class="stats">
      <div><dt>{len(stock)}</dt><dd>Available now</dd></div>
      <div><dt>{len(sold)}</dt><dd>Sold</dd></div>
      {years}
    </dl>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head">
      <div>
        <p class="eyebrow dark">Just arrived</p>
        <h2>Latest inventory</h2>
      </div>
      <a class="link-arrow" href="inventory.html">See all {len(stock)} &rarr;</a>
    </div>
    {render_grid(stock[:3], 'New arrivals are on the way. Check back soon.')}
  </div>
</section>

<section class="section section-alt">
  <div class="wrap split">
    <div>
      <p class="eyebrow dark">Our story</p>
      <h2>Built on a love of old cars.</h2>
    </div>
    <div class="prose">
      <p>{h(about[0])}</p>
      <a class="link-arrow" href="about.html">More about us &rarr;</a>
    </div>
  </div>
</section>

<section class="cta">
  <div class="wrap cta-inner">
    <h2>Looking for something specific?</h2>
    <p>Tell us what you're hunting for and we'll keep an eye out. We also buy and consign classics.</p>
    <a class="btn btn-light" href="contact.html">Get in touch</a>
  </div>
</section>'''


def inventory_page(stock):
    toolbar = ''
    if stock:
        word = 'car' if len(stock) == 1 else 'cars'
        toolbar = f'''
  <div class="toolbar">
    <input id="filter" class="search" type="search" placeholder="Search by make, model, color..." aria-label="Search inventory">
    <span id="result-count" class="muted">{len(stock)} {word}</span>
  </div>'''
    empty = 'No cars are listed right now. Check back soon, or <a href="contact.html">get in touch</a> about what we have coming in.'
    return f'''<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">For sale</p>
    <h1>Current inventory</h1>
  </div>
</section>

<section class="wrap list-section">{toolbar}
  {render_grid(stock, empty)}
  <p id="no-results" class="empty" hidden>No cars match your search.</p>
</section>'''


def sold_page(sold):
    return f'''<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">Past sales</p>
    <h1>Sold</h1>
    <p class="lead">A look at some of the cars that have found new homes.</p>
  </div>
</section>

<section class="wrap list-section">
  {render_grid(sold, 'Nothing here yet.')}
</section>'''


def car_page(car, stock):
    s = SITE
    is_sold = car['kind'] == 'sold'
    urls = [img_url(car, f) for f in car['images']]
    specs = [(k, v) for k, v in car['specs'] if k.lower() not in ('price', 'status')]
    others = [c for c in stock if c['name'] != car['name']][:3]

    badge = ''
    if car['status']:
        badge = '<span class="badge badge-static%s">%s</span>' % (' badge-dark' if is_sold else '', h(car['status']))

    if urls:
        thumbs = ''
        if len(urls) > 1:
            btns = ''.join(
                '<button type="button" class="%s" aria-label="Photo %d"><img loading="lazy" src="%s" alt=""></button>'
                % ('active' if i == 0 else '', i + 1, h(u)) for i, u in enumerate(urls))
            thumbs = '\n        <div class="thumbs">%s</div>' % btns
        gallery = f'''<div class="gallery" data-gallery data-images="{h(json.dumps(urls))}">
        <div class="main-photo"><img src="{h(urls[0])}" alt="{h(car['name'])}"></div>{thumbs}
      </div>'''
    else:
        gallery = '<div class="main-photo"><div class="no-photo">Photos coming soon</div></div>'

    desc = ''
    if car['paragraphs']:
        desc = '<div class="prose car-desc">\n%s\n      </div>' % '\n'.join('        <p>%s</p>' % h(p) for p in car['paragraphs'])

    price = '<p class="price-big">%s</p>' % h(car['price']) if car['price'] else ''
    table = ''
    if specs:
        rows = ''.join('<tr><th>%s</th><td>%s</td></tr>' % (h(k), h(v)) for k, v in specs)
        table = '<table class="specs">%s</table>' % rows

    if is_sold:
        actions = '<p class="muted">This car has been sold. <a href="inventory.html">See what\'s available now.</a></p>'
    else:
        btns = ''
        if s['email']:
            subject = quote('Inquiry: ' + car['name'], safe='')
            btns += '<a class="btn btn-primary" href="mailto:%s?subject=%s">Inquire about this car</a>' % (h(s['email']), subject)
        if s['phone']:
            btns += '<a class="btn btn-outline" href="%s">Call %s</a>' % (h(phone_href(s['phone'])), h(s['phone']))
        actions = '<div class="panel-actions">%s</div>' % btns

    more = ''
    if others:
        more = '''
  <section class="more-cars">
    <h2>More from our inventory</h2>
    %s
  </section>''' % render_grid(others, '')

    back_href, back_label = ('sold.html', 'Sold') if is_sold else ('inventory.html', 'Inventory')
    return f'''<div class="wrap car-page">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="{back_href}">&larr; {back_label}</a>
  </nav>

  <div class="car-head">
    <h1>{h(car['name'])}</h1>
    {badge}
  </div>

  <div class="car-layout">
    <div>
      {gallery}
      {desc}
    </div>

    <aside class="panel">
      {price}
      {table}
      {actions}
    </aside>
  </div>
{more}
</div>'''


def about_page():
    s = SITE
    paras = about_paragraphs()
    eyebrow = 'Since ' + h(s['since']) if s['since'] else 'Our story'
    photo = ''
    if os.path.isfile(os.path.join(ROOT, 'assets', 'about.jpg')):
        photo = '\n    <div class="about-photo"><img src="assets/about.jpg" alt=""></div>'
    prose = '\n'.join('      <p>%s</p>' % h(p) for p in paras)
    return f'''<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">{eyebrow}</p>
    <h1>About us</h1>
  </div>
</section>

<section class="wrap list-section">
  <div class="split about-split">
    <div class="prose">
{prose}
      <a class="btn btn-primary" href="inventory.html">See what we have</a>
    </div>{photo}
  </div>
</section>'''


def contact_page():
    s = SITE
    cards = []
    if s['phone']:
        cards.append('<div class="contact-card"><p class="footer-title dark">Call or text</p><a class="contact-big" href="%s">%s</a></div>'
                     % (h(phone_href(s['phone'])), h(s['phone'])))
    if s['email']:
        cards.append('<div class="contact-card"><p class="footer-title dark">Email</p><a class="contact-big" href="mailto:%s">%s</a></div>'
                     % (h(s['email']), h(s['email'])))
    if s['address']:
        maps = 'https://www.google.com/maps/search/?api=1&query=' + quote(s['address'], safe='')
        cards.append('<div class="contact-card"><p class="footer-title dark">Visit</p><p class="contact-big">%s</p>'
                     '<a class="link-arrow" href="%s" target="_blank" rel="noopener">Open in Maps &rarr;</a></div>'
                     % (h(s['address']), h(maps)))
    if s['hours']:
        lines = ''.join('<li>%s</li>' % h(l) for l in s['hours'])
        cards.append('<div class="contact-card"><p class="footer-title dark">Hours</p><ul class="hours">%s</ul></div>' % lines)
    return f'''<section class="page-head">
  <div class="wrap">
    <p class="eyebrow dark">Say hello</p>
    <h1>Contact us</h1>
    <p class="lead">Questions about a car, want to schedule a visit, or have something to sell or consign? Reach out.</p>
  </div>
</section>

<section class="wrap list-section">
  <div class="contact-grid">
{chr(10).join(cards)}
  </div>
</section>'''


# ---------------------------------------------------------------- build
def build():
    stock, sold = scan('cars'), scan('sold')

    if os.path.isdir(OUT):
        shutil.rmtree(OUT)
    os.makedirs(OUT)

    shutil.copytree(os.path.join(ROOT, 'assets'), os.path.join(OUT, 'assets'))
    write('.nojekyll', '')

    for car in stock + sold:
        dest = os.path.join(OUT, car['kind'], car['name'])
        os.makedirs(dest, exist_ok=True)
        for f in car['images']:
            shutil.copy2(os.path.join(ROOT, car['kind'], car['name'], f), os.path.join(dest, f))
        write(car['file'], page(car['name'], 'sold' if car['kind'] == 'sold' else 'inventory',
                                car_page(car, stock), car['short']))

    write('index.html', page(SITE['name'], 'home', home_page(stock, sold)))
    write('inventory.html', page('Inventory', 'inventory', inventory_page(stock),
                                 'Current inventory of classic cars and trucks for sale.'))
    write('sold.html', page('Sold', 'sold', sold_page(sold),
                            'A look at classic cars and trucks we have recently sold.'))
    write('about.html', page('About', 'about', about_page(), about_paragraphs()[0]))
    write('contact.html', page('Contact', 'contact', contact_page(),
                               'Get in touch about a car, or to buy or consign a classic.'))

    print('Built %d car(s) for sale and %d sold -> dist/' % (len(stock), len(sold)))
    for c in stock:
        print('  for sale: %s (%d photo%s)' % (c['name'], len(c['images']), '' if len(c['images']) == 1 else 's'))
    for c in sold:
        print('  sold:     %s (%d photo%s)' % (c['name'], len(c['images']), '' if len(c['images']) == 1 else 's'))


if __name__ == '__main__':
    build()
