/**
 * @file
 * Audiofield build MediaElement audio player.
 */

(($, Drupal) => {
  'use strict';

  Drupal.AudiofieldMediaelement = {};

  /**
   * Generate a mediaelement player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {array} file
   *   The audio file for which we are generating a player.
   * @param {jQuery} settings
   *   The Drupal settings for this player..
   */
  Drupal.AudiofieldMediaelement.generate = (context, file, settings) => {
    // Create the media player.
    $(file, context).once('generate-mediaelement').mediaelementplayer({
      startVolume: settings.volume,
      loop: false,
      enableAutosize: true,
      isVideo: false,
    });
  };

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of MediaElement audio players.
   */
  Drupal.behaviors.audiofieldmediaelement = {
    attach: (context, settings) => {
      $.each(settings.audiofieldmediaelement, (key, settingEntry) => {
        // Loop over each file.
        $.each(settingEntry.elements, (key2, fileEntry) => {
          // Create the media player.
          Drupal.AudiofieldMediaelement.generate(context, fileEntry, settingEntry);
        });
      });
    },
  };
})(jQuery, Drupal);
