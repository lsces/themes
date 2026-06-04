<?php
namespace Bitweaver\Plugins;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 * @link https://www.bitweaver.org/wiki/function_booticon function_booticon
 */

/**
 * Turn collected information into an html image
 *
 * @param array $pParams parameter hash
 * @var boolean $pParams['url'] set to TRUE if you only want the url and nothing else
 * @var string $pParams['iexplain'] Explanation of what the icon represents
 * @var string $pParams['iforce'] takes following optins: icon, icon_text, text - will override system settings
 * @var string $pFile Path to icon file
 * @var string iforce  override site-wide setting how to display icons (can be set to 'icon', 'text' or 'icon_text')
 * @access public
 * @return string Full <img> on success
 */
function smarty_function_biticon( $pParams ) {
	global $gBitSystem;

	if( empty( $pParams['iforce'] )) {
		$pParams['iforce'] = '';
	}

	$outstr = '';
	if( isset( $pParams['href'] ) ) {
		$outstr .= '<a href="'.$pParams['href'].'" class="icon"';
		if( isset( $pParams['iexplain'] ) ) {
			$outstr .= ' title="'.htmlentities( $pParams['iexplain'] ).'"';
		}
		$outstr .= '>';
	}

	$outstr .= '<span class="';
	if( isset( $pParams["iname"] ) ) {
		$outstr .= $pParams['iname'];
	}
	if( isset( $pParams["iclass"] ) ) {
		$outstr .=  ' '.$pParams["iclass"].'';
	}
	if( isset( $pParams["class"] ) ) {
		$outstr .=  ' '.$pParams["class"].'';
	}
	$outstr .= '"';

	if( isset( $pParams["id"] ) ) {
		$outstr .=  ' id="'.$pParams['id'].'"';
	}

	if( isset( $pParams['iexplain'] ) ) {
		$outstr .= ' title="'.htmlentities( $pParams['iexplain'] ).'"';
	}

	foreach( array_keys( $pParams ) as $key ) {
		if( strpos( $key, 'on' ) === 0 ) {
			$outstr .=  ' '.$key.'="'.$pParams[$key].'"';
		}
	}

	$outstr .= '></span>';

	if( !empty( $pParams['ilocation'] ) ) {
		if( $pParams['ilocation'] == 'menu' && isset( $pParams['iexplain'] ) ) {
			$outstr .= ' '.$pParams['iexplain'];
		}
	}
	if( isset( $pParams["href"] ) ) {
		$outstr .= '</a>';
	}

	return $outstr;
}
