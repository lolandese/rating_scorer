# Rating Scorer - Fivestar/VotingAPI Integration Plan

## Data Storage Discovery

### VotingAPI Table Structure
**Table**: `votingapi_vote`

```
+-------------+------------------+
| Field       | Type             |
+-------------+------------------+
| id          | int(10) unsigned |
| type        | varchar(32)      | (e.g., 'vote')
| uuid        | varchar(128)     |
| entity_type | varchar(64)      | (e.g., 'node')
| entity_id   | int(10)          | (e.g., 116)
| value       | float            | (rating value: 4.5, 100)
| value_type  | varchar(64)      | (e.g., 'percent')
| user_id     | int(10)          |
| timestamp   | int(11)          |
| vote_source | varchar(255)     |
+-------------+------------------+
```

### Fivestar Vote Result API

**Service**: `fivestar.vote_result_manager`

```php
$voteResultManager = Drupal::service('fivestar.vote_result_manager');
$results = $voteResultManager->getResults($entity);

// Returns:
$results = [
  'vote' => [
    'vote_count' => 2,      // Number of votes
    'vote_sum' => 104.5,    // Sum of rating values
    'vote_average' => 52.25 // Average rating (52.25 = 2.45 out of 5 when normalized)
  ]
];
```

## Current Problem

**Rating Scorer** currently:
- Reads vote data from NUMBER FIELDS on the entity (field_vote_count, field_vote_sum, field_rating)
- Does NOT support reading from VotingAPI/Fivestar
- Requires manual field mapping configuration

**Desired Solution**:
1. Auto-detect Fivestar fields when added to entity types
2. Automatically create mappings to use VotingAPI data
3. Support VotingAPI as a native data source (not field-based)
4. Clean up mappings when Fivestar fields are removed

## Implementation Requirements

### 1. Add VotingAPI Support to RatingScoreCalculator

**Current Code Flow**:
```
RatingScoreCalculator::calculateScoreForEntity()
  → reads field_rating value
  → reads field_vote_count value
  → calculates Bayesian score
```

**Needed Flow**:
```
RatingScoreCalculator::calculateScoreForEntity()
  → Check mapping type (FIELD or VOTINGAPI)
  → If VOTINGAPI:
    → Use fivestar.vote_result_manager to get vote_count, vote_sum
    → Calculate average rating = vote_sum / vote_count
  → If FIELD:
    → Read from field values (current behavior)
  → Calculate Bayesian score
  → Store in field_rating_score
```

### 2. Modify Field Mapping Configuration

**Current Mapping Structure**:
```yaml
field_mappings:
  node_article:
    rating_field: field_rating
    vote_count_field: field_vote_count
    vote_sum_field: field_vote_sum
    entity_type: node
    bundle: article
```

**New Mapping Structure**:
```yaml
field_mappings:
  node_article:
    source_type: FIELD  # or VOTINGAPI
    # If FIELD:
    rating_field: field_rating
    vote_count_field: field_vote_count
    vote_sum_field: field_vote_sum
    # If VOTINGAPI:
    vote_field: field_page_rating  # The fivestar field name (for reference)
    entity_type: node
    bundle: article
```

### 3. Hook into Field Lifecycle

**Hooks to Implement**:

```php
hook_field_storage_config_insert(&$field_storage)
  → Detect if field_type == 'fivestar'
  → Auto-create mapping with source_type: VOTINGAPI
  
hook_field_storage_config_delete(&$field_storage)
  → Detect if field_type == 'fivestar'
  → Remove associated mapping
```

### 4. Create Migration/Update Path

Need to handle existing field mappings:
- Check if mapping references field that doesn't exist
- Auto-detect if entity has Fivestar field
- Suggest conversion to VOTINGAPI source

## Implementation Steps

### Phase 1: VotingAPI Support (Core)
- [ ] Add `source_type` field to mapping configuration
- [ ] Extend RatingScoreCalculator to support VOTINGAPI source
- [ ] Extract vote data via fivestar.vote_result_manager
- [ ] Update Bayesian algorithm to work with vote_sum/vote_count
- [ ] Add unit tests for VOTINGAPI source

### Phase 2: Auto-Detection (Hooks)
- [ ] Implement field_storage_config_insert hook
- [ ] Implement field_storage_config_delete hook
- [ ] Auto-create/remove mappings on Fivestar field lifecycle
- [ ] Add logging for auto-created mappings

### Phase 3: UI Updates
- [ ] Update Settings form to show source_type selector
- [ ] Update FieldMappingForm to handle both source types
- [ ] Add guidance text for choosing source type
- [ ] Display which content types have auto-detected Fivestar

### Phase 4: Migration/Cleanup
- [ ] Detect broken mappings (field doesn't exist)
- [ ] Provide drush command to auto-fix mappings
- [ ] Document migration path for existing setups

## Example Test Case

**Setup**:
1. Create "Basic Page" content type
2. Add Fivestar field: `field_page_rating`
3. Create a page and collect votes (e.g., 2 votes: 4.5 and 5.0)

**Expected Behavior**:
1. Rating Scorer automatically creates mapping:
   ```yaml
   node_page:
     source_type: VOTINGAPI
     vote_field: field_page_rating
   ```

2. Vote data available via Fivestar API:
   ```
   vote_count: 2
   vote_sum: 104.5  (or 9.5 if normalized to 0-5)
   vote_average: 52.25 (or 4.75 if normalized)
   ```

3. Bayesian calculation:
   ```
   average_rating = 9.5 / 2 = 4.75
   bayesian_score = (vote_count * average_rating + threshold * default_rating) / (vote_count + threshold)
   bayesian_score = (2 * 4.75 + 10 * 3.5) / (2 + 10) = (9.5 + 35) / 12 = 3.71
   ```

4. Score stored in `field_rating_score`

## Benefits

✅ **Automatic**: No manual configuration needed
✅ **Seamless**: Works with Fivestar out of the box
✅ **Flexible**: Supports both field-based and VotingAPI sources
✅ **Clean**: Auto-cleanup when Fivestar fields removed
✅ **Familiar**: Uses standard Drupal field hooks
