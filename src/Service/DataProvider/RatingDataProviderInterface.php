<?php

namespace Drupal\rating_scorer\Service\DataProvider;

/**
 * Interface for rating data providers.
 */
interface RatingDataProviderInterface {

  /**
   * Get the average rating for an entity.
   *
   * @param object $entity
   *   The entity to get the rating for.
   * @param string $vote_type
   *   The vote type (e.g., 'rating', 'points').
   *
   * @return float|null
   *   The average rating, or NULL if not available.
   */
  public function getAverageRating($entity, $vote_type = 'rating');

  /**
   * Get the vote/rating count for an entity.
   *
   * @param object $entity
   *   The entity to get the count for.
   * @param string $vote_type
   *   The vote type (e.g., 'rating', 'points').
   *
   * @return int|null
   *   The vote count, or NULL if not available.
   */
  public function getVoteCount($entity, $vote_type = 'rating');

  /**
   * Check if this provider can handle the given entity.
   *
   * @param object $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if this provider can handle the entity.
   */
  public function applies($entity);

}
