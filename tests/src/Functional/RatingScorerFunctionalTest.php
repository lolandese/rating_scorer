<?php

namespace Drupal\Tests\rating_scorer\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;

/**
 * Functional tests for the Rating Scorer module with demo data.
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
    'rating_scorer_demo',
  ];

  /**
   * Test that the home page loads with a 200 response.
   */
  public function testHomePageLoads(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test that demo articles are created with correct data.
   */
  public function testDemoArticlesCreated(): void {
    // Verify that demo articles exist
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'article']);

    $this->assertNotEmpty($nodes, 'Demo articles should be created.');
    $this->assertCount(5, $nodes, 'Exactly 5 demo articles should be created.');
  }

  /**
   * Test that articles have correct rating scores calculated.
   */
  public function testArticleRatingScoresCalculated(): void {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'article']);

    foreach ($nodes as $node) {
      $this->assertTrue($node->hasField('field_rating_score'), 'Node should have rating_score field.');
      $this->assertTrue($node->hasField('field_rating'), 'Node should have rating field.');
      $this->assertTrue($node->hasField('field_vote_count'), 'Node should have vote_count field.');

      // Score should be calculated (not empty)
      $score = $node->get('field_rating_score')->value;
      $this->assertNotEmpty($score, 'Rating score should be calculated for: ' . $node->getTitle());
    }
  }

  /**
   * Test that articles-by-rating view renders correctly.
   */
  public function testArticlesByRatingViewRendering(): void {
    $this->drupalGet('/articles-by-rating');
    $this->assertSession()->statusCodeEquals(200);

    // View should contain the demo article titles
    $this->assertSession()->pageTextContains('Lorem Ipsum Dolor Sit Amet');
    $this->assertSession()->pageTextContains('Voluptate Velit Esse Cillum Dolore');
    $this->assertSession()->pageTextContains('Fugiat Nulla Pariatur Excepteur Sint');
  }

  /**
   * Test that articles are ordered by rating score in the view.
   */
  public function testArticlesOrderedByRatingScore(): void {
    $this->drupalGet('/articles-by-rating');
    $this->assertSession()->statusCodeEquals(200);

    // Extract the page content to check article order
    $page = $this->getSession()->getPage();
    $content = $page->getContent();

    // The view should display articles ordered by rating score (DESC)
    // Article with 200 votes (4.12 score) should appear before article with 5 votes (3.27 score)
    $pos_voluptate = strpos($content, 'Voluptate Velit Esse Cillum Dolore');
    $pos_lorem = strpos($content, 'Lorem Ipsum Dolor Sit Amet');

    $this->assertLessThan($pos_lorem, $pos_voluptate, 'Higher score article should appear first in view.');
  }

  /**
   * Test that rating scores are displayed in the view.
   */
  public function testRatingScoresDisplayedInView(): void {
    $this->drupalGet('/articles-by-rating');
    $this->assertSession()->statusCodeEquals(200);

    // The view should display calculated rating scores
    $this->assertSession()->pageTextContains('4.12');
    $this->assertSession()->pageTextContains('4.08');
    $this->assertSession()->pageTextContains('3.97');
  }

  /**
   * Test that field mapping is created for demo.
   */
  public function testFieldMappingCreatedForDemo(): void {
    $mapping = \Drupal::entityTypeManager()
      ->getStorage('rating_scorer_field_mapping')
      ->load('node_article');

    $this->assertNotNull($mapping, 'Field mapping for node_article should exist.');
    $this->assertEqual('node_article', $mapping->id());
  }

}
