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

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<div class="description"><p><strong>' . $this->t('Note:') . '</strong> ' . $this->t('These default settings apply only to the Calculator widget displayed on admin pages. For automatic scoring of content fields, configure Field Mappings instead.') . '</p></div>',
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

    $form['bayesian_assumed_average'] = [
      '#type' => 'number',
      '#title' => $this->t('Bayesian assumed average'),
      '#description' => $this->t('The default assumption for the average rating used in Bayesian calculations. This represents what initial score a new item starts with before any ratings are gathered. Default is 3.5 for a 5-star scale.'),
      '#default_value' => $config->get('bayesian_assumed_average'),
      '#min' => 2.5,
      '#max' => 4.5,
      '#step' => 0.1,
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
        'weighted' => $this->t('Weighted Score: Favors high-volume ratings; simple to understand'),
        'bayesian' => $this->t('Bayesian Average (recommended): Prevents gaming; requires confidence through volume'),
        'wilson' => $this->t('Wilson Score: Most conservative; penalizes items with few ratings'),
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
      ->set('default_minimum_ratings', $form_state->getValue('default_minimum_ratings'))
      ->set('bayesian_assumed_average', $form_state->getValue('bayesian_assumed_average'))
      ->set('default_rating', $form_state->getValue('default_rating'))
      ->set('default_num_ratings', $form_state->getValue('default_num_ratings'))
      ->set('default_method', $form_state->getValue('default_method'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
