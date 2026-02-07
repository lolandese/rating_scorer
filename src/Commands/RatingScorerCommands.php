<?php

namespace Drupal\rating_scorer\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drush\Commands\DrushCommands;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected FieldTypePluginManagerInterface $fieldTypeManager;

  /**
   * Constructs the Drush commands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    ModuleHandlerInterface $module_handler,
    FieldTypePluginManagerInterface $field_type_manager
  ) {
    parent::__construct();
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * Auto-create Rating Scorer mappings for all Fivestar fields.
   *
   * @command rating_scorer:auto-map
   * @description Automatically creates Rating Scorer field mappings for all Fivestar fields that have corresponding rating_score fields.
   * @argument dry-run If specified, show what would be done without actually creating mappings.
   * @usage rating_scorer:auto-map Auto-create mappings for all Fivestar fields.
   * @usage rating_scorer:auto-map dry-run Show what would be created without making changes.
   */
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

  /**
   * Test command for Rating Scorer.
   *
   * @command rating_scorer:test
   * @description Test command to verify Rating Scorer Drush commands are working.
   * @usage rating_scorer:test Test the command registration.
   */
  public function test(): void {
    $this->io()->writeln("Rating Scorer Drush commands are working!");
  }

  /**
   * List all Rating Scorer field mappings.
   *
   * @command rating_scorer:list-mappings
   * @description Display all configured Rating Scorer field mappings with their settings.
   * @usage rating_scorer:list-mappings Show all field mappings.
   */
  public function listMappings(): void {
    $mapping_storage = $this->entityTypeManager->getStorage('rating_scorer_field_mapping');
    $mappings = $mapping_storage->loadMultiple();

    if (empty($mappings)) {
      $this->io()->writeln("<comment>No field mappings found.</comment>");
      return;
    }

    $this->io()->writeln("<info>Rating Scorer Field Mappings:</info>");
    $this->io()->writeln("");

    foreach ($mappings as $mapping) {
      $this->io()->writeln("📝 <options=bold>{$mapping->label()}</> ({$mapping->id()})");
      $this->io()->writeln("   Content Type: <info>{$mapping->get('content_type')}</info>");
      $this->io()->writeln("   Source Type: <info>{$mapping->get('source_type')}</info>");
      $this->io()->writeln("   Algorithm: <info>{$mapping->get('scoring_method')}</info>");

      if ($mapping->get('source_type') === 'VOTINGAPI') {
        $this->io()->writeln("   Vote Field: <info>{$mapping->get('vote_field')}</info>");
      } else {
        $this->io()->writeln("   Rating Field: <info>{$mapping->get('average_rating_field')}</info>");
        $this->io()->writeln("   Count Field: <info>{$mapping->get('number_of_ratings_field')}</info>");
      }

      if ($mapping->get('scoring_method') === 'bayesian') {
        $this->io()->writeln("   Threshold: <info>{$mapping->get('bayesian_threshold')}</info>");
      }
      $this->io()->writeln("");
    }

    $this->io()->writeln("<options=bold>Total: " . count($mappings) . " mapping(s)</>");
  }

  /**
   * Create a new Rating Scorer field mapping.
   *
   * @command rating_scorer:create-mapping
   * @description Create a new field mapping for Rating Scorer.
   * @argument content-type The content type machine name (e.g., 'article').
   * @option source-type Data source type: 'FIELD' or 'VOTINGAPI'. Defaults to 'VOTINGAPI'.
   * @option algorithm Scoring algorithm: 'weighted', 'bayesian', or 'wilson'. Defaults to 'bayesian'.
   * @option vote-field Field name for VotingAPI source (required if source-type is VOTINGAPI).
   * @option rating-field Field name containing average rating (required if source-type is FIELD).
   * @option count-field Field name containing rating count (required if source-type is FIELD).
   * @option threshold Bayesian threshold value. Defaults to 10.
   * @usage rating_scorer:create-mapping article --vote-field=field_rating Create mapping for article content type using VotingAPI.
   * @usage rating_scorer:create-mapping product --source-type=FIELD --rating-field=field_avg_rating --count-field=field_rating_count Create mapping using direct field sources.
   */
  public function createMapping($content_type, $options = [
    'source-type' => 'VOTINGAPI',
    'algorithm' => 'bayesian',
    'vote-field' => NULL,
    'rating-field' => NULL,
    'count-field' => NULL,
    'threshold' => 10,
  ]): void {
    if (empty($content_type)) {
      $this->io()->error("Content type is required.");
      return;
    }

    $mapping_id = "node_" . $content_type;
    $mapping_storage = $this->entityTypeManager->getStorage('rating_scorer_field_mapping');

    // Check if mapping already exists.
    if ($mapping_storage->load($mapping_id)) {
      $this->io()->error("Field mapping for content type '{$content_type}' already exists.");
      return;
    }

    // Validate required fields based on source type.
    if ($options['source-type'] === 'VOTINGAPI' && empty($options['vote-field'])) {
      $this->io()->error("Vote field is required when using VOTINGAPI source type.");
      return;
    }

    if ($options['source-type'] === 'FIELD') {
      if (empty($options['rating-field']) || empty($options['count-field'])) {
        $this->io()->error("Rating field and count field are required when using FIELD source type.");
        return;
      }
    }

    // Create the mapping.
    $mapping_data = [
      'id' => $mapping_id,
      'label' => ucfirst($content_type) . ' Rating Score Mapping',
      'content_type' => $content_type,
      'source_type' => $options['source-type'],
      'scoring_method' => $options['algorithm'],
      'bayesian_threshold' => (int) $options['threshold'],
    ];

    if ($options['source-type'] === 'VOTINGAPI') {
      $mapping_data['vote_field'] = $options['vote-field'];
      $mapping_data['number_of_ratings_field'] = '';
      $mapping_data['average_rating_field'] = '';
    } else {
      $mapping_data['vote_field'] = '';
      $mapping_data['number_of_ratings_field'] = $options['count-field'];
      $mapping_data['average_rating_field'] = $options['rating-field'];
    }

    $mapping = $mapping_storage->create($mapping_data);
    $mapping->save();

    $this->io()->success("Created field mapping for content type '{$content_type}' (ID: {$mapping_id})");
  }

  /**
   * Delete a Rating Scorer field mapping.
   *
   * @command rating_scorer:delete-mapping
   * @description Delete an existing Rating Scorer field mapping.
   * @argument mapping-id The mapping ID to delete (e.g., 'node_article').
   * @option force Skip confirmation prompt.
   * @usage rating_scorer:delete-mapping node_article Delete the article content type mapping.
   * @usage rating_scorer:delete-mapping node_product --force Delete without confirmation.
   */
  public function deleteMapping($mapping_id, $options = ['force' => FALSE]): void {
    if (empty($mapping_id)) {
      $this->io()->error("Mapping ID is required.");
      return;
    }

    $mapping_storage = $this->entityTypeManager->getStorage('rating_scorer_field_mapping');
    $mapping = $mapping_storage->load($mapping_id);

    if (!$mapping) {
      $this->io()->error("Field mapping '{$mapping_id}' not found.");
      return;
    }

    if (!$options['force']) {
      $confirm = $this->io()->confirm(
        "Are you sure you want to delete the field mapping '{$mapping->label()}' ({$mapping_id})?",
        FALSE
      );

      if (!$confirm) {
        $this->io()->writeln("Operation cancelled.");
        return;
      }
    }

    $mapping->delete();
    $this->io()->success("Deleted field mapping '{$mapping_id}'.");
  }

  /**
   * Recalculate rating scores for content.
   *
   * @command rating_scorer:recalculate
   * @description Recalculate rating scores for all content or a specific content type.
   * @argument content-type Optional: specific content type to recalculate (e.g., 'article').
   * @option limit Maximum number of entities to process. Defaults to 0 (no limit).
   * @option batch-size Number of entities to process per batch. Defaults to 50.
   * @usage rating_scorer:recalculate Recalculate all rating scores.
   * @usage rating_scorer:recalculate article Recalculate scores only for article content type.
   * @usage rating_scorer:recalculate --limit=100 --batch-size=25 Process maximum 100 entities in batches of 25.
   */
  public function recalculate($content_type = NULL, $options = [
    'limit' => 0,
    'batch-size' => 50,
  ]): void {
    $calculator = \Drupal::service('rating_scorer.calculator');
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Build query.
    $query = $node_storage->getQuery()->accessCheck(FALSE);

    if ($content_type) {
      $query->condition('type', $content_type);
    }

    if ($options['limit'] > 0) {
      $query->range(0, $options['limit']);
    }

    $nids = $query->execute();
    $total = count($nids);

    if ($total === 0) {
      $this->io()->writeln("<comment>No entities found to recalculate.</comment>");
      return;
    }

    $this->io()->writeln("<info>Processing {$total} entities...</info>");

    $batch_size = max(1, (int) $options['batch-size']);
    $batches = array_chunk($nids, $batch_size);
    $processed = 0;

    foreach ($batches as $batch_index => $batch_nids) {
      $nodes = $node_storage->loadMultiple($batch_nids);

      foreach ($nodes as $node) {
        try {
          $calculator->updateScoreFieldsOnEntity($node);
          $node->save();
          $processed++;
        } catch (\Exception $e) {
          $this->io()->writeln("<error>Error processing node {$node->id()}: {$e->getMessage()}</error>");
        }
      }

      // Show progress.
      $progress = $processed;
      $this->io()->writeln("  Processed batch " . ($batch_index + 1) . "/" . count($batches) . " ({$progress}/{$total})");
    }

    $this->io()->success("Recalculated rating scores for {$processed} entities.");
  }

  /**
   * Show Rating Scorer module status and health metrics.
   *
   * @command rating_scorer:status
   * @description Display module status, field mapping health, and coverage statistics.
   * @usage rating_scorer:status Show complete module status.
   */
  public function status(): void {
    $this->io()->writeln("<info>📊 Rating Scorer Module Status</info>");
    $this->io()->writeln("");

    // Module info.
    $module_handler = \Drupal::service('module_handler');
    $this->io()->writeln("<options=bold>Module Information:</>");
    $this->io()->writeln("  Status: " . ($module_handler->moduleExists('rating_scorer') ? "<info>✅ Enabled</info>" : "<error>❌ Disabled</error>"));

    // Field mappings status.
    $mapping_storage = $this->entityTypeManager->getStorage('rating_scorer_field_mapping');
    $mappings = $mapping_storage->loadMultiple();
    $mapping_count = count($mappings);

    $this->io()->writeln("");
    $this->io()->writeln("<options=bold>Field Mappings:</>");
    $this->io()->writeln("  Total Configured: <info>{$mapping_count}</info>");

    if ($mapping_count > 0) {
      $source_types = [];
      $algorithms = [];

      foreach ($mappings as $mapping) {
        $source_types[$mapping->get('source_type')] = ($source_types[$mapping->get('source_type')] ?? 0) + 1;
        $algorithms[$mapping->get('scoring_method')] = ($algorithms[$mapping->get('scoring_method')] ?? 0) + 1;
      }

      $this->io()->writeln("  Source Types:");
      foreach ($source_types as $type => $count) {
        $this->io()->writeln("    {$type}: <info>{$count}</info>");
      }

      $this->io()->writeln("  Algorithms:");
      foreach ($algorithms as $algorithm => $count) {
        $this->io()->writeln("    {$algorithm}: <info>{$count}</info>");
      }
    }

    // Content coverage.
    $this->io()->writeln("");
    $this->io()->writeln("<options=bold>Content Coverage:</>");

    $node_storage = $this->entityTypeManager->getStorage('node');
    $total_nodes = $node_storage->getQuery()->accessCheck(FALSE)->count()->execute();
    $this->io()->writeln("  Total Content: <info>{$total_nodes}</info>");

    if ($mapping_count > 0) {
      $covered_nodes = 0;
      foreach ($mappings as $mapping) {
        $content_type = $mapping->get('content_type');
        $type_count = $node_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', $content_type)
          ->count()
          ->execute();
        $covered_nodes += $type_count;
        $this->io()->writeln("    {$content_type}: <info>{$type_count}</info>");
      }

      $coverage_percent = $total_nodes > 0 ? round(($covered_nodes / $total_nodes) * 100, 1) : 0;
      $this->io()->writeln("  Coverage: <info>{$coverage_percent}%</info> ({$covered_nodes}/{$total_nodes})");
    } else {
      $this->io()->writeln("  <comment>No content types configured for rating scoring.</comment>");
    }

    // Configuration status.
    $config = $this->configFactory->get('rating_scorer.settings');
    $this->io()->writeln("");
    $this->io()->writeln("<options=bold>Configuration:</>");
    $this->io()->writeln("  Default Threshold: <info>{$config->get('default_minimum_ratings')}</info>");
    $this->io()->writeln("  Bayesian Average: <info>{$config->get('bayesian_assumed_average')}</info>");

    // Integration status.
    $this->io()->writeln("");
    $this->io()->writeln("<options=bold>Module Integrations:</>");
    $integrations = [
      'votingapi' => 'VotingAPI',
      'fivestar' => 'Fivestar',
      'rate' => 'Rate',
    ];

    foreach ($integrations as $module_name => $display_name) {
      $status = $module_handler->moduleExists($module_name) ? "<info>✅ Enabled</info>" : "<comment>➖ Not installed</comment>";
      $this->io()->writeln("  {$display_name}: {$status}");
    }
  }

}
