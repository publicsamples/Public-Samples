/**
 * @file
 * Audiofield build jPlayer audio players of various types.
 */

(($, Drupal) => {
  'use strict';

  Drupal.AudiofieldJplayer = {};

  /**
   * Generate a jplayer player for the default single file layout.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {jQuery} settings
   *   The Drupal settings for this player.
   */
  Drupal.AudiofieldJplayer.generate = (context, settings) => {
    // Create the media player.
    $(`#jquery_jplayer_${settings.unique_id}`, context).once('generate-jplayer').jPlayer(
      {
        cssSelectorAncestor: `#jp_container_${settings.unique_id}`,
      },
      {
        ready: () => {
          const mediaArray = {
            title: settings.description,
          };
          mediaArray[settings.filetype] = settings.file;
          $(`#jquery_jplayer_${settings.unique_id}`, context).jPlayer('setMedia', mediaArray);
        },
        canplay: () => {
          // Handle autoplay.
          if (!!settings.autoplay) {
            $(`#jquery_jplayer_${settings.unique_id}`, context).jPlayer('play');
          }
        },
        swfPath: '/libraries/jplayer/dist/jplayer',
        supplied: settings.filetype,
        wmode: 'window',
        useStateClassSkin: true,
        autoBlur: false,
        preload: settings.lazyload,
        smoothPlayBar: true,
        keyEnabled: true,
        remainingDuration: false,
        toggleDuration: false,
        volume: settings.volume,
      },
    );
  };

  /**
   * Generate a jplayer formatter for a player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {jQuery} settings
   *   The Drupal settings for this player.
   */
  Drupal.AudiofieldJplayer.generatePlaylist = (context, settings) => {
    $.each($(context).find(`#jquery_jplayer_${settings.unique_id}`).once('generate-jplayer'), (index, item) => {
      // Initialize the container audio player.
      const thisPlaylist = new jPlayerPlaylist({
        jPlayer: $(item),
        cssSelectorAncestor: `#jp_container_${settings.unique_id}`,
      }, [], {
        canplay: () => {
          // Handle autoplay.
          if (!!settings.autoplay) {
            $(item).jPlayer('play');
          }
        },
        playlistOptions: {
          enableRemoveControls: false,
        },
        swfPath: '/libraries/jplayer/dist/jplayer',
        wmode: 'window',
        useStateClassSkin: true,
        autoBlur: false,
        preload: settings.lazyload,
        smoothPlayBar: true,
        keyEnabled: true,
        volume: settings.volume,
      });

      // Loop over each file.
      $.each(settings.files, (key, fileEntry) => {
        // Build the media array.
        const mediaArray = {
          title: fileEntry.description,
        };
        mediaArray[fileEntry.filetype] = fileEntry.file;
        // Add the file to the playlist.
        thisPlaylist.add(mediaArray);
      });
    });
  };

  /**
   * Generate a jplayer circle player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {array} file
   *   The audio file for which we are generating a player.
   */
  Drupal.AudiofieldJplayer.generateCircle = (context, file) => {
    // Create the media player.
    $.each($(context).find(`#jquery_jplayer_${file.fid}`).once('generate-jplayer'), (index, item) => {
      // Build the media array for this player.
      const mediaArray = {};
      mediaArray[file.filetype] = file.file;

      // Initialize the player.
      new CirclePlayer(
        $(item),
        mediaArray,
        {
          cssSelectorAncestor: `#cp_container_${file.fid}`,
          canplay: () => {
            // Handle autoplay.
            if (!!file.autoplay) {
              $(item).jPlayer('play');
            }
          },
          swfPath: '/libraries/jplayer/dist/jplayer',
          wmode: 'window',
          preload: file.lazyload,
          keyEnabled: true,
          supplied: file.filetype,
        },
      );
    });
  };

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of Jplayer audio players.
   */
  Drupal.behaviors.audiofieldjplayer = {
    attach: (context, settings) => {
      $.each(settings.audiofieldjplayer, (key, settingEntry) => {
        // Default audio player.
        if (settingEntry.playertype === 'default') {
          // We can just initialize the audio player direcly.
          Drupal.AudiofieldJplayer.generate(context, settingEntry);
        }
        // Playlist audio player.
        else if (settingEntry.playertype === 'playlist') {
          Drupal.AudiofieldJplayer.generatePlaylist(context, settingEntry);
        }
        // Circle audio player.
        else if (settingEntry.playertype === 'circle') {
          // Loop over the files.
          $.each(settingEntry.files, (key2, fileEntry) => {
            // Create the player.
            Drupal.AudiofieldJplayer.generateCircle(context, fileEntry);
          });
        }
      });
    },
  };
})(jQuery, Drupal);
