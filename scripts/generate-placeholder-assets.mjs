import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const ASSETS_DIR = path.resolve(process.cwd(), 'assets');
const ICON_PATH = path.join(ASSETS_DIR, 'icon.png');
const SPLASH_PATH = path.join(ASSETS_DIR, 'splash.png');

// Brand-ish green pulled from SplashScreen config (#065f46)
const BRAND_GREEN = '#065f46';
const WHITE = '#ffffff';

function hexToRgb(hex) {
  const n = parseInt(hex.replace('#', ''), 16);
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}

// Minimal PNG writer for 8-bit RGB (no alpha), filter type 0 per line
function crc32(buf) {
  let c = ~0;
  for (let i = 0; i < buf.length; i++) {
    let byte = buf[i];
    c ^= byte;
    for (let k = 0; k < 8; k++) {
      c = (c >>> 1) ^ (0xEDB88320 & (-(c & 1)));
    }
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
  // IHDR
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8; // bit depth
  ihdr[9] = 2; // color type = truecolor RGB
  ihdr[10] = 0; // compression
  ihdr[11] = 0; // filter
  ihdr[12] = 0; // interlace

  // Raw scanlines with filter byte 0 per row
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
  return Buffer.concat([
    pngSignature,
    chunk('IHDR', ihdr),
    chunk('IDAT', idatData),
    chunk('IEND', Buffer.alloc(0)),
  ]);
}

function makeSolidRgbBuffer(width, height, { r, g, b }) {
  const buf = Buffer.alloc(width * height * 3);
  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const i = (y * width + x) * 3;
      buf[i] = r;
      buf[i + 1] = g;
      buf[i + 2] = b;
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
        buf[i] = r;
        buf[i + 1] = g;
        buf[i + 2] = b;
      }
    }
  }
}

function writeFileSyncEnsured(outPath, data) {
  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  fs.writeFileSync(outPath, data);
}

function createIconIfMissing() {
  if (fs.existsSync(ICON_PATH)) {
    console.log('icon.png already exists — skipping');
    return;
  }
  const size = 1024;
  const bg = hexToRgb(BRAND_GREEN);
  const pixels = makeSolidRgbBuffer(size, size, bg);
  const fg = hexToRgb(WHITE);
  drawFilledCircleRgb(pixels, size, size, Math.floor(size / 2), Math.floor(size / 2), Math.floor(size * 0.31), fg);
  const png = encodePNG({ width: size, height: size, pixels });
  writeFileSyncEnsured(ICON_PATH, png);
  console.log(`Created ${ICON_PATH}`);
}

function createSplashIfMissing() {
  if (fs.existsSync(SPLASH_PATH)) {
    console.log('splash.png already exists — skipping');
    return;
  }
  const w = 2732;
  const h = 2732;
  const bg = hexToRgb(BRAND_GREEN);
  const pixels = makeSolidRgbBuffer(w, h, bg);
  const fg = hexToRgb(WHITE);
  drawFilledCircleRgb(pixels, w, h, Math.floor(w / 2), Math.floor(h / 2), Math.floor(Math.min(w, h) * 0.24), fg);
  const png = encodePNG({ width: w, height: h, pixels });
  writeFileSyncEnsured(SPLASH_PATH, png);
  console.log(`Created ${SPLASH_PATH}`);
}

function main() {
  fs.mkdirSync(ASSETS_DIR, { recursive: true });
  createIconIfMissing();
  createSplashIfMissing();
  console.log('Done. You can now run: npm run cap:assets');
}

main();
