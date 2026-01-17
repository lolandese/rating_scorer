# Rating Scorer Integration Guide

This guide shows how to integrate Rating Scorer with different rating modules and data sources.

## Table of Contents

1. [Fivestar Integration](#fivestar-integration)
2. [Votingapi Integration](#votingapi-integration)
3. [Custom Rating Fields](#custom-rating-fields)
4. [Troubleshooting](#troubleshooting)

---

## Fivestar Integration

Fivestar is a popular Drupal module that provides 5-star rating widgets and stores votes in VotingAPI. Rating Scorer can extract average ratings from Fivestar voting data and calculate fair scores.

### Version-Specific Notes

**Drupal 10**
- Fivestar 8.x-1.0-alpha5 (uses legacy Drupal 8.x versioning scheme)
- Stable alpha release, well-tested
- Suitable for production use

**Drupal 11**
- Fivestar 3.0.x-dev (development version)
- Early development state, actively changing
- Suitable for testing/development only

### How It Works

Rating Scorer integrates with the **VotingAPI** storage layer that Fivestar uses. When users vote via Fivestar stars:
1. Votes are stored in VotingAPI
2. Rating Scorer reads vote aggregates from VotingAPI
3. Fair scores are calculated based on voting volume and average

This decoupling means Rating Scorer can work with votes from any voting widget (Fivestar, Rate, custom code) as long as they store to VotingAPI.

### Prerequisites

- Fivestar 3.0.x-dev module installed and enabled
- VotingAPI module (installed automatically as Fivestar dependency)
- Fivestar fields added to your content type
- A "Rating Score" field added to the content type (will be auto-created if using Field Mapping Wizard)

### Setup Steps

1. **Install Fivestar**
   ```bash
   composer require drupal/fivestar
   drush en fivestar
   ```

2. **Add Fivestar field to content type**
   - Go to **Structure > Content types > [Your Type] > Manage fields**
   - Click **+ Create a new field**
   - Choose field type: **Fivestar**
   - Name it (e.g., `field_rating`)
   - Configure widget as needed (star count, widget type, etc.)
   - Save

3. **Create a Rating Score field** (if not already present)
   - Go to **Structure > Content types > [Your Type] > Manage fields**
   - Click **+ Create a new field**
   - Choose field type: **Rating Score**
   - Name it `field_rating_score`
   - Save

4. **Create field mapping**
   - Go to **Admin > Configuration > Content Authoring > Rating Scorer > Field Mappings**
   - Click **+ Add a field mapping**
   - Fill in:
     - **Label**: "Article Ratings" (or your content type)
     - **Content Type**: Select your content type
     - **Number of Ratings Field**: Select a numeric field containing vote count
       - *Note: Fivestar stores the rating, not the count*
       - You may need a separate field for tracking vote counts
       - Or use Votingapi alongside Fivestar
     - **Average Rating Field**: `field_rating` (your Fivestar field)
     - **Scoring Method**: Bayesian (recommended)
     - **Bayesian Threshold**: 10 (adjust based on your needs)
   - Save

5. **Test it out**
   - Create/edit a content item with the Fivestar field
   - The Rating Score field should auto-populate with a calculated fair score
   - Check the Rating Scorer dashboard to verify

### Example Setup

**Scenario**: You have Articles with Fivestar ratings and you want to rank them fairly.

```
Content Type: Article
Fields:
  - field_title: Text
  - field_rating: Fivestar (1-5 stars)
  - field_rating_count: Integer (manually managed or from another source)
  - field_rating_score: Rating Score (auto-calculated)

Field Mapping Configuration:
  - Average Rating Field: field_rating
  - Number of Ratings Field: field_rating_count
  - Scoring Method: Bayesian Average
  - Bayesian Threshold: 10
```

---

## Votingapi Integration

Votingapi is a powerful voting/rating engine that stores and aggregates votes. Rating Scorer can automatically extract average ratings and vote counts from Votingapi.

### Prerequisites

- Votingapi module installed and enabled
- Votes being recorded via Votingapi
- A "Rating Score" field added to your content type

### Setup Steps

1. **Install Votingapi**
   ```bash
   composer require drupal/votingapi
   drush en votingapi
   ```

2. **Set up voting on your content type**
   - Install and configure a voting UI module (e.g., `votingapi_widgets`, custom votes, etc.)
   - Configure Votingapi to record votes on your content type

3. **Create a Rating Score field**
   - Go to **Structure > Content types > [Your Type] > Manage fields**
   - Click **+ Create a new field**
   - Choose field type: **Rating Score**
   - Name it `field_rating_score`
   - Save

4. **Create field mapping (Votingapi auto-detection)**
   - Go to **Admin > Configuration > Content Authoring > Rating Scorer > Field Mappings**
   - Click **+ Add a field mapping**
   - You should see: **"Rating modules detected: Voting API"** message
   - Fill in:
     - **Label**: "Article Ratings"
     - **Content Type**: Select your content type
     - **Number of Ratings Field**: Look for `[Detected]` fields
       - Rating Scorer attempts to detect vote count fields
       - Or you can create a custom numeric field that's populated via Votingapi data
     - **Average Rating Field**: Select a numeric field or create one
       - The field mapping form will guide you to suitable fields
     - **Scoring Method**: Bayesian (recommended)
     - **Bayesian Threshold**: 10
   - Save

5. **Alternative: Use Votingapi Data Provider Directly** (for advanced use)
   - The `RatingDataProviderManager` service automatically handles Votingapi
   - You can programmatically access vote data:
   ```php
   $provider_manager = \Drupal::service('rating_scorer.rating_data_provider_manager');
   $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

   $avg_rating = $provider_manager->getAverageRating($entity, 'rating');
   $vote_count = $provider_manager->getVoteCount($entity, 'rating');
   ```

6. **Test it out**
   - Create votes on your content via Votingapi
   - Edit the content to trigger score recalculation
   - Check the Rating Score field to see the calculated fair score

### Example Setup

**Scenario**: Community articles with Votingapi-based voting system.

```
Content Type: Article
Votingapi Configuration:
  - Vote type: 'rating'
  - Vote range: 1-5 stars
  - Entity type: node
  - Bundle: article

Fields:
  - field_title: Text
  - field_rating_score: Rating Score (auto-calculated)

Field Mapping Configuration:
  - Number of Ratings Field: (Votingapi auto-provides this)
  - Average Rating Field: (Votingapi auto-provides this)
  - Scoring Method: Wilson Score (for conservative scoring with low vote counts)
  - Bayesian Threshold: 5
```

---

## Custom Rating Fields

If you don't use Fivestar or Votingapi, you can create your own rating fields and use Rating Scorer with them.

### Setup Steps

1. **Create custom numeric fields**
   - Go to **Structure > Content types > [Your Type] > Manage fields**
   - Create field: **Average Rating** (Decimal)
     - Name: `field_avg_rating`
     - Decimal places: 2
     - Min: 0, Max: 5
   - Create field: **Number of Ratings** (Integer)
     - Name: `field_num_ratings`
     - Min: 0

2. **Create a Rating Score field**
   - Go to **Structure > Content types > [Your Type] > Manage fields**
   - Click **+ Create a new field**
   - Choose field type: **Rating Score**
   - Name it `field_rating_score`
   - Save

3. **Populate the rating fields**
   - Manually via content edit forms
   - Via API/programmatically:
   ```php
   $node->field_avg_rating = 4.5;
   $node->field_num_ratings = 150;
   $node->save(); // Rating Score auto-calculates
   ```
   - Via Views bulk operations
   - Via custom modules/imports

4. **Create field mapping**
   - Go to **Admin > Configuration > Content Authoring > Rating Scorer > Field Mappings**
   - Click **+ Add a field mapping**
   - Fill in:
     - **Label**: "Article Fair Scores"
     - **Content Type**: Select your content type
     - **Number of Ratings Field**: `field_num_ratings`
     - **Average Rating Field**: `field_avg_rating`
     - **Scoring Method**: Bayesian
     - **Bayesian Threshold**: 10
   - Save

5. **Verify setup**
   - Create/edit a content item
   - Manually enter rating values in the custom fields
   - The Rating Score field should auto-populate
   - Go to dashboard to verify coverage

### Example Setup

**Scenario**: Simple product ratings stored in custom fields.

```
Content Type: Product
Fields:
  - field_title: Text
  - field_avg_rating: Decimal (0-5, 2 decimals)
  - field_num_ratings: Integer
  - field_rating_score: Rating Score (auto-calculated)

Manual Entry:
  Product A:
    - field_avg_rating: 4.8
    - field_num_ratings: 250
    - field_rating_score: 4.78 (calculated via Bayesian)

  Product B:
    - field_avg_rating: 4.9
    - field_num_ratings: 3
    - field_rating_score: 3.92 (conservative score due to few ratings)
```

### Programmatic Population

```php
<?php

use Drupal\node\Entity\Node;

// Create a node with rating fields
$node = Node::create([
  'type' => 'product',
  'title' => 'Example Product',
  'field_avg_rating' => 4.5,
  'field_num_ratings' => 100,
]);

$node->save();
// Rating Score field auto-calculates on save
echo "Fair Score: " . $node->field_rating_score->value;
```

---

## Troubleshooting

### Rating Score field not calculating

1. **Verify field mapping exists**
   - Go to **Rating Scorer > Field Mappings**
   - Ensure mapping is created for your content type
   - Check that fields are correctly selected

2. **Clear cache**
   ```bash
   drush cr
   ```

3. **Re-save the content**
   - Rating scores calculate on content save
   - Edit and save the node to trigger recalculation

4. **Check configuration**
   - Ensure content type matches the mapping
   - Verify field names are correct
   - Check that numeric fields contain valid data

### "Rating modules detected" message not showing

1. **Install the required module**
   - Fivestar: `composer require drupal/fivestar && drush en fivestar`
   - Votingapi: `composer require drupal/votingapi && drush en votingapi`

2. **Clear cache after install**
   ```bash
   drush cr
   ```

3. **Verify module is enabled**
   - Go to **Extend** and search for the module
   - Ensure it shows as enabled (green checkmark)

### Rating Score field not populated despite existing data

1. **Trigger recalculation**
   - Go to **Rating Scorer > Field Mappings**
   - Click **Edit** on your mapping
   - Click **Save** - this recalculates scores for all entities
   - Check the message: "Rating scores have been recalculated for X items"

2. **Check field values are numeric**
   - Edit a content item
   - Verify rating and count fields contain valid numbers
   - Invalid/empty values are skipped

3. **Verify score field exists**
   - Go to **Structure > Content types > Manage fields**
   - Ensure "Rating Score" field is present
   - If missing, the mapping won't work

### Different scores than expected

1. **Understand the algorithm**
   - **Weighted**: Favors high volume, simple to understand
   - **Bayesian**: Conservative with low ratings, prevents gaming
   - **Wilson**: Most conservative, uses confidence intervals
   - See **Rating Scorer > Calculator** for interactive examples

2. **Adjust Bayesian Threshold**
   - Lower threshold = higher scores for items with few ratings
   - Higher threshold = more conservative scoring
   - Typical values: 5-20 depending on your needs

3. **Check data quality**
   - Run the **Rating Scorer Dashboard**
   - Review coverage statistics
   - Identify content with missing or invalid data

---

## Additional Resources

- **Interactive Calculator**: Go to **Admin > Configuration > Content Authoring > Rating Scorer > Calculator** to test algorithms before deployment
- **Reusable Block**: Add the calculator widget to any page via **Block Layout**
- **API Usage**: See code examples above for programmatic access
- **Views Integration**: Rating Score fields can be used in Views for sorting and filtering

