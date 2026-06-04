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
	// Navigation
	"go-next"                        => "Next / navigate right / sort indicator",
	"go-previous"                    => "Previous / back / navigate left",
	"go-home"                        => "Home",
	"go-up"                          => "Move up / navigate up",
	"go-down"                        => "Move down / navigate down",
	"go-first"                       => "First page",
	"go-last"                        => "Last page",
	"view-sort-ascending"            => "Sort",
	"view-refresh"                   => "Refresh",
	"zoom-in"                        => "Zoom in / magnify",
	// Edit actions
	"edit-delete"                    => "Delete / remove",
	"document-properties"            => "Edit item",
	"list-add"                       => "Add / new item",
	"list-remove"                    => "Remove item",
	"edit-find"                      => "Search / find",
	"edit-undo"                      => "Undo",
	"edit-clear"                     => "Clear / recycle",
	"edit-cut"                       => "Cut / leave",
	"accessories-text-editor"        => "Edit text / pencil",
	"document-print"                 => "Print",
	"document-save"                  => "Save",
	// Files and content
	"text-x-generic"                 => "File / list / document",
	"image-x-generic"                => "Image / picture",
	"folder-open"                    => "Open folder",
	"folder"                         => "Folder",
	"insert-object"                  => "Insert object",
	// Dialogs and status
	"dialog-ok"                      => "Success / accepted",
	"dialog-warning"                 => "Warning",
	"dialog-error"                   => "Error",
	"dialog-information"             => "Information / announcement",
	"dialog-cancel"                  => "Cancel",
	"process-stop"                   => "Stop / cancel / disable",
	"emblem-important"               => "Required / flagged / asterisk",
	// Communication
	"internet-mail"                  => "Email / envelope / inbox",
	"internet-group-chat"            => "Comment / discussion",
	"mail-attachment"                => "Attachment / paperclip",
	"network-transmit"               => "Upload / send / RSS feed",
	"network-receive"                => "Download",
	// Auth and permissions
	"lock"                           => "Lock / key / permissions",
	"emblem-readonly"                => "Hidden / read-only",
	"emblem-unreadable"              => "Visible / show",
	// Users
	"user-desktop"                   => "User",
	"user-home"                      => "User home",
	"system-users"                   => "User group / users",
	"system-log-out"                 => "Sign out",
	"preferences-system-network-proxy" => "Telephone / contact",
	// Emblems
	"emblem-favorite"                => "Bookmark / tag / favourite",
	"emblem-shared"                  => "Shared / unlocked",
	"emblem-symbolic-link"           => "Link",
	"emblem-downloads"               => "Download emblem",
	"emblem-photos"                  => "Photos emblem",
	// Media
	"camera-photo"                   => "Camera / photo",
	"camera-video"                   => "Video / film",
	"media-playback-pause"           => "Pause",
	"media-playback-stop"            => "Stop",
	"media-skip-backward"            => "Skip to start",
	// System
	"preferences-system"             => "Settings / administration",
	"drive-harddisk"                 => "Hard drive / disk quota",
	"package-x-generic"              => "Package / shopping cart / box",
	"help-browser"                   => "Help / documentation",
	"config-language"                => "Language / translate",
	"applications-accessories"       => "Plugin / layers",
	"bookmark-new"                   => "Bookmark",
	"appointment"                    => "Clock / time / history",
	// Faces
	"face-smile"                     => "Positive / happy",
	"face-sad"                       => "Negative / sad",
	// Weather
	"weather-clear-night"            => "Stop monitoring / night mode",
	// Collapsed/expanded tree nodes
	"collapsed"                      => "Collapsed tree node",
	"expanded"                       => "Expanded tree node",
	// App-specific (installer / admin)
	"bitweaver"                      => "Bitweaver",
	"adodb"                          => "ADOdb",
	"smarty"                         => "Smarty",
	"firebird"                       => "Firebird",
	"php"                            => "PHP",
	"pear"                           => "PEAR",
	"mysql"                          => "MySQL",
	"postgresql"                     => "PostgreSQL",
	"oracle"                         => "Oracle",
	"htmlpurifier"                   => "HTMLPurifier",
	"google-favicon"                 => "Google",
	"pdf"                            => "PDF",
	"silhouette"                     => "Anonymous / silhouette user",
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
