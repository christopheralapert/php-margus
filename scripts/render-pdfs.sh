#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."
gray=false
if [[ "${1:-}" == "--gray" ]]; then
    gray=true
    shift
fi
if (( $# )); then
    echo 'Usage: scripts/render-pdfs.sh [--gray]' >&2
    exit 1
fi

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
