<?php

namespace Drupal\rating_scorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Rating Scorer Calculator form.
 */
class RatingScorerCalculatorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rating_scorer_calculator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes'] = ['class' => ['rating-scorer-calculator-form']];

    $form['number_of_ratings'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Ratings'),
      '#description' => $this->t('The total number of ratings'),
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 1,
    ];

    $form['average_rating'] = [
      '#type' => 'number',
      '#title' => $this->t('Average Rating'),
      '#description' => $this->t('The average rating value'),
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.1,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate Score'),
    ];

    // Display results if available.
    if ($form_state->get('result') !== NULL) {
      $results = $form_state->get('result');
      $form['results'] = [
        '#type' => 'markup',
        '#markup' => '<div class="rating-scorer-results"><h3>' . $this->t('Comparison of Scoring Methods') . '</h3>' . $results . '</div>',
        '#weight' => 100,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $number_of_ratings = (int) $form_state->getValue('number_of_ratings');
    $average_rating = (float) $form_state->getValue('average_rating');

    // Calculate scores using all three methods
    $weighted_score = $this->calculateWeightedScore($number_of_ratings, $average_rating);
    $bayesian_score = $this->calculateBayesianScore($number_of_ratings, $average_rating, 10);
    $wilson_score = $this->calculateWilsonScore($number_of_ratings, $average_rating);

    // Build comparison table
    $html = '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
    $html .= '<thead><tr style="border-bottom: 2px solid #ddd;"><th style="padding: 10px; text-align: left;">Method</th><th style="padding: 10px; text-align: right;">Score</th><th style="padding: 10px; text-align: left;">Description</th></tr></thead>';
    $html .= '<tbody>';
    $html .= '<tr style="border-bottom: 1px solid #eee;">';
    $html .= '<td style="padding: 10px;"><strong>' . $this->t('Weighted Score') . '</strong></td>';
    $html .= '<td style="padding: 10px; text-align: right;"><strong>' . number_format($weighted_score, 2) . '</strong></td>';
    $html .= '<td style="padding: 10px; font-size: 0.9em; color: #666;">Favors high-volume ratings; simple to understand</td>';
    $html .= '</tr>';
    $html .= '<tr style="border-bottom: 1px solid #eee; background-color: #f9f9f9;">';
    $html .= '<td style="padding: 10px;"><strong>' . $this->t('Bayesian Average') . '</strong> <span style="color: #0066cc; font-weight: bold;">★ Recommended</span></td>';
    $html .= '<td style="padding: 10px; text-align: right;"><strong>' . number_format($bayesian_score, 2) . '</strong></td>';
    $html .= '<td style="padding: 10px; font-size: 0.9em; color: #666;">Prevents gaming; requires confidence through volume</td>';
    $html .= '</tr>';
    $html .= '<tr style="border-bottom: 1px solid #eee;">';
    $html .= '<td style="padding: 10px;"><strong>' . $this->t('Wilson Score') . '</strong></td>';
    $html .= '<td style="padding: 10px; text-align: right;"><strong>' . number_format($wilson_score, 2) . '</strong></td>';
    $html .= '<td style="padding: 10px; font-size: 0.9em; color: #666;">Most conservative; penalizes items with few ratings</td>';
    $html .= '</tr>';
    $html .= '</tbody></table>';

    $form_state->set('result', $html);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Calculate weighted score using logarithmic weighting.
   */
  private function calculateWeightedScore($num_ratings, $avg_rating) {
    // Weighted score: average rating * log of number of ratings
    return $avg_rating * log($num_ratings + 1);
  }

  /**
   * Calculate Bayesian average score.
   */
  private function calculateBayesianScore($num_ratings, $avg_rating, $min_ratings = 10) {
    // Bayesian average: (min_ratings * avg_global + total_rating) / (min_ratings + num_ratings)
    // Assuming global average rating of 2.5 out of 5
    $global_avg = 2.5;
    return ($min_ratings * $global_avg + ($avg_rating * $num_ratings)) / ($min_ratings + $num_ratings);
  }

  /**
   * Calculate Wilson score.
   */
  private function calculateWilsonScore($num_ratings, $avg_rating) {
    // Wilson score interval (lower bound of CI at 95%)
    // Treating binary positive/negative: p = avg_rating / 5
    if ($num_ratings == 0) {
      return 0;
    }

    $p = $avg_rating / 5;
    $z = 1.96; // 95% confidence interval

    $numerator = $p + ($z * $z) / (2 * $num_ratings) - $z * sqrt(($p * (1 - $p) + ($z * $z) / (4 * $num_ratings)) / $num_ratings);
    $denominator = 1 + ($z * $z) / $num_ratings;

    return ($numerator / $denominator) * 5; // Scale back to 0-5
  }

}
