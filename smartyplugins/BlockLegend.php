<?php
namespace Bitweaver\Plugins;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;
use Bitweaver\KernelTools;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {form} block plugin
 *
 * Type:     block
 * Name:     form
 * Input:
 *           - legend      (optional) - text that appears in the legend
 */
class BlockLegend implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
        if( $content ) {
            $attributes = '';
            $attributes .= !empty( $params['class'] ) ? ' class="'.$params['class'].'" ' : '' ;
            $attributes .= !empty( $params['id'] ) ? ' id="'.$params['id'].'" ' : '' ;
            $ret = '<fieldset '.$attributes.'>';
            if( !empty( $params['legend'] ) ) {
                $ret .= '<legend>'.KernelTools::tra( $params['legend'] ).'</legend>';
            }
            $ret .= $content;
            $ret .= '</fieldset>';
            return $ret;
        }
        return '';
    }

	public function isCacheable(): bool {
		return true;
	}
}