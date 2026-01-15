<?php

namespace Drupal\rating_scorer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the rating scorer field mapping configuration entity.
 *
 * @ConfigEntityType(
 *   id = "rating_scorer_field_mapping",
 *   label = @Translation("Rating Scorer Field Mapping"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\rating_scorer\RatingScorerFieldMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rating_scorer\Form\RatingScorerFieldMappingForm",
 *       "edit" = "Drupal\rating_scorer\Form\RatingScorerFieldMappingForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   config_prefix = "field_mapping",
 *   admin_permission = "administer rating scorer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "content_type",
 *     "number_of_ratings_field",
 *     "average_rating_field",
 *     "scoring_method",
 *     "bayesian_threshold"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/rating-scorer/field-mapping/add",
 *     "edit-form" = "/admin/config/rating-scorer/field-mapping/{rating_scorer_field_mapping}/edit",
 *     "delete-form" = "/admin/config/rating-scorer/field-mapping/{rating_scorer_field_mapping}/delete",
 *     "collection" = "/admin/config/rating-scorer/field-mappings"
 *   }
 * )
 */
class RatingScorerFieldMapping extends ConfigEntityBase {

  /**
   * The configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * The content type machine name.
   *
   * @var string
   */
  protected $content_type;

  /**
   * The field name containing number of ratings.
   *
   * @var string
   */
  protected $number_of_ratings_field;

  /**
   * The field name containing average rating.
   *
   * @var string
   */
  protected $average_rating_field;

  /**
   * The scoring method (weighted, bayesian, wilson).
   *
   * @var string
   */
  protected $scoring_method;

  /**
   * The Bayesian threshold value.
   *
   * @var int
   */
  protected $bayesian_threshold;

}
