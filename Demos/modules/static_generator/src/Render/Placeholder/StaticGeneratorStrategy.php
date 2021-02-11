<?php

namespace Drupal\static_generator\Render\Placeholder;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the StaticGenerator placeholder strategy, to create ESI's.
 *
 */
class StaticGeneratorStrategy implements PlaceholderStrategyInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Constructs a new StaticGeneratorStrategy class.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RequestStack $request_stack, RouteMatchInterface $route_match, SessionConfigurationInterface $session_configuration) {
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->sessionConfiguration = $session_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {

    //$request = $this->requestStack->getCurrentRequest();

    //    if ($this->sessionConfiguration->hasSession($request)) {
    //      return [];
    //    }

    //return $this->doProcessPlaceholders($placeholders);
    return $placeholders;
  }

  /**
   * Transforms placeholders to StaticGenerator placeholders.
   *
   * @param array $placeholders
   *   The placeholders to process.
   *
   * @return array
   *   The StaticGenerator placeholders.
   */
  protected function doProcessPlaceholders(array $placeholders) {
    $overridden_placeholders = [];
    foreach ($placeholders as $placeholder => $placeholder_render_array) {
      if ($placeholder_render_array['#lazy_builder'][0] == 'Drupal\block\BlockViewBuilder::lazyBuilder') {

        // Markup
        $callback = 'Drupal\static_generator\Render\Placeholder\StaticGeneratorStrategy::lazy_builder';
        $arguments = UrlHelper::buildQuery($placeholder_render_array['#lazy_builder'][1]);
        $token = Crypt::hashBase64(serialize($placeholder_render_array));
        $placeholder_markup = '<drupal-render-placeholder callback="' . Html::escape($callback) . '" arguments="' . Html::escape($arguments) . '" token="' . Html::escape($token) . '"></drupal-render-placeholder>';

        $overridden_placeholders[$placeholder]['#cache'] = $placeholder_render_array['#cache'];
        $overridden_placeholders[$placeholder]['#lazy_builder'][0] = $callback;
        $overridden_placeholders[$placeholder]['#lazy_builder'][1] = $placeholder_render_array['#lazy_builder'][1];

      } else {
        $overridden_placeholders[$placeholder] = $placeholder_render_array;
      }
    }
    return $overridden_placeholders;
  }

  //    $overridden_placeholders = [];
  //$overridden_placeholders[$placeholder] = static::createStaticGeneratorPlaceholder($placeholder, $placeholder_elements);

  /**
   * Creates a StaticGenerator placeholder.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   *
   * @return array
   *   The resulting StaticGenerator placeholder render array.
   */
  protected static function createStaticGeneratorPlaceholder($original_placeholder, array $placeholder_render_array) {
    $static_generator_placeholder_id = static::generateStaticGeneratorPlaceholderId($original_placeholder, $placeholder_render_array);
    //kint($placeholder_render_array);

    if ($placeholder_render_array['#lazy_builder'][0] == 'Drupal\block\BlockViewBuilder::lazyBuilder') {

      // Markup
      $callback = 'Drupal\static_generator\Render\Placeholder\StaticGeneratorStrategy::lazy_builder';
      $arguments = UrlHelper::buildQuery($placeholder_render_array['#lazy_builder'][1]);
      $token = Crypt::hashBase64(serialize($placeholder_render_array));
      $placeholder_markup = '<drupal-render-placeholder callback="' . Html::escape($callback) . '" arguments="' . Html::escape($arguments) . '" token="' . Html::escape($token) . '"></drupal-render-placeholder>';

      // Change Callable
      $placeholder_render_array['#lazy_builder'][0] = 'Drupal\static_generator\Render\Placeholder\StaticGeneratorStrategy::lazy_builder';

      // Build render array.
      $sg_placeholder_render_array = [];
      $sg_placeholder_render_array['#markup'] = Markup::create($placeholder_markup);
      $sg_placeholder_render_array['#attached']['placeholders'][$placeholder_markup] = $placeholder_render_array;

      return $sg_placeholder_render_array;
    }
    else {
      return $placeholder_render_array;
    }
  }

  /**
   * #lazy_builder callback; builds a #pre_render-able block.
   *
   * @param $block_id
   *   A block config entity ID.
   *
   * @return array
   *   A render array with a #pre_render callback to render the block.
   */
  public static function lazyBuilder($block_id) {
    //return ['#markup' => '<span><!--#include virtual="/esi/block/' . Html::escape($block_id) . '" --></span>'];
    return Markup::create('<span><!--#include virtual="/esi/block/' . Html::escape($block_id) . '" --></span>');
  }

  /**
   * Generates a StaticGenerator placeholder ID.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   *
   * @return string
   *   The generated StaticGenerator placeholder ID.
   */
  protected static function generateStaticGeneratorPlaceholderId($original_placeholder, array $placeholder_render_array) {
    // Generate a StaticGenerator placeholder ID (to be used by Static Generator's ESI's).
    // @see \Drupal\Core\Render\PlaceholderGenerator::createPlaceholder()
    if (isset($placeholder_render_array['#lazy_builder'])) {
      $callback = $placeholder_render_array['#lazy_builder'][0];
      $arguments = $placeholder_render_array['#lazy_builder'][1];
      $token = Crypt::hashBase64(serialize($placeholder_render_array));
      return UrlHelper::buildQuery([
        'callback' => $callback,
        'args' => $arguments,
        'token' => $token,
      ]);
    }
    // When the placeholder's render array is not using a #lazy_builder,
    // anything could be in there: only #lazy_builder has a strict contract that
    // allows us to create a more sane selector. Therefore, simply the original
    // placeholder into a usable placeholder ID, at the cost of it being obtuse.
    else {
      return Html::getId($original_placeholder);
    }
  }

}






//      $sg_placeholder_render_array ['#cache'] = $placeholder_render_array['#cache'];
//      $sg_placeholder_render_array ['#cache']['max-age'] = 0;
//      $sg_placeholder_render_array ['#cache']['keys'] = [];
//      $sg_placeholder_render_array ['#cache']['tags'] = [];


//      $sg_placeholder_render_array = [
//        '#lazy_builder' => 'Drupal\static_generator\Render\Placeholder\StaticGeneratorStrategy::lazy_builder',
//        '#markup' => '<drupal-render-placeholder callback="Drupal\static_generator\Render\StaticGeneratorStrategy::lazyBuilder arguments="' . '>',
//        '#cache' => [
//          'max-age' => 0,
//        ],
//        'static_generator_placeholders' => [
//          Html::escape($static_generator_placeholder_id) => $placeholder_render_array,
//        ],
//      ];
//    }

//      '<drupal-render-placeholder
//        callback="Drupal\static_generator\Render\StaticGeneratorStrategy::lazyBuilder"
//        arguments="0=views_block__content_recent_block_1&amp;1=full&amp;2"
//        token="YubCraeCL0yOsmG4F9WpXita9XPg6z54-4ARk2s9ruM">
//        </drupal-render-placeholder>';


//return $sg_placeholder_render_array;
