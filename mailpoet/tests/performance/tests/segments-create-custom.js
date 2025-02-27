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
  defaultListName,
  segmentsPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import {
  login,
  selectInReact,
  focusAndClick,
  waitForSelectorToBeClickable,
} from '../utils/helpers.js';

export async function segmentsCreateCustom() {
  const page = await browser.newPage();

  try {
    const complexSegmentName = 'Complex Segment ' + Date.now();

    // Log in to WP Admin
    await login(page);

    // Go to the segments page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-segments`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_01.png',
      fullPage: fullPageSet,
    });

    // Click to add a new segment
    await page.waitForSelector('[data-automation-id="new-segment"]');
    await page.locator('[data-automation-id="new-segment"]').click();
    await page.waitForSelector('[data-automation-id="new-custom-segment"]');
    await page.locator('[data-automation-id="new-custom-segment"]').click();
    await page
      .locator('[data-automation-id="input-name"]')
      .type(complexSegmentName, { delay: 25 });

    // Select "Subscribed to a list" action
    await selectInReact(page, '#react-select-2-input', 'subscribed to list');
    await selectInReact(page, '#react-select-4-input', defaultListName);
    await page.waitForSelector('.mailpoet-form-notice-message');
    const noticeElement = await page
      .locator('.mailpoet-form-notice-message')
      .innerText();
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see calculating message 1st time', async () => {
        expect(noticeElement).to.contain('Calculating segment size…');
      });
    });
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_02.png',
      fullPage: fullPageSet,
    });

    // Click to add a new segment action
    await page
      .locator('div.mailpoet-segments-conditions-bottom > button')
      .click();

    // Select "Subscribed date" action
    await selectInReact(page, '#react-select-5-input', 'subscribed date');
    await page.waitForSelector('.mailpoet-form-notice-message');
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see calculating message 2nd time', async () => {
        expect(noticeElement).to.contain('Calculating segment size…');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_03.png',
      fullPage: fullPageSet,
    });

    // Click to add a new segment action
    await page
      .locator('div.mailpoet-segments-conditions-bottom > button')
      .click();

    // WordPress user role action has been automatically added
    // Select a WP user role
    await selectInReact(page, '#react-select-8-input', 'Administrator');
    await page.waitForSelector('.mailpoet-form-notice-message');
    await page.waitForLoadState('networkidle');
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see Calculating message 3rd time', async () => {
        expect(noticeElement).to.contain('Calculating segment size…');
      });
    });
    await page
      .locator('[data-automation-id="dynamic-segment-condition-type-or"]')
      .click();
    await page.waitForSelector('.mailpoet-form-notice-message');
    await page.waitForLoadState('networkidle');

    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_04.png',
      fullPage: fullPageSet,
    });

    // Save the segment
    await waitForSelectorToBeClickable(
      page,
      'div.mailpoet-form-actions > button',
    );
    await focusAndClick(page, 'div.mailpoet-form-actions > button');
    await page.waitForSelector('[data-automation-id="new-segment"]', {
      state: 'visible',
    });
    await page.waitForLoadState('networkidle');
    const segmentAddedMessage =
      "//div[@class='notice-success'].//p[starts-with(text(),'Segment successfully added!')]";
    const segmentNoticeMessage = await page.locator(segmentAddedMessage);
    describe(segmentsPageTitle, () => {
      describe('segments-create-custom: should be able to see Segment Added message', async () => {
        expect(segmentNoticeMessage).to.exist;
      });
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Segments_Create_Custom_05.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function segmentsCreateCustomTest() {
  await segmentsCreateCustom();
}
