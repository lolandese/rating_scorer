<?php

namespace Drupal\rating_scorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rating_scorer\Service\FieldDetectionService;
use Drupal\rating_scorer\Service\FieldCreationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Multi-step form wizard for creating field mappings with auto-detection.
 */
class FieldMappingWizardForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field detection service.
   *
   * @var \Drupal\rating_scorer\Service\FieldDetectionService
   */
  protected $fieldDetection;

  /**
   * The field creation service.
   *
   * @var \Drupal\rating_scorer\Service\FieldCreationService
   */
  protected $fieldCreation;

  /**
   * Constructs the form.
   */
  public static function create(ContainerInterface $container) {
    $form = new static();
    $form->entityTypeManager = $container->get('entity_type.manager');
    $form->fieldDetection = $container->get('rating_scorer.field_detection');
    $form->fieldCreation = $container->get('rating_scorer.field_creation');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rating_scorer_field_mapping_wizard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 'select_content_type';

    $form['#tree'] = TRUE;

    switch ($step) {
      case 'select_content_type':
        $form = $this->buildSelectContentTypeStep($form, $form_state);
        break;

      case 'select_fields':
        $form = $this->buildSelectFieldsStep($form, $form_state);
        break;

      case 'rating_score_field':
        $form = $this->buildRatingScoreFieldStep($form, $form_state);
        break;

      case 'review':
        $form = $this->buildReviewStep($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * Build step 1: Select content type.
   */
  protected function buildSelectContentTypeStep(array $form, FormStateInterface $form_state) {
    $contentTypes = $this->fieldDetection->getContentTypesWithNumericFields();

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Let\'s create a field mapping for your content type. This wizard will guide you through setting up automatic rating score calculation.') . '</p>',
    ];

    $form['content_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Which content type do you want to set up?'),
      '#options' => $contentTypes,
      '#required' => TRUE,
      '#description' => $this->t('Only content types with numeric fields are shown.'),
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#submit' => [[$this, 'submitSelectContentType']],
    ];

    return $form;
  }

  /**
   * Build step 2: Select rating and review count fields.
   */
  protected function buildSelectFieldsStep(array $form, FormStateInterface $form_state) {
    $contentType = $form_state->get('content_type');
    $numericFields = $this->fieldDetection->detectNumericFields($contentType);

    if (empty($numericFields)) {
      $form['error'] = [
        '#markup' => '<div class="messages messages--error">' . $this->t('No numeric fields found on this content type.') . '</div>',
      ];
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => [[$this, 'submitBack']],
      ];
      return $form;
    }

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('We found the following numeric fields on %type. Please select which fields contain the average rating and number of reviews.', [
        '%type' => $contentType,
      ]) . '</p>',
    ];

    $form['number_of_ratings_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of Ratings Field'),
      '#options' => $numericFields,
      '#required' => TRUE,
      '#description' => $this->t('The field containing the count of reviews/ratings.'),
    ];

    $form['average_rating_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Average Rating Field'),
      '#options' => $numericFields,
      '#required' => TRUE,
      '#description' => $this->t('The field containing the average rating (e.g., 3.5 out of 5).'),
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => [[$this, 'submitBack']],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#submit' => [[$this, 'submitSelectFields']],
    ];

    return $form;
  }

  /**
   * Build step 3: Rating Score field creation.
   */
  protected function buildRatingScoreFieldStep(array $form, FormStateInterface $form_state) {
    $contentType = $form_state->get('content_type');
    $hasField = $this->fieldDetection->hasRatingScoreField($contentType);

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Now let\'s set up the Rating Score field.') . '</p>',
    ];

    if ($hasField) {
      $form['field_status'] = [
        '#markup' => '<div class="messages messages--status">' . $this->t('✓ Rating Score field already exists on %type.', [
          '%type' => $contentType,
        ]) . '</div>',
      ];
    }
    else {
      $form['create_field'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create Rating Score field'),
        '#description' => $this->t('We can automatically create the Rating Score field on %type for you. It will be added to the default form and view displays.', [
          '%type' => $contentType,
        ]),
        '#default_value' => TRUE,
      ];
    }

    $form['scoring_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scoring Method'),
      '#options' => [
        'bayesian' => $this->t('Bayesian Average (Recommended) - IMDB style: prevents items with few ratings from ranking too high'),
        'weighted' => $this->t('Weighted Score - Logarithmic weighting: simple approach balancing quality and quantity'),
        'wilson' => $this->t('Wilson Score - Conservative: most protective against low-volume items'),
      ],
      '#default_value' => 'bayesian',
      '#required' => TRUE,
    ];

    $form['bayesian_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Ratings Threshold (for Bayesian)'),
      '#default_value' => 10,
      '#min' => 1,
      '#max' => 1000,
      '#description' => $this->t('Items need this many ratings to reach high scores. Lower = more forgiving. Higher = more conservative.'),
      '#states' => [
        'visible' => [
          ':input[name="scoring_method"]' => ['value' => 'bayesian'],
        ],
      ],
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => [[$this, 'submitBack']],
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Review & Create'),
      '#submit' => [[$this, 'submitRatingScoreField']],
    ];

    return $form;
  }

  /**
   * Build step 4: Review and create.
   */
  protected function buildReviewStep(array $form, FormStateInterface $form_state) {
    $contentType = $form_state->get('content_type');
    $numRatingsField = $form_state->get('number_of_ratings_field');
    $avgRatingField = $form_state->get('average_rating_field');
    $scoringMethod = $form_state->get('scoring_method');
    $threshold = $form_state->get('bayesian_threshold');

    $form['review_intro'] = [
      '#markup' => '<p>' . $this->t('Review your field mapping configuration:') . '</p>',
    ];

    $form['review'] = [
      '#type' => 'item',
      '#markup' => '<dl>
        <dt>' . $this->t('Content Type:') . '</dt>
        <dd>' . $contentType . '</dd>
        <dt>' . $this->t('Number of Ratings Field:') . '</dt>
        <dd>' . $numRatingsField . '</dd>
        <dt>' . $this->t('Average Rating Field:') . '</dt>
        <dd>' . $avgRatingField . '</dd>
        <dt>' . $this->t('Scoring Method:') . '</dt>
        <dd>' . ucfirst(str_replace('_', ' ', $scoringMethod)) . '</dd>
        ' . ($scoringMethod === 'bayesian' ? '<dt>' . $this->t('Bayesian Threshold:') . '</dt><dd>' . $threshold . '</dd>' : '') . '
      </dl>',
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => [[$this, 'submitBack']],
    ];

    $form['actions']['create'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Field Mapping'),
      '#button_type' => 'primary',
      '#submit' => [[$this, 'submitCreateMapping']],
    ];

    return $form;
  }

  /**
   * Submit handler for select content type step.
   */
  public function submitSelectContentType(array &$form, FormStateInterface $form_state) {
    $form_state->set('content_type', $form_state->getValue('content_type'));
    $form_state->set('step', 'select_fields');
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for select fields step.
   */
  public function submitSelectFields(array &$form, FormStateInterface $form_state) {
    $form_state->set('number_of_ratings_field', $form_state->getValue('number_of_ratings_field'));
    $form_state->set('average_rating_field', $form_state->getValue('average_rating_field'));
    $form_state->set('step', 'rating_score_field');
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for rating score field step.
   */
  public function submitRatingScoreField(array &$form, FormStateInterface $form_state) {
    $form_state->set('scoring_method', $form_state->getValue('scoring_method'));
    $form_state->set('bayesian_threshold', $form_state->getValue('bayesian_threshold'));
    $form_state->set('create_field', $form_state->getValue('create_field') ?? FALSE);
    $form_state->set('step', 'review');
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler to go back to previous step.
   */
  public function submitBack(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    $previousSteps = [
      'select_fields' => 'select_content_type',
      'rating_score_field' => 'select_fields',
      'review' => 'rating_score_field',
    ];

    if (isset($previousSteps[$step])) {
      $form_state->set('step', $previousSteps[$step]);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Submit handler to create the mapping.
   */
  public function submitCreateMapping(array &$form, FormStateInterface $form_state) {
    $contentType = $form_state->get('content_type');
    $numRatingsField = $form_state->get('number_of_ratings_field');
    $avgRatingField = $form_state->get('average_rating_field');
    $scoringMethod = $form_state->get('scoring_method');
    $threshold = $form_state->get('bayesian_threshold');
    $createField = $form_state->get('create_field');

    try {
      // Create Rating Score field if needed
      if ($createField) {
        $this->fieldCreation->createRatingScoreFieldIfNeeded($contentType);
        $this->messenger()->addStatus($this->t('Rating Score field created successfully.'));
      }

      // Create field mapping entity
      $mappingId = "node_{$contentType}";
      $mapping = $this->entityTypeManager->getStorage('rating_scorer_field_mapping')->create([
        'id' => $mappingId,
        'label' => ucfirst(str_replace('_', ' ', $contentType)),
        'content_type' => $contentType,
        'number_of_ratings_field' => $numRatingsField,
        'average_rating_field' => $avgRatingField,
        'scoring_method' => $scoringMethod,
        'bayesian_threshold' => $threshold,
      ]);

      $mapping->save();

      $this->messenger()->addStatus($this->t('Field mapping created successfully for %type.', [
        '%type' => $contentType,
      ]));

      // Redirect to field mappings list
      $form_state->setRedirect('rating_scorer.field_mappings');
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error creating field mapping: @error', [
        '@error' => $e->getMessage(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handled by individual step submit handlers
  }

}
