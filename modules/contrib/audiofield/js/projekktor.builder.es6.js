/**
 * @file
 * Audiofield build Projekktor audio player.
 */

(($, Drupal) => {
  'use strict';

  Drupal.AudiofieldProjekktor = {};

  /**
   * Generate a projekktor player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {array} file
   *   The audio file for which we are generating a player.
   * @param {jQuery} settings
   *   The Drupal settings for this player..
   */
  Drupal.AudiofieldProjekktor.generate = (context, file, settings) => {
    $.each($(context).find(`#${file}`).once('generate-projekktor'), (index, item) => {
      projekktor($(item), {
        debug: false,
        playerFlashMP4: settings.swfpath,
        playerFlashMP3: settings.swfpath,
        enableFullscreen: false,
        streamType: 'http',
        controls: true,
        thereCanBeOnlyOne: true,
        volume: settings.volume,
        autoplay: !!settings.autoplay,
        plugin_display: {},
      }, {});
    });
  };

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of Projekktor audio players.
   */
  Drupal.behaviors.audiofieldprojekktor = {
    attach: (context, settings) => {
      // Have to encapsulate because of the way library is created.
      jQuery(() => {
        $.each(settings.audiofieldprojekktor, (key, settingEntry) => {
          // Loop over the attached files.
          $.each(settingEntry.files, (key2, file) => {
            // Create the audioplayer for each file.
            Drupal.AudiofieldProjekktor.generate(context, file, settingEntry);
          });
        });
      });
    },
  };
})(jQuery, Drupal);
