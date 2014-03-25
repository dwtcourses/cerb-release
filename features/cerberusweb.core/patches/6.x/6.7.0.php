<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Set the new default number of columns to 3 on existing dashboard tabs

$params_json = json_encode(array('num_columns' => 3));
$db->Execute(sprintf("UPDATE workspace_tab SET params_json = %s WHERE extension_id = 'core.workspace.tab' AND params_json IS NULL",
	$db->qstr($params_json)
));

// ===========================================================================
// Add `cache_ttl` to `workspace_widget`

if(!isset($tables['workspace_widget'])) {
	$logger->error("The 'workspace_widget' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_widget');

if(!isset($columns['cache_ttl'])) {
	$db->Execute("ALTER TABLE workspace_widget ADD COLUMN cache_ttl MEDIUMINT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute("UPDATE workspace_widget SET cache_ttl = 60");
}

// ===========================================================================
// Add `context` to `fulltext_comment_content`

if(isset($tables['fulltext_comment_content'])) {
	list($columns, $indexes) = $db->metaTable('fulltext_comment_content');

	if(!isset($columns['context'])) {
		$db->Execute("ALTER TABLE fulltext_comment_content ADD COLUMN context VARCHAR(255)");
		$db->Execute("UPDATE fulltext_comment_content INNER JOIN comment ON (fulltext_comment_content.id=comment.id) SET fulltext_comment_content.context = comment.context");
	}
}

// ===========================================================================
// Finish up

return TRUE;