<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Service\RatingModuleDetectionService;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Tests for the RatingModuleDetectionService.
 *
 * @group rating_scorer
 */
class RatingModuleDetectionServiceTest extends UnitTestCase {

  /**
   * Mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Mock entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Mock field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->fieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->fieldTypeManager = $this->createMock(FieldTypePluginManagerInterface::class);
  }

  /**
   * Test detection of installed rating modules.
   */
  public function testDetectsInstalledRatingModules(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnCallback(function ($module) {
        return in_array($module, ['votingapi', 'fivestar']);
      });

    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $detected = $service->getDetectedRatingModules();

    $this->assertArrayHasKey('votingapi', $detected);
    $this->assertArrayHasKey('fivestar', $detected);
    $this->assertArrayNotHasKey('rate', $detected);
  }

  /**
   * Test detection returns empty array when no modules installed.
   */
  public function testDetectsNoModulesWhenNoneInstalled(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturn(FALSE);

    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $detected = $service->getDetectedRatingModules();

    $this->assertEmpty($detected);
  }

  /**
   * Test detection of all three rating modules.
   */
  public function testDetectsAllThreeRatingModules(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnCallback(function ($module) {
        return in_array($module, ['votingapi', 'fivestar', 'rate']);
      });

    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $detected = $service->getDetectedRatingModules();

    $this->assertCount(3, $detected);
    $this->assertArrayHasKey('votingapi', $detected);
    $this->assertArrayHasKey('fivestar', $detected);
    $this->assertArrayHasKey('rate', $detected);
  }

  /**
   * Test service class exists.
   */
  public function testServiceClassExists(): void {
    $this->assertTrue(class_exists(RatingModuleDetectionService::class));
  }

  /**
   * Test service is instantiable.
   */
  public function testServiceIsInstantiable(): void {
    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $this->assertInstanceOf(RatingModuleDetectionService::class, $service);
  }

  /**
   * Test Votingapi module detection specifically.
   */
  public function testDetectsVotingapiModule(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnCallback(function ($module) {
        return $module === 'votingapi';
      });

    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $detected = $service->getDetectedRatingModules();

    $this->assertCount(1, $detected);
    $this->assertArrayHasKey('votingapi', $detected);
  }

  /**
   * Test Fivestar module detection specifically.
   */
  public function testDetectsFivestarModule(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnCallback(function ($module) {
        return $module === 'fivestar';
      });

    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $detected = $service->getDetectedRatingModules();

    $this->assertCount(1, $detected);
    $this->assertArrayHasKey('fivestar', $detected);
  }

  /**
   * Test Rate module detection specifically.
   */
  public function testDetectsRateModule(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnCallback(function ($module) {
        return $module === 'rate';
      });

    $service = new RatingModuleDetectionService(
      $this->moduleHandler,
      $this->fieldManager,
      $this->fieldTypeManager
    );
    $detected = $service->getDetectedRatingModules();

    $this->assertCount(1, $detected);
    $this->assertArrayHasKey('rate', $detected);
  }

}
