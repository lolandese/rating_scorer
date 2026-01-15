<?php

namespace Drupal\rating_scorer;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

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
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['content_type'] = $entity->get('content_type');
    $row['scoring_method'] = ucfirst($entity->get('scoring_method'));
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    
    // Add "Add a field mapping" link above the table
    $add_url = Url::fromRoute('entity.rating_scorer_field_mapping.add_form');
    
    // Add add link (no back link needed - this IS the parent page)
    $build['#prefix'] = '<p><a href="' . $add_url->toString() . '">Add a field mapping</a></p>';
    
    return $build;
  }

}
