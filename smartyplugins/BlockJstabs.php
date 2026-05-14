<?php
namespace Bitweaver\Plugins;

use Bitweaver\BitBase;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {jstabs} block plugin
 *
 * Type:		block
 * Name:		jstabs
 * Input:		you can use {jstab tab=<tab number>} (staring with 0) to select a given tab
 *              or you can use the url to do so: page.php?jstab=<tab number>
 * Abstract:	Used to enclose a set of tabs
 */
class BlockJstabs implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
		global $gBitSystem, $jsTabLinks;
		if( $repeat ){
			$jsTabLinks = [];
		} else {
			extract( $params );

			$tabId = !empty( $params['id'] ) ? $params['id'] : substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);

			if( isset( $_REQUEST['jstab'] ) ) {
				// make sure we aren't passed any evil shit
				if( !isset( $tab ) && isset( $_REQUEST['jstab'] ) && preg_match( "!^\d+$!", $_REQUEST['jstab'] ) ) {
					$tab = $_REQUEST['jstab'];
				}
				$setupJs = '$(\'#'.$tabId.' a[href="#profile"]\').tab(\'show\');';
			} else {
				$setupJs = "$('#$tabId a:first').tab('show');";
			}

			$tabType = BitBase::getParameter( $params, 'tabtype', 'tab' );

			$ret = '<ul class="nav nav-'.$tabType.'s" data-tab="'.$tabType.'" id="'.$tabId.'">';
			foreach( $jsTabLinks as $tabLink ) {
				$ret .= $tabLink;
			}
			$ret .= '</ul><div class="tab-content">'.$content.'</div>';
			$ret .= '<script nonce="{$cspNonce}">/*<![CDATA[*/ $(\'#'.$tabId.' a\').click(function (e) { e.preventDefault(); $(this).tab(\'show\'); }); '.$setupJs .'/*]]>*/</script> ';

			$jsTabLinks = NULL;

			return $ret;
		}
		return '';
	}

	public function isCacheable(): bool {
		return true;
	}
}
