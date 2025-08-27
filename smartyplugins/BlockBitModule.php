<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
// $Header$
/**
 * \brief Smarty {bitmodule}{/bitmodule} block handler
 *
 * To make a module it is enough to place something like following
 * into corresponding mod-name.tpl file:
 * \code
 *  {bitmodule name="module_name" title="Module title"}
 *    <!-- module Smarty/HTML code here -->
 *  {/bitmodule}
 * \endcode
 *
 * This block may (can) use 2 Smarty templates:
 *  1) module.tpl = usual template to generate module look-n-feel
 *  2) module-error.tpl = to generate diagnostic error message about
 *     incorrect {bitmodule} parameters

\Note
error was used only in case the name was not there.
I fixed that error case. -- mose
 
 */

namespace Bitweaver\Plugins;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;
use Smarty\Exception;

class BlockBitModule implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
		if (is_null($content)) {
			return '';
		}
		global $gBitSmarty;
		$moduleTag = !empty( $params['tag'] ) ? $params['tag'] : 'div';
		$gBitSmarty->assign( 'moduleTag', $moduleTag );
		if( empty( $content )) {
			return '';
		} else {
			$params['data'] = $content;
		}

		if( !empty( $params['name'] ) ) {
			$params['name'] = preg_replace( "/[^a-zA-Z0-9\\-\\_]/", "", $params['name'] );
		}
		$gBitSmarty->assign( 'modInfo', $params );

		$temp = $gBitSmarty->fetch('bitpackage:themes/module.tpl');
		return $temp;
		
	}

	public function isCacheable(): bool {
		return true;
	}
}