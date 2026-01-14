<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the Rating Scorer module.
 *
 * @group rating_scorer
 */
class RatingScorerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'rating_scorer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Test creating a user with administer site configuration permission.
   */
  public function testUserWithAdminPermission() {
    // Create a user with administer site configuration permission.
    $user = $this->createUser(['administer site configuration']);

    $this->assertNotEmpty($user->id());
    $this->assertTrue($user->hasPermission('administer site configuration'));
  }

}
