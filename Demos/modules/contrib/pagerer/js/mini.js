/**
 * @file
 * Pagerer mini pager scripts.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.pagererMini = {

    attach: function (context, settings) {

      /**
       * Pagerer page input box event binding.
       */
      $('.pagerer-page', context).once('pagerer').each(function (index) {
        Drupal.pagerer.state.isRelocating = false;
        this.pagererState = Drupal.pagerer.evalState(this);
        // Item ranges do not really work on widget.
        if (this.pagererState.display === 'item_ranges') {
          this.pagererState.display = 'items';
        }
        // Page number formatting does not work in widget.
        if (this.pagererState.display === 'items') {
          this.pagererState.pageTag = {
            page_current: '@item',
            page_previous: '@item',
            page_next: '@item'
          };
        }
        else {
          this.pagererState.pageTag = {
            page_current: '@number',
            page_previous: '@number',
            page_next: '@number'
          };
        }
        // Adjust width of the input box.
        if (this.pagererState.widgetResize) {
          var valueLength = String(Drupal.pagerer.indexToTag(this.pagererState.total - 1, this.pagererState, 'page')).length;
          $(this).css({
            width: (valueLength + 1) + '.5em'
          });
        }
        // Adjust the navigation button.
        if (this.pagererState.widgetButton !== 'no') {
          var button = $(this).parent().find('.pagerer-page-button');
          button.css('visibility', 'hidden');
          if (this.pagererState.widgetButton === 'auto') {
            var inputHeight = $(this).outerHeight(true);
            button.css('height', inputHeight + 'px');
          }
        }
      })
        .on('change', function (event) {
          // Show the navigation button.
          $(this).parent().find('.pagerer-page-button').css('visibility', 'visible');
        })
        .on('focus', function (event) {
          Drupal.pagerer.reset();
          this.select();
          $(this).addClass('pagerer-page-has-focus');
        })
        .on('blur', function (event) {
          $(this).removeClass('pagerer-page-has-focus');
        })
        .on('keydown', function (event) {
          switch (event.keyCode) {
            case 13:
            case 10:
              // Return key pressed, relocate.
              var targetPage = Drupal.pagerer.valueToIndex($(this).val(), this);
              if (targetPage !== this.pagererState.current) {
                Drupal.pagerer.relocate(this, targetPage);
              }
              event.stopPropagation();
              event.preventDefault();
              return false;

            case 27:
              // Escape.
              $(this).val(Drupal.pagerer.indexToTag(this.pagererState.current, this.pagererState, 'page'));
              // Hide the navigation button.
              $(this).parent().find('.pagerer-page-button').css('visibility', 'hidden');
              return false;

            case 33:
              // Page up.
              offsetValue(this, 5);
              return false;

            case 34:
              // Page down.
              offsetValue(this, -5);
              return false;

            case 35:
              // End.
              offsetValue(this, 'last');
              return false;

            case 36:
              // Home.
              offsetValue(this, 'first');
              return false;

          }
        });

      /**
        * Pagerer page button event binding.
        */
      $('.pagerer-page-button', context).each(function (index) {
        $(this).button();
        $(this).on('click', function (event) {
          var pageWidget = $(this).parent().find('.pagerer-page').get(0);
          var targetPage = Drupal.pagerer.valueToIndex($(pageWidget).val(), pageWidget);
          if (targetPage !== pageWidget.pagererState.current) {
            $(this).css('visibility', 'hidden');
            Drupal.pagerer.relocate(pageWidget, targetPage);
          }
        });
      });

      /**
       * Update value based on an offset.
       *
       * @param {HTMLElement} element
       *   Input box element.
       * @param {number} offset
       *   Offset from current value.
       */
      function offsetValue(element, offset) {
        var widgetValue = Drupal.pagerer.valueToIndex($(element).val(), element);
        var newValue;
        if (offset === 'first') {
          newValue = 0;
        }
        else if (offset === 'last') {
          newValue = element.pagererState.total - 1;
        }
        else {
          newValue = widgetValue + offset;
          if (newValue < 0) {
            newValue = 0;
          }
          else if (newValue >= element.pagererState.total) {
            newValue = element.pagererState.total - 1;
          }
        }
        if (newValue !== widgetValue) {
          $(element).val(Drupal.pagerer.indexToTag(newValue, element.pagererState, 'page'));
          $(element).trigger('change');
        }
      }

    },

    detach: function (context, settings) {
      $('.pagerer-page', context).each(function (index) {
        Drupal.pagerer.detachState(this);
      });
    }
  };
})(jQuery);
