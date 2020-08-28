const puppeteer = require('puppeteer');

(async (arguments) => {
    if (arguments.length !== 1) {
        process.exit(1);
    }

    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    page.setUserAgent('Depictr');

    await page.goto(arguments[0]);
    process.stdout.write(await page.content());

    await browser.close();
})(process.argv.slice(2));
