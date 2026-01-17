<?php

namespace Drupal\rating_scorer\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Service for calculating and updating rating scores on entities.
 */
class RatingScoreCalculator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Fivestar vote result manager (optional).
   *
   * @var \Drupal\fivestar\VoteResultManager|null
   */
  protected $voteResultManager;

  /**
   * Constructs a RatingScoreCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\fivestar\VoteResultManager|null $vote_result_manager
   *   The Fivestar vote result manager (optional, for VotingAPI integration).
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $vote_result_manager = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->voteResultManager = $vote_result_manager;
  }

  /**
   * Calculate and update rating score for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to calculate score for.
   * @param string $field_name
   *   The rating_score field name.
   *
   * @return float|null
   *   The calculated score, or NULL if configuration is missing.
   */
  public function calculateScoreForEntity(ContentEntityInterface $entity, string $field_name): ?float {
    if (!$entity->hasField($field_name)) {
      return NULL;
    }

    // Get the field mapping configuration for this entity type.
    $bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $config_id = "{$entity_type}_{$bundle}";

    $config = \Drupal::config('rating_scorer.settings');
    $field_mappings = $config->get('field_mappings') ?? [];

    if (empty($field_mappings[$config_id])) {
      return NULL;
    }

    $mapping_data = $field_mappings[$config_id];
    if (is_string($mapping_data)) {
      $mapping_data = json_decode($mapping_data, TRUE);
    }

    if (!$mapping_data) {
      return NULL;
    }

    // Determine data source type.
    $source_type = $mapping_data['source_type'] ?? 'FIELD';

    if ($source_type === 'VOTINGAPI') {
      return $this->calculateScoreFromVotingAPI($entity, $mapping_data);
    }

    // FIELD source type (default/legacy behavior).
    return $this->calculateScoreFromFields($entity, $mapping_data);
  }

  /**
   * Calculate score from field-based rating data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to calculate score for.
   * @param array $mapping_data
   *   The field mapping configuration data.
   *
   * @return float|null
   *   The calculated score, or NULL if data is invalid.
   */
  private function calculateScoreFromFields(ContentEntityInterface $entity, array $mapping_data): ?float {
    // Get source field values.
    $num_ratings_field = $mapping_data['number_of_ratings_field'] ?? NULL;
    $avg_rating_field = $mapping_data['average_rating_field'] ?? NULL;

    if (!$num_ratings_field || !$avg_rating_field) {
      return NULL;
    }

    if (!$entity->hasField($num_ratings_field) || !$entity->hasField($avg_rating_field)) {
      return NULL;
    }

    $number_of_ratings = (float) $entity->get($num_ratings_field)->value;
    $average_rating = (float) $entity->get($avg_rating_field)->value;

    if ($number_of_ratings < 0 || $average_rating < 0) {
      return NULL;
    }

    $scoring_method = $mapping_data['scoring_method'] ?? NULL;
    $bayesian_threshold = $mapping_data['bayesian_threshold'] ?? 10;

    // Call the main module helper function to calculate score.
    return _rating_scorer_calculate_score(
      $number_of_ratings,
      $average_rating,
      $scoring_method,
      $bayesian_threshold
    );
  }

  /**
   * Calculate score from VotingAPI/Fivestar vote data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to calculate score for.
   * @param array $mapping_data
   *   The field mapping configuration data.
   *
   * @return float|null
   *   The calculated score, or NULL if votes are unavailable.
   */
  private function calculateScoreFromVotingAPI(ContentEntityInterface $entity, array $mapping_data): ?float {
    if (!$this->voteResultManager) {
      return NULL;
    }

    try {
      $vote_results = $this->voteResultManager->getResults($entity);
    } catch (\Exception $e) {
      return NULL;
    }

    // VotingAPI stores results under 'vote' key with vote_count and vote_sum.
    if (empty($vote_results['vote'])) {
      return NULL;
    }

    $vote_count = (int) ($vote_results['vote']['vote_count'] ?? 0);
    $vote_sum = (float) ($vote_results['vote']['vote_sum'] ?? 0);

    if ($vote_count <= 0) {
      return NULL;
    }

    // Normalize vote values to 0-5 scale (VotingAPI uses percent: 0-100).
    $average_rating = ($vote_sum / $vote_count) / 20;  // 100/5 = 20

    $scoring_method = $mapping_data['scoring_method'] ?? NULL;
    $bayesian_threshold = $mapping_data['bayesian_threshold'] ?? 10;

    // Call the main module helper function to calculate score.
    return _rating_scorer_calculate_score(
      $vote_count,
      $average_rating,
      $scoring_method,
      $bayesian_threshold
    );
  }

  /**
   * Update all rating score fields on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to update.
   */
  public function updateScoreFieldsOnEntity(ContentEntityInterface $entity): void {
    // Find all rating_score fields on this entity.
    foreach ($entity->getFieldDefinitions() as $field_definition) {
      if ($field_definition->getType() === 'rating_score') {
        $field_name = $field_definition->getName();
        $score = $this->calculateScoreForEntity($entity, $field_name);

        if ($score !== NULL) {
          $entity->set($field_name, $score);
        }
      }
    }
  }

}
