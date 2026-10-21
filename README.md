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

## Hosting requirements

You need web hosting that runs **PHP 7.4 or newer**. Almost every standard hosting plan does (Bluehost,
SiteGround, HostGator, DreamHost, cPanel hosts, and so on). Upload the whole folder with FTP or the host's file manager.

Static-only hosts (GitHub Pages, Netlify's free static hosting, and website builders like GoDaddy Website Builder or
Wix) **do not run PHP** and won't work.

## Previewing on your own computer

If PHP is installed: open a terminal in this folder and run `php -S localhost:8000`, then open http://localhost:8000.
Without PHP installed, XAMPP (Windows/Mac) or MAMP (Mac) give you a free local PHP server.

## Sample data

The cars in `cars/` and `sold/` are placeholder samples with placeholder photos. Delete those folders when you add your real inventory.

## File layout

```
index.php  inventory.php  sold.php  car.php  about.php  contact.php
site.json  about.txt
assets/    style.css, app.js
includes/  shared PHP code (do not delete)
cars/      one folder per car for sale
sold/      one folder per sold car
```
