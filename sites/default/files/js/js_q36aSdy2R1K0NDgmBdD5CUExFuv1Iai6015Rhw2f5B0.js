!function(t,n,o){"use strict";Drupal.behaviors.atdoubleTap={attach:function(){t.fn.doubleTapToGo=function(i){return!!("ontouchstart"in n||navigator.msMaxTouchPoints||navigator.userAgent.toLowerCase().match(/windows phone os 7/i))&&("unbind"===i?this.each(function(){t(this).off(),t(o).off("click touchstart MSPointerDown",handleTouch)}):this.each(function(){var n=!1;t(this).on("click",function(o){var i=t(this);i[0]!=n[0]&&(o.preventDefault(),n=i)}),t(o).on("click touchstart MSPointerDown",function(o){for(var i=!0,a=t(o.target).parents(),c=0;c<a.length;c++)a[c]==n[0]&&(i=!1);i&&(n=!1)})}),this)}}}}(jQuery,window,document);

!function(e,a,r){"use strict";Drupal.behaviors.atrM={attach:function(o,n){function t(a){a.preventDefault(),a.stopPropagation(),e(this).toggleClass("is-open--parent"),"true"==e(this).attr("aria-expanded")?e(this).attr("aria-expanded","false"):"false"==e(this).attr("aria-expanded")&&e(this).attr("aria-expanded","true"),e(this).parent().next(".is-child").toggleClass("is-open--child")}function i(a){var r=e("#rm-accordion-trigger").html();e(a).each(function(){0==e(this).next(".rm-accordion-trigger").length&&e(this).after(r);var a=e(this).parent().parent().attr("id");e(this).next().attr("aria-controls",a+"__child-menu"),e(this).parent().next(".is-child").attr("id",a+"__child-menu")})}if(e(".rm-block").removeClass("js-hide"),r.matchMedia("only screen").matches){var s=n[n.ajaxPageState.theme].at_responsivemenus,c=s.default,l=s.responsive,d=".rm-block .rm-toggle__link",m=s.acd.acd_default,u=s.acd.acd_responsive,p=s.acd.acd_both,_=s.acd.acd_load;e(d,o).on("click",function(r){r.preventDefault(),r.stopPropagation(),e(a.body).toggleClass("rm-is-open"),"true"==e(this).attr("aria-expanded")?(e(this).attr("aria-expanded","false"),e("#rm-toggle__icon--use").attr("xlink:href","#rm-toggle__icon--open")):"false"==e(this).attr("aria-expanded")&&(e(this).attr("aria-expanded","true"),e("#rm-toggle__icon--use").attr("xlink:href","#rm-toggle__icon--close")),e(a).one("click",function(r){0===e(".rm-block").has(r.target).length&&(e(a.body).removeClass("rm-is-open"),e(d).attr("aria-expanded","false"),e("#rm-toggle__icon--use").attr("xlink:href","#rm-toggle__icon--open"))})}),enquire.register(s.bp,{setup:function(){e(a.body).addClass(c),e(".rm-block").parent(".l-r").addClass("rm-region").parent(".l-rw").addClass("rm-row"),"ms-dropmenu"==c&&e(".rm-block__content li:has(ul)").doubleTapToGo(),1==m&&1==_&&(e(".rm-block .menu-level-1").addClass("ms-accordion"),e.ready(i(".ms-accordion .is-parent__wrapper .menu__link")),e(".ms-accordion .rm-accordion-trigger",o).on("click",t))},match:function(){"ms-none"!==l&&l!==c&&(e(a.body).removeClass(c).addClass(l),1==_&&(1==u?0==p&&(e(".rm-block .menu-level-1").addClass("ms-accordion"),e.ready(i(".ms-accordion .is-parent__wrapper .menu__link")),e(".ms-accordion .rm-accordion-trigger",o).on("click",t)):(e(".ms-accordion .rm-accordion-trigger").remove(),e(".rm-block .menu-level-1").removeClass("ms-accordion"),e(".rm-block .menu").removeClass("is-open--child"))),"ms-dropmenu"==l?e(".rm-block__content li:has(ul)").doubleTapToGo():e(".rm-block__content li:has(ul)").doubleTapToGo("unbind"))},unmatch:function(){e(a.body).addClass(c),1==_&&(1==m?0==p&&(e(".rm-block .menu-level-1").addClass("ms-accordion"),e.ready(i(".ms-accordion .is-parent__wrapper .menu__link")),e(".ms-accordion .rm-accordion-trigger",o).on("click",t)):(e(".ms-accordion .rm-accordion-trigger").remove(),e(".rm-block .menu-level-1").removeClass("ms-accordion"),e(".rm-block .menu").removeClass("is-open--child"))),"ms-dropmenu"==c?e(".rm-block__content li:has(ul)").doubleTapToGo():e(".rm-block__content li:has(ul)").doubleTapToGo("unbind"),l!==c&&e(a.body).removeClass(l)}})}}}}(jQuery,document,window);

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
;
!function(t,a,e){"use strict";Drupal.behaviors.atBP={attach:function(t,n){if(a.matchMedia("only screen").matches){var i=n[n.ajaxPageState.theme].at_breakpoints;for(var o in i)i.hasOwnProperty(o)&&function(t,a){enquire.register(a,{match:function(){e.body.classList.add("bp--"+t)},unmatch:function(){e.body.classList.remove("bp--"+t)}})}(o.split("_").join("-"),i[o].mediaquery)}}}}(jQuery,window,document);
