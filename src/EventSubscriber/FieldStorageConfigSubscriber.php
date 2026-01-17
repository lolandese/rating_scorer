<?php

namespace Drupal\rating_scorer\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\Core\Entity\EntityTypeEvent;

/**
 * Event subscriber for field storage config changes.
 *
 * Automatically creates/deletes Rating Scorer mappings when Fivestar fields
 * are added or removed.
 */
class FieldStorageConfigSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'field_storage_config.insert' => 'onFieldStorageConfigInsert',
      'field_storage_config.delete' => 'onFieldStorageConfigDelete',
      'field_config.insert' => 'onFieldConfigInsert',
      'field_config.delete' => 'onFieldConfigDelete',
    ];
  }

  /**
   * Responds to field config insert.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *   The event.
   */
  public function onFieldConfigInsert(EntityTypeEvent $event): void {
    $field_config = $event->getEntity();
    
    // Get the field storage to check its type.
    try {
      $field_storage = \Drupal::entityTypeManager()
        ->getStorage('field_storage_config')
        ->load($field_config->getEntityType() . '.' . $field_config->getName());
      
      if ($field_storage && $field_storage->getType() === 'fivestar') {
        $this->createMappingForField($field_storage);
        \Drupal::logger('rating_scorer')->info(
          'Auto-created Rating Scorer mapping for Fivestar field @field on @entity_type entities',
          [
            '@field' => $field_storage->getName(),
            '@entity_type' => $field_storage->getTargetEntityTypeId(),
          ]
        );
      }
    } catch (\Exception $e) {
      \Drupal::logger('rating_scorer')->error(
        'Error during field config insert: @message',
        ['@message' => $e->getMessage()]
      );
    }
  }

  /**
   * Responds to field config delete.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *   The event.
   */
  public function onFieldConfigDelete(EntityTypeEvent $event): void {
    $field_config = $event->getEntity();
    
    // Get the field storage to check its type.
    try {
      $field_storage = \Drupal::entityTypeManager()
        ->getStorage('field_storage_config')
        ->load($field_config->getEntityType() . '.' . $field_config->getName());
      
      if ($field_storage && $field_storage->getType() === 'fivestar') {
        $this->deleteMappingForField($field_storage);
        \Drupal::logger('rating_scorer')->info(
          'Auto-removed Rating Scorer mapping for deleted Fivestar field @field',
          ['@field' => $field_storage->getName()]
        );
      }
    } catch (\Exception $e) {
      \Drupal::logger('rating_scorer')->error(
        'Error during field config delete: @message',
        ['@message' => $e->getMessage()]
      );
    }
  }

  /**
   * Responds to field storage config insert.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *   The event.
   */
  public function onFieldStorageConfigInsert(EntityTypeEvent $event): void {
    $field_storage = $event->getEntity();

    if (!($field_storage instanceof FieldStorageConfigInterface)) {
      return;
    }

    if ($field_storage->getType() !== 'fivestar') {
      return;
    }

    try {
      $this->createMappingForField($field_storage);
      \Drupal::logger('rating_scorer')->info(
        'Auto-created Rating Scorer mapping for Fivestar field @field on @entity_type entities',
        [
          '@field' => $field_storage->getName(),
          '@entity_type' => $field_storage->getTargetEntityTypeId(),
        ]
      );
    } catch (\Exception $e) {
      \Drupal::logger('rating_scorer')->error(
        'Failed to auto-create mapping for Fivestar field @field: @message',
        [
          '@field' => $field_storage->getName(),
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

  /**
   * Responds to field storage config delete.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *   The event.
   */
  public function onFieldStorageConfigDelete(EntityTypeEvent $event): void {
    $field_storage = $event->getEntity();

    if (!($field_storage instanceof FieldStorageConfigInterface)) {
      return;
    }

    if ($field_storage->getType() !== 'fivestar') {
      return;
    }

    try {
      $this->deleteMappingForField($field_storage);
      \Drupal::logger('rating_scorer')->info(
        'Auto-removed Rating Scorer mapping for deleted Fivestar field @field',
        ['@field' => $field_storage->getName()]
      );
    } catch (\Exception $e) {
      \Drupal::logger('rating_scorer')->error(
        'Failed to remove mapping for deleted Fivestar field @field: @message',
        [
          '@field' => $field_storage->getName(),
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

  /**
   * Create a mapping for a Fivestar field.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   The field storage config.
   *
   * @throws \Exception
   *   If mapping cannot be created.
   */
  protected function createMappingForField(FieldStorageConfigInterface $field_storage): void {
    $entity_type = $field_storage->getTargetEntityTypeId();
    $field_name = $field_storage->getName();

    // Only support node bundles for now.
    if ($entity_type !== 'node') {
      return;
    }

    $entity_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = $entity_bundle_info->getBundleInfo($entity_type);
    $field_manager = \Drupal::service('entity_field.manager');

    // Try to detect which bundle(s) have this field by checking field instances.
    $field_storage_entity = \Drupal::entityTypeManager()->getStorage('field_config');
    $field_configs = $field_storage_entity->loadByProperties([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
    ]);

    if (empty($field_configs)) {
      return;
    }

    foreach ($field_configs as $field_config) {
      $bundle = $field_config->getTargetBundle();

      if (!isset($bundles[$bundle])) {
        continue;
      }

      // Check if bundle has a rating_score field.
      $fields = $field_manager->getFieldDefinitions($entity_type, $bundle);
      $has_score_field = FALSE;
      foreach ($fields as $field_def) {
        if ($field_def->getType() === 'rating_score') {
          $has_score_field = TRUE;
          break;
        }
      }

      if (!$has_score_field) {
        continue;
      }

      // Check if mapping entity already exists.
      $config_id = "{$entity_type}_{$bundle}";
      $mapping_entity = \Drupal::entityTypeManager()
        ->getStorage('rating_scorer_field_mapping')
        ->load($config_id);

      if ($mapping_entity) {
        continue;  // Mapping already exists.
      }

      // Create the mapping entity with VotingAPI as source.
      $mapping_entity = \Drupal::entityTypeManager()
        ->getStorage('rating_scorer_field_mapping')
        ->create([
          'id' => $config_id,
          'label' => ucfirst($bundle) . ' - ' . $field_name,
          'content_type' => $bundle,
          'source_type' => 'VOTINGAPI',
          'number_of_ratings_field' => '',
          'average_rating_field' => '',
          'vote_field' => $field_name,
          'scoring_method' => 'bayesian',
          'bayesian_threshold' => 10,
        ]);

      $mapping_entity->save();
    }
  }

  /**
   * Delete a mapping for a deleted Fivestar field.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   The field storage config.
   *
   * @throws \Exception
   *   If mapping cannot be deleted.
   */
  protected function deleteMappingForField(FieldStorageConfigInterface $field_storage): void {
    $entity_type = $field_storage->getTargetEntityTypeId();
    $field_name = $field_storage->getName();

    // Only support node bundles for now.
    if ($entity_type !== 'node') {
      return;
    }

    $entity_bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = $entity_bundle_info->getBundleInfo($entity_type);

    $config = $this->configFactory->getEditable('rating_scorer.settings');
    $field_mappings = $config->get('field_mappings') ?? [];
    $changed = FALSE;

    foreach (array_keys($bundles) as $bundle) {
      $config_id = "{$entity_type}_{$bundle}";

      if (empty($field_mappings[$config_id])) {
        continue;
      }

      $mapping_data = $field_mappings[$config_id];
      if (is_string($mapping_data)) {
        $mapping_data = json_decode($mapping_data, TRUE);
      }

      // Remove if this mapping uses the deleted field.
      if (!empty($mapping_data['vote_field']) && $mapping_data['vote_field'] === $field_name) {
        unset($field_mappings[$config_id]);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $config->set('field_mappings', $field_mappings)->save();
    }
  }

}
