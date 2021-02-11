/**
 * @file
 * Attaches the behaviors for the Views-Jump-Menu module.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.viewsJumpMenu = {
    attach: function (context, settings) {
      $('.js-viewsJumpMenu', context).on('change', function () {
        window.location = $(this).find(':selected').data('url');
      });
    }
  };

})(jQuery, Drupal);
