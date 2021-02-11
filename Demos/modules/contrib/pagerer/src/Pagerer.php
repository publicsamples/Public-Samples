<?php

namespace Drupal\pagerer;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Pagerer pager management class.
 */
class Pagerer implements PagererInterface, ContainerInjectionInterface {

  /**
   * The pager element.
   *
   * This is the index used by query extenders to identify the query
   * to be paged, and reflected in the 'page=x,y,z' query parameter
   * of the HTTP request.
   *
   * @var int
   */
  protected $element;

  /**
   * The route name.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The route parameters.
   *
   * @var string[]
   */
  protected $routeParameters = [];

  /**
   * The pager adaptive keys.
   *
   * @var string
   */
  protected $adaptiveKeys;

  /**
   * The Pagerer factory.
   *
   * @var \Drupal\pagerer\PagererFactoryInterface
   */
  protected $factory;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new Pagerer pager object.
   *
   * @param \Drupal\pagerer\PagererFactoryInterface $pagerer_factory
   *   The Pagerer factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(PagererFactoryInterface $pagerer_factory, RequestStack $request_stack) {
    $this->factory = $pagerer_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $element = NULL) {
    $instance = new static(
      $container->get('pagerer.factory'),
      $container->get('request_stack')
    );

    // Set the pager element.
    $instance->element = $element;

    // Set the pager adaptive keys if they exist in the query string.
    if ($page_ak = $instance->getCurrentRequest()->query->get('page_ak')) {
      // A 'page_ak' query parameter exists in the calling URL.
      $adaptive_keys = explode(',', $page_ak);
      if (isset($adaptive_keys[$element])) {
        $instance->setAdaptiveKeys($adaptive_keys[$element]);
      }
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->routeName;
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteName($route_name) {
    $this->routeName = $route_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return $this->routeParameters;
  }

  /**
   * {@inheritdoc}
   */
  public function setRouteParameters(array $route_parameters) {
    $this->routeParameters = $route_parameters;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return $this->element;
  }

  /**
   * Returns the current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function getCurrentRequest() {
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function init($total, $limit) {
    pager_default_initialize($total, $limit, $this->element);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentPage() {
    global $pager_page_array;
    return isset($pager_page_array[$this->element]) ? $pager_page_array[$this->element] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages() {
    global $pager_total;
    return isset($pager_total[$this->element]) ? $pager_total[$this->element] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPage() {
    global $pager_total;
    return isset($pager_total[$this->element]) ? $pager_total[$this->element] - 1 : -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalItems() {
    global $pager_total_items;
    return isset($pager_total_items[$this->element]) ? $pager_total_items[$this->element] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit() {
    global $pager_limits;
    return isset($pager_limits[$this->element]) ? $pager_limits[$this->element] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdaptiveKeys() {
    return $this->adaptiveKeys;
  }

  /**
   * Sets the adaptive keys of this pager.
   *
   * Used by the Adaptive pager style.
   *
   * @param string $adaptive_keys
   *   The adaptive keys string, in the format 'L.R.X', where L is the
   *   adaptive lock to left page, R is the adaptive lock to right page,
   *   and X is the adaptive center lock for calculation of neighborhood.
   *
   * @return \Drupal\pagerer\Pagerer
   *   The Pagerer pager object.
   */
  protected function setAdaptiveKeys($adaptive_keys) {
    $this->adaptiveKeys = $adaptive_keys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters(array $parameters, $page, $adaptive_keys = NULL) {
    // Build the 'page' and 'page_ak' query parameter elements.
    // This is built based on the current page of each pager element (or NULL
    // if the pager is not set), with the exception of the requested page index
    // for the current element.
    $page_el = [];
    $page_ak = [];
    foreach ($this->factory->all() as $i => $p) {
      $page_el[$i] = ($i == $this->getElement()) ? $page : $p->getCurrentPage();
      $page_ak[$i] = ($i == $this->getElement()) ? $adaptive_keys : $p->getAdaptiveKeys();
    }

    // Build the 'page' and 'page_ak' fragments, removing unneeded trailing
    // keys.
    while (end($page_el) === NULL) {
      array_pop($page_el);
    }
    $parameters['page'] = implode(',', $page_el);
    while (end($page_ak) === NULL) {
      array_pop($page_ak);
    }
    $parameters['page_ak'] = implode(',', $page_ak);

    // Merge the updated pager query parameters, with any parameters coming
    // from the current request. In case of collision, current parameters
    // take precedence over the request ones.
    if ($current_request_query = $this->getCurrentRequest()->query->all()) {
      $parameters = array_merge($current_request_query, $parameters);
    }

    // Explicitly remove the segments if not relevant.
    if (empty($page_el)) {
      unset($parameters['page']);
    }
    if (empty($page_ak)) {
      unset($parameters['page_ak']);
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getHref(array $parameters, $page, $adaptive_keys = NULL, $set_query = TRUE) {
    $options = $set_query ? [
      'query' => $this->getQueryParameters($parameters, $page, $adaptive_keys),
    ] : [];
    return Url::fromRoute($this->getRouteName(), $this->getRouteParameters(), $options);
  }

}
