# Rating Scorer

## Introduction

Rating Scorer calculates and stores fair rating scores as computed fields on your content. The module prevents items with few reviews from dominating rankings by combining average rating with review volume using sophisticated algorithms. Scores are automatically calculated when content is created or modified, making them globally sortable and filterable. Rank and sort items equitably by implementing proven scoring methods (Weighted, Bayesian, and Wilson Score) used by major platforms like IMDB and Reddit. Includes an interactive calculator and reusable block for testing and demonstration.

## Primary Feature: Computed Rating Score Fields

The main purpose of this module is to calculate equitable rating scores as stored fields on your content, preventing unfair rankings in both Views and programmatic access. Without fair scoring:
- A single 5-star review can rank an item above products with 100 4-star reviews
- New items with one positive review appear above established quality content
- Extreme outliers skew rankings unpredictably

Define per-content-type field mappings that automatically calculate fair scores based on your content's rating data. Scores are calculated when content is created or when field mappings are updated. This allows you to:

- **Prevent vote manipulation** - Items with few reviews are scored conservatively
- **Reward quality over hype** - Established items with many ratings rank fairly
- **Use proven algorithms** - Bayesian (IMDB-style), Weighted, and Wilson Score methods
- **Configure per-content-type** - Different scoring strategies for different content types
- **Sort fairly** - Display and sort items globally by calculated score for equitable rankings
- **Access programmatically** - Calculated scores available via field API, not just Views

See [Configuration](#configuration) section below for detailed setup instructions.

## Features

- **Computed Rating Score Field** - Automatic calculation and storage of fair rating scores on content with configurable algorithms
  - **Weighted Score** - Logarithmic weighting favoring items with many ratings
  - **Bayesian Average** - IMDB-style scoring that requires confidence through volume, preventing items with few ratings from ranking unfairly high
  - **Wilson Score** - Confidence interval approach used by Reddit and others, conservative scoring for low-review items

- **Status Dashboard** - Monitor field mapping health and data quality metrics at a glance with coverage statistics and last-updated timestamps

- **Field Mapping Configuration** - Per-content-type configuration mapping your rating and review count fields to a rating score field
  - Separate configuration entity per content type
  - Choose scoring method and algorithm parameters per content type
  - Scores auto-calculate on content save
  - Scores auto-recalculate when field mappings are updated

- **Field Mapping Wizard** - Guided 4-step process for creating field mappings with automatic detection of numeric fields and optional auto-creation of Rating Score fields

- **Interactive Calculator Interface** - Real-time visualization at `/admin/config/rating-scorer/calculator` for testing algorithms before applying to content

- **Reusable Block** - Place the calculator on any page via the block layout system

- **Configurable Defaults** - Site administrators can set default parameters for calculator including minimum ratings threshold for Bayesian scoring

- **Tabbed Admin Interface** - Dashboard, Field Mappings, Calculator, and Defaults organized in intuitive tabs

- **Granular Permissions** - Single "Administer rating scorer" permission controls access to all functionality

- **Rating Module Integration** - Auto-detection and integration with popular Drupal rating modules:
  - **Votingapi** - Extract average ratings and vote counts directly from Votingapi aggregates
  - **Fivestar** - Support for Fivestar rating fields
  - **Rate** - Compatible with Rate module rating widgets
  - Custom numeric fields for flexible integration

## Use Cases

- **E-commerce Product Rankings** - Prevent single positive reviews from appearing above established quality products with hundreds of reviews
- **Content Aggregators** - Fairly rank user-generated content without gaming by manipulation
- **Review Platforms** - Create trustworthy rankings that reward quality items with substantial user feedback
- **Community Voting** - Design voting systems that prevent tyranny of single votes while rewarding genuine consensus
- **Fair Content Curation** - Automatically surface truly popular items instead of items with outlier ratings

## Requirements

- Drupal 11.x
- PHP 8.2 or higher

## Installation

Install as you would normally install a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further information.

## Configuration

### Status Dashboard

Monitor the health and status of your rating scorer implementation:

1. Navigate to **Administration > Configuration > Content authoring > Rating Scorer** (Dashboard tab)
2. View real-time statistics:
   - Total field mappings configured
   - Total entities across all content types
   - Number of entities with rating scores
   - Overall coverage percentage
3. Review per-mapping status including:
   - Scoring method used
   - Entity count and coverage for each content type
   - Last updated timestamp

The dashboard helps you identify coverage gaps and verify that ratings are being calculated correctly. See [DASHBOARD_FEATURE.md](DASHBOARD_FEATURE.md) for detailed information.

### Field Mappings (Primary Use Case)

The main workflow for this module is creating field mappings that automatically calculate and store fair rating scores:

1. Navigate to **Administration > Configuration > Content authoring > Rating Scorer** (Field Mappings tab)
2. Click **"+ Add a field mapping"** to create a new mapping for your content type
3. Configure the field mapping:
   - **Content Type** - Select which content type this mapping applies to
   - **Number of Ratings Field** - Select the field containing the count of ratings
   - **Average Rating Field** - Select the field containing the average rating value
   - **Scoring Method** - Choose your fairness approach:
     - **Weighted Score** - Simple approach balancing quality and quantity with logarithmic weighting
     - **Bayesian Average** - **Recommended for fairness** - Prevents items with few ratings from ranking unfairly high by applying a prior expectation
     - **Wilson Score** - Conservative confidence-interval approach, heavily penalizes low-review items
   - **Minimum Ratings Threshold** - (For Bayesian only) Set the minimum ratings needed to reach high scores
4. Add a **Rating Score** field to your content type (if not already present)
5. Save the field mapping

**What happens:**
- Scores are automatically calculated whenever content is created or modified
- Scores are also recalculated whenever the field mapping is saved (useful after changing algorithms)
- Calculated scores are stored in the Rating Score field and can be displayed, sorted, and filtered like any other field

**Example:** With Bayesian Average and a 10-rating threshold, an item with 1 five-star review will score ~2.8, while an item with 100 four-star reviews scores ~3.98—preventing the single review from dominating rankings.

This allows Rating Score to be used globally across Views, blocks, templates, and programmatically, without requiring per-view configuration.

### Calculator Defaults

1. Navigate to **Administration > Configuration > Content authoring > Rating Scorer > Defaults** (Defaults tab)
2. Configure default values for the calculator widget:
   - Minimum ratings threshold (affects Bayesian average)
   - Default rating value
   - Default number of ratings
   - Default scoring method
3. Save configuration

**Note:** These defaults apply ONLY to the interactive Calculator widget, not to the automatic field calculation. Field mapping configuration controls automatic scoring.

### Testing with Calculator

For testing and demonstrating different scoring algorithms before applying them to field mappings:

1. Navigate to **Administration > Configuration > Content authoring > Rating Scorer > Calculator** (Calculator tab)
2. Adjust the inputs to see how different values affect the final score
3. Switch between scoring methods to compare results
4. Use this to determine which algorithm and threshold settings work best for your content types

### Placing Calculator as a Block

For additional visibility or demonstration purposes, place the calculator on any page:

1. Navigate to **Administration > Structure > Block layout**
2. Click "Place block" in your desired region
3. Search for "Rating Scorer Calculator" and place the block
4. Configure block visibility settings as needed

### Using in Views

Since Rating Scores are stored fields, they work naturally in Views:

1. Create or edit a Views display
2. Add the **Rating Score** field to your display
3. Configure sorting and filtering as needed
4. Save the view

Because scores are calculated and stored in the database (not virtual fields), they support:
- Global sorting across all results
- Filtering by score range
- Grouping and aggregation
- Exposed filters for users

## Testing

The module includes comprehensive PHPUnit tests covering unit and functional scenarios:

```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_BASE_URL="http://web" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'
```

Test Coverage:
- **25+ unit tests** validating scoring algorithms (Bayesian, Weighted, Wilson)
- **Field type tests** ensuring computed field structure and schema
- **Configuration entity tests** validating per-content-type mappings
- **Form tests** validating settings and mapping forms
- **Controller tests** validating admin page rendering
- **ListBuilder tests** validating field mapping list display
- **Block tests** validating calculator block functionality
- **Functional tests** validating admin interface tabs and routing
- **Recalculation tests** validating auto-calculation on content and mapping changes

Tests validate algorithm correctness, field configuration, permissions, admin UI, and auto-recalculation behavior.

## Architecture

### Main Components

- **Controller** - Renders the calculator page with configurable settings
- **Forms** - Settings form for configuration, calculator form for testing
- **Block Plugin** - Reusable block for placing calculator anywhere
- **Views Field Plugin** - Calculates scores dynamically in Views displays
- **Permissions** - Single unified permission for all admin functions

### Scoring Algorithms

**Weighted Score:**
```
score = average_rating * log(number_of_ratings + 1)
```

**Bayesian Average:**
```
score = (number_of_ratings * average_rating + minimum_threshold * 2.5) / (number_of_ratings + minimum_threshold)
```
Uses a prior rating of 2.5 (midpoint of 0-5 scale).

**Wilson Score:**
```
Lower bound of Wilson confidence interval (95% confidence)
```
Conservative approach that penalizes low-rated items with few ratings.

## About This Module

This module was developed using AI assistance (GitHub Copilot - Claude Haiku 4.5). For detailed development history, problem-solving approaches, and technical decisions made during implementation, see [DEVELOPMENT_HISTORY.md](DEVELOPMENT_HISTORY.md). For a complete list of development prompts that guided the project from initial concept through final implementation, see [PROMPT_HISTORY_RAW.md](PROMPT_HISTORY_RAW.md).

## Maintainers

Current maintainers:
- [Martin Postma](https://www.drupal.org/u/lolandese) (lolandese)
