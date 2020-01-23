/**
 * @file
 * Audiofield build WordPress audio player.
 */

(($, Drupal) => {
  'use strict';

  Drupal.AudiofieldWordpress = {};

  /**
   * Generate a wordpress player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {array} file
   *   The audio file for which we are generating a player.
   * @param {jQuery} settings
   *   The Drupal settings for this player.
   */
  Drupal.AudiofieldWordpress.generate = (context, file, settings) => {
    let autostartSetting = 'no';
    if (!!settings.autoplay) {
      autostartSetting = 'yes';
    }
    $.each($(context).find(`#wordpressaudioplayer_${file.unique_id}`).once('generate-waveform'), (index, item) => {
      AudioPlayer.embed($(item).attr('id'), {
        soundFile: file.file,
        titles: file.title,
        autostart: autostartSetting,
        loop: 'no',
        initialvolume: settings.volume,
        checkpolicy: 'yes',
        animation: settings.animate,
      });
    });
  };

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of Wordpress audio players.
   */
  Drupal.behaviors.audiofieldwordpress = {
    attach: function buildWordpressPlayers(context, settings) {
      $.each(settings.audiofieldwordpress, (key, settingEntry) => {
        // Initialize the audioplayer.
        AudioPlayer.setup('/libraries/wordpress-audio/player.swf', {
          width: 400,
          initialvolume: settingEntry.volume,
          transparentpagebg: 'yes',
        });
        // Loop over the files.
        $.each(settingEntry.files, (key2, fileEntry) => {
          // Generate the player for each file.
          Drupal.AudiofieldWordpress.generate(context, fileEntry, settingEntry);
        });
      });
    },
  };
})(jQuery, Drupal);
