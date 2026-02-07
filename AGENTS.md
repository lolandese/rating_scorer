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

## Dependency Management

### Adding New Dependencies

When adding new dependencies to the module, follow these guidelines to ensure stability and predictable builds:

#### Development Version Dependencies

When adding dependencies that use development versions (e.g., `dev-main`, `3.0.x-dev`, `4.x-dev`), **always pin to a specific commit hash** to prevent unexpected code changes during `composer update`.

**Why This Matters:**
- Development branches change frequently
- `composer update` can pull in breaking changes unexpectedly
- Pinning ensures reproducible builds across environments
- Prevents CI/testing failures due to upstream changes

**Correct Format:**
```json
{
  "require": {
    "drupal/fivestar": "3.0.x-dev#abc123def456",
    "vendor/package": "dev-main#789xyz012345"
  }
}
```

**Incorrect Format (Avoid):**
```json
{
  "require": {
    "drupal/fivestar": "3.0.x-dev",
    "vendor/package": "dev-main"
  }
}
```

#### How to Find Commit Hashes

1. **Via Git Repository:**
   ```bash
   git log --oneline -n 10
   # Copy the commit hash from desired commit
   ```

2. **Via Composer Show:**
   ```bash
   composer show vendor/package --all
   # Look for the commit hash in version information
   ```

3. **Via GitHub/GitLab:**
   - Navigate to the repository
   - Go to the specific branch
   - Copy the latest commit hash

#### When to Update Commit Hashes

- **Before major releases** - Update to latest stable commits
- **When fixing bugs** - Update if upstream fixes are needed
- **During security updates** - Update immediately if security fixes are available
- **Regular maintenance** - Review and update quarterly

#### Stable Version Dependencies

For stable releases, use semantic versioning constraints:

```json
{
  "require": {
    "drupal/core": "^10.0 || ^11.0",
    "drupal/votingapi": "^3.0"
  }
}
```

### Dependency Documentation

When adding new dependencies:

1. **Document the purpose** in commit messages
2. **Update README.md** if it affects installation
3. **Add to integration tests** if it affects functionality
4. **Note version requirements** in module documentation

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

## Module Configuration Guide

### Configuration Architecture

The Rating Scorer module uses two types of configuration:

1. **Simple Configuration** - Stored in `rating_scorer.settings` (YAML file)
   - Default settings for new field mappings
   - Global preferences
   - Located at `config/install/rating_scorer.settings.yml`

2. **Configuration Entities** - Stored as entity records (database)
   - Field mappings per content type
   - Configuration type: `rating_scorer_field_mapping`
   - Defined in `src/Entity/RatingScorerFieldMapping.php`
   - Schema: `config/schema/rating_scorer.schema.yml`

### Configuration Tasks for AI Agents

When you receive requests to "configure the module," they typically fall into these categories:

#### 1. Adding Module-Level Settings
**When to use**: Requests like "add a setting for...", "create a configuration option for...", "allow users to set..."

**Steps**:
1. Define setting in `config/install/rating_scorer.settings.yml`
2. Add schema entry in `config/schema/rating_scorer.schema.yml`
3. Create/update form in `src/Form/RatingScorerSettingsForm.php` to expose setting to UI
4. Add getter method in appropriate service to retrieve setting
5. Use `\Drupal::config('rating_scorer.settings')->get('setting_key')`
6. Add tests to `tests/src/Kernel/RatingScorerServiceTest.php`
7. Run `ddev exec drush cr` to clear caches and register new config

**Example**: Adding a new default scoring algorithm selection:
- Add to settings.yml: `default_algorithm: bayesian`
- Add to schema with validation
- Add form field to `RatingScorerSettingsForm`
- Use in `RatingScoreCalculator` for defaults

#### 2. Modifying Field Mapping Configuration Entity
**When to use**: Requests like "add a field to field mappings", "change how field mappings work", "store additional metadata"

**Steps**:
1. Edit `src/Entity/RatingScorerFieldMapping.php` to add new property/method
2. Update schema in `config/schema/rating_scorer.schema.yml` with field validation
3. Update form in `src/Form/RatingScorerFieldMappingForm.php` with new form field
4. Update wizard in `src/Form/FieldMappingWizardForm.php` if applicable
5. Update `RatingScorerFieldMappingListBuilder.php` if adding display columns
6. Add validation logic to entity class
7. Add tests to `tests/src/Kernel/` for entity functionality
8. Export config: `ddev exec drush config:export`

**Example**: Adding algorithm selection per field mapping:
- Add property `$algorithm` to entity class
- Define in schema with allowed values
- Add dropdown to form
- Store in configuration

#### 3. Default Configuration Installation
**When to use**: Requests like "set up default field mappings", "create sample configuration", "initialize default settings"

**Steps**:
1. Create YAML files in `config/install/`
2. Use format: `rating_scorer_field_mapping.MAPPING_ID.yml`
3. Configure in `rating_scorer.install` hook_install() if programmatic setup needed
4. Test with fresh install: `ddev reinstall` or similar DDEV command
5. Verify configuration is applied

#### 4. Configuration Dependencies
**When to use**: Requests like "make this field required only if...", "enable setting when module detected"

**Implementation**:
- Use conditional logic in forms and services
- Check configuration in `EventSubscriber` classes
- Validate dependencies in entity constraints
- Document in config schema

### Configuration File Locations Reference

| File | Purpose | Editing Method |
|------|---------|-----------------|
| `config/install/rating_scorer.settings.yml` | Default module settings | Edit directly, or via form |
| `config/schema/rating_scorer.schema.yml` | Configuration schema & validation | Edit directly, affects form/entity validation |
| `src/Entity/RatingScorerFieldMapping.php` | Field mapping entity definition | Update class properties, getters/setters |
| `src/Form/RatingScorerSettingsForm.php` | Settings UI form | Add form fields here |
| `src/Form/RatingScorerFieldMappingForm.php` | Field mapping UI form | Add/modify form fields |
| `src/Form/FieldMappingWizardForm.php` | Field mapping creation wizard | Add wizard steps here |
| `rating_scorer.services.yml` | Service definitions and parameters | For service configuration |

### Configuration Schema Documentation

The module defines its configuration structure in [config/schema/rating_scorer.schema.yml](config/schema/rating_scorer.schema.yml). This schema validates configuration data and provides documentation for the existing settings.

#### Current Configuration Schema

**rating_scorer.settings** - Module-level settings for default values and testing parameters:

```yaml
rating_scorer.settings:
  type: config_object
  label: 'Rating Scorer settings'
  mapping:
    default_minimum_ratings:
      type: integer
      label: 'Default minimum ratings threshold'
      # Default: 7 - Minimum number of ratings for Bayesian calculation
    bayesian_assumed_average:
      type: float
      label: 'Bayesian assumed average'
      # Default: 3.5 - Global average rating used in Bayesian algorithm
    default_rating:
      type: float
      label: 'Default rating value'
      # Default: 4.5 - Default rating for calculator testing
    default_num_ratings:
      type: integer
      label: 'Default number of ratings'
      # Default: 100 - Default number of ratings for calculator testing
    scenario_rating_deviation:
      type: float
      label: 'Scenario rating deviation (%)'
      # Default: 5 - Percentage deviation for rating in scenario testing
    scenario_reviews_deviation:
      type: float
      label: 'Scenario reviews deviation (%)'
      # Default: 30 - Percentage deviation for review count in scenario testing
```

**rating_scorer_field_mapping** - Configuration entity properties exported to YAML:

Defined in `src/Entity/RatingScorerFieldMapping.php`, these properties are stored per field mapping:

- `id` (string) - Unique machine name for the mapping
- `label` (string) - Human-readable label for the mapping
- `content_type` (string) - Target content type machine name (e.g., 'article', 'product')
- `source_type` (string) - Data source type: 'FIELD' or 'VOTINGAPI'
- `number_of_ratings_field` (string) - Field name storing the count of ratings
- `average_rating_field` (string) - Field name storing the average rating value
- `vote_field` (string) - Field name for VotingAPI/Fivestar vote collection
- `scoring_method` (string) - Algorithm: 'weighted', 'bayesian', or 'wilson'
- `bayesian_threshold` (integer) - Minimum ratings threshold for Bayesian calculation

These properties are automatically validated by Drupal's configuration system when saved or imported.

## Hooks and Events

### Drupal Hooks Provided

The module implements several Drupal hooks that other modules can interact with. All hooks are **thoroughly documented** with PHPDoc including parameter types, return values, and usage examples in [rating_scorer.module](rating_scorer.module).

#### Core Hooks Implemented

**1. `hook_entity_presave()`** - Primary score calculation hook
- **Purpose**: Automatically calculates rating scores when content entities are saved
- **Trigger**: Before any content entity is saved to database
- **Usage**: Processes entities with field mappings configured
- **Service**: Uses `rating_scorer.calculator` service
- **Documentation**: Complete PHPDoc with examples in `rating_scorer_entity_presave()`

**2. `hook_entity_update()`** - Mass recalculation trigger
- **Purpose**: Recalculates all scores when field mapping configuration changes
- **Trigger**: When `rating_scorer_field_mapping` entities are updated
- **Usage**: Ensures configuration changes apply to existing content
- **Performance**: Can be resource-intensive for large content sets
- **Documentation**: Complete PHPDoc with examples in `rating_scorer_entity_update()`

**3. `hook_views_pre_render()`** - Views sorting support  
- **Purpose**: Enables proper sorting of Views by rating score fields
- **Trigger**: After view query execution, before rendering
- **Usage**: Automatically sorts results by calculated scores
- **Documentation**: Complete PHPDoc with examples in `rating_scorer_views_pre_render()`

**4. `hook_theme()`** - Template registration
- **Purpose**: Registers Twig templates for calculator and dashboard
- **Templates**: `rating-scorer.html.twig`, `rating-scorer-dashboard.html.twig`

**5. `hook_help()`** - Help system integration
- **Purpose**: Provides module help text on admin/help page

#### Event Subscribers

**FieldStorageConfigSubscriber** - Automatic field mapping management
- **Events**: `field_storage_config.insert`, `field_storage_config.delete`, `field_config.insert`, `field_config.delete`
- **Purpose**: Auto-creates/deletes field mappings when Fivestar fields are added/removed
- **Location**: `src/EventSubscriber/FieldStorageConfigSubscriber.php`
- **Registration**: Tagged as `event_subscriber` in `rating_scorer.services.yml`

### Integration Points for Other Modules

When other modules need to integrate with Rating Scorer:

1. **Use Services Directly** - Access `rating_scorer.calculator` service for score calculations
2. **Implement Standard Hooks** - React to same `hook_entity_presave()` events  
3. **Configuration API** - Create/modify field mappings programmatically via configuration entities
4. **Field API** - Add rating score fields to content types using field creation service

### Hook Documentation Standards

All hooks follow Drupal PHPDoc standards with:
- `@param` tags with types and descriptions
- `@return` tags for return values  
- `@throws` tags for exception handling
- Detailed functional descriptions
- `@see` cross-references to related classes
- Usage examples for other module developers
- Performance and behavior notes

### Guidelines for AI Agents: Adding New Hooks or Events

When extending the module with new hooks or events, **always** follow these documentation requirements:

#### For New Hook Implementations
1. **Complete PHPDoc documentation** - Include all standard tags (`@param`, `@return`, `@throws`)
2. **Detailed descriptions** - Explain when the hook is triggered and what it accomplishes
3. **Usage examples** - Provide code examples showing how other modules can implement the hook
4. **Cross-references** - Link to related services, classes, or documentation with `@see` tags
5. **Performance notes** - Document any performance considerations or limitations

#### For New Event Subscribers
1. **Class-level PHPDoc** - Document the subscriber's purpose and what events it handles
2. **Method documentation** - Document each event handler method with parameter types
3. **Service registration** - Ensure proper tagging in `rating_scorer.services.yml`
4. **Event documentation** - Document what triggers each event and expected behavior

#### Documentation Template for New Hooks
```php
/**
 * Implements hook_example().
 *
 * [Detailed description of when this hook is triggered and what it does.
 * Include information about the module's behavior and how other modules
 * can use this hook.]
 *
 * @param \\Fully\\Qualified\\ClassName $parameter
 *   Description of the parameter, including what data it contains and
 *   any important properties or methods other modules might use.
 *
 * @return void|array|mixed
 *   Description of return value and what it means. Use void for hooks
 *   that don't return values.
 *
 * @throws \\Exception
 *   Document any exceptions that might be thrown during hook execution.
 *
 * @see \\Related\\Class\\Name
 * @see \\Another\\Related\\Service
 *
 * Example usage by other modules:
 * @code
 * function mymodule_example($parameter) {
 *   // Show how other modules would implement this hook
 *   if ($parameter->someCondition()) {
 *     // Example logic
 *   }
 * }
 * @endcode
 */
function rating_scorer_example($parameter) {
  // Implementation
}
```

#### Requirements Checklist
- [ ] PHPDoc includes all parameter types and descriptions
- [ ] Return value is documented (even if void)
- [ ] Exception handling is documented if applicable
- [ ] Hook purpose and trigger conditions are clearly explained
- [ ] Usage example is provided for other module developers
- [ ] Cross-references point to related code
- [ ] Performance implications are noted if relevant

### Configuration Best Practices for AI Agents

1. **Always Update Schema** - When adding config, always add schema definition
2. **Clear Caches** - Always end configuration changes with `ddev exec drush cr`
3. **Test Configuration** - Write tests that load and validate configuration
4. **Documentation** - Document new settings in code comments and README
5. **Validation** - Add constraints and validation rules to configuration entities
6. **Backward Compatibility** - When modifying config, ensure upgrades paths don't break
7. **Export Configuration** - Use `ddev exec drush config:export` to export changes to files

### Common Configuration-Related Tasks

#### Task: Add a new algorithm option
1. Update `RatingScorerFieldMapping.php` with algorithm property
2. Add validation in entity constraints
3. Add to `RatingScorerFieldMappingForm.php` as dropdown
4. Update schema in `rating_scorer.schema.yml`
5. Test algorithm selection persists across save/load

#### Task: Create default field mappings for demo content
1. Add YAML files in `config/install/` with `rating_scorer_field_mapping.*.yml` naming
2. Or update `modules/demo/config/install/` for demo-only defaults
3. Include all required fields from entity class
4. Test with `ddev exec drush config:import` or fresh install

#### Task: Add global module preference setting
1. Add to `config/install/rating_scorer.settings.yml`
2. Add schema with type and constraints
3. Add form field to `RatingScorerSettingsForm.php`
4. Create getter method in appropriate service
5. Use via `\Drupal::config('rating_scorer.settings')->get('key')`

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
