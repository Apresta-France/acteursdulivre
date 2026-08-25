const fs = require('fs');
const path = require('path');
const html = fs.readFileSync('c:/_DEV/acteursdulivre/_extracted_admin/template.html', 'utf8');
const out = 'c:/_DEV/acteursdulivre/_extracted_admin/screens';
fs.mkdirSync(out, { recursive: true });

const MAP = {
  isDash: 'dash',
  isVerif: 'verif',
  isModeration: 'moderation',
  isUsers: 'users',
  isCatalogue: 'catalogue',
  isMissions: 'missions',
  isFinances: 'finances',
  isLitiges: 'litiges',
  isAvis: 'avis',
  isPreouverture: 'preouverture',
  isCms: 'cms',
  isReglages: 'reglages',
  isSignales: 'signales',
};

function extractBlock(src, condKey) {
  const needle = `value="{{ ${condKey} }}"`;
  const idx = src.indexOf(needle);
  if (idx === -1) return null;
  const start = src.indexOf('>', idx) + 1;
  let depth = 1;
  let i = start;
  while (i < src.length && depth > 0) {
    const nextOpen = src.indexOf('<sc-if', i);
    const nextClose = src.indexOf('</sc-if>', i);
    if (nextClose === -1) break;
    if (nextOpen !== -1 && nextOpen < nextClose) {
      depth++;
      i = nextOpen + 6;
    } else {
      depth--;
      if (depth === 0) return src.slice(start, nextClose);
      i = nextClose + 8;
    }
  }
  return null;
}

for (const [cond, name] of Object.entries(MAP)) {
  const block = extractBlock(html, cond);
  if (!block) {
    console.log('MISSING', name);
    continue;
  }
  fs.writeFileSync(path.join(out, name + '.html'), block.trim() + '\n');
  console.log('OK', name, block.length);
}

// layout bits
const dcStart = html.indexOf('<x-dc>');
const dc = html.slice(dcStart);
fs.writeFileSync(path.join(out, '_full_dc.html'), dc);
console.log('DC', dc.length);
