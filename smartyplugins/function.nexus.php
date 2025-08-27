<?php
namespace Bitweaver\Plugins;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {nexus} function plugin
 *
 * Type:	function
 * Name:	nexus
 * Input:	- id	(required) - id of the menu that should be displayed
 */
function smarty_function_nexus( $params, &$gBitSmarty ) {
	extract($params);

	if( empty( $id ) ) {
		$gBitSmarty->trigger_error("assign: missing id");
		return;
	}

	$tmpNexus = new \Bitweaver\Nexus\Nexus( $id );
	$nexusMenu = $tmpNexus->mInfo;

	$gBitSmarty->assign( 'nexusMenu', $nexusMenu );
	$gBitSmarty->assign( 'nexusId', $id );
	$gBitSmarty->display('bitpackage:nexus/nexus_module.tpl');
}
