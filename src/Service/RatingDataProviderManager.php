<?php

namespace Drupal\rating_scorer\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rating_scorer\Service\DataProvider\RatingDataProviderInterface;
use Drupal\rating_scorer\Service\DataProvider\VotingapiDataProvider;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Manager for rating data providers.
 */
class RatingDataProviderManager {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Array of data providers.
   *
   * @var \Drupal\rating_scorer\Service\DataProvider\RatingDataProviderInterface[]
   */
  protected $providers = [];

  /**
   * Constructs the manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->initializeProviders();
  }

  /**
   * Initialize all available providers.
   */
  protected function initializeProviders() {
    // Add Votingapi provider if module is installed
    if ($this->moduleHandler->moduleExists('votingapi')) {
      $this->providers['votingapi'] = new VotingapiDataProvider($this->moduleHandler);
    }

    // Additional providers can be added here
  }

  /**
   * Get a provider for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to find a provider for.
   *
   * @return \Drupal\rating_scorer\Service\DataProvider\RatingDataProviderInterface|null
   *   A suitable provider, or NULL if none found.
   */
  public function getProvider(EntityInterface $entity) {
    foreach ($this->providers as $provider) {
      if ($provider->applies($entity)) {
        return $provider;
      }
    }
    return NULL;
  }

  /**
   * Get average rating for an entity using an appropriate provider.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $vote_type
   *   The vote type.
   *
   * @return float|null
   *   The average rating or NULL.
   */
  public function getAverageRating(EntityInterface $entity, $vote_type = 'rating') {
    $provider = $this->getProvider($entity);
    if ($provider) {
      return $provider->getAverageRating($entity, $vote_type);
    }
    return NULL;
  }

  /**
   * Get vote count for an entity using an appropriate provider.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $vote_type
   *   The vote type.
   *
   * @return int|null
   *   The vote count or NULL.
   */
  public function getVoteCount(EntityInterface $entity, $vote_type = 'rating') {
    $provider = $this->getProvider($entity);
    if ($provider) {
      return $provider->getVoteCount($entity, $vote_type);
    }
    return NULL;
  }

  /**
   * Check if any data provider is available.
   *
   * @return bool
   *   TRUE if at least one provider is available.
   */
  public function hasProviders() {
    return !empty($this->providers);
  }

  /**
   * Get list of available providers.
   *
   * @return array
   *   Array of provider names.
   */
  public function getAvailableProviders() {
    return array_keys($this->providers);
  }

}
