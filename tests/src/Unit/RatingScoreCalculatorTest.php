<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rating_scorer\Service\RatingScoreCalculator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for the RatingScoreCalculator service.
 *
 * @group rating_scorer
 */
class RatingScoreCalculatorTest extends UnitTestCase {

  /**
   * The RatingScoreCalculator service.
   *
   * @var \Drupal\rating_scorer\Service\RatingScoreCalculator
   */
  protected RatingScoreCalculator $calculator;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->calculator = new RatingScoreCalculator($this->entityTypeManager);
  }

  /**
   * Test that the calculator service exists.
   */
  public function testCalculatorServiceExists(): void {
    $this->assertInstanceOf(RatingScoreCalculator::class, $this->calculator);
  }

  /**
   * Test calculateScoreForEntity returns NULL when entity has no field.
   */
  public function testCalculateScoreReturnsNullWhenFieldMissing(): void {
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('hasField')
      ->with('rating_score')
      ->willReturn(FALSE);

    $result = $this->calculator->calculateScoreForEntity($entity, 'rating_score');
    $this->assertNull($result);
  }

  /**
   * Test calculateScoreForEntity returns NULL when mapping not configured.
   */
  public function testCalculateScoreReturnsNullWhenMappingMissing(): void {
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('hasField')
      ->with('rating_score')
      ->willReturn(TRUE);
    $entity->expects($this->once())
      ->method('bundle')
      ->willReturn('article');
    $entity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn('node');

    // Mock storage to return no mapping.
    $storage = $this->createMock(\Drupal\Core\Entity\EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('node.article')
      ->willReturn(NULL);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('rating_scorer_field_mapping')
      ->willReturn($storage);

    $result = $this->calculator->calculateScoreForEntity($entity, 'rating_score');
    $this->assertNull($result);
  }

  /**
   * Test that the updateScoreFieldsOnEntity method exists and is callable.
   */
  public function testUpdateScoreFieldsOnEntityMethodExists(): void {
    // Verify the method exists on the service.
    $this->assertTrue(
      method_exists($this->calculator, 'updateScoreFieldsOnEntity'),
      'updateScoreFieldsOnEntity method should exist'
    );

    // The method should be callable.
    $reflectionMethod = new \ReflectionMethod(
      RatingScoreCalculator::class,
      'updateScoreFieldsOnEntity'
    );
    $this->assertTrue($reflectionMethod->isPublic());
  }

}
