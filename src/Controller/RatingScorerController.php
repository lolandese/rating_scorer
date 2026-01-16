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
   * The dashboard service.
   *
   * @var \Drupal\rating_scorer\Service\RatingScorerDashboardService
   */
  protected $dashboardService;

  /**
   * Constructs a RatingScorerController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rating_scorer\Service\RatingScorerDashboardService $dashboard_service
   *   The dashboard service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, \Drupal\rating_scorer\Service\RatingScorerDashboardService $dashboard_service) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->dashboardService = $dashboard_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('rating_scorer.dashboard')
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

    // Return the list builder render array directly - it handles all rendering
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
      'bayesianAssumedAverage' => $config->get('bayesian_assumed_average'),
      'defaultRating' => $config->get('default_rating'),
      'defaultNumRatings' => $config->get('default_num_ratings'),
      'defaultMethod' => $config->get('default_method'),
    ];

    return [
      'info' => [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--info"><strong>' . $this->t('Purpose') . ':</strong> ' . $this->t('Use this calculator to understand how different scoring methods combine ratings and review counts. This helps you verify your chosen calculation method and adjust settings before applying them to your content.') . '</div><div class="messages messages--info"><strong>' . $this->t('Block Available') . ':</strong> ' . $this->t('This calculator is also available as a block. You can place it on any page by visiting the <a href="@block-layout">block layout page</a>.', ['@block-layout' => '/admin/structure/block']) . '</div>',
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

  /**
   * Displays the rating scorer dashboard.
   *
   * @return array
   *   A render array.
   */
  public function dashboard() {
    $stats = $this->dashboardService->getDashboardStatistics();
    $mappings = $this->dashboardService->getFieldMappingsWithStatus();

    // Render operations for each mapping
    foreach ($mappings as &$mapping) {
      $mapping['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => \Drupal\Core\Url::fromRoute('rating_scorer.field_mapping_edit', ['rating_scorer_field_mapping' => $mapping['id']]),
          ],
          'recalculate' => [
            'title' => $this->t('Recalculate'),
            'url' => \Drupal\Core\Url::fromRoute('rating_scorer.recalculate_scores', ['bundle' => $mapping['content_type']]),
          ],
        ],
      ];
    }

    return [
      '#theme' => 'rating_scorer_dashboard',
      '#statistics' => $stats,
      '#field_mappings' => $mappings,
      '#attached' => [
        'library' => [
          'rating_scorer/dashboard',
          'core/drupal.dropbutton',
        ],
      ],
    ];
  }

  /**
   * Recalculates all rating scores for a content type.
   *
   * @param string $bundle
   *   The content type bundle.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to dashboard.
   */
  public function recalculateScores($bundle) {
    try {
      $calculator = \Drupal::service('rating_scorer.calculator');
      $storage = $this->entityTypeManager->getStorage('node');

      // Load all nodes of this type
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $bundle);
      $entity_ids = $query->execute();

      if (empty($entity_ids)) {
        \Drupal::messenger()->addStatus($this->t('No entities found for content type %type.', ['%type' => $bundle]));
        return $this->redirect('rating_scorer.dashboard');
      }

      $nodes = $storage->loadMultiple($entity_ids);
      $count = 0;

      foreach ($nodes as $node) {
        try {
          $calculator->updateScoreFieldsOnEntity($node);
          $node->save();
          $count++;
        }
        catch (\Exception $e) {
          \Drupal::logger('rating_scorer')->error(
            'Error recalculating scores for node @nid: @error',
            ['@nid' => $node->id(), '@error' => $e->getMessage()]
          );
        }
      }

      \Drupal::messenger()->addStatus($this->t('Recalculated rating scores for @count entities of type %type.', [
        '@count' => $count,
        '%type' => $bundle,
      ]));

      \Drupal::logger('rating_scorer')->info(
        'Bulk recalculated rating scores for @count entities of type @bundle',
        ['@count' => $count, '@bundle' => $bundle]
      );
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Error during recalculation: @error', ['@error' => $e->getMessage()]));
      \Drupal::logger('rating_scorer')->error('Bulk recalculation failed: @error', ['@error' => $e->getMessage()]);
    }

    return $this->redirect('rating_scorer.dashboard');
  }

}
