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
    $scoring_method = $entity->get('scoring_method');
    $row['scoring_method'] = $scoring_method ? ucfirst($scoring_method) : '-';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // Add "Dashboard" and "New Field Mapping" links
    $dashboard_url = Url::fromRoute('rating_scorer.dashboard');
    $wizard_url = Url::fromRoute('rating_scorer.field_mapping_wizard');

    $build['#prefix'] = '<p><a href="' . $dashboard_url->toString() . '" class="button">' . $this->t('← Dashboard') . '</a> <a href="' . $wizard_url->toString() . '" class="button button-action">' . $this->t('+ New Field Mapping') . '</a></p>';

    return $build;
  }

}
