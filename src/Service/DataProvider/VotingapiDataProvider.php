<?php

namespace Drupal\rating_scorer\Service\DataProvider;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Data provider for Votingapi votes.
 */
class VotingapiDataProvider implements RatingDataProviderInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The votingapi service.
   *
   * @var \Drupal\votingapi\VotesQueryFactory
   */
  protected $votingapiService;

  /**
   * Constructs the provider.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;

    // Try to get the votingapi service if module is installed
    if ($this->moduleHandler->moduleExists('votingapi')) {
      try {
        $this->votingapiService = \Drupal::service('votingapi.query');
      } catch (\Exception $e) {
        // Service not available
        $this->votingapiService = NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity) {
    return $this->moduleHandler->moduleExists('votingapi') &&
           $this->votingapiService !== NULL &&
           $entity instanceof EntityInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function getAverageRating($entity, $vote_type = 'rating') {
    if (!$this->applies($entity)) {
      return NULL;
    }

    try {
      $query = \Drupal::service('votingapi.query');
      if (!$query) {
        return NULL;
      }

      // Query votes for this entity
      $votes = $query
        ->condition('entity_id', $entity->id())
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('type', $vote_type)
        ->execute();

      if (empty($votes)) {
        return NULL;
      }

      // Calculate average from the retrieved votes
      $total = 0;
      $count = 0;
      foreach ($votes as $vote) {
        $total += $vote->getValue();
        $count++;
      }

      return $count > 0 ? $total / $count : NULL;
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVoteCount($entity, $vote_type = 'rating') {
    if (!$this->applies($entity)) {
      return NULL;
    }

    try {
      $query = \Drupal::service('votingapi.query');
      if (!$query) {
        return NULL;
      }

      // Query votes for this entity
      $votes = $query
        ->condition('entity_id', $entity->id())
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('type', $vote_type)
        ->execute();

      return count($votes);
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get aggregated voting data for an entity.
   *
   * This provides direct access to votingapi's aggregation features.
   *
   * @param object $entity
   *   The entity to get aggregates for.
   * @param string $vote_type
   *   The vote type.
   *
   * @return array
   *   Array with keys: average, count, total, percentage, approval.
   */
  public function getAggregates($entity, $vote_type = 'rating') {
    if (!$this->applies($entity)) {
      return [];
    }

    try {
      $query = \Drupal::service('votingapi.query');
      if (!$query) {
        return [];
      }

      $votes = $query
        ->condition('entity_id', $entity->id())
        ->condition('entity_type', $entity->getEntityTypeId())
        ->condition('type', $vote_type)
        ->execute();

      if (empty($votes)) {
        return [
          'average' => 0,
          'count' => 0,
          'total' => 0,
        ];
      }

      $total = 0;
      $count = 0;
      foreach ($votes as $vote) {
        $total += $vote->getValue();
        $count++;
      }

      $average = $count > 0 ? $total / $count : 0;

      return [
        'average' => $average,
        'count' => $count,
        'total' => $total,
        'percentage' => ($average / 5) * 100, // Assuming 5-star scale
      ];
    } catch (\Exception $e) {
      return [];
    }
  }

}
