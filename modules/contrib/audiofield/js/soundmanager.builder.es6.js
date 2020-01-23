/**
 * @file
 * Audiofield build SoundManager audio player.
 */

(($, Drupal) => {
  'use strict';

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of Soundmanager audio players.
   */
  Drupal.behaviors.audiofieldsoundmanager = {
    attach: (context, settings) => {
      // Soundmanager intercepts everything so the setup is very simple.
      soundManager.setup({
        // Required: path to directory containing SM2 SWF files.
        url: settings.audiofieldsoundmanager.swfpath,
        preferFlash: false,
        defaultOptions: {
          volume: settings.audiofieldsoundmanager.volume,
        },
      });
    },
  };
})(jQuery, Drupal);
