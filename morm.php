<?php
class Morm {
	private $driver;
	private $host;
	private $charset;
	private $dbname;
	private $dbuser;
	private $dbpass;
	private $conexion;
	private $sesion;
	private $newItem;
	private $sql_text;
	private $pk;
	private $query;
	private $allRows;
	private $actualRow;

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

	private $joinTable;
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
		if(isset($mormsDbParams['dbname'])) $this->dbname = $mormsDbParams['dbname'];
		if(isset($mormsDbParams['user'])) $this->dbuser = $mormsDbParams['user'];
		if(isset($mormsDbParams['password'])) $this->dbpass=$mormsDbParams['password'];
		if(isset($mormsDbParams['driver'])) $this->driver=$mormsDbParams['driver'];
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
			echo "Error de conexion en la base de datos, por favor, intentelo mas tarde";
		}
	}
	public function create(){
		$this->unsetAll();
		$this->select = '*';
		$this->andWhere = array();
		$this->orWhere = array();
		$this->joinTable = array();
		$this->fkColumnInner = array();
		$this->fkColumn = array();
		return $this;
	}
	private function unsetAll(){
		unset($this->sql_text);
		unset($this->pk);
		unset($this->query);
		unset($this->allRows);
		unset($this->actualRow);
		unset($this->select);
		unset($this->typeSelect);
		unset($this->typeQuery);
		unset($this->from);
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
		unset($this->fkColumnInner);
		unset($this->fkColumn);
	}
	public function select($data = '*'){
		if(!isset($this->select)){ //with this we can set create as optional
			$this->create();
		}
		$this->typeQuery = 'select';
		$this->select = $data;
		return $this;
	}
	public function find($data = '*'){
		if isset($this->allRows[0]){ // simple select ->getTable('table')->find('data')
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
		$join->type='LEFT';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey();
	}
	public function leftJoin($table){
		$join = new stdClass();
		$join->type='LEFT';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey();
	}
	public function rightJoin($table){
		$join = new stdClass();
		$join->type='RIGHT';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey();
	}
	public function innerJoin($table){
		$join = new stdClass();
		$join->type='LEFT';
		$join->table = $table;
		$this->joinTable[] = $join;
		$this->getForeignKey();
	}
	private function getPrimaryKey(){
		$pk = $this->conexion->prepare("SHOW KEYS FROM " . $this->from . " WHERE Key_name = 'PRIMARY'");
		$pk->execute(array());
		$pk = $pk->fetch(PDO::FETCH_OBJ);
		$pk = $pk->Column_name;
		$this->pk = $pk;
	}
	private function getForeignKey(){
		$jTable = $this->joinTable[count($this->joinTable) - 1];
		$jTable = $jTable->table;
		$fk = $this->conexion->prepare("SELECT column_name, referenced_column_name FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND table_name = '" . $this->from . "' AND referenced_table_name = '" . $jTable . "' AND table_schema = '" . $this->dbname . "'");
		$fk->execute(array());
		$fk = $fk->fetch(PDO::FETCH_OBJ);
		$this->fkColumnInner[] = $fk->Column_name;
		$this->fkColumn[] = $fk->referenced_column_name;
	}
	public function from($data){
		$this->from = $data;
		if(isset($this->find)){ // simple select ->find('data')->from('table')
			$this->getPrimaryKey();
			$pk = $this->pk;
			if ($pk){
				$this->sql_text = "SELECT * FROM ".$data." WHERE ".$pk." = :data";
				$this->query = $this->conexion->prepare($this->sql_text);
				$this->query->execute(array(':data' => $this->find));
				$this->actualRow = $this->query->fetch(PDO::FETCH_OBJ);
				return $this->actualRow;
			}
		}else{
			return $this;
		}
	}
	public function where($data = '1', $values = null){
		$temp = new stdClass();
		$temp->sentence = $data;
		$temp->values = $values;
		$this->where = $temp;
		return $this;
	}
	public function andWhere($data = '1', $values = null){
		$temp = new stdClass();
		$temp->sentence = $data;
		$temp->values = $values;
		$this->andWhere[] = $temp;
		return $this;
	}
	public function orWhere($data = '1', $values = null){
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
	public function execute(){
		switch($this->typeQuery){
			case 'select':
				$sqlText = 'SELECT '.$this->select;
				if(isset($this->typeSelect)) $sqlText .= ' ' . $this->typeSelect;
				$sqlText = ' FROM '.$this->from;
				if(isset($this->joinTable)){
					foreach($this->joinTable as $key => $j){
						$sqlText .= ' ' . $j->type . ' JOIN ' . $j->table . ' ON ' . $this->from . '.' . $this->fkColumnInner[$key] . '=' . $j->table . '.' . $this->fkColumn[$key];
					}
				}
				if(isset($this->where)){
					$sqlText .= ' WHERE ' . $this->where;
					if(isset($this->andWhere)){
						foreach($this->andWhere as $w){
							$sqlText .= ' AND (WHERE ' . $w . ')';
						}
					}
					if(isset($this->orWhere)){
						foreach($this->orWhere as $w){
							$sqlText .= ' OR (WHERE ' . $w . ')';
						}
					}
				}
				if(isset($this->groupBy)) $sqlText .= ' GROUP BY ' . $this->groupBy;
				if(isset($this->having)) $sqlText .= ' HAVING ' . $this->having;
				if(isset($this->orderBy)) $sqlText .= ' ORDER BY ' . $this->orderBy;
				if(isset($this->limit)) $sqlText .= ' LIMIT ' . $this->limit;
				if(isset($this->offset)) $sqlText .= ' OFFSET ' . $this->offset;
				if(isset($this->procedure)) $sqlText .= ' PROCEDURE ' . $this->procedure;
				if(isset($this->into)) $sqlText .= ' INTO ' . $this->into . "'" . $this->filename . "'";
				if(isset($this->queryCharset)) $sqlText .= ' CHARACTER SET ' . $this->queryCharset;
				$this->sql_text = $sqlText;
				$this->query = $this->conexion->query($this->sql_text);
				$this->allRows = $this->query->fetchAll(PDO::FETCH_OBJ);
				return $this;
			break;
			case 'delete':
				$done=  false;
			  	$sql = $this->conexion->prepare("DELETE FROM " . $this->from."");
				if ($sql->execute(array()))
					$done=  true;
				return $done;
			break;
			case 'update':
				$done = false;
			  	$sql = $this->conexion->prepare("UPDATE FROM " . $this->from."");
				if ($sql->execute(array()))
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
	public functiom getFirsts($val){
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
	public functiom getLasts($val){
		$temp = array();
		if($this->allRows > 0){
			$allCount = count($this->allRows);
			$i = ($allCount - $val > 0)?$allCount - $val:0:
			for ($i;$i < $allCount;$i++){
				if(isset($this->allRows[$i]))
					$temp[] = $this->allRows[$i];
			}
		}
		return $temp;
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
		  $done = true:
		  $this->id = $this->conexion->lastInsertId();
		}
		return $done;
	}
	public function delete($table){
		if(!isset($this->select)){ //with this we can set create as optional
			$this->create();
		}
		$this->from = $data;
		$this->typeQuery = 'delete';
		return $this;
	}
	public function update($table){
		if(!isset($this->select)){ //with this we can set create as optional
			$this->create();
		}
		$this->from = $data;
		$this->typeQuery = 'update';
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
