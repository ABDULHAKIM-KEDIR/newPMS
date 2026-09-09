export default async function run(page, ui) {
    await page.goto("http://localhost/login");
    const snap = await ui.snapshot();
    console.log("LOGIN PAGE:\n" + snap);
}
