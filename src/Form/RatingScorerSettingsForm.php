<?php

namespace Drupal\rating_scorer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Rating Scorer settings.
 */
class RatingScorerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['rating_scorer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rating_scorer_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rating_scorer.settings');

    $form['field_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Field Mapping (for auto-calculation)'),
      '#description' => $this->t('Configure which fields contain the number of ratings and average rating. When configured, rating scores will be automatically calculated and cached on save.'),
      '#open' => TRUE,
    ];

    $form['field_mapping']['ratings_field_mapping'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of Ratings Field (machine name)'),
      '#description' => $this->t('e.g., field_num_ratings'),
      '#default_value' => $config->get('ratings_field_mapping'),
    ];

    $form['field_mapping']['average_field_mapping'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Average Rating Field (machine name)'),
      '#description' => $this->t('e.g., field_average_rating'),
      '#default_value' => $config->get('average_field_mapping'),
    ];

    $form['default_minimum_ratings'] = [
      '#type' => 'number',
      '#title' => $this->t('Default minimum ratings threshold'),
      '#description' => $this->t('The default value for the minimum ratings threshold used in Bayesian average calculations. This affects how quickly items with few ratings can achieve high scores.'),
      '#default_value' => $config->get('default_minimum_ratings'),
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['default_rating'] = [
      '#type' => 'number',
      '#title' => $this->t('Default rating value'),
      '#description' => $this->t('The default rating value (0-5).'),
      '#default_value' => $config->get('default_rating'),
      '#min' => 0,
      '#max' => 5,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['default_num_ratings'] = [
      '#type' => 'number',
      '#title' => $this->t('Default number of ratings'),
      '#description' => $this->t('The default number of ratings to display.'),
      '#default_value' => $config->get('default_num_ratings'),
      '#min' => 0,
      '#max' => 10000,
      '#required' => TRUE,
    ];

    $form['default_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Default scoring method'),
      '#description' => $this->t('The default calculation method to use.'),
      '#options' => [
        'weighted' => $this->t('Weighted Score'),
        'bayesian' => $this->t('Bayesian Average'),
        'wilson' => $this->t('Wilson Score'),
      ],
      '#default_value' => $config->get('default_method'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('rating_scorer.settings')
      ->set('ratings_field_mapping', $form_state->getValue('ratings_field_mapping'))
      ->set('average_field_mapping', $form_state->getValue('average_field_mapping'))
      ->set('default_minimum_ratings', $form_state->getValue('default_minimum_ratings'))
      ->set('default_rating', $form_state->getValue('default_rating'))
      ->set('default_num_ratings', $form_state->getValue('default_num_ratings'))
      ->set('default_method', $form_state->getValue('default_method'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
