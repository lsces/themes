<?php
namespace Bitweaver\Plugins;
use Bitweaver\KernelTools;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * smarty_function_jspopup
 */

/**
 * smarty_function_jspopup 
 * 
 * @param array $pParams hash of options 
 * @var string $pParams[href] link the popup should open
 * @var string $pParams[title] title of the link
 * @var string $pParams[img] source of an image that is to be displayed instead of the title
 * @var string $pParams[href]
 * @var string $pParams[href]
 * @param array $pSmarty 
 * @return string
 */
function smarty_function_jspopup( $pParams, &$pSmarty=NULL ) {
	global $gBitThemes, $gBitSmarty;

	$ret = '';
	if( empty( $pParams['href'] ) ) {
		return 'assign: missing "href" parameter';
	}

	$title = empty( $pParams['title'] )
		? basename( $pParams['href'] )
		: ( empty( $pParams['notra'] ) ? $pParams['title'] : KernelTools::tra( $pParams['title'] ) );

	if( empty( $pParams['text'] )){
		$text = $title;
	} else {
		$text = empty( $pParams['notra'] ) ? $pParams['text'] : KernelTools::tra( $pParams['text'] );
		// remove it from the hash since later the params are looped over for to formulate the a tag
		unset( $pParams['text'] );
	}


	$optionHash = array( 'type', 'width', 'height', 'gutsonly', 'img' );
	$guts = '';
	foreach( $pParams as $param => $val ) {
		if( !in_array( $param, $optionHash ) ) {
			if( $param == 'title' ) {
				$guts .= ' '.$param.'="'.KernelTools::tra( 'This will open a new window: ' ).$title.'"';
			}elseif( $param == 'href' && $gBitThemes->isJavascriptEnabled()){
				$guts .= ' '.$param.'="javascript:void(0)"';
			} else {
				$guts .= ' '.$param.'="'.$val.'"';
			}
		}
	}

	if( !empty( $pParams['ibiticon'] ) ) {
//		$gBitSmarty->loadPlugin( 'smarty_function_biticon' );

		$tmp = explode( '/', $pParams['ibiticon'] );
		$ibiticon = [
			'ipackage' => $tmp[0],
			'iname'    => $tmp[1],
			'iexplain' => $title,
		];

		if( !empty( $pParams['iforce'] ) ) {
			$ibiticon['iforce'] = $pParams['iforce'];
		}
		$img = smarty_function_biticon( $ibiticon );
	}

	if( !empty( $pParams['img'] )) {
		$img_size = NULL;
		$file = str_replace( '//', '/', BIT_BASE_PATH.( str_replace( BIT_ROOT_URI, '', urldecode( $pParams['img'] ))));
		if( is_file( $file )) {
			if( $imgSizeHash = @getimagesize( $file )) {
				$img_size = $imgSizeHash[3];
			}
		}
		$img = '<img src="'.$pParams['img'].'" alt="'.$title.'" title="'.$title.'" '.$img_size.' />';
	}

	$js = !empty( $pParams['type'] ) && $pParams['type'] == 'fullscreen'
		? 'BitBase.popUpWin( \''.$pParams['href'].'\',\'fullScreen\');'
		: ( 'BitBase.popUpWin( \''.$pParams['href'].'\',\'standard\',' .
			( !empty( $pParams['width'] ) ? $pParams['width'] : 600 ) .
			',' . ( !empty( $pParams['height'] ) ? $pParams['height'] : 400 ) . ');' );

	// deprecated slated for removal - onkeypress causes focus problems with browsers - if this is an ada issue get in touch -wjames5.
	// $guts .= ' onkeypress="'.$js.'" onclick="'.$js.'return false;"';
	$guts .= ' onclick="'.$js.'return false;"';

	if( !empty( $pParams['gutsonly'] ) ) {
		return $guts;
	} else {
		return '<a '.$guts.'>'.( !empty( $img ) ? $img : $text ).'</a>';
	}
}
