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
      const defaultRating = config.defaultRating || 4.5;
      const defaultNumRatings = config.defaultNumRatings || 100;
      const defaultMethod = config.defaultMethod || 'bayesian';

      // Create the calculator interface
      container.innerHTML = `
        <div class="rating-scorer-container">
          <h2>${Drupal.t('Rating Score Calculator')}</h2>
          
          <div class="rating-scorer-controls">
            <div class="control-group">
              <label>${Drupal.t('Average Rating')}: <span id="rating-value">${defaultRating.toFixed(2)}</span> / 5.00</label>
              <input type="range" id="rating-slider" min="0" max="5" step="0.01" value="${defaultRating}">
            </div>
            
            <div class="control-group">
              <label>${Drupal.t('Number of Ratings')}: <span id="num-ratings-value">${defaultNumRatings}</span></label>
              <input type="range" id="num-ratings-slider" min="0" max="1000" step="1" value="${defaultNumRatings}">
            </div>
            
            <div class="control-group">
              <label>${Drupal.t('Minimum Ratings Threshold')}: <span id="min-ratings-value">${minRatings}</span></label>
              <p class="help-text">${Drupal.t('(Used by Bayesian Average method)')}</p>
              <input type="range" id="min-ratings-slider" min="1" max="100" step="1" value="${minRatings}">
            </div>
          </div>
          
          <div class="rating-scorer-scenario-comparison">
            <h3>${Drupal.t('Impact of Rating Changes on Scores')}</h3>
            <p class="scenario-intro">${Drupal.t('Compare how different rating patterns affect each scoring method:')}</p>
            
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
                <tr class="threshold-row">
                  <td colspan="3" class="threshold-label">${Drupal.t('Min. Ratings Threshold:')}</td>
                  <td class="threshold-value">${Drupal.t('N/A')}</td>
                  <td class="threshold-value recommended"><strong id="threshold-value">${minRatings}</strong></td>
                  <td class="threshold-value">${Drupal.t('N/A')}</td>
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
                  <td><strong>${Drupal.t('Higher Rating')}</strong><br><span class="change">+5% rating, -30% reviews</span></td>
                  <td><strong id="scenario-higher-rating">4.73</strong>/5</td>
                  <td><strong id="scenario-higher-reviews">70</strong></td>
                  <td><strong id="scenario-higher-weighted">0.00</strong></td>
                  <td class="recommended"><strong id="scenario-higher-bayesian">0.00</strong></td>
                  <td><strong id="scenario-higher-wilson">0.00</strong></td>
                </tr>
                <tr class="lower-row">
                  <td><strong>${Drupal.t('More Reviews')}</strong><br><span class="change">-5% rating, +30% reviews</span></td>
                  <td><strong id="scenario-lower-rating">4.27</strong>/5</td>
                  <td><strong id="scenario-lower-reviews">130</strong></td>
                  <td><strong id="scenario-lower-weighted">0.00</strong></td>
                  <td class="recommended"><strong id="scenario-lower-bayesian">0.00</strong></td>
                  <td><strong id="scenario-lower-wilson">0.00</strong></td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="table-footer">
                  <td colspan="6" class="footer-text">★ = Highest score in that method column</td>
                </tr>
              </tfoot>
            </table>
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
                  <td class="desc-cell">${Drupal.t('Multiplies the average rating by the logarithm of the number of ratings. This gives higher scores to items with both good ratings and many reviews.')}</td>
                </tr>
                <tr class="recommended">
                  <td><strong>${Drupal.t('Bayesian Average')}</strong> <span class="recommended-badge">★ ${Drupal.t('Recommended')}</span></td>
                  <td class="desc-cell">${Drupal.t('Blends the actual rating with an assumed average (3.5), weighted by the number of ratings. Requires more ratings to pull away from the average. Configurable via the Minimum Ratings Threshold.')}</td>
                </tr>
                <tr>
                  <td><strong>${Drupal.t('Wilson Score')}</strong></td>
                  <td class="desc-cell">${Drupal.t('A confidence-based approach that calculates the lower bound of a confidence interval. This naturally handles uncertainty from low rating counts. Most conservative method.')}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      `;

      const ratingSlider = document.getElementById('rating-slider');
      const ratingValue = document.getElementById('rating-value');
      const numRatingsSlider = document.getElementById('num-ratings-slider');
      const numRatingsValue = document.getElementById('num-ratings-value');
      const minRatingsSlider = document.getElementById('min-ratings-slider');
      const minRatingsValue = document.getElementById('min-ratings-value');
      const bayesianHeader = document.getElementById('bayesian-header');
      const thresholdValue = document.getElementById('threshold-value');

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

      function calculateScore() {
        const rating = parseFloat(ratingSlider.value);
        const numRatings = parseInt(numRatingsSlider.value);
        const minRatings = parseInt(minRatingsSlider.value);

        // Calculate all three methods for current input
        const weightedScore = calculateWeightedScore(rating, numRatings);
        const bayesianScore = calculateBayesianScore(rating, numRatings, minRatings);
        const wilsonScore = calculateWilsonScore(rating, numRatings);

        // Calculate scenarios
        const higherRating = rating * 1.05;
        const higherReviews = Math.round(numRatings * 0.70);
        const lowerRating = rating * 0.95;
        const lowerReviews = Math.round(numRatings * 1.30);

        const higherWeighted = calculateWeightedScore(higherRating, higherReviews);
        const higherBayesian = calculateBayesianScore(higherRating, higherReviews, minRatings);
        const higherWilson = calculateWilsonScore(higherRating, higherReviews);

        const lowerWeighted = calculateWeightedScore(lowerRating, lowerReviews);
        const lowerBayesian = calculateBayesianScore(lowerRating, lowerReviews, minRatings);
        const lowerWilson = calculateWilsonScore(lowerRating, lowerReviews);

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

        // Clear previous content and classes
        elements.forEach(el => {
          el.classList.remove('highest-score', 'lowest-score');
          // Store the original numeric value
          const value = el.textContent;
          el.dataset.value = value;
        });

        // Find max and min scores
        const maxScore = Math.max(...scores);
        const minScore = Math.min(...scores);

        // Apply classes to elements with highest and lowest scores
        elements.forEach((el, index) => {
          const value = parseFloat(el.dataset.value);
          if (scores[index] === maxScore && maxScore !== minScore) {
            el.classList.add('highest-score');
            el.textContent = '★ ' + el.dataset.value;
          } else if (scores[index] === minScore && maxScore !== minScore) {
            el.classList.add('lowest-score');
            el.textContent = el.dataset.value;
          } else {
            el.textContent = el.dataset.value;
          }
        });
      }

      function calculateWeightedScore(rating, numRatings) {
        return rating * Math.log10(numRatings + 1);
      }

      function calculateBayesianScore(rating, numRatings, minRatings) {
        const C = 3.5;
        return (numRatings / (numRatings + minRatings)) * rating + (minRatings / (numRatings + minRatings)) * C;
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
        const minRatings = parseInt(minRatingsSlider.value);
        thresholdValue.textContent = minRatings;
      }

      ratingSlider.addEventListener('input', function() {
        ratingValue.textContent = parseFloat(this.value).toFixed(2);
        calculateScore();
      });

      numRatingsSlider.addEventListener('input', function() {
        numRatingsValue.textContent = this.value;
        calculateScore();
      });

      minRatingsSlider.addEventListener('input', function() {
        minRatingsValue.textContent = this.value;
        updateBayesianHeader();
        calculateScore();
      });

      updateBayesianHeader();
      calculateScore();
    }
  };

})(Drupal, drupalSettings);
