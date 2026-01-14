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
              <label for="method-select">${Drupal.t('Scoring Method')}</label>
              <select id="method-select">
                <option value="weighted">${Drupal.t('Weighted Score')}</option>
                <option value="bayesian" selected>${Drupal.t('Bayesian Average')}</option>
                <option value="wilson">${Drupal.t('Wilson Score')}</option>
              </select>
            </div>
            
            <div class="control-group" id="min-ratings-control">
              <label>${Drupal.t('Minimum Ratings Threshold')}: <span id="min-ratings-value">${minRatings}</span></label>
              <input type="range" id="min-ratings-slider" min="1" max="100" step="1" value="${minRatings}">
            </div>
          </div>
          
          <div class="rating-scorer-result">
            <div class="result-label">${Drupal.t('Final Score')}</div>
            <div class="result-value" id="final-score">0.00</div>
          </div>
          
          <div class="rating-scorer-description" id="method-description"></div>
        </div>
      `;

      const ratingSlider = document.getElementById('rating-slider');
      const ratingValue = document.getElementById('rating-value');
      const numRatingsSlider = document.getElementById('num-ratings-slider');
      const numRatingsValue = document.getElementById('num-ratings-value');
      const methodSelect = document.getElementById('method-select');
      const minRatingsSlider = document.getElementById('min-ratings-slider');
      const minRatingsValue = document.getElementById('min-ratings-value');
      const minRatingsControl = document.getElementById('min-ratings-control');
      const finalScore = document.getElementById('final-score');
      const methodDescription = document.getElementById('method-description');

      methodSelect.value = defaultMethod;

      function calculateScore() {
        const rating = parseFloat(ratingSlider.value);
        const numRatings = parseInt(numRatingsSlider.value);
        const method = methodSelect.value;
        const minRatings = parseInt(minRatingsSlider.value);

        let score = 0;

        switch (method) {
          case 'weighted':
            score = rating * Math.log10(numRatings + 1);
            break;
          case 'bayesian':
            const C = 3.5;
            score = (numRatings / (numRatings + minRatings)) * rating + (minRatings / (numRatings + minRatings)) * C;
            break;
          case 'wilson':
            if (numRatings === 0) {
              score = 0;
            } else {
              const maxRating = 5;
              const p = rating / maxRating;
              const n = numRatings;
              const z = 1.96;
              const left = p + (z * z) / (2 * n);
              const right = z * Math.sqrt((p * (1 - p) + (z * z) / (4 * n)) / n);
              const under = 1 + (z * z) / n;
              score = ((left - right) / under) * maxRating;
            }
            break;
        }

        finalScore.textContent = score.toFixed(2);
        updateDescription(method);
      }

      function updateDescription(method) {
        const descriptions = {
          weighted: Drupal.t('Weighted Score: Multiplies the average rating by the logarithm of the number of ratings. This gives higher scores to items with both good ratings and many reviews.'),
          bayesian: Drupal.t('Bayesian Average: Blends the actual rating with an assumed average (3.5), weighted by the number of ratings. Items need more ratings to pull away from the average.'),
          wilson: Drupal.t('Wilson Score: A confidence-based approach that calculates the lower bound of a confidence interval. This naturally handles uncertainty from low rating counts.')
        };
        methodDescription.textContent = descriptions[method];
      }

      function updateMinRatingsVisibility() {
        minRatingsControl.style.display = methodSelect.value === 'bayesian' ? 'block' : 'none';
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
        calculateScore();
      });

      methodSelect.addEventListener('change', function() {
        updateMinRatingsVisibility();
        calculateScore();
      });

      updateMinRatingsVisibility();
      calculateScore();
    }
  };

})(Drupal, drupalSettings);
