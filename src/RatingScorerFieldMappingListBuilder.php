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

    // Add "New Field Mapping" link using the wizard
    $wizard_url = Url::fromRoute('rating_scorer.field_mapping_wizard');

    $build['#prefix'] = '<p><a href="' . $wizard_url->toString() . '" class="button button-action">' . $this->t('+ New Field Mapping') . '</a></p>';

    return $build;
  }

}
