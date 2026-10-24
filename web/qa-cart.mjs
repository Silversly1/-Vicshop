export default async function run(page, ui) {
  const stamp = Date.now().toString().slice(-7);
  const email = 'qa' + stamp + '@test.com';

  await page.goto('http://localhost/web/user-register.php');
  await page.fill('#username', 'qa' + stamp);
  await page.fill('#email', email);
  await page.fill('#password', 'password123');
  await page.click('button[type=submit]');
  await page.waitForTimeout(700);
  await page.fill('#email', email);
  await page.fill('#password', 'password123');
  await page.click('button[type=submit]');
  await page.waitForTimeout(700);

  // add two products
  await page.goto('http://localhost/web/index.php');
  await page.waitForTimeout(300);
  const btns = page.locator('.product-card form button');
  const n = Math.min(await btns.count(), 2);
  for (let i = 0; i < n; i++) {
    await page.locator('.product-card form button').nth(i).click();
    await page.waitForTimeout(500);
    await page.goto('http://localhost/web/index.php');
    await page.waitForTimeout(300);
  }
  await page.goto('http://localhost/web/cart.php');
  await page.waitForTimeout(400);
  await page.screenshot({ path: 'c:\\xampp\\htdocs\\web\\qa-cart.png', fullPage: true });

  const layout = await page.evaluate(() => {
    const r = (sel) => { const el = document.querySelector(sel); if (!el) return 'MISSING'; const b = el.getBoundingClientRect(); return { w: Math.round(b.width), h: Math.round(b.height), x: Math.round(b.x) }; };
    const table = document.querySelector('.cart-table');
    return {
      viewport: window.innerWidth,
      layout: r('.cart-layout'),
      table: r('.cart-table'),
      wrapper: r('.cart-table-wrap'),
      rows: document.querySelectorAll('.cart-table tbody tr').length,
      summary: r('.cart-summary'),
      cells: [...document.querySelectorAll('.cart-table td')].map(td => Math.round(td.getBoundingClientRect().width)),
      rows: document.querySelectorAll('.cart-table tbody tr').length,
      tableOverflow: table ? table.scrollWidth > table.clientWidth : null,
      docOverflow: document.documentElement.scrollWidth > window.innerWidth,
    };
  });

  // mobile view
  await page.setViewportSize({ width: 390, height: 844 });
  await page.waitForTimeout(300);
  await page.screenshot({ path: 'c:\\xampp\\htdocs\\web\\qa-cart-mobile.png', fullPage: true });
  const mobile = await page.evaluate(() => ({
    docOverflow: document.documentElement.scrollWidth > window.innerWidth,
    scrollW: document.documentElement.scrollWidth,
    tableW: document.querySelector('.cart-table')?.getBoundingClientRect().width,
  }));
  return { layout, mobile };
}
