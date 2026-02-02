# AGENTS.md: AI Agent Guide for Rating Scorer Module

> AI Agent Instructions: This guide provides comprehensive instructions for AI coding agents working on
> the Rating Scorer Drupal module. Follow these guidelines for consistent, high-quality contributions.
> Human contributors should use README.md instead.

## Module Overview

- **Module Name**: Rating Scorer
- **Type**: Drupal contributed module
- **Package**: Rating
- **Core Compatibility**: Drupal 10.x - 11.x
- **PHP Requirement**: 8.1+
- **Purpose**: Calculate and store fair rating scores as computed fields on content entities

### What This Module Does

Rating Scorer prevents items with few reviews from dominating rankings by combining average rating
with review volume using sophisticated algorithms. Key features:

- **Computed Rating Score Fields** - Automatic calculation and storage on content save
- **Multiple Scoring Algorithms** - Weighted, Bayesian Average (IMDB-style), Wilson Score (Reddit-style)
- **Field Mapping System** - Per-content-type configuration for automatic score calculation
- **Rating Module Integration** - Auto-detection of Votingapi, Fivestar, Rate modules
- **Interactive Calculator** - Testing interface at `/admin/config/rating-scorer/calculator`
- **Status Dashboard** - Monitor field mapping health and coverage statistics

## Project Structure

```
rating_scorer/
├── src/
│   ├── Commands/                    # Drush commands
│   │   └── RatingScorerCommands.php
│   ├── Controller/                  # Admin page controllers
│   │   └── RatingScorerController.php
│   ├── Entity/                      # Configuration entities
│   │   └── RatingScorerFieldMapping.php
│   ├── EventSubscriber/             # Event subscribers
│   │   └── FieldStorageConfigSubscriber.php
│   ├── Form/                        # Admin forms
│   │   ├── FieldMappingWizardForm.php
│   │   ├── RatingScorerCalculatorForm.php
│   │   ├── RatingScorerFieldMappingForm.php
│   │   └── RatingScorerSettingsForm.php
│   ├── Plugin/
│   │   ├── Block/                   # Calculator block
│   │   └── Field/                   # Field types, formatters, widgets
│   └── Service/                     # Core business logic
│       ├── DataProvider/            # Rating data provider plugins
│       ├── FieldCreationService.php
│       ├── FieldDetectionService.php
│       ├── RatingDataProviderManager.php
│       ├── RatingModuleDetectionService.php
│       ├── RatingScoreCalculator.php
│       └── RatingScorerDashboardService.php
├── tests/
│   └── src/
│       ├── Functional/              # Browser-based tests
│       ├── Kernel/                  # Drupal integration tests
│       └── Unit/                    # Isolated unit tests
├── config/
│   ├── install/                     # Default configuration
│   └── schema/                      # Configuration schema
├── docs/                            # Extended documentation
├── modules/demo/                    # Demo submodule with sample content
└── templates/                       # Twig templates
```

## Key Services

| Service ID | Class | Purpose |
|------------|-------|---------|
| `rating_scorer.calculator` | `RatingScoreCalculator` | Core scoring algorithms (Weighted, Bayesian, Wilson) |
| `rating_scorer.field_detection` | `FieldDetectionService` | Detect numeric fields on content types |
| `rating_scorer.field_creation` | `FieldCreationService` | Create Rating Score fields programmatically |
| `rating_scorer.rating_module_detection` | `RatingModuleDetectionService` | Auto-detect installed rating modules |
| `rating_scorer.rating_data_provider_manager` | `RatingDataProviderManager` | Manage data provider plugins |
| `rating_scorer.dashboard` | `RatingScorerDashboardService` | Dashboard statistics and health metrics |

## Scoring Algorithms

### 1. Weighted Score
Simple logarithmic weighting favoring items with many ratings:
```
score = average_rating * log(1 + num_ratings)
```

### 2. Bayesian Average (Recommended)
IMDB-style scoring that requires confidence through volume:
```
score = (min_threshold * global_average + num_ratings * average_rating) / (min_threshold + num_ratings)
```

### 3. Wilson Score
Confidence interval approach (lower bound of 95% CI):
```
score = (p + z²/2n - z√(p(1-p)/n + z²/4n²)) / (1 + z²/n)
```
Where p = positive proportion, n = total votes, z = 1.96 (95% confidence)

## Development Environment

### Prerequisites
- DDEV installed and configured
- Drupal 10.x or 11.x
- PHP 8.1+

### Essential Commands

```bash
# Start development environment
cd <drupal-project-root>
ddev start

# Clear caches after code changes
ddev exec drush cr

# Run all module tests
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'

# Run only unit tests (fast)
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --testsuite unit'

# Run only functional tests
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --testsuite functional'

# Run specific test file
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --filter RatingScorerAlgorithmsTest'

# Code style checking
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/rating_scorer/src/

# Auto-fix code style issues
ddev exec vendor/bin/phpcbf --standard=Drupal,DrupalPractice web/modules/custom/rating_scorer/src/

# Static analysis
ddev exec vendor/bin/phpstan analyse web/modules/custom/rating_scorer/src/

# Check for deprecated code
ddev exec vendor/bin/drupal-check web/modules/custom/rating_scorer/

# Export configuration changes
ddev exec drush config:export

# Database snapshot before testing
ddev snapshot
```

## Testing Guidelines

### Test Organization

| Directory | Type | Purpose | Speed |
|-----------|------|---------|-------|
| `tests/src/Unit/` | UnitTestCase | Isolated algorithm tests, no Drupal bootstrap | Fast |
| `tests/src/Kernel/` | KernelTestBase | Service integration, entity operations | Medium |
| `tests/src/Functional/` | BrowserTestBase | Full browser simulation, UI testing | Slow |

### Test Coverage Areas

- **Algorithm Tests** - Validate Weighted, Bayesian, Wilson calculations
- **Field Type Tests** - Computed field structure and configuration
- **Field Mapping Tests** - Configuration entity validation
- **Calculator Service Tests** - Score calculation service
- **Form Tests** - Settings and field mapping form validation
- **Controller Tests** - Admin page rendering
- **Block Tests** - Calculator block functionality
- **Admin Interface Tests** - Routing, tabs, UI elements
- **Recalculation Tests** - Auto-calculation on content/mapping changes

### Running Tests Before Commits

```bash
# Full test suite
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'

# Quick validation (unit tests only)
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml --testsuite unit'
```

## Code Style Standards

Follow Drupal coding standards (PSR-12 with Drupal extensions):

- **PHP**: 2-space indentation, ≤80 char lines, CamelCase classes, snake_case functions
- **YAML**: 2-space indentation, lowercase keys
- **Twig**: `{{ }}` for output, `{% %}` for logic, always escape with `|e`

### Before Submitting Code

```bash
# Check code style
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/rating_scorer/src/

# Run tests
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'

# Clear caches
ddev exec drush cr
```

## Common Development Tasks

### Adding a New Scoring Algorithm

1. Add calculation method to `RatingScoreCalculator::calculateScore()`
2. Add option to `RatingScorerFieldMappingForm`
3. Add unit tests in `RatingScorerAlgorithmsTest`
4. Update calculator form and block
5. Update documentation

### Adding a New Data Provider

1. Create class in `src/Service/DataProvider/` implementing `RatingDataProviderInterface`
2. Register with `RatingDataProviderManager`
3. Add detection logic to `RatingModuleDetectionService`
4. Add unit tests

### Modifying Field Mappings

1. Update `RatingScorerFieldMapping` entity
2. Update config schema in `config/schema/rating_scorer.schema.yml`
3. Add/update form fields in `RatingScorerFieldMappingForm`
4. Run `ddev exec drush cr` to clear caches

## Admin Routes

| Path | Controller/Form | Purpose |
|------|-----------------|---------|
| `/admin/config/rating-scorer` | `RatingScorerController::dashboard` | Status dashboard |
| `/admin/config/rating-scorer/mappings` | `RatingScorerFieldMappingListBuilder` | Field mapping list |
| `/admin/config/rating-scorer/mappings/add` | `FieldMappingWizardForm` | Add field mapping |
| `/admin/config/rating-scorer/calculator` | `RatingScorerCalculatorForm` | Interactive calculator |
| `/admin/config/rating-scorer/settings` | `RatingScorerSettingsForm` | Default settings |

## Integration Points

### Votingapi Integration
- Primary data source for rating data
- Extracts average ratings and vote counts from Votingapi aggregates
- Fully tested and supported

### Fivestar Integration
- Uses Votingapi as storage layer
- D10: 8.x-1.0-alpha5 (stable alpha)
- D11: 3.0.x-dev (development only)
- Verified through manual testing

### Rate Module Integration
- Compatible with Rate module rating widgets
- Uses Votingapi backend

## Troubleshooting

### Common Issues

1. **Scores not calculating**: Check field mapping configuration, ensure source fields exist
2. **Cache issues**: Run `ddev exec drush cr`
3. **Test failures**: Ensure `SIMPLETEST_BASE_URL` is set correctly
4. **Module detection failing**: Verify rating modules are enabled

### Debugging Commands

```bash
# Check module status
ddev exec drush pm:list --filter=rating_scorer

# View recent logs
ddev exec drush watchdog:show --type=rating_scorer

# Test field mapping
ddev exec drush eval "print_r(\Drupal::entityTypeManager()->getStorage('rating_scorer_field_mapping')->loadMultiple());"
```

## Additional Resources

- [README.md](README.md) - User documentation
- [docs/TESTING.md](docs/TESTING.md) - Detailed testing guide
- [docs/INTEGRATION_GUIDE.md](docs/INTEGRATION_GUIDE.md) - Integration setup
- [docs/WIZARD_FEATURE.md](docs/WIZARD_FEATURE.md) - Field mapping wizard
- [docs/DASHBOARD_FEATURE.md](docs/DASHBOARD_FEATURE.md) - Dashboard feature

## Version Control

- **Primary Remote**: `git@git.drupal.org:project/rating_scorer.git` (origin)
- **Secondary Remote**: `git@github.com:lolandese/rating_scorer.git` (github)
- **Commit Format**: `[#issue_number] Brief descriptive title`
- **Branch Strategy**: Feature branches from main/develop
