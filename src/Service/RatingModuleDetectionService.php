<?php

namespace Drupal\rating_scorer\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Service for detecting and listing rating module fields.
 */
class RatingModuleDetectionService {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs the service.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityFieldManagerInterface $field_manager,
    FieldTypePluginManagerInterface $field_type_manager
  ) {
    $this->moduleHandler = $module_handler;
    $this->fieldManager = $field_manager;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * Get a list of detected rating modules.
   *
   * @return array
   *   Array of detected rating modules with their info.
   */
  public function getDetectedRatingModules() {
    $modules = [];

    if ($this->moduleHandler->moduleExists('fivestar')) {
      $modules['fivestar'] = [
        'name' => 'Fivestar',
        'field_type' => 'fivestar',
        'description' => 'Fivestar module detected - star rating fields',
      ];
    }

    if ($this->moduleHandler->moduleExists('votingapi')) {
      $modules['votingapi'] = [
        'name' => 'Voting API',
        'field_type' => 'votingapi',
        'description' => 'Voting API module detected - vote aggregation',
      ];
    }

    if ($this->moduleHandler->moduleExists('rate')) {
      $modules['rate'] = [
        'name' => 'Rate',
        'field_type' => 'rate',
        'description' => 'Rate module detected - rating widget',
      ];
    }

    return $modules;
  }

  /**
   * Check if any rating module is installed.
   *
   * @return bool
   *   TRUE if any rating module is installed.
   */
  public function hasRatingModules() {
    return !empty($this->getDetectedRatingModules());
  }

  /**
   * Get suggested fields for a content type based on detected rating modules.
   *
   * @param string $bundle
   *   The bundle (content type) machine name.
   *
   * @return array
   *   Array of suggestions with field name, module, and type.
   */
  public function suggestRatingFields($bundle) {
    $suggestions = [];
    $fields = $this->fieldManager->getFieldDefinitions('node', $bundle);

    // Check for Fivestar fields
    if ($this->moduleHandler->moduleExists('fivestar')) {
      foreach ($fields as $field_name => $field_def) {
        if ($field_def->getType() === 'fivestar') {
          $suggestions[] = [
            'field_name' => $field_name,
            'label' => $field_def->getLabel(),
            'module' => 'fivestar',
            'type' => 'rating',
            'description' => 'Fivestar rating field - use as Average Rating',
          ];
        }
      }
    }

    // Check for numeric fields that might be vote counts
    $numeric_types = ['integer', 'decimal', 'float'];
    foreach ($fields as $field_name => $field_def) {
      if (in_array($field_def->getType(), $numeric_types)) {
        // Common naming patterns for vote count fields
        $label_lower = strtolower((string) $field_def->getLabel());
        if (preg_match('/(vote|rating|count|number|total).*count/i', $label_lower) ||
            preg_match('/count.*(vote|rating)/i', $label_lower)) {
          $suggestions[] = [
            'field_name' => $field_name,
            'label' => $field_def->getLabel(),
            'module' => 'custom',
            'type' => 'count',
            'description' => 'Numeric field - likely vote/rating count',
          ];
        }
      }
    }

    return $suggestions;
  }

  /**
   * Get all numeric fields for a content type.
   *
   * @param string $bundle
   *   The bundle (content type) machine name.
   *
   * @return array
   *   Array of numeric fields keyed by field name.
   */
  public function getNumericFields($bundle) {
    $numeric_fields = [];
    $fields = $this->fieldManager->getFieldDefinitions('node', $bundle);
    $numeric_types = ['integer', 'decimal', 'float', 'fivestar'];

    foreach ($fields as $field_name => $field_def) {
      if (in_array($field_def->getType(), $numeric_types)) {
        $numeric_fields[$field_name] = (string) $field_def->getLabel();
      }
    }

    return $numeric_fields;
  }

  /**
   * Get rating module field suggestions formatted for UI display.
   *
   * @param string $bundle
   *   The bundle (content type) machine name.
   *
   * @return array
   *   Array of suggestions with human-readable descriptions.
   */
  public function getFieldSuggestionsForDisplay($bundle) {
    $suggestions = $this->suggestRatingFields($bundle);
    $formatted = [];

    foreach ($suggestions as $suggestion) {
      $module_label = match($suggestion['module']) {
        'fivestar' => '[Fivestar]',
        'votingapi' => '[Voting API]',
        'rate' => '[Rate]',
        'custom' => '[Detected]',
        default => '',
      };

      $formatted[] = [
        'field_name' => $suggestion['field_name'],
        'label' => $suggestion['label'] . ' ' . $module_label,
        'description' => $suggestion['description'],
        'type' => $suggestion['type'],
      ];
    }

    return $formatted;
  }

}
