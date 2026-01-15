# Testing Guide - Rating Scorer Module

This document describes the test suite for the Rating Scorer module, including test organization, coverage, and guidelines for running and contributing tests.

## Test Overview

The module includes comprehensive tests covering:
- **Algorithm Tests** (22 tests) - Validation of scoring calculations (Weighted, Bayesian, Wilson)
- **Field Type Tests** (6 tests) - Computed field structure and configuration
- **Field Mapping Tests** (3 tests) - Configuration entity validation
- **Calculator Service Tests** (4 tests) - Score calculation service functionality
- **Form Tests** (2 tests) - Settings and field mapping form validation
- **Controller Tests** (3 tests) - Admin page rendering
- **ListBuilder Tests** (3 tests) - Field mapping list display
- **Block Tests** (3 tests) - Calculator block functionality
- **Admin Interface Tests** (7 functional tests) - Routing, tabs, and UI elements
- **Recalculation Tests** (2 functional tests) - Auto-calculation on content and mapping changes

**Total: 55+ tests across 12 test files**

## Running Tests

### Prerequisites

Tests require Drupal to be set up with DDEV:

```bash
cd /home/martinus/ddev-projects/green
ddev start
```

### Run All Tests

```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'
```

### Run Specific Test File

Run only algorithm tests:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter RatingScorerAlgorithmsTest'
```

Run only field mapping tests:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter RatingScorerFieldMappingTest'
```

Run only admin interface functional tests:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter RatingScorerAdminInterfaceTest'
```

### Run Specific Test Method

Run a single algorithm test:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter testBayesianAverageBasic'
```

Run a single recalculation test:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter testFieldMappingSaveTriggersRecalculation'
```

## Test File Organization

```
tests/
├── src/
│   ├── Unit/
│   │   ├── RatingScorerAlgorithmsTest.php      (22 algorithm tests)
│   │   ├── RatingScoreFieldTypeTest.php        (6 field type tests)
│   │   ├── RatingScorerFieldMappingTest.php    (3 config entity tests)
│   │   ├── RatingScoreCalculatorTest.php       (4 service tests)
│   │   ├── RatingScorerFormTest.php            (2 form tests)
│   │   ├── RatingScorerControllerTest.php      (3 controller tests)
│   │   ├── RatingScorerListBuilderTest.php     (3 listbuilder tests)
│   │   ├── RatingScorerCalculatorBlockTest.php (3 block tests)
│   │   └── RatingScorerTest.php                (1 basic test)
│   └── Functional/
│       ├── RatingScorerAdminInterfaceTest.php  (7 functional tests)
│       ├── RatingScorerRecalculationTest.php   (2 recalculation tests)
│       └── RatingScorerFunctionalTest.php      (1 functional test)
```

## Test Coverage

### Algorithm Tests (`RatingScorerAlgorithmsTest.php`)

Tests for the three scoring algorithms used by the score calculation service.

#### Weighted Score Tests (5 tests)
Tests the logarithmic weighting algorithm: `score = average_rating * log(number_of_ratings + 1)`

- **testWeightedScoreBasic** - Validates calculation with 100 ratings at 4.0 average
- **testWeightedScoreSingleRating** - Tests edge case with 1 rating at 5.0 average
- **testWeightedScoreZeroRatings** - Verifies zero ratings returns 0 score
- **testWeightedScoreHighVolume** - Tests with 10,000 ratings
- **testWeightedScoreLowRatingHighVolume** - Combination of low rating and high volume

#### Bayesian Average Tests (6 tests)
Tests the IMDB-style Bayesian averaging: `(n*r + m*p) / (n + m)` where:
- n = number of ratings
- r = average rating
- m = minimum threshold
- p = prior rating (2.5)

- **testBayesianAverageBasic** - Basic calculation with 100 ratings at 4.0 average
- **testBayesianAverageSingleRating** - Single 5-star pulled down by prior (→ 2.92)
- **testBayesianAverageZeroRatings** - Zero ratings returns prior (2.5)
- **testBayesianAverageHighThreshold** - Large threshold pulls score toward prior
- **testBayesianAverageLowThreshold** - Small threshold keeps score near average
- **testBayesianPenalizesLowReviewItems** - Single 5-star scores lower than 100 4-star reviews

#### Bayesian Properties Tests (2 tests)
Tests mathematical properties of Bayesian algorithm.

- **testBayesianConvergence** - Verifies score converges to average rating as volume increases
  - Low volume (10 ratings) pulls toward prior
  - Medium volume (100 ratings) closer to average
  - High volume (1000 ratings) very close to average

#### Wilson Score Tests (6 tests)
Tests the confidence-interval-based Wilson score used by Reddit and others.

- **testWilsonScoreZeroRatings** - Zero ratings returns 0
- **testWilsonScoreBasic** - Basic calculation with 100 ratings at 4.0
- **testWilsonScoreSingleRating** - Single rating is heavily discounted
- **testWilsonScoreIsConservative** - High volume scores higher than low volume (more confidence)
- **testWilsonScoreNeverNegative** - Score never goes below 0 (safety check)
- **testWilsonScorePerfectRating** - Perfect 5.0 rating approaches 1.0
- **testWilsonScoreNeutralRating** - Neutral 2.5/5.0 rating approaches 0.5

#### Edge Case Tests (3 tests)
Tests boundary conditions and unusual inputs.

- **testInvalidMethodDefaultsToWeighted** - Unknown method falls back to weighted
- **testDecimalRatings** - Properly handles decimal average ratings (e.g., 3.7)
- **testBayesianLargeNumbers** - Handles millions of ratings without errors

### Field Type Tests (`RatingScoreFieldTypeTest.php`)

Tests the computed rating score field type implementation.

- **testRatingScoreFieldTypeExists** - Field type is registered
- **testRatingScoreFieldTypeHasCorrectConfiguration** - Configuration properties defined
- **testRatingScoreFieldTypeSchemaCorrect** - Database schema with proper decimal precision (10,2)
- **testRatingScoreWidgetExists** - Widget plugin is registered
- **testRatingScoreFormatterExists** - Formatter plugin is registered
- **testRatingScorePropertyDefinitions** - Field properties defined correctly

### Field Mapping Tests (`RatingScorerFieldMappingTest.php`)

Tests the configuration entity for per-content-type field mappings.

- **testRatingScorerFieldMappingEntityCreation** - Config entity can be created and saved
- **testRatingScorerFieldMappingEntityProperties** - Entity has required properties (bundle, number_of_ratings_field, average_rating_field, etc.)
- **testRatingScorerFieldMappingEntityConfiguration** - Entity configuration is properly stored

### Calculator Service Tests (`RatingScoreCalculatorTest.php`)

Tests the centralized score calculation service.

- **testRatingScoreCalculatorServiceExists** - Service is registered and callable
- **testCalculateScoreForEntity** - Method calculates scores for entities with field mappings
- **testUpdateScoreFieldsOnEntity** - Method updates all rating_score fields on entity
- **testRecalculationWithDifferentAlgorithms** - Service supports all three algorithms

### Form Tests (`RatingScorerFormTest.php`)

Tests form validation and configuration.

- **testSettingsFormHasClarifyingNote** - Defaults form displays clarifying note about calculator use
- **testSettingsFormHasCalculatorDefaultFields** - Form includes required calculator default fields

### Controller Tests (`RatingScorerControllerTest.php`)

Tests admin page rendering.

- **testFieldMappingsListMethodExists** - Controller method for field mappings list
- **testCalculatorMethodExists** - Controller method for calculator page
- **testControllerMethodsReturnRenderArrays** - Both methods return valid render arrays

### ListBuilder Tests (`RatingScorerListBuilderTest.php`)

Tests field mapping list builder customizations.

- **testListBuilderHasRenderMethod** - Render method is implemented
- **testListBuilderHasBuildHeaderMethod** - Build header method defines columns
- **testListBuilderHasBuildRowMethod** - Build row method formats each mapping row

### Block Tests (`RatingScorerCalculatorBlockTest.php`)

Tests the calculator block plugin.

- **testCalculatorBlockExists** - Block plugin is registered
- **testCalculatorBlockHasBuildMethod** - Build method is implemented
- **testCalculatorBlockExtendsBlockBase** - Proper inheritance from BlockBase

### Admin Interface Tests (`RatingScorerAdminInterfaceTest.php`)

Functional tests for admin UI routing and tabs.

- **testFieldMappingsTabAtParentRoute** - Field Mappings tab accessible at parent route
- **testCalculatorTabAccessible** - Calculator tab accessible
- **testDefaultsTabAccessible** - Defaults tab accessible
- **testAllTabsVisibleOnFieldMappingsPage** - All three tabs render on field mappings page
- **testAddFieldMappingLinkVisible** - "+ Add a field mapping" link displays
- **testSettingsFormHasNote** - Defaults form shows clarifying note
- **testCalculatorPageHasPurposeMessage** - Calculator page shows purpose explanation

### Recalculation Tests (`RatingScorerRecalculationTest.php`)

Functional tests for auto-recalculation behavior.

- **testFieldMappingSaveTriggersRecalculation** - Saving field mapping recalculates scores
- **testRecalculationMessageAfterSave** - User message appears after recalculation

## Test Structure

All tests use the appropriate base class for their scope:

### Unit Tests
Unit tests extend appropriate base classes:
- Form and service tests: `KernelTestBase` or direct class testing
- Algorithm and field tests: Can use `BrowserTestBase` for full access to Drupal bootstrap

### Functional Tests
Functional tests extend `BrowserTestBase` for full Drupal environment:

```php
class RatingScorerAdminInterfaceTest extends BrowserTestBase {
  protected $defaultTheme = 'stark';
  protected static $modules = [
    'rating_scorer',
    'field',
    'node',
  ];
}
```

This ensures:
- Database connection available
- Drupal bootstrap complete
- Module hooks initialized
- Admin UI accessible for testing

## Contributing New Tests

### When to Add Tests

- New algorithm behavior
- Bug fixes (add test reproducing bug first)
- Edge cases discovered in usage
- Performance optimizations
- Views integration changes

### Test Template

```php
/**
 * Test description (what behavior is being verified).
 */
public function testDescriptiveName() {
  // Arrange
  $input_value = someCalculation();

  // Act
  $result = _rating_scorer_calculate_score($number, $average, $method, $threshold);

  // Assert
  $this->assertGreaterThan(expected_min, $result);
  $this->assertLessThan(expected_max, $result);
}
```

### Best Practices

1. **One assertion per concept** - Test a single behavior
2. **Clear naming** - Method name should describe what's tested
3. **Use comments** - Document expected values and why
4. **Include edge cases** - Test boundaries and unusual inputs
5. **Group related tests** - Use test class organization
6. **Verify output ranges** - Use `assertGreaterThan`, `assertLessThan` for calculations with float precision

### Example: Adding a New Algorithm

1. Add test in `RatingScorerAlgorithmsTest.php`:

```php
public function testNewAlgorithmBasic() {
  $score = _rating_scorer_calculate_score(100, 4.0, 'new_method', 5);
  $this->assertGreaterThan(3, $score);
  $this->assertLessThan(5, $score);
}
```

2. Implement algorithm in `RatingScorerCalculator.php`:

```php
case 'new_method':
  return /* calculation */;
```

3. Run test:

```bash
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter testNewAlgorithmBasic'
```

### Example: Adding Field Mapping Tests

1. Create new test in `RatingScorerFieldMappingTest.php`:

```php
public function testNewMappingProperty() {
  $mapping = RatingScorerFieldMapping::create([
    'id' => 'test_mapping',
    'content_type' => 'article',
    'new_property' => 'test_value',
  ]);
  $this->assertEqual('test_value', $mapping->new_property);
}
```

2. Run test:

```bash
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter testNewMappingProperty'
```

## Test Coverage Summary

| Component | Tests | Coverage |
|-----------|-------|----------|
| Weighted Score Algorithm | 5 | Basic, edge cases, volume sensitivity |
| Bayesian Average Algorithm | 6 | Basic, thresholds, convergence, fairness |
| Wilson Score Algorithm | 6 | Basic, conservativeness, bounds |
| Algorithm Edge Cases | 3 | Invalid methods, decimals, large numbers |
| Field Type Plugin | 6 | Field registration, schema, widget, formatter |
| Configuration Entity | 3 | Entity creation, properties, persistence |
| Calculator Service | 4 | Service instantiation, calculation methods |
| Forms | 2 | Settings/defaults form validation |
| Controller | 3 | Admin page rendering |
| ListBuilder | 3 | List display and customization |
| Block | 3 | Block plugin structure and methods |
| Admin UI (Functional) | 7 | Routing, tabs, links, forms, messages |
| Recalculation (Functional) | 2 | Auto-recalculation on save |
| Basic Integration | 1 | Module functionality |
| **Total** | **55+** | **Comprehensive algorithm, plugin, service & UI validation** |

## Known Limitations

- **Functional tests** require full Drupal test database setup with SIMPLETEST_BASE_URL
- **Algorithm tests** use full Drupal bootstrap for access to service/hook system (could be optimized for unit-only tests)
- **No end-to-end content scoring tests** - could add tests that create actual content and verify scores are calculated

## Future Test Improvements

Potential areas for expansion:

1. **Auto-Calculation Tests**
   - Test calculation happens on entity presave
   - Test with missing field mappings
   - Test with invalid field configuration

2. **Permission Tests**
   - Verify only admins can access field mapping admin
   - Test access control on configuration pages

3. **Views Integration Tests**
   - Field sorting and filtering in Views
   - Field configuration in Views UI
   - Multiple scores on same display

4. **Performance Tests**
   - Large dataset handling (1000+ nodes)
   - Recalculation performance with many field mappings
   - Service efficiency benchmarks

5. **Edge Cases**
   - Missing number_of_ratings or average_rating fields
   - Field mapping for non-existent content type
   - Orphaned field mapping after content type deletion

## Troubleshooting

### Tests fail with "SIMPLETEST_BASE_URL not set"
Make sure environment variable is exported:
```bash
export SIMPLETEST_BASE_URL="http://web"
```

### Tests fail with database connection error
Verify DDEV is running:
```bash
ddev status
ddev start
```

### Specific test fails
Run with verbose output:
```bash
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit -v --configuration web/modules/custom/rating_scorer/phpunit.xml'
```

### Functional tests fail with "SIMPLETEST_BASE_URL environment variable" error
This is expected for functional tests that require full Drupal database. Ensure:
1. DDEV container is running: `ddev start`
2. Environment variable is set: `export SIMPLETEST_BASE_URL="http://web"`
3. Drupal database exists and is accessible

### Float precision assertions fail
Use `assertGreaterThan` and `assertLessThan` instead of `assertEquals` for float results:
```php
// Good
$this->assertGreaterThan(2.91, $score);
$this->assertLessThan(2.93, $score);

// Problematic (due to float precision)
$this->assertEquals(2.917, $score);
```
