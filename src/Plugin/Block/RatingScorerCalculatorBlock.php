<?php

namespace Drupal\rating_scorer\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Rating Scorer Calculator block.
 *
 * @Block(
 *   id = "rating_scorer_calculator",
 *   admin_label = @Translation("Rating Scorer Calculator"),
 *   category = @Translation("Rating Scorer")
 * )
 */
class RatingScorerCalculatorBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a RatingScorerCalculatorBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get('rating_scorer.settings');

    $settings = [
      'defaultMinimumRatings' => $config->get('default_minimum_ratings'),
      'defaultRating' => $config->get('default_rating'),
      'defaultNumRatings' => $config->get('default_num_ratings'),
      'defaultMethod' => $config->get('default_method'),
      'bayesianAssumedAverage' => $config->get('bayesian_assumed_average'),
    ];

    return [
      '#theme' => 'rating_scorer',
      '#attached' => [
        'library' => [
          'rating_scorer/calculator',
        ],
        'drupalSettings' => [
          'ratingScorer' => $settings,
        ],
      ],
    ];
  }

}
