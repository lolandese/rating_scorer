<?php

namespace Drupal\rating_scorer\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * A handler to sort by calculated rating score.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("rating_score")
 */
class RatingScore extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This is a calculated field - don't modify the query for sorting.
    // Sorting will be handled in PHP after the query results are fetched.
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // Ensure the field handler has been rendered to populate cached scores.
    $field_handler = NULL;
    foreach ($this->view->field as $field) {
      if ($field->getPluginId() === 'rating_score') {
        $field_handler = $field;
        break;
      }
    }

    // If no rating_score field handler exists, we can't sort.
    if (!$field_handler) {
      return;
    }

    // Render all rows to cache their scores.
    foreach ($values as $row) {
      $field_handler->render($row);
    }

    // Sort the result rows by the cached rating score.
    usort($values, function ($a, $b) {
      $score_a = $a->rating_score_value ?? 0;
      $score_b = $b->rating_score_value ?? 0;

      // Compare scores: return difference for ascending order.
      $result = $score_a <=> $score_b;

      // Reverse if descending order requested.
      if ($this->options['order'] === 'DESC') {
        $result = -$result;
      }

      return $result;
    });
  }

}

