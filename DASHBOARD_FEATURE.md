# Status Dashboard Feature

## Overview

The **Status Dashboard** provides a centralized view of your rating scorer field mappings and data quality metrics. It gives you quick insights into how many entities are rated, coverage percentages, and when data was last updated.

## Access the Dashboard

Navigate to: **Admin > Configuration > Rating Scorer**

Or directly at: `/admin/config/rating-scorer`

The Dashboard tab appears first in the tab navigation (before Field Mappings, Calculator, and Defaults).

## Dashboard Sections

### 1. Overall Statistics

The statistics section displays four key metrics:

- **Field Mappings**: Total number of configured field mappings
- **Total Entities**: Combined count of all entities across all content types
- **Rated Entities**: Number of entities that have been assigned a rating score
- **Coverage**: Percentage of entities with rating scores

These statistics update in real-time based on your current data.

### 2. Field Mappings Status

A table displaying detailed status for each field mapping:

| Column | Description |
|--------|-------------|
| **Content Type** | The content type this mapping applies to |
| **Scoring Method** | Which algorithm is used (Bayesian, Weighted, Wilson) |
| **Total Entities** | Number of entities in this content type |
| **With Ratings** | How many have been assigned a rating score |
| **Coverage** | Visual progress bar showing percentage of rated entities |
| **Last Updated** | When the most recent rating was calculated |
| **Actions** | Edit button to modify the mapping |

### 3. Data Quality Insights

Contextual messages based on your coverage percentage:

- **Low Coverage** (< 50%): Warning that less than half of entities have ratings
- **Partial Coverage** (50-99%): Status message indicating new entities will be auto-rated
- **Complete Coverage** (100%): Confirmation that all entities have rating scores

## How Ratings Are Calculated

Ratings are **automatically calculated** when:
- A new entity is created
- An existing entity is updated
- A field mapping is saved

The "Last Updated" column shows the timestamp from the most recently modified entity in that content type.

## Tips for Optimal Coverage

1. **New Content**: Ratings are calculated automatically as new entities are created, so coverage will naturally increase over time.

2. **Bulk Updates**: If you modify a field mapping, only entities that are subsequently updated will have recalculated ratings. To rate existing entities, you may need to touch them (save without changes).

3. **Verify Mappings**: If coverage is low, check that:
   - Field mappings are correctly configured
   - Source fields (number of ratings, average rating) contain valid data
   - The rating_score field exists on the content type

## Service Architecture

### RatingScorerDashboardService

Located in `src/Service/RatingScorerDashboardService.php`

Methods:

- `getFieldMappingsWithStatus()`: Returns all mappings with their status and metrics
- `getDashboardStatistics()`: Returns aggregate statistics (coverage, total entities, etc.)
- `getContentTypeEntityCount($bundle)`: Count entities for a bundle
- `getRatingScoreFieldCount($bundle)`: Count entities with rating scores
- `getLastRecalculationTime($bundle)`: Get human-readable last update time

The service is injected into `RatingScorerController::dashboard()` and handles all data gathering.

## Template and Styling

- **Template**: `templates/rating-scorer-dashboard.html.twig`
- **Styles**: `css/rating-scorer-dashboard.css`
- **Library**: `rating_scorer/dashboard` (registered in `rating_scorer.libraries.yml`)

The dashboard is fully responsive and works on mobile, tablet, and desktop devices.

## Integration with Other Features

The dashboard complements:
- **Field Mappings**: Configure which fields to use for calculations
- **Calculator**: Test scoring algorithms before applying them
- **Defaults**: Set module-wide default values

All three features work together to provide a complete rating scoring solution.

## Future Enhancements

Potential improvements for the dashboard:

- **Historical Charts**: Show coverage trends over time
- **Bulk Recalculation**: Trigger recalculation of all entities at once
- **Export Data**: Download dashboard metrics as CSV
- **Alerts**: Notify when coverage drops or mappings are misconfigured
- **Field Health Checks**: Validate that source fields contain valid data
