<?php
namespace Bitweaver\Plugins;

use Bitweaver\KernelTools;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * required setup
 */
global $gBitSmarty;
// $gBitSmarty->loadPlugin('smarty_shared_make_timestamp');

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     bit_date_format
 * Purpose:  format datestamps via strftime, (timezone adjusted to administrator specified timezone)
 * Input:    string: input date string
 *           format: strftime format for output
 * -------------------------------------------------------------
 */
function smarty_modifier_bit_date_format( $pString, $format = "%b %e, %Y", $pTraFormat = "%b %e, %Y" ) {
	global $gBitSystem, $gBitUser, $gBitLanguage;

	if( empty( $pString )) {
		return '';
	}

	// we translate the entire date format string for total control
	if( $gBitSystem->getConfig( "bitlanguage", "en" ) != $gBitLanguage->mLanguage ) {
		$format = KernelTools::tra( $pTraFormat );
	}

	if( $gBitUser->getPreference( 'site_display_utc' ) == 'Fixed' ) {
		// Real per-call DateTimeZone (kernel/DATETIME.md's "Open" TODO) - no
		// date_default_timezone_set() global mutation. A naive (offset-less) string is
		// interpreted as already being in this zone, same as the previous ambient-global
		// approach did; a numeric epoch is always UTC by construction, timezone-independent.
		$tz = $gBitUser->getUserTimezone();
		try {
			$dateTimeUser = is_numeric( $pString )
				? new \DateTime( '@'.(int)$pString )
				: new \DateTime( $pString, $tz );
		} catch ( \DateMalformedStringException | \Exception $e ) {
			return '';
		}
		return $gBitSystem->mServerTimestamp->strftime( $format, $dateTimeUser->getTimestamp(), false, $tz );
	}
		$format = $gBitSystem->get_display_offset()
			? preg_replace( "/ ?%Z/",'', $format )
			: $format = preg_replace( "/%Z/", "UTC", $format );
		if( !is_numeric( $pString ) && strtotime( $pString ) === false ) {
			return '';
		}
		$disptime = $gBitSystem->mServerTimestamp->getDisplayDateFromUTC( $pString );

	return $gBitSystem->mServerTimestamp->strftime( $format, $disptime, TRUE );
}
