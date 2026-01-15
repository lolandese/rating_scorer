<?php

namespace Drupal\rating_scorer\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for Rating Scorer field mapping configurations.
 */
class RatingScorerFieldMappingForm extends EntityForm {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs the form.
   */
  public static function create(ContainerInterface $container) {
    $form = new static();
    $form->fieldManager = $container->get('entity_field.manager');
    $form->entityTypeManager = $container->get('entity_type.manager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $mapping = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $mapping->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#default_value' => $mapping->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$mapping->isNew(),
    ];

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $this->getNodeTypeOptions(),
      '#default_value' => $mapping->get('content_type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateFieldOptions'],
        'event' => 'change',
        'wrapper' => 'field-options-wrapper',
      ],
    ];

    $form['field_options'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'field-options-wrapper'],
    ];

    $content_type = $form_state->getValue('content_type') ?? $mapping->get('content_type');

    if ($content_type) {
      $field_options = $this->getFieldOptions($content_type);

      $form['field_options']['number_of_ratings_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Number of Ratings Field'),
        '#options' => $field_options,
        '#default_value' => $mapping->get('number_of_ratings_field'),
        '#required' => TRUE,
      ];

      $form['field_options']['average_rating_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Average Rating Field'),
        '#options' => $field_options,
        '#default_value' => $mapping->get('average_rating_field'),
        '#required' => TRUE,
      ];
    }

    $form['scoring_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Scoring Method'),
      '#options' => [
        'weighted' => $this->t('Weighted (Logarithmic)'),
        'bayesian' => $this->t('Bayesian Average (Recommended)'),
        'wilson' => $this->t('Wilson Score'),
      ],
      '#default_value' => $mapping->get('scoring_method') ?? 'bayesian',
      '#required' => TRUE,
    ];

    $form['bayesian_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Bayesian Threshold'),
      '#description' => $this->t('For Bayesian scoring: minimum ratings to reach high scores. Typical value: 10'),
      '#default_value' => $mapping->get('bayesian_threshold') ?? 10,
      '#min' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $mapping = $this->entity;

    $mapping->set('label', $form_state->getValue('label'));
    $mapping->set('content_type', $form_state->getValue('content_type'));
    $mapping->set('number_of_ratings_field', $form_state->getValue('number_of_ratings_field'));
    $mapping->set('average_rating_field', $form_state->getValue('average_rating_field'));
    $mapping->set('scoring_method', $form_state->getValue('scoring_method'));
    $mapping->set('bayesian_threshold', $form_state->getValue('bayesian_threshold'));

    $status = $mapping->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('Field mapping %label has been added.', ['%label' => $mapping->label()]));
    } else {
      $this->messenger()->addMessage($this->t('Field mapping %label has been updated.', ['%label' => $mapping->label()]));
    }

    $form_state->setRedirectUrl($mapping->toUrl('collection'));
  }

  /**
   * Check if the configuration ID already exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('rating_scorer_field_mapping')->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Get available node type options.
   */
  protected function getNodeTypeOptions() {
    $options = [];
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $type) {
      $options[$type->id()] = $type->label();
    }
    return $options;
  }

  /**
   * Get available numeric fields for a content type.
   */
  protected function getFieldOptions($bundle) {
    $options = [];
    $fields = $this->fieldManager->getFieldDefinitions('node', $bundle);

    foreach ($fields as $field_name => $field_definition) {
      // Only include numeric fields (integer, decimal, float).
      $type = $field_definition->getType();
      if (in_array($type, ['integer', 'decimal', 'float'])) {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    return $options;
  }

  /**
   * AJAX callback to update field options.
   */
  public function updateFieldOptions(array &$form, FormStateInterface $form_state) {
    return $form['field_options'];
  }

}
