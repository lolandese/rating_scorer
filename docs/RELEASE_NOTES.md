## Rating Scorer 1.0.x - Initial Release

**What's New**

Rating Scorer brings fair statistical rating calculation to Drupal 10 and 11. This initial release includes the complete Bayesian Average algorithm implementation for eliminating rating bias.

### Key Features

- **Bayesian Average Algorithm** - Calculates fair ratings that prevent manipulation by low-volume reviews
- **Configurable Scoring** - Adjust confidence thresholds and baseline ratings to match your content strategy
- **Field Type Integration** - Native "Rating Score" field type for storing calculated scores
- **Block Display** - Rating Scorer Calculator block to display scoring methodology to stakeholders
- **Fivestar Integration** - Works seamlessly with the Fivestar module for complete rating workflows
- **Views Support** - Sort and filter content by fair rating scores
- **Test Coverage** - 31 comprehensive unit tests (100% passing)

### Installation & Setup

1. Install the module via Composer or manual installation
2. Configure settings at `/admin/config/content/rating_scorer` (2 settings: confidence threshold & baseline rating)
3. Add Rating Score fields to content types or use the calculator block
4. Start ranking fairly!

### Requirements

**Required:**

- **Drupal 10.0+** (also compatible with Drupal 11)

**Optional:**

- Votingapi and/or Fivestar modules for full integration

### Code Quality

- Security Audit: 9/9 PASS
- Code Review: 9/9 PASS
- Unit Tests: 31/31 PASS

### Documentation

Complete setup guides and integration examples available in the module's `docs/` folder.

### Feedback & Support

Found a bug or have a feature request? Visit the module's issue queue on Drupal.org.

---

**Version:** 1.0.0  
**Release Date:** January 18, 2026  
**Drupal Compatibility:** 10.0+ and 11.x
