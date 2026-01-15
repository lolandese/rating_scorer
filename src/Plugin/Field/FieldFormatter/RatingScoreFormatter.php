<?php

namespace Drupal\rating_scorer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'rating_score_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "rating_score_formatter",
 *   label = @Translation("Rating Score Formatter"),
 *   field_types = {
 *     "rating_score"
 *   }
 * )
 */
class RatingScoreFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => isset($item->value) ? number_format($item->value, 2) : '—',
      ];
    }

    return $elements;
  }

}
