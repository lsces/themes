<?php
namespace Bitweaver\Plugins;

use Bitweaver\KernelTools;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
* smarty_function_attachmenthelp
*/
function smarty_function_attachhelp( $pParams, &$gSmartyTemplate ) {
	global $gBitSystem, $gBitSmarty;

	// print legend if desired
	if( !empty( $pParams['legend'] )) {
		$gBitSmarty->assign( 'legend', TRUE );
	}

	// get all the info into the right place
	if( !empty( $pParams['hash'] ) && is_array( $pParams['hash'] )) {
		$pParams = array_merge( $pParams, $pParams['hash'] );
		unset( $pParams['hash'] );
	}

	// prepare the output
	if( empty( $pParams['attachment_id'] )) {
		trigger_error( KernelTools::tra( 'You need to provide an attachment_id' ));
		return;
	} elseif( !empty( $pParams['wiki_plugin_link'] )) {
		$attachhelp = trim( $pParams['wiki_plugin_link'] );
	} else {
		$attachhelp = "{attachment id={$pParams['attachment_id']}}";
	}

	// if we're viewing this page at a particular size, we want to include that in the output
	if( !empty( $_REQUEST['size'] )) {
		$attachhelp = str_replace( "}", " size={$_REQUEST['size']}}", $attachhelp );
	}

	$gBitSmarty->assign( 'attachhelp', $attachhelp );
	return $gBitSmarty->fetch( 'bitpackage:liberty/attachhelp.tpl' );
}
