<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\RatingScorerFieldMappingListBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for RatingScorerFieldMappingListBuilder.
 *
 * @group rating_scorer
 */
class RatingScorerListBuilderTest extends UnitTestCase {

  /**
   * Test that ListBuilder has render method override.
   */
  public function testListBuilderHasRenderMethod(): void {
    $reflection = new \ReflectionClass(RatingScorerFieldMappingListBuilder::class);
    $this->assertTrue($reflection->hasMethod('render'));

    $method = $reflection->getMethod('render');
    $this->assertTrue($method->isPublic());
  }

  /**
   * Test that ListBuilder has buildHeader method.
   */
  public function testListBuilderHasBuildHeaderMethod(): void {
    $reflection = new \ReflectionClass(RatingScorerFieldMappingListBuilder::class);
    $this->assertTrue($reflection->hasMethod('buildHeader'));
  }

  /**
   * Test that ListBuilder has buildRow method.
   */
  public function testListBuilderHasBuildRowMethod(): void {
    $reflection = new \ReflectionClass(RatingScorerFieldMappingListBuilder::class);
    $this->assertTrue($reflection->hasMethod('buildRow'));
  }

}
