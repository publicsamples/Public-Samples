/**
 * @file
 * Pagerer slider pager scripts.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.pagererSlider = {

    attach: function (context, settings) {

      /**
       * Constants.
       */
      var PAGERER_LEFT = -1;
      var PAGERER_RIGHT = 1;

      /**
       * Pagerer slider jQuery UI slider event binding.
       */
      $('.pagerer-slider', context).once('pagerer').each(function (index) {
        Drupal.pagerer.state.isRelocating = false;
        this.pagererState = Drupal.pagerer.evalState(this);

        // Create slider.
        var sliderBar = $(this);
        sliderBar.slider({
          min: 0,
          max: this.pagererState.total - 1,
          step: 1,
          value: this.pagererState.current,
          range: 'min',
          animate: true
        });

        // Set slider handle dimensions and text.
        var sliderHandle = sliderBar.find('.ui-slider-handle');
        sliderHandle
          .css('width', (String(Drupal.pagerer.indexToTag(this.pagererState.total - 1, this.pagererState, 'page')).length + 2) + 'em')
          .css('height', Math.max(sliderHandle.height(), 16) + 'px')
          .css('line-height', Math.max(sliderHandle.height(), 16) + 'px')
          .css('margin-left', -sliderHandle.width() / 2)
          .text(Drupal.pagerer.indexToTag(this.pagererState.current, this.pagererState, 'page'))
          .on('blur', function (event) {
            Drupal.pagerer.reset();
            var sliderBar = $(this).parent().get(0);
            if (!sliderBar.pagererState.spinning) {
              sliderBar.pagererState.spinning = true;
              $(sliderBar).slider('option', 'value', sliderBar.pagererState.current);
              $(this).text(Drupal.pagerer.indexToTag(sliderBar.pagererState.current, sliderBar.pagererState, 'page'));
              sliderBar.pagererState.spinning = false;
            }
          });

        // Set slider bar dimensions.
        if (this.pagererState.sliderWidth) {
          sliderBar.css('width', (this.pagererState.sliderWidth + 'em'));
        }
        sliderBar
          .css('margin-left', sliderHandle.width() / 2)
          .css('margin-right', sliderHandle.width() / 2);

        var pixelsPerStep = sliderBar.width() / this.pagererState.total;
        // If autodetection of navigation action, determine whether to
        // use tickmark or timeout.
        if (this.pagererState.action === 'auto') {
          if (pixelsPerStep > 3) {
            this.pagererState.action = 'timeout';
          }
          else {
            this.pagererState.action = 'tickmark';
          }
        }
        // If autodetection of navigation icons, determine whether to
        // hide icons.
        if (this.pagererState.icons === 'auto' && pixelsPerStep > 3) {
          $(this).parents('.pager').find('.pagerer-slider-control-icon').parent().hide();
        }
        // Add information to user to click on the tickmark to start page
        // relocation.
        if (this.pagererState.action === 'tickmark') {
          var title = $(this).attr('title');
          $(this).attr('title', title + ' ' + this.pagererState.tickmarkTitle);
        }
      })
        .on('slide', function (event, ui) {
          Drupal.pagerer.reset();
          $(this).find('.ui-slider-handle').text(Drupal.pagerer.indexToTag(ui.value, this.pagererState, 'page'));
        })
        .on('slidechange', function (event, ui) {

          var sliderBar = this;
          var sliderHandle = $(this).find('.ui-slider-handle');
          var sliderHandleIcon;

          // Set handle text to widget value.
          sliderHandle.text(Drupal.pagerer.indexToTag(ui.value, this.pagererState, 'page'));

          // If currently sliding the handle via navigation icons,
          // do nothing.
          if (this.pagererState.spinning) {
            return false;
          }

          // If selected same page as current, do nothing.
          if (ui.value === this.pagererState.current) {
            return false;
          }

          // Relocate immediately to target page if no
          // tickmark/timeout confirmation required.
          if (this.pagererState.action === 'timeout' && this.pagererState.timeout === 0) {
            sliderHandle.append('<div class="pagerer-slider-handle-icon"/>');
            sliderHandleIcon = sliderHandle.find('.pagerer-slider-handle-icon');
            Drupal.pagerer.relocate(this, ui.value);
            return false;
          }

          // Otherwise, add a tickmark or clock icon to the handle text,
          // to be clicked to activate page relocation.
          sliderHandle.text(Drupal.pagerer.indexToTag(ui.value, this.pagererState, 'page') + ' ');
          if (this.pagererState.action === 'timeout') {
            sliderHandle.append('<div class="pagerer-slider-handle-icon throbber"/>');
          }
          else {
            sliderHandle.append('<div class="pagerer-slider-handle-icon ui-icon ui-icon-check"/>');
          }

          // Bind page relocation to mouse clicking on the icon.
          sliderHandleIcon = sliderHandle.find('.pagerer-slider-handle-icon');
          sliderHandleIcon.on('mousedown', function (event) {
            Drupal.pagerer.reset();
            // Remove icon.
            $(sliderBar).find('.pagerer-slider-handle-icon').remove();
            // Relocate.
            Drupal.pagerer.relocate(sliderBar, ui.value);
            return false;
          });

          // Bind page relocation to timeout of timeout.
          if (this.pagererState.action === 'timeout') {
            Drupal.pagerer.reset();
            Drupal.pagerer.state.timeoutAction = setTimeout(function () {
              // Remove icon.
              $(sliderBar).find('.pagerer-slider-handle-icon').removeClass('ui-icon').removeClass('throbber');
              // Relocate.
              Drupal.pagerer.relocate(sliderBar, ui.value);
              return false;
            }, this.pagererState.timeout);
          }

        });

      /**
        * Pagerer slider control icons event binding.
        *
        * The navigation icons serve as an helper for the slider positioning,
        * to fine-tune the selection. Once mouse is pressed on an icon, the
        * slider handle is moved +/- one value. If mouse is kept pressed, the
        * slider handle will move continuosly. When mouse is released or moved
        * away from the icon, sliding will stop and the handle status will be
        * processed through slider 'slidechange' event triggered by the
        * sliderOffsetValue() function.
        */
      $('.pagerer-slider-control-icon', context)
        .on('mousedown', function (event) {
          Drupal.pagerer.reset();
          var slider = $(this).parents('.pager').find('.pagerer-slider').get(0);
          slider.pagererState.spinning = true;
          var offset = $(this).hasClass('ui-icon-circle-minus') ? PAGERER_LEFT : PAGERER_RIGHT;
          sliderOffsetValue(slider, offset);
          Drupal.pagerer.state.intervalAction = setInterval(function () {
            Drupal.pagerer.state.intervalCount++;
            if (Drupal.pagerer.state.intervalCount > 10) {
              sliderOffsetValue(slider, offset);
            }
          }, 50);
        })
        .on('mouseup mouseleave', function () {
          var slider = $(this).parents('.pager').find('.pagerer-slider').get(0);
          if (slider.pagererState.spinning) {
            Drupal.pagerer.state.intervalCount = 0;
            clearInterval(Drupal.pagerer.state.intervalAction);
            slider.pagererState.spinning = false;
            sliderOffsetValue(slider, 0);
            $(slider).find('.ui-slider-handle').focus();
          }
        });

      /**
       * Update value based on an offset.
       *
       * @param {HTMLElement} element
       *   Slider element.
       * @param {number} offset
       *   Offset from current value.
       */
      function sliderOffsetValue(element, offset) {
        var newValue = $(element).slider('option', 'value') + offset;
        var maxValue = $(element).slider('option', 'max');
        if (newValue >= 0 && newValue <= maxValue) {
          $(element).slider('option', 'value', newValue);
        }
      }
    },

    detach: function (context, settings) {
      $('.pagerer-slider', context).each(function (index) {
        Drupal.pagerer.detachState(this);
      });
    }
  };
})(jQuery);
