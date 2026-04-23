import fs from "node:fs";
import path from "node:path";
import zlib from "node:zlib";

const outputDir = path.resolve(process.cwd(), "public", "icons");
fs.mkdirSync(outputDir, { recursive: true });

function makeCrcTable() {
  const table = new Uint32Array(256);
  for (let n = 0; n < 256; n += 1) {
    let c = n;
    for (let k = 0; k < 8; k += 1) {
      c = (c & 1) ? (0xedb88320 ^ (c >>> 1)) : (c >>> 1);
    }
    table[n] = c >>> 0;
  }
  return table;
}

const crcTable = makeCrcTable();

function crc32(buffer) {
  let c = 0xffffffff;
  for (let i = 0; i < buffer.length; i += 1) {
    c = crcTable[(c ^ buffer[i]) & 0xff] ^ (c >>> 8);
  }
  return (c ^ 0xffffffff) >>> 0;
}

function chunk(type, data) {
  const typeBuf = Buffer.from(type, "ascii");
  const len = Buffer.alloc(4);
  len.writeUInt32BE(data.length, 0);
  const crc = Buffer.alloc(4);
  const crcValue = crc32(Buffer.concat([typeBuf, data]));
  crc.writeUInt32BE(crcValue, 0);
  return Buffer.concat([len, typeBuf, data, crc]);
}

function createPng(width, height, rgbaFn) {
  const signature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(width, 0);
  ihdr.writeUInt32BE(height, 4);
  ihdr[8] = 8;
  ihdr[9] = 6;
  ihdr[10] = 0;
  ihdr[11] = 0;
  ihdr[12] = 0;

  const raw = Buffer.alloc((width * 4 + 1) * height);
  for (let y = 0; y < height; y += 1) {
    const rowOffset = y * (width * 4 + 1);
    raw[rowOffset] = 0;
    for (let x = 0; x < width; x += 1) {
      const pixelOffset = rowOffset + 1 + x * 4;
      const [r, g, b, a] = rgbaFn(x, y, width, height);
      raw[pixelOffset + 0] = r;
      raw[pixelOffset + 1] = g;
      raw[pixelOffset + 2] = b;
      raw[pixelOffset + 3] = a;
    }
  }

  const compressed = zlib.deflateSync(raw, { level: 9 });
  const png = Buffer.concat([
    signature,
    chunk("IHDR", ihdr),
    chunk("IDAT", compressed),
    chunk("IEND", Buffer.alloc(0)),
  ]);
  return png;
}

function brandPixel(x, y, width, height) {
  const centerX = width / 2;
  const centerY = height / 2;
  const dx = x - centerX;
  const dy = y - centerY;
  const distance = Math.sqrt(dx * dx + dy * dy);
  const radius = Math.min(width, height) * 0.44;

  const bgTop = [15, 23, 42];
  const bgBottom = [30, 58, 138];
  const t = y / (height - 1);
  let r = Math.round(bgTop[0] + (bgBottom[0] - bgTop[0]) * t);
  let g = Math.round(bgTop[1] + (bgBottom[1] - bgTop[1]) * t);
  let b = Math.round(bgTop[2] + (bgBottom[2] - bgTop[2]) * t);
  let a = 255;

  if (distance <= radius) {
    r = Math.min(255, r + 20);
    g = Math.min(255, g + 30);
    b = Math.min(255, b + 40);
  }

  const checkTop = centerY - radius * 0.15;
  const checkMid = centerY + radius * 0.12;
  const checkRight = centerX + radius * 0.42;
  const checkLeft = centerX - radius * 0.35;
  const stroke = Math.max(2, Math.round(width * 0.03));

  const line1 = Math.abs((y - checkTop) - ((checkMid - checkTop) / (centerX - checkLeft)) * (x - checkLeft));
  const line2 = Math.abs((y - checkMid) - ((checkTop - checkMid) / (checkRight - centerX)) * (x - centerX));
  const onSegment1 = x >= checkLeft && x <= centerX && y >= checkTop - stroke && y <= checkMid + stroke;
  const onSegment2 = x >= centerX && x <= checkRight && y >= checkTop - stroke && y <= checkMid + stroke;

  if ((onSegment1 && line1 <= stroke) || (onSegment2 && line2 <= stroke)) {
    r = 248;
    g = 250;
    b = 252;
    a = 255;
  }

  return [r, g, b, a];
}

function writeIcon(filename, size) {
  const png = createPng(size, size, brandPixel);
  fs.writeFileSync(path.join(outputDir, filename), png);
  console.log(`generated: ${filename}`);
}

writeIcon("icon-192.png", 192);
writeIcon("icon-512.png", 512);
writeIcon("icon-maskable-512.png", 512);
