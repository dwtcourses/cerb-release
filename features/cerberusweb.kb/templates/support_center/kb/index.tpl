<div id="kb">

<fieldset>
	{* Heading *}
	<legend>
		{if empty($root_id)}
		{'Topics'|devblocks_translate|capitalize}
		{else}
		{$categories.$root_id->name}
		{/if}
	</legend>
	
	{* Breadcrumb category navigation *}
	<div style="padding-bottom:10px;">
	{if !empty($root_id)}
		<a href="{devblocks_url}c=kb&a=browse{/devblocks_url}">{'portal.kb.public.top'|devblocks_translate}</a> ::
		{if !empty($breadcrumb)}
			{foreach from=$breadcrumb item=bread_id}
				<a href="{devblocks_url}c=kb&a=browse&id={$bread_id|string_format:"%06d"}{/devblocks_url}">{$categories.$bread_id->name}</a> :
			{/foreach}
		{/if}
	{/if}
	</div>
	
	{* Browse Categories *}
	{if !empty($tree.$root_id)}
	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
		<td width="50%" valign="top">
		{foreach from=$tree.$root_id item=count key=cat_id name=kbcats}
			<span class="glyphicons glyphicons-folder-open" style="color:rgb(253,180,23);"></span>&nbsp;
			<a href="{devblocks_url}c=kb&a=browse&id={$cat_id}-{$categories.$cat_id->name|devblocks_permalink}{/devblocks_url}" style="font-weight:bold;">{$categories.$cat_id->name}</a> ({$count|string_format:"%d"})<br>
		
			{if !empty($tree.$cat_id)}
				&nbsp; &nbsp; 
				{foreach from=$tree.$cat_id item=count key=child_id name=subcats}
					 <a href="{devblocks_url}c=kb&a=browse&id={$child_id}-{$categories.$child_id->name|devblocks_permalink}{/devblocks_url}">{$categories.$child_id->name}</a>{if !$smarty.foreach.subcats.last}, {/if}
				{/foreach}
				<br>
			{/if}
			<br>
			
			{if $smarty.foreach.kbcats.iteration==$mid}
				</td>
				<td width="50%" valign="top">
			{/if}
		{/foreach}
		</td>
		</tr>
	</table>
	{else}
	There are no subcategories.
	{/if}
</fieldset>

{if !empty($root_id)}
	<div class="header"><b>Articles in {$categories.$root_id->name}</b></div>
{elseif empty($root) && !empty($view)}
	<div class="header"><b>All Articles</b></div>
{/if}

{if !empty($view)}
<div id="view{$view->id}">
{$view->render()}
</div>
{/if}

</div><!--#kb-->