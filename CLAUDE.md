# Endless — working notes

Laravel 13 app for memorial pages. Hebrew/RTL throughout. See `README.md` for setup and `docs/PLAN.md` for the original spec.

## Commands

```bash
php artisan test                 # 112 tests, keep them green
php artisan migrate:fresh --seed # rebuild the demo memorial at /m/kochav
npm run dev                      # Vite watch
npm run build                    # required before checking pages in a browser
```

PHP lives at `C:\Users\user\.config\herd\bin\php84\php.exe` (Herd); `php` and `composer` are on PATH.

## Design system

The visual language is copied measurement-by-measurement from the reference pages on `endless.day`
(the front page, the "yad-lashiryon" landing page, the `person/kochav` memorial page, the `/ko/` feed
and the `/join/` form), re-typed in **Heebo** — the reference's three fonts are deliberately not used.
Circular Std maps one weight lighter in Heebo (Cir 400 → Heebo 300, Cir 600 → Heebo 500); the
reference's `word-spacing: -13px` compensates for its display font's wide spaces and is not carried over.

Tokens live in `resources/css/tokens.css`. The values that matter and should not drift:

| Element | Value |
|---|---|
| Page background | `#F9F8F5` |
| Ink | `#1D1D20`, soft `#575757` |
| Card radius / image radius / button radius / input radius | 20 / 12 / 12 / 10 px |
| Container widths | 1200 wide · 800 form · 777 text+quote · 700 read |
| Section padding | 75px block |
| Hero eyebrow + dates | 18px, letter-spacing 3px |
| Memorial name | 55px desktop / 40px mobile, weight 300, line-height 1 |
| Section headings | 21px, letter-spacing 3px |
| Body / biography | 19px, weight 300, line-height 1.5 |
| Memory card text / author | 19px lh 1.2 white / 16px ls 1px, margin-top 15px |
| Quote | 40px, weight 200, line-height 1.4 |
| Masonry card / gallery tile | 373px / 275px with 20px gaps inside a 1160px content box |

Rounded corners are intentional here and override the usual sharp-corner house style — the brief was
"exactly like the reference".

## The two landing pages

`/` is a 1:1 Hebrew clone of the `endless.day` front page — hardcoded copy in `home.blade.php`,
its own `resources/css/home.css`, reference photography in `public/images/home/`. `/shiryon`
(`shiryon.blade.php`) is the old "yad lashiryon" pitch page and is the one driven by the
`landing` settings group; the lead form and `POST /leads` live there.

## The book builder

`/dashboard/book` composes a memorial into printed pages (`BookComposer`). Five things to know:

- **The page budget is counted in rendered lines, not characters.** The body uses `white-space: pre-line`,
  so a one-word line costs a whole line. `BookSize::linesPerPage()` / `charsPerLine()` were measured
  against the preview by binary search — re-measure them if the book's type or padding changes.
- **The preview is a scale model.** Type is sized in `cqw` off `--type-scale` (`21 / widthCm`), so a
  landscape page draws smaller type at the same pane width, exactly as it would on paper. Size it in
  px and the budget stops matching what the page shows.
- **The preview is a stack of leaves, not a slideshow.** Every page before the current one carries
  `.is-turned`, so forward and back are the same operation and CSS animates it. `overflow` and
  `container-type` flatten 3-D, so they live on `.book__face`, never on the leaf that rotates, and
  the clip sits on `.book__frame` outside the perspective.
- **The full-screen view is bound like a real book.** A sheet carries two pages, so `book-spread.js`
  groups pages into leaves — leaf n is page 2n-1 front, 2n back — and every leaf pivots on the centre
  spine from the left half onto the right. That is what makes the spread read right page first, and
  what lets you see the back of the sheet you just turned. Page-type layout rules must name both
  `.book__page--x > .book__face` and `.spread__face.book__page--x`; the two renderers nest differently.
- **Per-page edits are keyed overrides** (`books.overrides`, keyed by the page key). A field
  edited back to the composed text is dropped rather than frozen, so the page keeps following the
  memorial. Keys that no longer exist after a resize are ignored. Photos taken out of the book live
  separately in `books.excluded_images` as `gallery:12` / `memory:45`, since they change the pagination.

Ordering is deliberately closed — `Book` stores the draft so it can become an order when payments land.

## Things that will bite you

- **Percentage padding on a flex item resolves against the flex container, not the item.** The
  reference's `padding: 0 15%` columns are Elementor's inner wrapper, so `.home-hero__inner` /
  `.home-why__inner` exist to reproduce that. Putting the padding on the flex item itself blows the
  row past 100vw.
- **`stacking.js` mirrors the reference's GSAP numbers** — each covered card loses 6% scale and gains
  30% grayscale, interpolated from `(index / count)` of the wrapper hitting the viewport top until the
  wrapper's bottom hits the viewport middle. Change the card count and the constants still hold.
- **Masonry re-parents its cards.** `masonry.js` empties the container and rebuilds columns, which detaches
  nodes from the IntersectionObserver. It fires a `masonry:layout` event and `reveal.js` re-observes; if you
  add another observer over those cards, listen for that event too.
- **`.fade-in` is gated behind `html.js`.** Without JavaScript everything stays visible. Full-page screenshots
  can catch cards mid-transition — add `is-visible` to all of them before capturing.
- **Storage URLs are relative** (`FILESYSTEM_PUBLIC_URL=/storage`) so the app works on any host. Do not put
  `APP_URL` back into the public disk config.
- **`OtpService` must stay a singleton** — tests read `lastPlainCode` off the resolved instance.
- **Settings are cached forever** (`SettingsRepository::CACHE_KEY`). Call `flush()` after writing outside the
  admin controller.
- **Secrets in `settings`** (`mail.password`, `sms.019.token`, `sms.019.password`) are encrypted; an empty
  submitted value keeps the stored one rather than clearing it.

## Layout conventions

- `layouts/app.blade.php` — header, footer, `--scene-image` custom property.
- `layouts/dashboard.blade.php` / `layouts/admin.blade.php` — the split-screen `.scene` with a centred
  `.paper-card`, mirroring the reference `/join/` page.
- `.wrap--edge` gives masonry sections the reference's 20px inner gutter; plain `.wrap` uses 40px.
