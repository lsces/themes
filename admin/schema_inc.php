<?php

global $gBitInstaller;

$tables = [
	/* module_id			a unique id
	 * package				package for which this module is used
	 * layout_area			column in which this module is visible - l r or c
	 * module_rows			number of lines displayed
	 * module_rsrc			path to module template
	 * parameters			parameters for this particular module
	 * pos					positional data to specify the order in which the modules are displayed */

	'themes_layouts' => "
		module_id I4 PRIMARY,
		title C(255),
		layout C(160) NOTNULL DEFAULT 'kernel',
		layout_area C(1) NOTNULL,
		module_rows I4,
		module_rsrc C(250) NOTNULL,
		params C(255),
		cache_time I8,
		roles C(255),
		pos I4 NOTNULL DEFAULT '1'
	",

	'themes_custom_modules' => "
		name C(40) PRIMARY NOTNULL,
		title C(200),
		data X
	",
];

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( THEMES_PKG_NAME, $tableName, $tables[$tableName], true );
}

$indices = [
	'themes_layouts_module_idx' => [ 'table' => 'themes_layouts', 'cols' => 'module_id', 'opts' => null ],
];
$gBitInstaller->registerSchemaIndexes( THEMES_PKG_NAME, $indices );

// ### Sequences
$sequences = [
	'themes_layouts_module_id_seq' => [ 'start' => 1 ],
];
$gBitInstaller->registerSchemaSequences( THEMES_PKG_NAME, $sequences );

$gBitInstaller->registerPackageInfo( THEMES_PKG_NAME, [
	'description' => "The Themes package is an integral part of bitweaver which allows you to control the look and feel of you site.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
] );

//$gBitInstaller->registerSchemaTable( THEMES_PKG_NAME, '', '', true );
$gBitInstaller->registerPreferences( THEMES_PKG_NAME, [
//	[ THEMES_PKG_NAME,'themes_joined_js_css', 'y' ],
//	[ THEMES_PKG_NAME,'themes_packed_js_css', 'y' ],
	[ THEMES_PKG_NAME,'site_slide_style', DEFAULT_THEME ],
	[ THEMES_PKG_NAME,'style', DEFAULT_THEME ],
	[ THEMES_PKG_NAME,'site_style_layout', 'gala_1' ],
	[ THEMES_PKG_NAME,'site_icon_style', 'tango' ],
	[ THEMES_PKG_NAME,'site_top_bar_dropdown', 'y' ],
	[ THEMES_PKG_NAME,'site_bot_bar', 'y' ],
	// Disable languages menu by default since it is duplicated in admin menu
	[ THEMES_PKG_NAME,'menu_languages', 'n' ],
	// Disable languages menu by default since it is linked from the header
	[ THEMES_PKG_NAME,'menu_users', 'n' ],
] );

// Package Requirements
$gBitInstaller->registerRequirements( THEMES_PKG_NAME, [
	'liberty'   => [ 'min' => '5.0.0' ],
	'users'     => [ 'min' => '5.0.0' ],
	'kernel'    => [ 'min' => '5.0.0' ],
	'languages' => [ 'min' => '5.0.0' ],
] );
