<?php

namespace Drupal\Tests\rating_scorer\Unit;

use Drupal\rating_scorer\Service\RatingDataProviderManager;
use Drupal\rating_scorer\Service\DataProvider\RatingDataProviderInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Tests for the RatingDataProviderManager service.
 *
 * @group rating_scorer
 */
class RatingDataProviderManagerTest extends UnitTestCase {

  /**
   * Mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
  }

  /**
   * Test manager class exists.
   */
  public function testManagerClassExists(): void {
    $this->assertTrue(class_exists(RatingDataProviderManager::class));
  }

  /**
   * Test manager is instantiable.
   */
  public function testManagerIsInstantiable(): void {
    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertInstanceOf(RatingDataProviderManager::class, $manager);
  }

  /**
   * Test manager has providers method.
   */
  public function testManagerHasProvidersMethod(): void {
    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertTrue(method_exists($manager, 'hasProviders'));
  }

  /**
   * Test manager has get provider method.
   */
  public function testManagerHasGetProviderMethod(): void {
    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertTrue(method_exists($manager, 'getProvider'));
  }

  /**
   * Test manager has get average rating method.
   */
  public function testManagerHasGetAverageRatingMethod(): void {
    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertTrue(method_exists($manager, 'getAverageRating'));
  }

  /**
   * Test manager has get vote count method.
   */
  public function testManagerHasGetVoteCountMethod(): void {
    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertTrue(method_exists($manager, 'getVoteCount'));
  }

  /**
   * Test manager detects Votingapi provider when module installed.
   */
  public function testManagerDetectsVotingapiProvider(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturnCallback(function ($module) {
        return $module === 'votingapi';
      });

    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertTrue($manager->hasProviders());
  }

  /**
   * Test manager returns no providers when none installed.
   */
  public function testManagerReturnsNoProvidersWhenNoneInstalled(): void {
    $this->moduleHandler
      ->method('moduleExists')
      ->willReturn(FALSE);

    $manager = new RatingDataProviderManager($this->moduleHandler);
    $this->assertFalse($manager->hasProviders());
  }

  /**
   * Test data provider interface exists.
   */
  public function testDataProviderInterfaceExists(): void {
    $this->assertTrue(interface_exists(RatingDataProviderInterface::class));
  }

  /**
   * Test provider interface has required methods.
   */
  public function testProviderInterfaceHasRequiredMethods(): void {
    $interface = new \ReflectionClass(RatingDataProviderInterface::class);
    $methods = $interface->getMethods();
    $methodNames = array_map(function ($m) { return $m->getName(); }, $methods);

    $this->assertContains('getAverageRating', $methodNames);
    $this->assertContains('getVoteCount', $methodNames);
    $this->assertContains('applies', $methodNames);
  }

}
