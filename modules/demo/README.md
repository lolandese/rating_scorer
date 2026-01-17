# Rating Scorer Demo Module

A demonstration submodule for the Rating Scorer module that automatically sets up sample content and views for testing and evaluation.

## What It Does

When installed, the Rating Scorer Demo module automatically creates:

### Content Type
- **Article** content type with custom fields

### Fields
- **field_rating** (Decimal): Average rating value (0-5)
- **field_vote_count** (Integer): Number of votes received
- **field_rating_score** (Rating Score): Auto-calculated fair rating score

### Configuration
- **Field Mapping**: Configures the Article content type for Rating Scorer with:
  - Algorithm: Bayesian
  - Rating field: field_rating
  - Vote count field: field_vote_count
  - Bayesian threshold: 10

### Sample Data
5 demo articles with different ratings and vote counts:
1. **Excellent Article on Drupal Best Practices** - 4.9★, 203 votes
2. **Great Tutorial for Beginners** - 4.8★, 156 votes
3. **Good Overview of Rating Systems** - 4.2★, 89 votes
4. **Average Article with Useful Information** - 3.5★, 42 votes
5. **Article Needs More Detail** - 2.1★, 23 votes

### View
- **Articles by Rating Score**: A demo view that displays all articles in a table, sorted by the calculated Rating Score in descending order
- **Path**: `/demo/articles-by-rating`
- **Menu**: Added to the main menu as "Demo: Articles by Rating"

## Installation

1. Enable the module:
   ```bash
   drush en rating_scorer_demo
   ```

2. The module will automatically create all sample content and configuration.

3. Visit `/demo/articles-by-rating` to see the demo view.

## Testing Rating Scorer

Use the demo setup to test:

1. **View ordering by Rating Score**
   - Navigate to `/demo/articles-by-rating`
   - Verify articles are ordered by calculated score (highest first)
   - Notice how the Bayesian algorithm accounts for both rating and vote count

2. **Field mapping configuration**
   - Go to `/admin/config/rating-scorer/field-mappings`
   - Review the Article mapping configuration

3. **Admin dashboard**
   - Visit `/admin/config/rating-scorer`
   - See statistics about the demo content

4. **Calculator**
   - Go to `/admin/config/rating-scorer/calculator`
   - Test different rating/vote combinations to understand the algorithm

5. **Edit and recalculate**
   - Edit an article (`/admin/content`)
   - Change the rating or vote count and save
   - The Rating Score field will automatically recalculate

## Uninstallation

When the module is uninstalled, it automatically removes:
- All demo articles
- The field mapping configuration
- The demo view
- All custom fields (field_rating, field_vote_count, field_rating_score)
- The Article content type

**Warning**: Uninstalling this module will permanently delete all demo data and the Article content type.

## Compatibility

- Drupal 10+
- Drupal 11+
- Requires: Rating Scorer module

## Use Cases

- **Product evaluation**: Try the module before deploying to production
- **Team training**: Set up a demo environment for developers
- **Sandbox testing**: Verify Rating Scorer functionality with realistic data
- **Drupal.org submission**: Provide a demo site for evaluators
