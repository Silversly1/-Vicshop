export default async function run(page, ui) {
  const stamp = Date.now().toString().slice(-7);
  const email = 'qa' + stamp + '@test.com';
  const out = {};

  // register + login as customer
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

  const audit = async (url, label) => {
    await page.goto(url);
    await page.waitForTimeout(350);
    const r = await page.evaluate(() => {
      const over = [...document.body.querySelectorAll('*')].filter(el => {
        const b = el.getBoundingClientRect();
        const cs = getComputedStyle(el);
        if (cs.position === 'fixed' || cs.position === 'absolute') return false;
        if (cs.overflowX !== 'visible') return false;
        return b.right > window.innerWidth + 1 || b.x < -1;
      }).slice(0, 6).map(el => el.tagName + '.' + String(el.className).split(' ')[0] + ' right=' + Math.round(el.getBoundingClientRect().right));
      return {
        title: document.title,
        docOverflow: document.documentElement.scrollWidth > window.innerWidth,
        scrollW: document.documentElement.scrollWidth,
        overflowing: over,
      };
    });
    out[label] = r;
  };

  const pages = [
    ['index.php', 'shop'],
    ['product.php?id=1', 'product'],
    ['cart.php', 'cart'],
    ['checkout.php', 'checkout'],
    ['order-confirmed.php?order_id=1', 'confirmed'],
    ['user-login.php', 'login'],
    ['user-register.php', 'register'],
  ];

  // add an item so cart/checkout are non-empty
  await page.goto('http://localhost/web/index.php');
  await page.locator('.product-card form button').nth(0).click();
  await page.waitForTimeout(600);

  for (const [p, label] of pages) {
    await audit('http://localhost/web/' + p, 'desktop:' + label);
  }
  for (const [p, label] of pages) {
    await page.setViewportSize({ width: 390, height: 844 });
    await audit('http://localhost/web/' + p, 'mobile:' + label);
    await page.setViewportSize({ width: 1280, height: 900 });
  }
  return out;
}
