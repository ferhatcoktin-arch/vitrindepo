// Yerel test sitesinin mobil ve masaüstü ekran görüntülerini alır, sayfa hatalarını raporlar.
// Kullanım (playwright-core kurulu bir klasörden): node <repo>/tema/test/ekran.mjs [adres] [çıktı klasörü]
import { createRequire } from 'node:module';
import { mkdirSync } from 'node:fs';

const require = createRequire(process.cwd() + '/');
const { chromium } = require('playwright-core');

const base = process.argv[2] || 'http://127.0.0.1:8080';
const out = process.argv[3] || 'ekran';
mkdirSync(out, { recursive: true });

const mobile = { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
const desktop = { viewport: { width: 1366, height: 900 } };
const adult = [{ name: 'vt_18', value: '1', url: base }];
const errors = [];

const browser = await chromium.launch({ executablePath: process.env.CHROMIUM || '/opt/pw-browsers/chromium' });

async function shoot(name, device, path, { cookies = adult, full = false, before } = {}) {
	const context = await browser.newContext(device);
	if (cookies.length) {
		await context.addCookies(cookies);
	}
	const page = await context.newPage();
	page.on('pageerror', (e) => errors.push(`${name}: ${e.message}`));
	page.on('console', (m) => m.type() === 'error' && !m.text().includes('404') && errors.push(`${name}: ${m.text()}`));
	await page.goto(base + path, { waitUntil: 'networkidle' });
	if (before) {
		await before(page);
	}
	await page.screenshot({ path: `${out}/${name}.png`, fullPage: full });
	const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
	if (overflow > 0) {
		errors.push(`${name}: sayfa ${overflow}px yana taşıyor`);
	}
	await context.close();
}

await shoot('mobil-18-yas', mobile, '/', { cookies: [] });
await shoot('mobil-ana-sayfa', mobile, '/', { full: true });
await shoot('mobil-kategoriler-paneli', mobile, '/', {
	before: (page) => page.click('.vt-bar [data-vt-open]'),
});
await shoot('mobil-arama-onerisi', mobile, '/', {
	before: async (page) => {
		await page.click('.vt-bar [data-vt-search]');
		await page.keyboard.type('terea');
		await page.waitForSelector('#vt-oneri [role="option"]');
	},
});
await shoot('mobil-kategori-sayfasi', mobile, '/urun-kategori/terea/terea-mentollu/', { full: true });
await shoot('mobil-urun', mobile, '/urun/terea-amber/', { full: true });
await shoot('masaustu-ana-sayfa', desktop, '/', { full: true });
await shoot('masaustu-aroma-mentollu', desktop, '/', {
	before: async (page) => {
		await page.click('label[for="vt-aroma-1"]');
		await page.locator('#vt-baslik-aroma').scrollIntoViewIfNeeded();
	},
});
await shoot('masaustu-menu-acik', desktop, '/urun-kategori/terea/', {
	before: (page) => page.hover('.vt-menu > li:nth-child(2) > a'),
});
await shoot('masaustu-arama-onerisi', desktop, '/', {
	before: async (page) => {
		await page.fill('#vt-s', 'pearl');
		await page.waitForSelector('#vt-oneri [role="option"]');
		await page.keyboard.press('ArrowDown');
	},
});

await browser.close();
console.log(errors.length ? 'SORUNLAR:\n' + errors.join('\n') : 'Sayfa hatası yok, yana taşma yok.');
