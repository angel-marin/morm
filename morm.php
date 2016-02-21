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
	private $query;
	private $allRows;
	private $actualRow;

	private $select;
	private $typeSelect;
	private $update;
	private $delete;
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

	//configuration set by static function so you can
	static public function config($globalVar, $dbparams){
		$GLOBALS['mormsDbParams']=$dbparams;
		$GLOBALS[$globalVar] = new Morm();
	}
	public function create(){
		$this->select='*';
		$this->andWhere=[];
		$this->orWhere=[];
		return $this;
	}
	public function select($data='*'){
		$this->select=$data;
		return $this;
	}
	// simple select with find - from
	public function find($data='*'){
		$this->find=$data;
		return $this;
	}

	public function distinct(){
		$this->typeSelect='DISTINCT';
		return $this;
	}
	private function getPrimaryKey($table){
		$pk = $this->conexion->prepare("SHOW KEYS FROM ".$table." WHERE Key_name = 'PRIMARY'");
		$pk->execute(array());
		$pk = $pk->fetch(PDO::FETCH_OBJ);
		$pk=$pk->Column_name;
		return $pk;
	}
	public function from($data){
		if(isset($this->find)){
			$pk = $this->getPrimaryKey($data);
			if ($pk){
		   		$this->sql_text = "SELECT * FROM ".$data." WHERE ".$pk." = :data";
			    $this->query = $this->conexion->prepare($this->sql_text);
				$this->query->execute(array(':data'=>$this->find));
				$this->actualRow=$this->query->fetch(PDO::FETCH_OBJ);
				return $this->actualRow;
			}
		}else{
			$this->from=$data;
			return $this;
		}
	}
	public function where($data='1',$values=null){
		$temp=new stdClass();
		$temp->sentence=$data;
		$temp->values=$values;
		$this->where=$temp;
		return $this;
	}
	public function andWhere($data='1',$values=null){
		$temp=new stdClass();
		$temp->sentence=$data;
		$temp->values=$values;
		$this->andWhere[]=$temp;
		return $this;
	}
	public function orWhere($data='1',$values=null){
		$temp=new stdClass();
		$temp->sentence=$data;
		$temp->values=$values;
		$this->orWhere[]=$temp;
		return $this;
	}
	public function groupBy($data='*'){
		$this->groupBy=$data;
		return $this;
	}
	public function having($data='1'){
		$this->having=$data;
		return $this;
	}
	public function orderBy($data='*'){
		$this->orderBy=$data;
		return $this;
	}
	public function limit($data='1'){
		$this->limit=$data;
		return $this;
	}
	public function offset($data='0'){
		$this->offset=$data;
		return $this;
	}
	public function procedure($data=''){
		$this->procedure=$data;
		return $this;
	}
	public function into($data='OUTFILE',$filename='dump_query',$charset='UTF8_bin'){
		$this->into=$data;
		$this->filename=$filename;
		$this->queryCharset=$charset;
		return $this;
	}

	public function __construct() {
		$this->construct();
   }
   private function construct(){
   		global $mormsDbParams;
       $this->driver=(isset($mormsDbParams['driver']))?$mormsDbParams['driver']:'mysql';
	   $this->host=(isset($mormsDbParams['host']))?$mormsDbParams['host']:'localhost';
       $this->charset=(isset($mormsDbParams['charset']))?$mormsDbParams['charset']:'UTF8';
	   if(isset($mormsDbParams['dbname']))$this->dbname=$mormsDbParams['dbname'];
	   if(isset($mormsDbParams['user']))$this->dbuser=$mormsDbParams['user'];
	   if(isset($mormsDbParams['password']))$this->dbpass=$mormsDbParams['password'];
	   if(isset($mormsDbParams['driver']))$this->driver=$mormsDbParams['driver'];
	   if(!isset($mormsDbParams['connect']) || $mormsDbParams['connect']=='true')$this->conectar();
   }

   public function desconectar()
   {
	$this->conexion=null;
   }

   public function conectar()
   {
	   try{
		$this->conexion = new PDO($this->driver.":host=".$this->host.";dbname=".$this->dbname.";charset=".$this->charset, $this->dbuser, $this->dbpass);
		}
		catch (PDOException $e) {
			echo "Error de conexion en la base de datos, por favor, intentelo mas tarde";
		}
   }
   public function execute(){
   		$this->sql_text= "SELECT ".$this->select." FROM ".$this->from;
	    $this->query = $this->conexion->query($this->sql_text);
		$this->allRows=$this->query->fetchAll(PDO::FETCH_OBJ);
		return $this;
   }
   public function getAlls(){
		return $this->allRows;
   }
   public function getFirst(){
		return $this->allRows[0];
   }
	public function getLast(){
		return $this->allRows[count($this->allRows )-1];
	}

	public function newItem($table){
		$this->newItem=new MormItem();
		$this->newItem->_mormTableName=$table;
		return $this->newItem;
	}

	public function save(){
		$creado=false;
		$columns='';
		$values='';
		$valuesParams=array();
		$temp=$this->getVars();
		foreach($temp as $key=>$value){
			if($key!='_mormTableName'){
				$columns.=$key.',';
				$values.='?,';
				$valuesParams[]=$value;
			}
		}
		if(strlen($columns)>0){
		   $columns	= substr($columns, 0, -1);
		   $values	= substr($values, 0, -1);
		}
		$sql = $this->conexion->prepare("INSERT INTO ".$temp['_mormTableName']." (".$columns.") VALUES (".$values.")");
		if ($sql->execute($valuesParams))
		{
		  $creado=true:
		  $this->id=$this->conexion->lastInsertId();
		}
		return $creado;
	}
}
class MormItem extends Morm {
	private $functions = array();
	private $vars = array();
	
	function __set($name,$data)
	{
	  if(is_callable($data))
	    $this->functions[$name] = $data;
	  else
	   $this->vars[$name] = $data;
	}
	
	function __get($name)
	{
	  if(isset($this->vars[$name]))
	   return $this->vars[$name];
	}
	
	function getVars(){
		return $this->vars;
	}
	
	function __call($method,$args)
	{
	  if(isset($this->functions[$method]))
	  {
	   call_user_func_array($this->functions[$method],$args);
	  } else {
	   // error out
	  }
	}
}
?>
