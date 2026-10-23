# Classic Car Dealership Website

A static website hosted free on GitHub Pages. No PHP and no server. You manage the inventory from your
iPhone with a built-in admin page, or by adding folders to the repo.

Live site: https://kylew357.github.io/harpersclasssics/
Admin page: https://kylew357.github.io/harpersclasssics/admin/

## Add and edit cars from your iPhone

The admin page lets you take photos, fill in the details and publish. Photos are resized on your phone
before they upload, so there is nothing to resize by hand.

**One-time setup (about 3 minutes):**
1. In Safari, sign in to GitHub and open
   [Settings → Developer settings → Fine-grained tokens → Generate new token](https://github.com/settings/personal-access-tokens/new).
2. Name it "Site admin" and choose an expiration (up to one year; you'll make a new one when it expires).
3. Under **Repository access** choose **Only select repositories** and pick this repository.
4. Under **Permissions → Repository permissions** set **Contents** to **Read and write**.
5. Generate the token and copy it.
6. Open the admin page above, enter the repository name (`kylew357/harpersclasssics`), paste the token and sign in.
7. Optional: tap the Share button in Safari and choose **Add to Home Screen** so the admin page opens like an app.

**Every day:**
- **Add a car:** tap *+ Add a car*, fill in the title, price and details, tap *Add photos* (pick from your library or take
  new ones), then tap *Publish to site*.
- **Edit a car:** tap *Edit* to change any detail, add or remove photos, or choose the cover photo (★).
- **Mark sold:** tap *Mark sold*. It moves to the Sold page. *Back for sale* undoes it.
- **Bring a Trailer link:** in the car form, set **Badge** to *Bring a Trailer*, then paste the link to the car's listing in the box that appears. The car's page gets a *View on Bring a Trailer* button that opens it in a new tab, and the tile shows a *Bring a Trailer* badge.
- **Hide a car:** tap *Hide* to take a car off the website without deleting it (it appears under *Hidden* on the admin page, and *Show on site* brings it back). This is the same as putting `_` or `.` at the start of the folder name on GitHub.
- **Sort the list:** use *Sort by* to order cars by newest or oldest added, by year, or by name. The year comes from the title (or the Year detail if the title has none), and "added" is the date the car was first put on the site.
- **Delete:** tap *Delete* to remove a car and its photos.
- **Phone, hours and About text:** tap *Edit site info*.

Changes go live about a minute after you publish.

**About the token:** it is saved only in the browser on your phone, and it can only touch this one repository.
If you lose your phone, delete the token in GitHub's token settings. Anyone can open the admin page address,
but it does nothing without a token.

## Add a car without the admin page

You can also add folders directly in the repo (on GitHub: *Add file → Upload files*, or with git).

1. Create a folder inside `cars/`. **The folder name is the car's title**, e.g. `cars/1966 Pontiac GTO/`
2. Put a text file in it (any name ending in `.txt`, e.g. `description.txt`).
3. Add the photos to the same folder.

Photos of any size are fine. When the site builds, they are automatically shrunk, rotated upright and converted
to JPEG (iPhone HEIC photos included), and small thumbnails are made for the gallery.

## The description file

```
Status: Just In
Price: $28,900
Year: 1972
Mileage: 61,400 mi
Engine: 360 V8
Color: Two-tone Green

First paragraph: shown on the inventory tile and at the top of the car page.

More paragraphs (after a blank line) only show on the car's own page.
```

- The `Key: Value` lines at the top become the details table. Use any keys you like.
- **There must be a blank line** between the `Key: Value` lines and your description text.
- `Price` is shown on the tile and large on the car page. If you leave it out, the tile says "Call for price".
- `Status` shows as a badge (`Just In`, `Sale Pending`, ...). Cars in `sold/` get "Sold" automatically.
- `Bring a Trailer URL: https://bringatrailer.com/listing/...` adds a *View on Bring a Trailer* button to the car's page (the admin page writes this line for you; it is not shown in the details table).
- The first two other details (like Mileage, Engine) show as small chips on the tile.

## Photos

- The admin page names them for you. If you add photos by hand, they show in alphabetical order, so
  `01-front.jpg`, `02-interior.jpg`, ... controls the order.
- A file named `cover.jpg` is always the first photo and the tile picture.
- Visitors can click the main photo on a car page for a full-screen viewer with arrows.

## Order of cars

Newest first, by when a car's folder was last changed.

## Hiding a car without deleting it

Put an underscore in front of the folder name: `_1966 Pontiac GTO`. Folders starting with `_` or `.` are ignored.

## How it works

Every time something is pushed to `main`, a GitHub Action (`.github/workflows/pages.yml`) runs
`scripts/build_static.py`. It reads `cars/`, `sold/`, `site.json` and `about.txt`, resizes the photos, writes the
pages into `dist/`, and publishes that to GitHub Pages. The admin page (`admin/index.html`) is a single file that
talks to GitHub's API to create those commits for you.

GitHub Pages must be set to deploy from **GitHub Actions** (Settings → Pages → Source). The repository must be
public on the free plan.

To preview on a computer: `pip install pillow pillow-heif`, then `python3 scripts/build_static.py` and open
`dist/index.html`. Without Pillow the site still builds, but photos are copied without resizing.

## Other things you can edit

| File | What it controls |
| --- | --- |
| `site.json` | Business name, headline, phone, email, address, hours, social links (also editable in the admin page) |
| `about.txt` | The About page text (blank line between paragraphs) |
| `assets/hero.jpg` | Optional. If you add this file it becomes the home page hero background |
| `assets/about.jpg` | Optional. If you add this file it appears on the About page |
| `assets/style.css` | Colors and fonts (the colors are at the top of the file) |

## Sample data

The cars in `cars/` and `sold/` are placeholders with placeholder photos. Delete them (use *Delete* in the admin page)
when you add your real inventory.

## File layout

```
cars/      one folder per car for sale
sold/      one folder per sold car
site.json  about.txt  assets/
admin/index.html                  the phone admin page
scripts/build_static.py           builds the site
.github/workflows/pages.yml       builds and publishes on every push
```
