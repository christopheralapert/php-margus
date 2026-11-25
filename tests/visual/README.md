# A5 PDF visual QA

Run from the repository root, in Git Bash on Windows or Bash on Ubuntu. PHP,
Composer dependencies and Poppler (`pdftoppm`, `pdffonts`, `pdfinfo`, `pdftotext`)
must be available locally; no network or browser is needed.

```bash
php artisan leaflet:render-samples && bash scripts/render-pdfs.sh --gray
```

Samples use a separate in-memory SQLite database and freeze the content clock at
2026-11-24 10:00:00. They never seed the development database. PDFs and PNGs go
under the git-ignored `tests/visual/output/`; PNGs are in `output/png/` at 110 dpi.
Text stays embedded vector text in the PDFs; PNGs are review previews only.
Open every colour page and its `-gray-` counterpart, including the English sample
b, category proof and icon proof. `--only=f-print-et` renders just the print PDF;
`--print` applies print mode to other samples for additional checks.

```bash
php artisan leaflet:render-samples && bash scripts/render-pdfs.sh --compare
```

Comparison also renders grayscale. The table lists unchanged, changed, new and
missing pages across the union of `output/png/*.png` and `reference/*.png`.
ImageMagick `magick compare -metric AE` (or `compare -metric AE`) reports the
number of different pixels when installed. Otherwise `cksum` compares PNG bytes
and lengths. New pages outside the reference subset are expected. Differences
always exit 0; failed rendering or font embedding still fails the script.
Use the same Poppler version, fonts, application URL and clinic settings when
comparing: platform antialiasing, URL/QR changes and font updates can change PNGs.

After reviewing the output, replace the reference set with:

```bash
php artisan leaflet:render-samples && bash scripts/render-pdfs.sh --update-reference --compare
```

This copies every colour page of a, c and f plus grayscale a into the tracked
`reference/` directory and removes stale reference pages for those samples.
References use 110 dpi (no downsampling); keep the set below 8 MB. Leave generated
PDFs and other output untracked. The lead engineer commits the reviewed PNGs.

## Print layout

`PdfRenderer::render($doc, printMode: true)` uses mPDF's supported `sheet-size`
and `marks: crop` page CSS, `bleedMargin`, `cropMarkMargin`, `cropMarkLength` and
`mirrorMargins` configuration. MediaBox is 168 x 230 mm; TrimBox is inset 10 mm
on each side (148 x 210 mm); BleedBox extends 3 mm beyond trim. Crop marks start
4 mm outside trim and are 5 mm long, clear of bleed. The non-print border is
1 mm; mPDF's 8 mm default would reverse the marks on this small surround. Content margins alternate
inner 16 / outer 12 mm. The strip and cover/back bands extend through the bleed.

Padding uses one real mPDF layout to read `$mpdf->page` before serialization.
If necessary, a second layout inserts `(4 - pages % 4) % 4` notes pages before
the back page. Already complete booklets and screen PDFs need only one layout.
Notes have 18 vector hairlines at 8 mm intervals and the usual running footer.
The category proof deliberately omits booklet cover/back/padding.

mPDF 8.3.1's `src/Writer/MetadataWriter.php` calls `time()` directly for both
CreationDate and ModDate and exposes no timestamp setter/configuration option.
These internal dates remain real time; the visible generation date is frozen.
No vendor patch or binary PDF rewriting is used. These metadata timestamps do
not affect page pixels; PDF byte checksums themselves are not reference tests.

## Acceptance checklist (PDF_DESIGN section 11)

- Review the entire matrix, colour and grayscale, including English where present.
- No overflow, clipping or text within 5 mm of trim; check print crop/bleed too.
- No orphan headings; cards, timeline steps and the red-flag box stay together.
- Check `õ ä ö ü š ž Õ Ä Ö Ü Š Ž` and punctuation in every font and weight.
- Body at least 10.5 pt, headings at least 16 pt; no table shrinkage reducing type.
- Grayscale retains readable text, timeline circles, labelled do/don't icons and
  the prominent red-flag box; body contrast at least 7:1.
- Localized title, subject, clinic author, creator and `/Lang` metadata present;
  `pdffonts` must report `emb yes` (the script checks this automatically).
- Balanced whitespace, aligned edges, consistent radii; back-page 112 is Nunito 800.
- Print page count divisible by four, localized notes only when needed, back last.

Useful checks:

```bash
pdfinfo -box tests/visual/output/f-print-et.pdf
pdffonts tests/visual/output/a-dental-et.pdf
pdftotext -bbox tests/visual/output/f-print-et.pdf -
php artisan test
vendor/bin/pint --test
```

## T3.8 review record

All numbered pages below were opened in colour and grayscale contact sheets;
print notes and the long multi-procedure URL were also opened at full size.
The matrix contains 59 pages / 118 PNGs. No safe-zone violations were found by
Poppler word bounding boxes. All fonts are embedded and subset. The existing
font coverage test checks the required glyphs in all six static TTF files.
An additional temporary font proof was reviewed in all six font slots:
`output/font-proof-1.png`, `output/font-proof-2.png`,
`output/font-proof-gray-1.png`, `output/font-proof-gray-2.png`.

| Sample prefix | Colour suffixes | Grayscale suffixes |
| --- | --- | --- |
| `a-dental-et` | `-1.png` through `-6.png` | `-gray-1.png` through `-gray-6.png` |
| `b-dental-en` | `-1.png` through `-6.png` | `-gray-1.png` through `-gray-6.png` |
| `c-multi-et` | `-01.png` through `-11.png` | `-gray-01.png` through `-gray-11.png` |
| `d-long-et` | `-1.png` through `-6.png` | `-gray-1.png` through `-gray-6.png` |
| `e-anon-et` | `-1.png` through `-6.png` | `-gray-1.png` through `-gray-6.png` |
| `f-print-et` | `-1.png` through `-8.png` | `-gray-1.png` through `-gray-8.png` |
| `g-ent-et` | `-1.png` through `-6.png` | `-gray-1.png` through `-gray-6.png` |
| `categories` | `-1.png` through `-9.png` | `-gray-1.png` through `-gray-9.png` |
| `icons` | `-1.png` | `-gray-1.png` |

Fixed: missing print padding/notes, crop marks overlapping bleed, back-page 112
weight, and the unbreakable multi-procedure URL. Verified the existing bleed
decorations and mirrored margins. Trying s6 general-advice gaps made sample c
grow from 11 to 12 pages, so its existing compact 1 mm gaps were retained as
allowed by T3.8. References contain 31 PNGs: colour a/c/f and grayscale a.
The reference set is 2,207,392 bytes (about 2.11 MiB) at 110 dpi. Git Bash
comparison checks exercised the checksum fallback, including changed, new,
missing and unchanged pages; ImageMagick was unavailable on this machine.
