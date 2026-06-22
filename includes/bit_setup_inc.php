<?php
namespace Bitweaver\Themes;

$pRegisterHash = [
	'package_name' => 'themes',
	'package_path' => dirname( dirname( __FILE__ ) ).'/',
	'activatable' => false,
	'required_package'=> true,
];

// fix to quieten down VS Code which can't see the dynamic creation of these ...
define( 'THEMES_PKG_NAME', $pRegisterHash['package_name'] );
define( 'THEMES_PKG_URL', BIT_ROOT_URL . basename( $pRegisterHash['package_path'] ) . '/' );

$gBitSystem->registerPackage( $pRegisterHash );

define( 'DEFAULT_ICON_STYLE', $gBitSystem->getConfig( 'default_icon_style', 'tango' ) );

$gLibertySystem->registerService(
	LIBERTY_SERVICE_THEMES,
	THEMES_PKG_NAME,
	[
		'content_display_function' => 'themes_content_display',
		'content_list_function' => 'themes_content_list',
	],
	[ 'description' => 'Applied when user themes are enabled; See theme pkg administration to enable.' ],
);

BitThemes::loadSingleton();
global $gBitThemes, $gBitSmarty;

$gBitSmarty->verifyCompileDir();

// setStyle first, in case package decides it wants to reset the style in it's own <package>/bit_setup_inc.php
if( !$gBitThemes->getStyle() ) {
	$gBitThemes->setStyle( DEFAULT_THEME );
}
$gBitSmarty->assign( 'gBitThemes', $gBitThemes );

// load some core javascript files
$gBitThemes->loadJavascript( THEMES_PKG_PATH.'js/bitweaver.js', true, 1 );
$gBitThemes->loadAjax( $gBitSystem->getConfig( 'themes_jquery_hosting', 'jquery' ) );

if( $gBitSystem->isFeatureActive( 'site_fancy_zoom' )) {
	$gBitThemes->loadJavascript( THEMES_PKG_PATH.'js/fancyzoom/js-global/FancyZoom.js', true, 80 );
	$gBitThemes->loadJavascript( THEMES_PKG_PATH.'js/fancyzoom/js-global/FancyZoomHTML.js', true, 81 );
	$gBitSystem->setOnloadScript( 'setupZoom();' );
}

$gBitSystem->mOnload[] = 'BitBase.setupShowHide();';

$gBitThemes->loadJavascript( THEMES_PKG_PATH.'js/jquery.innerfade.js', FALSE, 700, FALSE );
$gBitThemes->loadJavascript( THEMES_PKG_PATH.'js/overlib.js', FALSE, 701, FALSE );

if( is_readable(CONFIG_PKG_PATH.'theme_setup_inc.php')) {
	include_once CONFIG_PKG_PATH.'theme_setup_inc.php';
}
