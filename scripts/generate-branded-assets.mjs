import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const ASSETS_DIR = path.resolve(process.cwd(), 'assets');
const ICON_PATH = path.join(ASSETS_DIR, 'icon.png');
const SPLASH_PATH = path.join(ASSETS_DIR, 'splash.png');

// Brand colors (match SplashScreen config)
const BRAND_GREEN = '#065f46';
const WHITE = '#ffffff';

function hexToRgb(hex) {
  const n = parseInt(hex.replace('#', ''), 16);
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}

// ---- Minimal PNG writer for 8‑bit RGB (no alpha) ----
function crc32(buf) {
  let c = ~0;
  for (let i = 0; i < buf.length; i++) {
    c ^= buf[i];
    for (let k = 0; k < 8; k++) c = (c >>> 1) ^ (0xEDB88320 & (-(c & 1)));
  }
  return ~c >>> 0;
}

function chunk(type, data) {
  const typeBuf = Buffer.from(type, 'ascii');
  const lenBuf = Buffer.alloc(4);
  lenBuf.writeUInt32BE(data.length, 0);
  const crcBuf = Buffer.alloc(4);
  const crc = crc32(Buffer.concat([typeBuf, data]));
  crcBuf.writeUInt32BE(crc, 0);
  return Buffer.concat([lenBuf, typeBuf, data, crcBuf]);
}

function encodePNG({ width, height, pixels }) {
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8; // bit depth
  ihdr[9] = 2; // color type RGB
  ihdr[10] = 0; // compression
  ihdr[11] = 0; // filter
  ihdr[12] = 0; // interlace

  const bytesPerPixel = 3;
  const stride = width * bytesPerPixel;
  const raw = Buffer.alloc((stride + 1) * height);
  for (let y = 0; y < height; y++) {
    const rowStart = y * (stride + 1);
    raw[rowStart] = 0; // filter type 0
    pixels.copy(raw, rowStart + 1, y * stride, y * stride + stride);
  }
  const idatData = zlib.deflateSync(raw, { level: 9 });
  const pngSignature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
  return Buffer.concat([pngSignature, chunk('IHDR', ihdr), chunk('IDAT', idatData), chunk('IEND', Buffer.alloc(0))]);
}

function makeSolidRgbBuffer(width, height, { r, g, b }) {
  const buf = Buffer.alloc(width * height * 3);
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const i = (y * width + x) * 3;
      buf[i] = r; buf[i + 1] = g; buf[i + 2] = b;
    }
  }
  return buf;
}

function drawFilledCircleRgb(buf, width, height, cx, cy, radius, color) {
  const { r, g, b } = color;
  const r2 = radius * radius;
  const x0 = Math.max(0, Math.floor(cx - radius));
  const y0 = Math.max(0, Math.floor(cy - radius));
  const x1 = Math.min(width - 1, Math.ceil(cx + radius));
  const y1 = Math.min(height - 1, Math.ceil(cy + radius));
  for (let y = y0; y <= y1; y++) {
    for (let x = x0; x <= x1; x++) {
      const dx = x - cx;
      const dy = y - cy;
      if (dx * dx + dy * dy <= r2) {
        const i = (y * width + x) * 3;
        buf[i] = r; buf[i + 1] = g; buf[i + 2] = b;
      }
    }
  }
}

function drawFilledRectRgb(buf, width, height, x, y, w, h, color) {
  const { r, g, b } = color;
  const x0 = Math.max(0, Math.floor(x));
  const y0 = Math.max(0, Math.floor(y));
  const x1 = Math.min(width, Math.floor(x + w));
  const y1 = Math.min(height, Math.floor(y + h));
  for (let yy = y0; yy < y1; yy++) {
    let i = (yy * width + x0) * 3;
    for (let xx = x0; xx < x1; xx++) {
      buf[i++] = r; buf[i++] = g; buf[i++] = b;
    }
  }
}

// Simple 5x7 pixel font for A, S, L, M
// 1 = filled pixel, 0 = empty
const FONT_5x7 = {
  A: [
    0,1,1,1,0,
    1,0,0,0,1,
    1,0,0,0,1,
    1,1,1,1,1,
    1,0,0,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
  ],
  S: [
    0,1,1,1,1,
    1,0,0,0,0,
    1,0,0,0,0,
    0,1,1,1,0,
    0,0,0,0,1,
    0,0,0,0,1,
    1,1,1,1,0,
  ],
  L: [
    1,0,0,0,0,
    1,0,0,0,0,
    1,0,0,0,0,
    1,0,0,0,0,
    1,0,0,0,0,
    1,0,0,0,0,
    1,1,1,1,1,
  ],
  M: [
    1,0,0,0,1,
    1,1,0,1,1,
    1,0,1,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
    1,0,0,0,1,
  ],
};

function drawGlyph5x7(buf, width, height, gx, gy, scale, color, glyph) {
  const gw = 5; const gh = 7;
  for (let y = 0; y < gh; y++) {
    for (let x = 0; x < gw; x++) {
      if (glyph[y * gw + x]) {
        drawFilledRectRgb(buf, width, height, gx + x * scale, gy + y * scale, scale, scale, color);
      }
    }
  }
}

function measureText5x7(text, scale, letterSpacing = 1) {
  const gw = 5; const gh = 7;
  const letters = text.split('');
  const count = letters.length;
  const width = count * gw * scale + (count - 1) * letterSpacing * scale;
  const height = gh * scale;
  return { width, height };
}

function drawText5x7(buf, width, height, x, y, text, scale, color, letterSpacing = 1) {
  const gw = 5;
  let cursorX = x;
  for (const ch of text) {
    const glyph = FONT_5x7[ch];
    if (glyph) {
      drawGlyph5x7(buf, width, height, cursorX, y, scale, color, glyph);
      cursorX += (gw + letterSpacing) * scale;
    } else {
      // Unknown char: advance by space width
      cursorX += (gw + letterSpacing) * scale;
    }
  }
}

function writeFileSyncEnsured(outPath, data) {
  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  fs.writeFileSync(outPath, data);
}

function createBrandedIcon() {
  const size = 1024;
  const bg = hexToRgb(BRAND_GREEN);
  const pixels = makeSolidRgbBuffer(size, size, bg);
  const white = hexToRgb(WHITE);
  const green = bg;

  // Badge circle
  const radius = Math.floor(size * 0.36);
  drawFilledCircleRgb(pixels, size, size, Math.floor(size / 2), Math.floor(size / 2), radius, white);

  // Wordmark inside circle (green on white)
  const text = 'ASSALAM';
  const safeW = Math.floor(radius * 2 * 0.82);
  const safeH = Math.floor(radius * 2 * 0.38);
  // Compute scale
  const gw = 5; const gh = 7; const spacing = 1;
  const unitsW = text.length * gw + (text.length - 1) * spacing;
  const scaleByW = Math.floor(safeW / unitsW);
  const scaleByH = Math.floor(safeH / gh);
  const scale = Math.max(1, Math.min(scaleByW, scaleByH));
  const textW = unitsW * scale;
  const textH = gh * scale;
  const tx = Math.floor(size / 2 - textW / 2);
  const ty = Math.floor(size / 2 - textH / 2);
  drawText5x7(pixels, size, size, tx, ty, text, scale, green, spacing);

  const png = encodePNG({ width: size, height: size, pixels });
  writeFileSyncEnsured(ICON_PATH, png);
}

function createBrandedSplash() {
  const w = 2732; const h = 2732;
  const bg = hexToRgb(BRAND_GREEN);
  const pixels = makeSolidRgbBuffer(w, h, bg);
  const white = hexToRgb(WHITE);

  const text = 'ASSALAM';
  const gw = 5; const gh = 7; const spacing = 1;
  const unitsW = text.length * gw + (text.length - 1) * spacing;
  const safeW = Math.floor(w * 0.72);
  const safeH = Math.floor(h * 0.16);
  const scaleByW = Math.floor(safeW / unitsW);
  const scaleByH = Math.floor(safeH / gh);
  const scale = Math.max(1, Math.min(scaleByW, scaleByH));
  const textW = unitsW * scale;
  const textH = gh * scale;
  const tx = Math.floor(w / 2 - textW / 2);
  const ty = Math.floor(h / 2 - textH / 2);
  drawText5x7(pixels, w, h, tx, ty, text, scale, white, spacing);

  const png = encodePNG({ width: w, height: h, pixels });
  writeFileSyncEnsured(SPLASH_PATH, png);
}

function main() {
  const force = process.argv.includes('--force') || process.argv.includes('-f');
  fs.mkdirSync(ASSETS_DIR, { recursive: true });

  if (!force && fs.existsSync(ICON_PATH)) {
    console.log('icon.png exists — use --force to overwrite. Skipping.');
  } else {
    createBrandedIcon();
    console.log(`Wrote ${ICON_PATH}`);
  }

  if (!force && fs.existsSync(SPLASH_PATH)) {
    console.log('splash.png exists — use --force to overwrite. Skipping.');
  } else {
    createBrandedSplash();
    console.log(`Wrote ${SPLASH_PATH}`);
  }

  console.log('Branded assets ready. Next: npm run cap:assets && npm run cap:sync');
}

main();
