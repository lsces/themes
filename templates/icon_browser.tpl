{strip}
<div class="listing themes">
	<div class="header">
		<h1>{tr}Icon Listing{/tr}</h1>
	</div>

	<div class="body">
		<p class="help">
			Icon sets use <a class="external" href="https://specifications.freedesktop.org/icon-naming-spec/latest/">freedesktop icon naming</a>.
			The active set is <strong>tango</strong>, built from <a class="external" href="https://gitlab.com/tango-project/tango-icon-theme">Tango3</a> with raster sizes at 16px (small), 24px (medium) and 32px (large), plus scalable SVGs.
			SVGs are served automatically when no raster file exists for the requested size.
		</p>
		<p>
			{if $usedOnly}
				{tr}Showing icons in use only.{/tr} <a href="{$smarty.request.SCRIPT_NAME}{if $smarty.request.icon_style}?icon_style={$smarty.request.icon_style}{/if}">{tr}Show all icons{/tr}</a>
			{else}
				{tr}Showing all icons.{/tr} <a href="{$smarty.request.SCRIPT_NAME}?used_only=1{if $smarty.request.icon_style}&amp;icon_style={$smarty.request.icon_style}{/if}">{tr}Show used icons only{/tr}</a>
			{/if}
		</p>
		<table class="table data">
			<tr>
				{foreach from=$iconList item=icons key=iconStyle}
				<th class="width1p" colspan="3"><a href="{$smarty.request.SCRIPT_NAME}?icon_style={$iconStyle}">{$iconStyle}</a></th>
			{/foreach}
				<th class="width70p;">{tr}Icon name{/tr}</th>
				<th class="width29p;">{tr}bitweaver uses{/tr}</th>
			</tr>

			{foreach from=$iconNames item=name}
				<tr class="{cycle values="odd,even"}">
					{foreach from=$iconList item=icons key=iconStyle}
					{assign var=tdClass value=($gBitSystem->getConfig('site_icon_style') == $iconStyle) ? 'prio1' : (($iconStyle == $smarty.const.DEFAULT_ICON_STYLE) ? 'prio2' : '')}
					{if $iconList.$iconStyle.$name}
					<td class="{$tdClass}">{biticon istyle=$iconStyle ipackage=icons iname=$name isize="small"  iexplain=$name}</td>
					<td class="{$tdClass}">{biticon istyle=$iconStyle ipackage=icons iname=$name isize="medium" iexplain=$name}</td>
					<td class="{$tdClass}">{biticon istyle=$iconStyle ipackage=icons iname=$name isize="large"  iexplain=$name}</td>
					{else}
					<td class="{$tdClass}"></td><td class="{$tdClass}"></td><td class="{$tdClass}"></td>
					{/if}
					{/foreach}
					
					<td>
						{$name}<br />
						<small>{ldelim}biticon ipackage="icons" iname="{$name}" iexplain="Icon"{rdelim}</small>
					</td>
					<td>
						{if $iconUsage.$name}
							{$iconUsage.$name}
						{/if}
					</td>
				</tr>
			{/foreach}
		</table>
	</div><!-- end .body -->
</div><!-- end .___ -->
{/strip}
