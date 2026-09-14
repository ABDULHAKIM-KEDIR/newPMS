export default async function run(page, ui) {
    await page.locator("input[type=email]").fill("admin@pms.test");
    await page.locator("input[type=password]").fill("password");
    await Promise.all([
        page.waitForNavigation({ waitUntil: "networkidle" }).catch(() => {}),
        page.locator("button[type=submit]").click(),
    ]);
    return {
        url: page.url(),
        body: (await page.textContent("body")).replace(/\s+/g, " ").slice(0, 300),
    };
}
