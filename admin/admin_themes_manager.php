<?php
require_once '../../kernel/includes/setup_inc.php';
require_once KERNEL_PKG_INCLUDE_PATH . 'simple_form_functions_lib.php';

$gBitSystem->verifyPermission( 'p_admin' );

// apply the icon theme
if (!empty( $_REQUEST["site_icon_style"] )) {
	$gBitSystem->storeConfig( 'site_icon_style', $_REQUEST["site_icon_style"], THEMES_PKG_NAME );
}

// apply the style layout
if (!empty( $_REQUEST["site_style_layout"] )) {
	if (!empty( $_REQUEST['approved'] )) {
		$gBitSystem->storeConfig( 'site_style_layout', ( $_REQUEST["site_style_layout"] != 'remove' ) ? $_REQUEST["site_style_layout"] : null, THEMES_PKG_NAME );
	}
	else {
		$gBitSystem->setConfig( 'site_style_layout', ( $_REQUEST["site_style_layout"] != 'remove' ) ? $_REQUEST["site_style_layout"] : null, THEMES_PKG_NAME );
		$gBitSmarty->assign( 'approve', true );
	}
}

// apply the site style
if( !empty( $_REQUEST["site_style"] ) ) {
	if( !empty( $_REQUEST['approved'] ) ) {
		$gBitSystem->storeConfig( 'style', $_REQUEST["site_style"], THEMES_PKG_NAME );
		$gBitSystem->storeConfig( 'style_variation', !empty( $_REQUEST["style_variation"] ) ? $_REQUEST["style_variation"] : '', THEMES_PKG_NAME );
		$gBitThemes->setStyle( $_REQUEST["site_style"] );
	} else {
		$gBitSystem->setConfig( 'style_variation', !empty( $_REQUEST["style_variation"] ) ? $_REQUEST["style_variation"] : '', THEMES_PKG_NAME );
		$gBitSmarty->assign( 'approve', true );
		$gBitThemes->setStyle( $_REQUEST["site_style"] );
	}
}

// Get list of available styles
$styles = $gBitThemes->getStyles();
$gBitSmarty->assign( "styles", $styles );

$subDirs = [ 'style_info', 'alternate' ];
$stylesList = $gBitThemes->getStylesList( '', false, $subDirs );
$gBitSmarty->assign( "stylesList", $stylesList );

$subDirs = [ 'style_info' ];
$iconStyles = $gBitThemes->getStylesList( UTIL_PKG_PATH."iconsets/", false, $subDirs );
$gBitSmarty->assign( "iconStyles", $iconStyles );

$styleLayouts = $gBitThemes->getStyleLayouts();
$gBitSmarty->assign( "styleLayouts", $styleLayouts );

// pick some icons for the preview.
$sampleIcons = [
	'applications-internet',
	'dialog-cancel',
	'dialog-error',
	'dialog-information',
	'dialog-ok',
	'dialog-warning',
	'emblem-favorite',
	'emblem-photos',
	'emblem-readonly',
	'go-jump',
	'go-home',
	'go-next',
	'go-up',
	'help-browser',
	'folder',
];
$gBitSmarty->assign( "sampleIcons", $sampleIcons );

// load css file
$gBitThemes->loadCss( THEMES_PKG_PATH.'css/admin_themes.css' );

$gBitSystem->display( 'bitpackage:themes/admin_themes_manager.tpl', 'Themes Manager' , [ 'display_mode' => 'admin' ]);
