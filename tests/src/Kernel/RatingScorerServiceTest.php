<?php

namespace Drupal\Tests\rating_scorer\Kernel;

/**
 * Tests Rating Scorer service registration and basic functionality.
 *
 * @group rating_scorer
 */
class RatingScorerServiceTest extends RatingScorerKernelTestBase {

  /**
   * Tests that the calculator service is properly registered.
   */
  public function testCalculatorServiceRegistration(): void {
    $calculator = $this->getCalculator();
    $this->assertNotNull($calculator, 'Calculator service should be available.');
    $this->assertInstanceOf(
      'Drupal\rating_scorer\Service\RatingScoreCalculator',
      $calculator,
      'Calculator service should be an instance of RatingScoreCalculator.'
    );
  }

  /**
   * Tests that the field detection service is properly registered.
   */
  public function testFieldDetectionServiceRegistration(): void {
    $service = $this->getFieldDetectionService();
    $this->assertNotNull($service, 'Field detection service should be available.');
    $this->assertInstanceOf(
      'Drupal\rating_scorer\Service\FieldDetectionService',
      $service,
      'Field detection service should be an instance of FieldDetectionService.'
    );
  }

  /**
   * Tests that the rating module detection service is properly registered.
   */
  public function testRatingModuleDetectionServiceRegistration(): void {
    $service = $this->getRatingModuleDetectionService();
    $this->assertNotNull($service, 'Rating module detection service should be available.');
    $this->assertInstanceOf(
      'Drupal\rating_scorer\Service\RatingModuleDetectionService',
      $service,
      'Rating module detection service should be an instance of RatingModuleDetectionService.'
    );
  }

  /**
   * Tests Bayesian score calculation via the module helper function.
   */
  public function testBayesianCalculation(): void {
    // Test Bayesian average calculation using the module function.
    // With 10 ratings averaging 4.5 and threshold of 5, global average 3.0.
    $score = _rating_scorer_calculate_score(10, 4.5, 'bayesian', 5);

    // Score should be between global average (3.0) and item average (4.5).
    $this->assertGreaterThan(3.0, $score, 'Bayesian score should be above global average.');
    $this->assertLessThan(4.5, $score, 'Bayesian score should be below item average for moderate ratings.');
  }

  /**
   * Tests weighted score calculation via the module helper function.
   */
  public function testWeightedCalculation(): void {
    // Test weighted score calculation.
    $score = _rating_scorer_calculate_score(100, 4.0, 'weighted', 10);

    // Weighted score should be positive and reflect high rating with many votes.
    $this->assertGreaterThan(0, $score, 'Weighted score should be positive.');
  }

  /**
   * Tests Wilson score calculation via the module helper function.
   */
  public function testWilsonCalculation(): void {
    // Test Wilson score calculation.
    // 80% positive rate (4.0 out of 5.0).
    $score = _rating_scorer_calculate_score(100, 4.0, 'wilson', 10);

    // Wilson score should be between 0 and 1 (normalized).
    $this->assertGreaterThanOrEqual(0, $score, 'Wilson score should be non-negative.');
    $this->assertLessThanOrEqual(1, $score, 'Wilson score should not exceed 1.');
  }

}
