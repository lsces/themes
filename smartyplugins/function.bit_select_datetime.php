<?php
namespace Bitweaver\Plugins;

/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage plugins
 */

/**
 * smarty_function_bit_select_datetime
 *
 * Generates an HTML5 date/datetime-local input for form date picking.
 *
 * Parameters:
 *   name      Field name. Defaults to 'date'. A hidden field with this name carries the Unix timestamp.
 *   showtime  Show time selector. 'true' (default) or 'false'.
 *   time      Unix timestamp to display. Defaults to now.
 *
 * Server usage: $_REQUEST[$name] contains a Unix timestamp.
 */
function smarty_function_bit_select_datetime( $pParams, &$gBitSmarty ) {
	global $gBitSystem, $gBitUser;

	$name     = 'date';
	$showtime = 'true';
	$time     = time();
	extract( $pParams );

	$nname    = str_replace( ['[', ']'], '_', $name );
	$time     = (int) $time;
	$dtId     = "{$nname}_dt";
	$tsId     = "{$nname}_ts";

	if( $showtime === 'true' ) {
		$dtValue   = date( 'Y-m-d\TH:i', $time );
		$inputType = 'datetime-local';
	} else {
		$dtValue   = date( 'Y-m-d', $time );
		$inputType = 'date';
	}

	// Display input updates the hidden Unix-timestamp field on change.
	$html  = "<input type=\"{$inputType}\" id=\"{$dtId}\" value=\"{$dtValue}\"";
	$html .= " onchange=\"document.getElementById('{$tsId}').value=Math.floor(new Date(this.value).getTime()/1000)\" />\n";
	$html .= "<input type=\"hidden\" name=\"{$name}\" id=\"{$tsId}\" value=\"{$time}\" />\n";

	return $html . "(" . $gBitUser->getPreference( 'site_display_utc' ) . ")\n";
}
