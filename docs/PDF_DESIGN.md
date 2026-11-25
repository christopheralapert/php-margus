# PDF_DESIGN — Lumi aftercare leaflet

The design system for the PDF leaflet. All measurements are in **mm** unless noted, and type sizes are in **pt**.
Everything here must be possible in **mPDF 8** (see §10). If the render does not match the spec, change the
implementation, not the spec, unless the change is agreed in the PR.

## 1. Principles

1. **Calm, friendly, clear.** A worried patient reads this on a phone at night. One idea per block, short lines and lots of white space.
2. **Colour guides and icons confirm.** Meaning never depends on colour alone: ✓/✕ icons, labels and shapes all repeat it (grayscale-safe).
3. **The red-flag box is the strongest element** of the document. Nothing else uses solid red.
4. **Consistent theme per procedure**: its colour appears in its header band, timeline, icons and page-top strip.
5. **Original art only**: flat, rounded, two or three colours per drawing, no people's faces in detail and no photos.

## 2. Page formats

| Format | Trim (W×H) | Margins T / B / inner / outer | Header / footer distance | Content area |
|--------|-----------|-------------------------------|--------------------------|--------------|
| **A5 booklet (default)** | 148 × 210 | 14 / 22 / 14 / 14 | header 6, footer 7 | 120 × 174 |
| A5 print mode | trim 148 × 210 on a 168 × 230 sheet, `bleedMargin` 3, crop marks | same as above (mirrored: inner 16, outer 12) | same | 120 × 174 |
| A4 tri-fold (P7) | 297 × 210 landscape, 3 panels of 99 (inside fold panel 97, outer 100) | 10 per panel | none | panel 79–80 × 190 |

- Cover and back page use named pages without the running footer (`@page cover`, `@page back`).
- Page numbering: "3 / 8" in the footer, right-aligned. The cover counts as page 1 but shows no number.
- Print mode only: pad the page count to a multiple of 4 with a "Märkmed / Notes" page (lined) before the back page.

## 3. Grid

- Content width 120, single column by default (best on a phone).
- 2-column grid for topic tiles: 57 + 6 gutter + 57 (implemented as a table, `width:100%`, cells `width:50%`, padding 0 3 to create the gutter).
- 4-column "day ruler" in the timeline: 4 × 30.
- Baseline rhythm: 15 pt leading for body text. Vertical spacing uses the spacing scale only.

**Spacing scale**: `s1=1` `s2=2` `s3=3` `s4=4` `s6=6` `s8=8` `s12=12`.
Block-to-block = s6. Heading-to-content = s3. Card padding = s4. List item gap = s2.

## 4. Palette

### Brand (from settings, defaults below)
| Token | Hex | Use |
|-------|-----|-----|
| primary | `#2E6FD8` | cover background shapes, links, general section |
| secondary | `#2BB3A3` | accents, follow-up box |
| accent | `#F5A524` | highlights, "tip" markers, never body text on white |
| danger | `#D93B3B` | red-flag box only, the "don't" icon |
| ink | `#1F2937` | body text |
| paper | `#FFFFFF` | page |
| soft | `#F3F7FC` | soft panels, back page band |

### Semantic
| Token | base | dark (text on white) | tint (background) |
|-------|------|----------------------|-------------------|
| do (green) | `#2F9E5B` | `#1E7A43` | `#EAF6EF` |
| dont (red) | `#D93B3B` | `#B42323` | `#FDECEC` |
| warn (amber) | `#F5A524` | `#8A5A00` | `#FFF6E5` |
| muted text | `#6B7280` | – | – |
| hairline | `#DCE3EE` | – | – |

### Category defaults (seeded, editable)
| Category | Base | Category | Base |
|----------|------|----------|------|
| Dental / Hambaravi | `#2E6FD8` | General & minor surgery / Üldkirurgia | `#3B8C5A` |
| Dermatology / Dermatoloogia | `#E2725B` | Orthopaedics & physio / Ortopeedia ja füsio | `#F08A24` |
| Aesthetic / Esteetiline | `#D9488F` | Gynaecology / Günekoloogia | `#A23E9C` |
| Eye / Silmaravi | `#2BB3A3` | Vaccination & injections / Vaktsineerimine ja süstid | `#0EA5C6` |
| ENT / Kõrva-nina-kurgu | `#7C5CD6` | | |

`ThemeColor` derives the rest from the base colour: `dark` = darkened until contrast with white ≥ 4.5:1;
`tint` = 12% base on white; `on` = `#FFFFFF` if contrast(base, white) ≥ 4.5, else ink `#1F2937`.

## 5. Typography

Fonts (OFL, embedded, subset; static TTFs in `resources/pdf/fonts/`, with `OFL.txt`):
- **Nunito** (ExtraBold 800, Bold 700) for headings, numbers and the phone number. mPDF family: `nunito`.
- **Source Sans 3** (Regular, Italic, SemiBold, Bold) for body text. mPDF family: `sourcesans3` (default).
Both must render `õ ä ö ü š ž Õ Ä Ö Ü Š Ž` and `– — “ ” „ € ·`. The test fixture string is
`"Jäätisetõrjuja šokolaad ŽÕÄÖÜ – „õige“"`.

| Style | Font | Size / leading | Weight | Colour | Notes |
|-------|------|----------------|--------|--------|-------|
| cover-title | Nunito | 30 / 34 | 800 | ink | max 2 lines |
| cover-sub | Source Sans 3 | 13 / 17 | 400 | muted | |
| h1 (procedure title) | Nunito | 20 / 24 | 800 | theme.on on theme band | |
| h2 (block heading) | Nunito | 16 / 20 | 700 | theme.dark | **minimum heading size** |
| body | Source Sans 3 | **10.5** / 15 | 400 | ink | **minimum body size** |
| body-strong | Source Sans 3 | 10.5 / 15 | 600 | ink | |
| label (day markers, chips) | Source Sans 3 | 9 / 11 | 700, UPPERCASE, +0.3 pt tracking | theme.dark | only for short labels, never sentences |
| phone-xl | Nunito | 22 / 26 | 800 | danger-dark | red-flag box |
| emergency-xxl | Nunito | 32 / 34 | 800 | white on danger | "112" |
| footer | Source Sans 3 | 7.5 / 10 | 400 | muted | only in the footer |

## 6. Iconography and illustrations

- Icons: 24×24 viewBox SVG with one-colour fills and `fill` attributes (no `<style>`). Rendered at 4–6 mm.
  Set: check, cross, warning-triangle, phone, clock, pin, mail, calendar, pill, cup, droplet, run, moon, stethoscope, info.
  Path: `resources/pdf/icons/*.svg`. The colour is injected server-side (template replace on `currentColor`).
- Category icons: `public/icons/categories/{slug}.svg` (also used in the web UI).
- Illustrations: `public/illustrations/{category}/{procedure-slug}.svg`, 160×120 viewBox, flat, maximum 3 theme
  colours + ink + white, rounded shapes, no text. A placeholder per category is fine until final art exists.
  The path is stored on the procedure, so swapping art means uploading a file.
- Raster images (when uploaded): PNG at **≥ 300 dpi** at placed size (cover art 90 mm wide ⇒ ≥ 1063 px).
  The admin upload rejects images under 200 dpi at the target size.

## 7. Components

### 7.1 Running footer (all pages except the cover)
Two lines, 120 wide, hairline rule above (0.2, `#DCE3EE`):
`Line 1:` disclaimer (et: "See juhend ei asenda arsti isiklikku nõuannet." / en: "This leaflet does not replace your doctor's personal advice.")
`Line 2:` left "Lumi Kliinik · +372 600 0000", right "3 / 8".

### 7.2 Page-top strip
A full-width 3 mm bar at the top edge (bleeds in print mode) in the colour of the current section's theme
(primary for the general and back pages). Implemented with `position:fixed` div or page background.

### 7.3 Section header band (one per procedure)
Rounded rectangle (radius 4) 120 × ~28, background theme.base, padding s4. Left: category icon
in a white circle (12 Ø). Text: label "HAMBARAVI" (label style, colour theme.on at 85%), h1 procedure
name (theme.on). Right: illustration thumb 24 wide (optional). It always starts a new page.

### 7.4 Timeline
1. **Day ruler**: a 4-cell table. Each cell has a 6 Ø circle (SVG, theme.base, white number 1–4) and a label below:
   `0–24 h`, `1.–3. päev`, `1. nädal`, `Kuni paranemiseni` (en: `First 24 h`, `Days 1–3`, `Week 1`, `Until healed`).
   A 0.8 line in theme.tint runs through the circle centres (cell `border-bottom` trick or a background image).
2. **Steps** (vertical): a 2-column table, left column 24 wide with the phase label chip (theme.tint background,
   theme.dark text, radius 2) and a 0.8 left rule in theme.base connecting the steps; right column: step title
   (body-strong) and body. Each step has `page-break-inside: avoid`.

### 7.5 Do / Don't cards
Two stacked full-width cards (not side by side; this reads better on a phone).
- Card: radius 3, background tint, left border 1.5 in base, padding s4.
- Header: 6 Ø icon circle (do: check in white on do.base; don't: cross in white on dont.base) + h2 "Tee nii" / "Väldi"
  (en: "Do" / "Don't") in the dark colour.
- Items: a table, 4 mm mini icon (check/cross in base colour) + body text. If there are 6 or more items, use two columns.

### 7.6 Topic tiles
Pain & medication, Eating & drinking, Hygiene, Activity, Sleep. 2-column table of tiles; each tile has a soft
background (`#F3F7FC`), an icon (5 mm, theme.dark), h2-sized title (16 pt), body text. Pain & medication always includes the
fixed line "Kasuta valuvaigisteid nii, nagu arst on määranud." / "Use pain relief as prescribed by your doctor." (from lang files).
Empty topics are skipped; an odd last tile spans both columns.

### 7.7 Red-flag box ("Millal kohe abi otsida" / "When to get help right away")
The most dominant element. Full width, `page-break-inside: avoid`, starts a new page if less than 90 mm remain.
```
┌───────────────────────────────────────────┐  border 0.8 danger, radius 4
│▓▓ ⚠  MILLAL KOHE ABI OTSIDA ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│  band: danger bg, white Nunito 16/800, triangle icon 7
│                                           │
│  ●  Palavik üle 38 °C, mis ei alane       │  body 10.5/15, red bullet 2.5 Ø
│  ●  Tugev verejooks, mis ei peatu ...     │  general items first, then per
│  ● [■ Hambaravi] Näo turse ...            │  procedure (theme-coloured dot + name)
│                                           │
│  Helista kliinikule        ┌───────────┐  │
│  +372 600 0000  (22 pt)    │ HÄDAABI   │  │  pill: danger bg, radius 4,
│  Õhtuti/öösiti: +372 600 0001 │  112   │  │  white "112" 32 pt
│                            └───────────┘  │
└───────────────────────────────────────────┘
```
Background inside the box: dont.tint. In grayscale it is still identified by the thick border, the solid band and the triangle.

### 7.8 Follow-up box
Radius 3, secondary tint background, calendar icon, h2 "Järelkontroll" / "Follow-up visit", body text, and the procedure date if given:
"Protseduur tehti: 24.11.2026".

### 7.9 General advice section (multi-procedure only)
Header band in primary, title "Üldised soovitused" / "General advice", subtitle listing the procedures as chips
(coloured 3 Ø dot + name). It contains merged do/don't/topic items with the same components as above.

### 7.10 QR block (back page)
QR 32 × 32 (endroid, PNG, error correction M, margin 0, ink on white), caption to the right (body):
"Skaneeri: juhend veebis ja küsi küsimusi" / "Scan: this guide online and ask questions", plus the URL in footer style.

### 7.11 DRAFT watermark
When any included procedure has status `draft`: mPDF `watermarktext` "MUSTAND / DRAFT", alpha 0.06, and a small
amber notice under the cover title: "MUSTAND — vajab kliiniku meditsiinitöötajate ülevaatust".

## 8. Page wireframes (A5, 148 × 210)

### Cover
```
┌──────────────────────────────┐
│ [logo 32w]                   │  14 from top
│                              │
│   ╭──────────────────────╮   │  soft blob shapes in theme/primary tint
│   │   ILLUSTRATION 90w   │   │  single procedure: its art; multi: up to 3 small arts
│   │                      │   │
│   ╰──────────────────────╯   │
│                              │
│ Ravijärgne juhend            │  cover-title 30
│ ● Hamba eemaldamine          │  chips, 16 pt, theme dot each
│ ● Igemepuhastus              │
│                              │
│ Mari jaoks · 24.11.2026      │  cover-sub (only if given)
│ [MUSTAND notice if draft]    │
│▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│  bottom band 12 high, primary, clinic name in white
└──────────────────────────────┘
```

### Procedure page(s)
```
┌──────────────────────────────┐
│▓▓▓▓▓▓▓▓ theme strip 3 ▓▓▓▓▓▓▓│
│╭────────────────────────────╮│
││(icon) HAMBARAVI      [art] ││  header band (7.3)
││Hamba eemaldamine           ││
│╰────────────────────────────╯│
│ Summary text (2–3 lines)     │
│                              │
│ Paranemise ajajoon       h2  │
│ ①────②────③────④             │  day ruler
│ 0–24h 1–3p 1.näd  paranem.   │
│ [0–24 h]│ Title              │  vertical steps
│         │ body…              │
│ [1–3 p] │ …                  │
│ ╭ ✓ Tee nii ───────────────╮ │  do card
│ │ ✓ …   ✓ …               │ │
│ ╰─────────────────────────╯ │
│ ╭ ✕ Väldi ─────────────────╮ │  don't card
│ ╰─────────────────────────╯ │
│ ┌ Valu ja ┐ ┌ Söömine ja ┐   │  topic tiles 2-col
│ │ ravimid │ │ joomine    │   │
│ └─────────┘ └────────────┘   │
│ ── disclaimer ───────────────│  footer
│ Lumi Kliinik · +372…   3 / 8 │
└──────────────────────────────┘
```

### General advice page (multi-procedure only)
Same as the procedure page, with a primary header band, "Üldised soovitused", procedure chips, and no timeline.

### Red-flag + follow-up page
```
┌──────────────────────────────┐
│▓▓▓ strip (danger) ▓▓▓▓▓▓▓▓▓▓▓│
│ [RED-FLAG BOX — §7.7]        │
│                              │
│ [Follow-up box(es) — §7.8]   │
│ footer                       │
└──────────────────────────────┘
```

### Back page
```
┌──────────────────────────────┐
│ [logo 40w]                   │
│ Lumi Kliinik                 │  h2
│ (phone) +372 600 0000        │  contact table, icons 5 mm
│ (moon)  Valveliin +372 600 0001
│ (mail)  info@lumikliinik.example
│ (pin)   Näidise tn 1, Tallinn│
│ (clock) E–R 8.00–18.00       │
│ ┌────┐ Skaneeri: juhend      │
│ │ QR │ veebis ja küsi …      │  QR block
│ └────┘ url                   │
│ ╭ Hädaolukorras helista 112 ╮│  small danger pill
│ ┌ disclaimer (soft bg) ─────┐│
│ └───────────────────────────┘│
│ Koostatud 24.11.2026 · v1    │  footer style
└──────────────────────────────┘
```

### A4 tri-fold (P7, outline)
```
 outside:  [ inside flap: red flags ] [ back: contacts + QR ] [ front: cover ]
 inside:   [ timeline              ] [ do / don't           ] [ topics + follow-up ]
```

## 9. Print safety

- **Bleed**: print mode adds 3 mm bleed (mPDF `bleedMargin`, `marks: crop`, larger `sheet-size`). Every element that
  touches the trim edge (page strip, cover band, back band) is drawn 3 mm beyond the trim.
- Safe zone: no text within 5 mm of the trim edge.
- Images ≥ 200 dpi at placed size (target 300); SVG preferred.
- **Grayscale**: `scripts/render-pdfs.sh --gray` renders grayscale PNGs. Check that the do/don't cards differ by icon
  and label, the red-flag box is the darkest block, and the timeline circles stay visible. Body text contrast stays ≥ 7:1.
- Fonts are embedded and subset; no RGB-only effects (transparency is allowed only for the watermark).
- Metadata: `SetTitle("Ravijärgne juhend – <procedures>")`, `SetAuthor(<clinic>)`, `SetSubject`, `SetCreator`, and
  document language via `<html lang="et">` / mPDF `SetLang` (`autoLangToFont` off).

## 10. mPDF CSS support (what to use and what to avoid)

**Use (works reliably in mPDF 8):**
- `@page` with `size`, `sheet-size`, `margin-*`, `margin-header`, `margin-footer`, `odd-/even-header-name`,
  `odd-/even-footer-name`, `marks`, named pages (`@page cover {}` + `page: cover` on a block or `<pagebreak page-selector="cover">`).
- Block elements (`div`, `p`, `h1–h6`): `margin`, `padding`, `border`, **`border-radius`**, `background-color`,
  `background-image` (incl. `linear-gradient`), `width`/`height` in mm, `page-break-before|after: always`,
  `page-break-inside: avoid`, `text-align`, `line-height`, `letter-spacing`, `text-transform`, `color` with `rgba`.
- `position: fixed|absolute` only on top-level divs (not nested), for strips, bands and the cover blobs.
- `float: left|right` with explicit `width` (simple cases only).
- Tables: the **primary layout tool**. `border-collapse`, per-cell `background-color`, `padding`, `vertical-align`,
  `width` in %/mm, `page-break-inside: avoid` on the table, `autosize="1"` off for layout tables.
- Images: `<img>` with SVG, PNG or JPG; `width`/`height` in mm; data URIs are allowed.
- mPDF tags: `<pagebreak>`, `<htmlpageheader>`, `<htmlpagefooter>`, `<sethtmlpagefooter>`, `{PAGENO}`, `{nbpg}`,
  `<watermarktext>`, `<columns>` (avoid unless needed).
- Fonts are registered via the `fontDir` and `fontdata` config. **Not via `@font-face`.**

**Avoid (unsupported or unreliable):**
- `display: flex|grid|inline-block`, CSS variables, `calc()`, `vw/vh`, `rem` (use pt/mm), `::before`/`::after`, `:nth-child`
  and other advanced selectors (prefer classes), `transform`, `box-shadow` (use borders), `overflow`, `position: relative` offsets,
  `max-width` for layout, `border-radius` on table cells (put a rounded div around the table instead), nested floats,
  `@font-face`, external URLs (everything local / data URI).
- In SVG: `<style>`, CSS classes, `<text>`, filters, masks, `clipPath`, `<foreignObject>`, complex gradients. Use plain
  `path/circle/rect/ellipse/polygon` with `fill` attributes.
- Emoji and font glyphs for icons (✓ ✕ ⚠): **always draw them as SVG.**

**Orphan control**: wrap every h2 together with its first block in `<div class="keep">` (`page-break-inside: avoid`).
Header bands always start a new page. The red-flag box moves to a new page if less than 90 mm remain (check with
`$mpdf->y` in a custom tag, or simply put it on its own page).

## 11. Acceptance checklist for every PDF PR

- [ ] PNG renders attached for the sample matrix (PLAN §7), et and en.
- [ ] No text overflow or clipping; nothing within 5 mm of the trim.
- [ ] No orphan headings; cards, timeline steps and the red-flag box never split.
- [ ] `õ ä ö ü š ž` render in every font and weight used.
- [ ] Body ≥ 10.5 pt, headings ≥ 16 pt.
- [ ] Grayscale render is still readable.
- [ ] PDF metadata (title, language) is set; fonts are embedded (`pdffonts` shows `emb yes`).
- [ ] It looks beautiful: balanced whitespace, aligned edges, consistent radii.
