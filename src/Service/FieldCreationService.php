<?php

namespace Drupal\rating_scorer\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Service to automatically create rating score fields on content types.
 */
class FieldCreationService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FieldCreationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Create a rating_score field on a content type if it doesn't exist.
   *
   * @param string $bundle
   *   The bundle (content type) to add the field to.
   * @param string $entityType
   *   The entity type (default: 'node').
   *
   * @return bool
   *   TRUE if field was created or already exists, FALSE on error.
   */
  public function createRatingScoreFieldIfNeeded($bundle, $entityType = 'node') {
    try {
      // Check if field storage already exists
      $fieldStorage = $this->entityTypeManager
        ->getStorage('field_storage_config')
        ->load("$entityType.rating_score");

      if (!$fieldStorage) {
        // Create field storage
        $fieldStorage = FieldStorageConfig::create([
          'field_name' => 'rating_score',
          'entity_type' => $entityType,
          'type' => 'rating_score',
          'cardinality' => 1,
          'settings' => [],
        ]);
        $fieldStorage->save();
      }

      // Check if field instance exists on this bundle
      $field = $this->entityTypeManager
        ->getStorage('field_config')
        ->load("$entityType.$bundle.rating_score");

      if ($field) {
        // Field already exists on this bundle
        return TRUE;
      }

      // Create field instance
      $field = FieldConfig::create([
        'field_storage' => $fieldStorage,
        'bundle' => $bundle,
        'label' => 'Rating Score',
        'description' => 'Computed fair rating score based on average rating and number of reviews.',
        'required' => FALSE,
      ]);
      $field->save();

      // Get the entity type definition
      $entityTypeDefinition = $this->entityTypeManager
        ->getDefinition($entityType);

      // Add default formatter if entity has form/view display
      if ($entityTypeDefinition->get('field_ui_base_route')) {
        $this->createDefaultDisplay($entityType, $bundle);
      }

      return TRUE;
    }
    catch (\Exception $e) {
      // Log error if needed
      return FALSE;
    }
  }

  /**
   * Create default form and view display for rating_score field.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   */
  protected function createDefaultDisplay($entityType, $bundle) {
    try {
      // Add to default view display
      $viewDisplay = $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->load("$entityType.$bundle.default");

      if ($viewDisplay) {
        $viewDisplay->setComponent('rating_score', [
          'type' => 'rating_score_default',
          'settings' => [],
        ])->save();
      }

      // Add to default form display
      $formDisplay = $this->entityTypeManager
        ->getStorage('entity_form_display')
        ->load("$entityType.$bundle.default");

      if ($formDisplay) {
        $formDisplay->setComponent('rating_score', [
          'type' => 'rating_score_widget',
          'settings' => [],
        ])->save();
      }
    }
    catch (\Exception $e) {
      // Non-critical error, continue
    }
  }

}
