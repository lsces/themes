<?php
namespace Bitweaver\Plugins;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     autolink
 * Purpose:  turn bare http(s):// addresses in already-escaped text into links that open in a new tab;
 *           use after |escape, e.g. {$text|escape|autolink|nl2br}
 * -------------------------------------------------------------
 */
function smarty_modifier_autolink( $string )
{
	return preg_replace_callback( '~https?://[^\s<]+~i', function ( $m ) {
		$url = rtrim( $m[0], '.,;:!?)' );
		return '<a href="'.$url.'" target="_blank" rel="noopener">'.$url.'</a>'.substr( $m[0], strlen( $url ) );
	}, (string)$string );
}
