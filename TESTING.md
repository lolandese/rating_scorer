# Testing Guide - Rating Scorer Module

This document describes the test suite for the Rating Scorer module, including test organization, coverage, and guidelines for running and contributing tests.

## Test Overview

The module includes comprehensive tests covering:
- **Algorithm Tests** (22 tests) - Validation of scoring calculations
- **Functional Tests** (1 test) - HTTP response and page loading
- **Integration Tests** (1 test) - User creation and permissions

**Total: 24 tests, all passing**

## Running Tests

### Prerequisites

Ensure test dependencies are installed:

```bash
cd /home/martinus/ddev-projects/green
ddev composer require --dev drupal/core-dev:^11.3 phpunit/phpunit:^11.5.42
```

### Run All Tests

```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'
```

### Run Specific Test File

Run only algorithm tests:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter RatingScorerAlgorithmsTest'
```

Run only functional/integration tests:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter RatingScorerTest'
```

### Run Specific Test Method

Run a single algorithm test:
```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter testBayesianAverageBasic'
```

## Test File Organization

```
tests/
├── src/
│   └── Unit/
│       ├── RatingScorerAlgorithmsTest.php    (22 algorithm tests)
│       └── RatingScorerTest.php              (1 functional + 1 integration test)
```

## Test Coverage

### Algorithm Tests (`RatingScorerAlgorithmsTest.php`)

Tests for the three scoring algorithms exposed by `_rating_scorer_calculate_score()`.

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

### Functional & Integration Tests (`RatingScorerTest.php`)

Tests module integration with Drupal.

- **testHomePageLoads** - Verifies `/` returns HTTP 200 (functional test)
- **testUserWithAdminPermission** - Creates user with "Administer site configuration" permission (integration test)

## Test Structure

All tests extend `BrowserTestBase` for full Drupal environment access:

```php
class RatingScorerAlgorithmsTest extends BrowserTestBase {
  protected $defaultTheme = 'stark';
  protected static $modules = ['system', 'user', 'rating_scorer'];
}
```

This ensures:
- Database connection available
- Drupal bootstrap complete
- Module hooks initialized
- Helper functions callable (`_rating_scorer_calculate_score()`)

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

2. Implement algorithm in `rating_scorer.module`:

```php
case 'new_method':
  return /* calculation */;
```

3. Run test:

```bash
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter testNewAlgorithmBasic'
```

## Test Coverage Summary

| Component | Tests | Coverage |
|-----------|-------|----------|
| Weighted Score Algorithm | 5 | Basic, edge cases, volume sensitivity |
| Bayesian Average Algorithm | 6 | Basic, thresholds, convergence, fairness |
| Wilson Score Algorithm | 6 | Basic, conservativeness, bounds |
| Edge Cases | 3 | Invalid methods, decimals, large numbers |
| Functional Integration | 1 | Page loading |
| User Permissions | 1 | User creation and roles |
| **Total** | **24** | **Comprehensive algorithm validation** |

## Known Limitations

- **Algorithm tests** use `BrowserTestBase` for access to Drupal functions, though tests don't require database transactions
- **Functional tests** are minimal - could be expanded with Views integration testing
- **No performance tests** - algorithms are generally fast, but could benchmark very large datasets

## Future Test Improvements

Potential areas for expansion:

1. **Views Integration Tests**
   - Field handler rendering
   - Sort criteria functionality
   - Field configuration validation

2. **Settings Form Tests**
   - Form submission and validation
   - Default value persistence
   - Invalid input handling

3. **Block Plugin Tests**
   - Block rendering
   - Block configuration

4. **Performance Tests**
   - Large dataset handling
   - Batch operation efficiency

5. **Permissions Tests**
   - Admin-only access verification
   - Non-admin denial

## Troubleshooting

### Tests fail with "SIMPLETEST_DB not set"
Make sure environment variables are exported:
```bash
export SIMPLETEST_DB="mysql://db:db@db:3306/db"
export SIMPLETEST_BASE_URL="http://localhost"
```

### Tests fail with database connection error
Verify DDEV is running:
```bash
ddev status
```

### Specific test fails
Run with verbose output:
```bash
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit -v --configuration web/modules/custom/rating_scorer/phpunit.xml'
```

### Float precision assertions fail
Use `assertGreaterThan` and `assertLessThan` instead of `assertEquals` for float results:
```php
// Good
$this->assertGreaterThan(2.91, $score);
$this->assertLessThan(2.93, $score);

// Problematic (due to float precision)
$this->assertEquals(2.917, $score);
```
