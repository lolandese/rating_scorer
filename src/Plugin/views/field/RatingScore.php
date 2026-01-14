<?php

namespace Drupal\rating_scorer\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to calculate and display a rating score based on two fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("rating_score")
 */
class RatingScore extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['number_of_ratings_field'] = ['default' => ''];
    $options['average_rating_field'] = ['default' => ''];
    $options['scoring_method'] = ['default' => 'weighted'];
    $options['minimum_ratings_threshold'] = ['default' => 5];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['number_of_ratings_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of Ratings Field'),
      '#default_value' => $this->options['number_of_ratings_field'] ?? '',
    ];

    $form['average_rating_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Average Rating Field'),
      '#default_value' => $this->options['average_rating_field'] ?? '',
    ];

    $form['scoring_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Scoring Method'),
      '#options' => [
        'weighted' => $this->t('Weighted Score'),
        'bayesian' => $this->t('Bayesian Average'),
        'wilson' => $this->t('Wilson Score'),
      ],
      '#default_value' => $this->options['scoring_method'],
    ];

    $form['minimum_ratings_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Ratings Threshold'),
      '#default_value' => $this->options['minimum_ratings_threshold'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $number_of_ratings_field = $this->options['number_of_ratings_field'];
    $average_rating_field = $this->options['average_rating_field'];

    if (empty($number_of_ratings_field) || empty($average_rating_field)) {
      return $this->t('Error: Missing field configuration');
    }

    // Get the values from the referenced fields, treating empty values as zero.
    $number_of_ratings_value = $this->view->field[$number_of_ratings_field]->getValue($values);
    $average_rating_value = $this->view->field[$average_rating_field]->getValue($values);

    $number_of_ratings = (int) (!empty($number_of_ratings_value) ? $number_of_ratings_value : 0);
    $average_rating = (float) (!empty($average_rating_value) ? $average_rating_value : 0);

    $score = $this->calculateScore(
      $number_of_ratings,
      $average_rating,
      $this->options['scoring_method'],
      $this->options['minimum_ratings_threshold']
    );

    return round($score, 2);
  }

  /**
   * Calculate the rating score using the specified method.
   *
   * @param int $number_of_ratings
   *   The number of ratings.
   * @param float $average_rating
   *   The average rating.
   * @param string $method
   *   The scoring method ('weighted', 'bayesian', 'wilson').
   * @param int $minimum_threshold
   *   The minimum ratings threshold for Bayesian method.
   *
   * @return float
   *   The calculated score.
   */
  protected function calculateScore($number_of_ratings, $average_rating, $method, $minimum_threshold) {
    switch ($method) {
      case 'bayesian':
        return $this->calculateBayesianAverage($number_of_ratings, $average_rating, $minimum_threshold);

      case 'wilson':
        return $this->calculateWilsonScore($number_of_ratings, $average_rating);

      case 'weighted':
      default:
        return $this->calculateWeightedScore($number_of_ratings, $average_rating);
    }
  }

  /**
   * Calculate weighted score.
   *
   * @param int $number_of_ratings
   *   The number of ratings.
   * @param float $average_rating
   *   The average rating.
   *
   * @return float
   *   The weighted score.
   */
  protected function calculateWeightedScore($number_of_ratings, $average_rating) {
    // Simple weighted score: average_rating * log of number of ratings.
    return $average_rating * log($number_of_ratings + 1);
  }

  /**
   * Calculate Bayesian average.
   *
   * @param int $number_of_ratings
   *   The number of ratings.
   * @param float $average_rating
   *   The average rating.
   * @param int $minimum_threshold
   *   The minimum ratings threshold.
   *
   * @return float
   *   The Bayesian average score.
   */
  protected function calculateBayesianAverage($number_of_ratings, $average_rating, $minimum_threshold) {
    // Bayesian average: (number_of_ratings * average_rating + minimum_threshold * 2.5) / (number_of_ratings + minimum_threshold)
    // Assuming a prior rating of 2.5 (midpoint of 0-5 scale).
    $prior_rating = 2.5;
    return ($number_of_ratings * $average_rating + $minimum_threshold * $prior_rating) / ($number_of_ratings + $minimum_threshold);
  }

  /**
   * Calculate Wilson score.
   *
   * @param int $number_of_ratings
   *   The number of ratings.
   * @param float $average_rating
   *   The average rating.
   *
   * @return float
   *   The Wilson score (0-1 scale).
   */
  protected function calculateWilsonScore($number_of_ratings, $average_rating) {
    // Simplified Wilson score assuming binary positive/negative rating.
    // Normalize average_rating to 0-1 range (assuming 0-5 scale).
    $normalized_rating = $average_rating / 5;

    // Wilson score interval lower bound.
    $z = 1.96; // 95% confidence.

    if ($number_of_ratings == 0) {
      return 0;
    }

    $phat = $normalized_rating;
    $denominator = 1 + $z * $z / $number_of_ratings;
    $center = ($phat + $z * $z / (2 * $number_of_ratings)) / $denominator;
    $margin = $z * sqrt(($phat * (1 - $phat) / $number_of_ratings) + ($z * $z / (4 * $number_of_ratings * $number_of_ratings))) / $denominator;

    return max(0, $center - $margin);
  }

}
