<?php

namespace Drupal\Tests\static_generator\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Routing\Route;

/**
 * Verifies operation of the Static Generator service.
 *
 * @group static_generator
 */
class StaticGeneratorTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'static_generator',
  ];

  /**
   * Installation profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissionsAdmin = [
    'administer static generator',
    'access administration pages',
    'administer users',
    'administer account settings',
    'administer site configuration',
    'administer user fields',
    'administer user form display',
    'administer user display',
  ];

  /**
   * {@inheritdoc}
   */
  public function setup() {
    parent::setup();

    // Create a page node.
    $this->drupalCreateNode(['type' => 'page', 'title' => 'Test']);

  }

  /**
   * Tests static generator caching of route.
   *
   * @param Route $route
   * The route to cache.
   */
  public function testCacheRoute() {
    $this->assertTrue(TRUE);
  }

  /**
   * Tests clearing the static generation cache.
   */
  public function testCacheClear() {
    $this->assertTrue(TRUE);
  }

  /**
   * Tests generating markup for a single route.
   */
  public function testGenerateStaticMarkupForRoute() {
    $static_markup = \Drupal::service('static_generator.static_generator')->generateStaticMarkupForNode(1);
    $this->assertContains('html', $static_markup);
  }

  /**
   * Tests full generation.
   */
  public function testGenerateAll() {
    $this->assertTrue(TRUE);
  }

}
