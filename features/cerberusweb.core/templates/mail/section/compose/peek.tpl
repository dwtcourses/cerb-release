{$mail_reply_html = DAO_WorkerPref::get($active_worker->id, 'mail_reply_html', 0)}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmComposePeek{$popup_uniqid}" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveComposePeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="draft_id" value="{$draft->id}">
<input type="hidden" name="format" value="{if ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}parsedown{/if}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.message'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right"><b>From:</b>&nbsp;</td>
			<td width="100%">
				<select name="group_id">
					{foreach from=$groups item=group key=group_id}
					{if $active_worker->isGroupMember($group_id)}
					<option value="{$group_id}" member="true" {if $defaults.group_id == $group_id}selected="selected"{/if}>{$group->name}</option>
					{/if}
					{/foreach}
				</select>
				<select class="ticket-peek-bucket-options" style="display:none;">
					{foreach from=$buckets item=bucket key=bucket_id}
					<option value="{$bucket_id}" group_id="{$bucket->group_id}">{$bucket->name}</option>
					{/foreach}
				</select>
				<select name="bucket_id">
					{foreach from=$buckets item=bucket key=bucket_id}
						{if $bucket->group_id == $defaults.group_id}
						<option value="{$bucket_id}" {if $defaults.bucket_id == $bucket_id}selected="selected"{/if}>{$bucket->name}</option>
						{/if}
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.organization'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="org_name" value="{if !empty($org)}{$org}{else}{$draft->params.org_name}{/if}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" placeholder="(optional) Link this ticket to an organization for suggested recipients">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.to'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="to" id="emailinput{$popup_uniqid}" value="{if !empty($to)}{$to}{else}{$draft->params.to}{/if}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" placeholder="These recipients will automatically be included in all future correspondence">
				
				<div id="compose_suggested{$popup_uniqid}" style="display:none;">
					<a href="javascript:;" onclick="$(this).closest('div').hide();">x</a>
					<b>Consider adding these recipients:</b>
					<ul class="bubbles"></ul> 
				</div>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.cc'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="cc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.cc}" placeholder="These recipients will publicly receive a copy of this message" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'message.header.bcc'|devblocks_translate|capitalize}:&nbsp;</td>
			<td width="100%">
				<input type="text" name="bcc" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->params.bcc}" placeholder="These recipients will secretly receive a copy of this message" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b>&nbsp;</td>
			<td width="100%">
				<input type="text" name="subject" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$draft->subject}" autocomplete="off" required>
			</td>
		</tr>
		<tr>
			<td width="100%" colspan="2">
				<div id="divDraftStatus{$popup_uniqid}"></div>
				
				<div>
					<fieldset style="display:inline-block;">
						<legend>Actions</legend>
						
						<div id="divComposeInteractions{$popup_uniqid}" style="display:inline-block;">
						{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.tpl"}
						</div>
						
						<button id="btnComposeSaveDraft{$popup_uniqid}" class="toolbar-item" type="button"><span class="glyphicons glyphicons-circle-ok"></span> Save Draft</button>
						<button id="btnComposeInsertSig{$popup_uniqid}" class="toolbar-item" type="button" {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+G)"{/if}"><span class="glyphicons glyphicons-edit"></span> Insert Signature</button>
					</fieldset>
				
					<fieldset style="display:inline-block;">
						<legend>{'common.snippets'|devblocks_translate|capitalize}</legend>
						<div>
							Insert: 
							<input type="text" size="25" class="context-snippet autocomplete" {if $pref_keyboard_shortcuts}placeholder="(Ctrl+Shift+I)"{/if}>
							<button type="button" onclick="ajax.chooserSnippet('snippets',$('#divComposeContent{$popup_uniqid}'), { '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });"><span class="glyphicons glyphicons-search"></span></button>
							<button type="button" onclick="var txt = encodeURIComponent($('#divComposeContent{$popup_uniqid}').selection('get')); genericAjaxPopup('add_snippet','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_SNIPPET}&context_id=0&edit=1&text=' + txt,null,false,'50%');"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
					</fieldset>
				</div>
				
				<textarea id="divComposeContent{$popup_uniqid}" name="content" style="width:98%;height:150px;border:1px solid rgb(180,180,180);padding:2px;">{if !empty($draft)}{$draft->body}{else}{if $defaults.signature_pos}



#signature
#cut{/if}{/if}</textarea>

				<b>(Use #commands to perform additional actions)</b>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<div>
		<label>
		<input type="checkbox" name="options_dont_send" value="1" {if $draft->params.options_dont_send}checked="checked"{/if}> 
		Start a new conversation without sending a copy of this message to the recipients
		</label>
	</div>
	
	<div style="margin-top:10px;">
		<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+O)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_OPEN}" class="status_open" {if (empty($draft) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_OPEN)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','none');"> {'status.open'|devblocks_translate}</label>
		<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+W)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_WAITING}" class="status_waiting" {if (empty($draft) && 'waiting'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_WAITING)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','block');"> {'status.waiting'|devblocks_translate}</label>
		{if $active_worker->hasPriv('core.ticket.actions.close')}<label {if $pref_keyboard_shortcuts}title="(Ctrl+Shift+C)"{/if}><input type="radio" name="status_id" value="{Model_Ticket::STATUS_CLOSED}" class="status_closed" {if (empty($draft) && 'closed'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_CLOSED)}checked="checked"{/if} onclick="toggleDiv('divComposeClosed{$popup_uniqid}','block');"> {'status.closed'|devblocks_translate}</label>{/if}
		
		<div id="divComposeClosed{$popup_uniqid}" style="display:{if (empty($draft) && 'open'==$defaults.status) || (!empty($draft) && $draft->params.status_id==Model_Ticket::STATUS_OPEN)}none{else}block{/if};margin-top:5px;margin-left:10px;">
			<b>{'display.reply.next.resume'|devblocks_translate}</b><br>
			{'display.reply.next.resume_eg'|devblocks_translate}<br> 
			<input type="text" name="ticket_reopen" size="64" class="input_date" value="{$draft->params.ticket_reopen}"><br>
			{'display.reply.next.resume_blank'|devblocks_translate}<br>
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Assignments</legend>
	
	<table cellpadding="0" cellspacing="0" width="100%" border="0">
		<tr>
			<td width="1%" nowrap="nowrap" style="padding-right:10px;" valign="top">
				{'common.owner'|devblocks_translate|capitalize}:
			</td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-field-name="owner_id" data-autocomplete="" data-autocomplete-if-empty="true" data-single="true"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container">
					{foreach from=$workers item=v key=k}
					{if !$v->is_disabled && $draft->params.owner_id == $v->id}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$v->id}{/devblocks_url}?v={$v->updated}"><input type="hidden" name="owner_id" value="{$v->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$v->id}">{$v->getName()}</a></li>
					{/if}
					{/foreach}
				</ul>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" style="padding-right:10px;" valign="top">
				{'common.watchers'|devblocks_translate|capitalize}:
			</td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-field-name="add_watcher_ids[]" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container" style="display:block;">
					{if $draft->params.add_watcher_ids && is_array($draft->params.add_watcher_ids)}
					{foreach from=$draft->params.add_watcher_ids item=watcher_id}
						{$watcher = DAO_Worker::get($watcher_id)}
						{if $watcher}
						<li>
							<input type="hidden" name="add_watcher_ids[]" value="{$watcher_id}">
							{$watcher->getName()}
							<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
						</li>
						{/if}
					{/foreach}
					{/if}
				</ul>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek" style="{if empty($custom_fields) && empty($group_fields)}display:none;{/if}" id="compose_cfields{$popup_uniqid}">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>
	
	{$custom_field_values = $draft->params.custom_fields}
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	{/if}
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET bulk=false}

<fieldset class="peek compose-attachments">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="bubbles chooser-container">
	{if $draft->params.file_ids}
	{foreach from=$draft->params.file_ids item=file_id}
		{$file = DAO_Attachment::get($file_id)}
		{if !empty($file)}
			<li><input type="hidden" name="file_ids[]" value="{$file_id}">{$file->name} ({$file->storage_size} bytes) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
		{/if} 
	{/foreach}
	{/if}
	</ul>
</fieldset>

<div class="status"></div>

<button type="button" class="submit" title="{if $pref_keyboard_shortcuts}(Ctrl+Shift+Enter){/if}"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'display.ui.send_message'|devblocks_translate}</button>
</form>

<script type="text/javascript">
	if(draftComposeAutoSaveInterval == undefined)
		var draftComposeAutoSaveInterval = null;

	var $popup = genericAjaxPopupFind('#frmComposePeek{$popup_uniqid}');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{'mail.send_mail'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		
		var $frm = $('#frmComposePeek{$popup_uniqid}');

		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=to]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=cc]', { multiple: true } );
		ajax.emailAutoComplete('#frmComposePeek{$popup_uniqid} input[name=bcc]', { multiple: true } );

		ajax.orgAutoComplete('#frmComposePeek{$popup_uniqid} input:text[name=org_name]');
		
		$frm.find('button.chooser-abstract').cerbChooserTrigger();
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// Drag/drop attachments
		
		var $attachments = $frm.find('fieldset.compose-attachments');
		$attachments.cerbAttachmentsDropZone();
		
		// Text editor
		
		var $content = $frm.find('textarea[name=content]');
		
		var markitupPlaintextSettings = $.extend(true, { }, markitupPlaintextDefaults);
		var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);
		
		var markitupReplyFunctions = {
			switchToMarkdown: function(markItUp) { 
				$content.markItUpRemove().markItUp(markitupParsedownSettings);
				{if !empty($mail_reply_textbox_size_auto)}
				$content.autosize();
				{/if}
				$content.closest('form').find('input:hidden[name=format]').val('parsedown');

				// Template chooser
				
				var $ul = $content.closest('.markItUpContainer').find('.markItUpHeader UL');
				var $li = $('<li style="margin-left:10px;"></li>');
				
				var $select = $('<select name="html_template_id"></select>');
				$select.append($('<option value="0"/>').text(' - {'common.default'|devblocks_translate|lower|escape:'javascript'} -'));
				
				{foreach from=$html_templates item=html_template}
				var $option = $('<option/>').attr('value','{$html_template->id}').text('{$html_template->name|escape:'javascript'}');
				{if $draft && $draft->params.html_template_id == $html_template->id}
				$option.attr('selected', 'selected');
				{/if}
				$select.append($option);
				{/foreach}
				
				$li.append($select);
				$ul.append($li);
			},
			
			switchToPlaintext: function(markItUp) { 
				$content.markItUpRemove().markItUp(markitupPlaintextSettings);
				{if !empty($mail_reply_textbox_size_auto)}
				$content.autosize();
				{/if}
				$content.closest('form').find('input:hidden[name=format]').val('');
			}
		};
		
		markitupPlaintextSettings.markupSet.unshift(
			{ name:'Switch to Markdown', openWith: markitupReplyFunctions.switchToMarkdown, className:'parsedown' },
			{ separator:' ' },
			{ name:'Preview', key: 'P', call:'preview', className:"preview" }
		);
		
		markitupPlaintextSettings.previewAutoRefresh = true;
		markitupPlaintextSettings.previewInWindow = 'width=800, height=600, titlebar=no, location=no, menubar=no, status=no, toolbar=no, resizable=yes, scrollbars=yes';
		
		markitupPlaintextSettings.previewParser = function(content) {
			genericAjaxPost(
				$frm,
				'',
				'c=display&a=getReplyPreview',
				function(o) {
					content = o;
				}
			);
			
			return content;
		};
		
		markitupParsedownSettings.previewParser = function(content) {
			genericAjaxPost(
				'frmComposePeek{$popup_uniqid}',
				'',
				'c=display&a=getReplyMarkdownPreview',
				function(o) {
					content = o;
				}
			);
			
			return content;
		};
		
		markitupParsedownSettings.markupSet.unshift(
			{ name:'Switch to Plaintext', openWith: markitupReplyFunctions.switchToPlaintext, className:'plaintext' },
			{ separator:' ' }
		);
		
		markitupParsedownSettings.markupSet.splice(
			6,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");
					});
				},
				key: 'U',
				className:'image-inline'
			}
			//{ separator:' ' }
		);
		
		try {
			$content.markItUp(markitupPlaintextSettings);
			
			{if ($draft && $draft->params.format == 'parsedown') || $mail_reply_html}
			markitupReplyFunctions.switchToMarkdown();
			{/if}
			
			$content.autosize();
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
		
		$frm.validate();
		
		// @who and #command
		
		var atwho_file_bundles = {CerberusApplication::getFileBundleDictionaryJson() nofilter};
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};
		
		$content
			.atwho({
				at: '#attach ',
				{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${tag}</small></li>',{/literal}
				{literal}insertTpl: '#attach ${tag}\n',{/literal}
				suffix: '',
				data: atwho_file_bundles,
				limit: 10
			})
			.atwho({
				at: '#',
				data: [
					'attach ',
					'comment',
					'comment @',
					'cut\n',
					'signature\n',
					'unwatch\n',
					'watch\n'
				],
				limit: 10,
				suffix: '',
				hide_without_suffix: true,
				callbacks: {
					before_insert: function(value, $li) {
						if(value.substr(-1) != '\n' && value.substr(-1) != '@')
							value += ' ';
						
						return value;
					}
				}
			})
			.atwho({
				at: '@',
				{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
				{literal}insertTpl: '@${at_mention}',{/literal}
				data: atwho_workers,
				searchKey: '_index',
				limit: 10
			})
			;
		
		$content.on('inserted.atwho', function(event, $li) {
			//if($li.text() == 'delete quote from here\n')
			//	$(this).trigger('delete_quote_from_cursor');
		});
		
		// Group and bucket
		$frm.find('select[name=group_id]').on('change', function(e) {
			var $select = $(this);
			var group_id = $select.val();
			var $bucket_options = $select.siblings('select.ticket-peek-bucket-options').find('option')
			var $bucket = $select.siblings('select[name=bucket_id]');
			
			$bucket.children().remove();
			
			$bucket_options.each(function() {
				var parent_id = $(this).attr('group_id');
				if(parent_id == '*' || parent_id == group_id)
					$(this).clone().appendTo($bucket);
			});
			
			$bucket.focus();
		});
		
		$frm.find('input:text[name=to], input:text[name=cc], input:text[name=bcc]').focus(function(event) {
			$('#compose_suggested{$popup_uniqid}').appendTo($(this).closest('td'));
		});
		
		$frm.find('input:text[name=org_name]').bind('autocompletechange',function(event, ui) {
			genericAjaxGet('', 'c=contacts&a=getTopContactsByOrgJson&org_name=' + $(this).val(), function(json) {
				var $sug = $('#compose_suggested{$popup_uniqid}');
				
				$sug.find('ul.bubbles li').remove();
				
				if(0 == json.length) {
					$sug.hide();
					return;
				}
				
				for(i in json) {
					var label = '';
					if(null != json[i].name && json[i].name.length > 0) {
						label += json[i].name + " ";
						label += "&lt;" + json[i].email + '&gt;';
					} else {
						label += json[i].email;
					}
					
					$sug.find('ul.bubbles').append($("<li><a href=\"javascript:;\" class=\"suggested\">" + label + "</a></li>"));
				}
				
				// Insert suggested on click
				$sug.find('a.suggested').click(function(e) {
					var $this = $(this);
					var $sug = $this.text();
					
					var $to = $this.closest('td').find('input:text:first');
					var $val = $to.val();
					var $len = $val.length;
					
					var $last = null;
					if($len>0)
						$last = $val.substring($len-1);
					
					if(0==$len || $last==' ')
						$to.val($val+$sug);
					else if($last==',')
						$to.val($val + ' '+$sug);
					else $to.val($val + ', '+$sug);
						$to.focus();
					
					var $ul = $this.closest('ul');
					$this.closest('li').remove();
					if(0==$ul.find('li').length)
						$ul.closest('div').remove();
				});
				
				$sug.show();
			});
		});
		
		// Date entry
		
		$frm.find('> fieldset:nth(1) input.input_date').cerbDateInputHelper();
		
		// Insert Sig
		
		$('#btnComposeInsertSig{$popup_uniqid}').click(function(e) {
			var $textarea = $('#divComposeContent{$popup_uniqid}');
			$textarea.insertAtCursor('#signature\n').focus();
		});
		
		// Drafts
		
		$('#btnComposeSaveDraft{$popup_uniqid}').click(function(e) {
			var $this = $(this);
			
			if(!$this.is(':visible')) {
				clearTimeout(draftComposeAutoSaveInterval);
				draftComposeAutoSaveInterval = null;
				return;
			}
			
			if($this.attr('disabled'))
				return;
			
			$this.attr('disabled','disabled');
			
			genericAjaxPost(
				'frmComposePeek{$popup_uniqid}',
				null,
				'c=profiles&a=handleSectionAction&section=draft&action=saveDraft&type=compose',
				function(json) { 
					var obj = $.parseJSON(json);
					
					if(!obj || !obj.html || !obj.draft_id)
						return;
				
					$('#divDraftStatus{$popup_uniqid}').html(obj.html);
					
					$('#frmComposePeek{$popup_uniqid} input[name=draft_id]').val(obj.draft_id);
					
					$('#btnComposeSaveDraft{$popup_uniqid}').removeAttr('disabled');
				}
			);
		});
		
		if(null != draftComposeAutoSaveInterval) {
			clearTimeout(draftComposeAutoSaveInterval);
			draftComposeAutoSaveInterval = null;
		}
		
		draftComposeAutoSaveInterval = setInterval("$('#btnComposeSaveDraft{$popup_uniqid}').click();", 30000); // and every 30 sec
		
		// Snippet chooser shortcut
		
		$frm.find('input:text.context-snippet').autocomplete({
			delay: 300,
			source: DevblocksAppPath+'ajax.php?c=internal&a=autocomplete&context=cerberusweb.contexts.snippet&contexts[]=cerberusweb.contexts.worker&_csrf_token=' + $('meta[name="_csrf_token"]').attr('content'),
			minLength: 1,
			focus:function(event, ui) {
				return false;
			},
			autoFocus:true,
			select:function(event, ui) {
				var $this = $(this);
				var $textarea = $('#divComposeContent{$popup_uniqid}');
				
				var $label = ui.item.label.replace("<","&lt;").replace(">","&gt;");
				var $value = ui.item.value;
				
				// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
				var url = 'c=internal&a=snippetPaste&id=' + $value;

				// Context-dependent arguments
				if ('cerberusweb.contexts.worker'==ui.item.context) {
					url += "&context_id={$active_worker->id}";
				}

				genericAjaxGet('',url,function(json) {
					// If the content has placeholders, use that popup instead
					if(json.has_custom_placeholders) {
						$textarea.focus();
						
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '600');
					
						$popup_paste.bind('snippet_paste', function(event) {
							if(null == event.text)
								return;
						
							$textarea.insertAtCursor(event.text).focus();
						});
						
					} else {
						$textarea.insertAtCursor(json.text).focus();
					}
					
				});

				$this.val('');
				return false;
			}
		});
		
		// Interactions
		var $interaction_container = $('#divComposeInteractions{$popup_uniqid}');
		{include file="devblocks:cerberusweb.core::events/interaction/interactions_menu.js.tpl"}
		
		// Shortcuts
		
		{if $pref_keyboard_shortcuts}
		
		// Reply textbox
		$('#divComposeContent{$popup_uniqid}').keydown(function(event) {
			if(!$(this).is(':focus'))
				return;
			
			if(!event.shiftKey || !event.ctrlKey)
				return;
			
			if(event.which == 16 || event.which == 17)
				return;

			switch(event.which) {
				case 13: // (RETURN) Send message
					try {
						event.preventDefault();
						$frm.find('button.submit').focus();
					} catch(ex) { } 
					break;
				case 67: // (C) Set closed + focus reopen
				case 79: // (O) Set open
				case 87: // (W) Set waiting + focus reopen
					try {
						event.preventDefault();
						
						var $radio = $frm.find('input:radio[name=status_id]');
						
						switch(event.which) {
							case 67: // closed
								$radio.filter('.status_closed').click();
								$frm
									.find('input:text[name=ticket_reopen]')
										.select()
										.focus()
									;
								break;
							case 79: // open
								$radio.filter('.status_open').click().focus();
								break;
							case 87: // waiting
								$radio.filter('.status_waiting').click();
								$frm
									.find('input:text[name=ticket_reopen]')
										.select()
										.focus()
									;
								break;
						}
						
					} catch(ex) {}
					break;
				case 71: // (G) Insert Signature
					try {
						event.preventDefault();
						$('#btnComposeInsertSig{$popup_uniqid}').click();
					} catch(ex) { } 
					break;
				case 73: // (I) Insert Snippet
					try {
						event.preventDefault();
						$('#frmComposePeek{$popup_uniqid}').find('INPUT:text.context-snippet').focus();
					} catch(ex) { } 
					break;
				case 81: // (Q) Reformat quotes
					try {
						event.preventDefault();
						var txt = $(this).val();
						
						var lines = txt.split("\n");
						
						var bins = [];
						var last_prefix = null;
						var wrap_to = 76;
						
						// Sort lines into bins
						for(i in lines) {
							var line = lines[i];
							var matches = line.match(/^((\> )+)/);
							var prefix = '';
							
							if(matches)
								prefix = matches[1];
							
							if(prefix != last_prefix)
								bins.push({ prefix:prefix, lines:[] });
							
							// Strip the prefix
							line = line.substring(prefix.length);
							
							idx = Math.max(bins.length-1, 0);
							bins[idx].lines.push(line);
							
							last_prefix = prefix;
						}
						
						// Rewrap quoted blocks
						for(i in bins) {
							prefix = bins[i].prefix;
							l = 0;
							bail = 75000; // prevent infinite loops
							
							if(prefix.length == 0)
								continue;
							
							while(undefined != bins[i].lines[l] && bail > 0) {
								line = bins[i].lines[l];
								boundary = wrap_to-prefix.length;
								
								if(line.length > boundary) {
									// Try to split on a space
									pos = line.lastIndexOf(' ', boundary);
									break_word = (-1 == pos);
									
									overflow = line.substring(break_word ? boundary : (pos+1));
									bins[i].lines[l] = line.substring(0, break_word ? boundary : pos);
									
									// If we don't have more lines, add a new one
									if(overflow) {
										if(undefined != bins[i].lines[l+1]) {
											if(bins[i].lines[l+1].length == 0) {
												bins[i].lines.splice(l+1,0,overflow);
											} else {
												bins[i].lines[l+1] = overflow + " " + bins[i].lines[l+1];
											}
										} else {
											bins[i].lines.push(overflow);
										}
									}
								}
								
								l++;
								bail--;
							}
						}
						
						out = "";
						
						for(i in bins) {
							for(l in bins[i].lines) {
								out += bins[i].prefix + bins[i].lines[l] + "\n";
							}
						}
						
						$(this).val($.trim(out));
						
					} catch(ex) { }
					break;
			}
		});
		
		{/if}
		
		$frm.find(':input:text:first').focus().select();
		
		$frm.find('button.submit').click(function() {
			var $frm = $(this).closest('form');
			var $input = $frm.find('input#emailinput{$popup_uniqid}');
			var $status = $frm.find('div.status').html('').hide();
			
			var $to = $frm.find('input[name=to]');
			var $cc = $frm.find('input[name=cc]');
			var $bcc = $frm.find('input[name=bcc]');
			
			// If we have a Cc:/Bcc: but no To:
			if($to.val().length == 0 && ($cc.val().length > 0 || $bcc.val().length > 0)) {
				$status.text("A 'To:' address is required when using 'Cc:' and 'Bcc:'.").addClass('error').fadeIn();
				return false;
			}
			
			if($frm.validate().form()) {
				if(null != draftComposeAutoSaveInterval) { 
					clearTimeout(draftComposeAutoSaveInterval);
					draftComposeAutoSaveInterval = null;
				}
				
				genericAjaxPopupPostCloseReloadView(null,'frmComposePeek{$popup_uniqid}','{$view_id}',false,'compose_save');
			}
		});
		
		{if $org}
		$frm.find('input:text[name=org_name]').trigger('autocompletechange');
		{/if}

	});
</script>
