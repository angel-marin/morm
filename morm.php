<?php
class Morm {
	private $driver;
	private $host;
	private $charset;
	private $dbname;
	private $dbuser;
	private $dbpass;
	private $conexion;
	private $safeJoin;
	private $sesion;
	private $newItem;
	private $sqlText;
	private $sqlValues;
	private $pk;
	private $query;
	private $allRows;
	private $actualRow;
	private $columNames;

	private $select;
	private $typeSelect;
	private $typeQuery;
	private $from;
	private $where;
	private $andWhere;
	private $orWhere;
	private $groupBy;
	private $having;
	private $orderBy;
	private $limit;
	private $offset;
	private $procedure;
	private $into;
	private $filename;
	private $queryCharset;
	private $find;

	private $set;

	private $joinTable;
	private $joinJoin;
	private $fkColumnInner;
	private $fkColumn;

	public function __construct() {
		$this->construct();
	}
	private function construct(){
	   	global $mormsDbParams;
		$this->driver = (isset($mormsDbParams['driver']))?$mormsDbParams['driver']:'mysql';
		$this->host = (isset($mormsDbParams['host']))?$mormsDbParams['host']:'localhost';
		$this->charset = (isset($mormsDbParams['charset']))?$mormsDbParams['charset']:'UTF8';
		$this->safeJoin = (isset($mormsDbParams['safeJoin']))?$mormsDbParams['safeJoin']:true;
		if(isset($mormsDbParams['dbname'])) $this->dbname = $mormsDbParams['dbname'];
		if(isset($mormsDbParams['user'])) $this->dbuser = $mormsDbParams['user'];
		if(isset($mormsDbParams['password'])) $this->dbpass=$mormsDbParams['password'];
		if(!isset($mormsDbParams['connect']) || $mormsDbParams['connect'] == 'true') $this->conectar();
	}
	//configuration set by static function so you can
	static public function config($globalVar, $dbparams){
		$GLOBALS['mormsDbParams'] = $dbparams;
		$GLOBALS[$globalVar] = new Morm();
	}
	public function desconectar(){
		$this->conexion = null;
	}
	public function conectar(){
		try{
			$this->conexion = new PDO($this->driver.":host=".$this->host.";dbname=".$this->dbname.";charset=".$this->charset, $this->dbuser, $this->dbpass);
		}
		catch (PDOException $e) {
			return array('Error' => $e->getMessage());
		}
	}
	public function create(){
		$this->unsetAll();
		$this->columNames = array();
		$this->select = '*';
		$this->sqlValues = array();
		$this->andWhere = array();
		$this->orWhere = array();
		$this->joinTable = array();
		$this->joinJoin = false;
		$this->fkColumnInner = array();
		$this->fkColumn = array();
		$this->set = array();
		$this->typeQuery = 'select';
		$this->allRows = array();
		return $this;
	}
	private function unsetAll(){
		unset($this->columNames);
		unset($this->sqlText);
		unset($this->pk);
		unset($this->query);
		unset($this->allRows);
		unset($this->actualRow);
		unset($this->select);
		unset($this->typeSelect);
		unset($this->typeQuery);
		unset($this->from);
		unset($this->sqlValues);
		unset($this->where);
		unset($this->andWhere);
		unset($this->orWhere);
		unset($this->groupBy);
		unset($this->having);
		unset($this->orderBy);
		unset($this->limit);
		unset($this->offset);
		unset($this->procedure);
		unset($this->into);
		unset($this->filename);
		unset($this->queryCharset);
		unset($this->find);
		unset($this->joinTable);
		unset($this->joinJoin);
		unset($this->fkColumnInner);
		unset($this->fkColumn);
		unset($this->set);
	}
	public function select($data = '*'){
		$this->create();
		$this->typeQuery = 'select';
		$this->select = $data;
		return $this;
	}
	public function find($data = '*'){
		if (isset($this->allRows[0])){ // simple select ->getTable('table')->find('data')
			$this->getPrimaryKey();
			$pk = $this->pk;
			foreach ($this->allRows as $row){
				if($row->$pk == $data)
					return $row;
			}
		}
		$this->find = $data;
		return $this;
	}
	public function getTable($table){
		$this->select();
		$this->from($table);
		$this->execute();
	}
	public function distinct(){
		$this->typeSelect = 'DISTINCT';
		return $this;
	}
	public function join($table){
		$join = new stdClass();
		$join->type='';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey($table);
		return $this;
	}
	public function leftJoin($table){
		$join = new stdClass();
		$join->type='LEFT';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey($table);
		return $this;
	}
	public function rightJoin($table){
		$join = new stdClass();
		$join->type='RIGHT';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey($table);
		return $this;
	}
	public function innerJoin($table){
		$join = new stdClass();
		$join->type='INNER';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey($table);
		return $this;
	}
	public function forceJoin($table, $method){
		$join = new stdClass();
		$join->type='forced';
		$join->table = $table;
		$join->method = $method;
		$this->joinTable[] = $join;
		return $this;
	}
	public function joinJoin(){
		$this->joinJoin = true;
		$this->getPrimaryKey();
		return $this;
	}
	private function getPrimaryKey(){
		$pk = $this->conexion->prepare("SHOW KEYS FROM " . $this->from . " WHERE Key_name = 'PRIMARY'");
		$pk->execute(array());
		$pk = $pk->fetch(PDO::FETCH_OBJ);
		$pk = $pk->Column_name;
		$this->pk = $pk;
	}
	private function getForeignKey($jTable){
		$fk = $this->conexion->prepare("SELECT column_name, referenced_column_name FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND table_name = '" . $this->from . "' AND referenced_table_name = '" . $jTable . "' AND table_schema = '" . $this->dbname . "'");
		$fk->execute(array());
		$fk = $fk->fetch(PDO::FETCH_OBJ);
		if($fk){
			$this->fkColumnInner[$jTable] = $fk->column_name;
			$this->fkColumn[$jTable] = $fk->referenced_column_name;
		}else{
			throw new Exception('Can not get union between ' . $this->from . ' and ' . $jTable, 3);
		}
	}
	private function getColumnNames($table){
		$temp = $this->conexion->prepare("SHOW COLUMNS FROM " . $table);
		$temp->execute(array());
		$temp_rows = $temp->fetchAll(PDO::FETCH_OBJ);
		$this->columNames[$table] = array();
		foreach($temp_rows as $r)
			$this->columNames[$table][] = $r->Field;
	}
	public function from($data){
		$this->from = $data;
		if(isset($this->find)){ // simple select ->find('data')->from('table')
			$this->getPrimaryKey();
			$pk = $this->pk;
			if ($pk){
				$this->sqlText = "SELECT * FROM ".$data." WHERE ".$pk." = :data";
				$this->query = $this->conexion->prepare($this->sqlText);
				$this->query->execute(array(':data' => $this->find));
				$this->actualRow = $this->query->fetch(PDO::FETCH_OBJ);
				return $this->actualRow;
			}
		}else{
			return $this;
		}
	}
	public function where($data = '1', $values = null){
		if((array)$values !== $values)
			$values = array($values);
		$temp = new stdClass();
		$temp->sentence = $data;
		$temp->values = $values;
		$this->where = $temp;
		return $this;
	}
	public function andWhere($data = '1', $values = null){
		if((array)$values !== $values)
			$values = array($values);
		$temp = new stdClass();
		$temp->sentence = $data;
		$temp->values = $values;
		$this->andWhere[] = $temp;
		return $this;
	}
	public function orWhere($data = '1', $values = null){
		if((array)$values !== $values)
			$values = array($values);
		$temp = new stdClass();
		$temp->sentence = $data;
		$temp->values = $values;
		$this->orWhere[] = $temp;
		return $this;
	}
	public function groupBy($data = '*'){
		$this->groupBy = $data;
		return $this;
	}
	public function having($data = '1'){
		$this->having = $data;
		return $this;
	}
	public function orderBy($data = '*'){
		$this->orderBy = $data;
		return $this;
	}
	public function limit($data = '1'){
		$this->limit = $data;
		return $this;
	}
	public function offset($data = '0'){
		$this->offset = $data;
		return $this;
	}
	public function procedure($data = ''){
		$this->procedure = $data;
		return $this;
	}
	public function into($data = 'OUTFILE', $filename = 'dump_query', $charset = 'UTF8_bin'){
		$this->into = $data;
		$this->filename = $filename;
		$this->queryCharset = $charset;
		return $this;
	}
	private function parseWhere(){
		if(isset($this->where)){
			$this->sqlText .= ' WHERE ' . $this->where->sentence;
			$this->sqlValues = array_merge($this->sqlValues, $this->where->values);
			if(isset($this->andWhere)){
				foreach($this->andWhere as $w){
					$this->sqlText .= ' AND (' . $w->sentence . ')';
					$this->sqlValues = array_merge($this->sqlValues, $w->values);
				}
			}
			if(isset($this->orWhere)){
				foreach($this->orWhere as $w){
					$this->sqlText .= ' OR (' . $w->sentence . ')';
					$this->sqlValues = array_merge($this->sqlValues, $w->values);
				}
			}
		}
	}
	private function safeExecute(){
		$this->sqlText = 'SELECT '.$this->select;
		if(isset($this->typeSelect)) $this->sqlText .= ' ' . $this->typeSelect;
		$this->sqlText .= ' FROM '.$this->from;
		$this->getColumnNames($this->from);
		if(isset($this->joinTable)){
			foreach($this->joinTable as $key => $j){
				$this->getColumnNames($j->table);
				if($j->type == 'forced'){
					$this->sqlText .= ' JOIN ' . $j->table . ' ON ' . $j->method;
				}else{
					$this->sqlText .= ' ' . $j->type . ' JOIN ' . $j->table . ' ON ' . $this->from . '.' . $this->fkColumnInner[$j->table] . '=' . $j->table . '.' . $this->fkColumn[$j->table];
				}
			}
		}
		$this->parseWhere();
		if(isset($this->groupBy)) $this->sqlText .= ' GROUP BY ' . $this->groupBy;
		if(isset($this->having)) $this->sqlText .= ' HAVING ' . $this->having;
		if(isset($this->orderBy)) $this->sqlText .= ' ORDER BY ' . $this->orderBy;
		if(isset($this->limit)) $this->sqlText .= ' LIMIT ' . $this->limit;
		if(isset($this->offset)) $this->sqlText .= ' OFFSET ' . $this->offset;
		if(isset($this->procedure)) $this->sqlText .= ' PROCEDURE ' . $this->procedure;
		if(isset($this->into)) $this->sqlText .= ' INTO ' . $this->into . "'" . $this->filename . "'";
		if(isset($this->queryCharset)) $this->sqlText .= ' CHARACTER SET ' . $this->queryCharset;
		$this->query = $this->conexion->prepare($this->sqlText);
		$this->query->execute($this->sqlValues);
		if($this->query){
			while($row = $this->query->fetch(PDO::FETCH_NUM)){
				$i=0;
				$temp_object = new stdClass();
				foreach ($this->columNames[$this->from] as $column){
					$temp_object->$column = $row[$i];
					$i++;
				}
				$foundRelated = false;
				if(isset($this->joinTable)){
					foreach($this->joinTable as $key => $j){
						$temp_join_object = new stdClass();
						if($j->type == 'forced'){
							$pieces = explode("=", $j->method);
							foreach($pieces as $p){
								if(strpos($j->method, $j->table) !== false){
									$related = $j->table;
								}
							}
						}else{
							$related = $this->fkColumnInner[$j->table];
						}
						foreach ($this->columNames[$j->table] as $column){
							$temp_join_object->$column = $row[$i];
							$i++;
						}
						if($this->joinJoin){
							$pk = $this->pk;
							foreach ($this->allRows as $key => &$val) {
								if ($val->$pk === $temp_object->$pk) {
									array_push($val->$related, $temp_join_object);
									$foundRelated = true;
								}
							}
							if(!$foundRelated){
								$temp_object->$related = array($temp_join_object);
							}
						}else{
							$temp_object->$related = $temp_join_object;
						}
					}
				}
				if(!$foundRelated)
					$this->allRows[] = $temp_object;
			}
		}
	}
	public function execute(){
		switch($this->typeQuery){
			case 'select':
				if($this->safeJoin){
					$this->safeExecute();
				}else{
					$this->sqlText = 'SELECT '.$this->select;
					if(isset($this->typeSelect)) $this->sqlText .= ' ' . $this->typeSelect;
					$this->sqlText .= ' FROM '.$this->from;
					if(isset($this->joinTable)){
						foreach($this->joinTable as $key => $j){
							$this->sqlText .= ' ' . $j->type . ' JOIN ' . $j->table . ' ON ' . $this->from . '.' . $this->fkColumnInner[$j->table] . '=' . $j->table . '.' . $this->fkColumn[$j->table];
						}
					}
					$this->parseWhere();
					if(isset($this->groupBy)) $this->sqlText .= ' GROUP BY ' . $this->groupBy;
					if(isset($this->having)) $this->sqlText .= ' HAVING ' . $this->having;
					if(isset($this->orderBy)) $this->sqlText .= ' ORDER BY ' . $this->orderBy;
					if(isset($this->limit)) $this->sqlText .= ' LIMIT ' . $this->limit;
					if(isset($this->offset)) $this->sqlText .= ' OFFSET ' . $this->offset;
					if(isset($this->procedure)) $this->sqlText .= ' PROCEDURE ' . $this->procedure;
					if(isset($this->into)) $this->sqlText .= ' INTO ' . $this->into . "'" . $this->filename . "'";
					if(isset($this->queryCharset)) $this->sqlText .= ' CHARACTER SET ' . $this->queryCharset;
					$this->query = $this->conexion->prepare($this->sqlText);
					$this->query->execute($this->sqlValues);
					if($this->query)
						$this->allRows = $this->query->fetchAll(PDO::FETCH_OBJ);
				}
				return $this;
			break;
			case 'delete':
				$done = false;
			  	$this->sqlText = 'DELETE FROM ' . $this->from;
				$this->parseWhere();
				if(isset($this->orderBy)) $this->sqlText .= ' ORDER BY ' . $this->orderBy;
				if(isset($this->limit)) $this->sqlText .= ' LIMIT ' . $this->limit;
			  	$this->query = $this->conexion->prepare($this->sqlText);
				if ($this->query->execute($this->sqlValues))
					$done = true;
				return $done;
			break;
			case 'update':
				$done = false;
				$this->sqlText = 'UPDATE ' . $this->from;
				if(isset($this->set)){
					$this->sqlText .= ' SET ';
					foreach($this->set as $key => $j){
						$this->sqlText .= ' ' . $j->sentence . ', ';
						$this->sqlValues = array_merge($this->sqlValues, $j->values);
					}
					$this->sqlText = substr($this->sqlText, 0, -2) . ' ';
				}
				$this->parseWhere();
				if(isset($this->orderBy)) $this->sqlText .= ' ORDER BY ' . $this->orderBy;
				if(isset($this->limit)) $this->sqlText .= ' LIMIT ' . $this->limit;
			  	$this->query = $this->conexion->prepare($this->sqlText);
				if ($this->query->execute($this->sqlValues))
					$done = true;
				return $done;
			break;
		}
	}
	public function getAlls(){
		return $this->allRows;
	}
	public function getFirst(){
		return $this->allRows[0];
	}
	public function getFirsts($val){
		$temp = array();
		if($this->allRows > 0){
			for ($i = 0;$i < $val;$i++){
				if(isset($this->allRows[$i]))
					$temp[] = $this->allRows[$i];
			}
		}
		return $temp;
	}
	public function getLast(){
		return $this->allRows[count($this->allRows) - 1];
	}
	public function getLasts($val){
		$temp = array();
		if($this->allRows > 0){
			$allCount = count($this->allRows);
			$i = ($allCount - $val > 0)?$allCount - $val:0;
			for ($i;$i < $allCount;$i++){
				if(isset($this->allRows[$i]))
					$temp[] = $this->allRows[$i];
			}
		}
		return $temp;
	}
	public function getQuery(){
		return $this->sqlText;
	}
	public function newItem($table){
		$this->newItem = new MormItem();
		$this->newItem->_mormTableName = $table;
		return $this->newItem;
	}
	public function save(){
		$done = false;
		$columns = '';
		$values = '';
		$valuesParams = array();
		$temp = $this->getVars();
		foreach($temp as $key => $value){
			if($key != '_mormTableName'){
				$columns .= $key.',';
				$values .= '?,';
				$valuesParams[] = $value;
			}
		}
		if(strlen($columns) > 0){
			$columns = substr($columns, 0, -1);
			$values	= substr($values, 0, -1);
		}
		$sql = $this->conexion->prepare("INSERT INTO ".$temp['_mormTableName']." (".$columns.") VALUES (".$values.")");
		if ($sql->execute($valuesParams))
		{
		  $done = true;
		  $this->id = $this->conexion->lastInsertId();
		}
		return $done;
	}
	public function delete($table){
		$this->create();
		$this->from = $table;
		$this->typeQuery = 'delete';
		return $this;
	}
	public function update($table){
		$this->create();
		$this->from = $table;
		$this->typeQuery = 'update';
		return $this;
	}
	public function set($data, $values = null){
		if((array)$values !== $values)
			$values = array($values);
		$temp = new stdClass();
		$temp->sentence = $data;
		$temp->values = $values;
		$this->set[] = $temp;
		return $this;
	}
}
class MormItem extends Morm {
	private $functions = array();
	private $vars = array();

	function __set($name,$data){
		if(is_callable($data))
			$this->functions[$name] = $data;
		else
			$this->vars[$name] = $data;
	}
	function __get($name){
		if(isset($this->vars[$name]))
			return $this->vars[$name];
	}
	function getVars(){
		return $this->vars;
	}
	function __call($method,$args){
		if(isset($this->functions[$method]))
			call_user_func_array($this->functions[$method],$args);
		else{
		// error
		}
	}
}
?>
