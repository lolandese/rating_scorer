<?php

namespace Drupal\rating_scorer\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Service to detect available rating and review count fields on content types.
 */
class FieldDetectionService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a FieldDetectionService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypeManager
   *   The field type plugin manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FieldTypePluginManagerInterface $fieldTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldTypeManager = $fieldTypeManager;
  }

  /**
   * Detect numeric fields on a content type that could be used for ratings.
   *
   * @param string $bundle
   *   The bundle (content type) to scan.
   * @param string $entityType
   *   The entity type (default: 'node').
   *
   * @return array
   *   Array of field names and labels that are numeric.
   *   Format: ['field_name' => 'Field Label']
   */
  public function detectNumericFields($bundle, $entityType = 'node') {
    $numericFields = [];

    try {
      $bundleFields = $this->entityTypeManager
        ->getStorage('field_config')
        ->loadByProperties([
          'entity_type' => $entityType,
          'bundle' => $bundle,
        ]);

      foreach ($bundleFields as $field) {
        $fieldType = $field->getType();

        // Include numeric field types suitable for ratings
        if (in_array($fieldType, ['integer', 'decimal', 'float'])) {
          $numericFields[$field->getName()] = $field->getLabel();
        }
      }
    }
    catch (\Exception $e) {
      // Log exception if needed, but don't break the wizard
    }

    return $numericFields;
  }

  /**
   * Check if a Rating Score field exists on a content type.
   *
   * @param string $bundle
   *   The bundle (content type) to check.
   * @param string $entityType
   *   The entity type (default: 'node').
   *
   * @return bool
   *   TRUE if rating_score field exists, FALSE otherwise.
   */
  public function hasRatingScoreField($bundle, $entityType = 'node') {
    try {
      $field = $this->entityTypeManager
        ->getStorage('field_config')
        ->load("$entityType.$bundle.rating_score");

      return $field !== NULL;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get all content types available on the system.
   *
   * @return array
   *   Array of content type machine names and labels.
   *   Format: ['machine_name' => 'Label']
   */
  public function getAvailableContentTypes() {
    $contentTypes = [];

    try {
      $types = $this->entityTypeManager
        ->getStorage('node_type')
        ->loadMultiple();

      foreach ($types as $type) {
        $contentTypes[$type->id()] = $type->label();
      }
    }
    catch (\Exception $e) {
      // Return empty array if error
    }

    return $contentTypes;
  }

  /**
   * Get content types that have numeric fields (potential rating candidates).
   *
   * @return array
   *   Array of content type IDs that have numeric fields.
   */
  public function getContentTypesWithNumericFields() {
    $eligibleTypes = [];

    try {
      $types = $this->entityTypeManager
        ->getStorage('node_type')
        ->loadMultiple();

      foreach ($types as $type) {
        $numericFields = $this->detectNumericFields($type->id());
        if (!empty($numericFields)) {
          $eligibleTypes[$type->id()] = $type->label();
        }
      }
    }
    catch (\Exception $e) {
      // Return empty array if error
    }

    return $eligibleTypes;
  }

}
