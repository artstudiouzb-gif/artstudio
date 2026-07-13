const { test, expect } = require('@playwright/test');

test('public home renders without horizontal overflow', async ({ page }) => {
    const response = await page.goto('/');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page.locator('main#main-content')).toBeVisible();

    const overflow = await page.evaluate(() => ({
        viewport: document.documentElement.clientWidth,
        content: document.documentElement.scrollWidth
    }));
    expect(overflow.content).toBeLessThanOrEqual(overflow.viewport + 1);
});

test('Uzbek home uses localized title and secure language URL', async ({ page }) => {
    const response = await page.goto('/uz');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(/Bosh sahifa/);
    expect(page.url()).toMatch(/^http:\/\/127\.0\.0\.1:8080\/uz\/?$/);
});

test('mobile menu opens and closes accessibly', async ({ page, isMobile }) => {
    test.skip(!isMobile, 'Mobile-only interaction');
    await page.goto('/');
    const burger = page.locator('[data-mobile-menu-toggle]').first();
    await expect(burger).toBeVisible();
    await burger.click();
    await expect(page.locator('body')).toHaveClass(/mobile-menu-open/);
    await expect(burger).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press('Escape');
    await expect(page.locator('body')).not.toHaveClass(/mobile-menu-open/);
    await expect(burger).toHaveAttribute('aria-expanded', 'false');
});

test('health endpoint and admin login are reachable', async ({ page, request }) => {
    const health = await request.get('/health');
    expect(health.status()).toBe(200);
    const healthPayload = await health.json();
    expect(healthPayload.status).toMatch(/^(ok|degraded)$/);

    const response = await page.goto('/admin/login');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
});
