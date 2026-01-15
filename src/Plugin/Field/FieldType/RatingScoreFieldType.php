<?php

namespace Drupal\rating_scorer\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'rating_score' field type.
 *
 * @FieldType(
 *   id = "rating_score",
 *   label = @Translation("Rating Score"),
 *   description = @Translation("Stores and computes fair rating scores based on average rating and number of ratings"),
 *   default_widget = "rating_score_widget",
 *   default_formatter = "rating_score_formatter"
 * )
 */
class RatingScoreFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'precision' => 10,
      'scale' => 2,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'number_of_ratings_field' => '',
      'average_rating_field' => '',
      'scoring_method' => 'bayesian',
      'bayesian_threshold' => 10,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Rating Score'))
      ->setDescription(t('The computed rating score value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * Get the configured number of ratings field.
   */
  public function getNumberOfRatingsField() {
    return $this->getSetting('number_of_ratings_field');
  }

  /**
   * Get the configured average rating field.
   */
  public function getAverageRatingField() {
    return $this->getSetting('average_rating_field');
  }

  /**
   * Get the configured scoring method.
   */
  public function getScoringMethod() {
    return $this->getSetting('scoring_method');
  }

  /**
   * Get the configured Bayesian threshold.
   */
  public function getBayesianThreshold() {
    return $this->getSetting('bayesian_threshold');
  }

}
