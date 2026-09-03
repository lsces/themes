<?php
namespace Bitweaver\Plugins;

use Bitweaver\KernelTools;
/**
 * @package Smarty
 * @subpackage plugins
 */

/**
 * basic function to convert a number of seconds into a human readable format
 * 
 * @param array $pDuration Duration of event in seconds
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function smarty_modifier_display_duration( $pDuration ) {
	// % (modulo, below) doesn't support float operands - PHP 8.1+ deprecates the implicit
	// truncation this used to do silently. Whole seconds are already the finest unit this
	// function ever displays, so rounding a fractional input (e.g. durationMs/1000 producing
	// 2585.216) here is lossless in practice, not just a deprecation workaround.
	$pDuration = (int)round( $pDuration );
	$units = [
		'month'  => 60 * 60 * 24 * 7 * 4,
		'week'   => 60 * 60 * 24 * 7,
		'day'    => 60 * 60 * 24,
		'hour'   => 60 * 60,
		'min'    => 60,
		'sec'    => 1,
	];

	foreach( $units as $unit => $secs ) {
		$duration[$unit] = 0;
		if( $pDuration > $secs ) {
			$duration[$unit] = floor( $pDuration / $secs );
			$pDuration = $pDuration % $secs;
		}
	}

	$ret  = !empty( $duration['month'] ) ? $duration['month'].KernelTools::tra( 'month(s)' ).' ' : '';
	$ret .= !empty( $duration['week'] )  ? $duration['week'] .KernelTools::tra( 'week(s)' ).' '  : '';
	$ret .= !empty( $duration['day'] )   ? $duration['day']  .KernelTools::tra( 'day(s)' ).' '   : '';
	$ret .= str_pad( $duration['hour'], 2, 0, STR_PAD_LEFT ).':'.str_pad( $duration['min'], 2, 0, STR_PAD_LEFT ).':'.str_pad( $duration['sec'], 2, 0, STR_PAD_LEFT );
	return $ret;
}
