<?php

namespace Drupal\rating_scorer\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Service for gathering rating scorer dashboard data.
 */
class RatingScorerDashboardService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a RatingScorerDashboardService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    Connection $database,
    FieldTypePluginManagerInterface $field_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * Get all field mappings with their status and metrics.
   *
   * @return array
   *   Array of field mappings with status information.
   */
  public function getFieldMappingsWithStatus() {
    $field_mappings = $this->entityTypeManager
      ->getStorage('rating_scorer_field_mapping')
      ->loadMultiple();

    $mappings_data = [];

    foreach ($field_mappings as $mapping) {
      $content_type = $mapping->get('content_type');
      $scoring_method = $mapping->get('scoring_method');

      // Add validation status
      $validation = $this->validateMapping($mapping);

      $mappings_data[] = [
        'id' => $mapping->id(),
        'label' => $mapping->label(),
        'content_type' => $content_type,
        'scoring_method' => $scoring_method,
        'entity_count' => $this->getContentTypeEntityCount($content_type),
        'rating_score_count' => $this->getRatingScoreFieldCount($content_type),
        'last_recalculation' => $this->getLastRecalculationTime($content_type),
        'validation_status' => $validation['status'],
        'validation_message' => $validation['message'],
        'missing_fields' => $validation['missing_fields'],
      ];
    }

    return $mappings_data;
  }

  /**
   * Validates if all required fields exist for a mapping.
   *
   * @param \Drupal\rating_scorer\Entity\RatingScorerFieldMapping $mapping
   *   The mapping entity.
   *
   * @return array
   *   Validation result with keys:
   *   - status: 'green', 'yellow', or 'red'
   *   - message: Human-readable status message
   *   - missing_fields: Array of missing field names
   */
  protected function validateMapping($mapping) {
    $missing_fields = [];
    $content_type = $mapping->get('content_type');
    $source_type = $mapping->get('source_type');

    // Check critical field: number_of_ratings_field
    $number_field = $mapping->get('number_of_ratings_field');
    if ($number_field && !$this->fieldExists($number_field, $content_type)) {
      $missing_fields['number_of_ratings'] = $number_field;
    }

    // Check critical field: average_rating_field
    $average_field = $mapping->get('average_rating_field');
    if ($average_field && !$this->fieldExists($average_field, $content_type)) {
      $missing_fields['average_rating'] = $average_field;
    }

    // Check source-specific fields
    if ($source_type === 'VOTINGAPI') {
      $vote_field = $mapping->get('vote_field');
      if ($vote_field && !$this->fieldExists($vote_field, $content_type)) {
        $missing_fields['vote_field'] = $vote_field;
      }
    }

    // Determine status level
    if (empty($missing_fields)) {
      return [
        'status' => 'green',
        'message' => 'All fields exist',
        'missing_fields' => [],
      ];
    } elseif (isset($missing_fields['number_of_ratings']) || isset($missing_fields['average_rating'])) {
      return [
        'status' => 'red',
        'message' => 'Error - Critical field missing',
        'missing_fields' => $missing_fields,
      ];
    } else {
      return [
        'status' => 'yellow',
        'message' => 'Warning - Source field missing',
        'missing_fields' => $missing_fields,
      ];
    }
  }

  /**
   * Checks if a field exists on a content type.
   *
   * @param string $field_name
   *   The field name.
   * @param string $content_type
   *   The content type machine name.
   *
   * @return bool
   *   TRUE if field exists, FALSE otherwise.
   */
  protected function fieldExists($field_name, $content_type) {
    try {
      $field_config = \Drupal::entityTypeManager()
        ->getStorage('field_config')
        ->load('node.' . $content_type . '.' . $field_name);
      return (bool) $field_config;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get count of entities for a content type.
   *
   * @param string $bundle
   *   The content type bundle.
   *
   * @return int
   *   The number of entities.
   */
  protected function getContentTypeEntityCount($bundle) {
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $bundle);

    return $query->count()->execute();
  }

  /**
   * Get count of entities with populated rating score field.
   *
   * @param string $bundle
   *   The content type bundle.
   *
   * @return int
   *   The number of entities with rating score field values.
   */
  protected function getRatingScoreFieldCount($bundle) {
    try {
      // Get field definitions for this content type to find rating_score fields
      $field_definitions = \Drupal::service('entity_field.manager')
        ->getFieldDefinitions('node', $bundle);

      $rating_score_field = NULL;
      foreach ($field_definitions as $field_definition) {
        if ($field_definition->getType() === 'rating_score') {
          $rating_score_field = $field_definition->getName();
          break;
        }
      }

      // If no rating_score field found, return 0
      if (!$rating_score_field) {
        return 0;
      }

      $query = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $bundle)
        ->exists($rating_score_field);

      return $query->count()->execute();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Get last recalculation timestamp for a content type.
   *
   * @param string $bundle
   *   The content type bundle.
   *
   * @return string|null
   *   Human-readable last recalculation time, or NULL if never recalculated.
   */
  protected function getLastRecalculationTime($bundle) {
    try {
      // Get field definitions for this content type to find rating_score fields
      $field_definitions = \Drupal::service('entity_field.manager')
        ->getFieldDefinitions('node', $bundle);

      $rating_score_field = NULL;
      foreach ($field_definitions as $field_definition) {
        if ($field_definition->getType() === 'rating_score') {
          $rating_score_field = $field_definition->getName();
          break;
        }
      }

      // If no rating_score field found, return NULL
      if (!$rating_score_field) {
        return NULL;
      }

      $query = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $bundle)
        ->exists($rating_score_field)
        ->sort('changed', 'DESC')
        ->range(0, 1);

      $entity_ids = $query->execute();

      if (empty($entity_ids)) {
        return NULL;
      }

      $entity_id = reset($entity_ids);
      $node = $this->entityTypeManager->getStorage('node')->load($entity_id);

      if ($node) {
        $timestamp = $node->getChangedTime();
        return $this->formatTimestamp($timestamp);
      }
    }
    catch (\Exception $e) {
      return NULL;
    }

    return NULL;
  }

  /**
   * Format a timestamp for display.
   *
   * @param int $timestamp
   *   The timestamp to format.
   *
   * @return string
   *   Formatted timestamp string.
   */
  protected function formatTimestamp($timestamp) {
    $diff = \Drupal::time()->getCurrentTime() - $timestamp;

    if ($diff < 60) {
      return 'Just now';
    }
    elseif ($diff < 3600) {
      $minutes = floor($diff / 60);
      return "$minutes minute" . ($minutes > 1 ? 's' : '') . ' ago';
    }
    elseif ($diff < 86400) {
      $hours = floor($diff / 3600);
      return "$hours hour" . ($hours > 1 ? 's' : '') . ' ago';
    }
    elseif ($diff < 604800) {
      $days = floor($diff / 86400);
      return "$days day" . ($days > 1 ? 's' : '') . ' ago';
    }
    else {
      $date_formatter = \Drupal::service('date.formatter');
      return $date_formatter->format($timestamp, 'short');
    }
  }

  /**
   * Get overall dashboard statistics.
   *
   * @return array
   *   Array with statistics.
   */
  public function getDashboardStatistics() {
    $mappings = $this->getFieldMappingsWithStatus();

    $total_entities = 0;
    $total_with_ratings = 0;

    foreach ($mappings as $mapping) {
      $total_entities += $mapping['entity_count'];
      $total_with_ratings += $mapping['rating_score_count'];
    }

    return [
      'total_mappings' => count($mappings),
      'total_entities' => $total_entities,
      'total_with_ratings' => $total_with_ratings,
      'coverage_percentage' => $total_entities > 0 ? round(($total_with_ratings / $total_entities) * 100, 1) : 0,
    ];
  }

}
