export default async function run(page, ui) {
    await page.locator("input[type=email]").fill("admin@example.com");
    await page.locator("input[type=password]").fill("ChangeMe123!");
    await Promise.all([
        page.waitForNavigation({ waitUntil: "networkidle" }).catch(() => {}),
        page.locator("button[type=submit]").click(),
    ]);
    const url = page.url();
    const body = (await page.textContent("body"))
        .replace(/\s+/g, " ")
        .slice(0, 300);
    return { url, body };
}
