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
   * Constructs a RatingScoreCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
    $config_id = "{$entity_type}.{$bundle}";

    try {
      $mapping = $this->entityTypeManager
        ->getStorage('rating_scorer_field_mapping')
        ->load($config_id);
    } catch (\Exception $e) {
      return NULL;
    }

    if (!$mapping) {
      return NULL;
    }

    // Get source field values.
    $num_ratings_field = $mapping->get('number_of_ratings_field');
    $avg_rating_field = $mapping->get('average_rating_field');

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

    $scoring_method = $mapping->get('scoring_method');
    $bayesian_threshold = $mapping->get('bayesian_threshold') ?? 10;

    // Call the main module helper function to calculate score.
    return _rating_scorer_calculate_score(
      $number_of_ratings,
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
