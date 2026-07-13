#!/usr/bin/env bash
# Baut ein sauberes, installierbares AirlineInfoPulse-Release-ZIP.
# Ausgabe: dist/AirlineInfoPulse-v<version>.zip — GENAU EIN Top-Level-Ordner
# "AirlineInfoPulse/" mit ausschließlich Laufzeit-Dateien.
#
# NICHT `git archive` benutzen: das packt docs/, .github/, .gitignore & Co. mit ein.
# Ein phpVMS-Modul-ZIP enthält nur, was auf dem Host zur Laufzeit gebraucht wird
# (Vorbild: HelpDesk, SkyOps, PaxStudio).
set -euo pipefail
cd "$(dirname "$0")/.."   # Repo-Wurzel

NAME="AirlineInfoPulse"
VERSION="$(grep -oE '"version": *"[0-9]+\.[0-9]+\.[0-9]+"' module.json | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
OUT="dist"
STAGE="$OUT/$NAME"
ZIP="$OUT/$NAME-v$VERSION.zip"

rm -rf "$STAGE" "$ZIP"
mkdir -p "$STAGE"

INCLUDE=(
  module.json composer.json LICENSE README.md CHANGELOG.md PILOTGUIDE.md
  Config Helpers Http Observers Providers Resources
)
for item in "${INCLUDE[@]}"; do
  [ -e "$item" ] && cp -R "$item" "$STAGE/"
done

# Vollständigkeits-Wächter: jedes getrackte Top-Level-Verzeichnis muss entweder ins ZIP
# oder bewusst ausgeschlossen sein — sonst fällt ein neues Verzeichnis still hinten runter.
EXCLUDE_OK="docs .github tests dist"
MISSING=""
for dir in $(git ls-files | grep '/' | cut -d/ -f1 | sort -u); do
  case " ${INCLUDE[*]} $EXCLUDE_OK " in
    *" $dir "*) ;;
    *) MISSING="$MISSING $dir" ;;
  esac
done
if [ -n "$MISSING" ]; then
  echo "ABBRUCH: Verzeichnis(se) weder eingepackt noch bewusst ausgeschlossen:$MISSING" >&2
  exit 1
fi

find "$STAGE" \( -name '.DS_Store' -o -name '._*' \) -delete 2>/dev/null || true
( cd "$OUT" && COPYFILE_DISABLE=1 zip -r -X "$(basename "$ZIP")" "$NAME" \
    -x '*.DS_Store' '*/._*' '__MACOSX/*' >/dev/null )

echo "Gebaut: $ZIP"
