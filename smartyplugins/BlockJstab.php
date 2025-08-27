<?php
namespace Bitweaver\Plugins;
use Bitweaver\BitBase;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;
use Bitweaver\KernelTools;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {jstab} block plugin
  *
 * Type:		block
 * Name:		jstab
 * Input:
 * Abstract:	Used to enclose a set of tabs
 */

 class BlockJstab implements BlockHandlerInterface {

	public function handle( $params, $content, Template $template, &$repeat): string {
		if( empty( $repeat ) ){
			global $jsTabLinks;
			
			$tClass = isset( $params['class'] ) ? ' class="'.$params['class'].'"' : '';
			$tStyle	= isset( $params['style'] ) ? ' style="'.$params['style'].'"' : '';
			$tClick	= isset( $params['onclick'] ) ? ' onclick="'.$params['onclick'].'"' : '';
			$tTitle	= KernelTools::tra( isset( $params['title'] ) ? $params['title'] : 'No Title' );

			$tabId = strtolower( isset( $params['id'] ) ? $params['id'] : 'tab'.preg_replace("/[^A-Za-z0-9]/", '', $tTitle) ); 

			$tabString = '<li '.$tClick.' '.$tClass.' '.$tStyle.'><a href="#'.$tabId.'">' . $tTitle . '</a></li>';
			if( isset( $params['position'] ) ) {
				array_splice( $jsTabLinks, $params['position'], 0, $tabString );
			} else {
				$jsTabLinks[] = $tabString;
			}

			$tabType = BitBase::getParameter( $params, 'tabtype', 'tab' );

			$ret = '<div class="'.$tabType.'-pane" id="'.$tabId.'">'; 
			$ret .= $content;
			$ret .= '</div>';

			return $ret;
		}
		return '';
	}

	public function isCacheable(): bool {
		return true;
	}
}