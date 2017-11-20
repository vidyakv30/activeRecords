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

	
		public static function getRecordSet(){
		$db = dbConn::getConnection();
		$tableName = get_called_class();
		$sql = "SELECT * from ". $tableName;
		$statement = $db->prepare ($sql);
		$statement->execute();
		$class = static::$modelName;
		$statement->setFetchMode(PDO::FETCH_CLASS, $class);
        $recordSet =  $statement->fetchAll();
        //print_r($recordSet);
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


abstract class model {

	protected $tableName;
	protected static $statement;
    
    public function save()
   		 {
		$array = get_object_vars($this);
    	unset($array["tableName"]);
    		     
        if ($this->id == '') {

            // echo "Call Insert Method <br>";
            $sql = $this->insert();
            // echo " <br> Inserted record into table " . $this->tableName; 
        } else {
             $sql = $this->update();
              // echo "<br> Updated record in table " . $this->tableName ." with ID = " . $this->id;

        }

        $db = dbConn::getConnection();
        self::$statement = $db->prepare($sql);
     
        foreach ($array as $key=>$value)
        {
         if ($this->id == ''){
           self::$statement->bindValue(":$key","$value");
         }
         else {
            if ($value != '' && $key != "id"){
                self::$statement->bindValue(":$key","$value");
            }
         }
        }

         self::$statement->execute();
        
         $lastId = $db->lastInsertId();
         return ($lastId);
    
    }

    public function insert()
    {   $array = get_object_vars($this);
    
       unset($array["tableName"]);
        
 	   $columnString = implode(',', array_keys($array));
 	   $valueString = ":".implode(',:', array_keys($array));
       $sql = "INSERT INTO $this->tableName (" . $columnString . ") VALUES (" . $valueString . ")";
       print_r($sql);
       return $sql;		
       
    	}	


     public function update()
    {
      $array = get_object_vars($this);
    
       unset($array["tableName"]);

       $sql = "UPDATE ". $this->tableName ." SET";
       foreach ($array as $key => $value){
        if ($value != "" & $key != "id")
        {

        $sql.= " " .$key ." = :$key ,";
        //$values[":$key"] = $value;
       }
 
       } 

       $sql = substr($sql,0,-1);

       $sql.= " WHERE id = " .$this->id;

       echo $sql;
       return $sql; 
       
    }


    public function delete()
    
    {    $array = get_object_vars($this);
        $sql = "DELETE FROM " . $this->tableName . " WHERE id = " . $this->id;
        //echo $sql;
        $db = dbConn::getConnection();
        self::$statement = $db->prepare($sql);
        self::$statement->execute();
       //echo "<br> <b> Record Deleted <b> <br>";

       // return $sql;
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

    public static function getTable(){
        return self::$table;
    }

	public static function populateTable ($rec){
        self::$table ="";
		if(count($rec) == 0){
			self::$table.= "<b> No records returned from table <b> <br>";
		}
		else {
          
          self::drawTable($rec);
          
		}
		//return $printTable;

	}

	private static function drawTable($rec){

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
        //return self::$table;

	}
	
}

class output{
  public $header;
  public $message;
  public $table;  

  public function printResults(){
    echo "<h2> $this->header </h2>";
    echo "$this->message <br>";
    echo "$this->table";
    echo ("<hr>");
  }
}

$outputArray = array();

$record= accounts::getRecordSet();
tableClass::populateTable($record);
$outputVar = new output();
$outputVar->header = "Select ALL records";
$outputVar->message = "Select ALL records";
$outputVar->table = tableClass::getTable();
array_push($outputArray,$outputVar);

$id = 11;
$record = accounts::getOneRecord($id);
tableClass::populateTable($record);
$outputVar = new output();
$outputVar->header = "Select One Record";
$outputVar->message = "Select record with id: $id";
$outputVar->table = tableClass::getTable();
array_push($outputArray,$outputVar);

$newRec = new account();
$newRec->email="vkv@gmail.com";
$newRec->fname="James";
$newRec->lname="Bond";
$newRec->phone='007';
$newRec->birthday='01011955';
$newRec->gender='male';
$newRec->password='001';
$newID = $newRec->save();

$record= accounts::getRecordSet();
tableClass::populateTable($record);
$outputVar = new output();
$outputVar->header = "Insert New Record ";
$outputVar->message = "New Record data Email = $newRec->email, fname = $newRec->fname lname= $newRec->lname phone = $newRec->phone birthday = $newRec->birthday gender = $newRec->gender password = $newRec->password ";
$outputVar->table = tableClass::getTable();
array_push($outputArray,$outputVar);

 $updateRec = new account();
 $updateRec->id = $newID;
 $updateRec->email="bond007@gmail.com";
 $updateId = $updateRec->save();

 $record= accounts::getRecordSet();
tableClass::populateTable($record);
$outputVar = new output();
$outputVar->header = "Update Record ";
$outputVar->message = "Updated record with id = $upateID";
$outputVar->table = tableClass::getTable();
array_push($outputArray,$outputVar);

$deleteRec = new account();
$deleteRec->id = $newID;
$deleteId = $deleteRec->delete();

$record= accounts::getRecordSet();
tableClass::populateTable($record);
$outputVar = new output();
$outputVar->header = "Delete Record ";
$outputVar->message = "Deleted record with id = $newID";
$outputVar->table = tableClass::getTable();
array_push($outputArray,$outputVar);




foreach($outputArray as $output){
    $output->printResults();
}

// print_r($outputArray);
//$outputVar->printResults();


// $record = accounts::getOneRecord(114);
// tableClass::populateTable($record);
// $record= todos::getRecordSet();
// tableClass::populateTable($record);
// $record= todos::getOneRecord(5);
// tableClass::populateTable($record);
// echo (tableClass::getTable());


 
 
 $deleteRec = new account();
 $deleteRec->id = $newID;
 $deleteId = $deleteRec->delete();


?>