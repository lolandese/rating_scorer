<?php

namespace Drupal\rating_scorer;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Render\Markup;

/**
 * Provides a listing of rating scorer field mapping configurations.
 */
class RatingScorerFieldMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['content_type'] = $this->t('Content Type');
    $header['scoring_method'] = $this->t('Scoring Method');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['content_type'] = $entity->get('content_type');
    $scoring_method = $entity->get('scoring_method');
    $row['scoring_method'] = $scoring_method ? ucfirst($scoring_method) : '-';
    
    // Add validation status
    $validation = $this->validateMapping($entity);
    $row['status'] = $this->getStatusDisplay($validation);
    
    return $row + parent::buildRow($entity);
  }

  /**
   * Validates if all required fields exist for a mapping.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The mapping entity.
   *
   * @return array
   *   Validation result with keys:
   *   - status: 'green', 'yellow', or 'red'
   *   - message: Human-readable status message
   *   - missing_fields: Array of missing field names
   */
  private function validateMapping(EntityInterface $entity) {
    $missing_fields = [];
    $content_type = $entity->get('content_type');
    $source_type = $entity->get('source_type');
    
    // Check critical field: number_of_ratings_field (always required)
    $number_field = $entity->get('number_of_ratings_field');
    if ($number_field && !$this->fieldExists($number_field, $content_type)) {
      $missing_fields['number_of_ratings'] = $number_field;
    }
    
    // Check critical field: average_rating_field (always required)
    $average_field = $entity->get('average_rating_field');
    if ($average_field && !$this->fieldExists($average_field, $content_type)) {
      $missing_fields['average_rating'] = $average_field;
    }
    
    // Check source-specific fields
    if ($source_type === 'VOTINGAPI') {
      $vote_field = $entity->get('vote_field');
      if ($vote_field && !$this->fieldExists($vote_field, $content_type)) {
        $missing_fields['vote_field'] = $vote_field;
      }
    }
    
    // Determine status level
    if (empty($missing_fields)) {
      return [
        'status' => 'green',
        'message' => $this->t('All fields exist'),
        'missing_fields' => [],
      ];
    } elseif (isset($missing_fields['number_of_ratings']) || isset($missing_fields['average_rating'])) {
      // Critical fields missing
      return [
        'status' => 'red',
        'message' => $this->t('Error - Critical field missing'),
        'missing_fields' => $missing_fields,
      ];
    } else {
      // Non-critical fields missing (e.g., vote source field)
      return [
        'status' => 'yellow',
        'message' => $this->t('Warning - Source field missing'),
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
  private function fieldExists($field_name, $content_type) {
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    if (!$field_storage) {
      return FALSE;
    }
    
    // Check if this bundle has an instance of this field
    $field_config = \Drupal::entityTypeManager()
      ->getStorage('field_config')
      ->load('node.' . $content_type . '.' . $field_name);
    
    return (bool) $field_config;
  }

  /**
   * Returns HTML display for validation status.
   *
   * @param array $validation
   *   The validation result array.
   *
   * @return \Drupal\Component\Render\Markup
   *   The rendered status indicator.
   */
  private function getStatusDisplay(array $validation) {
    $status_icons = [
      'green' => '✅',
      'yellow' => '⚠️',
      'red' => '🔴',
    ];
    
    $status = $validation['status'];
    $icon = $status_icons[$status] ?? '?';
    $message = $validation['message'];
    
    // Build tooltip with missing fields if any
    $tooltip = $message;
    if (!empty($validation['missing_fields'])) {
      $missing = implode(', ', array_values($validation['missing_fields']));
      $tooltip .= ' (' . $missing . ')';
    }
    
    $html = '<span title="' . htmlspecialchars($tooltip) . '" class="rating-scorer-status rating-scorer-status-' . htmlspecialchars($status) . '">' . $icon . ' ' . $message . '</span>';
    
    return Markup::create($html);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // Add CSS library for status indicators
    $build['#attached']['library'][] = 'rating_scorer/admin-styles';

    // Add "Dashboard" and "New Field Mapping" links
    $dashboard_url = Url::fromRoute('rating_scorer.dashboard');
    $wizard_url = Url::fromRoute('rating_scorer.field_mapping_wizard');

    $build['#prefix'] = '<p><a href="' . $dashboard_url->toString() . '" class="button">' . $this->t('← Dashboard') . '</a> <a href="' . $wizard_url->toString() . '" class="button button-action">' . $this->t('+ New Field Mapping') . '</a></p>';

    return $build;
  }

}
