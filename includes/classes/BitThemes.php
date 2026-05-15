<?php
/**
 * @package themes
 */

/**
 * Set-up
 */
namespace Bitweaver\Themes;

use Bitweaver\BitCache;
use Bitweaver\BitSingleton;
use Bitweaver\Users\RolePermUser;
use Bitweaver\Users\RoleUser;
use Bitweaver\KernelTools;

/**
 * BitThemes
 *
 * @package themes
 * @uses BitBase
 */
class BitThemes extends BitSingleton {
	// Array that contains a full description of the current layout
	public $mLayout = [];

	// contains the currently active style
	public $mStyle;

	// an array with style information
	public $mStyles = [];

	// Ajax libraries needed by current Ajax framework (MochiKit libs, etc.)
	public $mAjaxLibs = [];

	// Auxiliary Javascript and Css Files
	public $mAuxFiles = [
		'js'  => [],
		'css' => [],
	];

	// Raw Javascript and Css Files
	public $mRawFiles = [
		'js'  => [],
		'css' => [],
	];
	public $mUnloadFiles = [];

	// Display Mode
	public $mDisplayMode;

	// When all modules are loaded they are loaded here
	public $mModules = [];

	// Caching object
	public $mThemeCache;

	/**
	 * Summary of mFormatHeader
	 * @var 
	 */
	public $mFormatHeader;

	/**
	 * Initiate class
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		// start up caching engine
		$this->mThemeCache = new BitCache( 'themes', true );
	}

	public static function isCacheableClass() {
		return true;
	}

	public function __sleep() {
		return [ ...parent::__sleep(), 'mStyles', 'mThemeCache', 'mAjaxLibs', 'mAuxFiles', 'mRawFiles', 'mModules' ];
	}

	// {{{ =================== Styles ====================
	/**
	 * load up style related information that must be
	 * loaded before template rendering begins
	 *
	 * @note this is a interim method as we continue sorting
	 * out the optimal order of operations for rendering
	 * pages. there was some conflict between rendering
	 * module templates and loading styles, where some
	 * style information needs to be loaded before the templates
	 * are rendered, and some such as packing javascript and css
	 * should happen after
	 *
	 * @see BitSystem::preDisplay
	 */
	public function preLoadStyle(){
		// define style url and path
		if( !defined( 'THEMES_STYLE_URL' ) ) {
			define( 'THEMES_STYLE_URL', $this->getStyleUrl() );
		}
		if( !defined( 'THEMES_STYLE_PATH' ) ) {
			define( 'THEMES_STYLE_PATH', $this->getStylePath() );
		}
	}

	/*
	 * load up all style related information
	 * populates mStyle and mStyles
	 *
	 * @access public
	 * @return void
	 */
	public function loadStyle(): void {
		global $gBitSystem;
		// load default css files
		if( empty( $this->mStyles['styleSheet'] )) {
			$this->mStyles['styleSheet'] = $this->getStyleCssFile( '', true );
		}

		// load tpl files that need to be included
		$this->loadTplFiles( "html_head_inc" );
		$this->loadTplFiles( "footer_inc" );

		// join javascript files that have been loaded
		$this->mStyles['joined_javascript'] = $this->joinAuxFiles( 'js' );

		// layout is called as the very first, package css is around pos 300 and theme / browser are called last
		// css inserted in <pkg>/html_head_inc.tpl is called before these files since these are inserted last
//		$this->loadCss( $this->getLayoutCssFile(),       true, 1,	true, true );
		$this->loadCss( $this->getStyleCssFile(),        true, 998,	true, true );
		$this->loadCss( $this->getBrowserStyleCssFile(), true, 999,	true, true );
		// check for customized CSS file
		if( file_exists( CONFIG_PKG_PATH.'css/config.css' ) ) {
			$this->loadCss( CONFIG_PKG_PATH.'css/config.css' );
		}
		$this->mStyles['joined_css'] = $this->joinAuxFiles( 'css' );
	}

	/**
	 * Get the current style from the config array
	 *
	 * @return string
	 */
	public function getStyle() {
		global $gBitSystem;
		if( empty( $this->mStyle )) {
			$this->mStyle = $gBitSystem->getConfig( 'style' );
		}
		return $this->mStyle;
	}

	/**
	 * figure out the current style
	 *
	 * @param string $ pScanFile file to be looked for
	 * @return void
	 */
	public function setStyle( $pStyle ): void {
		global $gBitSmarty;
		$this->mStyle = $pStyle;
		$gBitSmarty->verifyCompileDir();
	}

	/**
	 * Get the location as either an absolute path or a URL for current theme style CSS
	 *
	 * @param string $pStyle to be looked for
	 * @param bool $pUrl 
	 * @return string 
	 */
	public function getStyleCssFile( string  $pStyle = '', bool $pUrl = false ): string {
		global $gBitSystem;
		if( empty( $pStyle )) {
			$pStyle = $this->getStyle();
		}
		$ret = '';
		$base = $pUrl ? $this->getStyleUrl() : $this->getStylePath();

		if( $gBitSystem->getConfig( 'style_variation' ) && is_readable( $this->getStylePath().'alternate/'.$gBitSystem->getConfig( 'style_variation' ).'.css' )) {
			$ret = $base.'alternate/'.$gBitSystem->getConfig( 'style_variation' ).'.css';
		} elseif( is_readable( $this->getStylePath().$pStyle.'.css' )) {
			$ret = $base.$pStyle.'.css';
		}
		return $ret;
	}

	/**
	 * get browser specific css file
	 *
	 * @param bool $pUrl 
	 * @return string path to browser specific css file
	 */
	public function getBrowserStyleCssFile( $pUrl = false ): string {
		global $gSniffer;

		$base = $pUrl ? $this->getStyleUrl() : $this->getStylePath();
		$subpath = $this->getStyle().'_'.$gSniffer->property( 'browser' );

		// Allow us to split by major version with a fallback for others
		if( file_exists( $this->getStylePath().$subpath.$gSniffer->property( 'maj_ver' ).'.css' )) {
			$ret = $base.$subpath.$gSniffer->property( 'maj_ver' ).'.css';
		} elseif( file_exists( $this->getStylePath().$subpath.'.css' )) {
			$ret = $base.$subpath.'.css';
		}
		return !empty( $ret ) ? $ret : '';
	}

	/**
	 * get browser specific css file
	 *
	 * @param none
	 * @return string to browser specific css file
	 */
	public function getLayoutCssFile(): string {
		global $gBitSystem;
		if( $gBitSystem->isFeatureActive( 'site_style_layout' )) {
			$ret = realpath( THEMES_PKG_PATH."layouts/".$gBitSystem->getConfig( 'site_style_layout' ).".css" );
		}
		return !empty( $ret ) ? $ret : '';
	}

	/**
	 * figure out the current style URL
	 *
	 * @param string $pStyle file to be looked for
	 * @return string 
	 */
	public function getStyleUrl( string $pStyle = '' ) {
		if( empty( $pStyle )) {
			$pStyle = $this->getStyle();
		}
		return CONFIG_PKG_URL.'themes/'.$pStyle.'/';
	}

	/**
	 * figure out the current style URL
	 *
	 * @param string $pStyle file to be looked for
	 * @return string
	 */
	public function getStylePath( string $pStyle = '' ) {
		if( empty( $pStyle )) {
			$pStyle = $this->getStyle();
		}
		return CONFIG_PKG_PATH.'themes/'.$pStyle.'/';
	}

	/**
	 * getStyles
	 *
	 * @param string $pDir
	 * @param bool $pNullOption
	 * @param array $bIncludeCustom
	 * @return array List of installed themes
	 */
	public function getStyles( string $pDir = '', bool $pNullOption = true, bool $bIncludeCustom = false ): array {
		global $gBitSystem, $gBitUser;

		if( empty( $pDir )) {
			$pDir = CONFIG_PKG_PATH.'themes/';
		}
		$ret = [];

		if( !empty( $pNullOption )) {
			$ret[] = '';
		}

		if( is_dir( $pDir )) {
			$h = opendir( $pDir );
			while( $file = readdir( $h )) {
				if ( is_dir( $pDir."$file" ) && ( $file != '.' && $file != '..' && $file != 'CVS' && $file != 'slideshows' && $file != 'blank' )) {
					$ret[] = $file;
				}
			}
			closedir( $h );
		}

		if( count( $ret )) {
			sort( $ret );
		}

		return $ret;
	}

	/**
	 * getStyleLayouts
	 *
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function getStyleLayouts(): array {
		$ret = [];

		if( is_dir( THEMES_PKG_PATH.'layouts/' )) {
			$h = opendir( THEMES_PKG_PATH.'layouts/' );
			// collect all layouts
			while( false !== ( $file = readdir( $h ))) {
				if ( !preg_match( "/^\./", $file )) {
					$ret[substr( $file, 0, strrpos( $file, '.' ))][substr( $file, 0 )] = $file;
				}
			}
			closedir( $h );

			// weed out any files that don't have a css file associated with them
			foreach( $ret as $key => $layout ) {
				if( empty( $layout['css'] )) {
					unset( $ret[$key] );
				}
			}

			ksort( $ret );
		}
		return $ret;
	}

	/**
	 * @param $pSubDirs a subdirectory to scan as well - you can pass in multiple dirs using an array
	 *
	 * @param string $pDir
	 * @param bool $pNullOption
	 * @param string|array|null $pSubDirs
	 * @return array true on success, false on failure - mErrors will contain reason for failure
	 */
	public function getStylesList( string $pDir = '', bool $pNullOption = true, string|array|null $pSubDirs = null ): array {
		global $gBitSystem;

		$ret = [];

		if( empty( $pSubDirs )) {
			$subDirs[] = [ '' ];
		} elseif( !\is_array( $pSubDirs )) {
			$subDirs[] = $pSubDirs;
		} else {
			$subDirs = $pSubDirs;
		}

		if( empty( $pDir )) {
			$pDir = CONFIG_PKG_PATH.'themes/';
		}

		if( $pNullOption ) {
			$ret[] = '';
		}

		// open directories
		if( is_dir( $pDir )) {
			$h = opendir( $pDir );
			// cycle through files / dirs
			while( false !== ( $file = readdir( $h ))) {
				if ( is_dir( $pDir.$file ) && ( $file != '.' && $file != '..' && $file != 'CVS' && $file != 'slideshows' && $file != 'blank' )) {
					$ret[$file]['style'] = $file;
					// check if we want to have a look in any subdirs
					foreach( $subDirs as $dir ) {
						if( is_dir( $infoDir = $pDir.$file.'/'.$dir.'/' )) {
							$dh = opendir( $infoDir );
							// cycle through files / dirs
							while( false !== ( $f = readdir( $dh ))) {
								if( is_readable( $infoDir.$f ) && ( $f != '.' &&  $f != '..' &&  $f != 'CVS' )) {
									$ret[$file][$dir][preg_replace( "/\..*/", "", $f )] = CONFIG_PKG_URL.basename( dirname( dirname( $infoDir ))).'/'.$file.'/'.$dir.'/'.$f;

									if( preg_match( "/\.htm$/", $f )) {
										$fh = fopen( $infoDir.$f, "r" );
										$ret[$file][$dir][preg_replace( "/\.htm$/", "", $f )] = fread( $fh, filesize( $infoDir.$f ));
										fclose( $fh );
									}
								}
							}
							// sort the returned items
							@ksort( $ret[$file][$dir] );
							closedir( $dh );
						}
					}
				}
			}
			closedir( $h );
		}

		if( count( $ret )) {
			ksort( $ret );
		}

		return $ret;
	}

	/**
	 * get the icon cache path
	 *
	 * @return string absolute path on where the system should store it's icons
	 */
	public function getIconCachePath(): string {
		global $gSniffer, $gBitSystem, $gBitLanguage;

		// use bitweaver version as dir in case there has been changes since the last version
		$version = $gBitSystem->getBitVersion( false );

		$cachedir = TEMP_PKG_PATH.'themes/biticon/'.$version.'/'.$gBitSystem->getConfig( 'site_icon_style', DEFAULT_ICON_STYLE ).'/'.$gBitLanguage->getLanguage().'/default/';
		if( !is_dir( $cachedir )) {
			KernelTools::mkdir_p( $cachedir );
		}
		return $cachedir;
	}

	// }}}
	// {{{ =================== Layout ====================
	/**
	 * load current layout into mLayout
	 *
	 * @param  $pParamHash
	 * @return void
	 */
	public function loadLayout( $pParamHash = null ): void {
		global $gBitSystem;
		if( !empty( $pParamHash ) || empty( $this->mLayout ) || !count( $this->mLayout )) {
			$this->mLayout = $this->getLayout( $pParamHash );

			/**
			 * this needs to occur after loading the layout to ensure that we don't distrub the fallback process during layout loading
			 * we can disable clumns using various criteria:
			 *     <package>_hide_<area>_col
			 *     <display_mode>_hide_<area>_col
			 *     <package>_<display_mode>_hide_<area>_col
			 */
			$areas = [ 't' => 'top', 'l' => 'left', 'r' => 'right', 'b' => 'bottom' ];
			foreach( $areas as $layout => $area ) {
				if(
					$gBitSystem->isFeatureActive( "{$this->mDisplayMode}_hide_{$area}_col" ) ||
					$gBitSystem->isFeatureActive( "{$gBitSystem->getActivePackage()}_hide_{$area}_col" ) ||
					$gBitSystem->isFeatureActive( "{$gBitSystem->getActivePackage()}_{$this->mDisplayMode}_hide_{$area}_col" )
				) {
					unset( $this->mLayout[$layout] );
				}
			}
		}
	}

	public function hasColumnModules( string $pColumn ): bool {
		return !empty( $this->mLayout[$pColumn] );
	}

	public function displayLayoutColumn( string $pColumn ): void {
		if( $colHtml = $this->fetchLayoutColumn( $pColumn ) ) {
			print $colHtml;
		}
	}

	public function fetchLayoutColumn( string $pColumn ): string {
		global $gBitSmarty, $gBitSystem;
		$ret = '';
// vd($this->mLayout);
		if( !empty( $this->mLayout[$pColumn] ) ) {
			for ($i = 0; $i < count( $this->mLayout[$pColumn] ); $i++) {
				$r = &$this->mLayout[$pColumn][$i];
				if( !empty( $r['visible'] )) {
					try {
						// @TODO MODULE UPGRADE under new module organization this is not reliable as tpls are in sub dir in modules/ change this when upgrade is complete
						list( $package, $template ) = explode(  '/', $r['module_rsrc'] );
						// deal with custom modules
						if( $package == '_custom:custom' ) {
							global $gBitLanguage;

							// We're gonna run our own cache mechanism for user_modules
							// the cache is here to avoid calls to consumming queries,
							// each module is different for each language because of the strings
							$cacheDir = TEMP_PKG_PATH.'modules/cache/';
							if( !is_dir( $cacheDir )) {
								KernelTools::mkdir_p( $cacheDir );
							}
							$cachefile = $cacheDir.'_custom.'.$gBitLanguage->mLanguage.'.'.$template.'.tpl.cache';

							if( !empty( $r["cache_time"] ) && file_exists( $cachefile ) && !(( $gBitSystem->getUTCTime() - filemtime( $cachefile )) > $r["cache_time"] )) {
								$fp = fopen( $cachefile, "r" );
								$data = fread( $fp, filesize( $cachefile ));
								fclose( $fp );
								$r["data"] = $data;
							} else {
								if( $moduleParams = $this->getCustomModule( $template )) {
									$moduleParams = [ ...$r, ...$moduleParams ];
									$gBitSmarty->assign( 'moduleParams', $moduleParams );
									$ret .= $gBitSmarty->fetch( 'bitpackage:themes/custom_module.tpl' );

									if( !empty( $r["cache_time"] ) ) {
										// write to chache file
										$fp = fopen( $cachefile, "w+" );
										fwrite( $fp, $data, strlen( $data ));
										fclose( $fp );
									}
									$r["data"] = $data;
								}
							}
							unset( $data );
						} else {
							$explosion = explode( '/', $r['module_rsrc'] );
							$template = \array_pop( $explosion );

							// using $module_rows, $module_params and $module_title is deprecated. please use $moduleParams hash instead
							global $module_rows, $module_params, $module_title, $gBitLanguage;

							$cacheDir = TEMP_PKG_PATH.'modules/cache/';
							if( !is_dir( $cacheDir )) {
								KernelTools::mkdir_p( $cacheDir );
							}

							// include tpl name and module id to uniquely identify
							$cachefile = $cacheDir.'_module_'.$r['module_id'].'.'.$gBitLanguage->mLanguage.'.'.$template.'.cache';
							// if the time is right get the cache else get it fresh
							if( !empty( $r["cache_time"] ) && file_exists( $cachefile ) && filesize( $cachefile ) && !(( $gBitSystem->getUTCTime() - filemtime( $cachefile )) > $r["cache_time"] ) ) {
								$fp = fopen( $cachefile, "r" );
								$data = fread( $fp, filesize( $cachefile ));
								fclose( $fp );
								$r["data"] = $data;
							} else {
								$module_params = $r['module_params']; // backwards compatability

								if( !$r['module_rows'] ) {
									$r['module_rows'] = 10;
								}

								// if there's no custom title, get one from file name
								if( !$r['title'] = isset( $r['title'] ) ? KernelTools::tra( $r['title'] ) : null ) {
									$pattern[0] = "/.*\/mod_(.*)\.tpl/";
									$replace[0] = "$1";
									$pattern[1] = "/_/";
									$replace[1] = " ";
									$r['title'] = !empty( $r['title'] ) ? KernelTools::tra( $r['title'] ) : KernelTools::tra( ucwords( preg_replace( $pattern, $replace, $r['module_rsrc'] )));
								}

								// moduleParams are extracted in BitSmarty::getSiblingAttachments() and passed on the the module php file
								$moduleParams = $r;
								//$gBitSmarty->assign( 'moduleParams', $moduleParams );
								$gBitSmarty->assign( 'moduleParams', $moduleParams );
								$gBitSmarty->assign( 'moduleTitle', $moduleParams['title'] );
								// assign the custom module title
								$ret .= $gBitSmarty->fetch( $r['module_rsrc'] );

								if( !empty( $r["cache_time"] ) && !empty( $data ) ) {
									// write to chache file
									$fp = fopen( $cachefile, "w+" );
									fwrite( $fp, $data, strlen( $data ));
									fclose( $fp );
								$r["data"] = $data;
								}
							}
							unset( $moduleParams );
						}
					} catch( \Exception $e ) {
						print( '<div class="alert alert-warning">'.$e->getMessage() ).'</div>';
					}
				}
			}
			if( !empty( $ret ) && ($pColumn == 'l' || $pColumn == 'r') ) {
				$ret = '<div class="panel-group col-xs-12">'.$ret.'</div>';
			}
		}
		return $ret;
	}

	/**
	 * get the current layout from the database, layouts are fetched in this order in this order until one is successfully loaded: 'layout', 'fallback_layout', ACTIVE_PACKGE, DEFAULT_PACKAGE"
	 *
	 * @param array $pParamHash
	 * @return array true on success, false on failure - mErrors will contain reason for failure
	 */
	public function getLayout( ?array $pParamHash = null ): array {
		global $gCenterPieces, $gBitUser, $gBitSystem, $gBitSmarty;
		$ret = [ 'l' => null, 'c' => null, 'r' => null ];

		$layouts =  [];
		if( !empty( $pParamHash['layout'] )) {
			$layouts[] = $pParamHash['layout'];
		}
		if( !empty( $pParamHash['fallback_layout'] )) {
			$layouts[] = $pParamHash['fallback_layout'];
		}
		$layouts[] = $gBitSystem->getActivePackage();
		$layouts[] = DEFAULT_PACKAGE;

		foreach( $layouts AS $l ) {
			$query =   "SELECT tl.*
						FROM `".BIT_DB_PREFIX."themes_layouts` tl
						WHERE  tl.`layout`=? ORDER BY ".$this->mDb->convertSortmode( "pos_asc" );

			$result = $this->mDb->query( $query, [ $l ] );
			if( $result && $result->RecordCount() ) {
				break;
			}
		}
		if( !empty( $result ) && $result->RecordCount() ) {
			$row = $result->fetchRow();
			// Check to see if we have active package modules at the top of the results
			$skipDefaults = isset( $row['layout'] ) && ( $row['layout'] != DEFAULT_PACKAGE ) && ( $gBitSystem->getActivePackage() != DEFAULT_PACKAGE )
				? true : false;

			if ( !\is_array( $gCenterPieces ) ){
				$gCenterPieces = [];
			}
			while( $row ) {
				if( $skipDefaults && $row['layout'] == DEFAULT_PACKAGE ) {
					// we're done! we've got all the non-DEFAULT_PACKAGE modules
					break;
				}

				if( empty( $row["roles"] )) {
					$row["visible"] = true;
					$row["module_roles"] = [];
				} else {
					$row['module_roles'] = $this->parseRoles( $row['roles'] );
					if( $gBitUser->isAdmin() ) {
						if ( $gBitSystem->isFeatureActive('site_mods_req_admn_grp') ) {
							if( \in_array(1, $row['module_roles']) ) {
								$row['visible'] = true;
							}
						} else {
							$row["visible"] = true;
						}
					} else {
						foreach( $row["module_roles"] as $modRoleId ) {
							if( $gBitUser->isInRole( $modRoleId )) {
								$row["visible"] = true;
								break;
							}
						}
					}
				}

				if( empty( $ret[$row['layout_area']] )) {
					$ret[$row['layout_area']] = [];
				}

				$row['module_params'] = !empty($row['params']) ? $this->parseString( $row['params']) : null;

				if( !empty( $pParamHash['load_config'] ) ) {
					global $moduleParams;
					$row['config'] = $gBitSmarty->getModuleConfig( $row['module_rsrc'] );
				}

				if( $row['layout_area'] == CENTER_COLUMN ) {
					array_push( $gCenterPieces, $row );
				}

				if( !empty( $row["visible"] )) {
					array_push( $ret[$row['layout_area']], $row );
				}

				$row = $result->fetchRow();
			}
		}

		return $ret;
	}

	/**
	 * isModuleLoaded will check if a given modules is being used in the currently active layout
	 *
	 * @param string $pModuleResource the module resource
	 * @param string $pArea optionally specify the area the module should be found in
	 * @access public
	 * @return bool true on success, false on failure
	 */
	public function isModuleLoaded( $pModuleResource, $pArea = null ) {
		// load the layout if it hasn't been done yet
		$this->loadLayout();

		if( !$this->verifyArea( $pArea ) && !empty( $this->mLayout[$pArea] )) {
			foreach( $this->mLayout[$pArea] as $module ) {
				if( $pModuleResource == $module['module_rsrc'] ) {
					return true;
				}
			}
		} else {
			foreach( \array_keys( $this->mLayout ) as $area ) {
				if( !empty( $this->mLayout[$area] )) {
					foreach( $this->mLayout[$area] as $module ) {
						if( $pModuleResource == $module['module_rsrc'] ) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * fix postional data in database using increments of 10 to make it easy for inserting new modules
	 *
	 * @param string 
	 * @return void
	 */
	public function fixPositions( string $pLayout = ''): void {
		$layouts = $this->getAllLayouts();

		// if we only want to fix the positions of a given layout, strip down the hash
		if( !empty( $pLayout ) && !empty( $layouts[$pLayout] )) {
			$layouts = [$layouts[$pLayout]];
		}

		foreach( $layouts as $layout ) {
			foreach( $layout as $column ) {
				$i = 5;
				foreach( $column as $module ) {
					$this->mDb->query( "UPDATE `".BIT_DB_PREFIX."themes_layouts` SET pos=? WHERE `module_id`=?", [ $i, $module['module_id'] ]);
					$i += 5;
				}
			}
		}
	}

	/**
	 * get a brief summary of set layouts
	 *
	 * @return array true on success, false on failure - mErrors will contain reason for failure
	 */
	public function getAllLayouts() {
		$layouts = [];
		$modules = $this->mDb->getAll( "SELECT tl.* FROM `".BIT_DB_PREFIX."themes_layouts` tl ORDER BY ".$this->mDb->convertSortmode( "pos_asc" ));
		foreach( $modules as $module ) {
			$module['module_roles'] = $this->parseRoles( $module['roles'] ?? '' );
			$layouts[$module['layout']][$module['layout_area']][] = $module;
		}
		ksort( $layouts );
		// Take the default/kernel layout and make sure it is the first item in hash
		if( ( count( $layouts ) > 1 ) && isset( $layouts['kernel'] ) ) {
			$kernel_layout = $layouts['kernel'];
			unset( $layouts['kernel'] );
			$layouts = [ 'kernel' => $kernel_layout ] + $layouts;
		}
		return $layouts;
	}

	/**
	 * cloneLayout
	 *
	 * @param string $pFromLayout
	 * @param string $pToLayout
	 * @return void
	 */
	public function cloneLayout( string $pFromLayout, string $pToLayout ): void {
		global $gBitSystem;
		if( !empty( $pFromLayout ) && !empty( $pToLayout ) ) {
			// nuke existing layout
			$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."themes_layouts` WHERE `layout`=?", [$pToLayout]);
			// get requested layout
			$layout = $this->mDb->getAll( "
				SELECT `title`, `layout_area`, `module_rows`, `module_rsrc`, `params`, `cache_time`, `roles`, `pos`
				FROM `".BIT_DB_PREFIX."themes_layouts` WHERE `layout`=?", [$pFromLayout] );
			foreach( $layout as $module ) {
				$module['layout'] = $pToLayout;
				$this->storeModule( $module );
			}
		}
	}

	/**
	 * expungeLayout
	 *
	 * @param string $pLayout
	 * @return void
	 */
	public function expungeLayout( string $pLayout = ''): void {
		$bindVars = [];
		if( !empty( $pLayout )) {
			$whereSql = "WHERE `layout`=?";
			$bindVars[] = $pLayout;
		}
		$this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."themes_layouts` $whereSql", $bindVars );
	}

	/**
	 * transform roles string to handy array
	 *
	 * @param string $pParseString either space separated list of roles or serialised array
	 * @return array of roles
	 */
	public function parseRoles( string $pParseString ): array {
		$ret = [];
		// convert role string to hash
		if( !empty($pParseString) && preg_match( '/[A-Za-z]/', $pParseString )) {
			// old style serialized role names
			if( $grps = @unserialize( $pParseString )) {
				foreach( $grps as $grp ) {
					global $gBitUser;
					if( !( $roleId = \array_search( $grp, $gBitUser->mRoles ))) {
						if( $gBitUser->isAdmin() ) {
							$ret[] = $gBitUser->roleExists( $grp, 0 );
						}
					}

					if( @$this->verifyId( $roleId )) {
						$ret[] = $roleId;
					}
				}
			}
		} else {
			// new imploded style
			$ret = explode( ' ', $pParseString ?? '' );
		}
		return $ret;
	}

	// }}}
	// {{{ =================== Modules ====================
	/**
	 * Verfiy module parameters when storing a new module
	 *
	 * @param array $pHash
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function verifyModuleParams( array &$pHash ): bool {
		// we need at least a module_id or a module_rsrc
		if( empty( $pHash['module_id'] ) && empty( $pHash['module_rsrc'] )) {
			$this->mErrors['module_rsrc'] = KernelTools::tra( 'No module id or module file given.' );
		} elseif( !empty( $pHash['module_id'] )) {
			$pHash['store']['module_id'] = $pHash['module_id'];
		} elseif( !empty( $pHash['module_rsrc'] )) {
			$pHash['store']['module_rsrc'] = $pHash['module_rsrc'];
		}

		// if we don't have a valid area, we'll just shove it in the left column
		$pHash['store']['layout_area'] = !empty($pHash['layout_area']) ? $this->verifyArea( $pHash['layout_area'] ) : 'l';

		$pHash['store']['title']         = !empty( $pHash['title'] )             ? $pHash['title']         : null;
		$pHash['store']['params']        = !empty( $pHash['params'] )            ? $pHash['params']        : null;
		$pHash['store']['layout']        = !empty( $pHash['layout'] )            ? $pHash['layout']        : DEFAULT_PACKAGE;
		$pHash['store']['module_rows']   = !empty($pHash['module_rows']) && is_numeric( $pHash['module_rows'] )  ? $pHash['module_rows']   : null;
		$pHash['store']['cache_time']    = !empty($pHash['cache_time']) && is_numeric( $pHash['cache_time'] )   ? $pHash['cache_time']    : null;
		$pHash['store']['pos']           = !empty($pHash['pos']) && is_numeric( $pHash['pos'] )          ? $pHash['pos']           : 1;

		if( !empty( $pHash['roles'] ) && \is_array( $pHash['roles'] )) {
			$pHash['store']['roles'] = implode( ' ', $pHash['roles'] );
		} else {
			$pHash['store']['roles'] = null;
		}

		if( !empty( $pHash['config'] ) ) {
			$pHash['store']['params'] = '';
			foreach( $pHash['config'] as $paramName=>$paramValue ) {
				$pHash['store']['params'] .= "$paramName=".urlencode( $paramValue ).'&';
			}
		} else {
			$pHash['store']['params']        = !empty( $pHash['params'] )            ? $pHash['params']        : null;
		}

		return count( $this->mErrors ) == 0;
	}

	/**
	 * storeModule
	 *
	 * @param array $pHash
	 * @access public
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function storeModule( &$pHash ) {
		if( $this->verifyModuleParams( $pHash )) {
			$table = BIT_DB_PREFIX."themes_layouts";

			if( \Bitweaver\BitBase::verifyId( $pHash['store']['module_id'] ?? 0 )) {
				// if we've been passed a module_id, we are updating an entry in the DB
				$result = $this->mDb->associateUpdate( $table, $pHash['store'], [ 'module_id' => $pHash['store']['module_id'] ]);
			} else {
				// no module_id yet - let's get one
				$pHash['store']['module_id'] = $this->mDb->GenID( 'themes_layouts_module_id_seq' );
				$result = $this->mDb->associateInsert( $table, $pHash['store'] );
			}
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * getModuleData
	 *
	 * @param mixed $pModuleId
	 * @return array module details of the requested module id
	 */
	public function getModuleData( mixed $pModuleId ): array {
		$ret = [];
		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			$ret = $this->mDb->getRow( "SELECT tl.* FROM `".BIT_DB_PREFIX."themes_layouts` tl WHERE `module_id`=? ", [ $pModuleId ]);
			$ret['module_params'] = !empty($ret['params']) ? $this->parseString( $ret['params']) : null;
		}
		return $ret;
	}

	/**
	 * moduleUp
	 *
	 * @param mixed $pModuleId
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function moveModuleUp( mixed $pModuleId ): bool {
		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			$this->moveModule( $pModuleId, 'up' );
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * moduleDown
	 *
	 * @param mixed $pModuleId
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function moveModuleDown( mixed $pModuleId ): bool {
		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			$this->moveModule( $pModuleId, 'down' );
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * generic function to move module up or down
	 *
	 * @param mixed $pModuleId
	 * @param string $pDirection
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function moveModule( mixed $pModuleId, string $pDirection = 'down' ): bool {
		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			// first we get next module we want to swap with
			$moduleData = $this->getModuleData( $pModuleId );
			if( $pDirection == 'up' ) {
				$pos_check = 'AND `pos`<=?';
				$pos_set   = 'SET `pos`=`pos`-1';
				$order     = 'ORDER BY pos DESC';
			} else {
				$pos_check = 'AND `pos`>=?';
				$pos_set   = 'SET `pos`=`pos`+1';
				$order     = 'ORDER BY pos ASC';
			}
			$query  = "SELECT `module_id` FROM `".BIT_DB_PREFIX."themes_layouts` WHERE `layout`=? AND `layout_area`=? $pos_check AND `module_id` <> ? $order";
			$swapModuleId = $this->mDb->getOne( $query, [ $moduleData['layout'], $moduleData['layout_area'], $moduleData['pos'], $moduleData['module_id'] ]);
			if( $moduleSwap = $this->getModuleData( $swapModuleId )) {
				if( $moduleData['pos'] == $moduleSwap['pos'] ) {
					$query = "UPDATE `".BIT_DB_PREFIX."themes_layouts` $pos_set WHERE `module_id`=?";
					$result = $this->mDb->query( $query, [ $moduleData['module_id'] ]);
				} else {
					$query = "UPDATE `".BIT_DB_PREFIX."themes_layouts` SET `pos`=? WHERE `module_id`=?";
					$result = $this->mDb->query( $query, [ $moduleSwap['pos'], $moduleData['module_id'] ]);
					$result = $this->mDb->query( $query, [ $moduleData['pos'], $moduleSwap['module_id'] ]);
				}
			}
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * setModulePosition
	 *
	 * @param mixed $pModuleId
	 * @param array $pPos
	 * @param array $pCol
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function setModulePosition( mixed $pModuleId, array $pPos, ?array $pCol = null ): bool {
		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			$bindVars = [];
			$updateSql = '';
			if( !empty( $pCol ) ) {
				$updateSql .= ' `layout_area`=?, ';
				$bindVars[] = $pCol;
			}
			$bindVars[] = $pPos;
			$bindVars[] = $pModuleId;
			$query = "UPDATE `".BIT_DB_PREFIX."themes_layouts` SET ".$updateSql." `pos`=? WHERE `module_id`=?";
			$result = $this->mDb->query( $query, $bindVars );
		}
		return true;
	}

	/**
	 * moveModuleToArea
	 *
	 * @param mixed $pModuleId
	 * @param array $pArea
	 * @access public
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function moveModuleToArea( mixed $pModuleId, $pArea ) {
		if( !$this->verifyArea( $pArea )) {
			$pArea = 'l';
		}

		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			$query = "UPDATE `".BIT_DB_PREFIX."themes_layouts` SET `layout_area`=? WHERE `module_id`=?";
			$result = $this->mDb->query( $query, [ $pArea, $pModuleId ]);
		}
		return true;
	}

	/**
	 * unassignModule
	 *
	 * @param string $pModuleMixed can be a module id or a resource path. if it is a resource path, all modules with that resource will be removed
	 * @access public
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function unassignModule( $pModuleMixed ) {
		$ret = false;
		if( \Bitweaver\BitBase::verifyId( $pModuleMixed )) {
			if( $this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."themes_layouts` WHERE `module_id`=?", [ $pModuleMixed ])) {
				$ret = true;
			}
		} elseif( !empty( $pModuleMixed )) {
			if( $this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."themes_layouts` WHERE `module_rsrc`=?", [ $pModuleMixed ])) {
				$ret = true;
			}
		}
		return $ret;
	}

	/**
	 * if the specified area doesn't make any sense, we just dump it in the left column
	 *
	 * @param string $pArea l --> left       r --> right       c --> center       b --> bottom       t --> top
	 * @return string with valid area
	 */
	public function verifyArea( string &$pArea ): string {
		return !empty( $pArea ) && preg_match( '/^[lrctb]$/', $pArea ) ? $pArea : 'l';
	}

	/**
	 * generates module names on full hash by reference
	 *
	 * @param array $p2DHash layout hash
	 * @return void
	 */
	public function generateModuleNames( array &$p2DHash ): void {
		if( \is_array( $p2DHash )) {
			// Generate human friendly names
			foreach( \array_keys( $p2DHash ) as $col ) {
				if( \is_array( $p2DHash[$col] ) && count( $p2DHash[$col] )) {
					foreach( \array_keys( $p2DHash["$col"] ) as $mod ) {
						[ $rsrc, $specifier ] = \explode( ':', $p2DHash[$col][$mod]['module_rsrc'], 2 );
						$specelems = \explode( '/', $specifier );
						$package = current( $specelems );
						if( $package == 'temp' ) $package = next( $specelems );
						// handle special case for custom modules
						if( !isset( $package )) {
							$package = $rsrc;
						}
						$file = end( $specelems );
						$file = str_replace( 'mod_', '', $file );
						$file = str_replace( '.tpl', '', $file );
						$p2DHash[$col][$mod]['name'] = $package.' &raquo; '.str_replace( '_', ' ', $file );
					}
				}
			}
		}
	}

	/**
	 * getAllModules
	 *
	 * @param string $pDir
	 * @param string $pPrefix
	 * @return array
	 */
	public function getAllModules( $pDir='modules', $pPrefix='mod_' ) {
		global $gBitSystem;
		// @TODO MODULE UPGRADE
		// hash for carrying references to modules:
		// $this->mModules[$pDir][$pPrefix]
		// this is ugly but is to smooth the transition until all modules are upgraded to directory and registration structure
		// it will be unncessary once all packages are caught up

		if(( $modules = $this->getCustomModuleList() ) && $pPrefix == 'mod_' ) {
			foreach( $modules as $m ) {
				$this->mModules[$pDir][$pPrefix][KernelTools::tra( 'Custom Modules' )]['_custom:custom/'.$m["name"]] = [ 'title' => $m["name"] ];
			}
			asort( $this->mModules[$pDir][$pPrefix][KernelTools::tra( 'Custom Modules' )] );
		}

		// iterate through all packages and look for all possible modules
		foreach( \array_keys( $gBitSystem->mPackages ) as $key ) {
			if( $gBitSystem->isPackageActive( $key )) {
				$loc = BIT_ROOT_PATH.$gBitSystem->mPackages[$key]['dir'].'/'.$pDir;
				if( @is_dir( $loc )) {
					$h = opendir( $loc );
					if( $h ) {
						while (($file = readdir($h)) !== false) {
							// match on legacy module files which require a prefix
							if ( preg_match( "/^$pPrefix(.*)\.tpl$/", $file, $match )) {
								$this->mModules[$pDir][$pPrefix][ucfirst( $key )]["bitpackage:$key/$file"] = [
									'title'    => str_replace( '_', ' ', $match[1] ),
									'template' => $file,
								];
							}
							// loop over nested directories which contain modern modules
							// these modules are only accessible from gBitThemes
							elseif ( !\in_array( $file, ['.','..','CVS'] ) && @is_dir( $loc.'/'.$file ) ){
								$conf_file = $loc.'/'.$file.'/config_inc.php';
								// we expect a configuration file
								if( @is_file( $conf_file ) ){
									require_once $conf_file;
								}
							}
						}
						closedir ($h);
						if( !empty( $this->mModules[$pDir][$pPrefix][ucfirst( $key )] ) ) {
							asort( $this->mModules[$pDir][$pPrefix][ucfirst( $key )] );
						}
					}
				}
				// we scan temp/<pkg>/modules for module files as well for on the fly generated modules (e.g. nexus)
				if( $pDir == 'modules' ) {
					$loc = TEMP_PKG_PATH.$gBitSystem->mPackages[$key]['name'].'/'.$pDir;
					if( @is_dir( $loc )) {
						$h = opendir( $loc );
						if( $h ) {
							while (($file = readdir($h)) !== false) {
								if ( preg_match( "/^$pPrefix(.*)\.tpl$/", $file, $match )) {
									$this->mModules[$pDir][$pPrefix][ucfirst( $key )]["bitpackage:temp/$key/$file"] = [
										'title'    => str_replace( '_', ' ', $match[1] ),
										'template' => $file,
									];
								}
							}
							closedir ($h);
							if( !empty( $this->mModules[$pDir][$pPrefix][ucfirst($key)] ) ) {
								asort( $this->mModules[$pDir][$pPrefix][ucfirst($key)] );
							}
						}
					}
				}
			}
		}
		return $this->mModules[$pDir][$pPrefix];
	}

	public function registerModule( array $pMixed ): void{
		$pkg = $pMixed['package'];
		$dir = $pMixed['directory'];
		$tpl = $pMixed['template'];
		$legacy_dir = $pMixed['legacy_dir'];
		$legacy_prefix = $pMixed['legacy_prefix'];
		$this->mModules[$legacy_dir][$legacy_prefix][ucfirst( $pkg )]['bitpackage:'.$pkg.'/'.$dir.'/'.$tpl] = $pMixed;
	}

	// utility function for other packages when they upgrade their modules to the new module system
	// see themes/admin/upgrades/3.0.0.php for an example of usages
	public function upgradeModulesPaths(){
		$this->getAllModules();
		$legacy_mods = [];
		$upgrade_mods = [];

		foreach( $this->mModules['modules']['mod_'] as $pkg => $modules ){
			foreach( $modules as $modulepath => $module ){
				$parts =  explode( "/", $modulepath );
				if( count( $parts ) > 2 ){
					$upgrade_mods[array_pop( $parts )] = $modulepath;
				}
			}
		}

		$sql1 = "SELECT DISTINCT `module_rsrc` FROM `".BIT_DB_PREFIX."themes_layouts`";
		$legacy_mods = $this->mDb->getArray( $sql1 );

		// fix everything
		// transaction will save us if something goes bad
		$this->StartTrans();

		foreach( $legacy_mods as $old ){
			$key =  \array_pop( explode( "/", $old['module_rsrc'] ) );
			if( \in_array( $key, \array_keys($upgrade_mods) ) && $old['module_rsrc'] != $upgrade_mods[$key]){
				$storeHash = [ 'module_rsrc' => $upgrade_mods[$key] ];
				$this->mDb->associateUpdate( BIT_DB_PREFIX."themes_layouts", $storeHash, [ 'module_rsrc' => $old['module_rsrc'] ]);
			}
		}

		$this->CompleteTrans();
	}

	/**
	 * get a module-specfic parameters
	 *
	 * @param array $pModuleId
	 * @access public
	 * @return array or parameters
	 */
	public function getModuleParameters( $pModuleId ) {
		$ret = [];
		if( \Bitweaver\BitBase::verifyId( $pModuleId )) {
			$module = $this->getModuleData( $pModuleId );
			$ret = $module['module_params'];
		} else {
			KernelTools::deprecated( 'Please use the module parameters found in vd( $moduleParams[\'module_params\'] ); or pass in the module id for a database lookup.' );
		}
		return $ret;
	}

	/**
	 * parse URL-like parameter string
	 *
	 * @param string $pParseString
	 * @access public
	 * @return array or parameters
	 */
	public function parseString( string $pParseString ): array {
		$ret = [];
		if( !empty( $pParseString )) {
			// only call crazy regex when params are too complex for parse_str()
			if( strpos( trim( $pParseString ), ' ' )) {
				$ret =  KernelTools::parse_xml_attributes( $pParseString );
			} else {
				parse_str( $pParseString, $ret );
			}
		}
		return $ret;
	}

	// }}}
	// {{{ =================== Custom Modules ====================
	/**
	 * verifyCustomModule
	 *
	 * @param array $pParamHash
	 * @access public
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function verifyCustomModule( &$pParamHash ) {
		if( !empty( $pParamHash['name'] ) && preg_match( "/[a-zA-Z]/", $pParamHash['name'] )) {
			$pParamHash['store']['name'] = substr( strtolower( preg_replace( "/[^\w]*/", "", $pParamHash['name'] )), 0, 40 );
		}

		if( empty( $pParamHash['store']['name'] )) {
			$this->mErrors[] = KernelTools::tra( 'You need to provide a name for your custom module. Only alphanumeric characters are allowed and you need to use at least one letter.' );
		}

		if( !empty( $pParamHash['title'] )) {
			$pParamHash['store']['title'] = substr( $pParamHash['title'], 0, 200 );
		}

		if( !empty( $pParamHash['data'] )) {
			$pParamHash['store']['data'] = $pParamHash['data'];
		}

		return count( $this->mErrors ) == 0;
	}

	/**
	 * storeCustomModule
	 *
	 * @param array $pParamHash
	 * @access public
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function storeCustomModule( $pParamHash ) {
		if( $this->verifyCustomModule( $pParamHash )) {
			$table = "`".BIT_DB_PREFIX."themes_custom_modules`";
			$result = $this->mDb->query( "DELETE FROM $table WHERE `name`=?", [ $pParamHash['store']['name'] ]);
			$result = $this->mDb->associateInsert( $table, $pParamHash['store'] );
		}
		return count( $this->mErrors ) == 0;
	}

	/**
	 * getCustomModule
	 *
	 * @param string $pName
	 * @return array true on success, false on failure - mErrors will contain reason for failure
	 */
	public function getCustomModule( string $pName ): array {
		if( !empty( $pName )) {
			return $this->mDb->getRow( "SELECT * FROM `".BIT_DB_PREFIX."themes_custom_modules` WHERE `name`=?", [$pName] );
		}
			return [];

	}

	/**
	 * getCustomModuleList
	 *
	 * @return array
	 */
	public function getCustomModuleList(): array {
		return $this->mDb->getAll( "SELECT * FROM `".BIT_DB_PREFIX."themes_custom_modules`" );
	}

	/**
	 * expungeCustomModule
	 *
	 * @param string $pName
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function expungeCustomModule( $pName ): bool {
		if( !empty( $pName )) {
			$this->unassignModule( '_custom:custom/'.$pName );
			$result = $this->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."themes_custom_modules` WHERE `name`=?", [ $pName ]);
		}
		return true;
	}

	/**
	 * isCustomModule
	 *
	 * @param string $pMixed either name of module or the rsrc of a module
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function isCustomModule( string $pMixed ): bool {
		if( strpos( $pMixed, "_custom:custom" ) !== false ) {
			return true;
		} elseif( strpos( $pMixed, "bitpackage:" ) !== false ) {
			return false;
		}
			$result = $this->mDb->getOne( "SELECT `name` FROM `".BIT_DB_PREFIX."themes_custom_modules` WHERE `name`=?", [ $pMixed ]);
			return !empty( $result );

	}

	// }}}
	// {{{ =================== Javascript and CSS related Methods ====================
	/**
	 * Statically callable function to see if browser supports javascript
	 * determined by cookie set in bitweaver.js
	 */
	public function isJavascriptEnabled() {
	//	return( !empty( $_COOKIE['javascript_enabled'] ) && $_COOKIE['javascript_enabled'] == 'y' );
		return true; // This function is fuckt as cookie is empty for first query. And cookie privacy browsers are perfjects JS enabled
	}

	/**
	 * Statically callable function to determine if the current call was made using Ajax
	 *
	 */
	public function isAjaxRequest() {
		return !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || !empty( $_REQUEST['ajax_xml'] );
	}

	// {{{ Javascript and CSS load methods
	/**
	 * Load Ajax libraries
	 *
	 * @param string $pAjaxLib Name of the library we want to use e.g.: prototype or mochikit
	 * @param array $pLibHash Array of additional libraries we need to load
	 * @param array $pLibPath Array of additional libraries we need to load
	 * @param boolean $pPack Set to true if you want to pack the javascript file
	 * @return bool true on success, false on failure - mErrors will contain reason for failure
	 */
	public function loadAjax( string $pAjaxLib, ?array $pLibHash = null, ?array $pLibPath = null, bool $pPack = false ): bool {
		global $gBitSystem, $gBitSmarty, $gSniffer;
		$ret = false;
		$joined = true;
		$ajaxLib = strtolower( $pAjaxLib );
		if( $this->isJavascriptEnabled() ) {
			// set the javascript lib path if not set yet
			if( empty( $pLibPath )) {
				switch( $ajaxLib ) {
					case 'mochikit':
						$pLibPath = UTIL_PKG_PATH."javascript/MochiKit/";
						$pos = 100;
						break;
					case 'jquerylocal':
						$pLibPath = UTIL_PKG_PATH."javascript/jquery/";
						$pos = 100;
						break;
					default:
						$pLibPath = UTIL_PKG_PATH."javascript/";
						$pos = 200;
						break;
				}
			}

			if( !$this->isAjaxLoaded( $ajaxLib ) ) {
				// load core javascript files for ajax libraries
				$jqueryMin = $gBitSystem->isLive() ? '.min' : '';
				$bootstrapSrc = CONFIG_PKG_PATH.'themes/bootstrap/js/bootstrap'.$jqueryMin.'.js';
				switch( $ajaxLib ) {
					case 'jquery':
						$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
						$jqueryVersion = $gBitSystem->getConfig( 'jquery_version', '3.5.1' );
						$jqueryUiVersion = $gBitSystem->getConfig( 'jquery_ui_version', '1.12.1' );
						$jqueryTheme = $gBitSystem->getConfig( 'jquery_theme', 'smoothness' );
						$jquerySrc = '//ajax.googleapis.com/ajax/libs/jquery/'.$jqueryVersion.'/jquery'.$jqueryMin.'.js';
						$jqueryUiSrc = '//ajax.googleapis.com/ajax/libs/jqueryui/'.$jqueryUiVersion.'/jquery-ui'.$jqueryMin.'.js';
						$this->mRawFiles['js'][] = $jquerySrc;
						$this->mRawFiles['js'][] = $jqueryUiSrc;
						$this->mRawFiles['css'][] = '//ajax.googleapis.com/ajax/libs/jqueryui/'.$jqueryUiVersion.'/themes/'.$jqueryTheme.'/jquery-ui.min.css';
						// bootstrap needs to load after jquery
						if( file_exists( $bootstrapSrc ) ) {
							$this->mRawFiles['js'][] = $bootstrapSrc;
						}

						$gBitSmarty->assign( 'jquerySrc', $jquerySrc );
						break;
					case 'jquerylocal':
						$joined = false;
						$this->loadJavascript( THEMES_PKG_PATH.'js/jquery-3.7.1.js', false, $pos++, $joined );
//						$this->loadJavascript( THEMES_PKG_PATH.'js/jquery-ui-14.1.js', false, $pos++, $joined );
//						$this->loadJavascript( THEMES_PKG_PATH.'js/jquery-migrate-3.5.2.js', false, $pos++, $joined );
						$this->loadJavascript( THEMES_PKG_PATH.'js/bootstrap.js', false, $pos++, $joined );
						$this->loadJavascript( THEMES_PKG_PATH.'js/bootstrap-cookie-consent.js', false, $pos++, $joined );
						$this->loadCss( THEMES_PKG_PATH.'css/colourstrap-full.css', false, $pos++, $joined );
//						$this->loadCss( THEMES_PKG_PATH.'js/jquery-ui'.$jqueryMin.'.css', false, $pos++, $joined );
						break;
					case 'jqueryold':
						$joined = false;
						$this->loadJavascript( THEMES_PKG_PATH.'js/jquery'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( THEMES_PKG_PATH.'js/jquery-ui-1.10.3.custom'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'bootstrap/js/bootstrap'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'bootstrap/js/moment'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'bootstrap/datetimepicker/js/bootstrap-datetimepicker'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'bootstrap/signature-pad/assets/numeric-1.2.6.min.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'bootstrap/signature-pad/assets/bezier.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'bootstrap/signature-pad/jquery.signaturepad'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'formvalidation/dist/js/formValidation'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadJavascript( EXTERNAL_LIBS_PATH.'formvalidation/dist/js/framework/bootstrap'.$jqueryMin.'.js', false, $pos++, $joined );
						$this->loadCss( EXTERNAL_LIBS_PATH.'bootstrap/colourstrap/colourstrap.css', false, $pos++, $joined );
						$this->loadCss( EXTERNAL_LIBS_PATH.'bootstrap/colourstrap/colourstrap-icons.css', false, $pos++, $joined );
						$this->loadCss( EXTERNAL_LIBS_PATH.'bootstrap/datetimepicker/css/bootstrap-datetimepicker'.$jqueryMin.'.css', false, $pos++, $joined );
						$this->loadCSs( EXTERNAL_LIBS_PATH.'bootstrap/signature-pad/assets/jquery.signaturepad.css', false, $pos++, $joined );
						$this->loadCss( EXTERNAL_LIBS_PATH.'formvalidation/dist/css/formValidation'.$jqueryMin.'.css', false, $pos++, $joined );
						break;
					case 'mochikit':
						$this->loadJavascript( $pLibPath.'Base.js', false, $pos++ );
						$this->loadJavascript( $pLibPath.'Async.js', false, $pos++ );
						$this->loadJavascript( UTIL_PKG_PATH.'javascript//MochiKitBitAjax.js', false, $pos++, $joined );
						break;
					case 'yui':
						$this->loadJavascript( $pLibPath.'yuiloader-dom-event/yuiloader-dom-event.js', false, $pos++ );
						break;
				}
				$this->mAjaxLibs[$ajaxLib] = true;
			}

			if( \is_array( $pLibHash )) {
				foreach( $pLibHash as $lib ) {
					$fullLib = ($lib[0] == '/' ? '' : $pLibPath).$lib;
					$this->loadJavascript( $fullLib, $pPack, $pos++, $joined );
				}
			}

			$ret = true;
		}
		return $ret;
	}

	/**
	 * check to see if a given ajax library is loaded
	 *
	 * @param string $pAjaxLib
	 * @return bool true on success, false on failure
	 */
	public function isAjaxLoaded( string $pAjaxLib ): bool {
		if( !empty( $this->mAjaxLibs ) && !empty( $pAjaxLib )) {
			return \in_array( strtolower( $pAjaxLib ), \array_keys( $this->mAjaxLibs ));
		}   return false;
	}

	/**
	 * scan packages for <pkg>/templates/html_head_inc.tpl or footer_inc.tpl files
	 *
	 * @param string $pFilename Name of template file we want to scan for and collect
	 * @return void
	 */
	public function loadTplFiles( $pFilename ) {
		global $gBitSystem;
		// these package templates will be included last
		$prepend = [ 'kernel' ];
		$append = [ 'themes' ];
		$anti = $mid = $post = [];
//debug vd($gBitSystem->mPackages);
		foreach( $gBitSystem->mPackages as $package => $info ) {
		if( !empty( $info['path'] )) {
				$file = "{$info['path']}templates/{$pFilename}.tpl";
				$out = "bitpackage:{$package}/{$pFilename}.tpl";
				if( is_readable( $file )) {
					if( \in_array( $package, $prepend )) {
						$anti[] = $out;
					} elseif( \in_array( $package, $append )) {
						$post[] = $out;
					} else {
						$mid[] = $out;
					}
				}
			}
		}
		$this->mAuxFiles['templates'][$pFilename] = [ ...$anti, ...$mid, ...$post ];
//debug vd($this->mAuxFiles['templates']);
	}

	/**
	 * loadAuxFile will add a file to the mAuxFiles hash for later processing
	 *
	 * @param string $pFile Full path to the file in question
	 * @param string $pType specifies what files to join. typical values include 'js', 'css'
	 * @param int $pPosition Specify the position of the javascript file in the load process.
	 *                           If the selected position is occupied, it will search for the next free position in the hash.
	 * @access public
	 * @return bool true on success, false on failure
	 */
	public function loadAuxFile( string $pFile = '', string $pType = '', int $pPosition = 1, bool $pAuxFile = true ) {
		if( !empty( $pFile ) && !empty( $pType )) {
			if( is_readable( $pFile ) ) {
				if( $pAuxFile ) {
					$fileHash =& $this->mAuxFiles;
				} else {
					$fileHash =& $this->mRawFiles;
				}

				if( !$this->isAuxFile( $pFile, $pType, $pAuxFile )) {
					// if the selected position is occupied, we'll try to load it in the next position
					if( !empty( $fileHash[$pType][$pPosition] )) {
						$this->loadAuxFile( $pFile, $pType, ++$pPosition, $pAuxFile );
					} else {
						$fileHash[$pType][$pPosition] = $pFile;
						// ensure that hash is sorted correctly
						ksort( $fileHash[$pType] );

						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Load an addition javascript file
	 *
	 * @param string $pJavascriptFile Full path to javascript file
	 * @param boolean $pPack Set to true if you want to pack the javascript file
	 * @param numeric $pPosition Specify the position of the javascript file in the load process
	 * @note
	 *  - generic javascript libraries are loaded between 1 and 99
	 *  - ajax javascript libraries use position numbers between 100 and 599
	 *  - by default all loaded javascript files are after 600.
	 * @access public
	 * @return bool true on success, false on failure
	 */
	public function loadJavascript( $pJavascriptFile, $pPack = false, $pPosition = 600, $pJoined = true ) {
		global $gBitSystem;
		$ret = false;
		if( !empty( $pJavascriptFile )) {
			if( $pPack && $gBitSystem->isFeatureActive( 'themes_packed_js_css' ) && function_exists( 'shell_exec' ) && shell_exec( 'which java' ) ) {
				if( is_file( $pJavascriptFile )) {
					// get a name for the cache file we're going to store
					$cachefile = md5( $pJavascriptFile ).'.js';

					// if the file hasn't been packed and cached yet, we do that now.
					if( !$this->mThemeCache->isCached( $cachefile, filemtime( $pJavascriptFile ))) {
						/* DEPRECATED in favor of better yui compressor
						require_once( THEMES_PKG_INCLUDE_PATH.'class.JavaScriptPacker.php' );
						$packer = new JavaScriptPacker( file_get_contents( $pJavascriptFile ) );
						$this->mThemeCache->writeCacheFile( $cachefile, $packer->pack() );
						*/
						$cacheData = shell_exec( 'java -jar '.UTIL_PKG_INCLUDE_PATH.'yui/yuicompressor-2.4.2.jar --type js '.$pJavascriptFile );
						$this->mThemeCache->writeCacheFile( $cachefile, $cacheData );
					}

					// update javascript file with new path
					$pJavascriptFile = $this->mThemeCache->getCacheFile( $cachefile );
				}
			}

			$ret = $this->loadAuxFile( $pJavascriptFile, 'js', $pPosition, $pJoined && $gBitSystem->isFeatureActive( 'themes_joined_js_css' ));
		}
		return $ret;
	}

	/**
	 * Load an additional CSS file
	 *
	 * @param string $pCssFile Full path to CSS file
	 * @param boolean $pPack Specify if minimized version fo file to be loaded
	 * @param int $pPosition Specify the position of the javascript file in the load process
	 * @param boolean $pJoined Adds the file to the list of files to be concatenated into a single file
	 * @param boolean $pForce Forces the css file to always be loaded, should only be used by active style
	 * @return bool true on success, false on failure
	 */
	public function loadCss( string $pCssFile, bool $pPack = true, int $pPosition = 300, bool $pJoined = true, bool $pForce = false ): bool {
		global $gBitSystem;
		$ret = false;
		if( !empty( $pCssFile ) && ( !$gBitSystem->isFeatureActive( 'themes_disable_pkg_css' ) || $pForce )) {
			// only manipulate css file if we're joining or packing the files
			if(( $pJoined && $gBitSystem->isFeatureActive( 'themes_joined_js_css' )) || ( $pPack && $gBitSystem->isFeatureActive( 'themes_packed_js_css' ))) {
				$pCssFile = $this->packCss( $pCssFile, $pPack && $gBitSystem->isFeatureActive( 'themes_packed_js_css' ));
			}

			$ret = $this->loadAuxFile( $pCssFile, 'css', $pPosition, $pJoined && $gBitSystem->isFeatureActive( 'themes_joined_js_css' ));
		}
		return $ret;
	}

	/**
	 * simply pack css file by removing excess whitespace and comments
	 *
	 * @param string $pCssFile full path to css file
	 * @param bool $pPack use packed version of file
	 * @return string empty string if not found
	 */
	public function packCss( string $pCssFile, bool $pPack = true ): string {
		$ret = '';
		if( !empty( $pCssFile ) && is_readable( $pCssFile )) {
			$cachefile = md5( $pCssFile ).'.css';

			if( !$this->mThemeCache->isCached( $cachefile, filemtime( $pCssFile ))) {
				$content = file_get_contents( $pCssFile )."\n";

				// now that @import has been dealt with, there still might be some url()s in the file.
				// if we have any url() in the CSS file, we need to fix the path to the file with an absolute URL
				if( preg_match_all( "#\burl\s*\((.*?)\)#i", $content, $urls )) {
					foreach( $urls[1] as $key => $url ) {
						if( $url = $this->relativeToAbsolute( $url, $pCssFile )) {
							$content = str_replace( $urls[1][$key], $url, $content );
						}
					}
				}

				// if we have an @import(), we fetch that file and insert it
				if( preg_match_all( "#@import([^;]*);#", $content, $imports )) {
					foreach( $imports[1] as $key => $import ) {
						if( $file = $this->relativeToAbsolute( $import, $pCssFile, false )) {
							// since we're packing later on, we don't pack here, otherwise the same sections will be packed multiple times
							$content = str_replace( $imports[0][$key], file_get_contents( $this->packCss( $file, false )), $content );
						}
					}
				}

				// now pack the css file if wanted
				if( $pPack ) {
					$packer = [
						//						"#/\*.*\*/#"           => "",       // one line comments -- disabled for now since it causes problems when someone has a multiline comment and closes it with /* */
						"#\n\s*#s"             => "\n",     // leading whitespace
						"#[\t ]+#"             => " ",      // reduce whitespace
						"#,\s*#s"              => ",",      // whitespace after ,
						"#[ \t]*([:;])[ \t]*#" => "$1",     // whitespace around : ;
						"#;\n+#"               => ";",      // newlines after ;
						"#\s*([\{\}])\s*#"     => "$1",     // whitespace around { }
						"#\}#"                 => "}\n",    // insert newlines after } for readability
						"#{([^\}]*){#"         => "{\n$1{", // insert newlines after { when there's a second { on that line ( e.g.: @media{body{...} )
						"#.*{\s*\}#"           => '',       // remove empty definitions ( thanks to the ',' regex above, things like h1,h2,h3 {} should all be on one line )
						"#\n+#"                => "\n",     // excess newlines
					];
					$content = preg_replace( \array_keys( $packer ), \array_values( $packer ), $content );
				}

				// css files have been compressed and url()s have been fixed
				$this->mThemeCache->writeCacheFile( $cachefile, $content );
			}
			$ret = $this->mThemeCache->getCacheFile( $cachefile );
		}
		return $ret;
	}

	/**
	 * relativeToAbsolute convert a relative or absolute URL to an absolute URL or path
	 *
	 * @param string $pUrl url() in the css file
	 * @param string $pCssFile full path to the css file calling the url()
	 * @param boolean $pReturnUrl return URL or path to file
	 * @access private
	 * @return mixed URL/path on success, false on failure
	 */
	public function relativeToAbsolute( $pUrl, $pCssFile, $pReturnUrl = true ) {
		$ret = '';
		if( !empty( $pUrl ) && !empty( $pCssFile )) {
			// clean up url
			if( preg_match( "#url\s*\(#", $pUrl )) {
				$pUrl = trim( preg_replace( "#url\s*\(([^\)]*)\)#", "$1", $pUrl ));
			}

			$pUrl = trim( preg_replace( "#[\"']#", "", $pUrl ));

			if( strpos( $pUrl, "http" ) === 0 ) {
				// don't do anything
			} elseif( strpos( $pUrl, "/" ) === 0 ) {
				// if this is an absolute url, we check if the file exists
				$ret = substr_replace( $pUrl, BIT_ROOT_PATH, 0, strlen( BIT_ROOT_URL ));
			} else {
				// this url is relative to the original file
				$ret = realpath( dirname( $pCssFile )."/".$pUrl );
			}

			if( $pReturnUrl ) {
				if ( KernelTools::is_windows() ) {
					$ret = str_replace( '\\', '/',  $ret );
					// Put first forward slash back
					$ret = substr_replace($ret, '\\', 2, 1 );
					$winBitRootPath = str_replace( '\\', '/',  BIT_ROOT_PATH );
					// Put first forward slash back
					$winBitRootPath = substr_replace($winBitRootPath, '\\', 2, 1 );
					$ret = str_replace( $winBitRootPath, BIT_ROOT_URL, $ret );
				} else {
					$ret = str_replace( BIT_ROOT_PATH, BIT_ROOT_URL, $ret );
				}
			} else if ( KernelTools::is_windows() ) {
				$ret = str_replace(  '/', '\\', $ret );
			}
		}
		return $ret;
	}

	/**
	 * joinAuxFiles will join all files in mAuxFiles[hash] into one cached file. This helps keep our HTTP requests down to a minimum.
	 *
	 * @param string $pType specifies what files to join. typical values include 'js', 'css'
	 * @access private
	 * @return string url to cached file
	 */
	public function joinAuxFiles( $pType ) {
		global $gBitSystem;
		$ret = false;

		// remove conflicting aux files
		$this->cleanAuxFiles( $pType );

		if(( $pType == 'js' || $pType == 'css' ) && !$gBitSystem->isFeatureActive( 'themes_joined_js_css' )) {
			return $ret;
		}

		if( !empty( $pType ) && !empty( $this->mAuxFiles[$pType] ) && \is_array( $this->mAuxFiles[$pType] )) {
			$cachestring = '';
			$lastmodified = 0;
			// get a unique cachefile name for this set of javascript files
			foreach( $this->mAuxFiles[$pType] as $file ) {
				if( is_file( $file )) {
					$cachestring .= '|'.$file;
					$lastmodified = max( $lastmodified, filemtime( $file ));
				}
			}
			$cachefile = md5( $cachestring ).'.'.$pType;

			if( !$this->mThemeCache->isCached( $cachefile, $lastmodified )) {
				$contents = '';
				foreach( $this->mAuxFiles[$pType] as $file ) {
					// if we have an extension to check against, we'll do that
					$chars = 0 - ( strlen( $pType ) + 1 );
					if( !empty( $pType ) && substr( $file, $chars ) == '.'.$pType && is_readable( $file )) {
						$contents .= file_get_contents( $file )."\n";
					}
				}
				$this->mThemeCache->writeCacheFile( $cachefile, $contents );
			}

			$ret = $this->mThemeCache->getCacheUrl( $cachefile );
		}
		return $ret;
	}

	/**
	 * cleanAuxFiles will remove unwanted aux files if conflicting files have been loaded
	 *
	 * @param string $pType specifies what files to clean up. typical values include 'js', 'css'
	 * @access private
	 * @return void
	 * @note  It is regrettable that we have this method here but our previous
	 *        use of prototype requires this cleanup and might be needed in the
	 *        future as well
	 */
	public function cleanAuxFiles( string $pType ): void {
		// unload files that are not wanted by users
		if( !empty( $this->mUnloadFiles[$pType] )) {
			foreach( $this->mUnloadFiles[$pType] as $file ) {
				if( !empty( $this->mAuxFiles[$pType] )) {
					if( $key = \array_search( $file, $this->mAuxFiles[$pType] )) {
						unset( $this->mAuxFiles[$pType][$key] );
					}
				}

				if( !empty( $this->mRawFiles[$pType] )) {
					if( $key = \array_search( $file, $this->mRawFiles[$pType] )) {
						unset( $this->mRawFiles[$pType][$key] );
					}
				}
			}
		}

		// remove conflicting files
		if( !empty( $pType ) && !empty( $this->mAuxFiles[$pType] )) {
			if( $pType = 'js' ) {
				// prototype is loaded for a reason. we'll remove mochikit
				if( $this->isAjaxLoaded( 'prototype' ) && $this->isAjaxLoaded( 'mochikit' )) {
					foreach( $this->mAuxFiles[$pType] as $key => $js ) {
						if( strstr( $js, 'Mochi' )) {
							unset( $this->mAuxFiles[$pType][$key] );
						}
					}
				}
			}
		}

		// convert full file path to URL in mRawFiles hash
		if( !empty( $this->mRawFiles[$pType] )) {
			foreach( $this->mRawFiles[$pType] as $pos => $file ) {
				if ( KernelTools::is_windows() ) {
					$file = str_replace( '\\', '/',  $file );
					// Put first forward slash back
					$file = substr_replace( $file, '\\', 2, 1 );
					$winBitRootPath = str_replace( '\\', '/',  BIT_ROOT_PATH );
					// Put first forward slash back
					$winBitRootPath = substr_replace($winBitRootPath, '\\', 2, 1 );
					if ( strpos( $file, $winBitRootPath ) !== false ) {
						$this->mRawFiles[$pType][$pos] = BIT_ROOT_URL.substr( $file, strlen( $winBitRootPath ));
					}
				} else if ( strpos( $file, BIT_ROOT_PATH ) !== false ) {
					$this->mRawFiles[$pType][$pos] = BIT_ROOT_URL.substr( $file, strlen( BIT_ROOT_PATH ));
					if( file_exists( $file ) && ($cacheTime = filemtime( $file )) ) {
						$this->mRawFiles[$pType][$pos] .= (strpos('?',$file) ? '&' : '?' ).$cacheTime;
					}
				}
			}
		}
	}

	// }}}
	// {{{ Javascript and CSS unload methods
	/**
	 * unloadAuxFile
	 *
	 * @param string $pType specifies what files to clean up. typical values include 'js', 'css'
	 * @param string $pFile Full path to the file in question
	 * @return void
	 */
	public function unloadAuxFile( string $pType, string $pFile ) {
		if( !empty( $pType ) && !empty( $pFile ) && is_file( $pFile )) {
			$this->mUnloadFiles[$pType][] = $pFile;
		}
	}

	/**
	 * unloadCss
	 *
	 * @param string $pFile Full path to the file in question
	 * @return void
	 */
	public function unloadCss( string $pFile ): void {
		$this->unloadAuxFile( 'css', $pFile );
	}

	/**
	 * unloadJvascript
	 *
	 * @param string $pFile Full path to the file in question
	 * @return void
	 */
	public function unloadJavascript( string $pFile ): void {
		$this->unloadAuxFile( 'js', $pFile );
	}

	// }}}
	// {{{ Javascript and CSS override methods
	/**
	 * overrideAuxFile Override an aux file
	 *
	 * @param string $pType specifies what files to clean up. typical values include 'js', 'css'
	 * @param string $pOriginalFile Path to old file
	 * @param string $pNewFile Path to new file
	 * @return bool true on success, false on failure
	 * @note This can only be used after the original file has been loaded since we're swapping the original one with a new one
	 */
	public function overrideAuxFile( string $pType, string $pOriginalFile, string $pNewFile ): bool {
		$ret = false;
		if( is_file( $pNewFile )) {
			if( $key = \array_search( $pOriginalFile, $this->mAuxFiles[$pType] )) {
				$this->mAuxFiles[$pType][$key] = $pNewFile;
				$ret = true;
			}

			if( $key = \array_search( $pOriginalFile, $this->mRawFiles[$pType] )) {
				$this->mRawFiles[$pType][$key] = $pNewFile;
				$ret = true;
			}
		}
		return $ret;
	}

	/**
	 * overrideCss
	 *
	 * @param string $pOriginalFile Path to old file
	 * @param string $pNewFile Path to new file
	 * @return bool true on success, false on failure
	 * @note See overrideAuxFile note
	 */
	public function overrideCss( string $pOriginalFile, string $pNewFile ): bool {
		return $this->overrideAuxFile( 'css', $pOriginalFile, $pNewFile );
	}

	/**
	 * overrideJavascript
	 *
	 * @param string $pOriginalFile Path to old file
	 * @param string $pNewFile Path to new file
	 * @return bool true on success, false on failure
	 * @note See overrideAuxFile note
	 */
	public function overrideJavascript( string $pOriginalFile, string $pNewFile ): bool {
		return $this->overrideAuxFile( 'js', $pOriginalFile, $pNewFile );
	}
	// }}}

	/**
	 * isAuxFile
	 *
	 * @param string $pFile Full path to file
	 * @param string $pType specifies what files to check. typical values include 'js', 'css'
	 * @param bool $pAuxFile use AuxFile list rather than Raw list
	 * @access public
	 * @return bool true on success, false on failure
	 */
	public function isAuxFile( string $pFile = '', string $pType = '', bool $pAuxFile = true ): bool {
		if( $pAuxFile ) {
			$fileHash =& $this->mAuxFiles;
		} else {
			$fileHash =& $this->mRawFiles;
		}

		if( !empty( $pFile ) && !empty( $pType ) && !empty( $fileHash[$pType] )) {
			return \in_array( $pFile, $fileHash[$pType] );
		}   return false;
	}

	// }}}
	// {{{ =================== Miscellaneous Stuff ====================
	/**
	 * setDisplayMode
	 *
	 * @param string $pDisplayMode
	 * @access public
	 * @return void
	 */
	public function setDisplayMode( $pDisplayMode ) {
		if( !empty( $pDisplayMode )) {
			$this->mDisplayMode = $pDisplayMode;
		}
	}

	/**
	 * Set the proper headers for requested output
	 *
	 * @param  $pFormat the output headers. Available options include: html, json, xml or none
	 * @access public
	 */
	public function setFormatHeader( $pFormat = 'html' ) {
		// this will tell BitSystem::display what headers have been set in case it's been called independently
		$this->mFormatHeader = $pFormat;

		switch( $pFormat ) {
			case "xml" :
				//since we are returning xml we must report so in the header
				//we also need to tell the browser not to cache the page
				//see: http://mapki.com/index.php?title=Dynamic_XML
				// Date in the past
				header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
				// always modified
				header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" )." GMT" );
				// HTTP/1.1
				header( "Cache-Control: no-store, no-cache, must-revalidate" );
				header( "Cache-Control: post-check=0, pre-check=0", false );
				// HTTP/1.0
				header( "Pragma: no-cache" );
				//XML Header
				header( "Content-Type: text/xml" );
				break;

			case "json" :
				header( 'Content-type: application/json' );
				break;

			case "none" :
			case "center_only" :
				break;

			case "html" :
			default :
				header( 'Content-Type: text/html; charset=utf-8' );
				break;
		}
	}

	/**
	 * getGraphvizGraphAttributes
	 *
	 * @param array $pParams Override any of the settings coming out of this function
	 * @access public
	 * @return array Hash of default values
	 */
	public function getGraphvizGraphAttributes( $pParams = [] ) {
		global $gBitSystem;
		$ret = [
			'bgcolor'  => $gBitSystem->getConfig( 'graphviz_graph_bgcolor', 'transparent' ),
			'color'    => $gBitSystem->getConfig( 'graphviz_graph_color', '#000000' ),
			'fontname' => $gBitSystem->getConfig( 'graphviz_graph_fontname', 'Helvetica' ),
			'fontsize' => $gBitSystem->getConfig( 'graphviz_graph_fontsize', 10 ),
			'nodesep'  => $gBitSystem->getConfig( 'graphviz_graph_nodesep', '.1' ),
			'overlap'  => $gBitSystem->getConfig( 'graphviz_graph_overlap', 'scale' ),
			'rankdir'  => $gBitSystem->getConfig( 'graphviz_graph_rankdir', 'LR' ),
			'size'     => '',
		];

		foreach( $pParams as $key => $value ) {
			// any parameter can be prefixed that they can be passed in all at once
			if( empty( $value ) || preg_match( "@^(edge_|node_)@", $key )) {
				unset( $pParams[$key] );
			} elseif( isset( $ret[preg_replace( '@^graph_@', '', $key )] )) {
				$ret[preg_replace( '@^graph_@', '', $key )] = $value;
			}
		}
		return $ret;
	}

	/**
	 * getGraphvizNodeAttributes
	 *
	 * @param array $pParams Override any of the settings coming out of this function
	 * @access public
	 * @return array Hash of default values
	 */
	public function getGraphvizNodeAttributes( $pParams = [] ) {
		global $gBitSystem;
		$ret = [
			'color'     => $gBitSystem->getConfig( 'graphviz_node_color', '#aaaaaa' ),
			'fillcolor' => $gBitSystem->getConfig( 'graphviz_node_fillcolor', 'white' ),
			'fontname'  => $gBitSystem->getConfig( 'graphviz_node_fontname', 'Helvetica' ),
			'fontsize'  => $gBitSystem->getConfig( 'graphviz_node_fontsize', 10 ),
			'fontcolor' => $gBitSystem->getConfig( 'graphviz_node_fontcolor', 'black' ),
			'height'    => $gBitSystem->getConfig( 'graphviz_node_height', '.1' ),
			'overlap'   => $gBitSystem->getConfig( 'graphviz_node_overlap', 'scale' ),
			'penwidth'  => $gBitSystem->getConfig( 'graphviz_node_penwidth', '1' ),
			'shape'     => $gBitSystem->getConfig( 'graphviz_node_shape', 'box' ),
			'style'     => $gBitSystem->getConfig( 'graphviz_node_style', 'rounded,filled' ),
			'width'     => $gBitSystem->getConfig( 'graphviz_node_width', '.1' ),
		];

		foreach( $pParams as $key => $value ) {
			// any parameter can be prefixed that they can be passed in all at once
			if( empty( $value ) || preg_match( "@^(edge_|graph_)@", $key )) {
				unset( $pParams[$key] );
			} elseif( isset( $ret[preg_replace( '@^node_@', '', $key )] )) {
				$ret[preg_replace( '@^node_@', '', $key )] = $value;
			}
		}
		return $ret;
	}

	/**
	 * getGraphvizEdgeAttributes
	 *
	 * @param array $pParams Override any of the settings coming out of this function
	 * @access public
	 * @return array Hash of default values
	 */
	public function getGraphvizEdgeAttributes( $pParams = [] ) {
		global $gBitSystem;
		$ret = [
			'color'     => $gBitSystem->getConfig( 'graphviz_edge_color', '#888888' ),
			'fontcolor' => $gBitSystem->getConfig( 'graphviz_edge_fontcolor', 'black' ),
			'fontname'  => $gBitSystem->getConfig( 'graphviz_edge_fontname', 'Helvetica' ),
			'fontsize'  => $gBitSystem->getConfig( 'graphviz_edge_fontsize', 10 ),
			'style'     => $gBitSystem->getConfig( 'graphviz_edge_style', 'solid' ),
			'dir'       => '',
			'label'     => '',
		];

		foreach( $pParams as $key => $value ) {
			// any parameter can be prefixed that they can be passed in all at once
			if( empty( $value ) || preg_match( "@^(node_|graph_)@", $key )) {
				unset( $pParams[$key] );
			} elseif( isset( $ret[preg_replace( '@^edge_@', '', $key )] )) {
				$ret[preg_replace( '@^edge_@', '', $key )] = $value;
			}
		}
		return $ret;
	}

	// }}}
	// {{{ =================== Deprecated code ====================
	// deprecated stuff and temporary place holders
	// 																		--------------- all of these functions will be removed quite soon
	/**
	 * @deprecated deprecated since version 2.0.0
	 */
	public function storeLayout() {
		KernelTools::deprecated( 'Please remove this function and use storeModule instead' );
	}
	/**
	 * @deprecated deprecated since version 2.0.0
	 */
	public function storeModuleParameters($mod_rsrc, $user_id, $params) {
		KernelTools::deprecated( 'This method does not work as expected due to changes in the layout schema. we have not found a suitable replacement yet.' );
	}
	/**
	 * @deprecated deprecated since version 2.0.0
	 */
	public function getModuleId($mod_rsrc) {
		KernelTools::deprecated( 'This method does not work as expected due to changes in the layout schema. we have not found a suitable replacement yet.' );
	}
	/**
	 * @deprecated deprecated since version 2.0.0
	 */
	public function getStyleCss( $pStyle = null ) {
		KernelTools::deprecated( 'Please use: BitThemes::getStyleCssFile()' );
		return $this->getStyleCssFile( $pStyle, true );
	}
	// }}}
}

function themes_feedback_to_html( $params ) {

	KernelTools::detoxify( $params );
	if( !empty( $params['hash'] ) ) {
		$hash = &$params['hash'];
	} else {
		// maybe params were passed in separately
		$hash = &$params;
	}
	$feedback = '';
	$i = 0;
	$color = $hash['color']??"000000";
	foreach( $hash as $key => $val ) {
		if( $val ) {
			$keys = [ 'warning', 'success', 'error', 'important', 'note' ];
			if( \in_array( $key, $keys )) {
				switch( $key ) {
					case 'success':
						$alertClass = 'alert alert-success';
						break;
					case 'warning':
						$alertClass = 'alert alert-warning';
						break;
					case 'error':
						$alertClass = 'alert alert-danger';
						break;
					case 'note':
					case 'important':
					default:
						$alertClass = 'alert alert-info';
						break;
				}

				if( !\is_array( $val ) ) {
					$val = [ $val ];
				}

				foreach( $val as $valText ) {
					if( \is_array( $valText ) ) {
						foreach( $valText as $text ) {
							$feedback .= '<span class="inline-block '.$alertClass.'">'.$text.'</span>';
						}
					} else {
						$feedback .= '<span class="inline-block '.$alertClass.'">'.$valText.'</span>';
					}
				}

			} else {
				/* unfortunately this plugin was written a little strictly and so it expects all params to be display text
				 * to allow setting of a background color we have to exclude that param when rendering out the html
				 * otherwise we'll render the color as text. -wjames5
				 */
				if ( $key != 'color' ) {
					if( \is_array( $val ) ) {
						foreach( $val as $text ) {
							$feedback .= '<span class="'.$key.'">'.$text.'</span>';
						}
					} else {
						$feedback .= '<span class="'.$key.'">'.$val.'</span>';
					}
				}
			}
		}
	}

	$html = '';
	if( !empty( $feedback ) ) {
		$html .= $feedback;
	}
	return $html;
}

/**
 * load content specific theme picked by user
 *
 * @param object $pContent
 * @return void
 */
function themes_content_display( object $pContent ): void {
	global $gBitSystem, $gBitSmarty, $gBitThemes, $gBitUser, $gQueryUser;

	// users_themes='u' is for all users content
	if( is_a( $pContent, 'LibertyContent' ) && $pContent->getPreference( 'style' ) ) {
		$theme = $pContent->getPreference( 'style' );
	} elseif( $gBitSystem->getConfig( 'users_themes' ) == 'u' ) {
		if( $gBitSystem->isFeatureActive( 'users_preferences' ) && is_object( $pContent ) && $pContent->isValid() ) {
			if( $pContent->getField( 'user_id' ) == $gBitUser->mUserId ) {
				// small optimization to reduce checking when we are looking at our own content, which is frequent
				if( $userStyle = $gBitUser->getPreference( 'theme' )) {
					$theme = $userStyle;
				}
			} else {
				$theme = RoleUser::getUserPreference( 'theme', null, $pContent->getField( 'user_id' ) );
			}
		}
	}

	if( !empty( $theme ) && $theme != DEFAULT_THEME ) {
		$gBitThemes->setStyle( $theme );
		if( !is_object( $gQueryUser ) ) {
			$gQueryUser = new RolePermUser( $pContent->getField( 'user_id' ) );
			$gQueryUser->load();
			$gBitSmarty->assign( 'gQueryUser', $gQueryUser );
		}
	}
}

/**
 * themes_content_list
 *
 * @param array $pContent
 * @param array $pListHash
 * @access public
 * @return void
 */
function themes_content_list( $pContent, $pListHash ) {
	global $gBitSystem, $gBitSmarty, $gBitThemes, $gBitUser, $gQueryUser;
	// users_themes='u' is for all users content
	if( $gBitSystem->getConfig( 'users_themes' ) == 'u' ) {
		if( $gBitSystem->isFeatureActive( 'users_preferences' ) && !empty( $pListHash['user_id'] ) ) {
			if( $pListHash['user_id'] == $gBitUser->mUserId ) {
				// small optimization to reduce checking when we are looking at our own content, which is frequent
				if( $userStyle = $gBitUser->getPreference('theme') ) {
					$theme = $userStyle;
				}
			} else {
				$theme = RoleUser::getUserPreference( 'theme', null, $pListHash['user_id'] );
			}
		}
	}
	if( !empty( $theme ) && $theme != DEFAULT_THEME ) {
		$gBitThemes->setStyle( $theme );
		if( !is_object( $gQueryUser ) ) {
			$gQueryUser = new RolePermUser( $pListHash['user_id'] );
			$gQueryUser->load();
			$gBitSmarty->assign( 'gQueryUser', $gQueryUser );
		}
	}
}
