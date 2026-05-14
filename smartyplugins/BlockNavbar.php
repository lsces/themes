<?php
namespace Bitweaver\Plugins;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {navbar} block plugin
 *
 * Type:	block
 * Name:	navbar
 * Input:	set of links that are used for navigation purposes
 */
class BlockNavbar implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
		global $gBitSmarty;

		$links = $this->smarty_block_navbar_get_links( $content );
		$gBitSmarty->assign( 'links',$links );
		return $gBitSmarty->fetch( 'bitpackage:kernel/navbar.tpl' );
	}

	public function smarty_block_navbar_get_links( $content ) {
		$links = [];
		if( preg_match_all( "/<a.*?href=\".*?\">.*?<\/a>/i",$content,$res ) ) {
			$res = $res[0];
			$links = array_unique( $res );
		}
		return $links;
	}

	public function isCacheable(): bool {
		return true;
	}
}