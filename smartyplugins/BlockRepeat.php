<?php
namespace Bitweaver\Plugins;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;
use Smarty\Exception;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin
 * ------------------------------------------------------------- 
 * File: block.repeat.php
 * Type: block
 * Name: repeat
 * Purpose: repeat a template block a given number of times
 * Parameters: count [required] - number of times to repeat
 * assign [optional] - variable to collect output
 * Author: Scott Matthewman <scott@matthewman.net>
 * -------------------------------------------------------------
 */
class BlockRepeat implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
		global $gBitSmarty;
		if( !empty( $content ) ) {
			$intCount = intval( $params['count'] );
			if( $intCount < 0 ) {
				throw new Exception(
					"block: negative 'count' parameter" ); 
			}

			$strRepeat = str_repeat( $content, $intCount );
			if( !empty( $params['assign'] ) ) {
				$gBitSmarty->assign($params['assign'], $strRepeat );
			} else {
				echo $strRepeat;
			}
		}
		return '';
	}

	public function isCacheable(): bool {
		return true;
	}
}