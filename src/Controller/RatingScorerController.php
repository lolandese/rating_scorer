<?php

namespace Drupal\rating_scorer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Rating Scorer routes.
 */
class RatingScorerController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a RatingScorerController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Displays the rating scorer calculator.
   *
   * @return array
   *   A render array.
   */
  public function calculator() {
    $config = $this->configFactory->get('rating_scorer.settings');

    $settings = [
      'defaultMinimumRatings' => $config->get('default_minimum_ratings'),
      'defaultRating' => $config->get('default_rating'),
      'defaultNumRatings' => $config->get('default_num_ratings'),
      'defaultMethod' => $config->get('default_method'),
    ];

    return [
      'info' => [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--info"><strong>' . $this->t('Block Available') . ':</strong> ' . $this->t('This calculator is also available as a block. You can place it on any page by visiting the <a href="@block-layout">block layout page</a>.', ['@block-layout' => '/admin/structure/block']) . '</div>',
        '#weight' => -100,
      ],
      'calculator' => [
        '#theme' => 'rating_scorer',
        '#attached' => [
          'library' => [
            'rating_scorer/calculator',
          ],
          'drupalSettings' => [
            'ratingScorer' => $settings,
          ],
        ],
      ],
    ];
  }

}
