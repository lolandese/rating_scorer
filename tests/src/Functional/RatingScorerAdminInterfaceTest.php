<?php

namespace Drupal\Tests\rating_scorer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Rating Scorer admin interface and routing.
 *
 * @group rating_scorer
 */
class RatingScorerAdminInterfaceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'rating_scorer',
    'field',
    'node',
    'views',
    'views_ui',
  ];

  /**
   * Test that Field Mappings tab is accessible at parent route.
   */
  public function testFieldMappingsTabAtParentRoute(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // Parent route should be Field Mappings
    $this->drupalGet('/admin/config/rating-scorer');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Field Mappings');
  }

  /**
   * Test that Calculator tab is accessible.
   */
  public function testCalculatorTabAccessible(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/rating-scorer/calculator');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Calculator');
  }

  /**
   * Test that Defaults tab is accessible.
   */
  public function testDefaultsTabAccessible(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/rating-scorer/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Defaults');
  }

  /**
   * Test all three tabs are visible on Field Mappings page.
   */
  public function testAllTabsVisibleOnFieldMappingsPage(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/rating-scorer');
    $this->assertSession()->statusCodeEquals(200);

    // All three tabs should be visible
    $this->assertSession()->linkExists('Field Mappings');
    $this->assertSession()->linkExists('Calculator');
    $this->assertSession()->linkExists('Defaults');
  }

  /**
   * Test add field mapping link is visible on Field Mappings page.
   */
  public function testAddFieldMappingLinkVisible(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/rating-scorer');
    $this->assertSession()->statusCodeEquals(200);

    // The "+ Add a field mapping" link should be present
    $this->assertSession()->pageTextContains('+ Add a field mapping');
  }

  /**
   * Test that Settings form displays clarifying note.
   */
  public function testSettingsFormHasNote(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/rating-scorer/settings');
    $this->assertSession()->statusCodeEquals(200);

    // The clarifying note should be visible
    $this->assertSession()->pageTextContains('default settings apply only to the Calculator widget');
  }

  /**
   * Test that Calculator page displays purpose message.
   */
  public function testCalculatorPageHasPurposeMessage(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('/admin/config/rating-scorer/calculator');
    $this->assertSession()->statusCodeEquals(200);

    // The purpose message should be visible
    $this->assertSession()->pageTextContains('understand how different scoring methods combine ratings');
  }

}
