    <?php

    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
    echo '<link rel="stylesheet" href="styles.css" type="text/css">';
    define('DATABASE', 'vk427');
    define('USERNAME', 'vk427');
    define('PASSWORD', 'R0adrunner');
    define('CONNECTION', 'sql1.njit.edu');

    class dbConn
    {

        protected static $db;

        private function __construct()
        {

            try {
                self::$db = new PDO('mysql:host=' . CONNECTION . ';dbname=' . DATABASE, USERNAME, PASSWORD);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
            } catch (PDOException $e) {
                echo "Connection Error: " . $e->getMessage();

            }
        }

        public static function getConnection()
        {

            if (!self::$db) {
                new dbConn();
            }
            return self::$db;

        }
    }

    /* 
    *Abstrcat class  to select record(s) from a table
    */
    abstract class collection
    {
    /*
    * Method to retrieve records in a table
    */
        public static function getRecordSet()
        {
            $db        = dbConn::getConnection();
            $tableName = get_called_class();
            $sql       = "SELECT * from " . $tableName;
            $statement = $db->prepare($sql);
            $statement->execute();
            $class = static::$modelName;
            $statement->setFetchMode(PDO::FETCH_CLASS, $class);
            $recordSet = $statement->fetchAll();
            return $recordSet;
        }
        /* 
        * Method to retrieve one record from table using ID
        */

        public static function getOneRecord($id)
        {
            $db        = dbConn::getConnection();
            $tableName = get_called_class();
            $sql       = "SELECT * from " . $tableName . " WHERE id = $id";
            $statement = $db->prepare($sql);
            $statement->execute();
            $class = static::$modelName;
            $statement->setFetchMode(PDO::FETCH_CLASS, $class);
            $record = $statement->fetchAll();
            return $record;
        }
    }

    class accounts extends collection
    {
        protected static $modelName = 'account';
    }

    class todos extends collection
    {
        protected static $modelName = 'todo';
    }

    /* 
    *Abstract class model to perform INSERT,UPDATE & DELETE operations in a table
    */
    abstract class model
    {

        protected $tableName;
        protected static $statement;
        /* 
        * Method to INSERT or UPDATE a record into the table 
        */

        public function save()
        {
            $array = get_object_vars($this);
            unset($array["tableName"]);

            if ($this->id == '') {

                $sql = $this->insert();
            } else {
                $sql = $this->update();

            }

            $db = dbConn::getConnection();
            self::$statement = $db->prepare($sql);
           
            self::bindValues($array,$this);        

            self::$statement->execute();

            $lastId = $db->lastInsertId();
            return ($lastId);

        }

    /*
    * Method to bind the values
    */
        private static function bindValues($array,$obj){
            foreach ($array as $key => $value) {
                if ($obj->id == '') {
                    self::$statement->bindValue(":$key", "$value");
                } else {
                    if ($value != '' && $key != "id") {
                        self::$statement->bindValue(":$key", "$value");
                    }
                }
            }
        }

    /*
    * Method to prepare the insert query
    */

        private function insert()
        {
            $array = get_object_vars($this);

            unset($array["tableName"]);

            $columnString = implode(',', array_keys($array));
            $valueString  = ":" . implode(',:', array_keys($array));
            $sql          = "INSERT INTO $this->tableName (" . $columnString . ") VALUES (" . $valueString . ")";
            return $sql;

        }

     /*
    * Method to prepare the update query
    */

        private function update()
        {
            $array = get_object_vars($this);

            unset($array["tableName"]);

            $sql = "UPDATE " . $this->tableName . " SET";
            foreach ($array as $key => $value) {
                if ($value != "" & $key != "id") {
                    $sql .= " " . $key . " = :$key ,";
                }

            }

            $sql = substr($sql, 0, -1);

            $sql .= " WHERE id = " . $this->id;
            return $sql;

        }

    /*
    * Method to delete a record 
    */

        public function delete()
        {
            $array           = get_object_vars($this);
            $sql             = "DELETE FROM " . $this->tableName . " WHERE id = " . $this->id;
            $db              = dbConn::getConnection();
            self::$statement = $db->prepare($sql);
            self::$statement->execute();
        }

    }

    class account extends model
    {

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

    class todo extends model
    {

        public $id;
        public $owneremail;
        public $ownerid;
        public $createddate;
        public $duedate;
        public $message;
        public $isdone;

        public function __construct()
        {
            $this->tableName = 'todos';

        }
    }


    /*
    * Class to prepare HTML table definition
    */
    class tableClass
    {

        private static $table;
     
     /*
    * Method to select the action to be performed based on the count
    */
        public static function populateTable($rec)
        {
            self::$table = "";
            if (count($rec) == 0) {
                self::$table .= "<b> No records returned from table <b> <br>";
            } else {

                self::drawTable($rec);

            }
            return self::$table;

        }
     /*
    * Method to  create the HTML table
    */
        private static function drawTable($rec)
        {

            self::$table .= '<table>';
            self::$table .= '<tr>';
            $headerFields = $rec[0];
            foreach ($headerFields as $key => $value) {
                self::$table .= "<th>$key</th>";

            }
            self::$table .= '</tr>';
            foreach ($rec as $row) {
                self::$table .= '<tr>';

                foreach ($row as $key => $value) {
                    self::$table .= "<td>$value</td>";

                }
                self::$table .= '</tr>';

            }

            self::$table .= '</table> <br>';

        }

    }

     /*
    * Template Class  to output  the results
    */
    class output
    {
        private $header;
        private $message;
        private $table;

        public function printResults()
        {
            echo "<h2> $this->header </h2>";
            echo "$this->message <br>";
            echo "$this->table";
            echo ("<hr>");
        }

    /*
    * Method to generate the table based on the action performed and the resulting data.
    */
        public function templateGenerator($action, $messageVar, $tableData)
        {
            $this->table = $tableData;
            switch ($action) {
                case 'INSERT':{
                        $arrays        = get_object_vars($messageVar);
                        $this->header  = "<h2>Insert New Record</h2>";
                        $this->message = "Inserted New Record with Data: ";
                        foreach ($arrays as $key => $value) {
                            if($key!= "id"){
                            $this->message .= "$key = $value ";
                         }
                        }
                        break;
                    }
                case 'UPDATE':{
                        $this->header  = "<h2>Update Record</h2>";
                        $this->message = "Update Record with Id: $messageVar";
                        break;
                    }
                case 'SELECTALL':{
                        $this->header  = "<h2>Select All Records</h2>";
                        $this->message = "Select ALL Records";
                        break;

                    }
                case 'SELECT':{

                        $this->header  = "<h2>Select One Record</h2>";
                        $this->message = "Select Record with Id: $messageVar";
                        break;
                    }
                case 'DELETE':{
                        $this->header  = "<h2>Delete Record</h2>";
                        $this->message = "Delete Record with Id: $messageVar";
                    }
                default:

                    break;
            }

        }
    }


    $outputArray = array();
    echo "<h1>'accounts' Table</h1>";


     /*
    * Method Calls to perform CRUD operations on accounts table
    */
    $records   = accounts::getRecordSet();
    $outputVar = new output();
    $outputVar->templateGenerator("SELECTALL", $records, tableClass::populateTable($records));
    array_push($outputArray, $outputVar);

    $id        = 11;
    $record    = accounts::getOneRecord($id);
    $outputVar = new output();
    $outputVar->templateGenerator('SELECT', $id, tableClass::populateTable($record));
    array_push($outputArray, $outputVar);

    $newAccount           = new account();
    $newAccount->email    = "janedoe121@gmail.com";+
    $newAccount->fname    = "Jane";
    $newAccount->lname    = "Doe";
    $newAccount->phone    = '007008';
    $newAccount->birthday = '19500101';
    $newAccount->gender   = 'female';
    $newAccount->password = '001002';
    $newID                = $newAccount->save();

    $insertedAccounts = accounts::getRecordSet();
    $outputVar        = new output();

    $outputVar->templateGenerator("INSERT", $newAccount, tableClass::populateTable($insertedAccounts));
    array_push($outputArray, $outputVar);

    $updateAccount        = new account();
    $updateAccount->id    = $newID;
    $updateAccount->email = "doe007@gmail.com";
    $updateAccount->save();

    $updatedAccounts = accounts::getRecordSet();
    $outputVar       = new output();
    $outputVar->templateGenerator("UPDATE", $newID, tableClass::populateTable($updatedAccounts));
    array_push($outputArray, $outputVar);

    $deleteAccount     = new account();
    $deleteAccount->id = $newID;
    $deleteAccount->delete();

    $deletedAccounts = accounts::getRecordSet();
    $outputVar       = new output();
    $outputVar->templateGenerator("DELETE", $newID, tableClass::populateTable($deletedAccounts));
    array_push($outputArray, $outputVar);

    foreach ($outputArray as $output) {
        $output->printResults();
    }

    /*
    * Resetting the output array to perform operation on todos table
    */

    $outputArray = array();
    echo "<h1>'todos' Table</h1>";

     /*
    * Method Calls to perform CRUD operations on accounts table
    */

    $todoRecords = todos::getRecordSet();
    $outputVar   = new output();
    $outputVar->templateGenerator("SELECTALL", $todoRecords, tableClass::populateTable($todoRecords));
    array_push($outputArray, $outputVar);

    $id         = 4;
    $todoRecord = todos::getOneRecord($id);
    $outputVar  = new output();
    $outputVar->templateGenerator('SELECT', $id, tableClass::populateTable($todoRecord));
    array_push($outputArray, $outputVar);

    $newTodo              = new todo();
    $newTodo->owneremail  = "janedoe121@gmail.com";+
    $newTodo->ownerid     = "02";
    $newTodo->createddate = "2017-11-20";
    $newTodo->duedate     = "2017-12-20";
    $newTodo->message     = 'Record inserted';
    $newTodo->isdone      = '0';
    $newTodoId            = $newTodo->save();

    $insertedTodos = todos::getRecordSet();
    $outputVar     = new output();
    $outputVar->templateGenerator("INSERT", $newTodo, tableClass::populateTable($insertedTodos));
    array_push($outputArray, $outputVar);

    $updateTodo          = new todo();
    $updateTodo->id      = $newTodoId;
    $updateTodo->message = "Record updated";
    $updateTodo->isdone  = '1';
    $updateTodo->save();

    $updatedTodos = todos::getRecordSet();
    $outputVar    = new output();
    $outputVar->templateGenerator("UPDATE", $newTodoId, tableClass::populateTable($updatedTodos));
    array_push($outputArray, $outputVar);

    $deleteTodo     = new todo();
    $deleteTodo->id = $newTodoId;
    $deleteTodo->delete();

    $deletedTodos = todos::getRecordSet();
    $outputVar    = new output();
    $outputVar->templateGenerator("DELETE", $newTodoId, tableClass::populateTable($deletedTodos));
    array_push($outputArray, $outputVar);

    foreach ($outputArray as $output) {
        $output->printResults();
    }
