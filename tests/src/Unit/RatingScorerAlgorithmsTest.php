<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Rating Scorer scoring algorithms.
 *
 * @group rating_scorer
 */
class RatingScorerAlgorithmsTest extends BrowserTestBase {

  /**
   * The theme to use when running the test.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'rating_scorer',
  ];

  /**
   * Test weighted score calculation with basic input.
   */
  public function testWeightedScoreBasic() {
    // average_rating * log(number_of_ratings + 1)
    // 4.0 * log(101) ≈ 4.0 * 4.615 ≈ 18.46
    $score = _rating_scorer_calculate_score(100, 4.0, 'weighted', 5);
    $this->assertGreaterThan(18, $score);
    $this->assertLessThan(19, $score);
  }

  /**
   * Test weighted score with single rating.
   */
  public function testWeightedScoreSingleRating() {
    // 5.0 * log(2) ≈ 5.0 * 0.693 ≈ 3.46
    $score = _rating_scorer_calculate_score(1, 5.0, 'weighted', 5);
    $this->assertGreaterThan(3.4, $score);
    $this->assertLessThan(3.5, $score);
  }

  /**
   * Test weighted score with zero ratings.
   */
  public function testWeightedScoreZeroRatings() {
    // 4.0 * log(0 + 1) = 4.0 * 0 = 0
    $score = _rating_scorer_calculate_score(0, 4.0, 'weighted', 5);
    $this->assertEquals(0, $score);
  }

  /**
   * Test weighted score with high volume.
   */
  public function testWeightedScoreHighVolume() {
    // 3.5 * log(10001) ≈ 3.5 * 9.210 ≈ 32.24
    $score = _rating_scorer_calculate_score(10000, 3.5, 'weighted', 5);
    $this->assertGreaterThan(32, $score);
    $this->assertLessThan(33, $score);
  }

  /**
   * Test weighted score with low rating and high volume.
   */
  public function testWeightedScoreLowRatingHighVolume() {
    // 2.0 * log(1001) ≈ 2.0 * 6.908 ≈ 13.82
    $score = _rating_scorer_calculate_score(1000, 2.0, 'weighted', 5);
    $this->assertGreaterThan(13, $score);
    $this->assertLessThan(14, $score);
  }

  /**
   * Test Bayesian average basic calculation.
   */
  public function testBayesianAverageBasic() {
    // (100 * 4.0 + 5 * 2.5) / (100 + 5)
    // (400 + 12.5) / 105 = 412.5 / 105 ≈ 3.929
    $score = _rating_scorer_calculate_score(100, 4.0, 'bayesian', 5);
    $this->assertGreaterThan(3.92, $score);
    $this->assertLessThan(3.93, $score);
  }

  /**
   * Test Bayesian average with single rating below prior.
   */
  public function testBayesianAverageSingleRating() {
    // (1 * 5.0 + 5 * 2.5) / (1 + 5)
    // (5 + 12.5) / 6 = 17.5 / 6 ≈ 2.917
    // Single 5-star gets pulled down by prior
    $score = _rating_scorer_calculate_score(1, 5.0, 'bayesian', 5);
    $this->assertGreaterThan(2.9, $score);
    $this->assertLessThan(2.92, $score);
  }

  /**
   * Test Bayesian average with zero ratings.
   */
  public function testBayesianAverageZeroRatings() {
    // (0 * 4.0 + 5 * 2.5) / (0 + 5)
    // (0 + 12.5) / 5 = 2.5 (the prior)
    $score = _rating_scorer_calculate_score(0, 4.0, 'bayesian', 5);
    $this->assertEquals(2.5, $score);
  }

  /**
   * Test Bayesian average with very high threshold.
   */
  public function testBayesianAverageHighThreshold() {
    // (100 * 4.0 + 100 * 2.5) / (100 + 100)
    // (400 + 250) / 200 = 650 / 200 = 3.25
    $score = _rating_scorer_calculate_score(100, 4.0, 'bayesian', 100);
    $this->assertEquals(3.25, $score);
  }

  /**
   * Test Bayesian average with low threshold.
   */
  public function testBayesianAverageLowThreshold() {
    // (100 * 4.0 + 1 * 2.5) / (100 + 1)
    // (400 + 2.5) / 101 = 402.5 / 101 ≈ 3.985
    $score = _rating_scorer_calculate_score(100, 4.0, 'bayesian', 1);
    $this->assertGreaterThan(3.98, $score);
    $this->assertLessThan(3.99, $score);
  }

  /**
   * Test Bayesian penalizes low-review items relative to high-review items.
   */
  public function testBayesianPenalizesLowReviewItems() {
    // 1 five-star review with threshold 10
    $score_one = _rating_scorer_calculate_score(1, 5.0, 'bayesian', 10);
    // 100 four-star reviews with threshold 10
    $score_many = _rating_scorer_calculate_score(100, 4.0, 'bayesian', 10);

    // Single review should score lower than established product
    $this->assertLessThan($score_many, $score_one);
  }

  /**
   * Test Wilson score with zero ratings.
   */
  public function testWilsonScoreZeroRatings() {
    // With 0 ratings, should return 0
    $score = _rating_scorer_calculate_score(0, 4.0, 'wilson', 5);
    $this->assertEquals(0, $score);
  }

  /**
   * Test Wilson score basic calculation.
   */
  public function testWilsonScoreBasic() {
    // Wilson score with 100 ratings at 4.0/5.0 average
    $score = _rating_scorer_calculate_score(100, 4.0, 'wilson', 5);
    // Normalized rating: 4.0 / 5 = 0.8
    // Should be a positive value less than the normalized rating
    $this->assertGreaterThan(0, $score);
    $this->assertLessThan(0.8, $score);
  }

  /**
   * Test Wilson score with single rating.
   */
  public function testWilsonScoreSingleRating() {
    // With very few ratings, Wilson score is conservative
    $score = _rating_scorer_calculate_score(1, 5.0, 'wilson', 5);
    $this->assertGreaterThanOrEqual(0, $score);
    // Single 5-star should be significantly pulled down
    $this->assertLessThan(0.5, $score);
  }

  /**
   * Test Wilson score is conservative with low ratings.
   */
  public function testWilsonScoreIsConservative() {
    // High rating with few reviews
    $score_low_volume = _rating_scorer_calculate_score(5, 5.0, 'wilson', 5);
    // High rating with many reviews
    $score_high_volume = _rating_scorer_calculate_score(1000, 5.0, 'wilson', 5);

    // High-volume should score higher (more confident)
    $this->assertGreaterThan($score_low_volume, $score_high_volume);
  }

  /**
   * Test Wilson score never goes negative.
   */
  public function testWilsonScoreNeverNegative() {
    // Very low rating with few reviews should not go negative
    $score = _rating_scorer_calculate_score(1, 1.0, 'wilson', 5);
    $this->assertGreaterThanOrEqual(0, $score);
  }

  /**
   * Test Wilson score with perfect 5.0 rating.
   */
  public function testWilsonScorePerfectRating() {
    // Perfect 5.0 rating normalized to 1.0
    $score = _rating_scorer_calculate_score(100, 5.0, 'wilson', 5);
    // Should be close to 1.0 but slightly lower due to confidence margin
    $this->assertGreaterThan(0.9, $score);
    $this->assertLessThanOrEqual(1.0, $score);
  }

  /**
   * Test Wilson score with average 2.5 rating (neutral).
   */
  public function testWilsonScoreNeutralRating() {
    // Neutral 2.5/5.0 rating
    $score = _rating_scorer_calculate_score(100, 2.5, 'wilson', 5);
    // Normalized to 0.5, should be close to 0.5 with confidence margin
    $this->assertGreaterThan(0.4, $score);
    $this->assertLessThan(0.6, $score);
  }

  /**
   * Test default method is weighted when invalid method provided.
   */
  public function testInvalidMethodDefaultsToWeighted() {
    $score_invalid = _rating_scorer_calculate_score(100, 4.0, 'invalid_method', 5);
    $score_weighted = _rating_scorer_calculate_score(100, 4.0, 'weighted', 5);

    // Should fall back to weighted and produce same result
    $this->assertEquals($score_weighted, $score_invalid);
  }

  /**
   * Test with decimal ratings.
   */
  public function testDecimalRatings() {
    $score = _rating_scorer_calculate_score(50, 3.7, 'weighted', 5);
    // 3.7 * log(51) ≈ 3.7 * 3.932 ≈ 14.55
    $this->assertGreaterThan(14.5, $score);
    $this->assertLessThan(14.6, $score);
  }

  /**
   * Test Bayesian with large numbers.
   */
  public function testBayesianLargeNumbers() {
    // Should handle millions of ratings gracefully
    $score = _rating_scorer_calculate_score(1000000, 3.8, 'bayesian', 1000);
    // With massive rating count, score should approach the average rating
    $this->assertGreaterThan(3.79, $score);
    $this->assertLessThan(3.81, $score);
  }

  /**
   * Test that Bayesian approaches average as ratings increase.
   */
  public function testBayesianConvergence() {
    // With low ratings
    $score_low = _rating_scorer_calculate_score(10, 4.0, 'bayesian', 10);
    // With medium ratings
    $score_medium = _rating_scorer_calculate_score(100, 4.0, 'bayesian', 10);
    // With high ratings
    $score_high = _rating_scorer_calculate_score(1000, 4.0, 'bayesian', 10);

    // All should be close to 4.0, with lower counts pulling toward prior
    $this->assertLessThan(4.0, $score_low);
    $this->assertLessThan(4.0, $score_medium);
    $this->assertLessThan(4.0, $score_high);

    // Higher volume should be closer to actual average
    $this->assertGreaterThan($score_low, $score_medium);
    $this->assertGreaterThan($score_medium, $score_high);
  }

}
