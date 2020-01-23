<?php

namespace Drupal\static_generator\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Class BartikThemeNegotiator.
 *
 * @package Drupal\static_generator
 */
class BartikThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * If generating, use default theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {

    $request = \Drupal::requestStack()->getCurrentRequest();

    if (!$request->hasSession()) {
      return TRUE;
    }
    return TRUE;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   The name of the theme, or NULL if other negotiators, like the configured
   *   default one, should be used instead.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return 'bartik'; //Theme Machine Name
  }
}