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

    // Display result if available.
    if ($form_state->get('result') !== NULL) {
      $result = $form_state->get('result');
      $form['result'] = [
        '#type' => 'markup',
        '#markup' => '<div class="rating-scorer-result"><strong>' . $this->t('Score:') . '</strong> ' . $result . '</div>',
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

    // Simple scoring calculation: average rating * log of number of ratings.
    $score = $average_rating * log($number_of_ratings + 1);

    $form_state->set('result', round($score, 2));
    $form_state->setRebuild(TRUE);
  }

}
