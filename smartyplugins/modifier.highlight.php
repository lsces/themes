<?php

namespace Bitweaver\Plugins;

use Bitweaver\KernelTools;

function smarty_modifier_highlight( $source ) {
	global $gBitSystem, $gBitSmarty;

	if ( empty($_REQUEST['highlight']) ) return $source;

	if( !$gBitSystem->isFeatureActive( 'themes_output_highlighting' ) ) {
		return $source;
	}

	$colorArr = [ '#ffffcc', '#ffcccc', '#a0ffff', '#ffccff', '#ccffcc' ];

	$find = [
		"!(\s|^)%(\s|$)!",
		"!(\s|^)#(\s|$)!",
		"!(\s|^)@(\s|$)!",
		"!(\s|^):(\s|$)!",
		"!(\s|^)&(\s|$)!",
	];

	$words = trim( preg_replace( $find, "$1$2", urldecode( $_REQUEST['highlight'] ?? '' )));
	if( empty( $words )) {
		return $source;
	}

	$highlight = $source;

	$patterns = [
		"!<script[^>]+>.*?</script>!is"           => "@@@##########:#########%:#########&@@@",
		"!<div class=.?maketoc[^>]*>.*?</div>!si" => "@@@##########:#########%:#########@@@@",
		"'<[\/\!]*?[^<>]*?>'si"                   => "@@@##########:#########%:#########:@@@",
	];

	ksort( $patterns );

	foreach( $patterns as $pattern => $replace ) {
		preg_match_all( $pattern, $highlight, $match );
		$matches[$replace] = $match[0];
		$highlight = preg_replace( $pattern, $replace, $highlight );
	}

	$wordArr = [];
	$pattern = '#"([^"]*)"#';
	if( preg_match_all( $pattern, $words, $ms ) ) {
		$wordArr = $ms[1];
		$words = preg_replace( $pattern, "", $words );
	}

	$words = preg_replace( "!\s+!", " ", $words );
	if( !empty( $words ) ) {
		$wordArr = array_merge( $wordArr, explode( ' ', strip_tags($words) ) );
	}

	$i = 0;
	$wordList = KernelTools::tra( "Highlighted words" ).': ';
	foreach ( $wordArr as $word ) {
		$wordList .= '<span style="font-weight:bold;color:black;background-color:'.$colorArr[$i].';">'.$word.'</span> ';
		$highlight = preg_replace( "/(".preg_quote( $word, '/' ).")/si", '<span style="font-weight:bold;color:black;background-color:'.$colorArr[$i++].';">$1</span>', $highlight );
	}

	krsort( $patterns );

	foreach( $patterns as $pattern ) {
		foreach( $matches[$pattern] as $insert ) {
			$highlight = preg_replace( "!{$pattern}!", $insert, $highlight, 1 );
		}
	}
	$source = $highlight;
	$gBitSmarty->assign( 'highlightWordList', "<div class=\"wordlist\">$wordList</div>" );

	return $source;
}
