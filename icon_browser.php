<?php
/**
 * @version $Header$
 *
 * Copyright (c) 2008 bitweaver
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.
 *
 * @package themes
 * @subpackage functions
 */

/**
 * Setup
 */
use Bitweaver\KernelTools;
require_once "../kernel/includes/setup_inc.php";

if( !$gBitUser->isRegistered() ) {
	$gBitSystem->fatalError( "You need to be registered to view this page." );
}

$iconUsage = [
	"dialog-ok"                => "Success / Accept",
	"document-open"            => "Import",
	"document-properties"      => "Configuration",
	"document-save-as"         => "Export",
	"edit-copy"                => "Copy",
	"edit-delete"              => "Delete",
	"go-down"                  => "Navigate down",
	"go-home"                  => "Home",
	"go-next"                  => "Navigate right / next",
	"go-previous"              => "Navigate left / previous",
	"go-up"                    => "Navigate up",
	"help-contents"            => "Help",
	"insert-object"            => "Insert",
	"mail-forward"             => "Mail send",
	"view-sort-ascending"      => "All things sorting",
	"view-sort-descending"     => "All things sorting",
	"window-close"             => "Close window",
	"accessories-text-editor"  => "Edit",
	"applications-accessories" => "Plugin",
	"preferences-system"       => "bitweaver administration",
	"emblem-default"           => "Current selection",
	"emblem-downloads"         => "Download",
	"emblem-favorite"          => "Favorite",
	"emblem-readonly"          => "Extra permissions set / Previously used as locked",
	"emblem-shared"            => "No permissions set or unlocked",
	"x-office-document"        => "Note",
	"x-office-presentation"    => "Slideshow",
	"folder"                   => "Folder",
	"dialog-error"             => "Error",
	"dialog-information"       => "Information",
];
$gBitSmarty->assign( 'iconUsage', $iconUsage );

$iconList = [];
$iconNames = [];
$iconThemes = ( !empty( $_REQUEST['icon_style'] ) ) ? [ $_REQUEST['icon_style'] ] : scandir( UTIL_PKG_PATH . "iconsets/" );

foreach( $iconThemes as $iconStyle ) {
	if( $icons = icon_fetcher( $iconStyle ) ) {
		$iconList[$iconStyle] = $icons;
		$iconNames = array_merge( $iconNames, $iconList[$iconStyle] );
	}
}

asort( $iconNames );
$gBitSmarty->assign( 'iconNames', $iconNames );
$gBitSmarty->assign( 'iconList', $iconList );

$gBitSystem->display( 'bitpackage:themes/icon_browser.tpl', KernelTools::tra( 'Icon Listing' ) , [ 'display_mode' => 'display' ]);

function icon_fetcher( $pStyle = DEFAULT_ICON_STYLE ) {
	$ret = [];
	if( strpos( $pStyle, '.' ) !== 0 && $pStyle != 'CVS' ) {
		$stylePath = UTIL_PKG_PATH."iconsets/".$pStyle;

		// Primary: scalable SVGs give the most complete name list
		if( is_dir( $stylePath."/scalable" )) {
			foreach( scandir( $stylePath."/scalable" ) as $icon ) {
				if( preg_match( "#\.svg$#", $icon )) {
					$name = substr( $icon, 0, -4 );
					$ret[$name] = $name;
				}
			}
		}

		// Supplement with large PNGs not covered by scalable
		if( is_dir( $stylePath."/large" )) {
			foreach( scandir( $stylePath."/large" ) as $icon ) {
				if( preg_match( "#\.png$#", $icon ) && !preg_match( "#^process-working\.#", $icon )) {
					$name = substr( $icon, 0, -4 );
					$ret[$name] ??= $name;
				}
			}
		}
	}
	ksort( $ret );
	return $ret;
}
