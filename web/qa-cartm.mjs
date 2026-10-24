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
  await page.goto('http://localhost/web/index.php');
  await page.waitForTimeout(300);
  await page.locator('.product-card form button').nth(0).click();
  await page.waitForTimeout(600);

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('http://localhost/web/cart.php');
  await page.waitForTimeout(500);

  const info = await page.evaluate(() => {
    const rect = (el) => { if (!el) return null; const b = el.getBoundingClientRect(); return { w: Math.round(b.width), x: Math.round(b.x), right: Math.round(b.right), h: Math.round(b.height) }; };
    const rows = [...document.querySelectorAll('.summary-row')].map(el => ({
      cls: el.className,
      text: el.innerText.replace(/\n/g, ' | '),
      rect: rect(el),
      spans: [...el.children].map(c => ({ text: c.innerText, rect: rect(c), display: getComputedStyle(c).display, visibility: getComputedStyle(c).visibility, color: getComputedStyle(c).color, opacity: getComputedStyle(c).opacity })),
    }));
    const container = document.querySelector('.container');
    const layout = document.querySelector('.cart-layout');
    return {
      bodyW: document.body.getBoundingClientRect().width,
      containerW: rect(container),
      layoutW: rect(layout),
      layoutCols: getComputedStyle(layout).gridTemplateColumns,
      layoutDisplay: getComputedStyle(layout).display,
      summary: rect(document.querySelector('.cart-summary')),
      rows,
      actions: rect(document.querySelector('.cart-actions')),
      btn: rect(document.querySelector('.cart-actions .btn')),
      wrap: rect(document.querySelector('.cart-table-wrap')),
      tableW: document.querySelector('.cart-table').getBoundingClientRect().width,
    };
  });
  await page.screenshot({ path: 'c:\\xampp\\htdocs\\web\\qa-cart-mobile.png', fullPage: true });
  return info;
}
