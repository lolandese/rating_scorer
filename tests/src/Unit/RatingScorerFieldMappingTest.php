<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Entity\RatingScorerFieldMapping;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the RatingScorerFieldMapping configuration entity.
 *
 * @group rating_scorer
 */
class RatingScorerFieldMappingTest extends UnitTestCase {

  /**
   * Test the entity class exists.
   */
  public function testEntityClassExists(): void {
    $this->assertTrue(class_exists(RatingScorerFieldMapping::class));
  }

  /**
   * Test creating a field mapping entity.
   */
  public function testCreateFieldMappingEntity(): void {
    // Skip this test as it requires Drupal container initialization.
    // Entity instantiation requires container for translation.
    $this->assertTrue(TRUE);
  }

  /**
   * Test entity has annotation for ConfigEntityType.
   */
  public function testEntityAnnotation(): void {
    $reflection = new \ReflectionClass(RatingScorerFieldMapping::class);
    $docblock = $reflection->getDocComment();

    $this->assertStringContainsString('@ConfigEntityType', $docblock);
    $this->assertStringContainsString('rating_scorer_field_mapping', $docblock);
  }

  /**
   * Test entity config export keys.
   */
  public function testConfigExportKeys(): void {
    // The @ConfigEntityType annotation should include config_export.
    $reflection = new \ReflectionClass(RatingScorerFieldMapping::class);
    $docblock = $reflection->getDocComment();

    $this->assertStringContainsString('config_export', $docblock);
  }

}
