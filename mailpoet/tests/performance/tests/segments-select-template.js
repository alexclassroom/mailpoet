/**
 * External dependencies
 */
import { sleep } from 'k6';
import { browser } from 'k6/browser';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.5.0/index.js';
import {
  expect,
  describe,
} from 'https://jslib.k6.io/k6chaijs/4.5.0.0/index.js';

/**
 * Internal dependencies
 */
import {
  baseURL,
  thinkTimeMin,
  thinkTimeMax,
  segmentsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import {
  login,
  focusAndClick,
  waitForSelectorToBeClickable,
  clickFirstSelector,
} from '../utils/helpers.js';

export async function segmentsSelectTemplate() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the segments page
    await page.goto(
      `${baseURL}/wp-admin/admin.php?page=mailpoet-segments#/segment-templates`,
      {
        waitUntil: 'networkidle',
      },
    );

    await page.waitForSelector('.wp-heading-inline');
    await page.screenshot({
      path: screenshotPath + 'Segments_Select_Template_01.png',
      fullPage: fullPageSet,
    });

    // Select any segment's template on page
    await clickFirstSelector(page, '.mailpoet-templates-card');
    await page.waitForSelector('[data-automation-id="select-segment-action"]');

    await page.screenshot({
      path: screenshotPath + 'Segments_Select_Template_02.png',
      fullPage: fullPageSet,
    });

    // Save the segment
    await waitForSelectorToBeClickable(
      page,
      'div.mailpoet-form-actions > button',
    );
    await focusAndClick(page, 'div.mailpoet-form-actions > button');

    await page.waitForSelector('[data-automation-id="select_all"]');
    const segmentUpdatedMessage =
      "//div[@class='notice-success'].//p[starts-with(text(),'Segment successfully updated!')]";
    const noticeElement = await page.locator(segmentUpdatedMessage);
    describe(segmentsPageTitle, () => {
      describe('segments-select-template: should be able to see Segment Updated message', async () => {
        expect(noticeElement).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Select_Template_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function segmentsSelectTemplateTest() {
  await segmentsSelectTemplate();
}
