#!/usr/bin/env bash
set -e
cd "$(dirname "$0")/.."
FONT=/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf
IMG=assets/images

ph() { # ph <out> <w> <h> <label>
  convert -size "$2x$3" gradient:'#0F2E5A-#1B4373' \
    -font "$FONT" -gravity southwest -pointsize 15 -fill '#FDD701' \
    -annotate +16+14 "PLACEHOLDER $2x$3" "$IMG/$1"
}

ph landings/home/hero-collage.png 900 700 'COLLAGE HERO'
ph landings/home/aula.jpg 1400 600 'FOTO AULA MEDELLIN'
convert -size 1000x620 gradient:'#0F2E5A-#1B4373' "$IMG/landings/home/faq.jpg"
for i in 1 2 3 4; do ph "landings/home/blog-$i.jpg" 600 400 "IMAGEN BLOG $i"; done

# --- Logo CITA (header) ---
cat > "$IMG/logos/logo-cita.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 250 86" role="img" aria-label="CITA Comparendo">
  <g font-family="Montserrat, Arial, sans-serif">
    <text x="0" y="42" font-size="40" font-weight="800" font-style="italic" fill="#0F2E5A" letter-spacing="-1">CITA</text>
    <path d="M104 6 L126 6 L104 46 L82 46 Z" fill="#FDD701"/>
    <text x="0" y="64" font-size="19" font-weight="800" fill="#0F2E5A" letter-spacing="0.5">COMPARENDO</text>
    <text x="0" y="80" font-size="15" font-weight="700" fill="#0F2E5A">.COM</text>
  </g>
</svg>
SVG

# --- Logo CITA (footer, sobre navy) ---
cat > "$IMG/logos/logo-cita-footer.png" <<'PNG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 250 86" role="img" aria-label="CITA Comparendo">
  <g font-family="Montserrat, Arial, sans-serif">
    <text x="0" y="42" font-size="40" font-weight="800" font-style="italic" fill="#FFFFFF" letter-spacing="-1">CITA</text>
    <path d="M104 6 L126 6 L104 46 L82 46 Z" fill="#FDD701"/>
    <text x="0" y="64" font-size="19" font-weight="800" fill="#FDD701" letter-spacing="0.5">COMPARENDO</text>
    <text x="0" y="80" font-size="15" font-weight="700" fill="#FFFFFF">.COM</text>
  </g>
</svg>
SVG

# --- Aliados / pasarelas (placeholders) ---
cat > "$IMG/logos/simit.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40" role="img" aria-label="SIMIT">
  <g fill="#4CAF50" font-family="Montserrat, Arial, sans-serif">
    <text x="4" y="30" font-size="24" font-weight="700">simit</text>
    <circle cx="96" cy="12" r="3"/><circle cx="106" cy="20" r="3"/>
    <circle cx="92" cy="26" r="3"/><circle cx="110" cy="32" r="3"/>
  </g>
</svg>
SVG
cat > "$IMG/logos/runt.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40" role="img" aria-label="RUNT">
  <g font-family="Montserrat, Arial, sans-serif">
    <text x="4" y="26" font-size="26" font-weight="800" fill="#B71C1C">RUNT</text>
    <text x="4" y="37" font-size="6.5" font-weight="600" fill="#37474F">REGISTRO UNICO NACIONAL TRIBUTARIO</text>
  </g>
</svg>
SVG
cat > "$IMG/logos/mintransporte.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130 40" role="img" aria-label="La movilidad es de todos">
  <rect x="0" y="4" width="34" height="30" rx="3" fill="#0F2E5A"/>
  <path d="M8 26 L14 12 L20 26 Z" fill="#FDD701"/>
  <g font-family="Montserrat, Arial, sans-serif">
    <text x="40" y="21" font-size="10" font-weight="700" fill="#0F2E5A">La movilidad</text>
    <text x="40" y="34" font-size="10" font-weight="700" fill="#0F2E5A">es de todos</text>
  </g>
</svg>
SVG
cat > "$IMG/logos/visa.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 24" role="img" aria-label="Visa">
  <text x="2" y="20" font-size="20" font-weight="800" font-style="italic" fill="#1A1F71" font-family="Arial, sans-serif">VISA</text>
</svg>
SVG
cat > "$IMG/logos/mastercard.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 24" role="img" aria-label="Mastercard">
  <circle cx="26" cy="12" r="10" fill="#EB001B"/>
  <circle cx="40" cy="12" r="10" fill="#F79E1B" fill-opacity="0.9"/>
</svg>
SVG
cat > "$IMG/logos/pse.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 24" role="img" aria-label="PSE">
  <text x="2" y="19" font-size="17" font-weight="800" fill="#00953B" font-family="Arial, sans-serif">PSE</text>
</svg>
SVG

# --- Pines del mapa ---
cat > "$IMG/elements/pin-blue.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46">
  <path d="M16 1C7.7 1 1 7.6 1 15.8 1 27 16 45 16 45s15-18 15-29.2C31 7.6 24.3 1 16 1z" fill="#1E7EBB"/>
  <circle cx="16" cy="16" r="6" fill="#BBE3F7"/>
</svg>
SVG
cat > "$IMG/elements/pin-red.svg" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 46">
  <path d="M16 1C7.7 1 1 7.6 1 15.8 1 27 16 45 16 45s15-18 15-29.2C31 7.6 24.3 1 16 1z" fill="#E5362F"/>
  <circle cx="16" cy="16" r="6" fill="#FFFFFF"/>
</svg>
SVG

# --- Screenshot del tema (pestaña Apariencia > Temas) ---
convert -size 1200x900 gradient:'#F5F5ED-#F5F5ED' \
  -fill '#0F2E5A' -draw 'roundrectangle 60,60 1140,250 28,28' \
  -fill '#FDD701' -draw 'roundrectangle 60,280 1140,360 24,24' \
  -draw 'roundrectangle 60,390 585,690 20,20' -draw 'roundrectangle 615,390 1140,690 20,20' \
  -fill '#0F2E5A' -draw 'roundrectangle 60,720 1140,840 20,20' \
  -font "$FONT" -fill '#FDD701' -pointsize 54 -gravity North -annotate +0+120 'CITA COMPARENDO' \
  -fill '#0F2E5A' -pointsize 30 -gravity North -annotate +0+196 'Landing WordPress · Bootstrap 5' \
  screenshot.png

echo "assets generados:"; find assets/images -type f | sort
