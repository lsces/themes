<?php
namespace Bitweaver\Plugins;
use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\Template;

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/** 
 * Smarty plugin 
 * ------------------------------------------------------------- 
 * File: block.sortlinks.php 
 * Type: block 
 * Name: sortlinks 
 * ------------------------------------------------------------- 
 */ 
class BlockSortLinks implements BlockHandlerInterface {

    public function handle( $params, $content, Template $template, &$repeat): string {
        if ($content) { 
            $links = mb_split("\n",mb_strtolower($content) );
            $links2 = [];
            foreach ($links as $value) {
              $splitted=preg_split("/[<>]/",$value,-1,PREG_SPLIT_NO_EMPTY);
              $links2[$splitted[2]]=$value;
            }

            if( isset( $params['order'] ) && $params['order']=='reverse' ) {
              krsort( $links2 );
            } else {
              ksort($links2);
            }

            foreach($links2 as $value) {
              echo $value;
            }
        }
        return '';
    }

    public function isCacheable(): bool {
        return true;
    }
}
