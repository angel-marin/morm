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
	private $new;
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
	public function find($data='*'){

		return $this;
	}
	public function distinct(){
		$this->typeSelect='DISTINCT';
		return $this;
	}
	public function from($data){
		$this->from=$data;
		return $this;
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
		return $this->allRows;
   }
   public function getFirst(){
		return $this->allRows[0];
   }
	public function getLast(){
		return $this->allRows[count($this->allRows )-1];
	}
/*
   public function create()
   {
		// opt2: new class
		$new=new MormNewQuery();


   }
   */
}
?>
