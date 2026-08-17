<?php

namespace Bitweaver\Themes;

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
			$callable = 'Bitweaver\\Plugins\\smarty_function_' . $functionName;
			if (!is_callable($callable)) {
				$callable = 'smarty_function_' . $functionName;
			}
			$this->callbacks[$functionName]['name'] = $callable;
			$this->callbacks[$functionName]['loaded'] = true;
			if ( is_callable( $callable )) {
				$gBitSmarty->registerPlugin('function', $functionName, $callable);
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
			$callable = 'Bitweaver\\Plugins\\smarty_modifier_' . $functionName;
			if (!is_callable($callable)) {
				$callable = 'smarty_modifier_' . $functionName;
			}
			$this->callbacks[$functionName]['name'] = $callable;
			$this->callbacks[$functionName]['loaded'] = true;
			if ( is_callable( $callable )) {
				$gBitSmarty->registerPlugin( 'modifier', $functionName, $callable );
			}
		}
	}

	public function __call(string $name, array $arguments) {
		if (str_starts_with($name, 'smarty_modifier_')) {
			$modifierName = substr($name, strlen('smarty_modifier_'));
			if (isset($this->callbacks[$modifierName])) {
				return ($this->callbacks[$modifierName]['name'])(...$arguments);
			}
		} elseif (str_starts_with($name, 'smarty_function_')) {
			$functionName = substr($name, strlen('smarty_function_'));
			if (isset($this->callbacks[$functionName])) {
				return ($this->callbacks[$functionName]['name'])(...$arguments);
			}
		}
		throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
	}

	public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface {

		if (isset($this->modifiers[$modifier])) {
			return $this->modifiers[$modifier] ?? null;
		}

		switch ($modifier) {
			case 'tr': 					$this->modifiers[$modifier] = new \Bitweaver\Plugins\PreTr();
//			case 'bit_short_datetime':	$this->modifiers[$modifier] = new \Bitweaver\Plugins\BitShortDatetime();
		}

		return $this->modifiers[$modifier] ?? null;
	}

	public function getModifierCallback(string $modifierName) {
		if (!isset($this->callbacks[$modifierName])) {
			switch ($modifierName) {
				case 'extension_loaded':	return 'extension_loaded';
				case 'function_exists':		return 'function_exists';
				case 'basename':			return 'basename';
				case 'strpos':				return 'strpos';
				case 'http_build_query':	return 'http_build_query';
				case 'ucwords':				return 'ucwords';
				case 'is_object':			return 'is_object';
				case 'floor':				return 'floor';
				case 'is_a':				return 'is_a';
				case 'is_file':				return 'is_file';
				case 'is_readable':			return 'is_readable';
				case 'ucfirst':				return 'ucfirst';
				case 'html_entity_decode':	return 'html_entity_decode';
				case 'json_decode':			return 'json_decode';
			}
		} else {
			return $this->callbacks[$modifierName]['name'];
		}
		return null;
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

}
