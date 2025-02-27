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
  subscribersPageTitle,
  fullPageSet,
  screenshotPath,
} from '../config.js';
import { login, waitForSelectorToBeVisible } from '../utils/helpers.js';

export async function subscribersTrashingRestoring() {
  const page = await browser.newPage();

  try {
    // Log in to WP Admin
    await login(page);

    // Go to the Subscribers page
    await page.goto(`${baseURL}/wp-admin/admin.php?page=mailpoet-subscribers`, {
      waitUntil: 'networkidle',
    });

    await page.waitForLoadState('networkidle');
    await page.screenshot({
      path: screenshotPath + 'Subscribers_Trashing_Restoring_01.png',
      fullPage: fullPageSet,
    });

    // Check the subscribers filter is present
    await page.waitForSelector('[data-automation-id="filters_subscribed"]');
    const listingFilterElement = await page.locator(
      '[data-automation-id="listing_filter_segment"]',
    );
    describe(subscribersPageTitle, () => {
      describe('subscribers-trashing-restoring: should be able to see Lists Filter', async () => {
        expect(listingFilterElement).to.exist;
      });
    });

    // Select all subscribers
    await page.locator('[data-automation-id="select_all"]').click();
    await page.waitForSelector('.mailpoet-listing-select-all');
    await page.locator('.mailpoet-listing-select-all > a').click();
    await page.waitForSelector('.mailpoet-listing-select-all');

    // Move to trash all the subscribers
    await page.locator('[data-automation-id="action-trash"]').click();
    await page.waitForSelector('.notice-success');
    await page.waitForSelector('.colspanchange');
    const noticeElement = await page.locator('.colspanchange').innerText();
    describe(subscribersPageTitle, () => {
      describe('subscribers-trashing-restoring: should be able to see the message', async () => {
        expect(noticeElement).to.contain('No items found.');
      });
    });

    await page.screenshot({
      path: screenshotPath + 'Subscribers_Trashing_Restoring_02.png',
      fullPage: fullPageSet,
    });

    // Restore from trash all the trashed subscribers
    await page.locator('[data-automation-id="filters_trash"]').click();
    await page.waitForSelector('[data-automation-id="empty_trash"]');
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
    await page.locator('[data-automation-id="select_all"]').click();
    await page.waitForSelector('.mailpoet-listing-select-all');
    await page.locator('.mailpoet-listing-select-all > a').click();
    await page.waitForSelector('[data-automation-id="action-restore"]');
    await page.locator('[data-automation-id="action-restore"]').click();
    await page.waitForSelector('.notice-success');
    await waitForSelectorToBeVisible(page, '.colspanchange');
    await waitForSelectorToBeVisible(
      page,
      '[data-automation-id="filters_subscribed"]',
    );

    await page.screenshot({
      path: screenshotPath + 'Subscribers_Trashing_Restoring_03.png',
      fullPage: fullPageSet,
    });

    // Thinking time and closing
    await sleep(randomIntBetween(thinkTimeMin, thinkTimeMax));
  } finally {
    await page.close();
    await browser.context().close();
  }
}

export default async function subscribersTrashingRestoringTest() {
  await subscribersTrashingRestoring();
}
