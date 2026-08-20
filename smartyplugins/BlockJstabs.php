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

			// ?jstab=N selects the Nth tab (0-based) by its position in the nav list —
			// tab hrefs are title-derived slugs (see BlockJstab::handle()), not numeric, so
			// there's no fixed id to target directly; positional selection is the only
			// option that works without knowing any tab's generated id in advance.
			// Previously this branch computed $tab but never used it, hardcoding a literal
			// '#profile' selector that doesn't correspond to any real tab in this app —
			// jstab links silently never worked. Fixed 2026-08-20.
			if( isset( $_REQUEST['jstab'] ) && preg_match( "!^\d+$!", $_REQUEST['jstab'] ) ) {
				$tab = (int)$_REQUEST['jstab'];
				$setupJs = "$('#$tabId a').eq($tab).tab('show');";
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
