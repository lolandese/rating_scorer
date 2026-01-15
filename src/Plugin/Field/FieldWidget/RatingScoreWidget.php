<?php

namespace Drupal\rating_scorer\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'rating_score_widget' widget.
 *
 * @FieldWidget(
 *   id = "rating_score_widget",
 *   label = @Translation("Rating Score Widget"),
 *   field_types = {
 *     "rating_score"
 *   }
 * )
 */
class RatingScoreWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'step' => 0.01,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['step'] = [
      '#type' => 'number',
      '#title' => t('Step'),
      '#description' => t('Amount to increment or decrement when using up/down arrow keys.'),
      '#default_value' => $this->getSetting('step'),
      '#step' => 0.01,
    ];

    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered.'),
      '#default_value' => $this->getSetting('placeholder'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'number',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#step' => $this->getSetting('step'),
      '#placeholder' => $this->getSetting('placeholder'),
    ];

    return $element;
  }

}
