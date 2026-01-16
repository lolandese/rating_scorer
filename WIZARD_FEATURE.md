# Field Mapping Wizard Feature

## Overview

This feature branch (`feature/field-mapping-wizard`) implements a guided step-by-step wizard for creating field mappings with:
- Automatic detection of numeric fields on content types
- Auto-detection of available Rating Score fields
- Option to automatically create missing Rating Score fields
- Clear, user-friendly 4-step wizard process

## What's New

### 1. Field Detection Service (`src/Service/FieldDetectionService.php`)

Automatically discovers numeric fields on content types that can be used for ratings:

- `detectNumericFields($bundle)` - Finds all numeric fields (integer, decimal, float) on a content type
- `hasRatingScoreField($bundle)` - Checks if Rating Score field exists
- `getAvailableContentTypes()` - Lists all content types
- `getContentTypesWithNumericFields()` - Lists only content types with numeric fields

### 2. Field Creation Service (`src/Service/FieldCreationService.php`)

Automatically creates Rating Score fields on demand:

- `createRatingScoreFieldIfNeeded($bundle)` - Creates field storage and instance if missing
- Automatically configures default form and view displays
- Handles errors gracefully without breaking the wizard

### 3. Field Mapping Wizard Form (`src/Form/FieldMappingWizardForm.php`)

Multi-step guided form with 4 steps:

**Step 1: Select Content Type**
- Shows only content types with numeric fields
- Helpful intro message

**Step 2: Select Fields**
- Auto-detected numeric fields displayed as options
- User selects which fields contain:
  - Number of ratings/reviews
  - Average rating value

**Step 3: Rating Score Field Configuration**
- Shows status of Rating Score field
- Offers to create field if missing
- Allows user to choose scoring method (Bayesian, Weighted, Wilson)
- Configures Bayesian threshold if selected
- Shows help text for each algorithm

**Step 4: Review & Create**
- Summary of all settings
- User confirms before creation
- Creates config entity and optionally creates field

## User Experience Improvements

### Before
- Plain form with all fields visible at once
- No guidance on which fields to select
- Had to manually create Rating Score field first
- No validation until form submission

### After
- Clear step-by-step wizard (4 steps)
- Fields auto-detected and presented as options
- User can choose to auto-create Rating Score field
- Back button to revisit previous steps
- Review step before final creation
- Helpful descriptions for each algorithm

## Integration Points

### Routing
New route added: `/admin/config/rating-scorer/field-mapping/wizard`

### Updated Files
- `rating_scorer.routing.yml` - Added wizard route
- `rating_scorer.services.yml` - Registered two new services
- `src/RatingScorerFieldMappingListBuilder.php` - Updated link to use wizard

### Services Registered
- `rating_scorer.field_detection` - FieldDetectionService
- `rating_scorer.field_creation` - FieldCreationService

## How to Test

1. Navigate to `/admin/config/rating-scorer`
2. Click "New Field Mapping" button
3. Follow the 4-step wizard
4. Field mapping will be created with optional auto-created Rating Score field

## Future Enhancements

- Add algorithm preview calculator in Step 3
- Show example scores for detected fields
- Batch recalculation of existing nodes after mapping creation
- Validation that selected fields are actually numeric
- Handling of multi-select and entity_reference fields as alternatives

## Branch Status

- Feature branch: `feature/field-mapping-wizard`
- Ready for: Testing, code review, and merge to main
- No breaking changes to existing functionality

