<?php
namespace Bitweaver\Plugins;

use Bitweaver\KernelTools;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {form} block plugin
 *
 * Type:     block
 * Name:     form
 * Input:
 *           - ipackage    (optional) - package where we should direct the form after submission
 *           - ifile       (optional) - file that is targetted
 *           - ianchor     (optional) - move to anchor after submitting
 *                         if neither are set, SCRIPT_NAME is used as url
 *           - legend      if set, it will generate a fieldset using the input as legend
 * @uses smarty_function_escape_special_chars()
 * @todo somehow make the variable that is contained within $iselect global --> this will allow importing of outside variables not set in $_REQUEST
 */

class BlockForm implements BlockHandlerInterface {

	public function handle( $pParams, $pContent, Template $template, &$repeat): string {
		global $gBitSystem, $gBitUser, $gSniffer;

		if( !empty($pContent) ) {
			if ( $template ) {
				if( !isset( $pParams['method'] ) ) {
					$pParams['method'] = 'post';
				}
				$atts = '';
				$url = $gBitSystem->isLive() && isset( $pParams['secure'] ) && $pParams['secure']
					// This is NEEDED to enforce HTTPS secure logins!
					? 'https://' . $_SERVER['HTTP_HOST'] : '';
				$onsubmit = '';

				// services can add something to onsubmit
				if( $template->getTemplateVars( 'serviceOnsubmit' ) ) {
					$onsubmit .= $template->getTemplateVars( 'serviceOnsubmit' ).";";
				}

				foreach( $pParams as $key => $val ) {
					switch( $key ) {
						case 'ifile':
						case 'ipackage':
							if( $key == 'ipackage' ) {
								$url = match ( $val ) {
									'root'  => BIT_ROOT_URL . $pParams['ifile'],
									default => constant( strtoupper( $val ) . '_PKG_URL' ) . $pParams['ifile'],
								};
							}
							break;
						case 'legend':
							if( !empty( $val ) ) {
								$legend = '<legend>'.KernelTools::tra( $val ).'</legend>';
							}
							break;
						// this is needed for backwards compatibility since we sometimes pass in a url
						case 'action':
							if ( !empty( $val ) ) {
								if( substr( $val, 0, 4 ) == 'http' ) {
									if( isset( $pParams['secure'] ) && $pParams['secure'] && ( substr( $val, 0, 5 ) != 'https' )) {
										$val = preg_replace( '/^http/', 'https', $val );
									}
									$url = $val;
								} else {
									$url .= $val;
								}
							}
							break;
						case 'ianchor':
						case 'secure':
							break;
						case 'onsubmit':
							if( !empty( $val ) ) {
								$onsubmit .= "$val;";
							}
							break;
						default:
							if( !empty( $val ) ) {
								$atts .= "$key=\"$val\" ";
							}
							break;
					}
				}

				if( empty( $url )) {
					$url = $_SERVER['SCRIPT_NAME'];
				} else if( $url == 'https://' . $_SERVER['HTTP_HOST'] ) {
					$url .= $_SERVER['SCRIPT_NAME'];
				}

				$onsub = !empty( $onsubmit ) ? " onsubmit=\"$onsubmit\"" : '';
				$ret = '<form action="'.$url.( !empty( $pParams['ianchor'] ) ? '#'.$pParams['ianchor'] : '' ).'" '.$atts.$onsub.'>';
				$ret .= isset( $legend ) ? "<fieldset>$legend" : '';
				if( is_object( $gBitUser ) && $gBitUser->isRegistered() ) {
					$ret .= '<input type="hidden" name="tk" value="'.$gBitUser->mTicket.'" />';
				}
				$ret .= $pContent;
				$ret .= isset( $legend ) ? '</fieldset>' : '';			// close the open tags
				$ret .= '</form>';
				return $ret;
			}
				global $gSmartyFormHorizontal;
				// global var other plugin functions will pick up to add proper col-XX-YY styling for horizontal forms
				$gSmartyFormHorizontal = !empty( $pParams['class'] ) && strpos( $pParams['class'], 'form-horizontal' ) !== false;
				return '';

		}
		return '';
	}

	public function isCacheable(): bool {
		return true;
	}
}