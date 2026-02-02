<?php

namespace Drupal\Tests\rating_scorer\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for Rating Scorer kernel tests.
 *
 * Kernel tests provide a middle ground between unit tests (no Drupal bootstrap)
 * and functional tests (full browser). They're ideal for testing:
 * - Service integration
 * - Entity CRUD operations
 * - Configuration validation
 * - Database interactions
 *
 * @group rating_scorer
 */
abstract class RatingScorerKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'rating_scorer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install entity schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Install module configuration.
    $this->installConfig(['rating_scorer']);
  }

  /**
   * Gets the rating score calculator service.
   *
   * @return \Drupal\rating_scorer\Service\RatingScoreCalculator
   *   The rating score calculator service.
   */
  protected function getCalculator() {
    return $this->container->get('rating_scorer.calculator');
  }

  /**
   * Gets the field detection service.
   *
   * @return \Drupal\rating_scorer\Service\FieldDetectionService
   *   The field detection service.
   */
  protected function getFieldDetectionService() {
    return $this->container->get('rating_scorer.field_detection');
  }

  /**
   * Gets the rating module detection service.
   *
   * @return \Drupal\rating_scorer\Service\RatingModuleDetectionService
   *   The rating module detection service.
   */
  protected function getRatingModuleDetectionService() {
    return $this->container->get('rating_scorer.rating_module_detection');
  }

}
