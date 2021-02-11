/**
 * @file
 * Audiofield build AudioJs audio player.
 */

(($, Drupal) => {
  'use strict';

  Drupal.AudiofieldAudiojs = {};

  /**
   * Generate an audio.js player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {jQuery} settings
   *   The Drupal settings for this player.
   */
  Drupal.AudiofieldAudiojs.generate = (context, settings) => {
    // Create the media player.
    $.each($(context).find(`#${settings.element}`).once('generate-audiojs'), (index, item) => {
      // Initialize the audio player.
      const audioPlayer = audiojs.create($(item).find('audio').get(0), {
        css: false,
        createPlayer: {
          markup: false,
          playPauseClass: 'play-pauseZ',
          scrubberClass: 'scrubberZ',
          progressClass: 'progressZ',
          loaderClass: 'loadedZ',
          timeClass: 'timeZ',
          durationClass: 'durationZ',
          playedClass: 'playedZ',
          errorMessageClass: 'error-messageZ',
          playingClass: 'playingZ',
          loadingClass: 'loadingZ',
          errorClass: 'errorZ',
        },
        // Handle the end of a track.
        trackEnded: () => {
          let next = $(context).find(`#${settings.element} ol li.playing`).next();
          if (!next.length) {
            next = $(context).find(`#${settings.element} ol li:first`);
          }
          next.addClass('playing').siblings().removeClass('playing');
          audioPlayer.load($('a', next).attr('data-src'));
          audioPlayer.play();
        },
      });

      // Load in the first track.
      $(item).find('ol li:first').addClass('playing');
      audioPlayer.load($(item).find('ol a:first').attr('data-src'));

      // Load in a track on click.
      $(item).find('ol li').click((event) => {
        event.preventDefault();
        $(event.currentTarget).addClass('playing').siblings().removeClass('playing');
        audioPlayer.load($('a', event.currentTarget).attr('data-src'));
        audioPlayer.play();
      });
    });
  };

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of audio.js audio players.
   */
  Drupal.behaviors.audiofieldaudiojs = {
    attach: (context, settings) => {
      $.each(settings.audiofieldaudiojs, (key, settingEntry) => {
        Drupal.AudiofieldAudiojs.generate(context, settingEntry);
      });
    },
  };
})(jQuery, Drupal);
