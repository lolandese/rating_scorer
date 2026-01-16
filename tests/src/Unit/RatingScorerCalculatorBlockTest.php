<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Plugin\Block\RatingScorerCalculatorBlock;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for RatingScorerCalculatorBlock.
 *
 * @group rating_scorer
 */
class RatingScorerCalculatorBlockTest extends UnitTestCase {

  /**
   * Test that calculator block class exists.
   */
  public function testCalculatorBlockExists(): void {
    $this->assertTrue(class_exists(RatingScorerCalculatorBlock::class));
  }

  /**
   * Test that calculator block has build method.
   */
  public function testCalculatorBlockHasBuildMethod(): void {
    $reflection = new \ReflectionClass(RatingScorerCalculatorBlock::class);
    $this->assertTrue($reflection->hasMethod('build'));

    $method = $reflection->getMethod('build');
    $this->assertTrue($method->isPublic());
  }

  /**
   * Test that calculator block extends BlockBase.
   */
  public function testCalculatorBlockExtendsBlockBase(): void {
    $reflection = new \ReflectionClass(RatingScorerCalculatorBlock::class);

    // Check parent class
    $parent = $reflection->getParentClass();
    $this->assertNotNull($parent);
    $this->assertStringContainsString('BlockBase', $parent->getName());
  }

}
