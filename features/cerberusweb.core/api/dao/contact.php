<?php
class DAO_Contact extends Cerb_ORMHelper {
	const ID = 'id';
	const PRIMARY_EMAIL_ID = 'primary_email_id';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const TITLE = 'title';
	const ORG_ID = 'org_id';
	const USERNAME = 'username';
	const GENDER = 'gender';
	const DOB = 'dob';
	const LOCATION = 'location';
	const PHONE = 'phone';
	const MOBILE = 'mobile';
	const AUTH_SALT = 'auth_salt';
	const AUTH_PASSWORD = 'auth_password';
	const CREATED_AT = 'created_at';
	const UPDATED_AT = 'updated_at';
	const LAST_LOGIN_AT = 'last_login_at';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();
		
		$sql = "INSERT INTO contact () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_CONTACT, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'contact', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.contact.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_CONTACT, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('contact', $fields, $where);
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		$tables = DevblocksPlatform::getDatabaseTables();
		
		// Search indexes
		if(isset($tables['fulltext_contact'])) {
			$db->ExecuteMaster("DELETE FROM fulltext_contact WHERE id NOT IN (SELECT id FROM contact)");
			$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' fulltext_contact records.');
		}
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_CONTACT,
					'context_table' => 'contact',
					'context_key' => 'id',
				)
			)
		);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Contact[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, primary_email_id, first_name, last_name, title, org_id, username, gender, dob, location, phone, mobile, auth_salt, auth_password, created_at, updated_at, last_login_at ".
			"FROM contact ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_Contact
[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::getCacheService();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Contact::ID, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
			//$cache->save($buckets, self::_CACHE_ALL);
		//}
		
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_Contact
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_Contact[]
	 */
	static function getIds($ids) {
		if(!is_array($ids))
			$ids = array($ids);

		if(empty($ids))
			return array();

		if(!method_exists(get_called_class(), 'getWhere'))
			return array();

		$db = DevblocksPlatform::getDatabaseService();

		$ids = DevblocksPlatform::importVar($ids, 'array:integer');

		$models = array();

		$results = static::getWhere(sprintf("id IN (%s)",
			implode(',', $ids)
		));

		// Sort $models in the same order as $ids
		foreach($ids as $id) {
			if(isset($results[$id]))
				$models[$id] = $results[$id];
		}

		unset($results);

		return $models;
	}	
	
	/**
	 * @param resource $rs
	 * @return Model_Contact[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Contact();
			$object->id = $row['id'];
			$object->primary_email_id = intval($row['primary_email_id']);
			$object->first_name = $row['first_name'];
			$object->last_name = $row['last_name'];
			$object->title = $row['title'];
			$object->org_id = intval($row['org_id']);
			$object->username = $row['username'];
			$object->gender = $row['gender'];
			$object->dob = $row['dob'];
			$object->location = $row['location'];
			$object->phone = $row['phone'];
			$object->mobile = $row['mobile'];
			$object->auth_salt = $row['auth_salt'];
			$object->auth_password = $row['auth_password'];
			$object->created_at = intval($row['created_at']);
			$object->updated_at = intval($row['updated_at']);
			$object->last_login_at = intval($row['last_login_at']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function countByOrgId($org_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT count(id) FROM contact WHERE org_id = %d",
			$org_id
		);
		return intval($db->GetOneSlave($sql));
	}
	
	static function random() {
		return self::_getRandom('contact');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Clear address foreign keys to these contacts
		$db->ExecuteMaster(sprintf("UPDATE address SET contact_id = 0 WHERE contact_id IN (%s)", $ids_list));
		
		$db->ExecuteMaster(sprintf("DELETE FROM contact WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => 'cerberusweb.contexts.contact',
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Contact::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"contact.id as %s, ".
			"contact.primary_email_id as %s, ".
			"contact.first_name as %s, ".
			"contact.last_name as %s, ".
			"contact.title as %s, ".
			"contact.org_id as %s, ".
			"contact.username as %s, ".
			"contact.gender as %s, ".
			"contact.dob as %s, ".
			"contact.location as %s, ".
			"contact.phone as %s, ".
			"contact.mobile as %s, ".
			"contact.auth_salt as %s, ".
			"contact.auth_password as %s, ".
			"contact.created_at as %s, ".
			"contact.updated_at as %s, ".
			"contact.last_login_at as %s ",
				SearchFields_Contact::ID,
				SearchFields_Contact::PRIMARY_EMAIL_ID,
				SearchFields_Contact::FIRST_NAME,
				SearchFields_Contact::LAST_NAME,
				SearchFields_Contact::TITLE,
				SearchFields_Contact::ORG_ID,
				SearchFields_Contact::USERNAME,
				SearchFields_Contact::GENDER,
				SearchFields_Contact::DOB,
				SearchFields_Contact::LOCATION,
				SearchFields_Contact::PHONE,
				SearchFields_Contact::MOBILE,
				SearchFields_Contact::AUTH_SALT,
				SearchFields_Contact::AUTH_PASSWORD,
				SearchFields_Contact::CREATED_AT,
				SearchFields_Contact::UPDATED_AT,
				SearchFields_Contact::LAST_LOGIN_AT
			);
			
		$join_sql = "FROM contact ".
			(isset($tables['address']) ? "INNER JOIN address ON (address.id=contact.primary_email_id) " : '').
			(isset($tables['contact_org']) ? sprintf("INNER JOIN contact_org ON (contact_org.id = contact.org_id) ") : " ").
			(isset($tables['context_link']) ? sprintf("INNER JOIN context_link ON (context_link.to_context = %s AND context_link.to_context_id = contact.id) ", Cerb_ORMHelper::qstr('cerberusweb.contexts.contact')) : " ").
			'';
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'contact.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
	
		array_walk_recursive(
			$params,
			array('DAO_Contact', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'contact',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = 'cerberusweb.contexts.contact';
		$from_index = 'contact.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Contact::FULLTEXT_CONTACT:
				$search = Extension_DevblocksSearchSchema::get(Search_Contact::ID);
				$query = $search->getQueryFromParam($param);
				
				if(false === ($ids = $search->query($query, array()))) {
					$args['where_sql'] .= 'AND 0 ';
					
				} else if(is_array($ids)) {
					if(empty($ids))
						$ids = array(-1);
					
					$args['where_sql'] .= sprintf('AND contact.id IN (%s) ',
						implode(', ', $ids)
					);
					
				} elseif(is_string($ids)) {
					$args['join_sql'] .= sprintf("INNER JOIN %s ON (%s.id=contact.id) ",
						$ids,
						$ids
					);
				}
				break;
			
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
		
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY contact.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
		} else {
			$rs = $db->ExecuteSlave($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Contact::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT contact.id) " : "SELECT COUNT(contact.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_Contact implements IDevblocksSearchFields {
	const ID = 'c_id';
	const PRIMARY_EMAIL_ID = 'c_primary_email_id';
	const FIRST_NAME = 'c_first_name';
	const LAST_NAME = 'c_last_name';
	const TITLE = 'c_title';
	const ORG_ID = 'c_org_id';
	const USERNAME = 'c_username';
	const GENDER = 'c_gender';
	const DOB = 'c_dob';
	const LOCATION = 'c_location';
	const PHONE = 'c_phone';
	const MOBILE = 'c_mobile';
	const AUTH_SALT = 'c_auth_salt';
	const AUTH_PASSWORD = 'c_auth_password';
	const CREATED_AT = 'c_created_at';
	const UPDATED_AT = 'c_updated_at';
	const LAST_LOGIN_AT = 'c_last_login_at';
	
	const PRIMARY_EMAIL_ADDRESS = 'a_email_address';
	
	const ORG_NAME = 'o_name';
	
	const FULLTEXT_CONTACT = 'ft_contact';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'contact', 'id', $translate->_('common.id')),
			self::PRIMARY_EMAIL_ID => new DevblocksSearchField(self::PRIMARY_EMAIL_ID, 'contact', 'primary_email_id', $translate->_('common.email')),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'contact', 'first_name', $translate->_('common.name.first')),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'contact', 'last_name', $translate->_('common.name.last')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'contact', 'title', $translate->_('common.title')),
			self::ORG_ID => new DevblocksSearchField(self::ORG_ID, 'contact', 'org_id', $translate->_('common.organization')),
			self::USERNAME => new DevblocksSearchField(self::USERNAME, 'contact', 'username', $translate->_('common.username')),
			self::GENDER => new DevblocksSearchField(self::GENDER, 'contact', 'gender', $translate->_('common.gender')),
			self::DOB => new DevblocksSearchField(self::DOB, 'contact', 'dob', $translate->_('common.dob.abbr')),
			self::LOCATION => new DevblocksSearchField(self::LOCATION, 'contact', 'location', $translate->_('common.location')),
			self::PHONE => new DevblocksSearchField(self::PHONE, 'contact', 'phone', $translate->_('common.phone')),
			self::MOBILE => new DevblocksSearchField(self::MOBILE, 'contact', 'mobile', $translate->_('common.mobile')),
			self::AUTH_SALT => new DevblocksSearchField(self::AUTH_SALT, 'contact', 'auth_salt', null),
			self::AUTH_PASSWORD => new DevblocksSearchField(self::AUTH_PASSWORD, 'contact', 'auth_password', null),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'contact', 'created_at', $translate->_('common.created')),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'contact', 'updated_at', $translate->_('common.updated')),
			self::LAST_LOGIN_AT => new DevblocksSearchField(self::LAST_LOGIN_AT, 'contact', 'last_login_at', $translate->_('common.last_login')),
				
			self::PRIMARY_EMAIL_ADDRESS => new DevblocksSearchField(self::PRIMARY_EMAIL_ADDRESS, 'address', 'email', $translate->_('common.email')),
			self::ORG_NAME => new DevblocksSearchField(self::ORG_NAME, 'contact_org', 'name', $translate->_('common.organization')),

			self::FULLTEXT_CONTACT => new DevblocksSearchField(self::FULLTEXT_CONTACT, 'ft', 'contact', $translate->_('common.search.fulltext'), 'FT'),
				
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_CONTACT]->ft_schema = Search_Contact::ID;
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			'cerberusweb.contexts.contact',
		));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Search_Contact extends Extension_DevblocksSearchSchema {
	const ID = 'cerb.search.schema.contact';
	
	public function getNamespace() {
		return 'contact';
	}
	
	public function getAttributes() {
		return array();
	}
	
	public function query($query, $attributes=array(), $limit=500) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ids = $engine->query($this, $query, $attributes, $limit);
		
		return $ids;
	}
	
	public function reindex() {
		$engine = $this->getEngine();
		$meta = $engine->getIndexMeta($this);
		
		// If the index has a delta, start from the current record
		if($meta['is_indexed_externally']) {
			// Do nothing (let the remote tool update the DB)
			
		// Otherwise, start over
		} else {
			$this->setIndexPointer(self::INDEX_POINTER_RESET);
		}
	}
	
	public function setIndexPointer($pointer) {
		switch($pointer) {
			case self::INDEX_POINTER_RESET:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', 0);
				break;
				
			case self::INDEX_POINTER_CURRENT:
				$this->setParam('last_indexed_id', 0);
				$this->setParam('last_indexed_time', time());
				break;
		}
	}
	
	public function index($stop_time=null) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		if(false == ($engine = $this->getEngine()))
			return false;
		
		$ns = self::getNamespace();
		$id = $this->getParam('last_indexed_id', 0);
		$ptr_time = $this->getParam('last_indexed_time', 0);
		$ptr_id = $id;
		$done = false;

		while(!$done && time() < $stop_time) {
			$where = sprintf('(%s = %d AND %s > %d) OR (%s > %d)',
				DAO_Contact::UPDATED_AT,
				$ptr_time,
				DAO_Contact::ID,
				$id,
				DAO_Contact::UPDATED_AT,
				$ptr_time
			);
			$contacts = DAO_Contact::getWhere($where, array(DAO_Contact::UPDATED_AT, DAO_Contact::ID), array(true, true), 100);

			if(empty($contacts)) {
				$done = true;
				continue;
			}
			
			$last_time = $ptr_time;
			
			foreach($contacts as $contact) { /* @var $contact Model_Contact */
				$id = $contact->id;
				$ptr_time = $contact->updated_at;
				
				$ptr_id = ($last_time == $ptr_time) ? $id : 0;
				
				$logger->info(sprintf("[Search] Indexing %s %d...",
					$ns,
					$id
				));
				
				$doc = array(
					'firstName' => $contact->first_name,
					'lastName' => $contact->last_name,
					'location' => $contact->location,
					'phone' => $contact->phone,
					'mobile' => $contact->mobile,
					'gender' => $contact->gender,
					'emails' => array(),
				);
				
				if(false != ($org = $contact->getOrg()))
					$doc['org'] = $org->name;
				
				foreach($contact->getEmails() as $addy) {
					$doc['emails'][] = $addy->email;
				}
				
				if(false === ($engine->index($this, $id, $doc)))
					return false;
				
				flush();
			}
		}
		
		// If we ran out of records, always reset the ID and use the current time
		if($done) {
			$ptr_id = 0;
			$ptr_time = time();
		}
		
		$this->setParam('last_indexed_id', $ptr_id);
		$this->setParam('last_indexed_time', $ptr_time);
	}
	
	public function delete($ids) {
		if(false == ($engine = $this->getEngine()))
			return false;
		
		return $engine->delete($this, $ids);
	}
};

class Model_Contact {
	public $id;
	public $primary_email_id;
	public $first_name;
	public $last_name;
	public $title;
	public $org_id;
	public $username;
	public $gender;
	public $dob;
	public $location;
	public $phone;
	public $mobile;
	public $auth_salt;
	public $auth_password;
	public $created_at;
	public $updated_at;
	public $last_login_at;
	
	function getName() {
		return sprintf("%s%s%s",
			$this->first_name,
			(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
			$this->last_name
		);
	}
	
	function getNameWithEmail() {
		$name = $this->getName();
		
		if(false == ($addy = DAO_Address::get($this->primary_email_id)))
			return $name;

		if(!empty($name))
			$name .= ' <' . $addy->email . '>';
		else
			$name = $addy->email;
		
		return $name;
	}
	
	function getOrg() {
		if(empty($this->org_id))
			return null;
		
		return DAO_ContactOrg::get($this->org_id);
	}
	
	// Primary
	function getEmail() {
		if(empty($this->primary_email_id))
			return null;
		
		return DAO_Address::get($this->primary_email_id);
	}
	
	/**
	 * Primary plus alternates
	 * 
	 * @return Model_Address[]
	 */
	function getEmails() {
		return DAO_Address::getWhere(
			sprintf("%s = %d",
				DAO_Address::CONTACT_ID,
				$this->id
			)
		);
	}
};

class View_Contact extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'contact';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('common.contacts');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Contact::UPDATED_AT;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Contact::PRIMARY_EMAIL_ID,
			SearchFields_Contact::TITLE,
			SearchFields_Contact::ORG_ID,
			SearchFields_Contact::USERNAME,
			SearchFields_Contact::GENDER,
			SearchFields_Contact::LOCATION,
			SearchFields_Contact::UPDATED_AT,
			SearchFields_Contact::LAST_LOGIN_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_Contact::ORG_NAME,
			SearchFields_Contact::AUTH_SALT,
			SearchFields_Contact::AUTH_PASSWORD,
			SearchFields_Contact::PRIMARY_EMAIL_ADDRESS,
			SearchFields_Contact::FULLTEXT_CONTACT,
			SearchFields_Contact::VIRTUAL_CONTEXT_LINK,
			SearchFields_Contact::VIRTUAL_HAS_FIELDSET,
			SearchFields_Contact::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Contact::ORG_ID,
			SearchFields_Contact::PRIMARY_EMAIL_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Contact::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Contact', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Contact', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
//				case SearchFields_Contact::EXAMPLE:
//					$pass = true;
//					break;
					
				// Virtuals
				case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Contact::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
//			case SearchFields_Contact::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Contact', $column);
//				break;

//			case SearchFields_Contact::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn('DAO_Contact', $column);
//				break;
				
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_Contact', 'cerberusweb.contexts.contact', $column);
				break;

			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_Contact', 'cerberusweb.contexts.contact', $column);
				break;
				
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Contact', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Contact', $column, 'contact.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Contact::FULLTEXT_CONTACT),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Contact::CREATED_AT),
				),
			'email' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::PRIMARY_EMAIL_ADDRESS, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'email.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Contact::PRIMARY_EMAIL_ID),
				),
			'firstName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::FIRST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'lastName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Contact::LAST_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'org.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Contact::ORG_ID),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Contact::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Contact::VIRTUAL_WATCHERS),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext('cerberusweb.contexts.contact', $fields, null);
		
		// Engine/schema examples: Fulltext
		
		$ft_examples = array();
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_Contact::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['_fulltext']['examples'] = $ft_examples;
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				// ...
			}
		}
		
		$this->renderPage = 0;
		$this->addParams($params, true);
		
		return $params;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.contact');
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/contact/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Contact::FIRST_NAME:
			case SearchFields_Contact::LAST_NAME:
			case SearchFields_Contact::TITLE:
			case SearchFields_Contact::USERNAME:
			case SearchFields_Contact::GENDER:
			case SearchFields_Contact::DOB:
			case SearchFields_Contact::LOCATION:
			case SearchFields_Contact::ORG_NAME:
			case SearchFields_Contact::PHONE:
			case SearchFields_Contact::PRIMARY_EMAIL_ADDRESS:
			case SearchFields_Contact::MOBILE:
			case SearchFields_Contact::AUTH_SALT:
			case SearchFields_Contact::AUTH_PASSWORD:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Contact::ID:
			case SearchFields_Contact::PRIMARY_EMAIL_ID:
			case SearchFields_Contact::ORG_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Contact::CREATED_AT:
			case SearchFields_Contact::UPDATED_AT:
			case SearchFields_Contact::LAST_LOGIN_AT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Contact::FULLTEXT_CONTACT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, 'cerberusweb.contexts.contact');
				break;
				
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Contact::ORG_ID:
				$string = null;
				
				if(empty($param->value)) {
					$string = '(blank)';
				} else if(false != ($org = DAO_ContactOrg::get($param->value))) {
					$string = $org->name;
				}
				
				echo sprintf("<b>%s</b>", DevblocksPlatform::strEscapeHtml($string));
				break;
				
			case SearchFields_Contact::PRIMARY_EMAIL_ID:
				$string = null;
				
				if(empty($param->value)) {
					$string = '(blank)';
				} else if(false != ($addy = DAO_Address::get($param->value))) {
					$string = $addy->email;
				}
				
				echo sprintf("<b>%s</b>", DevblocksPlatform::strEscapeHtml($string));
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Contact::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Contact::FIRST_NAME:
			case SearchFields_Contact::LAST_NAME:
			case SearchFields_Contact::TITLE:
			case SearchFields_Contact::USERNAME:
			case SearchFields_Contact::GENDER:
			case SearchFields_Contact::DOB:
			case SearchFields_Contact::LOCATION:
			case SearchFields_Contact::ORG_NAME:
			case SearchFields_Contact::PHONE:
			case SearchFields_Contact::PRIMARY_EMAIL_ADDRESS:
			case SearchFields_Contact::MOBILE:
			case SearchFields_Contact::AUTH_SALT:
			case SearchFields_Contact::AUTH_PASSWORD:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Contact::ID:
			case SearchFields_Contact::PRIMARY_EMAIL_ID:
			case SearchFields_Contact::ORG_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Contact::CREATED_AT:
			case SearchFields_Contact::UPDATED_AT:
			case SearchFields_Contact::LAST_LOGIN_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Contact::FULLTEXT_CONTACT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
				break;
				
			case SearchFields_Contact::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Contact::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Contact::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Contact::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Contact::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_Contact::update($batch_ids, $change_fields);
			}

			// Custom Fields
			self::_doBulkSetCustomFields('cerberusweb.contexts.contact', $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Contact extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek { // IDevblocksContextImport
	function getRandom() {
		return DAO_Contact::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=contact&id='.$context_id, true);
		return $url;
	}
	
	function getMeta($context_id) {
		$contact = DAO_Contact::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($contact->getName());
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $contact->id,
			'name' => $contact->getName(),
			'permalink' => $url,
			'updated' => $contact->updated_at,
		);
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($contact, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Contact:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext('cerberusweb.contexts.contact');

		// Polymorph
		if(is_numeric($contact)) {
			$contact = DAO_Contact::get($contact);
		} elseif($contact instanceof Model_Contact) {
			// It's what we want already.
		} elseif(is_array($contact)) {
			$contact = Cerb_ORMHelper::recastArrayToModel($contact, 'Model_Contact');
		} else {
			$contact = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'first_name' => $prefix.$translate->_('common.name.first'),
			'last_name' => $prefix.$translate->_('common.name.last'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = 'cerberusweb.contexts.contact';
		$token_values['_types'] = $token_types;
		
		if($contact) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $contact->getName();
			$token_values['id'] = $contact->id;
			$token_values['first_name'] = $contact->first_name;
			$token_values['last_name'] = $contact->last_name;
			$token_values['name'] = $contact->getName();
			$token_values['updated_at'] = $contact->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($contact, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=contact&id=%d-%s",$contact->id, DevblocksPlatform::strToPermalink($contact->getName())), true);
		}
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = 'cerberusweb.contexts.contact';
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Contact';
		/*
		$view->addParams(array(
			SearchFields_Contact::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Contact::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_Contact::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Contact';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Contact::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Contact::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(!empty($context_id) && null != ($contact = DAO_Contact::get($context_id))) {
			$tpl->assign('model', $contact);
		}
		
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.contact', false);
		$tpl->assign('custom_fields', $custom_fields);
		
		if(!empty($context_id)) {
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds('cerberusweb.contexts.contact', $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		}
		
		if(empty($context_id) || $edit) {
			$tpl->display('devblocks:cerberusweb.core::internal/contact/peek_edit.tpl');
		} else {
			$activity_counts = array(
				'comments' => DAO_Comment::count(CerberusContexts::CONTEXT_CONTACT, $context_id),
				'emails' => DAO_Address::countByContactId($context_id),
				'links' => DAO_ContextLink::count(CerberusContexts::CONTEXT_CONTACT, $context_id),
				'tickets' => DAO_Ticket::countsByContactId($context_id),
			);
			$tpl->assign('activity_counts', $activity_counts);
			
			$links = array(
				CerberusContexts::CONTEXT_CONTACT => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							CerberusContexts::CONTEXT_CONTACT,
							$context_id,
							array(CerberusContexts::CONTEXT_WORKER, CerberusContexts::CONTEXT_CUSTOM_FIELDSET)
						),
				),
			);
			
			$tpl->assign('links', $links);
			
			$tpl->display('devblocks:cerberusweb.core::internal/contact/peek.tpl');
		}
	}
};