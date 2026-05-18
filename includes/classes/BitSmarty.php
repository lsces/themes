<?php
/**
 * Smarty Library Inteface Class
 *
 * @package Smarty
 * @version $Header$
 *
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.
 */

 /**
 * required setup
 */
namespace Bitweaver\Themes;

use Bitweaver\KernelTools;
require_once EXTERNAL_LIBS_PATH.'smarty/libs/Smarty.class.php';

/**
 * PermissionCheck
 *
 * @package kernel
 */
class PermissionCheck {
	function check( $perm ) {
		global $gBitUser;
		return $gBitUser->hasPermission( $perm );
	}
}

/**
 * BitSmarty
 *
 * @package kernel
 */
class BitSmarty extends \Smarty\Smarty {

	public $mCompileRsrc;

	/**
	 * BitSmarty initiation
	 *
	 * @return void
	 */
	public function __construct() {
		global $smarty_force_compile, $smarty_debugging;
		parent::__construct();
		$this->mCompileRsrc = null;
		$this->config_dir = "configs/";
		// $this->caching = true;
		$this->force_compile = isset($smarty_force_compile) && $smarty_force_compile;
		$this->debugging = $smarty_debugging ?? false;
		$this->assign( 'app_name', 'bitweaver' );
		$this->error_reporting = BIT_PHP_ERROR_REPORTING;

		global $permCheck;
		$permCheck = new PermissionCheck();
		$this->assign( 'perm', $permCheck );
	}

	public function scanPackagePluginDirs() {
		global $gBitSystem;
		foreach( $gBitSystem->mPackages as &$packageHash ) {
			if( $packageHash['dir'] != THEMES_PKG_DIR && file_exists( $packageHash['path'].'smartyplugins' ) ) {
//				$this->addPluginsDir( $packageHash['path'].'smartyplugins' );
			}
		}
	}

	/**
	 * Override the fetch method to add custom behavior.
	 *
	 * @param string $resource_name The template resource name.
	 * @param string|null $cache_id The cache ID.
	 * @param string|null $compile_id The compile ID.
	 * @return string The output of the template.
	 */
	public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
		global $gBitSystem;
		if( strpos( $template, ':' )) {
			list( $resource, $location ) = explode( ':', $template);
			if( $resource == 'bitpackage' ) {
				list( $package, $tpl ) = explode( '/', $location );
				// exclude temp, as it contains nexus menus
				if( !$gBitSystem->isPackageActive( $package ) && $package != 'temp' ) {
					return '';
				}
			}
		}

		if( defined( 'TEMPLATE_DEBUG' ) && TEMPLATE_DEBUG == true ) {
			echo "\n<!-- - - - {$template} - - - -->\n";
		}
		$this->debugging = false;
		$templateObject = parent::fetch($template, $cache_id, $compile_id);
		return $templateObject;
	}

	/**
	 * getModuleConfig
	 *
	 * @return array hash of config values set in sibling .cfg file
	 */
	public function getModuleConfig( $pModuleRsrc ) {
		global $moduleConfig;
		$moduleConfig = [];
		$moduleConfigFile = str_replace( '.tpl', '.cfg', $pModuleRsrc );
		$this->includeSiblingFile( $moduleConfigFile );
		return $moduleConfig;
	}

	/**
	 * THE method to invoke if you want to be sure a tpl's sibling php file gets included if it exists. This
	 * should not need to be invoked from anywhere except within this class
	 *
	 * @param string $pFile file to be included, should be of the form "bitpackage:<packagename>/<templatename>"
	 * @return bool true if a sibling php file was included
	 */
	private function includeSiblingFile( $pFile, $pIncludeVars=null ) {
		global $gBitThemes;
		$ret = false;
		if( strpos( $pFile, ':' )) {
			list( $resource, $location ) = explode( ':', $pFile );
			if( $resource == 'bitpackage' ) {
				list( $package, $modFile ) = explode( '/', $location );
				$subdir = preg_match( '/mod_/', $modFile ) ? 'modules' : 'templates';
				if( preg_match('/mod_/', $modFile ) || preg_match( '/center_/', $modFile ) ) {
					global $gBitSystem;
					$path = constant( strtoupper( $package )."_PKG_PATH" );
					$includeFile = "$path$subdir/$modFile";
					if( file_exists( $includeFile )) {
						global $gBitSmarty, $gBitSystem, $gBitUser, $gQueryUserId, $moduleParams;
						$moduleParams = [];
						if( !empty( $pIncludeVars['module_params'] ) ) {
							// module_params were passed through via the {include},
							// e.g. {include file="bitpackage:foobar/mod_list_foo.tpl" module_params="user_id=`$gBitUser->mUserId`&sort_mode=created_desc"}
							$moduleParams['module_params'] = $gBitThemes->parseString( $pIncludeVars['module_params'] );
						} else {
							// Module Params were passed in from the template, like kernel/dynamic.tpl
							$moduleParams = $this->getTemplateVars( 'moduleParams' );
						}
						include $includeFile;
						$ret = true;
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * verifyCompileDir
	 *
	 * @access public
	 * @return void
	 */
	public function verifyCompileDir(): void {
		global $gBitThemes, $gBitLanguage, $bitdomain;

		$temp = !defined( "TEMP_PKG_PATH" ) ? BIT_ROOT_PATH . "temp/" : TEMP_PKG_PATH;

		$endPath = $bitdomain.'/'.$gBitThemes->getStyle().'/'.$gBitLanguage->mLanguage;

		// Compile directory
		$compDir = $temp . "templates_c/$endPath";
		$compDir = str_replace( '//', '/', $compDir );
		$compDir = KernelTools::clean_file_path( $compDir );
		KernelTools::mkdir_p( $compDir );
		$this->setCompileDir( $compDir );

		// Cache directory
		$cacheDir = $temp . "cache/$endPath";
		$cacheDir = str_replace( '//', '/', $cacheDir );
		$cacheDir = KernelTools::clean_file_path( $cacheDir );
		KernelTools::mkdir_p( $cacheDir );
		$this->setCacheDir( $cacheDir );
	}
}
