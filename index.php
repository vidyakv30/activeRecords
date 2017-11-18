<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);
echo '<link rel="stylesheet" href="styles.css" type="text/css">';
define ('DATABASE', 'vk427');
define ('USERNAME', 'vk427');
define ('PASSWORD', 'R0adrunner');
define ('CONNECTION', 'sql1.njit.edu');

class dbConn{

	protected static $db;

	private function __construct(){

		try{
			self::$db= new PDO('mysql:host=' . CONNECTION .';dbname=' . DATABASE, USERNAME, PASSWORD );
			self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			echo "<b>Connected Succesfully </b><br>";

		}

		catch (PDOException $e){
			echo "Connection Error: " . $e->getMessage();

		}
	}

	public static function getConnection(){

		if(!self::$db){
			new dbConn();
		}
		return self::$db;

	}
}

abstract class collection{

	public static $record;

	    public static function create() {
    		  $model = new static::$modelName;
    		  return $model;
    }

		public static function getRecordSet(){
		$db = dbConn::getConnection();
		$tableName = get_called_class();
		$sql = "SELECT * from ". $tableName;
		$statement = $db->prepare ($sql);
		$statement->execute();
		$class = static::$modelName;
		$statement->setFetchMode(PDO::FETCH_CLASS, $class);
        $recordSet =  $statement->fetchAll();
        return $recordSet;
	}


     

    	public static function getOneRecord($id){
		$db = dbConn::getConnection();
		$tableName = get_called_class();
		$sql = "SELECT * from ". $tableName . " WHERE id = $id";
		$statement = $db->prepare ($sql);
		$statement->execute();
		$class = static::$modelName;
		$statement->setFetchMode(PDO::FETCH_CLASS, $class);
        $record =  $statement->fetchAll();
        return $record;
	}
}


class accounts extends collection {
    protected static $modelName = 'account';
}

class todos extends collection {
    protected static $modelName = 'todo';
}


class model {

	protected $tableName;
	protected static $statement;
    
    public function save()
   		 {

		$array = get_object_vars($this);

    	unset($array["tableName"]);
    		     
        if ($this->id == '') {

            echo "Call Insert Method <br>";
            $sql = $this->insert();
            
        } else {
             $sql = $this->update();
        }
        $db = dbConn::getConnection();
        self::$statement = $db->prepare($sql);
        foreach ($array as $key=>$value)
        {
         self::$statement->bindValue(":$key","$value");
        }
         self::$statement->execute();
    
    }

    public function insert()
    {   $array = get_object_vars($this);

        
    	foreach ($array as $key => $value)
    	{    
    		if ($key=="tableName")
    		{   
    			unset($array["tableName"]);
    		}
    	}
    	       
 	   $columnString = implode(',', array_keys($array));
 	   echo "<br> $columnString<br>";
       $valueString = ":".implode(',:', array_keys($array));
       echo "<br>$valueString<br>";
       $sql = "INSERT INTO $this->tableName (" . $columnString . ") VALUES (" . $valueString . ")";
       print_r($sql);
       return $sql;
    		
    	}	  

    
}


class account extends model {

    public $id;
    public $email;
    public $fname;
    public $lname;
    public $phone;
    public $birthday;
    public $gender;
    public $password;

    public function __construct()
    {
        $this->tableName = 'accounts';

}
}


class todo extends model {

    public $id;
    public $owneremail;
    public $ownerid;
    public $createdate;
    public $duedate;
    public $message;
    public $isdone;
    
    public function __construct()
    {
        $this->tableName = 'todos';

}
}




class tableClass{

	private static $table;

	public static function checkRecord ($rec){
		if(count($rec) == 0){
			$printTable = "No records returned <br>";
		}
		else {
          
          $printTable = self::drawTable($rec);
          
		}
		return $printTable;

	}

	public static function drawTable($rec){

		self::$table.= '<table>';
		self::$table.= '<tr>';
		$headerFields = $rec[0];
		foreach ($headerFields as $key => $value) {
                self::$table .= "<th>$key</th>";

            }
        self::$table.= '</tr>';
        foreach ($rec as $record){
        	self::$table.= '<tr>';

        	foreach($record as $key => $value){
        		self::$table.= "<td>$value</td>";

        	}
        	self::$table .= '</tr>';


        }

        self::$table .= '</table> <br>';
        return self::$table;

	}


	
}

$record= accounts::getRecordSet();
$record = tableClass::checkRecord($record);
$record = accounts::getOneRecord(10);
$record = tableClass::checkRecord($record);
$record= todos::getRecordSet();
$record = tableClass::checkRecord($record);
$record= todos::getOneRecord(5);
$record = tableClass::checkRecord($record);
echo ($record);

$newRec = new account();
$newRec->email="vkv@gmail.com";
$newRec->fname="James";
$newRec->lname="Bond";
$newRec->phone='007';
$newRec->birthday='01011955';
$newRec->gender='male';
$newRec->password='001';
$newRec->save();
echo " Inserted record into table";

?>