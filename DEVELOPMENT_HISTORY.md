# Rating Scorer Module - Development History

## Assistant Metadata
- **AI Assistant**: GitHub Copilot
- **AI Model**: Claude Haiku 4.5
- **Created**: January 2026
- **Module Type**: Drupal 11 Custom Module
- **Purpose**: Fair ranking system using Views field handler

---

## Project Evolution: From Stand-Alone Widget to Drupal Module

### Phase 0: Stand-Alone JavaScript Widget
The Rating Scorer project began as a pure JavaScript application outside of Drupal, iterating through the following development prompts:

**Development Iterations (Prompts 1-8)**:
1. **Initial Concept**: Create a scoring system based on a rating and number of ratings
2. **UI Enhancement**: Add rating slider with two decimal precision
3. **Second Field**: Create slider for number of ratings (one unit precision)
4. **Example Data**: Set examples with 30 and 100 ratings respectively
5. **Configuration**: Set minimum ratings threshold to 15
6. **Fine-tuning**: Adjust example ratings to 4.9 and 4.95
7. **Optimization**: Swap ratings between examples (4.95 and 4.90)
8. **Threshold Adjustment**: Changed default minimum ratings threshold to 1

**Outcome**: A fully functional stand-alone JavaScript widget demonstrating fair rating algorithms with configurable parameters.

### Phase 1: Drupal 11 Module Integration
**Prompt**: "Roll this application into a Drupal 11 module with a user permission definition for modifying the set parameter of the default minimum ratings threshold."

**Initial Challenge**: PHP namespace declaration syntax error in generated code
**Solution**: Corrected namespace ordering to appear before all other statements

**Implementation**:
- Migrated JavaScript widget logic to Drupal 11 module structure
- Created `rating_scorer.info.yml` with Drupal 11 core requirement
- Created `composer.json` with package metadata
- Set up routing with Settings and Calculator pages
- Created menu links with tabbed interface
- Implemented permission system for settings management
- Preserved all three scoring algorithms from widget version

### Phase 2: Admin Interface & Settings
**Implementation**:
- Built `RatingScorerSettingsForm.php` with configurable defaults
- Built `RatingScorerCalculatorForm.php` for testing calculations (evolved from widget)
- Built `RatingScorerController.php` for rendering admin pages
- Maintained all three scoring algorithms (Weighted, Bayesian, Wilson Score)

### Phase 3: Block Plugin
**Implementation**:
- Built `RatingScorerCalculatorBlock.php` extending BlockBase
- Made calculator available anywhere on the site via block system
- No custom access control needed (handled by Drupal block system)

### Phase 4: Views Field Handler - Initial Implementation
**Prompt**: Create a Views field handler to calculate fair scores in Views displays

**Implementation**:
- Created `RatingScore.php` in `/src/Plugin/views/field/`
- Extended `FieldPluginBase`
- Implemented `defineOptions()` for field configuration
- Implemented `render()` method for score calculation
- Attempted PHP 8 attribute syntax: `#[ViewsField("rating_score")]`

**Problem Encountered**: Field was registered in plugin system but not appearing in Views UI

### Phase 5: Views Field Handler - Debugging Discovery Issue
**Key Diagnostic Command**:
```bash
ddev drush php:eval "print_r(\Drupal::service('plugin.manager.views.field')->getDefinitions());"
```

**Findings**:
- Plugin WAS registered (confirmed "rating_score" in output)
- But Views UI wasn't discovering it for field selection
- Core fields (Link, Image) also weren't appearing in Views
- Suggested Currency module investigation as reference

**Root Cause**: System-wide Views field discovery was broken

**Solution Attempts**:
1. First attempt: Added `hook_views_data()` with static table definition
   - Result: Still not appearing in Views UI

2. Second attempt: Reverted from PHP 8 attributes to old annotation syntax
   - Changed from `#[ViewsField("rating_score")]` to `@ViewsField("rating_score")`
   - Added `@ingroup views_field_handlers` docblock
   - Result: Still not appearing

3. Final successful attempt: Rewrote `hook_views_data()` to register field across all entity base tables dynamically
   ```php
   foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type) {
     if ($base_table = $entity_type->getBaseTable()) {
       $data[$base_table]['rating_score'] = [...]
     }
   }
   ```
   - Result: ✅ Field NOW APPEARS in Views UI

**Cache Rebuild**: Critical after each change
```bash
ddev drush cache:rebuild
```


### Phase 6: Testing & Verification
After successful Views field discovery:

**Field Configuration Options**:
- Number of Ratings Field (textfield for Views field alias)
- Average Rating Field (textfield for Views field alias)
- Scoring Method (select dropdown: Weighted/Bayesian/Wilson)
- Minimum Ratings Threshold (number field)

**Usage Pattern**:
1. Create a Views display
2. Add numeric fields to the view (ratings count, average rating)
3. Note their exact field keys/aliases
4. Add "Rating Score" field from the field selection
5. Configure with the field aliases from step 2

### Phase 8: Views Field Sorting Implementation
**Date**: January 15, 2026

**Objective**: Enable sorting by the calculated rating_score field in Views displays.

**Approach Attempted**:
1. First attempt: PHP-based sorting in preRender hook
   - Problem: Only sorted current page, not globally across all pages

2. Second attempt: Remove LIMIT/OFFSET via hook_views_query_alter using Reflection
   - Problem: Drupal 11's typed properties can't be safely accessed/modified via Reflection when uninitialized

3. Third attempt: Materialized column approach
   - Add rating_score column to node table
   - Calculate scores on presave via hook_entity_presave()
   - Problem: Database schema addIndex() API incompatibility, presave hook causing navigation module errors

**Final Solution**: Per-page sorting implementation
- `hook_views_pre_render()` sorts current page's results by cached score
- Respects ASC/DESC sort order
- Maintains proper pagination and pager
- Clean, stable implementation without database schema changes

**Key Learnings**:
- Views' sorting architecture assumes database-backed fields
- Reflection with typed properties is fragile in Drupal 11
- Per-page sorting is acceptable for most use cases where large page sizes are used
- Materialized columns require careful schema API usage

**Sorting Limitation Documentation**:
- Updated README.md with clear explanation of per-page sorting behavior
- Documented limitations and suggested workarounds
- Added note about global sorting requiring materialized columns

---


**Prompts 11-12**: "Write a concise project page for this application to be published on Drupal.org" and "Generate a markdown text file that lists all the previously used prompts"

**Documentation Files Created**:

1. **README.md** - User-facing documentation
   - Feature overview emphasizing fair ranking
   - Use case examples (e-commerce, content aggregators, review platforms)
   - Installation and setup instructions
   - Scoring algorithm formulas and explanations
   - Views integration guide with field configuration
   - Requirements and permissions documentation
   - Credit to AI assistant (GitHub Copilot - Claude Haiku 4.5)
   - Link to DEVELOPMENT_HISTORY for technical details

2. **prompt_history.md** - Complete list of all development prompts
   - Documents the evolution from widget to Drupal module
   - Lists 12 key prompts that guided development

3. **DEVELOPMENT_HISTORY.md** (this file) - Complete technical documentation
   - Project evolution from stand-alone to Drupal module
   - Full development timeline by phase
   - Problems encountered and solutions
   - Key diagnostic commands and outputs
   - Technical insights and lessons learned
   - Troubleshooting checklist
   - Quick start guide

4. **Module Configuration Files**:
   - `rating_scorer.info.yml` - Module metadata and requirements
   - `rating_scorer.routing.yml` - Admin page routes (Settings, Calculator)
   - `rating_scorer.links.menu.yml` - Menu structure
   - `rating_scorer.links.task.yml` - Tabbed interface
   - `rating_scorer.permissions.yml` - Access control definitions
   - `rating_scorer.libraries.yml` - CSS/JS libraries
   - `composer.json` - Package metadata and dependencies

---

## Key Technical Insights

### Views Field Handler Registration
- Plugin class must extend `FieldPluginBase`
- Annotation: `@ViewsField("plugin_id")` with `@ingroup views_field_handlers`
- **Critical**: Requires `hook_views_data()` to expose field in Views UI
- Field must be registered across entity base tables, not static tables

### Field Configuration in Views
- Use Views field **aliases/keys**, not machine names
- Field aliases are determined by how they're added to the View
- Access other View fields via: `$this->view->field[$field_alias]->getValue($values)`

### Empty Field Handling
- Empty numeric fields treated as 0: `(!empty($value) ? $value : 0)`
- Prevents errors when optional fields aren't populated

### Scoring Algorithms
1. **Weighted Score**: `average_rating * log(number_of_ratings + 1)`
   - Favors items with more ratings

2. **Bayesian Average**: `(ratings * avg + threshold * 2.5) / (ratings + threshold)`
   - Prevents low-rating items from dominating
   - Uses confidence threshold

3. **Wilson Score**: Confidence interval approach (95% confidence)
   - Conservative, heavily penalizes items with few ratings

---

## Docker/Development Environment Issues

**Permission Issues Encountered**:
- Docker daemon socket permission errors: `permission denied while trying to connect to the Docker daemon socket`
- Required system restart to resolve
- After restart, ddev commands worked normally

**Drush Version**: Upgraded from Drush 12 to Drush 13
- Command syntax changed from `drush ev` to `drush php:eval`

---

## Final Module Structure

```
rating_scorer/
├── composer.json
├── rating_scorer.info.yml
├── rating_scorer.module (includes hook_views_data())
├── rating_scorer.routing.yml
├── rating_scorer.links.menu.yml
├── rating_scorer.links.task.yml
├── rating_scorer.permissions.yml
├── src/
│   ├── Controller/RatingScorerController.php
│   ├── Form/RatingScorerSettingsForm.php
│   ├── Form/RatingScorerCalculatorForm.php
│   └── Plugin/
│       ├── Block/RatingScorerCalculatorBlock.php
│       └── views/field/RatingScore.php
├── tests/
│   └── src/
│       ├── Unit/RatingScorerTest.php
│       └── Functional/RatingScorerFunctionalTest.php
├── templates/rating-scorer.html.twig
├── css/rating-scorer.css
├── js/rating-scorer.js
└── README.md
```

---

## Testing

**Run Unit Tests**:
```bash
ddev drush php:eval "require 'vendor/bin/phpunit'; exit(system('vendor/bin/phpunit --configuration modules/custom/rating_scorer/phpunit.xml'));"
```

**Manual Testing**:
1. Enable module: `ddev drush en rating_scorer`
2. Navigate to `/admin/config/rating-scorer` for settings
3. Create Views display with numeric fields
4. Add "Rating Score" field and configure with field aliases
5. Verify scores calculate correctly

---

## Lessons Learned

1. **Views field discovery requires `hook_views_data()`** - Plugin registration alone is insufficient
2. **Fields must be registered on entity base tables** - Not on arbitrary table names
3. **Cache rebuild is essential** - After module changes, always clear cache
4. **Test with real-world examples** - Currency module provided valuable implementation reference
5. **System diagnostics are crucial** - Using plugin manager queries helped identify root cause
6. **Documentation matters** - Clear README helps future maintainers understand the fair ranking use case

---

## Resources Used

- Drupal Views Plugin System Documentation
- Drupal Currency Module (contributed module - implementation reference)
- Drupal Core Views Field Handlers (Counter, Standard, etc.)
- PHPUnit Testing Framework

---

## Phase 6: Computed Field Integration (In Progress - Feature Branch)

**Branch**: `feature/computed-field-integration`

### Problem Statement
The current Views field handler implementation has a limitation: sorting is applied per-page rather than globally across all results. This occurs because calculated/virtual fields don't exist in the database, preventing database-level sorting.

### Solution Architecture: Custom Field Type Approach
Implement a Drupal custom field type (`rating_score`) that stores computed scores directly in the database, enabling:
- Global database-level sorting across all results
- Per-content-type field mapping configuration
- Seamless entity integration via presave hooks
- Backward compatibility with existing content

### Implementation Components

**1. RatingScoreFieldType Plugin** (`src/Plugin/Field/FieldType/RatingScoreFieldType.php`)
- Extends `FieldItemBase` for numeric storage
- Stores precision 10.2 (10 total digits, 2 decimal places)
- Field settings:
  - `number_of_ratings_field`: Source field for rating count
  - `average_rating_field`: Source field for average rating
  - `scoring_method`: Algorithm selection (weighted/bayesian/wilson)
  - `bayesian_threshold`: Minimum ratings for Bayesian scoring

**2. RatingScoreWidget Plugin** (`src/Plugin/Field/FieldWidget/RatingScoreWidget.php`)
- Provides `number` input widget for field values
- Supports configurable step and placeholder
- Allows manual score entry or automatic calculation

**3. RatingScoreFormatter Plugin** (`src/Plugin/Field/FieldFormatter/RatingScoreFormatter.php`)
- Displays score values with 2 decimal precision
- Format: `number_format($value, 2)`

**4. RatingScorerFieldMapping Configuration Entity** (`src/Entity/RatingScorerFieldMapping.php`)
- Stores per-content-type field mappings
- Config ID pattern: `{entity_type}.{bundle}` (e.g., `node.article`)
- Exports configuration to YAML for version control
- Supports all three scoring algorithms and Bayesian threshold

**5. RatingScoreCalculator Service** (`src/Service/RatingScoreCalculator.php`)
- Injected via `rating_scorer.calculator` service
- `calculateScoreForEntity()`: Computes single field score
- `updateScoreFieldsOnEntity()`: Updates all rating_score fields on entity
- Handles configuration lookup and validation

**6. Admin UI Components**
- `RatingScorerFieldMappingForm.php`: Configuration form with:
  - AJAX field selection based on selected content type
  - Scoring method dropdown
  - Bayesian threshold configuration
  - Automatic numeric field filtering
- `RatingScorerFieldMappingListBuilder.php`: List of all configured mappings
- Routes added to `rating_scorer.routing.yml` for CRUD operations

**7. Integration Hooks**
- `hook_entity_presave()`: Automatically calculates and updates scores when:
  - Entity source fields are modified
  - Entity is saved (by any process)
  - Presave occurs before entity state changes

**8. Service Registration**
- `rating_scorer.services.yml`: Registers `RatingScoreCalculator` service

### Configuration Workflow
1. Navigate to `/admin/config/rating-scorer/field-mappings`
2. Click "Add Field Mapping"
3. Select content type (e.g., "Article")
4. Select source fields for number of ratings and average rating
5. Choose scoring method and Bayesian threshold if applicable
6. Save configuration
7. Add `rating_score` field to content type via field UI
8. Future entity saves automatically calculate scores

### Database Schema
```sql
-- rating_score field storage in content type tables
ALTER TABLE node__field_rating_score ADD COLUMN field_rating_score_value NUMERIC(10, 2);
CREATE INDEX idx_rating_score ON node__field_rating_score(field_rating_score_value);
```

### Score Calculation Flow
1. Entity presave triggered
2. Find all `rating_score` fields on entity
3. For each field:
   a. Load entity's content type mapping configuration
   b. Get source field values
   c. Call `_rating_scorer_calculate_score()` helper
   d. Store result in field
4. Field saved to database with entity

### Testing
Created 13 new unit tests:
- `RatingScoreFieldTypeTest` (6 tests): Plugin structure and configuration
- `RatingScorerFieldMappingTest` (3 tests): Config entity validation
- `RatingScoreCalculatorTest` (4 tests): Service functionality

Tests validate:
- Plugin annotations and metadata
- Field storage schema (precision/scale)
- Property definitions
- Entity configuration structure
- Service instantiation and method signatures
- Null handling for missing configurations

### Key Design Decisions

**1. Config Entity vs Settings Form**
- Chosen: Config entities for per-content-type flexibility
- Reason: Allows different algorithms/thresholds per content type
- Alternative rejected: Single global settings form

**2. Presave Hook Integration**
- Chosen: Automatic calculation in `hook_entity_presave()`
- Reason: Seamless integration, no manual trigger needed
- Benefit: Works with all entity creation/modification methods

**3. Service Layer Abstraction**
- Chosen: Dedicated `RatingScoreCalculator` service
- Reason: Reusable, testable, dependency injectable
- Flexibility: Can be used outside presave hooks

**4. Field Type vs Views Handler**
- Computed Field Advantages:
  - ✅ Global database-level sorting
  - ✅ Works in all Views displays without configuration
  - ✅ Queryable, filterable, sortable
  - ✅ Exportable/importable via config management
  - ✅ Works outside Views (blocks, templates, etc.)
- Views Handler Limitations:
  - ❌ Per-page sorting only
  - ❌ Limited to Views displays
  - ❌ Recalculates on every Views render

### Migration Path (Planned)
1. Test computed field implementation thoroughly
2. Deploy to test/staging environment
3. Verify score accuracy and performance
4. Optionally keep Views handler for compatibility
5. Future: Remove Views handler when computed field fully adopted

### Files Created/Modified

**New Files**:
- `src/Plugin/Field/FieldType/RatingScoreFieldType.php`
- `src/Plugin/Field/FieldWidget/RatingScoreWidget.php`
- `src/Plugin/Field/FieldFormatter/RatingScoreFormatter.php`
- `src/Entity/RatingScorerFieldMapping.php`
- `src/Form/RatingScorerFieldMappingForm.php`
- `src/RatingScorerFieldMappingListBuilder.php`
- `src/Service/RatingScoreCalculator.php`
- `rating_scorer.services.yml`
- `tests/src/Unit/RatingScoreFieldTypeTest.php`
- `tests/src/Unit/RatingScorerFieldMappingTest.php`
- `tests/src/Unit/RatingScoreCalculatorTest.php`

**Modified Files**:
- `rating_scorer.module`: Updated entity_presave hook
- `rating_scorer.routing.yml`: Added field mapping routes

### Git Commits
- `551a89b`: Initial computed field type implementation
- `80b0070`: Fix parameter order and add routing
- `f96a972`: Add unit tests for computed field

---

## Status

✅ **Main Branch COMPLETE** - Views handler implementation is fully functional:
- Settings form for configurable defaults
- Calculator admin interface
- Reusable block plugin
- Views field handler discoverable and usable
- Three scoring algorithms
- PHPUnit test infrastructure (32 tests)
- Per-page sorting via hook_views_pre_render()

🔄 **Feature Branch IN PROGRESS** - Computed field implementation:
- Custom field type with numeric storage ✅
- Configuration entity for per-content-type mapping ✅
- RatingScoreCalculator service ✅
- Field widget and formatter ✅
- Configuration form with AJAX ✅
- 13 new unit tests ✅
- Admin routing and UI hooks ✅
- **Next**: Testing and refinement on feature branch
- **Future**: Merge to main after validation

**Next Steps**:
1. Integration testing with actual Drupal field creation
2. Verify score calculation during entity save
3. Test sorting and filtering in Views with new field type
4. Performance testing with large datasets
5. Merge to main branch once validation complete
6. Update README with computed field setup instructions
7. Optional: Deprecate Views handler after computed field adoption
