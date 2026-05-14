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
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     block.translate.php
 * Type:     block
 * Name:     translate
 * Purpose:  translate a block of text
 * -------------------------------------------------------------
 */
//global $lang;
//include_once('lang/language.php');
class BlockTr implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
		echo KernelTools::tra( $content );
		return '';
	}

	public function isCacheable(): bool {
		return true;
	}
}
