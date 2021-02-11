<?php

namespace Drupal\static_generator;

use DOMDocument;
use DOMXPath;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Static Generator Service.
 *
 * Provides static generation services.
 */
class StaticGenerator {

  /**
   * The renderer.
   *
   * @var RendererInterface
   */
  private $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The class resolver.
   *
   * @var ClassResolverInterface
   */
  private $classResolver;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The webform theme manager.
   *
   * @var \Drupal\webform\WebformThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new StaticGenerator object.ClassResolverInterface
   * $class_resolver,
   *
   * @param RendererInterface $renderer
   *  The renderer.
   * @param RouteMatchInterface $route_match
   *   The route matcher.
   * @param ClassResolverInterface $class_resolver
   *  The class resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP Kernel service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Path matcher.
   */
  public function __construct(RendererInterface $renderer, RouteMatchInterface $route_match, ClassResolverInterface $class_resolver, RequestStack $request_stack, HttpKernelInterface $http_kernel, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, PathMatcherInterface $path_matcher) {
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
    $this->classResolver = $class_resolver;
    $this->requestStack = $request_stack;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->httpKernel = $http_kernel;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Generate all pages and files.
   *
   * Limit the number of nodes generated for each bundle.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateAll() {
    \Drupal::logger('static_generator')->notice('Begin generateAll()');
    $elapsed_time = $this->deleteAll();
    $elapsed_time += $this->generatePages();
    $elapsed_time += $this->generateFiles();
    \Drupal::logger('static_generator')
      ->notice('End generateAll(), elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Generate pages.
   *
   * @param bool $delete_pages
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Theme\MissingThemeDependencyException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generatePages($delete_pages = FALSE) {
    $elapsed_time = 0;
    if ($delete_pages) {
      $elapsed_time = $this->deletePages();
      $elapsed_time += $this->deleteEsi();
    }
    $elapsed_time += $this->generateNodes();
    $elapsed_time += $this->generatePaths();
    //$elapsed_time += $this->generateRedirects();
    \Drupal::logger('static_generator')
      ->notice('Generation of all pages complete, elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Generate media entities.
   *
   * @param bool $esi_only
   * @param string $bundle
   * @param int $start
   * @param int $length
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateMedia($bundle = '', $esi_only = FALSE, $start = 0, $length = 100000) {
    $elapsed_time_total = 0;

    // Get bundles to generate from config if not specified in $type.
    if (empty($bundle)) {
      $bundles_string = $this->configFactory->get('static_generator.settings')
        ->get('gen_media');
      $bundles = explode(',', $bundles_string);
    } else {
      $bundles = [$bundle];
    }

    // Generate as Anonymous user.
    \Drupal::service('account_switcher')
      ->switchTo(new AnonymousUserSession());

    // Switch to default theme
    $active_theme = $this->themeManager->getActiveTheme();
    $default_theme_name = $this->configFactory->get('system.theme')
      ->get('default');
    $default_theme = $this->themeInitialization->getActiveThemeByName($default_theme_name);
    $this->themeManager->setActiveTheme($default_theme);

    // Generate each bundle.
    $blocks_processed = [];
    $sg_esi_processed = [];
    $sg_esi_existing = $this->existingSgEsiFiles();
    foreach ($bundles as $bundle) {
      $start_time = time();

      $query = \Drupal::entityQuery('media');
      $query->condition('status', 1);
      $query->condition('bundle', $bundle);
      $count = $query->count()->execute();

      $count_gen = 0;

      for ($i = $start; $i <= $count; $i = $i + $length) {
        $query = \Drupal::entityQuery('media');
        $query->condition('status', 1);
        $query->condition('bundle', $bundle);
        $query->range($i, $length);
        $query->sort('mid', 'DESC');
        $entity_ids = $query->execute();

        // Generate pages for bundle.
        foreach ($entity_ids as $entity_id) {
          $path_alias = \Drupal::service('path.alias_manager')
            ->getAliasByPath('/media/' . $entity_id);
          $this->generatePage($path_alias, '', $esi_only, FALSE, FALSE, FALSE, $blocks_processed, $sg_esi_processed, $sg_esi_existing);
          $count_gen++;
        }

        // Exit if single run for specified content type.
        if (!empty($bundle)) {
          break;
        }
      }

      // Elapsed time.
      $end_time = time();
      $elapsed_time = $end_time - $start_time;
      $elapsed_time_total += $elapsed_time;
      if ($count_gen > 0) {
        $seconds_per_page = round($elapsed_time / $count_gen, 2);
      } else {
        $seconds_per_page = 'n/a';
      }

      \Drupal::logger('static_generator_time')
        ->notice('Gen bundle ' . $bundle . ' ' . $count_gen .
          ' pages in ' . $elapsed_time . ' seconds, ' . $seconds_per_page . ' seconds per page.');
    }

    // Switch back from anonymous user.
    \Drupal::service('account_switcher')->switchBack();

    // Switch back to active theme.
    $this->themeManager->setActiveTheme($active_theme);

    return $elapsed_time_total;
  }

  /**
   * Generate term entities.
   *
   * @param bool $esi_only
   * @param string $bundle
   * @param int $start
   * @param int $length
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateVocabulary($bundle = '', $esi_only = FALSE, $start = 0, $length = 100000) {
    $elapsed_time_total = 0;

    // Get bundles to generate from config if not specified in $type.
    if (empty($bundle)) {
      // @todo Implement settings page UI for this.
      $bundles_string = $this->configFactory->get('static_generator.settings')
        ->get('gen_taxonomy');
      $bundles = explode(',', $bundles_string);
    } else {
      $bundles = [$bundle];
    }

    // Generate as Anonymous user.
    \Drupal::service('account_switcher')
      ->switchTo(new AnonymousUserSession());

    // Switch to default theme
    $active_theme = $this->themeManager->getActiveTheme();
    $default_theme_name = $this->configFactory->get('system.theme')
      ->get('default');
    $default_theme = $this->themeInitialization->getActiveThemeByName($default_theme_name);
    $this->themeManager->setActiveTheme($default_theme);

    // Get vocabulary id.
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load($bundle);
    $vid = $vocabulary->id();

    // Generate each bundle.
    $blocks_processed = [];
    $sg_esi_processed = [];
    $sg_esi_existing = $this->existingSgEsiFiles();
    foreach ($bundles as $bundle) {
      $start_time = time();

      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('status', 1);
      $query->condition('vid', $vid);
      $count = $query->count()->execute();

      $count_gen = 0;

      for ($i = $start; $i <= $count; $i = $i + $length) {

        // Reset memory
        //        drupal_static_reset();
        //        $manager = \Drupal::entityManager();
        //        foreach ($manager->getDefinitions() as $id => $definition) {
        //          $manager->getStorage($id)->resetCache();
        //        }
        // Run garbage collector to further reduce memory.
        //        gc_collect_cycles();
        // @TODO Can we reset container?

        $query = \Drupal::entityQuery('taxonomy_term');
        $query->condition('status', 1);
        $query->condition('vid', $vid);
        $query->sort('weight');
        $query->range($i, $length);
        $entity_ids = $query->execute();

        // Generate pages for bundle.
        foreach ($entity_ids as $entity_id) {
          $path_alias = \Drupal::service('path.alias_manager')
            ->getAliasByPath('/taxonomy/term/' . $entity_id);
          $this->generatePage($path_alias, '', $esi_only, FALSE, FALSE, FALSE, $blocks_processed, $sg_esi_processed, $sg_esi_existing);
          $count_gen++;
        }

        // Exit if single run for specified content type.
        if (!empty($bundle)) {
          break;
        }
      }

      // Elapsed time.
      $end_time = time();
      $elapsed_time = $end_time - $start_time;
      $elapsed_time_total += $elapsed_time;
      if ($count_gen > 0) {
        $seconds_per_page = round($elapsed_time / $count_gen, 2);
      } else {
        $seconds_per_page = 'n/a';
      }

      \Drupal::logger('static_generator_time')
        ->notice('Gen bundle ' . $bundle . ' ' . $count_gen .
          ' pages in ' . $elapsed_time . ' seconds, ' . $seconds_per_page . ' seconds per page.');
    }

    // Switch back from anonymous user.
    \Drupal::service('account_switcher')->switchBack();

    // Switch back to active theme.
    $this->themeManager->setActiveTheme($active_theme);

    return $elapsed_time_total;
  }

  /**
   * Generate nodes.
   *
   * @param string $bundle
   * @param bool $esi_only
   * @param int $start
   * @param int $length
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Theme\MissingThemeDependencyException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateNodes($bundle = '', $esi_only = FALSE, $start = 0, $length = 100000) {
    $elapsed_time_total = 0;

    // Get bundles to generate from config if not specified in $bundle.
    if (empty($bundle)) {
      $bundles_string = $this->configFactory->get('static_generator.settings')
        ->get('gen_node');
      $bundles = explode(',', $bundles_string);
    } else {
      $bundles = [$bundle];
    }

    // Generate as Anonymous user.
    \Drupal::service('account_switcher')
      ->switchTo(new AnonymousUserSession());

    // Switch to default theme
    $active_theme = $this->themeManager->getActiveTheme();
    $default_theme_name = $this->configFactory->get('system.theme')
      ->get('default');
    $default_theme = $this->themeInitialization->getActiveThemeByName($default_theme_name);
    $this->themeManager->setActiveTheme($default_theme);

    // Generate each bundle.
    $blocks_processed = [];
    $sg_esi_processed = [];
    $sg_esi_existing = $this->existingSgEsiFiles();
    foreach ($bundles as $bundle) {
      $start_time = time();

      $query = \Drupal::entityQuery('node');
      $query->condition('status', 1);
      $query->condition('type', $bundle);
      $count = $query->count()->execute();

      $count_gen = 0;

      for ($i = $start; $i <= $count; $i = $i + $length) {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', $bundle);
        $query->range($i, $length);
        $query->sort('nid', 'DESC');
        $entity_ids = $query->execute();

        // Generate pages for bundle.
        foreach ($entity_ids as $entity_id) {
          $path_alias = \Drupal::service('path.alias_manager')
            ->getAliasByPath('/node/' . $entity_id);
          $error_time = $this->generatePage($path_alias, '', $esi_only, FALSE, FALSE, FALSE, $blocks_processed, $sg_esi_processed, $sg_esi_existing);
          if (!is_null($error_time)) {
            $error_times[] = $error_time;
            if ($this->errorThresholdExceeded($error_times)) {
              watchdog_exception('static_generator_flood', new Exception('Static Generator - error log flooding.'));
              break;
            }
          }
          $count_gen++;
        }

        // Exit if single run for specified content type.
        if (!empty($type)) {
          break;
        }
      }

      // Elapsed time.
      $end_time = time();
      $elapsed_time = $end_time - $start_time;
      $elapsed_time_total += $elapsed_time;
      if ($count_gen > 0) {
        $seconds_per_page = round($elapsed_time / $count_gen, 2);
      } else {
        $seconds_per_page = 'n/a';
      }

      \Drupal::logger('static_generator_time')
        ->notice('Gen bundle ' . $bundle . ' ' . $count_gen .
          ' pages in ' . $elapsed_time . ' seconds, ' . $seconds_per_page . ' seconds per page.');
    }

    // Switch back from anonymous user.
    \Drupal::service('account_switcher')->switchBack();

    // Switch back to active theme.
    $this->themeManager->setActiveTheme($active_theme);

    return $elapsed_time_total;
  }

  /**
   * Examin array of errors to determine if log is being flooded.
   *
   * @param $errors
   *
   * @return bool
   */
  public function errorThresholdExceeded($errors) {
    $threshold_time = 30; // seconds
    $threshold_errors = 10; // number of errors

    $error_count = 0;
    for ($i = count($errors) - 1; $i >= 0; $i--) {
      if (time() - $errors[$i] < $threshold_time) {
        $error_count++;
        if ($error_count > $threshold_errors) {
          return TRUE;
        }
      } else {
        return FALSE;
      }
    }
  }

  /**
   * Generate paths specified in settings.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generatePaths() {
    $start_time = time();

    $paths_string = $this->configFactory->get('static_generator.settings')
      ->get('paths_generate');
    if (!empty($paths_string)) {
      $paths = explode(',', $paths_string);
      foreach ($paths as $path) {
        $this->generatePage(trim($path));
      }
    }

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    \Drupal::logger('static_generator')
      ->notice('generatePaths() elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Generate markup for a single page.
   *
   * @param string $path
   *   The page's path.
   *
   * @param string $path_generate
   * @param bool $esi_only
   *   Optionally omit generating the page (just generate the blocks).
   *
   * @param bool $log
   *   Should a log message be written to dblog.
   *
   * @param bool $account_switcher
   *
   * @param bool $theme_switcher
   *
   * @param array $blocks_processed
   *
   * @param array $sg_esi_processed
   *
   * @param array $sg_esi_existing
   *
   * @param bool $check_published
   *
   * @return string|void
   *   Returns null if no errors. If an error occurs, returns the time() of the error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Theme\MissingThemeDependencyException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generatePage($path, $path_generate = '', $esi_only = FALSE, $log = FALSE, $account_switcher = TRUE, $theme_switcher = TRUE, &$blocks_processed = [], &$sg_esi_processed = [], $sg_esi_existing = [], $check_published = FALSE) {

    // Get path alias for path.
    $path_alias = \Drupal::service('path.alias_manager')
      ->getAliasByPath($path);

    // Return if path is excluded.
    if ($this->excludePath($path)) {
      return null;
    }

    if ($this->endsWith($path_alias, '.xml')) {
      return null;
    }

    // Return if check published and not published.
    if ($check_published) {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $path_canonical = \Drupal::service('path.alias_manager')
        ->getPathByAlias($path);
      $nid = substr($path_canonical, strpos($path_canonical, '/', 1) + 1);
      $node = $node_storage->load($nid);
      if ($node instanceof NodeInterface) {
        if (!$node->isPublished()) {
          return null;
        }
      }
    }

    // Get the markup.
    $markup = $this->markupForPage($path_alias, $account_switcher, $theme_switcher);

    // Return if error.
    if ($markup == 'error') {
      return time();
    }
    if ($markup == '404') {
      return null;
    }

    // Prcoess ESIs.
    $markup = $this->injectESIs($markup, $path, $blocks_processed, $sg_esi_processed, $sg_esi_existing);

    // Get file name.
    if (empty($path_generate)) {
      $web_directory = $this->directoryFromPath($path_alias);
      $file_name = $this->filenameFromPath($path_alias);
    } else {
      $web_directory = $this->directoryFromPath($path_generate);
      $file_name = $this->filenameFromPath($path_generate);
    }

    // Return if on index.html and gen index is false.
    if ($file_name == "index.html" && !$this->generateIndex()) {
      return null;
    }

    // Generate redirect page for path.
    $this->generateRedirectPageForPath($path);

    // Write the page.
    $directory = $this->generatorDirectory() . $web_directory;
    //if ($path_alias == '/problem-page') { // This is good way to quickly debug bad page in batch.
    if (!$esi_only && file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      file_unmanaged_save_data($markup, $directory . '/' . $file_name, FILE_EXISTS_REPLACE);

      if ($log) {
        \Drupal::logger('static_generator')
          ->notice('Generate Page: ' . $directory . '/' . $file_name);
      }
    }

  }

  /**
   * Generates static pages for redirects.
   */
  public function generateRedirectPageForPath($path) {
    if (!empty($path) && \Drupal::moduleHandler()->moduleExists('redirect')) {

      $path_canonical = \Drupal::service('path.alias_manager')
        ->getPathByAlias($path);
      $nid = substr($path_canonical, strpos($path_canonical, '/', 1) + 1);

      $redirect_storage = $this->entityTypeManager->getStorage('redirect');

      $redirect_ids = $redirect_storage->getQuery()
        ->condition('redirect_redirect__uri', '%' . $nid, 'LIKE')
        ->execute();
      $redirects = $redirect_storage->loadMultiple($redirect_ids);

      foreach ($redirects as $redirect) {
        $source_url = $redirect->getSourceUrl();
        $target_array = $redirect->getRedirect();
        $target_uri = $target_array['uri'];
        $target_url = \Drupal::service('path.alias_manager')->getAliasByPath(substr($target_uri, 9));
        $this->generateRedirect($source_url, $target_url);
        if ($this->verboseLogging()) {
          \Drupal::logger('static_generator')
            ->notice('generateRedirects() source: ' . $source_url . ' target: ' . $target_url);
        }
      }
    }
  }

  /**
   * @param $menu_link
   * @param $path
   *
   * @throws \Drupal\Core\Theme\MissingThemeDependencyException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generatePagesMenuChildrenSiblings($menu_link, $path) {

    if ($path) {
      $this->queuePage($path);
    }

    // Get the menu link's children and generate their pages.
    $menu_parameters = new \Drupal\Core\Menu\MenuTreeParameters();
    $menu_parameters->setMaxDepth(1);
    $menu_parameters->setRoot($menu_link->getPluginId());
    $menu_parameters->excludeRoot();
    $menu_tree_service = \Drupal::service('menu.link_tree');
    $tree = $menu_tree_service->load('main', $menu_parameters);
    $children = $menu_tree_service->build($tree);

    if (isset($child_items) && array_key_exists('#items', $child_items)) {
      $child_items = $children['#items'];
      foreach ($child_items as $child_item) {
        $url = $child_item['url'];
        $path = $url->toString();
        if (substr($path, 0, 1) == '/') {
          \Drupal::service('static_generator')->queuePage($path);
        }
      }
    }

    // Get this link's parent and generate its pages.
    $menu_parameters_siblings = new \Drupal\Core\Menu\MenuTreeParameters();
    $menu_parameters_siblings->setMaxDepth(1);
    $parent_id = $menu_link->getParentId();
    $menu_parameters_siblings->setRoot($parent_id);
    $menu_parameters_siblings->excludeRoot();
    $menu_tree_service = \Drupal::service('menu.link_tree');
    $tree_siblings = $menu_tree_service->load('main', $menu_parameters_siblings);
    $siblings = $menu_tree_service->build($tree_siblings);
    $child_items = $siblings['#items'];
    foreach ($child_items as $child_item) {
      $url = $child_item['url'];
      $path = $url->toString();
      if (substr($path, 0, 1) == '/') {
        \Drupal::service('static_generator')->queuePage($path);
      }
    }
  }

  /**
   * Should a path be excluded by "Paths to not generate setting.
   *
   * @param $path
   *
   * @return boolean
   *   Return true if path is excluded, false otherwise.
   *`
   */
  public function excludePath($path) {

    $path_alias = \Drupal::service('path.alias_manager')
      ->getAliasByPath($path);

    // Get paths to exclude (not generate)
    $paths_do_not_generate_string = $this->configFactory->get('static_generator.settings')
      ->get('paths_do_not_generate');
    if (empty($paths_do_not_generate_string)) {
      return FALSE;
    }
    $paths_do_not_generate = explode(',', $paths_do_not_generate_string);

    foreach ($paths_do_not_generate as $path_dng) {
      if ($this->pathMatcher->matchPath($path_alias, $path_dng)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Generate block ESi fragment files.
   *
   * @param bool $frequent_only
   *   Generate frequent blocks only.  Frequent blocks are defined in settings.
   *
   * @return int
   *   Execution time in seconds.
   *`
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateBlocks($frequent_only = FALSE) {

    if ($frequent_only) {
      // Generate frequent blocks only.
      $blocks_frequent = $this->configFactory->get('static_generator.settings')
        ->get('blocks_frequent');
      if (!empty($blocks_frequent)) {
        $blocks_frequent = explode(',', $blocks_frequent);
        foreach ($blocks_frequent as $esi_id) {
          //$this->generateBlockById($block_id);
          $this->generateEsiById($esi_id);
        }
      }
    } else {
      // Generate all blocks.
      $this->deleteEsi();
      return $this->generateNodes('', TRUE);
    }
  }

  /**
   * Get all Block ID's to ESI, or optionally only those that match a patten.
   *
   * @param string $pattern
   *   The block id pattern.
   *
   * @return array|int
   *
   * @throws \Exception
   */
  public function blockIds($pattern = '') {

    $controller = $this->entityTypeManager->getStorage('block');
    $ids = [];
    foreach ($controller->loadMultiple() as $return_block) {
      $ids[] = $return_block->id();
    }

    if (!empty($pattern)) {
      $ids_match_pattern = [];
      foreach ($ids as $id) {
        if (substr($id, 0, strlen($pattern)) === $pattern) {
          $ids_match_pattern[] = $id;
        }
      }
      return $ids_match_pattern;
    } else {
      return 'done';
    }
  }

  /**
   *
   * Determines if block should be ESI.
   *
   * @param $block_id
   *
   * @return bool
   */
  public function esiBlock($block_id) {

    // Return if block on "no esi" in settings.
    $blocks_no_esi = $this->configFactory->get('static_generator.settings')
      ->get('blocks_no_esi');
    if (empty($blocks_no_esi)) {
      return TRUE;
    }
    $blocks_no_esi = explode(',', $blocks_no_esi);
    if (in_array($block_id, $blocks_no_esi)) {
      return FALSE;
    }

    // Return if block's pattern on "no esi" in settings.
    foreach ($blocks_no_esi as $block_no_esi) {
      if (substr($block_no_esi, strlen($block_no_esi) - 1, 1) === '*') {
        $block_no_esi = substr($block_no_esi, 0, strlen($block_no_esi) - 1);
        if (strpos($block_id, $block_no_esi) === 0) {
          return FALSE;
        }
      }
    }

    // Did not match id or pattern
    return TRUE;
  }

  /**
   * Generate a block fragment file.  This approach generates a block directly,
   * rather than taking the rendered block markup from the rendered pages, which
   * is the approach used when generating all pages.
   *
   * @param string $block_id
   *   The block id.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateBlockById($block_id) {
    if (empty($block_id)) {
      return;
    }
    // Return if block id listed in "block no esi" setting.
    if (!$this->esiBlock($block_id)) {
      return;
    }

    if (substr($block_id, 0, 8) === 'sg-esi--') {
      $generator_directory = $this->generatorDirectory() . '/esi/sg-esi';
    } else {
      $generator_directory = $this->generatorDirectory() . '/esi/block';
    }

    $files = file_scan_directory($generator_directory, '/*/', ['recurse' => FALSE]);
    foreach ($files as $file) {
      $filename = $file->filename;

      $block_id_file = substr($filename, 0, strpos($block_id, '__'));

      if ($block_id === $block_id_file) {
        $path_str = substr($block_id, strpos($block_id, '__'));
        $path = '/' . str_replace('-', '/', $path_str);
        $this->generatePage($path, '', TRUE);
      }
    }
  }

  /**
   * Create array of existing sg-esi files.
   *
   */
  public function existingSgEsiFiles() {

    $generator_directory = $this->generatorDirectory();
    $directory = $generator_directory . 'esi/sg-esi';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    $files = file_scan_directory($directory, '/.*/', ['recurse' => FALSE]);
    //$files = scandir($directory);
    $existingSgEsiFiles = [];
    foreach ($files as $file) {
      $filename = $file->filename;
      if (strpos($filename, '__') !== FALSE) {
        $esi_id = substr($filename, 0, strpos($filename, '__'));
        $existingSgEsiFiles[$esi_id] = $filename;
      }
    }
    return $existingSgEsiFiles;
  }

  /**
   * Generate a esi fragment file.
   *
   * @param string $esi_id
   *   The esi id.
   *
   * @throws \Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function generateEsiById($esi_id) {

    $generator_directory = $this->generatorDirectory() . '/esi/sg-esi';

    $files = file_scan_directory($generator_directory, '/.*/', ['recurse' => FALSE]);
    foreach ($files as $file) {
      $filename = $file->filename;
      $esi_id_file = substr($filename, 0, strpos($filename, '__'));

      $generate_page = FALSE;
      if ($this->endsWith($esi_id, "*")) {
        $esi_id_real = substr($esi_id, 0, strlen($esi_id) - 1);
        // Wildcard esi_id ends in *
        if (substr($esi_id_file, 0, strlen($esi_id_real)) === $esi_id_real) {
          $generate_page = TRUE;
        }
      } else {
        if ($esi_id === $esi_id_file) {
          $generate_page = TRUE;
        }
      }
      if ($generate_page) {
        $path_str = substr($filename, strpos($filename, '__') + 2);
        $path = '/' . str_replace('--', '/', $path_str);
        $this->generatePage($path, '', TRUE);
      }
    }
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  public function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  public function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
      return TRUE;
    }
    return (substr($haystack, -$length) === $needle);
  }

  /**
   * Generate all files.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function generateFiles() {
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')->notice('Begin generateFiles()');
    }

    $elapsed_time = $this->generateCodeFiles();
    $elapsed_time += $this->generatePublicFiles();

    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('End generateFiles(), elapsed time: ' . $elapsed_time . ' seconds.');
    }
    return $elapsed_time;
  }

  /**
   * Generate public files.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function generatePublicFiles() {
    $start_time = time();

    // Unpublished files to exclude.
    $exclude_media_ids = $this->excludeMediaIdsUnpublished();
    if (!isset($exclude_media_ids) || empty($exclude_media_ids)) {
      $exclude_media_ids = [];
    }

    $exclude_files = '';
    foreach ($exclude_media_ids as $exclude_media_id) {

      // Get the media entity.
      $media = \Drupal::entityTypeManager()
        ->getStorage('media')
        ->load($exclude_media_id);

      // Get the file id.
      $fid = 0;
      if ($media->hasField('field_media_image')) {
        $fid = $media->get('field_media_image')->getValue()[0]['target_id'];
      } elseif ($media->hasField('field_media_file')) {
        $value = $media->get('field_media_file')->getValue();
        if (!is_null($value) && is_array($value) && count($value) > 0 && array_key_exists('target_id', $value[0])) {
          $fid = $media->get('field_media_file')->getValue()[0]['target_id'];
        }
      } elseif ($media->hasField('field_media_audio_file')) {
        $fid = $media->get('field_media_audio_file')
          ->getValue()[0]['target_id'];
      }
      if ($fid > 0) {
        $file = File::load($fid);
        if (!is_null($file)) {
          $url = Url::fromUri($file->getFileUri());
          $uri = $url->getUri();
          $exclude_file = substr($uri, 9);
          $exclude_files .= $exclude_file . "\r\n";
        }
      }
    }

    // Files to exclude specified in settings.
    $rsync_public_exclude = $this->configFactory->get('static_generator.settings')
      ->get('rsync_public_exclude');
    if (!empty($rsync_public_exclude)) {
      $rsync_public_exclude_array = explode(',', $rsync_public_exclude);
      foreach ($rsync_public_exclude_array as $rsync_public_exclude_file) {
        $exclude_files .= $rsync_public_exclude_file . "\r\n";
      }
    }

    //$tmp_files_directory = $this->fileSystem->realpath('tmp://');
    $public_files_directory = $this->fileSystem->realpath('public://');

    file_unmanaged_save_data($exclude_files, $public_files_directory . '/rsync_public_exclude.tmp', FILE_EXISTS_REPLACE);

    // Create files directory if it does not exist.
    $generator_directory = $this->generatorDirectory(TRUE);
    exec('mkdir -p ' . $generator_directory . '/sites/default/files');

    // rSync public.
    $rsync_public = $this->configFactory->get('static_generator.settings')
      ->get('rsync_public');
    $rsync_public_command = $rsync_public . ' --exclude-from "' . $public_files_directory . '/rsync_public_exclude.tmp" ' . $public_files_directory . '/ ' . $generator_directory . '/sites/default/files';

    exec($rsync_public_command);

    // rSync CSS.
    $css_directory = $this->configFactory->get('static_generator.settings')
      ->get('css_directory');
    $rsync_css = 'rsync -azr ' . $public_files_directory . '/css/ ' . $css_directory;
    exec($rsync_css);

    // rSync JS.
    $js_directory = $this->configFactory->get('static_generator.settings')
      ->get('js_directory');
    $rsync_js = 'rsync -azr ' . $public_files_directory . '/js/ ' . $js_directory;
    exec($rsync_js);

    // Create symlinks to static files directory from css and js directories.
    symlink($css_directory, $generator_directory . '/sites/default/files/css');
    symlink($js_directory, $generator_directory . '/sites/default/files/js');

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('Generate Public Files elapsed time: ' . $elapsed_time .
          ' seconds. (' . $rsync_public . ')');
    }
    return $elapsed_time;
  }

  /**
   * Generate files for core, modules, and themes.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function generateCodeFiles() {
    $start_time = time();

    $rsync_code = $this->configFactory->get('static_generator.settings')
      ->get('rsync_code');
    $generator_directory = $this->generatorDirectory(TRUE);

    // rSync core.
    $rsync_core = $rsync_code . ' ' . DRUPAL_ROOT . '/core ' . $generator_directory;
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('generateCodeFiles() Core: ' . $rsync_core);
    }
    exec($rsync_core);

    // rSync modules.
    $rsync_modules = $rsync_code . ' ' . DRUPAL_ROOT . '/modules ' . $generator_directory;
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('generateCodeFiles() Modules: ' . $rsync_modules);
    }
    exec($rsync_modules);

    // rSync themes.
    $rsync_themes = $rsync_code . ' ' . DRUPAL_ROOT . '/themes ' . $generator_directory;
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('generateCodeFiles() Themes: ' . $rsync_themes);
    }
    exec($rsync_themes);

    // rSync libraries.
    $rsync_libraries = $rsync_code . ' ' . DRUPAL_ROOT . '/libraries ' . $generator_directory;
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('generateCodeFiles() Libraries: ' . $rsync_libraries);
    }
    exec($rsync_libraries);

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    if ($this->verboseLogging()) {
      \Drupal::logger('static_generator')
        ->notice('generateCodeFiles() elapsed time: ' . $elapsed_time . ' seconds.');
    }
    return $elapsed_time;
  }

  /**
   * Generate redirects - requires redirect module.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function generateRedirects() {
    $start_time = time();

    if (\Drupal::moduleHandler()->moduleExists('redirect')) {
      $storage = $this->entityTypeManager->getStorage('redirect');
      $ids = $storage->getQuery()
        ->execute();
      $redirects = $storage->loadMultiple($ids);
      foreach ($redirects as $redirect) {
        $source_url = $redirect->getSourceUrl();
        $target_array = $redirect->getRedirect();
        $target_uri = $target_array['uri'];

        // Grab alias instead of internal url;
        $target_url = \Drupal::service('path.alias_manager')->getAliasByPath(substr($target_uri, 9));
        $this->generateRedirect($source_url, $target_url);
        if ($this->verboseLogging()) {
          \Drupal::logger('static_generator')
            ->notice('generateRedirects() source: ' . $source_url . ' target: ' . $target_url);
        }
      }
    }

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    return $elapsed_time;
  }

  /**
   * Generate a redirect page file.
   *
   * @param string $source_url
   *   The source url.
   * @param string $target_url
   *   The target url.
   *
   * @throws \Exception
   *
   */
  public function generateRedirect($source_url, $target_url) {
    if (empty($source_url) || empty($target_url)) {
      return;
    }

    // Get the redirect markup.
    $redirect_markup = '<html><head><meta http-equiv="refresh" content="0;URL=' . $target_url . '"></head><body><a href="' . $target_url . '">Page has moved to this location.</a></body></html>';

    // Write redirect page files.
    $web_directory = $this->directoryFromPath($source_url);
    $file_name = $this->filenameFromPath($source_url);
    $directory = $this->generatorDirectory() . $web_directory;
    if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      file_unmanaged_save_data($redirect_markup, $directory . '/' . $file_name, FILE_EXISTS_REPLACE);
    }
  }

  /**
   * Get filename from path.
   *
   * @param string $path
   *   The page's path.
   *
   * @return string
   *   The filename.
   *
   * @throws \Exception
   */
  public function filenameFromPath($path) {
    $path_alias = \Drupal::service('path.alias_manager')
      ->getAliasByPath($path);
    $front = $this->configFactory->get('system.site')->get('page.front');
    $front_alias = \Drupal::service('path.alias_manager')
      ->getAliasByPath($front);
    if ($path_alias == $front_alias) {
      $file_name = 'index.html';
    } else {
      $file_name = strrchr($path_alias, '/') . '.html';
      $file_name = substr($file_name, 1);
    }
    return $file_name;
  }

  /**
   * Get page directory from path.
   *
   * @param string $path
   *   The page's path.
   *
   * @return string
   *   The directory and filename.
   *
   * @throws \Exception
   */
  public function directoryFromPath($path) {
    $directory = '';
    $front = $this->configFactory->get('system.site')->get('page.front');
    if ($path != $front) {
      $alias = \Drupal::service('path.alias_manager')
        ->getAliasByPath($path);
      $occur = substr_count($alias, '/');
      if ($occur > 1) {
        $last_pos = strrpos($alias, '/');
        $directory = substr($alias, 0, $last_pos);
      }
    }
    return $directory;
  }

  /**
   * Returns the rendered markup for a path.
   *
   * @param string $path
   *   The path.
   *
   * @param bool $account_switcher
   *
   * This allows caller to switch accounts once, that way the account
   * is not repeatedly switched, if repeated calls to this function are made.
   *
   * @param bool $theme_switcher
   *
   * This allows caller to switch theme once, that way the theme
   * is not repeatedly switched, if repeated calls to this function are made.
   *
   * @return string
   *   The rendered markup. The string '404' is returned if a 404 error is thrown,
   *   the strng 'error' is returned if any other error is thrown.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Theme\MissingThemeDependencyException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function markupForPage($path, $account_switcher = TRUE, $theme_switcher = TRUE) {
    global $base_url;

    // Switch to anonymous user.
    if ($account_switcher) {
      // Generate as Anonymous user.
      \Drupal::service('account_switcher')
        ->switchTo(new AnonymousUserSession());
    }

    // Switch to default theme.
    if ($theme_switcher) {
      $active_theme = $this->themeManager->getActiveTheme();
      $default_theme_name = $this->configFactory->get('system.theme')
        ->get('default');
      $default_theme = $this->themeInitialization->getActiveThemeByName($default_theme_name);
      $this->themeManager->setActiveTheme($default_theme);
    }

    // Get render method (core or guzzle).
    $render_method = $this->configFactory->get('static_generator.settings')
      ->get('render_method');
    $markup = '';

    // Make Request
    $configuration = \Drupal::service('config.factory')
      ->get('static_generator.settings');
    $static_url = $configuration->get('static_url');

    if ($render_method == 'Core') {
      // Internal request using Drupal Core.
      $request = Request::create($path, 'GET', [], [], [], $this->currentRequest->server->all());

      try {
        $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST);
        $markup = $response->getContent();
      } catch (\Exception $exception) {
        // Switch back to active theme.
        if ($theme_switcher) {
          $this->themeManager->setActiveTheme($active_theme);
        }

        // Switch back from anonymous user.
        if ($account_switcher) {
          \Drupal::service('account_switcher')->switchBack();
        }

        watchdog_exception('static_generator', $exception);
        return '';
      }
    } else {
      // Guzzle request using Drupal core (much slower than internal request).
      $client = \Drupal::httpClient(['SERVER_NAME' => $static_url]);
      try {
        $guzzle_host = $this->configFactory->get('static_generator.settings')
          ->get('guzzle_host');
        $guzzle_options = $this->configFactory->get('static_generator.settings')
          ->get('guzzle_options');
        if (!empty($guzzle_options)) {
          $guzzle_array = [];
          $eval_cmd = '$guzzle_array=' . $guzzle_options . ';';
          eval($eval_cmd);
          $response = $client->request('GET', $guzzle_host . $path, $guzzle_array);
        } else {
          $response = $client->request('GET', $guzzle_host . $path);
        }
        if ($response) {
          $markup = $response->getBody();
        }
      } catch (RequestException $exception) {
        // Switch back to active theme.
        if ($theme_switcher) {
          $this->themeManager->setActiveTheme($active_theme);
        }
        // Switch back from anonymous user.
        if ($account_switcher) {
          \Drupal::service('account_switcher')->switchBack();
        }

        if (strpos($exception, '404') !== FALSE) {
          \Drupal::logger('static_generator_404')->notice($path);
          return '404';
        } else {
          watchdog_exception('static_generator', $exception);
          return 'error';
        }

      }
    }

    // Switch back to active theme.
    if ($theme_switcher) {
      $this->themeManager->setActiveTheme($active_theme);
    }

    // Switch back from anonymous user.
    if ($account_switcher) {
      \Drupal::service('account_switcher')->switchBack();
    }

    // @todo need to implement this as a plugin, similar to a migrate process plugin
    $dom = new DomDocument();
    @$dom->loadHTML($markup);
    $finder = new DomXPath($dom);

    // Remove elements with class = block-local-task-block
    $remove_local_tasks = $finder->query("//*[contains(@class, 'block-local-tasks-block')]");
    foreach ($remove_local_tasks as $local_task) {
      $local_task->parentNode->removeChild($local_task);
    }

    // Remove parent of elements with class = node--unpublished
    $remove_unpublished = $finder->query("//*[contains(@class, 'node--unpublished')]");
    foreach ($remove_unpublished as $unpublished) {
      $unpublished->parentNode->parentNode->removeChild($unpublished->parentNode);
    }

    // Render video iframes.
    $iframes = $finder->query("//iframe");
    foreach ($iframes as $iframe) {

      // iframe Markup looks like:
      // '<iframe src="https://www.youtube.com/embed/Z2J_J2DY2-c" frameborder="0" width="480" height="270"></iframe>';

      $iframe_src = $iframe->getAttribute('src');

      // Get Youtube ID.
      $start_pos = strpos($iframe_src, 'youtu.be/');
      if ($start_pos === FALSE) {
        $iframe_src = urldecode($iframe_src);
        $start_pos = strpos($iframe_src, 'youtube.com/watch?v=');
        if ($start_pos === FALSE) {
          $start_pos = strpos($iframe_src, '//www.youtube.com/embed/');
          if ($start_pos !== FALSE) {
            $start_pos += 24;
          }
        } else {
          $start_pos += 20;
        }
      } else {
        $start_pos += 9;
      }

      if ($start_pos === FALSE) {
        continue;
      }

      if (strpos($iframe_src, '//www.youtube.com/embed/') === FALSE) {
        $end_pos = strpos($iframe_src, '&', $start_pos);
      } else {
        $end_pos = strpos($iframe_src, '?', $start_pos);
      }

      $youtube_id = substr($iframe_src, $start_pos, $end_pos - $start_pos);

      if (empty($youtube_id)) {
        continue;
      }

      // Get the width.
      $start_pos = strpos($iframe_src, 'width=') + 6;
      $end_pos = strpos($iframe_src, '&', $start_pos);
      $width = substr($iframe_src, $start_pos, $end_pos - $start_pos);
      if (!isset($width) || strlen($width) == 0) {
        continue;
      }

      // Get the height.
      $start_pos = strpos($iframe_src, 'height=') + 7;
      $end_pos = strpos($iframe_src, '&', $start_pos);
      $height = substr($iframe_src, $start_pos, $end_pos - $start_pos);
      if (!isset($height) || strlen($height) == 0) {
        continue;
      }
      $width = '480';
      $height = '270';

      $src = 'https://www.youtube.com/embed/' . $youtube_id;

      $iframe_element = $dom->createElement('iframe');
      $iframe_element->setAttribute('src', $src);
      $iframe_element->setAttribute('frameborder', '0');
      $iframe_element->setAttribute('width', $width);
      $iframe_element->setAttribute('height', $height);

      $iframe->parentNode->replaceChild($iframe_element, $iframe);
    }

    // Generation of pages for Views pagers.
    if (strpos($path, '?') === FALSE) {
      $last_page_lis = $finder->query("//li[contains(@class, 'pager__item--last')]");
      foreach ($last_page_lis as $last_page_li) {
        $last_page_href = $last_page_li->childNodes->item(1)
          ->getAttribute('href');
        $last_page = intval(substr($last_page_href, 6));
        for ($i = 0; $i <= $last_page; $i++) {
          //$this->queuePage($path . '?page=' . $i, $path . '/page/' . $i);
          $this->generatePage($path . '?page=' . $i, $path . '/page/' . $i);
        }
      }
    }

    // Fix pager links in markup.
    /** @var \DOMElement $node */
    foreach ($finder->query('//a[contains(@href,"?page=")]') as $node) {
      $original_href = $node->getAttribute('href');
      if (strpos($path, '?') === FALSE) {
        $new_path = $path;
      } else {
        $new_path = substr($path, 0, strpos($path, '?'));
      }
      $new_href = $new_path . str_replace('?page=', '/page/', $original_href);
      $node->setAttribute('href', $new_href);
    }

    $markup = $dom->saveHTML();

    // Fix canonical link so it has static site url.
    $configuration = \Drupal::service('config.factory')->get('static_generator.settings');

    // If we are using core, the source URL will be http://default.
    if ($render_method == 'Core') {
      // $markup = str_replace('http://default', $static_url, $markup);
      $markup = str_replace($base_url, '', $markup);
    } else {
      $static_url = $configuration->get('static_url');
      $guzzle_host = $configuration->get('guzzle_host');
      $markup = str_replace($guzzle_host, $static_url, $markup);
    }

    // Convert canonical path to aliased paths.
    $nids = [];
    $pos = 0;
    $i = 0;
    while (strpos($markup, 'node/', $pos) !== FALSE) {
      $i++;
      if ($i > 500) {
        $this->log('> 500 looping in page: ' . $path);
        return $markup;
      }

      $pos = strpos($markup, 'node/', $pos) + 5;
      $next_char = substr($markup, $pos, 1);

      if (!is_numeric($next_char)) {
        continue;
      }
      $nid = $next_char;

      $next_char = substr($markup, $pos + 1, 1);
      if (is_numeric($next_char)) {
        $nid .= $next_char;
      } else {
        $nids[] = $nid;
        continue;
      }

      $next_char = substr($markup, $pos + 2, 1);
      if (is_numeric($next_char)) {
        $nid .= $next_char;
      } else {
        $nids[] = $nid;
        continue;
      }

      $next_char = substr($markup, $pos + 3, 1);
      if (is_numeric($next_char)) {
        $nid .= $next_char;
      } else {
        $nids[] = $nid;
        continue;
      }

      $next_char = substr($markup, $pos + 4, 1);
      if (is_numeric($next_char)) {
        $nid .= $next_char;
      } else {
        $nids[] = $nid;
        continue;
      }

      $next_char = substr($markup, $pos + 5, 1);
      if (is_numeric($next_char)) {
        $nid .= $next_char;
        $nids[] = $nid;
      } else {
        $nids[] = $nid;
        continue;
      }
    }

    $nids = array_unique($nids);
    rsort($nids);
    foreach ($nids as $nid) {
      $node_path = 'node/' . $nid;
      $path_alias = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/' . $node_path);
      $markup = str_replace($node_path, substr($path_alias, 1), $markup);
    }

    // Return the markup.
    return $markup;

  }

  /**
   * Inject ESI markup/save ESI file.
   *
   * @param string $markup
   *   The markup.
   *
   * @param string $path
   *
   * @param array $blocks_processed
   *
   * @param array $sg_esi_processed
   *
   * @param $sg_esi_existing
   *
   * @return string
   *   Markup with ESI's injected.
   */
  public function injectESIs($markup, $path = '', &$blocks_processed = [], &$sg_esi_processed = [], $sg_esi_existing) {

    // Find all of the blocks in the markup.
    // @todo Currently SG does ESI for every block, but specific blocks may
    // @todo be excluded in the SG settings.  May make more sense to work by
    // @todo including blocks instead, or at least have that option.

    $dom = new DomDocument();
    @$dom->loadHTML($markup);
    $finder = new DomXPath($dom);

    $esi_blocks = $this->configFactory->get('static_generator.settings')
      ->get('esi_blocks');

    if ($esi_blocks) {

      $blocks = $finder->query("//*[contains(@class, 'block')]");

      foreach ($blocks as $block) {

        // Make sure class = "block".
        $block_classes_str = $block->getAttribute('class');
        if (!empty($block_classes_str)) {
          $block_classes = explode(' ', $block_classes_str);
          if (!in_array('block', $block_classes)) {
            continue;
          }
        } else {
          continue;
        }

        // Construct block id.
        $block_id = $block->getAttribute('id');
        if (empty($block_id)) {
          continue;
        }
        if (substr($block_id, 0, 6) == 'block-') {
          $block_id = substr($block_id, 6);
        }
        $block_id = str_replace('-', '_', $block_id);

        // Return if block id or block pattern is listed in "block no esi" setting.
        if (!$this->esiBlock($block_id)) {
          continue;
        }

        // Get ESI filename.
        $esi_filename = $block_id;

        // Create the ESI and then replace the block with the ESI markup.
        $esi_markup = '<!--#include virtual="/esi/block/' . Html::escape($esi_filename) . '" -->';
        $esi_element = $dom->createElement('span', $esi_markup);
        $block->parentNode->replaceChild($esi_element, $block);

        // Generate the ESI fragment file.
        if (in_array($block_id, $blocks_processed)) {
          // Return if block has been processed.
          continue;
        } else {
          $this->generateEsiFileByElement($esi_filename, $block, 'block');
          $blocks_processed[] = $block_id;
        }
      }
    }

    $esi_sg_esi = $this->configFactory->get('static_generator.settings')
      ->get('esi_sg_esi');

    if ($esi_sg_esi) {

      // Remove elements with class=sg--remove.
      $remove_elements = $finder->query("//*[contains(@class, 'sg--remove')]");
      foreach ($remove_elements as $remove_element) {
        $remove_element->parentNode->removeChild($remove_element);
      }

      // Process ESI for elements which have a class of sg-esi--<some-id>
      $sg_esi_elements = $finder->query("//*[contains(@class, 'sg-esi--')]");
      foreach ($sg_esi_elements as $element) {

        // Get esi class.
        $classes = $element->getAttribute('class');

        $classes_array = explode(' ', $classes);
        $esi_id = '';
        foreach ($classes_array as $esi_class) {
          if ($this->startsWith($esi_class, 'sg-esi--')) {
            // Remove three dashes - hack for site specific issue, will be removed.
            if ($this->startsWith($esi_class, 'sg-esi---')) {
              continue;
            }
            $esi_id = substr($esi_class, 8);
          }
        }

        // Must have an sg esi id.
        if (empty($esi_id)) {
          continue;
        }

        // Get list of existing sg esi filenames if not provided.
        if (count($sg_esi_existing) == 0) {
          $sg_esi_existing = $this->existingSgEsiFiles();
        }

        // Get ESI filename.
        if (array_key_exists($esi_id, $sg_esi_processed)) {
          // If esi id already processed, use existing file name.
          $esi_filename = $sg_esi_processed[$esi_id];
        } else {
          if (array_key_exists($esi_id, $sg_esi_existing)) {
            // Fragment file with esi_id exists, so use that file name.
            $esi_filename = $sg_esi_existing[$esi_id];
          } else {
            // Get new filename.
            $path_id = \Drupal::service('path.alias_manager')
              ->getPathByAlias($path);
            $path_id = substr($path_id, 1);
            $path_str = str_replace('/', '--', $path_id);
            $esi_filename = $esi_id . '__' . $path_str;
          }
        }

        // Replace the original element with the ESI markup.
        $esi_markup = '<!--#include virtual="/esi/sg-esi/' . Html::escape($esi_filename) . '" -->';
        $esi_element = $dom->createElement('span', $esi_markup);
        $element->parentNode->replaceChild($esi_element, $element);

        // Generate the ESI fragment file.
        if (array_key_exists($esi_id, $sg_esi_processed)) {
          // Return if esi_id has been processed.
          continue;
        } else {
          $this->generateEsiFileByElement($esi_filename, $element, 'sg-esi');
          $sg_esi_processed[$esi_id] = $esi_filename;
        }
      }
    }

    // Return markup with ESI's.
    $markup_esi = $dom->saveHTML();
    $markup_esi = str_replace('&lt;', '<', $markup_esi);
    $markup_esi = str_replace('&gt;', '>', $markup_esi);

    return $markup_esi;
  }

  /**
   * Generate a block fragment file using the block_id and DOM block element.
   *
   * @param $esi_filename
   *   The filename for the generated ESI file.
   *
   * @param $element
   * The dom element getting ESI.
   *
   * @param $directory
   * The target directory (/esi/<directory>)
   */
  public function generateEsiFileByElement($esi_filename, $element, $directory) {

    // Make sure directory exists.
    $directory = $this->generatorDirectory() . '/esi/' . $directory;
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);

    // Generate esi fragment file.
    $markup = $element->ownerDocument->saveHTML($element);
    file_unmanaged_save_data($markup, $directory . '/' . $esi_filename, FILE_EXISTS_REPLACE);
  }

  /**
   * Place page into queue.
   *
   * @param $path
   *
   * @param string $path_generate
   *
   * @return void ;
   */
  public function queuePage($path, $path_generate = '') {
    // Get the queue implementation for SG
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('page_generator');

    // Create new queue item
    $item = new \stdClass();
    $item->path = $path;
    $item->path_generate = $path_generate;
    $queue->createItem($item);
  }

  /**
   * @param $path
   */
  public function processQueue() {

    // Get the queue implementation for SG
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('page_generator');
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')
      ->createInstance('page_generator');

    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item);
        $queue->deleteItem($item);
      } catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        break;
      } catch (\Exception $e) {
        watchdog_exception('static_generator', $e);
      }
    }
  }

  public function processQueuesave($path) {
    /** @var QueueInterface $queue */
    $queue = $this->queueFactory->get('page_generator');
    /** @var QueueWorkerInterface $queue_worker */
    $queue_worker = $this->queueManager->createInstance('page_generator');
  }

  /**
   * Get verbose logging setting.
   *
   * @return boolean;
   */
  public function verboseLogging() {
    $verbose_logging = $this->configFactory->get('static_generator.settings')
      ->get('verbose_logging');
    return $verbose_logging;
  }

  /**
   * Get generate unpublished setting.
   *
   * @return boolean;
   */
  public function generateUnpublished() {
    $gen_unpublished = $this->configFactory->get('static_generator.settings')
      ->get('gen_unpublished');
    return $gen_unpublished;
  }

  /**
   * Get generate index.html setting.
   *
   * @return boolean;
   */
  public function generateIndex() {
    $generate_index = $this->configFactory->get('static_generator.settings')
      ->get('generate_index');
    return $generate_index;
  }

  /**
   * @param $notice
   */
  public function log($notice) {
    \Drupal::logger('static_generator')
      ->notice($notice);
  }

  /**
   * Get generator directory.
   *
   * @param bool $real_path
   *   Get the real path.
   *
   * @return string
   */
  public function generatorDirectory($real_path = FALSE) {
    $generator_directory = $this->configFactory->get('static_generator.settings')
      ->get('generator_directory');
    if ($real_path) {
      $generator_directory = $this->fileSystem->realpath($generator_directory);
    }
    return $generator_directory;
  }

  /**
   * Delete all generated pages and files.  This is done by deleting the top
   * level directory and then re-creating it.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function deleteAll() {
    $elapsed_time = $this->deletePages();
    $elapsed_time += $this->deleteEsi();
    $elapsed_time += $this->deleteDrupal();

    // Elapsed time.
    \Drupal::logger('static_generator')
      ->notice('Delete all elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Delete all generated pages.  Deletes all generated *.html files,
   * and ESI include files.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function deletePages() {
    $start_time = time();

    // Get Drupal dirs setting.
    $drupal = $this->configFactory->get('static_generator.settings')
      ->get('drupal');
    if (!empty($drupal)) {
      $drupal_array = explode(',', $drupal);
      $drupal_array[] = 'esi';
    } else {
      $drupal_array = ['esi'];
    }

    // Get Non Drupal dirs setting.
    $non_drupal = $this->configFactory->get('static_generator.settings')
      ->get('non_drupal');
    $non_drupal_array = [];
    if (!empty($non_drupal)) {
      $non_drupal_array = explode(',', $non_drupal);
    }

    $generator_directory = $this->generatorDirectory(TRUE);
    $files = file_scan_directory($generator_directory, '/.*/', ['recurse' => FALSE]);
    foreach ($files as $file) {
      $filename = $file->filename;
      $html_file = substr($filename, -strlen('html')) == 'html';
      if ($html_file && !in_array($filename, $non_drupal_array)) {
        file_unmanaged_delete_recursive($file->uri, $callback = NULL);
      } else {
        if (!in_array($filename, $drupal_array) && !in_array($filename, $non_drupal_array)) {
          if ($filename == 'node') {
            $node_files = file_scan_directory($generator_directory . '/node', '/.*/', ['recurse' => TRUE]);
            foreach ($node_files as $node_file) {
              file_unmanaged_delete_recursive($node_file->uri, $callback = NULL);
            }
            file_unmanaged_delete_recursive($file->uri, $callback = NULL);
          } else {
            file_unmanaged_delete_recursive($file->uri, $callback = NULL);
            exec('rm -rf ' . $file->uri);
          }
        }
      }
    }

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    \Drupal::logger('static_generator')
      ->notice('Delete Page elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Deletes all generated block include files in /esi/blocks.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function deleteEsi() {
    $start_time = time();

    // Delete Blocks
    $dir = $this->generatorDirectory(TRUE) . '/esi/block';

    // Delete ESIs include files and the esi directory.
    $esi_files = file_scan_directory($dir, '/.*/', ['recurse' => TRUE]);
    foreach ($esi_files as $block_esi_file) {
      file_unmanaged_delete_recursive($block_esi_file->uri, $callback = NULL);
    }
    file_unmanaged_delete_recursive($dir, $callback = NULL);

    // Delete sg_esi tags
    $dir = $this->generatorDirectory(TRUE) . '/esi/sg-esi';

    // Delete sg esi include files and the sg-esi directory.
    $esi_files = file_scan_directory($dir, '/.*/', ['recurse' => TRUE]);
    foreach ($esi_files as $block_esi_file) {
      file_unmanaged_delete_recursive($block_esi_file->uri, $callback = NULL);
    }
    file_unmanaged_delete_recursive($dir, $callback = NULL);

    // Delete /esi directory.
    $dir = $this->generatorDirectory(TRUE) . '/esi';
    file_unmanaged_delete_recursive($dir, $callback = NULL);

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    \Drupal::logger('static_generator')
      ->notice('Delete blocks elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Delete a single page.
   *
   * @param string $path
   *   The page's path.
   *
   * @throws \Exception
   */
  public function deletePage($path) {
    $web_directory = $this->directoryFromPath($path);
    $file_name = $this->filenameFromPath($path);
    $full_file_name = $this->generatorDirectory() . $web_directory . '/' . $file_name;
    file_unmanaged_delete($full_file_name);
    \Drupal::logger('static_generator')
      ->notice('Deleted page: ' . $full_file_name);
  }

  /**
   * Delete Drupal directories.
   *
   * @return int
   *   Execution time in seconds.
   *
   * @throws \Exception
   */
  public function deleteDrupal() {

    $start_time = time();

    // Get Drupal dirs setting.
    $drupal = $this->configFactory->get('static_generator.settings')
      ->get('drupal');
    $drupal_array = [];
    if (!empty($drupal)) {
      $drupal_array = explode(',', $drupal);
    }

    $generator_directory = $this->generatorDirectory(TRUE);
    $files = file_scan_directory($generator_directory, '/.*/', ['recurse' => FALSE]);
    foreach ($files as $file) {
      $filename = $file->filename;
      if (in_array($filename, $drupal_array)) {
        file_unmanaged_delete_recursive($file->uri, $callback = NULL);
        exec('rm -rf ' . $file->uri);
      }
    }

    // Elapsed time.
    $end_time = time();
    $elapsed_time = $end_time - $start_time;
    \Drupal::logger('static_generator')
      ->notice('deleteDrupal() elapsed time: ' . $elapsed_time . ' seconds.');
    return $elapsed_time;
  }

  /**
   * Exclude media that is not published (e.g. Draft or Archived).
   *
   * @throws \Exception
   */
  public function excludeMediaIdsUnpublished() {
    $query = \Drupal::entityQuery('media');
    $query->condition('status', 0);
    $exclude_media_ids = $query->execute();
    return $exclude_media_ids;
  }

  /**
   * List file name and update time for a path.
   *
   * @param $path
   *
   * @return string
   * @throws \Exception
   */
  public function fileInfo($path) {
    $file_name = $this->generatorDirectory(TRUE) . $this->directoryFromPath($path) . '/' .
    $this->filenameFromPath($path);
    if (file_exists($file_name)) {
      $return_string = $file_name . '<br/>' . date("F j, Y, g:i a", filemtime($file_name));
    } else {
      $return_string = 'Static page file not found.';
    }
    return $return_string;
  }

  /**
   * Get generation info for a page.
   *
   * @param $path
   *
   * @param $entity
   * @param array $form
   *
   * @param bool $details
   *
   * @return array
   *
   * @throws \Exception
   */
  public function generationInfoForm($path, $entity = NULL, &$form = [], $details = FALSE) {

    // Name and date info for static file.
    $file_info = $this->fileInfo($path);

    // Get path alias for path.
    $path_alias = \Drupal::service('path.alias_manager')
      ->getAliasByPath($path);

    // Get static URL setting.
    $configuration = \Drupal::service('config.factory')
      ->get('static_generator.settings');
    $static_url = $configuration->get('static_url');

    $markup = '<br/>' . $file_info . '<br/><br/>';
    if (!is_null($entity) && $entity->isPublished()) {
      $markup .= '<a  target="_blank" href="' . $path . '/gen' . '">' . t("Generate Static Page") . '</a>';
    } else {
      $markup .= t('This item is unpublished and may not be generated.');
    }
    $markup .= '<br/><br/><a  target="_blank" href="' . $static_url . $path_alias . '">' . t("View Static Page") . '</a>';

    $form['static_generator'] = [
      '#title' => t('Static Generation'),
      '#description' => t(''),
      '#group' => 'advanced',
      '#open' => FALSE,
      'markup' => [
        '#markup' => $markup,
      ],
      '#weight' => 1000,
    ];

    // Create form details.
    if ($details) {
      $form['static_generator']['#type'] = 'details';
    }

    return $form;
  }

  /**
   * @param $path
   *
   * @param $entity
   *
   * @return \Drupal\Component\Render\MarkupInterface
   * @throws \Exception
   */
  public function generationInfo($path, $entity) {
    $form = $this->generationInfoForm($path, $entity);
    $markup = $this->renderer->render($form);
    return $markup;
  }

}
