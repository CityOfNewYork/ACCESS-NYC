<?php
class wfDB {
	public $errorMsg = false;
	
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new wfDB();
		}
		return $_shared;
	}
  
  /**
   * Returns the table prefix for the main site on multisites and the site itself on single site installations.
   *
   * @return string
   */
	public static function networkPrefix() {
		global $wpdb;
		return $wpdb->base_prefix;
	}
  
  /**
   * Returns the table with the site (single site installations) or network (multisite) prefix added.
   *
   * @param string $table
   * @param bool $applyCaseConversion Whether or not to convert the table case to what is actually in use.
   * @return string
   */
	public static function networkTable($table, $applyCaseConversion = true) {
		if (wfSchema::usingLowercase() && $applyCaseConversion) {
			$table = strtolower($table);
		}
		return self::networkPrefix() . $table;
	}
  
  /**
   * Returns the table prefix for the given blog ID. On single site installations, this will be equivalent to wfDB::networkPrefix().
   *
   * @param int $blogID
   * @return string
   */
	public static function blogPrefix($blogID) {
	  global $wpdb;
	  return $wpdb->get_blog_prefix($blogID);
	}
  
  /**
   * Returns the table with the site (single site installations) or blog-specific (multisite) prefix added.
   *
   * @param string $table
   * @param bool $applyCaseConversion Whether or not to convert the table case to what is actually in use.
   * @return string
   */
	public static function blogTable($table, $blogID, $applyCaseConversion = true) {
		if (wfSchema::usingLowercase() && $applyCaseConversion) {
			$table = strtolower($table);
		}
	  	return self::blogPrefix($blogID) . $table;
	}
	
	/**
	 * Converts the given value into a MySQL hex string. This is needed because WordPress will run an unnecessary `SHOW
	 * FULL COLUMNS` on every hit where we use non-ASCII data (e.g., packed binary-encoded IP addresses) in queries.
	 * 
	 * @param string $binary
	 * @return string
	 */
	public static function binaryValueToSQLHex($binary) {
		return sprintf("X'%s'", bin2hex($binary));
	}
	
	public function querySingle(){
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->get_var(call_user_func_array(array($wpdb, 'prepare'), $args));
		} else {
			return $wpdb->get_var(func_get_arg(0));
		}
	}
	public function querySingleRec(){ //queryInSprintfFormat, arg1, arg2, ... :: Returns a single assoc-array or null if nothing found.
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->get_row(call_user_func_array(array($wpdb, 'prepare'), $args), ARRAY_A);
		} else {
			return $wpdb->get_row(func_get_arg(0), ARRAY_A);
		}
	}
	public function queryWrite(){
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->query(call_user_func_array(array($wpdb, 'prepare'), $args));
		} else {
			return $wpdb->query(func_get_arg(0));
		}
	}
	public function queryWriteArray($query, $array) {
		global $wpdb;
		return $wpdb->query($wpdb->prepare($query, $array));
	}
	public function flush(){ //Clear cache
		global $wpdb;
		$wpdb->flush();
	}
	public function querySelect(){ //sprintfString, arguments :: always returns array() and will be empty if no results.
		global $wpdb;
		if(func_num_args() > 1){
			$args = func_get_args();
			return $wpdb->get_results(call_user_func_array(array($wpdb, 'prepare'), $args), ARRAY_A);
		} else {
			return $wpdb->get_results(func_get_arg(0), ARRAY_A);
		}
	}
	public function queryWriteIgnoreError(){ //sprintfString, arguments
		global $wpdb;
		$oldSuppress = $wpdb->suppress_errors(true);
		$args = func_get_args();
		call_user_func_array(array($this, 'queryWrite'), $args);
		$wpdb->suppress_errors($oldSuppress);
	}
	public function columnExists($table, $col){
		$table = wfDB::networkTable($table);
		$q = $this->querySelect("desc $table");
		foreach($q as $row){
			if($row['Field'] == $col){
				return true;
			}
		}
		return false;
	}
	public function dropColumn($table, $col){
		$table = wfDB::networkTable($table);
		$this->queryWrite("alter table $table drop column $col");
	}
	public function createKeyIfNotExists($table, $col, $keyName){
		$table = wfDB::networkTable($table);
		
		$exists = $this->querySingle(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME='%s'
SQL
			, $table);
		$keyFound = false;
		if($exists){
			$q = $this->querySelect("show keys from $table");
			foreach($q as $row){
				if($row['Key_name'] == $keyName){
					$keyFound = true;
				}
			}
		}
		if(! $keyFound){
			$this->queryWrite("alter table $table add KEY $keyName($col)");
		}
	}
	public function getMaxAllowedPacketBytes(){
		$rec = $this->querySingleRec("show variables like 'max_allowed_packet'");
		return intval($rec['Value']);
	}
	public function getMaxLongDataSizeBytes() {
		$rec = $this->querySingleRec("show variables like 'max_long_data_size'");
		return $rec['Value'];
	}
	public function truncate($table){ //Ensures everything is deleted if user is using MySQL >= 5.1.16 and does not have "drop" privileges
		$this->queryWrite("truncate table $table");
		$this->queryWrite("delete from $table");
	}
	public function getLastError(){
		global $wpdb;
		return $wpdb->last_error;
	}
	public function realEscape($str){
		global $wpdb;
		return $wpdb->_real_escape($str);
	}
	public function insert($table, $columns, $rows, $updateOnDuplicate) {
		global $wpdb;
		$rowCount = count($rows);
		if ($rowCount === 0)
			return;
		$columnClause = implode(',', array_keys($columns));
		$valuesClause = ltrim(str_repeat(',(' . implode(',', $columns) . ')', $rowCount), ',');
		if ($updateOnDuplicate) {
			$duplicateClause = ' ON DUPLICATE KEY UPDATE ' . implode(',', array_map(function($column) {
				return "{$column} = VALUES({$column})";
			}, $updateOnDuplicate));
		}
		else {
			$duplicateClause = null;
		}
		$parameters = [];
		foreach ($rows as $row) {
			foreach ($row as $value) {
				$parameters[] = $value;
			}
		}
		$query = $wpdb->prepare("INSERT INTO {$table} ({$columnClause}) VALUES {$valuesClause}{$duplicateClause}", $parameters);
		$result = $wpdb->query($query);
		if ($result === false)
			throw new RuntimeException("Insert query failed: {$query}");
	}
	private static function getBindingType($value, $override = null) {
		if ($override !== null)
			return $override;
		if (is_int($value)) {
			return '%d';
		}
		else {
			return '%s';
		}
	}
	private static function buildWhereClause($conditions, $bindingOverrides, &$parameters) {
		$whereExpressions = [];
		foreach ($conditions as $column => $value) {
			$override = array_key_exists($column, $bindingOverrides) ? $bindingOverrides[$column] : null;
			if ($override === null) {
				$getBinding = [self::class, 'getBindingType'];
			}
			else {
				$getBinding = function($value) use ($override) { return $override; };
			}
			if (is_array($value)) {
				$whereExpressions[] = "{$column} IN (" . implode(',', array_map($getBinding, $value)) . ')';
				$parameters = array_merge($parameters, $value);
			}
			else {
				$whereExpressions[] = "{$column} = " . $getBinding($value);
				$parameters[] = $value;
			}
		}
		return implode(' AND ', $whereExpressions);
	}
	public function update($table, $set, $conditions, $bindingOverrides = []) {
		global $wpdb;
		$setExpressions = [];
		$parameters = [];
		foreach ($set as $column => $value) {
			if (is_array($value)) {
				$parameters[] = $value[1];
				$value = $value[0];
			}
			$setExpressions[] = "{$column} = {$value}";
		}
		$whereClause = self::buildWhereClause($conditions, $bindingOverrides, $parameters);
		$setClause = implode(',', $setExpressions);
		$query = $wpdb->prepare("UPDATE {$table} SET {$setClause} WHERE {$whereClause}", $parameters);
		$result = $wpdb->query($query);
		if ($result === false)
			throw new RuntimeException("UPDATE query failed: {$query}");
	}
	public function select($table, $columns, $conditions, $bindingOverrides = [], $limit = 500) {
		global $wpdb;
		$parameters = [];
		$selectClause = implode(',', $columns);
		$whereClause = Self::buildWhereClause($conditions, $bindingOverrides, $parameters);
		$limitClause = $limit === null ? '' : " LIMIT {$limit}";
		$query = $wpdb->prepare("SELECT {$selectClause} FROM {$table} WHERE {$whereClause}{$limitClause}", $parameters);
		if (count($columns) == 1) {
			$result = $wpdb->get_col($query);
		}
		else {
			$result = $wpdb->get_results($query, ARRAY_N);
		}
		if (!is_array($result))
			throw new RuntimeException("SELECT query failed: {$query}");
		return $result;
	}
	public function selectAll($table, $columns, $conditions, $bindingOverrides = []) {
		return $this->select($table, $columns, $conditions, $bindingOverrides, null);
	}
}

abstract class wfModel {

	private $data;
	private $db;
	private $dirty = false;

	/**
	 * Column name of the primary key field.
	 *
	 * @return string
	 */
	abstract public function getIDColumn();

	/**
	 * Table name.
	 *
	 * @return mixed
	 */
	abstract public function getTable();

	/**
	 * Checks if this is a valid column in the table before setting data on the model.
	 *
	 * @param string $column
	 * @return boolean
	 */
	abstract public function hasColumn($column);

	/**
	 * wfModel constructor.
	 * @param array|int|string $data
	 */
	public function __construct($data = array()) {
		if (is_array($data) || is_object($data)) {
			$this->setData($data);
		} else if (is_numeric($data)) {
			$this->fetchByID($data);
		}
	}

	public function fetchByID($id) {
		$id = absint($id);
		$data = $this->getDB()->get_row($this->getDB()->prepare('SELECT * FROM ' . $this->getTable() .
				' WHERE ' . $this->getIDColumn() . ' = %d', $id));
		if ($data) {
			$this->setData($data);
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function save() {
		if (!$this->dirty) {
			return false;
		}
		$this->dirty = ($this->getPrimaryKey() ? $this->update() : $this->insert()) === false;
		return !$this->dirty;
	}

	/**
	 * @return false|int
	 */
	public function insert() {
		$data = $this->getData();
		unset($data[$this->getPrimaryKey()]);
		$rowsAffected = $this->getDB()->insert($this->getTable(), $data);
		$this->setPrimaryKey($this->getDB()->insert_id);
		return $rowsAffected;
	}

	/**
	 * @return false|int
	 */
	public function update() {
		return $this->getDB()->update($this->getTable(), $this->getData(), array(
			$this->getIDColumn() => $this->getPrimaryKey(),
		));
	}

	/**
	 * @param $name string
	 * @return mixed
	 */
	public function __get($name) {
		if (!$this->hasColumn($name)) {
			return null;
		}
		return array_key_exists($name, $this->data) ? $this->data[$name] : null;
	}

	/**
	 * @param $name string
	 * @param $value mixed
	 */
	public function __set($name, $value) {
		if (!$this->hasColumn($name)) {
			return;
		}
		$this->data[$name] = $value;
		$this->dirty = true;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param array $data
	 * @param bool $flagDirty
	 */
	public function setData($data, $flagDirty = true) {
		$this->data = array();
		foreach ($data as $column => $value) {
			if ($this->hasColumn($column)) {
				$this->data[$column] = $value;
				$this->dirty = (bool) $flagDirty;
			}
		}
	}

	/**
	 * @return wpdb
	 */
	public function getDB() {
		if ($this->db === null) {
			global $wpdb;
			$this->db = $wpdb;
		}
		return $this->db;
	}

	/**
	 * @param wpdb $db
	 */
	public function setDB($db) {
		$this->db = $db;
	}

	/**
	 * @return int
	 */
	public function getPrimaryKey() {
		return $this->{$this->getIDColumn()};
	}

	/**
	 * @param int $value
	 */
	public function setPrimaryKey($value) {
		$this->{$this->getIDColumn()} = $value;
	}
}