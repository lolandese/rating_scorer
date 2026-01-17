<?php

namespace Drupal\rating_scorer\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drush commands for Rating Scorer.
 */
class RatingScorerCommands extends DrushCommands {

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
   * Constructs the Drush commands object.
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
    parent::__construct();
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Auto-create Rating Scorer mappings for all Fivestar fields.
   */
  #[CLI\Command(name: 'rating_scorer:auto-map', description: 'Automatically creates Rating Scorer field mappings for all Fivestar fields that have corresponding rating_score fields.')]
  #[CLI\Argument(name: 'dry-run', description: 'If specified, show what would be done without actually creating mappings.', required: FALSE)]
  #[CLI\Usage(name: 'rating_scorer:auto-map', description: 'Auto-create mappings for all Fivestar fields.')]
  public function autoMap($dry_run = FALSE): void {
    $created = 0;
    $skipped = 0;

    // Get all field storage configs.
    $field_storages = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->loadMultiple();

    foreach ($field_storages as $field_storage) {
      if ($field_storage->getType() !== 'fivestar') {
        continue;
      }

      $entity_type = $field_storage->getTargetEntityTypeId();
      $field_name = $field_storage->getName();

      // Only support node bundles for now.
      if ($entity_type !== 'node') {
        $skipped++;
        continue;
      }

      // Find all bundles that have this Fivestar field.
      $field_config_storage = $this->entityTypeManager->getStorage('field_config');
      $field_configs = $field_config_storage->loadByProperties([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
      ]);

      foreach ($field_configs as $field_config) {
        $bundle = $field_config->getTargetBundle();

        // Check if bundle has a rating_score field.
        $field_manager = \Drupal::service('entity_field.manager');
        $fields = $field_manager->getFieldDefinitions($entity_type, $bundle);
        $has_score_field = FALSE;
        foreach ($fields as $field_def) {
          if ($field_def->getType() === 'rating_score') {
            $has_score_field = TRUE;
            break;
          }
        }

        if (!$has_score_field) {
          $skipped++;
          continue;
        }

        // Create or update the mapping entity.
        $config_id = "{$entity_type}_{$bundle}";
        $mapping_storage = $this->entityTypeManager
          ->getStorage('rating_scorer_field_mapping');
        $mapping_entity = $mapping_storage->load($config_id);

        if ($mapping_entity) {
          $skipped++;
          continue;
        }

        // Create the mapping entity with VotingAPI as source.
        $mapping_entity = $mapping_storage->create([
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

        if ($dry_run) {
          $this->io()->writeln("  [DRY RUN] Would create mapping: <info>$config_id</info> (Fivestar field: <info>$field_name</info>)");
        } else {
          $mapping_entity->save();
          $this->io()->writeln("  ✅ Created mapping: <info>$config_id</info> (Fivestar field: <info>$field_name</info>)");
        }
        $created++;
      }
    }

    $this->io()->writeln("\n<options=bold>Summary:</>");
    $this->io()->writeln("  Created/Would create: <info>$created</info> mapping(s)");
    $this->io()->writeln("  Skipped: <options=bold;fg=yellow>$skipped</> (already exist or no rating_score field)");
  }

}
