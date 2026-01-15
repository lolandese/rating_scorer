<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Plugin\Field\FieldType\RatingScoreFieldType;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the RatingScoreFieldType plugin.
 *
 * @group rating_scorer
 */
class RatingScoreFieldTypeTest extends UnitTestCase {

  /**
   * Test field type exists and has correct ID.
   */
  public function testFieldTypeExists(): void {
    $this->assertTrue(class_exists(RatingScoreFieldType::class));
  }

  /**
   * Test field type has correct plugin annotation.
   */
  public function testFieldTypePluginAnnotation(): void {
    // Use reflection to get the class docblock.
    $reflection = new \ReflectionClass(RatingScoreFieldType::class);
    $docblock = $reflection->getDocComment();

    $this->assertStringContainsString('@FieldType', $docblock);
    $this->assertStringContainsString('rating_score', $docblock);
    $this->assertStringContainsString('Rating Score', $docblock);
  }

  /**
   * Test field type default storage settings.
   */
  public function testDefaultStorageSettings(): void {
    $settings = RatingScoreFieldType::defaultStorageSettings();

    $this->assertArrayHasKey('precision', $settings);
    $this->assertArrayHasKey('scale', $settings);
    $this->assertEquals(10, $settings['precision']);
    $this->assertEquals(2, $settings['scale']);
  }

  /**
   * Test field type default field settings.
   */
  public function testDefaultFieldSettings(): void {
    $settings = RatingScoreFieldType::defaultFieldSettings();

    $this->assertArrayHasKey('number_of_ratings_field', $settings);
    $this->assertArrayHasKey('average_rating_field', $settings);
    $this->assertArrayHasKey('scoring_method', $settings);
    $this->assertArrayHasKey('bayesian_threshold', $settings);

    $this->assertEquals('bayesian', $settings['scoring_method']);
    $this->assertEquals(10, $settings['bayesian_threshold']);
  }

  /**
   * Test field type schema definition.
   */
  public function testFieldTypeSchema(): void {
    $field_definition = $this->createMock(
      \Drupal\Core\Field\FieldStorageDefinitionInterface::class
    );

    $schema = RatingScoreFieldType::schema($field_definition);

    $this->assertArrayHasKey('columns', $schema);
    $this->assertArrayHasKey('value', $schema['columns']);
    $this->assertEquals('numeric', $schema['columns']['value']['type']);
    $this->assertEquals(10, $schema['columns']['value']['precision']);
    $this->assertEquals(2, $schema['columns']['value']['scale']);
  }

  /**
   * Test field type property definitions.
   */
  public function testFieldTypePropertyDefinitions(): void {
    $field_definition = $this->createMock(
      \Drupal\Core\Field\FieldStorageDefinitionInterface::class
    );

    $properties = RatingScoreFieldType::propertyDefinitions($field_definition);

    $this->assertArrayHasKey('value', $properties);
    $this->assertNotNull($properties['value']);
  }

}
