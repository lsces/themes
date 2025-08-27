<?php
/**
 * smarty_prefilter_add_link_ticket This will insert a ticket on all template URL's that have GET parameters.
 *
 * @param string $pTplSource source of template
 * @return string ammended template source
 */

 namespace Bitweaver\Plugins;
 use Smarty\Compile\Modifier\ModifierCompilerInterface;
 use Smarty\Compiler\Template;
 
class AddLinkTicket implements ModifierCompilerInterface {
    public function __construct() {
        // Initialization code
    }

	public function compile($params, Template $template) {
		global $gBitUser;

		$source = $params[0];

		if( is_object( $gBitUser ) && $gBitUser->isRegistered() ) {
	//		$from = '#href="(.*PKG_URL.*php)\?(.*)&(.*)"#i';
	//		$to = 'href="\\1?\\2&amp;tk={$gBitUser->mTicket}&\\3"';
	//		$pTplSource = preg_replace( $from, $to, $pTplSource );
			$from = '#<form([^>]*)>#i';
			// div tag is for stupid XHTML compliance.
			$to = '<form\\1><div style="display:inline"><input type="hidden" name="tk" value="{$gBitUser->mTicket}" /></div>';
			$pTplSource = preg_replace( $from, $to, $source );
			if( strpos( $pTplSource[0], '{form}' )) {
				$source = str_replace( '{form}', '{form}<div style="display:inline"><input type="hidden" name="tk" value="{$gBitUser->mTicket}" /></div>', $pTplSource );
			} elseif( strpos( $pTplSource[0], '{form ' ) ) {
				$from = '#\{form(\}| [^\}]*)\}#i';
				$to = '{form\\1}<div style="display:inline"><input type="hidden" name="tk" value="{$gBitUser->mTicket}" /></div>';
				$source = preg_replace( $from, $to, $pTplSource );
			}
		}

		return $source;
	}
}