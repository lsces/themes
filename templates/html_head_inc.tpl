{strip}
{if !empty($gBitThemes->mRawFiles.css)}
	{foreach from=$gBitThemes->mRawFiles.css item=cssFile}
		<link rel="stylesheet" title="{$style|default:'css'}" nonce="{$cspNonce}" type="text/css" href="{$cssFile}" media="all" />
	{/foreach}
{/if}
{if !empty($gBitThemes->mRawFiles.js)}
	{foreach from=$gBitThemes->mRawFiles.js item=jsFile}
		<script nonce="{$cspNonce}" src="{$jsFile}"></script>
	{/foreach}
{/if}
{if !empty($gBitThemes->mStyles.joined_css)}
	<link rel="stylesheet" title="{$style|default:'css'}" type="text/css" href="{$gBitThemes->mStyles.joined_css}" media="all" nonce="{$cspNonce}" />
{/if}
{/strip}
