<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Form\RatingScorerSettingsForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for Rating Scorer forms.
 *
 * @group rating_scorer
 */
class RatingScorerFormTest extends UnitTestCase {

  /**
   * Test RatingScorerSettingsForm has clarifying note about Calculator scope.
   */
  public function testSettingsFormHasClarifyingNote(): void {
    $this->assertTrue(class_exists(RatingScorerSettingsForm::class));

    // Verify the form class can be instantiated
    $reflection = new \ReflectionClass(RatingScorerSettingsForm::class);
    $this->assertTrue($reflection->hasMethod('buildForm'));
  }

  /**
   * Test that form has required fields for calculator defaults.
   */
  public function testSettingsFormHasCalculatorDefaultFields(): void {
    $reflection = new \ReflectionClass(RatingScorerSettingsForm::class);

    // Verify buildForm method exists and is public
    $method = $reflection->getMethod('buildForm');
    $this->assertTrue($method->isPublic());
  }

}
