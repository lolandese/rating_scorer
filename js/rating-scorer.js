(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.ratingScorer = {
    attach: function (context, settings) {
      const container = document.getElementById('rating-scorer-app');
      if (!container || container.dataset.initialized) {
        return;
      }
      container.dataset.initialized = 'true';

      const config = drupalSettings.ratingScorer || {};
      const minRatings = config.defaultMinimumRatings || 1;
      const bayesianAssumedAverage = config.bayesianAssumedAverage || 3.5;
      const defaultRating = config.defaultRating || 4.5;
      const defaultNumRatings = config.defaultNumRatings || 100;
      const defaultScenarioRatingDeviation = config.scenarioRatingDeviation || 5;
      const defaultScenarioReviewsDeviation = config.scenarioReviewsDeviation || 30;

      // Create the calculator interface
      container.innerHTML = `
        <div class="rating-scorer-container">
          <h3 class="impact-section-title">${Drupal.t('Impact of Rating Changes on Scores')}</h3>
          <p class="scenario-intro">${Drupal.t('Compare how different rating patterns affect each scoring method:')}</p>

          <div class="rating-scorer-main">
            <div class="rating-scorer-controls">
              <div class="control-group">
                <label for="rating-input">${Drupal.t('Average Rating')}</label>
                <div class="slider-with-buttons">
                  <button class="adjust-btn adjust-down" data-field="rating" data-delta="-0.01" title="${Drupal.t('Decrease rating')}">−</button>
                  <input type="number" id="rating-input" min="0" max="5" step="0.01" value="${defaultRating.toFixed(2)}" title="${Drupal.t('Average rating (0-5)')}">
                  <span class="input-unit">/5</span>
                  <button class="adjust-btn adjust-up" data-field="rating" data-delta="0.01" title="${Drupal.t('Increase rating')}">+</button>
                </div>
              </div>

              <div class="control-group">
                <label for="num-ratings-input">${Drupal.t('Number of Ratings')}</label>
                <div class="slider-with-buttons">
                  <button class="adjust-btn adjust-down" data-field="num-ratings" data-delta="-10" title="${Drupal.t('Decrease count')}">−</button>
                  <input type="number" id="num-ratings-input" min="0" max="1000" step="1" value="${defaultNumRatings}" title="${Drupal.t('Number of ratings')}">
                  <button class="adjust-btn adjust-up" data-field="num-ratings" data-delta="10" title="${Drupal.t('Increase count')}">+</button>
                </div>
              </div>

              <div class="control-group">
                <label for="min-ratings-input">${Drupal.t('Min. Ratings Threshold')}</label>
                <p class="help-text">${Drupal.t('(Bayesian only)')}</p>
                <div class="slider-with-buttons">
                  <button class="adjust-btn adjust-down" data-field="min-ratings" data-delta="-1" title="${Drupal.t('Decrease threshold')}">−</button>
                  <input type="number" id="min-ratings-input" min="1" max="100" step="1" value="${minRatings}" title="${Drupal.t('Minimum ratings threshold')}">
                  <button class="adjust-btn adjust-up" data-field="min-ratings" data-delta="1" title="${Drupal.t('Increase threshold')}">+</button>
                </div>
              </div>

              <div class="control-group">
                <label for="scenario-rating-dev-input">${Drupal.t('Rating Deviation')}</label>
                <div class="slider-with-buttons">
                  <button class="adjust-btn adjust-down" data-field="scenario-rating-dev" data-delta="-1" title="${Drupal.t('Decrease deviation')}">−</button>
                  <input type="number" id="scenario-rating-dev-input" min="0" max="100" step="1" value="${Math.round(defaultScenarioRatingDeviation)}" title="${Drupal.t('Scenario rating deviation')}">
                  <span class="input-unit">%</span>
                  <button class="adjust-btn adjust-up" data-field="scenario-rating-dev" data-delta="1" title="${Drupal.t('Increase deviation')}">+</button>
                </div>
              </div>

              <div class="control-group">
                <label for="scenario-reviews-dev-input">${Drupal.t('Reviews Deviation')}</label>
                <div class="slider-with-buttons">
                  <button class="adjust-btn adjust-down" data-field="scenario-reviews-dev" data-delta="-1" title="${Drupal.t('Decrease deviation')}">−</button>
                  <input type="number" id="scenario-reviews-dev-input" min="0" max="100" step="1" value="${Math.round(defaultScenarioReviewsDeviation)}" title="${Drupal.t('Scenario reviews deviation')}">
                  <span class="input-unit">%</span>
                  <button class="adjust-btn adjust-up" data-field="scenario-reviews-dev" data-delta="1" title="${Drupal.t('Increase deviation')}">+</button>
                </div>
              </div>
            </div>

            <div class="rating-scorer-scenario-comparison">
              <table class="scenario-comparison-table">
                <thead>
                  <tr>
                    <th colspan="3" class="scenario-group">${Drupal.t('Scenario Details')}</th>
                    <th colspan="3" class="methods-group">${Drupal.t('Scoring Methods')}</th>
                  </tr>
                  <tr>
                    <th>${Drupal.t('Scenario')}</th>
                    <th>${Drupal.t('Rating')}</th>
                    <th>${Drupal.t('Reviews')}</th>
                    <th>${Drupal.t('Weighted')}</th>
                    <th><span id="bayesian-header" class="bayesian-header-text">${Drupal.t('Bayesian')}</span></th>
                    <th>${Drupal.t('Wilson')}</th>
                  </tr>
                </thead>
              <tbody>
                <tr class="current-row">
                  <td><strong>${Drupal.t('Current Input')}</strong></td>
                  <td><strong id="scenario-current-rating">4.50</strong>/5</td>
                  <td><strong id="scenario-current-reviews">100</strong></td>
                  <td><strong id="scenario-current-weighted">0.00</strong></td>
                  <td class="recommended"><strong id="scenario-current-bayesian">0.00</strong></td>
                  <td><strong id="scenario-current-wilson">0.00</strong></td>
                </tr>
                <tr class="higher-row">
                  <td><strong>${Drupal.t('Higher Rating')}</strong><br><span class="change" id="higher-scenario-subtitle">+5% rating, -30% reviews</span></td>
                  <td><strong id="scenario-higher-rating">4.73</strong>/5</td>
                  <td><strong id="scenario-higher-reviews">70</strong></td>
                  <td><strong id="scenario-higher-weighted">0.00</strong></td>
                  <td class="recommended"><strong id="scenario-higher-bayesian">0.00</strong></td>
                  <td><strong id="scenario-higher-wilson">0.00</strong></td>
                </tr>
                <tr class="lower-row">
                  <td><strong>${Drupal.t('More Reviews')}</strong><br><span class="change" id="lower-scenario-subtitle">-5% rating, +30% reviews</span></td>
                  <td><strong id="scenario-lower-rating">4.27</strong>/5</td>
                  <td><strong id="scenario-lower-reviews">130</strong></td>
                  <td><strong id="scenario-lower-weighted">0.00</strong></td>
                  <td class="recommended"><strong id="scenario-lower-bayesian">0.00</strong></td>
                  <td><strong id="scenario-lower-wilson">0.00</strong></td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="threshold-row">
                  <td colspan="3" class="threshold-label">${Drupal.t('Min. Ratings Threshold:')}</td>
                  <td class="threshold-value">${Drupal.t('N/A')}</td>
                  <td class="threshold-value recommended"><strong id="threshold-value">${minRatings}</strong></td>
                  <td class="threshold-value">${Drupal.t('N/A')}</td>
                </tr>
                <tr class="assumed-average-row">
                  <td colspan="3" class="threshold-label">${Drupal.t('Assumed Average:')} <a href="/admin/config/rating-scorer/settings" class="configure-link" title="${Drupal.t('Configure')}">${Drupal.t('(configure)')}</a></td>
                  <td class="threshold-value">${Drupal.t('N/A')}</td>
                  <td class="threshold-value recommended"><strong id="assumed-average-value">${bayesianAssumedAverage.toFixed(1)}</strong></td>
                  <td class="threshold-value">${Drupal.t('N/A')}</td>
                </tr>
                <tr class="table-footer">
                  <td colspan="6" class="footer-text">★ = Highest score in that method column</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="rating-scorer-comparison">
          <h3>${Drupal.t('About the Scoring Methods')}</h3>
          <table class="comparison-table">
            <thead>
              <tr>
                <th>${Drupal.t('Method')}</th>
                <th>${Drupal.t('Description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>${Drupal.t('Weighted Score')}</strong></td>
                <td class="desc-cell">${Drupal.t('Multiplies the average rating by the logarithm of the number of ratings. This gives higher scores to items with both good ratings and many reviews.')}<br><br><em>${Drupal.t('Use case: Less common, but useful for simple content discovery scenarios.')}</em></td>
              </tr>
              <tr class="recommended">
                <td><strong>${Drupal.t('Bayesian Average')}</strong> <span class="recommended-badge">★ ${Drupal.t('Recommended')}</span></td>
                <td class="desc-cell">${Drupal.t('Blends the actual rating with an assumed average (') + bayesianAssumedAverage.toFixed(1) + Drupal.t('), weighted by the number of ratings. Requires more ratings to pull away from the average. Configurable via the Minimum Ratings Threshold.')}<br><br><em>${Drupal.t('Use case: Platforms like IMDB, Goodreads, and others that want to prevent new items with few reviews from ranking too high.')}</em></td>
              </tr>
              <tr>
                <td><strong>${Drupal.t('Wilson Score')}</strong></td>
                <td class="desc-cell">${Drupal.t('A confidence-based approach that calculates the lower bound of a confidence interval. This naturally handles uncertainty from low rating counts. Most conservative method.')}<br><br><em>${Drupal.t('Use case: Platforms like Reddit and Hacker News that need confidence-based ranking with limited vote counts.')}</em></td>
              </tr>
            </tbody>
          </table>
          <p class="scoring-note"><strong>${Drupal.t('Note:')}</strong> ${Drupal.t('Some platforms use hybrid approaches combining multiple strategies. For example, Airbnb, Booking.com, and YouTube use weighted averages with recency weighting, credibility scoring, user engagement metrics, and verification factors to create more sophisticated ranking systems than the three basic methods shown here.')}</p>
        </div>
      </div>
      `;

      const ratingInput = document.getElementById('rating-input');
      const numRatingsInput = document.getElementById('num-ratings-input');
      const minRatingsInput = document.getElementById('min-ratings-input');
      const scenarioRatingDevInput = document.getElementById('scenario-rating-dev-input');
      const scenarioReviewsDevInput = document.getElementById('scenario-reviews-dev-input');
      const bayesianHeader = document.getElementById('bayesian-header');
      const thresholdValue = document.getElementById('threshold-value');
      const assumedAverageValue = document.getElementById('assumed-average-value');
      const higherScenarioSubtitle = document.getElementById('higher-scenario-subtitle');
      const lowerScenarioSubtitle = document.getElementById('lower-scenario-subtitle');

      // Scenario elements
      const scenarioCurrentRating = document.getElementById('scenario-current-rating');
      const scenarioCurrentReviews = document.getElementById('scenario-current-reviews');
      const scenarioCurrentWeighted = document.getElementById('scenario-current-weighted');
      const scenarioCurrentBayesian = document.getElementById('scenario-current-bayesian');
      const scenarioCurrentWilson = document.getElementById('scenario-current-wilson');

      const scenarioHigherRating = document.getElementById('scenario-higher-rating');
      const scenarioHigherReviews = document.getElementById('scenario-higher-reviews');
      const scenarioHigherWeighted = document.getElementById('scenario-higher-weighted');
      const scenarioHigherBayesian = document.getElementById('scenario-higher-bayesian');
      const scenarioHigherWilson = document.getElementById('scenario-higher-wilson');

      const scenarioLowerRating = document.getElementById('scenario-lower-rating');
      const scenarioLowerReviews = document.getElementById('scenario-lower-reviews');
      const scenarioLowerWeighted = document.getElementById('scenario-lower-weighted');
      const scenarioLowerBayesian = document.getElementById('scenario-lower-bayesian');
      const scenarioLowerWilson = document.getElementById('scenario-lower-wilson');

      function updateScenarioSubtitleWithAnimation(element, ratingDev, reviewsDev, isHigher) {
        // Build HTML with percentage values wrapped in spans for styling
        const ratingPercent = isHigher ? `+${ratingDev}` : `-${ratingDev}`;
        const reviewsPercent = isHigher ? `-${reviewsDev}` : `+${reviewsDev}`;

        element.innerHTML = `<span class="dev-value">${ratingPercent}%</span> rating, <span class="dev-value">${reviewsPercent}%</span> reviews`;

        // Add highlight animation to all values
        const valuesToHighlight = element.querySelectorAll('.dev-value');
        valuesToHighlight.forEach(span => {
          span.classList.remove('highlight-pulse');
          // Trigger reflow to restart animation
          void span.offsetWidth;
          span.classList.add('highlight-pulse');
        });
      }

      function calculateScore() {
        const rating = parseFloat(ratingInput.value);
        const numRatings = parseInt(numRatingsInput.value);
        const minRatings = parseInt(minRatingsInput.value);
        const scenarioRatingDev = parseFloat(scenarioRatingDevInput.value);
        const scenarioReviewsDev = parseFloat(scenarioReviewsDevInput.value);

        // Calculate all three methods for current input
        const weightedScore = calculateWeightedScore(rating, numRatings);
        const bayesianScore = calculateBayesianScore(rating, numRatings, minRatings);
        const wilsonScore = calculateWilsonScore(rating, numRatings);

        // Calculate scenarios with configurable deviations
        const higherRating = Math.min(5, rating * (1 + scenarioRatingDev / 100));
        const higherReviews = Math.max(0, Math.round(numRatings * (1 - scenarioReviewsDev / 100)));
        const lowerRating = Math.max(0, rating * (1 - scenarioRatingDev / 100));
        const lowerReviews = Math.max(0, Math.round(numRatings * (1 + scenarioReviewsDev / 100)));

        const higherWeighted = calculateWeightedScore(higherRating, higherReviews);
        const higherBayesian = calculateBayesianScore(higherRating, higherReviews, minRatings);
        const higherWilson = calculateWilsonScore(higherRating, higherReviews);

        const lowerWeighted = calculateWeightedScore(lowerRating, lowerReviews);
        const lowerBayesian = calculateBayesianScore(lowerRating, lowerReviews, minRatings);
        const lowerWilson = calculateWilsonScore(lowerRating, lowerReviews);

        // Update scenario subtitles dynamically with highlight animation
        updateScenarioSubtitleWithAnimation(higherScenarioSubtitle, scenarioRatingDev, scenarioReviewsDev, true);
        updateScenarioSubtitleWithAnimation(lowerScenarioSubtitle, scenarioRatingDev, scenarioReviewsDev, false);

        // Update scenario displays
        scenarioCurrentRating.textContent = rating.toFixed(2);
        scenarioCurrentReviews.textContent = numRatings;
        scenarioCurrentWeighted.textContent = weightedScore.toFixed(2);
        scenarioCurrentBayesian.textContent = bayesianScore.toFixed(2);
        scenarioCurrentWilson.textContent = wilsonScore.toFixed(2);

        scenarioHigherRating.textContent = higherRating.toFixed(2);
        scenarioHigherReviews.textContent = higherReviews;
        scenarioHigherWeighted.textContent = higherWeighted.toFixed(2);
        scenarioHigherBayesian.textContent = higherBayesian.toFixed(2);
        scenarioHigherWilson.textContent = higherWilson.toFixed(2);

        scenarioLowerRating.textContent = lowerRating.toFixed(2);
        scenarioLowerReviews.textContent = lowerReviews;
        scenarioLowerWeighted.textContent = lowerWeighted.toFixed(2);
        scenarioLowerBayesian.textContent = lowerBayesian.toFixed(2);
        scenarioLowerWilson.textContent = lowerWilson.toFixed(2);

        // Highlight highest and lowest scores in each method column
        highlightScoreExtremes('weighted', [weightedScore, higherWeighted, lowerWeighted]);
        highlightScoreExtremes('bayesian', [bayesianScore, higherBayesian, lowerBayesian]);
        highlightScoreExtremes('wilson', [wilsonScore, higherWilson, lowerWilson]);
      }

      function highlightScoreExtremes(method, scores) {
        const elements = method === 'weighted'
          ? [scenarioCurrentWeighted, scenarioHigherWeighted, scenarioLowerWeighted]
          : method === 'bayesian'
          ? [scenarioCurrentBayesian, scenarioHigherBayesian, scenarioLowerBayesian]
          : [scenarioCurrentWilson, scenarioHigherWilson, scenarioLowerWilson];

        // Round scores to 2 decimals for display comparison
        const roundedScores = scores.map(s => Math.round(s * 100) / 100);
        const maxScore = Math.max(...roundedScores);
        const minScore = Math.min(...roundedScores);

        // Apply classes to elements with highest and lowest scores
        elements.forEach((el, index) => {
          el.classList.remove('highest-score', 'lowest-score');
          const displayValue = scores[index].toFixed(2);
          if (roundedScores[index] === maxScore && maxScore !== minScore) {
            el.classList.add('highest-score');
            el.textContent = '★ ' + displayValue;
          } else {
            el.textContent = displayValue;
          }
        });
      }

      function calculateWeightedScore(rating, numRatings) {
        return rating * Math.log10(numRatings + 1);
      }

      function calculateBayesianScore(rating, numRatings, minRatings) {
        return (numRatings / (numRatings + minRatings)) * rating + (minRatings / (numRatings + minRatings)) * bayesianAssumedAverage;
      }

      function calculateWilsonScore(rating, numRatings) {
        if (numRatings === 0) {
          return 0;
        }
        const maxRating = 5;
        const p = rating / maxRating;
        const n = numRatings;
        const z = 1.96;
        const left = p + (z * z) / (2 * n);
        const right = z * Math.sqrt((p * (1 - p) + (z * z) / (4 * n)) / n);
        const under = 1 + (z * z) / n;
        return ((left - right) / under) * maxRating;
      }

      function updateBayesianHeader() {
        const minRatings = parseInt(minRatingsInput.value);
        thresholdValue.textContent = minRatings;
      }

      // Event listeners for number inputs
      ratingInput.addEventListener('change', calculateScore);
      ratingInput.addEventListener('input', calculateScore);

      numRatingsInput.addEventListener('change', calculateScore);
      numRatingsInput.addEventListener('input', calculateScore);

      minRatingsInput.addEventListener('change', function() {
        updateBayesianHeader();
        calculateScore();
      });
      minRatingsInput.addEventListener('input', function() {
        updateBayesianHeader();
        calculateScore();
      });

      scenarioRatingDevInput.addEventListener('change', calculateScore);
      scenarioRatingDevInput.addEventListener('input', calculateScore);

      scenarioReviewsDevInput.addEventListener('change', calculateScore);
      scenarioReviewsDevInput.addEventListener('input', calculateScore);

      // Handle +/- buttons
      const adjustButtons = document.querySelectorAll('.adjust-btn');
      adjustButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const field = this.dataset.field;
          const delta = parseFloat(this.dataset.delta);

          if (field === 'rating') {
            const newValue = Math.max(0, Math.min(5, parseFloat(ratingInput.value) + delta));
            ratingInput.value = newValue.toFixed(2);
          } else if (field === 'num-ratings') {
            const newValue = Math.max(0, Math.min(1000, parseInt(numRatingsInput.value) + delta));
            numRatingsInput.value = newValue;
          } else if (field === 'min-ratings') {
            const newValue = Math.max(1, Math.min(100, parseInt(minRatingsInput.value) + delta));
            minRatingsInput.value = newValue;
          } else if (field === 'scenario-rating-dev') {
            const newValue = Math.max(0, Math.min(100, parseFloat(scenarioRatingDevInput.value) + delta));
            scenarioRatingDevInput.value = newValue;
          } else if (field === 'scenario-reviews-dev') {
            const newValue = Math.max(0, Math.min(100, parseFloat(scenarioReviewsDevInput.value) + delta));
            scenarioReviewsDevInput.value = newValue;
          }

          updateBayesianHeader();
          calculateScore();
        });
      });

      // Initialize scores on page load
      updateBayesianHeader();
      calculateScore();
    }
  };
})(Drupal, drupalSettings);
