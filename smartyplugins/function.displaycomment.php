<?php
namespace Bitweaver\Plugins;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * smarty_function_displaycomment
 */
function smarty_function_displaycomment( $pParams, &$pSmarty ) {
	global $gBitSmarty;

	if (!empty($pParams['comment'])) {
		$comment = $pParams['comment'];
		$gBitSmarty->assign('comment', $comment);
		$ret = ( empty( $pParams['template'] ) )
			? $gBitSmarty->fetch( 'bitpackage:liberty/display_comment.tpl' )
			: $gBitSmarty->fetch( $pParams['template'] );
	}

	return $ret;
}
