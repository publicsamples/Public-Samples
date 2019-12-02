!function(t,n,o){"use strict";Drupal.behaviors.atdoubleTap={attach:function(){t.fn.doubleTapToGo=function(i){return!!("ontouchstart"in n||navigator.msMaxTouchPoints||navigator.userAgent.toLowerCase().match(/windows phone os 7/i))&&("unbind"===i?this.each(function(){t(this).off(),t(o).off("click touchstart MSPointerDown",handleTouch)}):this.each(function(){var n=!1;t(this).on("click",function(o){var i=t(this);i[0]!=n[0]&&(o.preventDefault(),n=i)}),t(o).on("click touchstart MSPointerDown",function(o){for(var i=!0,a=t(o.target).parents(),c=0;c<a.length;c++)a[c]==n[0]&&(i=!1);i&&(n=!1)})}),this)}}}}(jQuery,window,document);

!function(e,a,r){"use strict";Drupal.behaviors.atrM={attach:function(o,n){function t(a){a.preventDefault(),a.stopPropagation(),e(this).toggleClass("is-open--parent"),"true"==e(this).attr("aria-expanded")?e(this).attr("aria-expanded","false"):"false"==e(this).attr("aria-expanded")&&e(this).attr("aria-expanded","true"),e(this).parent().next(".is-child").toggleClass("is-open--child")}function i(a){var r=e("#rm-accordion-trigger").html();e(a).each(function(){0==e(this).next(".rm-accordion-trigger").length&&e(this).after(r);var a=e(this).parent().parent().attr("id");e(this).next().attr("aria-controls",a+"__child-menu"),e(this).parent().next(".is-child").attr("id",a+"__child-menu")})}if(e(".rm-block").removeClass("js-hide"),r.matchMedia("only screen").matches){var s=n[n.ajaxPageState.theme].at_responsivemenus,c=s.default,l=s.responsive,d=".rm-block .rm-toggle__link",m=s.acd.acd_default,u=s.acd.acd_responsive,p=s.acd.acd_both,_=s.acd.acd_load;e(d,o).on("click",function(r){r.preventDefault(),r.stopPropagation(),e(a.body).toggleClass("rm-is-open"),"true"==e(this).attr("aria-expanded")?(e(this).attr("aria-expanded","false"),e("#rm-toggle__icon--use").attr("xlink:href","#rm-toggle__icon--open")):"false"==e(this).attr("aria-expanded")&&(e(this).attr("aria-expanded","true"),e("#rm-toggle__icon--use").attr("xlink:href","#rm-toggle__icon--close")),e(a).one("click",function(r){0===e(".rm-block").has(r.target).length&&(e(a.body).removeClass("rm-is-open"),e(d).attr("aria-expanded","false"),e("#rm-toggle__icon--use").attr("xlink:href","#rm-toggle__icon--open"))})}),enquire.register(s.bp,{setup:function(){e(a.body).addClass(c),e(".rm-block").parent(".l-r").addClass("rm-region").parent(".l-rw").addClass("rm-row"),"ms-dropmenu"==c&&e(".rm-block__content li:has(ul)").doubleTapToGo(),1==m&&1==_&&(e(".rm-block .menu-level-1").addClass("ms-accordion"),e.ready(i(".ms-accordion .is-parent__wrapper .menu__link")),e(".ms-accordion .rm-accordion-trigger",o).on("click",t))},match:function(){"ms-none"!==l&&l!==c&&(e(a.body).removeClass(c).addClass(l),1==_&&(1==u?0==p&&(e(".rm-block .menu-level-1").addClass("ms-accordion"),e.ready(i(".ms-accordion .is-parent__wrapper .menu__link")),e(".ms-accordion .rm-accordion-trigger",o).on("click",t)):(e(".ms-accordion .rm-accordion-trigger").remove(),e(".rm-block .menu-level-1").removeClass("ms-accordion"),e(".rm-block .menu").removeClass("is-open--child"))),"ms-dropmenu"==l?e(".rm-block__content li:has(ul)").doubleTapToGo():e(".rm-block__content li:has(ul)").doubleTapToGo("unbind"))},unmatch:function(){e(a.body).addClass(c),1==_&&(1==m?0==p&&(e(".rm-block .menu-level-1").addClass("ms-accordion"),e.ready(i(".ms-accordion .is-parent__wrapper .menu__link")),e(".ms-accordion .rm-accordion-trigger",o).on("click",t)):(e(".ms-accordion .rm-accordion-trigger").remove(),e(".rm-block .menu-level-1").removeClass("ms-accordion"),e(".rm-block .menu").removeClass("is-open--child"))),"ms-dropmenu"==c?e(".rm-block__content li:has(ul)").doubleTapToGo():e(".rm-block__content li:has(ul)").doubleTapToGo("unbind"),l!==c&&e(a.body).removeClass(l)}})}}}}(jQuery,document,window);

/**
 * @file
 * Pagerer jquery scripts.
 *
 * All jQuery navigation widgets implemented by pagerer are configured at
 * runtime by a JSON object prepared by the PHP part of the module, and
 * stored in a 'pagererState' object attached to each widget.
 *
 * pagererState properties:
 * - url: the URL of the target page for navigation
 * - queryString: the query string fragment of the request
 * - element: integer to distinguish between multiple pagers on one page
 * - quantity: number of page elements in the pager list
 * - total: total number of pages in the query
 * - totalItems: total number of items in the query
 * - current: 0-base index of current page
 * - interval: number of elements per page (1 if display = pages, items per
 *   page if display = items/item_ranges)
 * - display: pages|items|item_ranges indicates what is displayed in the page
 *   element
 * - pageSeparator: Text to fill between contiguous pages.
 * - pageTag: Array of text elements to use to render page/item/item range.
 * - widgetResize: (widget) determines if the widget width should be calculated
 *   dynamically based on the width of the string of the last page/item number.
 * - sliderWidth: (slider) determines the width of the slider bar in 'em's.
 *   If not set, CSS styling will prevail.
 * - action: (slider) determines how the page relocation should be triggered
 *   after it has been selected through the jQuery slider.
 * - timeout: (slider) the grace time (in milliseconds) to wait before the
 *   page is relocated, in case "timeout" action method is selected for
 *   the jQuery slider.
 * - icons: (slider) determines whether to display +/- navigation icons
 *   on the sides of the jQuery slider.
 * - tickmarkTitle: (slider) help text appended to the slider help when user is
 *   expected to click on the tickmark to start page relocation.
 */

(function ($) {

  'use strict';

  /**
   * Pagerer base utility functions.
   */
  Drupal.pagerer = Drupal.pagerer || {

    /**
     * State variables.
     *
     * These variables are reset at every page load, either normal or AJAX.
     */
    state: {
      timeoutAction: 0,
      intervalAction: 0,
      intervalCount: 0,
      isRelocating: false
    },

    /**
     * Return page text from zero-based page index number.
     *
     * @param {number} index
     *   The page number (0-indexed) to navigate to.
     * @param {object} pState
     *   A Pagerer state object.
     * @param {string} tagType
     *   The text to be formatted, page or title.
     *
     * @return {number}
     *   A formatted and translated text string to be used for rendering a
     *   pager element.
     */
    indexToTag: function (index, pState, tagType) {

      index = parseInt(index);
      var offset = index - pState.current;

      // Get required tag.
      var tag;
      if (tagType === 'page') {
        if (offset === 0) {
          tag = pState.pageTag.page_current;
        }
        else {
          if (offset < 0) {
            tag = pState.pageTag.page_previous;
          }
          else {
            tag = pState.pageTag.page_next;
          }
        }
      }
      else {
        if (index === 0) {
          tag = pState.pageTag.first_title;
        }
        else if (index === (pState.total - 1)) {
          tag = pState.pageTag.last_title;
        }
        else {
          tag = pState.pageTag.page_title;
        }
      }

      // Items.
      var l_item = (index * pState.interval) + 1;
      var h_item = Math.min(((index + 1) * pState.interval), pState.totalItems);
      var item_offset = offset * pState.interval;

      // Pages.
      var num = index + 1;
      var t_offset = offset;

      // Format text string.
      return Drupal.formatString(tag, {
        '@number': num,
        '@offset': t_offset,
        '@item_offset': item_offset,
        '@item_low': l_item,
        '@item_high': h_item,
        '@item': l_item,
        '@total_items': pState.totalItems,
        '@total': pState.total
      });
    },

    /**
     * Return zero-based page index number from textual value.
     *
     * @param {number} value
     *   A number, either a page or an item (1-indexed).
     * @param {HTMLElement} element
     *   An HTML element.
     *
     * @return {number}
     *   The page number (0-indexed) the link will navigate to.
     */
    valueToIndex: function (value, element) {
      var elementState = element.pagererState;
      switch (elementState.display) {
        case 'pages':
          if (isNaN(value)) {
            return 0;
          }
          value = parseInt(value);
          if (value < 1) {
            return 0;
          }
          if (value > elementState.total) {
            value = elementState.total;
          }
          return value - 1;

        case 'items':
          if (isNaN(value)) {
            return 0;
          }
          value = parseInt(value);
          if (value < 1) {
            return 0;
          }
          if (value > elementState.totalItems) {
            value = elementState.totalItems;
          }
          return parseInt((value - 1) / elementState.interval);

      }
    },

    /**
     * Return an element's pagererState from the HTML attribute.
     *
     * @param {HTMLElement} element
     *   An HTML element.
     *
     * @return {object}
     *   A Pagerer state object.
     */
    evalState: function (element) {
      var pagererStateId = $(element).attr('id');
      var pagererState = drupalSettings.pagerer.state[pagererStateId];
      var ajaxViewId = this.getAjaxViewId(element);

      if (ajaxViewId) {
        // Element is in Views AJAX context.
        pagererState.context = 'viewsAjax';
        pagererState.ajaxViewId = ajaxViewId;

      }
      else if (this.getViewsPreviewContext(element)) {
        // Element is in Views preview context.
        pagererState.context = 'viewsPreview';

      }
      else {
        // Normal page.
        pagererState.context = 'document';

      }

      return pagererState;
    },

    /**
     * Clears an element's pagererState from drupalSettings.
     *
     * @param {HTMLElement} element
     *   An HTML element.
     */
    detachState: function (element) {
      var pagererStateId = $(element).attr('id');
      delete drupalSettings.pagerer.state[pagererStateId];
      return;
    },

    /**
     * Return zero-based page index number from element href.
     *
     * @param {HTMLElement} element
     *   An HTML anchor element.
     * @param {object} pState
     *   A Pagerer state object.
     *
     * @return {number}
     *   The page number (0-indexed) the link will navigate to.
     */
    elementToIndex: function (element, pState) {
      var url = $(element).find('a').attr('href');
      if (typeof url == 'undefined') {
        if ($(element).hasClass('pager-current')) {
          return pState.current;
        }
        return 0;
      }
      var queryParameters = this.parseQueryString(url);
      if (typeof queryParameters['page'] == 'undefined') {
        return 0;
      }
      var elementPages = queryParameters['page'].split(',');
      if (typeof elementPages[pState.element] == 'undefined') {
        return 0;
      }
      return parseInt(elementPages[pState.element]);
    },

    /**
     * Helper function to parse a querystring.
     *
     * Copy of Drupal.Views.parseQueryString since the Views module may not be
     * enabled.
     *
     * @param {string} query
     *   The querystring to parse.
     *
     * @return {object}
     *   A map of query parameters.
     */
    parseQueryString: function (query) {
      var args = {};
      var pos = query.indexOf('?');
      if (pos !== -1) {
        query = query.substring(pos + 1);
      }
      var pair;
      var pairs = query.split('&');
      for (var i = 0; i < pairs.length; i++) {
        pair = pairs[i].split('=');
        // Ignore the 'q' path argument, if present.
        if (pair[0] !== 'q' && pair[1]) {
          args[decodeURIComponent(pair[0].replace(/\+/g, ' '))] = decodeURIComponent(pair[1].replace(/\+/g, ' '));
        }
      }
      return args;
    },

    /**
     * Reset pending transitions.
     *
     * Cancel timeout-bound page relocation and any unprocessed scrollpane
     * transition.
     */
    reset: function () {
      if (this.state.timeoutAction) {
        clearTimeout(this.state.timeoutAction);
      }
    },

    /**
     * Gets the URL to be used for links/AJAX relocation.
     *
     * @param {object} pState
     *   The Pagerer state associated with an element.
     * @param {number} targetPage
     *   The page to relocate to.
     * @param {boolean} queryOnNullUrl
     *   If TRUE, indicates to return the Pagerer state URL also if it is NULL.
     *
     * @return {string}
     *   The URL to be used for relocation.
     */
    getTargetUrl: function (pState, targetPage, queryOnNullUrl) {
      var targetUrl;
      if (pState.url || (!pState.url && queryOnNullUrl)) {
        targetUrl = pState.url;
      }
      else {
        targetUrl = Drupal.url(location.pathname.slice(drupalSettings.path.baseUrl.length + drupalSettings.path.pathPrefix.length));
      }
      if (pState.queryString) {
        targetUrl = targetUrl + '?' + pState.queryString.replace(/pagererpage/, targetPage);
      }
      return targetUrl;
    },

    /**
     * Relocate client browser to target page.
     *
     * Relocation method is decided based on the context of the pager element:
     *  - a normal page - document.location is used.
     *  - a Views preview area in a Views settings form - AJAX is used.
     *  - a AJAX enabled Views context - AJAX is used.
     *
     * @param {HTMLElement} element
     *   The element triggering the relocation.
     * @param {number} targetPage
     *   The page to relocate to.
     *
     * @return {boolean}
     *   FALSE if already relocating.
     */
    relocate: function (element, targetPage) {
      // Check we are not relocating already.
      if (this.state.isRelocating) {
        return false;
      }
      this.state.isRelocating = true;

      var targetUrl = this.getTargetUrl(element.pagererState, targetPage, false);

      if (element.pagererState.context === 'document') {
        // Normal page.
        document.location = targetUrl;

      }
      else if (element.pagererState.context === 'viewsPreview') {
        // Element is in Views preview context.
        var previewRelocate = this.setAjaxViewPreviewPaging(null, null, targetUrl);
        previewRelocate.execute();

      }
      else if (element.pagererState.context === 'viewsAjax') {
        // Element is in Views AJAX context.
        var ajaxViewRelocate = this.setAjaxViewPaging(null, null, element.pagererState.ajaxViewId, targetUrl);
        ajaxViewRelocate.execute();

      }
    },

    /**
     * Views - Return parent Ajax view ID.
     *
     * @param {HTMLElement} element
     *   The element for which to find the Ajax view ID.
     *
     * @return {string|boolean}
     *   The Ajax view ID if found, FALSE otherwise.
     */
    getAjaxViewId: function (element) {
      if (drupalSettings && drupalSettings.views && drupalSettings.views.ajaxViews) {
        // Loop through active Views Ajax elements.
        var ajaxViews = drupalSettings.views.ajaxViews;
        for (var i in ajaxViews) {
          if (ajaxViews.hasOwnProperty(i)) {
            var view = '.js-view-dom-id-' + drupalSettings.views.ajaxViews[i].view_dom_id;
            var viewDiv = $(element).parents(view);
            if (viewDiv.length) {
              return i;
            }
          }
        }
      }
      return false;
    },

    /**
     * Views - Set Views AJAX paging.
     *
     * @param {HTMLElement} element
     *   The element to associate the AJAX behaviour with.
     * @param {event} event
     *   The event that will trigger the AJAX behaviour.
     * @param {string} ajaxViewId
     *   The AJAX id of the view.
     * @param {string} href
     *   The URL to invoke.
     *
     * @return {Drupal.Ajax}
     *   A Drupal.Ajax object.
     */
    setAjaxViewPaging: function (element, event, ajaxViewId, href) {
      var ajaxView = Drupal.views.instances[ajaxViewId];
      var viewData = {};
      $.extend(
        viewData,
        ajaxView.settings,
        Drupal.Views.parseQueryString(href),
        // Extract argument data from the URL.
        Drupal.Views.parseViewArgs(href, ajaxView.settings.view_base_path)
      );

      var settings = $.extend({}, ajaxView.element_settings, {
        submit: viewData,
        base: false,
      });
      // Load AJAX element_settings object and attach AJAX behaviour.
      if (element) {
        settings.element = element;
      }
      if (event) {
        settings.event = event;
      }
      ajaxView.pagerAjax = Drupal.ajax(settings);
      return ajaxView.pagerAjax;
    },

    /**
     * Views - Check if element is part of a view preview form.
     *
     * @param {HTMLElement} element
     *   The element to associate the AJAX behaviour with.
     *
     * @return {number}
     *   The number of '#views-live-preview' elements found. If above 0, then
     *   we are in a view preview form context.
     */
    getViewsPreviewContext: function (element) {
      return $(element).parents('#views-live-preview').length;
    },

    /**
     * Views - Set Views preview AJAX.
     *
     * @param {HTMLElement} element
     *   The element to associate the AJAX behaviour with.
     * @param {event} event
     *   The event that will trigger the AJAX behaviour.
     * @param {string} path
     *   The URL to invoke.
     *
     * @return {Drupal.Ajax}
     *   A Drupal.Ajax object.
     */
    setAjaxViewPreviewPaging: function (element, event, path) {
      var element_settings = {
        url: path,
        event: event,
        progress: {type: 'fullscreen'},
        method: 'replaceWith',
        wrapper: 'views-preview-wrapper'
      };
      if (element) {
        element_settings.base = $(element).attr('id');
        element_settings.element = element;
      }
      if (event) {
        element_settings.event = event;
      }
      return Drupal.ajax(element_settings);
    }
  };

})(jQuery);
;
/*! jQuery UI - v1.12.1 - 2017-03-31
* http://jqueryui.com
* Copyright jQuery Foundation and other contributors; Licensed  */
!function(a){"function"==typeof define&&define.amd?define(["jquery","./controlgroup","./checkboxradio","../keycode","../widget"],a):a(jQuery)}(function(a){return a.widget("ui.button",{version:"1.12.1",defaultElement:"<button>",options:{classes:{"ui-button":"ui-corner-all"},disabled:null,icon:null,iconPosition:"beginning",label:null,showLabel:!0},_getCreateOptions:function(){var a,b=this._super()||{};return this.isInput=this.element.is("input"),a=this.element[0].disabled,null!=a&&(b.disabled=a),this.originalLabel=this.isInput?this.element.val():this.element.html(),this.originalLabel&&(b.label=this.originalLabel),b},_create:function(){!this.option.showLabel&!this.options.icon&&(this.options.showLabel=!0),null==this.options.disabled&&(this.options.disabled=this.element[0].disabled||!1),this.hasTitle=!!this.element.attr("title"),this.options.label&&this.options.label!==this.originalLabel&&(this.isInput?this.element.val(this.options.label):this.element.html(this.options.label)),this._addClass("ui-button","ui-widget"),this._setOption("disabled",this.options.disabled),this._enhance(),this.element.is("a")&&this._on({keyup:function(b){b.keyCode===a.ui.keyCode.SPACE&&(b.preventDefault(),this.element[0].click?this.element[0].click():this.element.trigger("click"))}})},_enhance:function(){this.element.is("button")||this.element.attr("role","button"),this.options.icon&&(this._updateIcon("icon",this.options.icon),this._updateTooltip())},_updateTooltip:function(){this.title=this.element.attr("title"),this.options.showLabel||this.title||this.element.attr("title",this.options.label)},_updateIcon:function(b,c){var d="iconPosition"!==b,e=d?this.options.iconPosition:c,f="top"===e||"bottom"===e;this.icon?d&&this._removeClass(this.icon,null,this.options.icon):(this.icon=a("<span>"),this._addClass(this.icon,"ui-button-icon","ui-icon"),this.options.showLabel||this._addClass("ui-button-icon-only")),d&&this._addClass(this.icon,null,c),this._attachIcon(e),f?(this._addClass(this.icon,null,"ui-widget-icon-block"),this.iconSpace&&this.iconSpace.remove()):(this.iconSpace||(this.iconSpace=a("<span> </span>"),this._addClass(this.iconSpace,"ui-button-icon-space")),this._removeClass(this.icon,null,"ui-wiget-icon-block"),this._attachIconSpace(e))},_destroy:function(){this.element.removeAttr("role"),this.icon&&this.icon.remove(),this.iconSpace&&this.iconSpace.remove(),this.hasTitle||this.element.removeAttr("title")},_attachIconSpace:function(a){this.icon[/^(?:end|bottom)/.test(a)?"before":"after"](this.iconSpace)},_attachIcon:function(a){this.element[/^(?:end|bottom)/.test(a)?"append":"prepend"](this.icon)},_setOptions:function(a){var b=void 0===a.showLabel?this.options.showLabel:a.showLabel,c=void 0===a.icon?this.options.icon:a.icon;b||c||(a.showLabel=!0),this._super(a)},_setOption:function(a,b){"icon"===a&&(b?this._updateIcon(a,b):this.icon&&(this.icon.remove(),this.iconSpace&&this.iconSpace.remove())),"iconPosition"===a&&this._updateIcon(a,b),"showLabel"===a&&(this._toggleClass("ui-button-icon-only",null,!b),this._updateTooltip()),"label"===a&&(this.isInput?this.element.val(b):(this.element.html(b),this.icon&&(this._attachIcon(this.options.iconPosition),this._attachIconSpace(this.options.iconPosition)))),this._super(a,b),"disabled"===a&&(this._toggleClass(null,"ui-state-disabled",b),this.element[0].disabled=b,b&&this.element.blur())},refresh:function(){var a=this.element.is("input, button")?this.element[0].disabled:this.element.hasClass("ui-button-disabled");a!==this.options.disabled&&this._setOptions({disabled:a}),this._updateTooltip()}}),a.uiBackCompat!==!1&&(a.widget("ui.button",a.ui.button,{options:{text:!0,icons:{primary:null,secondary:null}},_create:function(){this.options.showLabel&&!this.options.text&&(this.options.showLabel=this.options.text),!this.options.showLabel&&this.options.text&&(this.options.text=this.options.showLabel),this.options.icon||!this.options.icons.primary&&!this.options.icons.secondary?this.options.icon&&(this.options.icons.primary=this.options.icon):this.options.icons.primary?this.options.icon=this.options.icons.primary:(this.options.icon=this.options.icons.secondary,this.options.iconPosition="end"),this._super()},_setOption:function(a,b){return"text"===a?void this._super("showLabel",b):("showLabel"===a&&(this.options.text=b),"icon"===a&&(this.options.icons.primary=b),"icons"===a&&(b.primary?(this._super("icon",b.primary),this._super("iconPosition","beginning")):b.secondary&&(this._super("icon",b.secondary),this._super("iconPosition","end"))),void this._superApply(arguments))}}),a.fn.button=function(b){return function(){return!this.length||this.length&&"INPUT"!==this[0].tagName||this.length&&"INPUT"===this[0].tagName&&"checkbox"!==this.attr("type")&&"radio"!==this.attr("type")?b.apply(this,arguments):(a.ui.checkboxradio||a.error("Checkboxradio widget missing"),0===arguments.length?this.checkboxradio({icon:!1}):this.checkboxradio.apply(this,arguments))}}(a.fn.button),a.fn.buttonset=function(){return a.ui.controlgroup||a.error("Controlgroup widget missing"),"option"===arguments[0]&&"items"===arguments[1]&&arguments[2]?this.controlgroup.apply(this,[arguments[0],"items.button",arguments[2]]):"option"===arguments[0]&&"items"===arguments[1]?this.controlgroup.apply(this,[arguments[0],"items.button"]):("object"==typeof arguments[0]&&arguments[0].items&&(arguments[0].items={button:arguments[0].items}),this.controlgroup.apply(this,arguments))}),a.ui.button});;
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
;
!function(t,a,e){"use strict";Drupal.behaviors.atBP={attach:function(t,n){if(a.matchMedia("only screen").matches){var i=n[n.ajaxPageState.theme].at_breakpoints;for(var o in i)i.hasOwnProperty(o)&&function(t,a){enquire.register(a,{match:function(){e.body.classList.add("bp--"+t)},unmatch:function(){e.body.classList.remove("bp--"+t)}})}(o.split("_").join("-"),i[o].mediaquery)}}}}(jQuery,window,document);
