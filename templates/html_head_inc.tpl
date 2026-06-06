{strip}
{if !empty($gBitThemes->mRawUrls.css)}
	{foreach from=$gBitThemes->mRawUrls.css item=cssFile}
		<link rel="stylesheet" title="{$style|default:'css'}" nonce="{$cspNonce}" type="text/css" href="{$cssFile}" media="all" />
	{/foreach}
{/if}
{if !empty($gBitThemes->mRawUrls.js)}
	{foreach from=$gBitThemes->mRawUrls.js item=jsFile}
		<script nonce="{$cspNonce}" src="{$jsFile}"></script>
	{/foreach}
{/if}
{if !empty($gBitThemes->mStyles.joined_css)}
	<link rel="stylesheet" title="{$style|default:'css'}" type="text/css" href="{$gBitThemes->mStyles.joined_css}" media="all" nonce="{$cspNonce}" />
{/if}
{/strip}
