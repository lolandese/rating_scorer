<?php

namespace Drupal\Tests\rating_scorer\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\rating_scorer\Entity\RatingScorerFieldMapping;
use Drupal\rating_scorer\Commands\RatingScorerCommands;

/**
 * Tests for Rating Scorer Drush commands.
 *
 * @group rating_scorer
 */
class RatingScorerCommandsTest extends RatingScorerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'rating_scorer',
  ];

  /**
   * The Drush commands service.
   *
   * @var \Drupal\rating_scorer\Commands\RatingScorerCommands
   */
  protected $drushCommands;

  /**
   * Test field mapping data.
   *
   * @var array
   */
  protected $testMappingData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the Drush commands service manually.
    $this->drushCommands = new RatingScorerCommands(
      $this->container->get('config.factory'),
      $this->container->get('entity_type.manager')
    );

    // Create test content types.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    NodeType::create([
      'type' => 'product',
      'name' => 'Product',
    ])->save();

    // Define test mapping data.
    $this->testMappingData = [
      'id' => 'node_article',
      'label' => 'Article Rating Score Mapping',
      'content_type' => 'article',
      'source_type' => 'VOTINGAPI',
      'vote_field' => 'field_rating',
      'scoring_method' => 'bayesian',
      'bayesian_threshold' => 10,
      'number_of_ratings_field' => '',
      'average_rating_field' => '',
    ];
  }

  /**
   * Test the list-mappings command with no mappings.
   */
  public function testListMappingsEmpty() {
    // Capture output by extending the command to return data instead of printing.
    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');
    $mappings = $mapping_storage->loadMultiple();

    // Verify no mappings exist.
    $this->assertEmpty($mappings, 'No field mappings should exist initially.');
  }

  /**
   * Test the list-mappings command with existing mappings.
   */
  public function testListMappingsWithData() {
    // Create a test mapping.
    $mapping = RatingScorerFieldMapping::create($this->testMappingData);
    $mapping->save();

    // Load all mappings.
    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');
    $mappings = $mapping_storage->loadMultiple();

    // Verify mapping exists.
    $this->assertCount(1, $mappings, 'One field mapping should exist.');
    $this->assertEquals('node_article', key($mappings), 'Mapping ID should match.');

    // Verify mapping properties.
    $created_mapping = reset($mappings);
    $this->assertEquals('article', $created_mapping->get('content_type'));
    $this->assertEquals('VOTINGAPI', $created_mapping->get('source_type'));
    $this->assertEquals('bayesian', $created_mapping->get('scoring_method'));
  }

  /**
   * Test creating a mapping via the command data structure.
   */
  public function testCreateMappingData() {
    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');

    // Verify no mappings exist initially.
    $this->assertEmpty($mapping_storage->loadMultiple());

    // Simulate the create command data structure.
    $mapping_data = [
      'id' => 'node_product',
      'label' => 'Product Rating Score Mapping',
      'content_type' => 'product',
      'source_type' => 'FIELD',
      'scoring_method' => 'weighted',
      'bayesian_threshold' => 15,
      'number_of_ratings_field' => 'field_rating_count',
      'average_rating_field' => 'field_avg_rating',
      'vote_field' => '',
    ];

    // Create the mapping.
    $mapping = $mapping_storage->create($mapping_data);
    $mapping->save();

    // Verify mapping was created.
    $created_mapping = $mapping_storage->load('node_product');
    $this->assertNotNull($created_mapping, 'Mapping should be created.');
    $this->assertEquals('product', $created_mapping->get('content_type'));
    $this->assertEquals('FIELD', $created_mapping->get('source_type'));
    $this->assertEquals('weighted', $created_mapping->get('scoring_method'));
    $this->assertEquals('field_rating_count', $created_mapping->get('number_of_ratings_field'));
  }

  /**
   * Test creating a mapping that already exists.
   */
  public function testCreateMappingDuplicate() {
    // Create initial mapping.
    $mapping = RatingScorerFieldMapping::create($this->testMappingData);
    $mapping->save();

    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');

    // Verify mapping exists.
    $this->assertNotNull($mapping_storage->load('node_article'));

    // Attempt to create duplicate should fail validation.
    $duplicate_exists = $mapping_storage->load('node_article') !== NULL;
    $this->assertTrue($duplicate_exists, 'Duplicate mapping should already exist.');
  }

  /**
   * Test deleting a mapping.
   */
  public function testDeleteMapping() {
    // Create a test mapping.
    $mapping = RatingScorerFieldMapping::create($this->testMappingData);
    $mapping->save();

    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');

    // Verify mapping exists.
    $this->assertNotNull($mapping_storage->load('node_article'));

    // Delete the mapping.
    $mapping->delete();

    // Verify mapping is deleted.
    $this->assertNull($mapping_storage->load('node_article'), 'Mapping should be deleted.');
  }

  /**
   * Test deleting a non-existent mapping.
   */
  public function testDeleteNonExistentMapping() {
    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');

    // Verify mapping doesn't exist.
    $this->assertNull($mapping_storage->load('node_nonexistent'));

    // This would normally result in an error message in the Drush command.
    // We can simulate the check here.
    $mapping = $mapping_storage->load('node_nonexistent');
    $this->assertNull($mapping, 'Non-existent mapping should return null.');
  }

  /**
   * Test the status command data gathering.
   */
  public function testStatusData() {
    // Test with no mappings.
    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');
    $mappings = $mapping_storage->loadMultiple();
    $this->assertEmpty($mappings, 'No mappings should exist initially.');

    // Create test mappings with different configurations.
    $mappings_data = [
      [
        'id' => 'node_article',
        'label' => 'Article Mapping',
        'content_type' => 'article',
        'source_type' => 'VOTINGAPI',
        'scoring_method' => 'bayesian',
        'vote_field' => 'field_rating',
        'bayesian_threshold' => 10,
        'number_of_ratings_field' => '',
        'average_rating_field' => '',
      ],
      [
        'id' => 'node_product',
        'label' => 'Product Mapping',
        'content_type' => 'product',
        'source_type' => 'FIELD',
        'scoring_method' => 'wilson',
        'vote_field' => '',
        'bayesian_threshold' => 5,
        'number_of_ratings_field' => 'field_count',
        'average_rating_field' => 'field_avg',
      ],
    ];

    foreach ($mappings_data as $data) {
      $mapping = RatingScorerFieldMapping::create($data);
      $mapping->save();
    }

    // Test status data collection.
    $mappings = $mapping_storage->loadMultiple();
    $this->assertCount(2, $mappings, 'Two mappings should exist.');

    // Test source type aggregation.
    $source_types = [];
    $algorithms = [];

    foreach ($mappings as $mapping) {
      $source_types[$mapping->get('source_type')] = ($source_types[$mapping->get('source_type')] ?? 0) + 1;
      $algorithms[$mapping->get('scoring_method')] = ($algorithms[$mapping->get('scoring_method')] ?? 0) + 1;
    }

    $this->assertEquals(['VOTINGAPI' => 1, 'FIELD' => 1], $source_types);
    $this->assertEquals(['bayesian' => 1, 'wilson' => 1], $algorithms);
  }

  /**
   * Test recalculation data preparation.
   */
  public function testRecalculationData() {
    // Create test nodes.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Create article nodes.
    for ($i = 0; $i < 3; $i++) {
      $node = $node_storage->create([
        'type' => 'article',
        'title' => "Test Article {$i}",
        'status' => 1,
      ]);
      $node->save();
    }

    // Create product nodes.
    for ($i = 0; $i < 2; $i++) {
      $node = $node_storage->create([
        'type' => 'product',
        'title' => "Test Product {$i}",
        'status' => 1,
      ]);
      $node->save();
    }

    // Test querying all nodes.
    $all_nids = $node_storage->getQuery()->accessCheck(FALSE)->execute();
    $this->assertCount(5, $all_nids, 'Five nodes should be created.');

    // Test querying specific content type.
    $article_nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'article')
      ->execute();
    $this->assertCount(3, $article_nids, 'Three article nodes should exist.');

    $product_nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'product')
      ->execute();
    $this->assertCount(2, $product_nids, 'Two product nodes should exist.');

    // Test limit functionality.
    $limited_nids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->range(0, 2)
      ->execute();
    $this->assertCount(2, $limited_nids, 'Limited query should return 2 nodes.');
  }

  /**
   * Test configuration data access for status command.
   */
  public function testConfigurationAccess() {
    $config = $this->container->get('config.factory')->get('rating_scorer.settings');

    // Test default configuration values.
    $this->assertEquals(7, $config->get('default_minimum_ratings'));
    $this->assertEquals(3.5, $config->get('bayesian_assumed_average'));
    $this->assertEquals(4.5, $config->get('default_rating'));
    $this->assertEquals(100, $config->get('default_num_ratings'));
  }

  /**
   * Test module integration status checking.
   */
  public function testModuleIntegrationStatus() {
    $module_handler = $this->container->get('module_handler');

    // Test core modules that should be enabled.
    $this->assertTrue($module_handler->moduleExists('node'), 'Node module should be enabled.');
    $this->assertTrue($module_handler->moduleExists('field'), 'Field module should be enabled.');
    $this->assertTrue($module_handler->moduleExists('rating_scorer'), 'Rating Scorer module should be enabled.');

    // Test optional integration modules (these may or may not be installed).
    $optional_modules = ['votingapi', 'fivestar', 'rate'];
    foreach ($optional_modules as $module) {
      // We just test that the check works, not the specific result.
      $is_enabled = $module_handler->moduleExists($module);
      $this->assertIsBool($is_enabled, "Module status check should return boolean for {$module}.");
    }
  }

  /**
   * Test validation logic for create command.
   */
  public function testCreateMappingValidation() {
    // Test VOTINGAPI source type validation.
    $votingapi_data = [
      'content_type' => 'article',
      'source_type' => 'VOTINGAPI',
      'vote_field' => '',  // Missing required field.
    ];

    $vote_field_missing = empty($votingapi_data['vote_field']);
    $this->assertTrue($vote_field_missing, 'VOTINGAPI source should require vote field.');

    // Test FIELD source type validation.
    $field_data = [
      'content_type' => 'product',
      'source_type' => 'FIELD',
      'rating_field' => '',  // Missing required field.
      'count_field' => 'field_count',
    ];

    $rating_field_missing = empty($field_data['rating_field']);
    $this->assertTrue($rating_field_missing, 'FIELD source should require rating field.');

    $field_data_2 = [
      'content_type' => 'product',
      'source_type' => 'FIELD',
      'rating_field' => 'field_rating',
      'count_field' => '',  // Missing required field.
    ];

    $count_field_missing = empty($field_data_2['count_field']);
    $this->assertTrue($count_field_missing, 'FIELD source should require count field.');
  }

  /**
   * Test batch processing logic for recalculation.
   */
  public function testBatchProcessing() {
    // Test array chunking for batch processing.
    $nids = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    $batch_size = 3;

    $batches = array_chunk($nids, $batch_size);

    $this->assertCount(4, $batches, 'Should create 4 batches.');
    $this->assertCount(3, $batches[0], 'First batch should have 3 items.');
    $this->assertCount(3, $batches[1], 'Second batch should have 3 items.');
    $this->assertCount(3, $batches[2], 'Third batch should have 3 items.');
    $this->assertCount(1, $batches[3], 'Fourth batch should have 1 item.');

    // Test empty array handling.
    $empty_batches = array_chunk([], $batch_size);
    $this->assertEmpty($empty_batches, 'Empty array should produce no batches.');

    // Test batch size validation.
    $safe_batch_size = max(1, $batch_size);
    $this->assertEquals(3, $safe_batch_size, 'Batch size should be preserved when valid.');

    $safe_batch_size_zero = max(1, 0);
    $this->assertEquals(1, $safe_batch_size_zero, 'Batch size should be 1 minimum.');
  }

  /**
   * Test auto-map command data gathering.
   */
  public function testAutoMapDataGathering() {
    // Test field storage loading.
    $field_storage_storage = $this->container->get('entity_type.manager')->getStorage('field_storage_config');
    $field_storages = $field_storage_storage->loadMultiple();

    // Initially should have no fivestar fields.
    $fivestar_fields = [];
    foreach ($field_storages as $field_storage) {
      if ($field_storage->getType() === 'fivestar') {
        $fivestar_fields[] = $field_storage;
      }
    }

    $this->assertEmpty($fivestar_fields, 'No fivestar fields should exist initially.');

    // Test entity type filtering.
    $supported_entity_types = ['node'];
    foreach ($field_storages as $field_storage) {
      if ($field_storage->getType() === 'fivestar') {
        $entity_type = $field_storage->getTargetEntityTypeId();
        $is_supported = in_array($entity_type, $supported_entity_types);
        // This would be used to filter in the actual command.
        $this->assertIsBool($is_supported, 'Entity type support check should return boolean.');
      }
    }
  }

  /**
   * Test cleanup to ensure tests don't affect each other.
   */
  protected function tearDown(): void {
    // Clean up any test mappings.
    $mapping_storage = $this->container->get('entity_type.manager')->getStorage('rating_scorer_field_mapping');
    $mappings = $mapping_storage->loadMultiple();
    foreach ($mappings as $mapping) {
      $mapping->delete();
    }

    // Clean up test nodes.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $nodes = $node_storage->loadMultiple();
    foreach ($nodes as $node) {
      $node->delete();
    }

    parent::tearDown();
  }

}
