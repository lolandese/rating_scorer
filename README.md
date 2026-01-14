# Rating Scorer

## Introduction

Rating Scorer provides a Views field integration to calculate and display fair rating scores in your content listings. The module prevents items with few reviews from dominating rankings by combining average rating with review volume using sophisticated algorithms. Rank and sort items equitably by implementing proven scoring methods (Weighted, Bayesian, and Wilson Score) used by major platforms like IMDB and Reddit. Includes an interactive calculator and reusable block for testing and demonstration.

## Primary Feature: Fair Rating Scores in Views

The main purpose of this module is to calculate equitable rating scores in Views displays that prevent unfair rankings. Without fair scoring:
- A single 5-star review can rank an item above products with 100 4-star reviews
- New items with one positive review appear above established quality content
- Extreme outliers skew rankings unpredictably

Add a "Rating Score" field to any Views display to automatically compute fair scores based on your content's rating data. This allows you to:

- **Prevent vote manipulation** - Items with few reviews are scored conservatively
- **Reward quality over hype** - Established items with many ratings rank fairly
- **Use proven algorithms** - Bayesian (IMDB-style), Weighted, and Wilson Score methods
- **Configure per-view** - Different scoring strategies for different content types
- **Sort fairly** - Display and sort items by calculated score for equitable rankings

See [Using in Views](#using-in-views) section below for detailed setup instructions.

## Features

- **Views Field Handler** - Calculate and display fair rating scores directly in Views displays with configurable fields and algorithms
  - **Weighted Score** - Logarithmic weighting favoring items with many ratings
  - **Bayesian Average** - IMDB-style scoring that requires confidence through volume, preventing items with few ratings from ranking unfairly high
  - **Wilson Score** - Confidence interval approach used by Reddit and others, conservative scoring for low-review items

- **Interactive Calculator Interface** - Real-time visualization at `/admin/config/rating-scorer/calculator` for testing algorithms

- **Reusable Block** - Place the calculator on any page via the block layout system

- **Configurable Defaults** - Site administrators can set default parameters including minimum ratings threshold for Bayesian scoring

- **Tabbed Interface** - Settings and Calculator organized in intuitive tabs

- **Granular Permissions** - Single "Administer rating scorer" permission controls access to all functionality

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

### Using in Views (Primary Use Case)

The main workflow for this module is integrating fair calculated rating scores into your Views displays to prevent unfair rankings:

1. Create or edit a Views display that contains your rating data (number of ratings and average rating)
2. Add a new field and search for **"Rating Score"**
3. Configure the field settings:
   - **Number of Ratings Field** - Select the field containing the count of ratings
   - **Average Rating Field** - Select the field containing the average rating value
   - **Scoring Method** - Choose your fairness approach:
     - **Weighted Score** - Simple approach balancing quality and quantity with logarithmic weighting
     - **Bayesian Average** - **Recommended for fairness** - Prevents items with few ratings from ranking unfairly high by applying a prior expectation
     - **Wilson Score** - Conservative confidence-interval approach, heavily penalizes low-review items
   - **Minimum Ratings Threshold** - (For Bayesian only) Set the minimum ratings needed to reach high scores
4. Place this field in your view and configure sorting/filtering as needed
5. Save the view

**Example:** With Bayesian Average and a 10-rating threshold, an item with 1 five-star review will score ~2.8, while an item with 100 four-star reviews scores ~3.98—preventing the single review from dominating rankings.

This allows you to display a calculated "Rating Score" column that automatically ranks items fairly based on your chosen algorithm, without modifying your underlying data.

### Basic Settings

1. Navigate to **Administration > Configuration > Content authoring > Rating Scorer** (Settings tab)
2. Configure default values for:
   - Minimum ratings threshold (affects Bayesian average)
   - Default rating value
   - Default number of ratings
   - Default scoring method
3. Save configuration

### Testing with Calculator

For testing and demonstrating different scoring algorithms before adding them to Views:

1. Navigate to **Administration > Configuration > Content authoring > Rating Scorer > Calculator** (Calculator tab)
2. Adjust the inputs to see how different values affect the final score
3. Switch between scoring methods to compare results

### Placing as a Block

For additional visibility or demonstration purposes, place the calculator on any page:

1. Navigate to **Administration > Structure > Block layout**
2. Click "Place block" in your desired region
3. Search for "Rating Scorer Calculator" and place the block
4. Configure block visibility settings as needed

### Using in Views

1. Create or edit a Views display
2. Add a field and search for "Rating Score"
3. Configure the field settings:
   - Select the field containing number of ratings
   - Select the field containing average rating
   - Choose the scoring method
   - If using Bayesian, set the minimum ratings threshold
4. Save the view

### Sorting Behavior

The "Rating Score" field can be used as a sort criterion in Views. **Important:** Due to the calculated nature of this field, sorting is applied per-page rather than globally across all results:

- Results within each page are sorted by rating score (globally if only 1 page)
- When paginating, each page shows its items sorted by rating score
- For true global sorting across all pages, consider:
  - Setting a high items-per-page limit to show most results on one page
  - Using a custom view with `hook_views_data_alter()` to expose a materialized score column
  - Pre-calculating scores into a database field via a batch process

This limitation is inherent to Views' architecture when sorting by calculated/virtual fields that don't exist in the database.

## Testing

The module includes PHPUnit tests for both unit and functional testing:

```bash
cd /home/martinus/ddev-projects/green
ddev exec bash -c 'export SIMPLETEST_DB="mysql://db:db@db:3306/db" && export SIMPLETEST_BASE_URL="http://localhost" && php vendor/bin/phpunit --configuration web/modules/custom/rating_scorer/phpunit.xml'
```

Tests cover:
- User creation with proper permissions
- Home page HTTP 200 response
- Module functionality validation

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
