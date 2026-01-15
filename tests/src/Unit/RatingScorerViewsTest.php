<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Rating Scorer Views integration.
 *
 * @group rating_scorer
 */
class RatingScorerViewsTest extends BrowserTestBase {

  /**
   * The theme to use when running the test.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

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
  }

  /**
   * Test rating_scorer module is installed.
   */
  public function testRatingScorerModuleExists() {
    $module_handler = \Drupal::moduleHandler();
    $this->assertTrue($module_handler->moduleExists('rating_scorer'));
  }

  /**
   * Test the module file exists.
   */
  public function testModuleFileExists() {
    $module_path = \Drupal::moduleHandler()->getModule('rating_scorer')->getPath();
    $this->assertFileExists($module_path . '/rating_scorer.module');
  }

  /**
   * Test helper function _rating_scorer_calculate_score exists.
   */
  public function testHelperFunctionExists() {
    $this->assertTrue(function_exists('_rating_scorer_calculate_score'));
  }

  /**
   * Test Views field handler class exists.
   */
  public function testFieldHandlerClassExists() {
    $class = 'Drupal\\rating_scorer\\Plugin\\views\\field\\RatingScore';
    $this->assertTrue(class_exists($class));
  }

  /**
   * Test Views sort handler class exists.
   */
  public function testSortHandlerClassExists() {
    $class = 'Drupal\\rating_scorer\\Plugin\\views\\sort\\RatingScore';
    $this->assertTrue(class_exists($class));
  }

  /**
   * Test the Views field handler extends FieldPluginBase.
   */
  public function testFieldHandlerExtendsFieldPluginBase() {
    $class = 'Drupal\\rating_scorer\\Plugin\\views\\field\\RatingScore';
    $reflection = new \ReflectionClass($class);
    $this->assertTrue($reflection->isSubclassOf('Drupal\\views\\Plugin\\views\\field\\FieldPluginBase'));
  }

  /**
   * Test the Views sort handler extends SortPluginBase.
   */
  public function testSortHandlerExtendsSortPluginBase() {
    $class = 'Drupal\\rating_scorer\\Plugin\\views\\sort\\RatingScore';
    $reflection = new \ReflectionClass($class);
    $this->assertTrue($reflection->isSubclassOf('Drupal\\views\\Plugin\\views\\sort\\SortPluginBase'));
  }

  /**
   * Test that hook_views_data is implemented in the module.
   */
  public function testHookViewsDataImplemented() {
    // Check if the function is defined in the module.
    $functions = get_defined_functions()['user'];
    $this->assertContains('rating_scorer_views_data', $functions);
  }

  /**
   * Test that hook_views_pre_render is implemented in the module.
   */
  public function testHookViewsPreRenderImplemented() {
    $functions = get_defined_functions()['user'];
    $this->assertContains('rating_scorer_views_pre_render', $functions);
  }

  /**
   * Test that the module provides core hooks.
   */
  public function testCoreHooksImplemented() {
    $functions = get_defined_functions()['user'];
    
    // Should have help hook.
    $this->assertContains('rating_scorer_help', $functions);
    
    // Should have theme hook.
    $this->assertContains('rating_scorer_theme', $functions);
  }

}
