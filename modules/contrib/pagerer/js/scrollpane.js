/**
 * @file
 * Pagerer scrollpane pager scripts.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.pagererScrollpane = {

    attach: function (context, settings) {

      /**
       * Constants.
       */
      var PAGERER_LEFT = -1;
      var PAGERER_RIGHT = 1;

      /**
       * Pagerer embedded scrollpane event binding.
       */
      $('.pagerer-scrollpane-embedded', context).once('pagerer').each(function (index) {
        Drupal.pagerer.state.isRelocating = false;

        // Get the scrollpane components, i.e. the embedded pager, the
        // pager wrapped within it, and the wrapped pager elements.
        var innerPager = $(this).get(0);
        var pagerList = $(this).find('.pager__items').get(0);
        var viewport = $(this).find('.pagerer-viewport');
        var pagerElements = $(pagerList).find('li');
        var pagerPages = $(pagerList).find('li:not(.pagerer-separator)');
        var pagerSeparators = $(pagerList).find('li.pagerer-separator');
        var cellHeight;
        var innerPagerWidth;
        var pagerWidth;

        // Attach state variables to the wrapped pager.
        innerPager.pagererState = Drupal.pagerer.evalState(this);
        $.extend(innerPager.pagererState, {
          outerPager: $(innerPager).parents('.pagerer-pager-scrollpane').get(0),
          innerPager: innerPager,
          pagerList: pagerList,
          pageWidth: 0,
          separatorWidth: 0,
          leftOverflow: 0,
          rightOverflow: 0,
          scrolling: false,
          scrollingDuration: 0,
          fastScrolling: 0
        });

        // Determine pager separator width, if existing.
        if (pagerSeparators.length > 0) {
          innerPager.pagererState.separatorWidth = Math.ceil($(pagerSeparators[0]).outerWidth(true));
        }

        // Determine pager element width from maximum width possible.
        if (innerPager.pagererState.total > 1) {
          var pageDupe = $(pagerPages[0]).clone();
          pageDupe.removeClass('is-active');
          pageDupe.addClass('pagerer-dupe');
          $(pageDupe).find('a').text(Drupal.pagerer.indexToTag(innerPager.pagererState.total - 1, innerPager.pagererState, 'page'));
          $(pagerList).append(pageDupe);
          innerPager.pagererState.pageWidth = Math.ceil($(pageDupe).outerWidth(true));
          cellHeight = Math.ceil($(pageDupe).outerHeight(true));
          pagerWidth = getPagerWidth(innerPager.pagererState, pagerPages.length, pagerSeparators.length);
          innerPagerWidth = Math.min((innerPager.pagererState.quantity * innerPager.pagererState.pageWidth) + ((innerPager.pagererState.quantity - 1) * innerPager.pagererState.separatorWidth), pagerWidth);
          $(pagerList).find('.pagerer-dupe').remove();
          $(pagerList).css({
            width: pagerWidth + 'px',
            height: cellHeight + 'px',
            left: '0px'
          });
        }
        else {
          // If no pages, then only 'No pages' message is in the pager.
          innerPager.pagererState.pageWidth = $(pagerPages[0]).outerWidth(true);
          cellHeight = $(pagerPages[0]).outerHeight(true);
          pagerWidth = innerPager.pagererState.pageWidth;
          innerPagerWidth = innerPager.pagererState.pageWidth;
          $(pagerPages[0]).css('left', '0px');
          $(pagerList).css({
            width: pagerWidth + 'px',
            height: cellHeight + 'px'
          });
        }

        // Set dimensions.
        $(innerPager).css({
          'padding-left': Math.ceil(innerPager.pagererState.pageWidth / 4) + 'px',
          'padding-right': Math.ceil(innerPager.pagererState.pageWidth / 4) + 'px',
          'width': innerPagerWidth + 'px',
          'height': cellHeight + 'px'
        });
        $(viewport).css({
          width: innerPagerWidth + 'px',
          height: (cellHeight * 2) + 'px'
        });
        // If no pages, return.
        if (innerPager.pagererState.total <= 1) {
          return;
        }

        // Scan pager elements to allocate 'left' property.
        var elementLeft = 0;
        var pagerCurrentPage = 0;
        pagerElements.each(function (index) {
          if (!$(this).hasClass('pagerer-separator')) {
            var pageWidth = $(this).outerWidth(true);
            var margin = (innerPager.pagererState.pageWidth - pageWidth) / 2;
            $(this).css({
              'left': elementLeft + 'px',
              'margin-left': (parseInt($(this).css('margin-left')) + margin) + 'px',
              'margin-right': (parseInt($(this).css('margin-right')) + margin) + 'px'
            });
            if ($(this).hasClass('is-active')) {
              pagerCurrentPage = index;
            }
            elementLeft += innerPager.pagererState.pageWidth;
          }
          else {
            $(this).css('left', elementLeft + 'px');
            elementLeft += innerPager.pagererState.separatorWidth;
          }
        });

        // Set current item to the middle of the innerPager.
        var pagerLeftPage = pagerCurrentPage - Math.floor(innerPager.pagererState.quantity / 2);
        if (pagerLeftPage < 0) {
          pagerLeftPage = 0;
        }
        else if (pagerLeftPage > pagerPages.length - innerPager.pagererState.quantity) {
          pagerLeftPage = pagerPages.length - innerPager.pagererState.quantity;
        }

        // Left- and right-most pages.
        var pagerLeftPageIndex = Drupal.pagerer.elementToIndex(pagerPages[0], innerPager.pagererState);
        var pagerRightPageIndex = Drupal.pagerer.elementToIndex(pagerPages[pagerPages.length - 1], innerPager.pagererState);

        // Add elements to the left.
        innerPager.pagererState.leftOverflow = addPagerElements(
          innerPager.pagererState,
          PAGERER_LEFT,
          pagerLeftPageIndex - 1,
          innerPager.pagererState.quantity,
          false
        );

        // Add elements to the right.
        innerPager.pagererState.rightOverflow = addPagerElements(
          innerPager.pagererState,
          PAGERER_RIGHT,
          pagerRightPageIndex + 1,
          innerPager.pagererState.quantity,
          false
        );

      });

      /**
        * Pagerer scrollpane buttons event binding.
        *
        * The navigation buttons shift the entire pager embedded in the
        * scrollpane inner pager.
        * Once mouse is pressed on a prev/next button, the pager is moved
        * right/left by one position. If mouse is kept pressed, the pager will
        * shift continuosly. When mouse is released or moved away from the icon,
        * shifting will stop.
        * If mouse is pressed on a first/last button, the pager is moved to
        * first/last page.
        */
      $('.pagerer-scrollpane-button', context).once('pagerer').each(function (index) {
        this.pagererState = {
          outerPager: $(this).parents('.pagerer-pager-scrollpane').get(0),
          innerPager: $(this).parents('.pagerer-pager-scrollpane').find('.pagerer-scrollpane-embedded').get(0),
          pager: $(this).parents('.pagerer-pager-scrollpane').find('.pagerer-scrollpane-embedded').find('.pager__items').get(0)
        };
        $(this).button();
        // Set button enable/disabled state.
        setButtonState(this);
        $(this)
          .on('mousedown', function (event) {
            var button = this;
            var innerPager = button.pagererState.innerPager;
            var scope;

            // Return immediately if button is disabled.
            if ($(button).button('option', 'disabled')) {
              return false;
            }

            // Determine scope of scroll request.
            if ($(button).hasClass('pagerer-next')) {
              scope = 'next';
            }
            else if ($(button).hasClass('pagerer-previous')) {
              scope = 'previous';
            }
            else if ($(button).hasClass('pagerer-first')) {
              scope = 'first';
            }
            else if ($(button).hasClass('pagerer-last')) {
              scope = 'last';
            }

            // If scrollpane is currently transitioning, and a request for a
            // different scope is received, reset all transitions.
            if (innerPager.pagererState.scrolling && innerPager.pagererState.scrolling !== scope) {
              Drupal.pagerer.reset();
            }

            // Transition duration based on single click.
            switch (innerPager.pagererState.scrollingDuration) {
              case 0:
                innerPager.pagererState.scrollingDuration = 500;
                break;

              case 500:
              case 200:
                innerPager.pagererState.scrollingDuration = 200;
                break;

            }
            scrollRequestEnqueue(innerPager.pagererState, scope, innerPager.pagererState.scrollingDuration);

            // If button is kept pressed long enough, start fastScrolling mode.
            if ((scope === 'previous' || scope === 'next') && !$(button).button('option', 'disabled')) {
              Drupal.pagerer.state.timeoutAction = setTimeout(function () {
                innerPager.pagererState.fastScrolling = 1;
                scrollRequestEnqueue(innerPager.pagererState, scope, getScrollDuration(innerPager.pagererState));
              }, innerPager.pagererState.scrollingDuration + 20);
            }
          })
          .on('mouseup mouseleave', function (event) {
            // Stop fastScrolling mode if active.
            clearTimeout(Drupal.pagerer.state.timeoutAction);
            this.pagererState.innerPager.pagererState.fastScrolling = 0;
          });
      });

      /**
       * Helper functions.
       */

      /**
       * Enqueues a scrollpane scroll request.
       *
       * Scrolls embedded pager to first/previous/next/last 'scope' in a
       * 'duration' timelapse.
       *
       * @param {object} pState
       *   A Pagerer state object.
       * @param {string} scope
       *   The scope of the scroll, first/previous/next/last.
       * @param {number} duration
       *   The duration of the scroll.
       */
      function scrollRequestEnqueue(pState, scope, duration) {
        $(pState.innerPager).queue('pagererQueue', function () {
          pState.scrolling = scope;

          // In fastScrolling mode, enqueue next iteration straight ahead.
          if (pState.fastScrolling) {
            pState.fastScrolling++;
            scrollRequestEnqueue(pState, scope, getScrollDuration(pState));
          }

          var pagerPages = $(pState.pagerList).find('li:not(.pagerer-separator)');
          var first = Drupal.pagerer.elementToIndex(pagerPages[0], pState);
          var last = Drupal.pagerer.elementToIndex(pagerPages[pagerPages.length - 1], pState);
          var addedElements;
          var fromEl;

          switch (scope) {
            // ***** Next - shift left.
            case 'next':
              // Add a pager element on the right.
              addedElements = addPagerElements(pState, PAGERER_RIGHT, last + 1, 1);
              if (pState.leftOverflow < pState.quantity) {
                // There's space on the left side to shift pager.
                if (pState.rightOverflow > 0) {
                  // Pager overflows to the right, so shift pager to the left.
                  shiftPagerList(pState, PAGERER_LEFT, 1, duration);
                  return;
                }
              }
              else {
                // No space on the left side to shift pager.
                if (addedElements || pState.rightOverflow > 0) {
                  // Remove first element on the left, then shift pager one
                  // position to the left.
                  removePagerElements(pState, PAGERER_LEFT, 1);
                  shiftPagerList(pState, PAGERER_LEFT, 1, duration);
                  return;
                }
              }
              break;

            // ***** Previous - shift right.
            case 'previous':
              // Add a pager element on the left.
              addedElements = addPagerElements(pState, PAGERER_LEFT, first - 1, 1);
              if (pState.rightOverflow < pState.quantity) {
                // There's space on the right side to shift pager.
                if (pState.leftOverflow > 0) {
                  // Pager overflows to the left, so shift pager to the right.
                  shiftPagerList(pState, PAGERER_RIGHT, 1, duration);
                  return;
                }
              }
              else {
                // No space on the right side to shift pager.
                if (addedElements || pState.leftOverflow > 0) {
                  // Remove first element on the right, then shift pager one
                  // position to the right.
                  removePagerElements(pState, PAGERER_RIGHT, 1);
                  shiftPagerList(pState, PAGERER_RIGHT, 1, duration);
                  return;
                }
              }
              break;

            // ***** First.
            case 'first':
              fromEl = Math.min((pState.quantity * 2) - 1, first - 1);
              addedElements = addPagerElements(pState, PAGERER_LEFT, fromEl, pState.quantity * 2);
              shiftPagerList(
                pState,
                PAGERER_RIGHT,
                pState.leftOverflow,
                duration,
                function () {
                  if (pState.rightOverflow > pState.quantity) {
                    removePagerElements(pState, PAGERER_RIGHT, pState.rightOverflow - pState.quantity);
                  }
                }
              );
              return;

            // ***** Last.
            case 'last':
              fromEl = Math.max((pState.total - (pState.quantity * 2)), last + 1);
              addedElements = addPagerElements(pState, PAGERER_RIGHT, fromEl, pState.quantity * 2);
              shiftPagerList(
                pState,
                PAGERER_LEFT,
                pState.rightOverflow,
                duration,
                function () {
                  if (pState.leftOverflow > pState.quantity) {
                    removePagerElements(pState, PAGERER_LEFT, pState.leftOverflow - pState.quantity);
                  }
                }
              );
              return;

          }

          // Dequeue next iteration in the queue.
          scrollRequestDequeue(pState);

        });

        // Starts the queue processing.
        if (pState.scrolling === false) {
          $(pState.innerPager).dequeue('pagererQueue');
        }
      }

      /**
       * Dequeues a scrollpane scroll request.
       *
       * If no more requests in the queue, clear state variables.
       *
       * @param {object} pState
       *   A Pagerer state object.
       */
      function scrollRequestDequeue(pState) {
        if ($(pState.innerPager).queue('pagererQueue').length > 0) {
          $(pState.innerPager).dequeue('pagererQueue');
        }
        else {
          pState.scrolling = false;
          pState.scrollingDuration = 0;
          pState.fastScrolling = 0;
        }
      }

      /**
       * Gets duration of next scroll transition.
       *
       * @param {object} pState
       *   A Pagerer state object.
       *
       * @return {number}
       *   The duration (msec) of next scroll transition.
       */
      function getScrollDuration(pState) {
        var ret = ((pState.fastScrolling - 1) * -19.8) + 200;
        return (ret > 2) ? ret : 2;
      }

      /**
       * Enables/disables scrollpane buttons.
       *
       * @param {HTMLElement} element
       *   An HTML element for a scrollpane button.
       */
      function setButtonState(element) {
        if ($(element).hasClass('pagerer-first') || $(element).hasClass('pagerer-previous')) {
          if (element.pagererState.innerPager.pagererState.leftOverflow === 0) {
            $(element).mouseup().mouseleave();
            $(element).button('disable');
          }
          else {
            $(element).button('enable');
          }
        }
        if ($(element).hasClass('pagerer-next') || $(element).hasClass('pagerer-last')) {
          if (element.pagererState.innerPager.pagererState.rightOverflow === 0) {
            $(element).mouseup().mouseleave();
            $(element).button('disable');
          }
          else {
            $(element).button('enable');
          }
        }
      }

      /**
       * Add pages to the embedded pager.
       *
       * Add 'count' pages and separators on left/right 'side', starting with
       * page at index 'start'.
       *
       * @param {object} pState
       *   A Pagerer state object.
       * @param {number} side
       *   The side of the pager to add elements to,
       *   PAGERER_LEFT|PAGERER_RIGHT.
       * @param {number} start
       *   The 0-indexed page number to start adding from.
       * @param {number} count
       *   The number of elements to remove.
       * @param {boolean} onReady
       *   TRUE if function is invoked at .ready().
       *
       * @return {number}
       *   The number of pages added to the pager.
       */
      function addPagerElements(pState, side, start, count, onReady) {

        // onReady will be true if function is invoked at .ready()
        onReady = onReady || false;

        var pagerPages;
        var pageWidth;
        var pageDupe;
        var margin;
        var pagerSeparators;
        var separatorWidth = 0;
        var separatorDupe;
        var last;
        var currentLeft;
        var i;

        for (i = 0; i < count; i++) {
          pagerPages = $(pState.pagerList).find('li:not(.pagerer-separator)');
          pagerSeparators = $(pState.pagerList).find('li.pagerer-separator');

          // If we have separators, prepare dupe and set width.
          if (pagerSeparators.length) {
            separatorDupe = $(pagerSeparators[0]).clone();
            separatorWidth = pState.separatorWidth;
          }
          else {
            separatorWidth = 0;
          }

          // Add separator and page.
          if (side === PAGERER_RIGHT) {
            last = pagerPages.length - 1;
            // Skip at end of run.
            if (Drupal.pagerer.elementToIndex(pagerPages[last], pState) >= (pState.total - 1)) {
              break;
            }
            // Current left.
            currentLeft = parseInt($(pagerPages[last]).css('left'));
            // Separator.
            if (separatorWidth) {
              $(separatorDupe).css('left', (currentLeft + pState.pageWidth) + 'px');
              $(pState.pagerList).append(separatorDupe);
            }
            // Page.
            pageDupe = $(pagerPages[last]).clone();
            setPagerElementHtml(pageDupe, pState, start + i, onReady);
            $(pageDupe)
              .css('left', (currentLeft + pState.pageWidth + separatorWidth) + 'px');
            $(pState.pagerList).append(pageDupe);
            pageWidth = $(pageDupe).outerWidth(true);
            margin = (pState.pageWidth - pageWidth) / 2;
            $(pageDupe).css('margin-left', (parseInt($(pageDupe).css('margin-left')) + margin) + 'px');
            $(pageDupe).css('margin-right', (parseInt($(pageDupe).css('margin-right')) + margin) + 'px');
            pState.rightOverflow++;
          }
          else if (side === PAGERER_LEFT) {
            // Skip at end of run.
            if (Drupal.pagerer.elementToIndex(pagerPages[0], pState) === 0) {
              break;
            }
            // Current left.
            currentLeft = parseInt($(pagerPages[0]).css('left'));
            // Separator.
            if (separatorWidth) {
              $(separatorDupe).css('left', (currentLeft - separatorWidth) + 'px');
              $(pState.pagerList).prepend(separatorDupe);
            }
            // Page.
            pageDupe = $(pagerPages[0]).clone();
            setPagerElementHtml(pageDupe, pState, start - i, onReady);
            $(pageDupe)
              .css('left', (currentLeft - pState.pageWidth - separatorWidth) + 'px');
            $(pState.pagerList).prepend(pageDupe);
            pageWidth = $(pageDupe).outerWidth(true);
            margin = (pState.pageWidth - pageWidth) / 2;
            $(pageDupe).css('margin-left', (parseInt($(pageDupe).css('margin-left')) + margin) + 'px');
            $(pageDupe).css('margin-right', (parseInt($(pageDupe).css('margin-right')) + margin) + 'px');
            pState.leftOverflow++;
          }
        }
        // Resize pager.
        pagerPages = $(pState.pagerList).find('li:not(.pagerer-separator)');
        pagerSeparators = $(pState.pagerList).find('li.pagerer-separator');
        $(pState.pagerList).css('width', getPagerWidth(pState, pagerPages.length, pagerSeparators.length) + 'px');
        // If elements were added on the left side, pager and elements will be
        // misplaced, so reposition the elements.
        if (side === PAGERER_LEFT) {
          $(pState.pagerList).css({
            left: (parseInt($(pState.pagerList).css('left')) - (pState.pageWidth * i) - (pState.separatorWidth * i)) + 'px'
          });
          shiftPagerElements(pState, PAGERER_RIGHT, i);
        }
        return i;
      }

      /**
       * Removes pages from the embedded pager.
       *
       * Remove 'count' pages and separators on left/right 'side'.
       *
       * @param {object} pState
       *   A Pagerer state object.
       * @param {number} side
       *   The side of the pager from where to remove elements,
       *   PAGERER_LEFT|PAGERER_RIGHT.
       * @param {number} count
       *   The number of elements to remove.
       *
       * @return {boolean}
       *   TRUE if successful.
       */
      function removePagerElements(pState, side, count) {
        var pagerPages;
        var pagerSeparators;
        var i;

        for (i = 0; i < count; i++) {
          pagerPages = $(pState.pagerList).find('li:not(.pagerer-separator)');
          pagerSeparators = $(pState.pagerList).find('li.pagerer-separator');
          if (side === PAGERER_RIGHT) {
            $(pagerPages[pagerPages.length - 1]).remove();
            if (pagerSeparators.length) {
              $(pagerSeparators[pagerSeparators.length - 1]).remove();
            }
            pState.rightOverflow--;
          }
          else if (side === PAGERER_LEFT) {
            $(pagerPages[0]).remove();
            if (pagerSeparators.length) {
              $(pagerSeparators[0]).remove();
            }
            pState.leftOverflow--;
          }
        }
        // Resize pager.
        $(pState.pagerList).css('width', getPagerWidth(pState, pagerPages.length - 1, pagerSeparators.length - 1) + 'px');
        // If elements were removed on the left side, the remaining ones will
        // be misplaced wihin the pager, so reposition them.
        if (side === PAGERER_LEFT) {
          $(pState.pagerList).css({
            left: (parseInt($(pState.pagerList).css('left')) + ((pState.pageWidth + pState.separatorWidth) * count)) + 'px'
          });
          shiftPagerElements(pState, PAGERER_LEFT, i);
        }
        return true;
      }

      /**
       * Shifts the embedded pager elements.
       *
       * Shift the elements of the embedded pager by 'count' pages in
       * left/right 'direction'.
       *
       * @param {object} pState
       *   A Pagerer state object.
       * @param {number} direction
       *   The direction of the shift, PAGERER_LEFT|PAGERER_RIGHT.
       * @param {number} count
       *   The number of pages to shift.
       */
      function shiftPagerElements(pState, direction, count) {
        $(pState.pagerList).find('li').each(function (index) {
          $(this).css({
            left: (parseInt($(this).css('left')) + (direction * (pState.pageWidth + pState.separatorWidth) * count)) + 'px'
          });
        });
      }

      /**
       * Shifts the embedded pager in the inner pager.
       *
       * Shift the entire pager by 'count' pages in left/right 'direction'.
       * If 'duration' is set (msec), the shift will be jQuery animated.
       * A 'complete' callback is executed at completion if set.
       * Overall pager shift is executed in a jQuery queue, so next action is
       * dequeued at the end of the call (for execution after the animation
       * is completed).
       *
       * @param {object} pState
       *   A Pagerer state object.
       * @param {number} direction
       *   The direction of the shift, PAGERER_LEFT|PAGERER_RIGHT.
       * @param {number} count
       *   The number of pages to shift.
       * @param {number} [duration]
       *   If set (msec), the shift will be jQuery animated.
       * @param {callback} [complete]
       *   Callback executed at completion if set.
       */
      function shiftPagerList(pState, direction, count, duration, complete) {
        var left = parseInt($(pState.pagerList).css('left'));
        var offset = direction * count * (pState.pageWidth + pState.separatorWidth);
        $(pState.pagerList).animate(
          {
            left: (left + offset) + 'px'
          },
          {
            duration: duration,
            queue: false,
            complete: function () {
              pState.leftOverflow -= direction * count;
              pState.rightOverflow += direction * count;
              if (typeof complete !== 'undefined') {
                complete();
              }
              if (pState.leftOverflow <= 1 || pState.rightOverflow <= 1) {
                $(pState.outerPager).find('.ui-button').each(function () {
                  setButtonState(this);
                });
              }
              scrollRequestDequeue(pState);
            }
          }
        );
      }

      /**
       * Sets HTML of a page element in the pager.
       *
       * @param {HTMLElement} element
       *   An HTML element.
       * @param {object} pState
       *   A Pagerer state object.
       * @param {number} targetPage
       *   The page to relocate to.
       * @param {boolean} onReady
       *   TRUE if function is invoked at .ready().
       */
      function setPagerElementHtml(element, pState, targetPage, onReady) {

        // onReady will be true if function is invoked at .ready()
        onReady = onReady || false;

        // Add 'is-active' class to current page.
        if (targetPage === pState.current) {
          $(element[0]).addClass('is-active');
        }
        else {
          $(element[0]).removeClass('is-active');
        }

        // Page link.
        var anchor = $(element).find('a');
        if (!anchor.length) {
          $(element).text('');
          $(element).append('<a></a>');
          anchor = $(element).find('a');
        }
        // Format hyperlink.
        var targetUrl = Drupal.pagerer.getTargetUrl(pState, targetPage, true);
        anchor[0].href = targetUrl;
        $(anchor[0]).text(Drupal.pagerer.indexToTag(targetPage, pState, 'page'));
        anchor[0].title = Drupal.pagerer.indexToTag(targetPage, pState, 'title');
        // In views, add AJAX where appropriate.
        if (!onReady && pState.context === 'viewsAjax') {
          // Element is in AJAX enabled view.
          Drupal.pagerer.setAjaxViewPaging(anchor[0], 'click', pState.ajaxViewId, targetUrl);
        }
        else if (pState.context === 'viewsPreview') {
          // Element is in Views preview context.
          Drupal.pagerer.setAjaxViewPreviewPaging(anchor[0], 'click', targetUrl);
        }
      }

      /**
       * Calculates embedded pager width.
       *
       * @param {object} pState
       *   A Pagerer state object.
       * @param {number} pages
       *   The number of page elements in the pager.
       * @param {number} separators
       *   The number of separator elements in the pager.
       *
       * @return {number}
       *   The total width of the pager.
       */
      function getPagerWidth(pState, pages, separators) {
        return ((pages * pState.pageWidth) + (separators * pState.separatorWidth));
      }

    },

    detach: function (context, settings) {
      $('.pagerer-scrollpane-embedded', context).each(function (index) {
        Drupal.pagerer.detachState(this);
      });
    }

  };
})(jQuery);
