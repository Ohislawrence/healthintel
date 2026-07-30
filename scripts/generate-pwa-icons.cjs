/**
 * Generates PWA icon PNG files for HealthIntel.
 *
 * Usage: node scripts/generate-pwa-icons.js
 *
 * Generates icons at sizes: 72, 96, 128, 144, 152, 192, 384, 512
 * plus maskable variants at 192 and 512.
 *
 * Requires: npm install canvas (or use sharp/puppeteer)
 * If canvas is not available, falls back to a pure-buffer approach.
 */

const fs = require('fs');
const path = require('path');

const ICONS_DIR = path.join(__dirname, '..', 'public', 'build', 'assets', 'icons');
const SIZES = [72, 96, 128, 144, 152, 192, 384, 512];
const MASKABLE_SIZES = [192, 512];

// HealthIntel brand colors
const TEAL = '#0E6B5C';
const WHITE = '#FFFFFF';

// Ensure directory exists
if (!fs.existsSync(ICONS_DIR)) {
  fs.mkdirSync(ICONS_DIR, { recursive: true });
}

/**
 * Generates a simple SVG icon and writes it as PNG.
 * Uses a pure buffer approach that doesn't need external dependencies.
 */
function generateIcon(size, maskable = false) {
  const filename = maskable
    ? `maskable-${size}x${size}.png`
    : `icon-${size}x${size}.png`;
  const filepath = path.join(ICONS_DIR, filename);

  // Create a minimal 1x1 transparent PNG as a placeholder
  // Real icons should be generated with a proper image library or designed manually.
  // This creates a valid minimal PNG that browsers accept as a placeholder.
  const png = createMinimalPNG(size);

  fs.writeFileSync(filepath, png);
  console.log(`Created: ${filename}`);
}

/**
 * Creates a minimal valid PNG buffer with a colored background.
 * This is a simplified generator — for production, replace with actual
 * designed icons using a proper image tool.
 */
function createMinimalPNG(size) {
  // PNG Signature
  const signature = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);

  // Build a proper PNG file
  const chunks = [];
  chunks.push(signature);

  // IHDR chunk
  const ihdrData = Buffer.alloc(13);
  ihdrData.writeUInt32BE(size, 0);  // width
  ihdrData.writeUInt32BE(size, 4);  // height
  ihdrData.writeUInt8(8, 8);        // bit depth (RGBA = 8 bits per channel)
  ihdrData.writeUInt8(6, 9);        // color type (RGBA)
  ihdrData.writeUInt8(0, 10);       // compression
  ihdrData.writeUInt8(0, 11);       // filter
  ihdrData.writeUInt8(0, 12);       // interlace
  chunks.push(createChunk('IHDR', ihdrData));

  // Generate pixel data: teal background with a white "H" approximation
  const rawData = Buffer.alloc(size * (4 * size + 1));
  let offset = 0;

  for (let y = 0; y < size; y++) {
    rawData[offset++] = 0; // filter byte (none)
    for (let x = 0; x < size; x++) {
      // Check if this pixel should be white (cross shape forming "H")
      const isCenterHorizontal = y >= size * 0.35 && y <= size * 0.65;
      const isLeftVertical = x <= size * 0.2 && y >= 0 && y < size;
      const isRightVertical = x >= size * 0.8 && y >= 0 && y < size;

      const isWhite = isCenterHorizontal || isLeftVertical || isRightVertical;

      if (isWhite) {
        rawData[offset++] = 255; // R
        rawData[offset++] = 255; // G
        rawData[offset++] = 255; // B
        rawData[offset++] = 255; // A
      } else {
        rawData[offset++] = 14;  // R (0x0E)
        rawData[offset++] = 107; // G (0x6B)
        rawData[offset++] = 92;  // B (0x5C)
        rawData[offset++] = 255; // A
      }
    }
  }

  // Compress using zlib (deflate)
  const zlib = require('zlib');
  const compressed = zlib.deflateSync(rawData);
  chunks.push(createChunk('IDAT', compressed));

  // IEND chunk
  chunks.push(createChunk('IEND', Buffer.alloc(0)));

  return Buffer.concat(chunks);
}

function createChunk(type, data) {
  const length = Buffer.alloc(4);
  length.writeUInt32BE(data.length, 0);

  const typeBuffer = Buffer.from(type, 'ascii');
  const crc = crc32(Buffer.concat([typeBuffer, data]));

  const crcBuffer = Buffer.alloc(4);
  crcBuffer.writeUInt32BE(crc >>> 0, 0);

  return Buffer.concat([length, typeBuffer, data, crcBuffer]);
}

function crc32(buf) {
  let crc = 0xFFFFFFFF;
  for (let i = 0; i < buf.length; i++) {
    crc ^= buf[i];
    for (let j = 0; j < 8; j++) {
      if (crc & 1) {
        crc = (crc >>> 1) ^ 0xEDB88320;
      } else {
        crc = crc >>> 1;
      }
    }
  }
  return (crc ^ 0xFFFFFFFF);
}

// Generate all icons
console.log('Generating HealthIntel PWA icons...');
SIZES.forEach((size) => generateIcon(size));
MASKABLE_SIZES.forEach((size) => generateIcon(size, true));

// Also generate a favicon-sized copy
const faviconPath = path.join(ICONS_DIR, 'icon-72x72.png');
try {
  fs.copyFileSync(path.join(ICONS_DIR, 'icon-72x72.png'), path.join(ICONS_DIR, 'favicon-32x32.png'));
} catch {}

console.log('\nDone! Icons generated in:', ICONS_DIR);
console.log('\nNOTE: These are placeholder icons. For production, replace them with');
console.log('properly designed icons using a tool like Adobe Illustrator, Figma, or');
console.log('an online PWA icon generator (e.g., https://progressier.com/pwa-icons-generator).');