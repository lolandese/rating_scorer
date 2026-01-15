<?php

namespace Drupal\rating_scorer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RatingScorerController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays the field mappings list.
   *
   * @return array
   *   A render array.
   */
  public function fieldMappingsList() {
    $list_builder = $this->entityTypeManager
      ->getListBuilder('rating_scorer_field_mapping');
    
    return $list_builder->render();
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

