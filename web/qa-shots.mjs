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
  await page.locator('.product-card form button').nth(0).click();
  await page.waitForTimeout(600);

  await page.setViewportSize({ width: 390, height: 844 });
  for (const [p, name] of [['index.php', 'shop'], ['product.php?id=1', 'product'], ['checkout.php', 'checkout']]) {
    await page.goto('http://localhost/web/' + p);
    await page.waitForTimeout(400);
    await page.screenshot({ path: 'c:\\xampp\\htdocs\\web\\qa-m-' + name + '.png', fullPage: true });
  }

  // desktop checkout for comparison
  await page.setViewportSize({ width: 1280, height: 900 });
  await page.goto('http://localhost/web/checkout.php');
  await page.waitForTimeout(400);
  await page.screenshot({ path: 'c:\\xampp\\htdocs\\web\\qa-d-checkout.png', fullPage: true });

  await page.goto('http://localhost/web/index.php');
  await page.waitForTimeout(400);
  await page.screenshot({ path: 'c:\\xampp\\htdocs\\web\\qa-d-shop.png', fullPage: true });
  return { done: true };
}
