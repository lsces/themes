<?php
/**
 * @version $Header$
 * @package themes
 * @subpackage modules
 */

/**
 * Setup
 */
global $gBitThemes;
$change_theme = $gBitSystem->getConfig('users_themes');
$gBitSmarty->assign( 'change_theme', $change_theme);
$style = $gBitThemes->getStyle();

if( $change_theme == 'y' ) {
	if ($gBitUser->isValid() && $gBitSystem->getConfig('users_preferences') == 'y') {
		$userStyle = $gBitUser->getPreference('theme');
		$style = empty($userStyle) ? $style : $userStyle;
	}
	if (isset($_COOKIE['bw-theme'])) {
		$style = $_COOKIE['bw-theme'];
	}

	$styles = $gBitThemes->getStyles( null, true );
	$stylesList = $gBitThemes->getStyles();

	$gBitSmarty->assign( 'styleslist', $stylesList );
	if(isset($style)){
		$gBitSmarty->assign( 'style', $style);
	}
}
