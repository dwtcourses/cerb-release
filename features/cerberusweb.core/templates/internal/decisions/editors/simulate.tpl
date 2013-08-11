<form id="frmBehaviorSimulator{$trigger->id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="runBehaviorSimulator">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}
<input type="hidden" name="event_params_json" value="{$event_params_json}">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
{if is_array($trigger->variables) && !empty($trigger->variables)}
<fieldset>
	<legend>Parameters</legend>
	{include file="devblocks:cerberusweb.core::internal/decisions/assistant/behavior_variables_entry.tpl" variables=$trigger->variables variable_values=$results field_name="values"}
</fieldset>
{/if}

	
	<div class="" style="display:block;max-height:200px;overflow-y:auto;padding:5px;">
	<b>Current time</b>:
	<div style="margin:0px 0px 2px 10px;">
		<input type="text" name="values[_current_time]" value="now" size="45" style="width:98%;">
	</div>
	
	{foreach from=$dictionary item=v key=k}
		<b>{$v.label}</b>
		<div style="margin:0px 0px 2px 10px;">
			{if $v.type == 'T'}
			<textarea name="values[{$k}]" cols="45" rows="8" style="width:98%;height:75px;">{$v.value}</textarea>
			{elseif $v.type == 'C'}
			<label><input type="radio" name="values[{$k}]" value="1" {if !empty($v.value)}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="values[{$k}]" value="0" {if empty($v.value)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
			{elseif $v.type == 'E'}
			<input type="text" name="values[{$k}]" value="{$v.value|devblocks_date}" size="45" style="width:98%;">
			{else}
			<input type="text" name="values[{$k}]" value="{$v.value}" size="45" style="width:98%;">
			{/if}
		</div>
	{/foreach}
	</div>
</fieldset>

<div>
	<button type="button" onclick="genericAjaxPost('frmBehaviorSimulator{$trigger->id}','divBehaviorSimulatorResults{$trigger->id}','');"><span class="cerb-sprite2 sprite-gear"></span> Simulate</button>
</div>

<div id="divBehaviorSimulatorResults{$trigger->id}" style="padding:5px;"></div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('simulate_behavior');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Simulate: {$trigger->title}");
	});
</script>
