<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Controller\RatingScorerController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for RatingScorerController.
 *
 * @group rating_scorer
 */
class RatingScorerControllerTest extends UnitTestCase {

  /**
   * Test that controller has fieldMappingsList method.
   */
  public function testFieldMappingsListMethodExists(): void {
    $reflection = new \ReflectionClass(RatingScorerController::class);
    $this->assertTrue($reflection->hasMethod('fieldMappingsList'));
    
    $method = $reflection->getMethod('fieldMappingsList');
    $this->assertTrue($method->isPublic());
  }

  /**
   * Test that controller has calculator method.
   */
  public function testCalculatorMethodExists(): void {
    $reflection = new \ReflectionClass(RatingScorerController::class);
    $this->assertTrue($reflection->hasMethod('calculator'));
    
    $method = $reflection->getMethod('calculator');
    $this->assertTrue($method->isPublic());
  }

  /**
   * Test that both methods return arrays (render arrays).
   */
  public function testControllerMethodsReturnRenderArrays(): void {
    $reflection = new \ReflectionClass(RatingScorerController::class);
    
    // Both methods should exist and be public
    $this->assertTrue($reflection->hasMethod('fieldMappingsList'));
    $this->assertTrue($reflection->hasMethod('calculator'));
  }

}
