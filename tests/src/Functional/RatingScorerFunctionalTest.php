<?php

namespace Drupal\Tests\rating_scorer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Rating Scorer module.
 *
 * @group rating_scorer
 */
class RatingScorerFunctionalTest extends BrowserTestBase {

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
  ];

  /**
   * Test that the home page loads with a 200 response.
   */
  public function testHomePageLoads() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
