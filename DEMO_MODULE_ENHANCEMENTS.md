# Demo Module Enhancements - Fivestar Integration

## Overview

The Rating Scorer Demo module has been enhanced to intelligently detect if the Fivestar module is enabled and automatically create appropriate demo content and field mappings.

## Features

### 1. Fivestar Module Detection

The demo module now includes a helper function `_rating_scorer_demo_fivestar_enabled()` that checks if the Fivestar module is installed and enabled using:

```php
Drupal::moduleHandler()->moduleExists('fivestar')
```

### 2. Conditional Field Creation

#### Standard Rating Fields (Always Created)
- `field_rating` - Average rating (decimal field)
- `field_vote_count` - Number of votes (integer field)
- `field_rating_score` - Auto-calculated rating score (computed field)

These fields work with the **standard FIELD source type** mapping and demonstrate basic Rating Scorer functionality.

#### Fivestar Fields (Conditional)
If Fivestar is detected, the demo module additionally creates:
- `field_article_rating` - Fivestar 5-star rating field with these settings:
  - 5 stars
  - Allow clearing selections
  - Allow re-voting
  
This field works with the **VOTINGAPI source type** mapping, demonstrating integration with Fivestar's vote storage system.

### 3. Automatic Field Mappings

#### Standard Mapping: `node_article`
```yaml
Content Type: article
Source Type: FIELD
Rating Field: field_rating
Vote Count Field: field_vote_count
Scoring Method: Bayesian (threshold: 10)
```

#### Fivestar Mapping: `node_article_fivestar` (Conditional)
```yaml
Content Type: article
Source Type: VOTINGAPI
Vote Field: field_article_rating
Scoring Method: Bayesian (threshold: 10)
```

The Fivestar mapping reads votes from the VotingAPI storage system, allowing the Bayesian algorithm to calculate scores based on Fivestar user ratings.

### 4. Demo View

The module includes a pre-configured view (`articles_by_rating_demo`) that:
- Displays all articles
- Shows rating scores (sorted descending by default)
- Includes vote count and average rating columns
- Provides a clean table display
- Available at: `/articles_by_rating_demo`

### 5. Sample Data

The demo module creates 5 sample articles demonstrating the inverse relationship between rating and vote count:

| Title | Average Rating | Vote Count | Demonstrates |
|-------|----------------|-----------|--------------|
| Lorem Ipsum... | 4.8 | 5 | High rating, low votes |
| Consectetur... | 4.6 | 15 | Medium-high rating, medium votes |
| Duis Aute... | 4.4 | 50 | Medium rating, medium-high votes |
| Voluptate... | 4.2 | 200 | Medium rating, high votes |
| Fugiat... | 4.0 | 500 | Lower rating, very high votes |

The Bayesian scoring algorithm prioritizes the 4.0 rating with 500 votes over the 4.8 rating with only 5 votes, demonstrating the importance of vote count reliability.

## Installation Message

When the demo module is installed, you'll see:

**Without Fivestar:**
```
✓ Rating Scorer Demo module installed successfully!
✓ Fivestar module not enabled. Enable Fivestar to test VotingAPI integration.
```

**With Fivestar:**
```
✓ Rating Scorer Demo module installed successfully!
✓ Fivestar integration enabled! Check the Fivestar field on articles.
```

## Content Type Behavior

### Current Design: Hard-Coded to 'article'

The demo module **hard-codes** the Article content type. If the Article content type doesn't exist:
- It is automatically created during module installation
- It includes standard Drupal fields (title, body, etc.)
- Fields are added with appropriate settings

### Design Rationale

The hard-coded approach was chosen for:
- **Reliability**: Guaranteed to work across all installations
- **Simplicity**: No dynamic detection complexity
- **Consistency**: Same behavior on every site

### Future Enhancement Options

If dynamic content type detection becomes necessary:
1. Query available content types on installation
2. Prefer 'article' if available
3. Fall back to 'page' or other common types
4. Allow configuration via settings form

## Uninstallation

The demo module's uninstall hook cleanly removes:
- All demo content (sample articles)
- All created fields (both standard and Fivestar)
- The Article content type
- All field mappings (both standard and Fivestar)
- The pre-configured view (handled by config system)

## Testing Scenarios

### Scenario 1: Without Fivestar
1. Install Rating Scorer and Rating Scorer Demo
2. Navigate to `/articles_by_rating_demo`
3. See articles sorted by calculated Bayesian scores
4. Verify ratings are calculated from field_rating and field_vote_count

### Scenario 2: With Fivestar
1. Install Fivestar module
2. Reinstall Rating Scorer Demo
3. See Fivestar field on article creation/edit forms
4. Create votes via Fivestar interface
5. Verify Bayesian scores are calculated from VotingAPI data

## File References

- **Install/Uninstall Logic**: [rating_scorer_demo.install](modules/demo/rating_scorer_demo.install)
- **View Configuration**: [modules/demo/config/install/views.view.articles_by_rating_demo.yml](modules/demo/config/install/views.view.articles_by_rating_demo.yml)
- **Module Info**: [modules/demo/rating_scorer_demo.info.yml](modules/demo/rating_scorer_demo.info.yml)

## Key Implementation Details

1. **Non-blocking Fivestar Detection**: If Fivestar isn't available, the demo still works with standard fields
2. **Idempotent Field Creation**: Fields are only created if they don't already exist
3. **Proper Cleanup**: Uninstall hook removes both standard and Fivestar components
4. **Source Type Handling**: Each mapping explicitly sets its source type (FIELD or VOTINGAPI)
5. **Config-based View**: View configuration is handled by config system, not programmatic creation
