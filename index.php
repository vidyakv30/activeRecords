<?php
//turn on debugging messages
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define('DATABASE', 'vk427');
define('USERNAME', 'vk427');
define('PASSWORD', 'R0adrunner');
define('CONNECTION', 'sql1.njit.edu');

class dbConn{
    //variable to hold connection object.
    protected static $db;
    //private construct - class cannot be instatiated externally.
    private function __construct() {
        try {
            // assign PDO object to db variable
            self::$db = new PDO( 'mysql:host=' . CONNECTION .';dbname=' . DATABASE, USERNAME, PASSWORD );
            self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            echo "Connected Succesfully <br>";
        }
        catch (PDOException $e) {
            //Output error - would normally log this to error file rather than output to user.
            echo "Connection Error: " . $e->getMessage();
        }
    }
    // get connection function. Static method - accessible without instantiation
    public static function getConnection() {
        //Guarantees single instance, if no connection object exists then create one.
        if (!self::$db) {
            //new connection object.
            new dbConn();
        }
        //return connection.
        return self::$db;
    }
}
class collection {
     private static $recordsSet;
    
    public static function create() {
      $model = new static::$modelName;
      return self::$model;
    }
    public static function findAll() {
        $db = dbConn::getConnection();
        $tableName = get_called_class();
        $sql = 'SELECT * FROM ' . $tableName;
        $statement = $db->prepare($sql);
        $statement->execute();
        $class = static::$modelName;
        $statement->setFetchMode(PDO::FETCH_CLASS, $class);
        self::$recordsSet =  $statement->fetchAll();
        return self::$recordsSet;
    }

    static public function findOne($id) {
        $db = dbConn::getConnection();
        $tableName = get_called_class();
        $sql = 'SELECT * FROM ' . $tableName . ' WHERE id =' . $id;
        $statement = $db->prepare($sql);
        $statement->execute();
        $class = static::$modelName;
        $statement->setFetchMode(PDO::FETCH_CLASS, $class);
        $recordsSet =  $statement->fetchAll();
        return $recordsSet[0];
    }
}
class accounts extends collection {
    protected static $modelName = 'account';
}



class model {
    protected $tableName;
    public function save()
    {

$array = get_object_vars($this);
        print_r($array);

        if ($this->id == '') {

            $sql = $this->insert();
        } else {
             $sql = $this->update();
        }
        $db = dbConn::getConnection();
        print_r($sql);
        $statement = $db->prepare($sql);
        $statement->execute();
        
        
        
       // echo "INSERT INTO $tableName (" . $columnString . ") VALUES (" . $valueString . ")</br>";
        echo 'I just saved record: ' . $this->id;
    }
    private function insert() {
        //$tableName = get_called_class();
        $array = get_object_vars($this);
        $columnString = implode(',', $array);
        $valueString = ":".implode(',:', $array);
        $sql = "INSERT INTO $tableName (" . $columnString . ") VALUES (" . $valueString . ")";
        return $sql;
    }
    private function update() {
        //$tableName = get_called_class();
        $array = get_object_vars($this);
        print_r($array);
        $counter=0;
        foreach ($array as $key=>$value){
            if($key=='id'){
                print_r($value);
                $condition = "$key = $value";
                print_r($condition);
            }
            else{
                if($value!=''&&$key!='tableName'){
            $stmt[$counter] = "$key = '$value'";
            $counter++;
        }
        }
        
    }
    $stmtString = implode(',',$stmt);
    $sql = "UPDATE $this->tableName SET ". $stmtString." WHERE ". $condition ;
        return $sql;
        echo 'I just updated record' . $this->id;
    }
    public function delete() {
        echo 'I just deleted record' . $this->id;
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



echo "<b> Find all </b> <br>";
$records = accounts::findAll();
print_r($records);
echo "<b> Find one </b> <br>";
$record = accounts::findOne(11);
print_r($record);
echo "<b> Insert Record </b><br>";
$newRec = new account();
$newRec->email="vkv@gmail.com";
$newRec->id=13;
$newRec->save();



?>