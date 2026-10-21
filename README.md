# Classic Car Dealership Website

No build step. No Node.js. The site reads the `cars/` and `sold/` folders every time a page loads, so
**adding or removing a folder updates the website immediately.**

## Adding a car

1. Create a folder inside `cars/`. **The folder name is the car's title**, e.g. `cars/1966 Pontiac GTO/`
2. Put a text file in it (any name ending in `.txt`, e.g. `description.txt`).
3. Drop the photos into the same folder.

Done. It appears on the Inventory page (and the newest three on the home page).

## When a car sells

Move its folder from `cars/` into `sold/`. It disappears from inventory and shows up on the Sold page
with a "Sold" badge. Delete the folder to remove it entirely.

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
- The first two other details (like Mileage, Engine) show as small chips on the tile.

## Photos

- `.jpg`, `.jpeg`, `.png`, `.webp`, `.gif`, `.avif`, `.svg`
- Shown in alphabetical order. Name them `01-front.jpg`, `02-interior.jpg`, ... to control the order.
- A file named `cover.jpg` is always the first photo and the tile picture.
- **Resize photos before uploading** (about 1600px wide is plenty). Straight-from-phone photos are huge
  and will make the inventory page slow.
- Visitors can click the main photo for a full-screen viewer with arrows.

## Order of cars

Newest folders first. Adding or editing a car's folder moves it to the top.

## Hiding a car without deleting it

Put an underscore in front of the folder name: `_1966 Pontiac GTO`. Folders starting with `_` or `.` are ignored.

## Other things you can edit

| File | What it controls |
| --- | --- |
| `site.json` | Dealership name, headline, phone, email, address, hours, social links |
| `about.txt` | The About page text (blank line between paragraphs) |
| `assets/hero.jpg` | Optional. If you add this file it becomes the home page hero background |
| `assets/about.jpg` | Optional. If you add this file it appears on the About page |
| `assets/style.css` | Colors and fonts (the colors are at the top of the file) |

## Two ways to host it

The same folders, `site.json` and `about.txt` power both options, with the same design.

### Option A: GitHub Pages (free, no PHP)

GitHub builds the site for you every time you push a change to `main`
(`.github/workflows/pages.yml` runs `scripts/build_static.py`). Nothing to install.

**One-time setup:**
1. Your repository must be **public** (GitHub Pages on private repos needs a paid plan).
2. On GitHub go to **Settings → Pages**, and under *Build and deployment* set **Source** to **GitHub Actions**.
3. Go to the **Actions** tab, open the latest *Build and deploy site to GitHub Pages* run and click **Re-run all jobs**
   (or just push any change). When it turns green, your site is at `https://<your-username>.github.io/<repo-name>/`.
4. Optional: add your own domain under Settings → Pages → *Custom domain*.

**Adding a car:** on GitHub, open the `cars` folder, choose **Add file → Upload files**, and drag in a folder that has
the description `.txt` and the photos (the folder name is the car's title), then click **Commit changes**. About a minute
later the site is updated. To mark a car sold, move its folder into `sold/`; to remove it, delete the folder.
Cars are ordered by when their folder was last changed, newest first.

You can also run the builder yourself to preview: `python3 scripts/build_static.py`, then open `dist/index.html`.

### Option B: a PHP web host

You need web hosting that runs **PHP 7.4 or newer**. Almost every standard hosting plan does (Bluehost,
SiteGround, HostGator, DreamHost, cPanel hosts, and so on). Upload the whole folder with FTP or the host's file manager.
In this mode the site updates **instantly** when you add or remove a folder on the server, with no build step.

Website builders like GoDaddy Website Builder or Wix do not run PHP and won't work.

To preview on your own computer: if PHP is installed, run `php -S localhost:8000` in this folder and open
http://localhost:8000. Without PHP, XAMPP or MAMP give you a free local PHP server.

## Sample data

The cars in `cars/` and `sold/` are placeholder samples with placeholder photos. Delete those folders when you add your real inventory.

## File layout

```
cars/      one folder per car for sale        (used by both options)
sold/      one folder per sold car            (used by both options)
site.json  about.txt  assets/                 (used by both options)

scripts/build_static.py, .github/workflows/pages.yml   Option A: GitHub Pages
index.php inventory.php sold.php car.php about.php contact.php, includes/   Option B: PHP host
```
