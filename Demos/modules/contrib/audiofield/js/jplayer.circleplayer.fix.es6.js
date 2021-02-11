/**
 * @file
 * Audiofield jPlayer Circleplayer issue fix.
 *
 * Circleplayer is not properly namespaced, it assumes $ = jQuery.
 * We have to define that namespace equivalence here to prevent issues.
 */

var $ = jQuery;
