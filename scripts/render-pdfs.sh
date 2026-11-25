#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
gray=false
compare_pages=false
update_reference=false
for option in "$@"; do
    case "$option" in
        --gray) gray=true ;;
        --compare) compare_pages=true; gray=true ;;
        --update-reference) update_reference=true; gray=true ;;
        *) echo 'Usage: scripts/render-pdfs.sh [--gray] [--compare] [--update-reference]' >&2; exit 1 ;;
    esac
done

shopt -s nullglob
pdfs=(tests/visual/output/*.pdf)
if (( ${#pdfs[@]} == 0 )); then
    echo 'No PDFs found. Run php artisan leaflet:render-samples first.' >&2
    exit 1
fi
mkdir -p tests/visual/output/png
for pdf in "${pdfs[@]}"; do
    name="$(basename "$pdf" .pdf)"
    prefix="tests/visual/output/png/$name"
    # Remove stale pages when a fresh render has fewer pages than the last.
    rm -f -- "$prefix"-[0-9]*.png "$prefix"-gray-[0-9]*.png
    pdftoppm -r 110 -png "$pdf" "$prefix"
    pages=("$prefix"-[0-9]*.png)
    if $gray; then
        pdftoppm -r 110 -gray -png "$pdf" "$prefix-gray"
    fi
    printf '%s: %s pages\n' "$name" "${#pages[@]}"
    fonts="$(pdffonts "$pdf")"
    printf '%s\n' "$fonts"
    # The columns before emb vary (e.g. CID TrueType). Count from the end:
    # emb, sub, uni, object ID (two columns).
    if ! awk 'NR > 2 && NF { count++; if ($(NF-4) != "yes") bad=1 } END { exit (bad || !count) }' <<< "$fonts"; then
        echo "Font embedding check failed: $pdf" >&2
        exit 1
    fi
done

reference=tests/visual/reference
output=tests/visual/output/png
if $update_reference; then
    # Check all required samples before changing the previous reference set.
    for sample in a-dental-et c-multi-et f-print-et; do
        pages=("$output/$sample"-[0-9]*.png)
        if (( ${#pages[@]} == 0 )); then
            echo "Missing reference sample: $sample. Run php artisan leaflet:render-samples first." >&2
            exit 1
        fi
    done
    mkdir -p "$reference"
    for sample in a-dental-et c-multi-et f-print-et; do
        rm -f -- "$reference/$sample"-[0-9]*.png
        cp -- "$output/$sample"-[0-9]*.png "$reference/"
    done
    rm -f -- "$reference/a-dental-et"-gray-[0-9]*.png
    cp -- "$output/a-dental-et"-gray-[0-9]*.png "$reference/"
    printf 'Updated references (colour a/c/f, grayscale a; 110 dpi).\n'
fi

if $compare_pages; then
    # Avoid unrelated commands called compare (including Windows utilities).
    comparator=()
    if command -v magick >/dev/null 2>&1 && magick -version 2>/dev/null | grep -q ImageMagick; then
        comparator=(magick compare)
    elif command -v compare >/dev/null 2>&1 && compare -version 2>/dev/null | grep -q ImageMagick; then
        comparator=(compare)
    fi
    printf '\n%-9s %-44s %s\n' 'Status' 'Page' 'AE pixels / checksum'
    names=()
    for page in "$output"/*.png "$reference"/*.png; do
        names+=("$(basename "$page")")
    done
    if (( ${#names[@]} )); then
        while IFS= read -r name; do
            status=unchanged
            metric='-'
            if [[ ! -f "$reference/$name" ]]; then
                status=new
            elif [[ ! -f "$output/$name" ]]; then
                status=missing
            elif (( ${#comparator[@]} )); then
                if metric=$("${comparator[@]}" -metric AE "$reference/$name" "$output/$name" null: 2>&1); then
                    status=unchanged
                else
                    status=changed
                fi
            elif [[ "$(cksum < "$reference/$name")" != "$(cksum < "$output/$name")" ]]; then
                status=changed
                metric='different checksum'
            else
                metric='equal checksum'
            fi
            printf '%-9s %-44s %s\n' "$status" "$name" "$metric"
        done < <(printf '%s\n' "${names[@]}" | LC_ALL=C sort -u)
    fi
fi
# Visual differences are informational, never a CI gate.
exit 0
