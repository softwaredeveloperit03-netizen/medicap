const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', (e) => errors.push(`PAGE: ${e.message}`));
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      errors.push(`CONSOLE: ${msg.text()}`);
    }
  });

  await page.goto('http://192.168.1.114:2222/#/', {
    waitUntil: 'networkidle',
    timeout: 60000,
  });
  await page.waitForTimeout(5000);

  const rootHtml = await page.locator('app-root').innerHTML();
  const loginVisible = await page.locator('.login-wrapper').count();
  const navbarVisible = await page.locator('app-navbar').count();
  const bodyText = await page.locator('body').innerText();

  console.log('ROOT length:', rootHtml.length);
  console.log('login wrappers:', loginVisible);
  console.log('navbars:', navbarVisible);
  console.log('body text preview:', JSON.stringify(bodyText.slice(0, 200)));
  console.log('ERRORS:');
  errors.slice(0, 30).forEach((e) => console.log(e));

  await browser.close();
})().catch((e) => {
  console.error('FAILED:', e.message);
  process.exit(1);
});
