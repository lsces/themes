<?php

namespace Bitweaver\Themes;
use Bitweaver\KernelTools;
use Smarty\Exception;
use Smarty\Extension\Base;

class BitweaverExtension extends Base {

	private $modifiers = [];

	private $callbacks = [];

	private $functionHandlers = [];

	private $blockHandlers = [];

	public function __construct(array $modifiers = [], $functionHandlers = []) {
		global $gBitweaverExtension, $gBitSmarty;
		$gBitweaverExtension = $this;
		// load legacy function and modifier plugins
		$this->initialize();
	}

    protected function initialize() {
        // Include helper functions
//        require_once __DIR__ . '/includes/helper_functions.php';

        // Scan and include function files
        $this->scanAndIncludeFunctionFiles();
        $this->scanAndIncludeModifierFiles();
    }

    protected function scanAndIncludeFunctionFiles() {
        global $gBitSmarty;
		$directory = THEMES_PKG_PATH . 'smartyplugins/';
        $pattern = $directory . 'function.*.php';

        $files = glob($pattern);
        if ($files === false) {
            throw new Exception("Failed to scan directory for function files.");
        }

        foreach ($files as $file) {
            require_once $file;
            $basename = basename($file);
            $functionName = str_replace('function.', '', $basename);
            $functionName = str_replace('.php', '', $functionName);
            $this->callbacks[$functionName]['name'] = 'Smarty\\smarty_function_' . $functionName;
            $this->callbacks[$functionName]['loaded'] = true;
			if ( is_callable('Bitweaver\\Plugins\\smarty_function_' . $functionName )) {
				$gBitSmarty->registerPlugin ('function', $functionName, 'Bitweaver\\Plugins\\smarty_function_' . $functionName );
			}
        }
    }


    protected function scanAndIncludeModifierFiles() {
		global $gBitSmarty;
		$directory = THEMES_PKG_PATH . 'smartyplugins/';
        $pattern = $directory . 'modifier.*.php';

        $files = glob($pattern);
        if ($files === false) {
            throw new Exception("Failed to scan directory for function files.");
        }

        foreach ($files as $file) {
            require_once $file;
            $basename = basename($file);
            $functionName = str_replace('modifier.', '', $basename);
            $functionName = str_replace('.php', '', $functionName);
            $this->callbacks[$functionName]['name'] = 'smarty_modifier_' . $functionName;
            $this->callbacks[$functionName]['loaded'] = true;
			if ( is_callable('BitWeaver\\Plugins\\smarty_modifier_' . $functionName)) {
				$gBitSmarty->registerPlugin ('modifier', $functionName, 'BitWeaver\\Plugins\\smarty_modifier_' . $functionName );
			}
        }
    }

    public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {

		if (isset($this->modifiers[$modifier])) {
			return $this->modifiers[$modifier] ?? null;
		}

        switch ($modifier) {
//			case 'add_link_ticket':		$this->modifiers[$modifier] = new \Bitweaver\Plugins\AddLinkTicket(); break;
			case 'tr': 					$this->modifiers[$modifier] = new \Bitweaver\Plugins\PreTr();
//			case 'bit_short_datetime':	$this->modifiers[$modifier] = new \Bitweaver\Plugins\BitShortDatetime();
        }

		return $this->modifiers[$modifier] ?? null;
	}

	public function getModifierCallback(string $modifierName) {
//		if (!isset($this->modifiers[$modifierName])) {
            if (!isset($this->callbacks[$modifierName])) {
                switch ($modifierName) {
                    case 'highlight':			return [$this, 'smarty_modifier_highlight'];
                    case 'implode':				return [$this, 'smarty_modifier_implode'];
                    case 'extension_loaded':	return [$this, 'smarty_modifier_extension_loaded'];
                    case 'function_exists':		return [$this, 'smarty_modifier_function_exists'];
                    case 'basename':			return [$this, 'smarty_modifier_basename'];
                    case 'strpos':				return [$this, 'smarty_modifier_function_strpos'];
					case 'http_build_query':	return [$this, 'smarty_modifier_http_build_query'];
					case 'ucwords':				return [$this, 'smarty_modifier_ucwords'];
					case 'is_object':			return [$this, 'smarty_modifier_is_object'];
					case 'floor':				return [$this, 'smarty_modifier_floor'];
					case 'is_a':				return [$this, 'smarty_modifier_is_a'];
					case 'is_file':				return [$this, 'smarty_modifier_is_file'];
					case 'is_readable':			return [$this, 'smarty_modifier_is_readable'];
					case 'ucfirst':				return [$this, 'smarty_modifier_ucfirst'];
					case 'html_entity_decode':	return [$this, 'smarty_modifier_html_entity_decode'];
                }
			} else {
				if ( !$this->callbacks[$modifierName]['loaded'] ) {
				}
				return [$this, $this->callbacks[$modifierName]['name']];
			}
			return null;
//		}
	}

	public function getFunctionHandler(string $functionName): ?\Smarty\FunctionHandler\FunctionHandlerInterface {

		if (isset($this->functionHandlers[$functionName])) {
			return $this->functionHandlers[$functionName] ?? null;
		}

		switch ($functionName) {
//			case 'biticon':			$this->functionHandlers[$functionName] = new \Bitweaver\Plugins\BitIcon(); break;
//			case 'booticon':		$this->functionHandlers[$functionName] = new \Bitweaver\Plugins\BootIcon(); break;
//			case 'displayname':		$this->functionHandlers[$functionName] = new \Bitweaver\Plugins\FunctionDisplayName(); break;
//			case 'minifind':		$this->functionHandlers[$functionName] = new \Bitweaver\Plugins\MiniFind(); break;
//			case 'pagination':		$this->functionHandlers[$functionName] = new \Bitweaver\Plugins\Pagination(); break;
//			case 'jspopup':			$this->functionHandlers[$functionName] = new \Bitweaver\Plugins\JsPopup(); break;
	    }
        return $this->functionHandlers[$functionName] ?? null;
    }

	public function getBlockHandler(string $blockTagName): ?\Smarty\BlockHandler\BlockHandlerInterface {


		switch ($blockTagName) {
			case 'bitmodule':	$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockBitModule(); break;
			case 'box':			$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockBox(); break;
			case 'form':		$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockForm(); break;
			case 'forminput':	$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockFormInput(); break;
			case 'jstab':		$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockJstab(); break;
			case 'jstabs':		$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockJstabs(); break;
			case 'legend':		$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockLegend(); break;
			case 'navbar':		$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockNavbar(); break;
			case 'repeat':		$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockRepeat(); break;
			case 'sortlinks':	$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockSortLinks(); break;
			case 'tr':			$this->blockHandlers[$blockTagName] = new \Bitweaver\Plugins\BlockTr(); break;
		}

		return $this->blockHandlers[$blockTagName] ?? null;
	}

	/**
	* Smarty plugin
	* @package Smarty
	* @subpackage plugins
	*/
	public function smarty_modifier_highlight( $source ) {
		global $gBitSystem, $gBitSmarty;
		// Skip out if nothing to highlight
		if ( empty($_REQUEST['highlight']) ) return $source;

		if( $gBitSystem->isFeatureActive( 'themes_output_highlighting' ) ) {
			// This array is used to choose colours for supplied highlight terms
			$colorArr = [ '#ffffcc', '#ffcccc', '#a0ffff', '#ffccff', '#ccffcc' ];

			// don't highlight characters that are used as replacements
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

			// extraction patterns and their replacements
			$patterns = [
				// scripts
				"!<script[^>]+>.*?</script>!is"           => "@@@##########:#########%:#########&@@@",
				// maketoc
				"!<div class=.?maketoc[^>]*>.*?</div>!si" => "@@@##########:#########%:#########@@@@",
				// html tags
				"'<[\/\!]*?[^<>]*?>'si"                   => "@@@##########:#########%:#########:@@@",
			];

			ksort( $patterns );

			foreach( $patterns as $pattern => $replace ) {
				preg_match_all( $pattern, $highlight, $match );
				$matches[$replace] = $match[0];
				$highlight = preg_replace( $pattern, $replace, $highlight );
			}

			// Wrap all the highlight words with a colourful span
			$wordArr = [];
			$pattern = '#"([^"]*)"#';
			if( preg_match_all( $pattern, $words, $ms ) ) {
				$wordArr = $ms[1];
				// remove the words we've just dealt with
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
			$wordList = "<div class=\"wordlist\">$wordList</div>";
			$gBitSmarty->assign( 'highlightWordList', $wordList ?? ''); 
		}
		return $source;
	}

	/**
	 * smarty_modifier_bit_short_date
	 */
	public function smarty_modifier_bit_short_date( $pString ) {
		global $gBitSystem;
		return \Bitweaver\Plugins\smarty_modifier_bit_date_format( $pString, $gBitSystem->get_short_date_format(), '%d %b %Y' );
	}

	/**
	 * PHP core functions reenabled in smarty5 templates 
	 * 
	 */
	public function smarty_modifier_implode( $array, $glue = '' ) {
		if (!is_array($array)) {
			return $array;
		}
		return join($array, $glue);
	}

	public function smarty_modifier_file_exists( $file ) {
		return file_exists($file);
	}

	public function smarty_modifier_basename( $file ) {
		return basename( $file, $suffix = "");
	}


	public function smarty_modifier_extension_loaded( $ext ) {
		return extension_loaded( $ext );
	}

	public function smarty_modifier_function_exists( $func ) {
		return function_exists($func);
	}

	public function smarty_modifier_function_strpos ( $haystack, $needle ) {
		return strpos( $haystack, $needle );
	}

	public function smarty_modifier_http_build_query ( $data ) {
		return http_build_query( $data );
	}

	public function smarty_modifier_ucwords ( $string ) {
		return ucwords( $string ?? '' );
	}

	public function smarty_modifier_is_object ( $string ) {
		return is_object( $string ?? '' );
	}

	public function smarty_modifier_floor ( $num ) {
		return floor( $num ?? 0 );
	}

	public function smarty_modifier_is_a ( object|string $hash, $class ) {
		return is_a( $hash, $class );
	}

	public function smarty_modifier_is_file ( string $file ) {
		return is_file( $file );
	}

	public function smarty_modifier_is_readable ( string $file ) {
		return is_readable( $file );
	}

	public function smarty_modifier_ucfirst ( string $file ) {
		return ucfirst( $file );
	}

	public function smarty_modifier_html_entity_decode ( string $file ) {
		return html_entity_decode( $file );
	}
}
