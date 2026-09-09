export default async function run(page, ui) {
    // Sign in
    await page.locator("input[type=email]").fill("admin@example.com");
    await page.locator("input[type=password]").fill("ChangeMe123!");
    await page.locator("button[type=submit]").click();
    await page.waitForLoadState("networkidle");

    // Go to a project edit page
    const projects = await page.goto("http://newPMS.test/projects");
    await page.waitForLoadState("networkidle");
    const links = await page.evaluate(() =>
        Array.from(document.querySelectorAll('a[href*="/projects/"]'))
            .map((a) => a.getAttribute("href"))
            .filter((h) => /\/projects\/\d+$/.test(h)),
    );
    if (!links.length)
        return {
            error: "no projects found",
            text: (await page.textContent("body")).slice(0, 500),
        };
    await page.goto("http://newPMS.test" + links[0] + "/edit");
    await page.waitForLoadState("networkidle");

    const deleteFormPresent = await page.evaluate(
        () => !!document.querySelector("form[data-confirm]"),
    );
    // Click the delete button
    await page
        .locator("form[data-confirm] button[type=submit]")
        .first()
        .click();
    await page.waitForTimeout(300);

    const modalVisible = await page.evaluate(() => {
        const m = document.getElementById("globalConfirmModal");
        if (!m) return { present: false };
        return {
            present: true,
            display: getComputedStyle(m).display,
            ariaHidden: m.getAttribute("aria-hidden"),
        };
    });
    const shot = "C:/Users/amanu/Herd/newPMS/modal-open.png";
    await page.screenshot({ path: shot });

    // Cancel
    const cancelBtn = await page
        .locator("#gcmCancel")
        .isVisible()
        .catch(() => false);
    if (cancelBtn) await page.locator("#gcmCancel").click();
    await page.waitForTimeout(200);
    const afterCancel = await page.evaluate(
        () =>
            getComputedStyle(document.getElementById("globalConfirmModal"))
                .display,
    );
    return { deleteFormPresent, modalVisible, afterCancel, shot };
}
