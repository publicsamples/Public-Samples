<?php

namespace Drupal\static_generator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Returns responses for StaticGenerator module routes.
 */
class StaticGeneratorController extends ControllerBase {

  /**
   * Test route for debugging.
   *
   * @return array
   */
  public function sgTest() {
    $build = [
      //'#markup' => \Drupal::service('static_generator')->processQueue(),
      //'#markup' => \Drupal::service('static_generator')->generateMedia('remote_video',FALSE, 0, 10000),
    ];
    return $build;
  }

  /**
   * Generate a specified node page.
   *
   * @param $nid
   * The node id.
   *
   * @return array
   * The markup.
   */
  public function generateNode($nid) {
    try {
      \Drupal::service('static_generator')
        ->generatePage('/node/' . $nid, '', FALSE, TRUE);
    } catch (\Exception $exception) {
    }

    $build = [
      '#markup' => $this->t('Page generation complete.'),
    ];
    return $build;
  }

  /**
   * Static generation info for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   * The markup.
   */
  public function generationInfoNode(NodeInterface $node) {
    $build = [
      '#markup' => \Drupal::service('static_generator')
        ->generationInfo('/node/' . $node->id(), $node),
    ];
    return $build;
  }

  /**
   * Generate a specified media page.
   *
   * @param $mid
   * The media id.
   *
   * @return array
   * The markup.
   */
  public function generateMedia($mid) {
    try {
      \Drupal::service('static_generator')
        ->generatePage('/media/' . $mid, '', FALSE, TRUE);
    } catch (\Exception $exception) {
    }

    $build = [
      '#markup' => $this->t('Page generation complete.'),
    ];
    return $build;
  }

  /**
   * Static generation info for a media.
   *
   * @param \Drupal\media\MediaInterface $media
   *
   * @return array
   * The markup.
   */
  public function generationInfoMedia(MediaInterface $media) {
    $build = [
      '#markup' => \Drupal::service('static_generator')
        ->generationInfo('/media/' . $media->id(), $media),
    ];
    return $build;
  }
  
}
