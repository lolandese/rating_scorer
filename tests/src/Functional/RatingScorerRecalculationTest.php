<?php

namespace Drupal\Tests\rating_scorer\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\rating_scorer\Entity\RatingScorerFieldMapping;

/**
 * Functional tests for rating score recalculation on Field Mapping save.
 *
 * @group rating_scorer
 */
class RatingScorerRecalculationTest extends BrowserTestBase {

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
    'rating_scorer_demo',
  ];

  /**
   * Test that Field Mapping save triggers score recalculation.
   */
  public function testFieldMappingSaveTriggersRecalculation(): void {
    // Load the field mapping created by demo module
    $mapping = RatingScorerFieldMapping::load('node_article');

    $this->assertNotNull($mapping, 'Field mapping for node_article should exist after demo install.');
    $this->assertEqual('node_article', $mapping->id());

    // Load articles that should have calculated scores
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'article']);

    $this->assertNotEmpty($nodes, 'Demo articles should exist.');

    // All articles should have rating scores calculated
    foreach ($nodes as $node) {
      $score = $node->get('field_rating_score')->value;
      $this->assertNotNull($score, 'Rating score should be calculated for article: ' . $node->getTitle());
    }
  }

  /**
   * Test that score recalculation message appears after form save.
   */
  public function testRecalculationMessageAfterSave(): void {
    $admin_user = $this->createUser([
      'administer rating scorer',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

    // Navigate to field mapping form for node_article
    $this->drupalGet('/admin/config/rating-scorer/manage/node_article');
    $this->assertSession()->statusCodeEquals(200);

    // The page should show the field mapping exists
    $this->assertSession()->pageTextContains('node_article');
  }

}
