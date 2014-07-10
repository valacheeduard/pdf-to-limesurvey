<?php
define("DB_AUTOQUERY_INSERT",0);
define("DB_AUTOQUERY_UPDATE",1);
define("DB_AUTOQUERY_DELETE",2);
//This requires PDO to be installed with PHP
if(!extension_loaded ('PDO' )){
	throw new Exception('The PDO extension is not available, DB.php can not be used',null);
}
class DB{
	private $options = Array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
		PDO::ATTR_PERSISTENT => false,
	);
	private $database = null;//the database connection
	private $result = null;//the result of an sql query or exec
	private $index = -1;//the row index in the result
	private $rowArray = null;//the last read row from the result
	private $sqlStatement;//holds the last query sent to query();
    private $lastParams; //holds the last sent params to query();
	private $errors;
	private $inTransaction;//indicates if beginTransaction has been called for the current DB instance
	private $escape_quotes = '';
    private $queryBuffer = Array();
    private $memoryLimit;

    /**
	 * This will prevent printing more than one general error.
	 *
	 * @var bool
	 */
	private $generalErrorPrinted = false;
	
	public $function;
		
	function __construct($persistent = false){
		try {
			$this->options[PDO::ATTR_PERSISTENT] = $persistent;
			$this->database = new PDO(DB_CONN_STRING, DB_USERNAME, DB_PASSWORD,$this->options);
		} catch (PDOException $e) {
			$this->onError($e);
		}
		$this->errors = Array();
        $this->memoryLimit = ini_get('memory_limit'); //in MB
        $this->memoryLimit *= 1024 * 1024;// in bytes
	}
	function __destruct(){
		//disconnecting;
		$this->database = null;
	}
	/**
	*
	* Prepare an sql query
	* @param string $sqlStatement
	* @return PDOStatement
	*/
	function prepare($sqlStatement){
		if(!is_object($this->database)){
			return null;
		}
		$this->sqlStatement = $sqlStatement;
		try{
			$query = $this->database->prepare($sqlStatement);
			return $query;
		}catch(PDOException $e){
			$this->onError($e);
			return null;
		}
	}
    private function executeQuery($oQuery,$params=null){
        /*$q = $this->getLastSqlStatement();
        if($q != ''){
            echo $q.'</br>';
        }*/

        /*$memoryUsed = memory_get_usage(true);
        if($this->memoryLimit - $memoryUsed < 16 * 1024 * 1024){
            $this->onError('Memory was almost exhausted. Stopping execution');
            return false;
        }*/

        $this->resetInternalBuffers();
        $this->lastParams = $params;
        $this->sqlStatement = $oQuery->queryString;
        if(!$oQuery->execute($params)){
            $err = $oQuery->errorInfo();
            $this->onError($err[2]);
            return false;
        }else{
            $this->result = $oQuery;
            $this->result->setFetchMode(PDO::FETCH_ASSOC);
        }
        return true;
    }
	/**
	*
	* Run an sql query
	* @param string $sqlStatement
	*/
	function query($sqlStatement,$params = Array()){
		if(!is_object($this->database)){
			return null;
		}
		try{
            if(isset($this->queryBuffer[$sqlStatement])){
                $query = $this->queryBuffer[$sqlStatement];
                $query->closeCursor();
            }else{
                $query = $this->database->prepare($sqlStatement);
                $this->queryBuffer[$sqlStatement] = $query;
            }
            $this->executeQuery($query,$params);
		}catch(PDOException $e){
			$this->onError($e);
		}
	}
	/**
	*
	* Executes an sql statement with multiple ; separated statements
	* @param string $sqlStatement
	*/
	function queryMultiple($sqlStatement){
		if(!is_object($this->database)){
			return;
		}
		//emulating multi query
		$queries = explode(";",$sqlStatement);
		foreach ($queries as $sql){
			$this->query($sql);
		}
	}
	/**
	 * 
	 * Executes an sql statement
	 * @param string $sqlStatement
	 */
	function exec($sqlStatement,$params = Array()){
		if(!is_object($this->database)){
			return;
		}
		try{
            $query = $this->database->prepare($sqlStatement);
            if($this->executeQuery($query,$params)){
                $this->result = $this->result->rowCount();
            }
		}catch(PDOException $e){
			$this->onError($e);
		}
	}
	/**
	 * 
	 * Executes an sql statement with multiple ; separated statements
	 * @param string $sqlStatement
	 */
	function execMultiple($sqlStatement){
		//emulating multi query
		$queries = explode(";",$sqlStatement);
		foreach ($queries as $sql){
			$this->exec($sql);
		}
	}
	/**
	   * Make automaticaly an sql query for prepare()
	   *
	   * Example : buildManipSQL('table_sql', array('field1', 'field2', 'field3'), DB_AUTOQUERY_INSERT)
	   *           will return the string : INSERT INTO table_sql (field1,field2,field3) VALUES (?,?,?)
	   *      - Be carefull ! If you don't give a $where param with an UPDATE or DELETE query, all
	   *        the records of the table will be updated or deleted!
	   *
	   * @param string name of the table
	   * @param ordered array containing the fields names
	   * @param int type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE or DB_AUTOQUERY_DELETE)
	   * @param string (in case of update or delete queries, this string will be put after the sql WHERE statement)
	   *
	   * @return string sql query for prepare()
	   * @access public
	   */
	function buildManipSQL($table, $table_fields, $mode, $where = false){
		if(!is_object($this->database)){
			return;
		}
		if (count($table_fields) == 0 && $mode != DB_AUTOQUERY_DELETE) {
			throw(new Exception('Not enough data for buildManipSql',null));
		}
		switch ($mode) {
			case DB_AUTOQUERY_INSERT:
				$cols = '['.implode('], [', $table_fields).']';
				$values = '?'.str_repeat(', ?', count($table_fields)-1);
				$sql = 'INSERT INTO '.$table.' ('.$cols.') VALUES ('.$values.')';
				break;
			case DB_AUTOQUERY_UPDATE:
				$set = '['.implode('] = ?, [', $table_fields).'] = ?';
				$sql = 'UPDATE '.$table.' SET '.$set;
				if ($where !== false) {
					$sql.= ' WHERE '.$where;
				}
				break;
			case DB_AUTOQUERY_DELETE:
				$sql = 'DELETE FROM '.$table;
				if ($where !== false) {
					$sql.= ' WHERE '.$where;
				}
				break;
		}
		return $sql;
	}
	/**
	 * Make automaticaly an insert or update query and call prepare() with it
	 *
	 * @param string table
	 * @param array the fields names
	 * @param int type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE or DB_AUTOQUERY_DELETE)
	 * @param string (in case of update queries, this string will be put after the sql WHERE statement)
	 * @param array that contains the types of the placeholders
	 *
	 * @return resource handle for the query
	 * @see buildManipSQL
	 * @access public
	 */
	function autoPrepare($table, $table_fields, $mode = DB_AUTOQUERY_INSERT, $where = false){
		if(!is_object($this->database)){
			return;
		}
		$query = $this->buildManipSQL($table, $table_fields, $mode, $where);
		try{
            if(isset($this->queryBuffer[$query])){
                $prep = $this->queryBuffer[$query];
                $prep->closeCursor();
            }else{
                $prep = $this->database->prepare($query);
                $this->queryBuffer[$query] = $prep;
            }
		}catch(PDOException $e){
			$this->onError($e);
		}
		return $prep;
	}
	/**
	 * Make automaticaly an insert or update query and call prepare() and execute() with it
	 *
	 * @param string name of the table
	 * @param array assoc ($key=>$value) where $key is a field name and $value its value
	 * @param int type of query to make (DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE or DB_AUTOQUERY_DELETE)
	 * @param string (in case of update or delete queries, this string will be put after the sql WHERE statement)
	 * @param array that contains the types of the placeholders
	 *
	 * @return mixed	
	 * @see autoPrepare
	 * @access public
	 */
	function autoExec($table, $fields_values, $mode = DB_AUTOQUERY_INSERT,$where = false){
		if(!is_object($this->database)){
			return;
		}
		if($fields_values === null){
			$fields_values = Array();
		}
		$query = $this->autoPrepare($table, array_keys($fields_values), $mode, $where);
		if(!is_object($query)){
			return null;
		}
		try{
			$params = array_values($fields_values);
            if($this->executeQuery($query,$params)){
			    $this->result = $query->rowCount();
            }
		}catch(PDOException $e){
			$this->onError($e);
		}
		return $this->result;
	}
	/**
	 * Returns the number of affected rows or the number of rows in the last select query for supporting drivers;
     * This will return only the row count of the last query() call for DBLIB
	 * @return number
     * @param $whatToCount string specifies what to send to function count()
	 */
	function numRows($whatToCount = '*'){
		if(!$this->result){
			return 0;
		}
        if(DB_PHPTYPE == 'dblib' || DB_PHPTYPE == 'odbc'){//no support for rowCount
            $db = new DB();
            $countQuery = 'SELECT COUNT('.$whatToCount.') AS count ';
            $prevQuery = $this->getLastSqlStatement();
            $fromPos = stripos($prevQuery,'from');
            $countQuery .= substr($prevQuery,$fromPos);
            $db->query($countQuery);
            return intval($db->getElement('count'));
        }
        else{
		    return $this->result->rowCount();
        }
	}
	/**
	 * Tells if there are any rows returned by the last query.
	 * @return boolean
	 */
	function areThereAnyRows(){
		if(!$this->result){
			return false;
		}
		if(DB_PHPTYPE != 'dblib'){
            if($this->result->rowCount() > 0){
			    return true;
            }
		}else{
            return $this->nextRow();
        }
		return false;
	}
	/**
	 * Travels $x number of rows through the results
	 * @param int $x
	 * @return boolean
	 */
	function seek($x){
		if(!$this->result || $x<0){
			return false;
		}
		try{
			do{
				$this->rowArray = $this->result->fetch();
				$this->index++;
			}while($this->rowArray !== FALSE && $this->index < $x );
		}catch(PDOException $e){
			$this->onError($e);
		}
		if($this->index < $x){//couldn't go that far
			return false;
		}
		return true;
	}
	/**
	 * Returns an Array of rows
	 * @return Array
	 */
	function getAllData(){
		$arr = Array();
		if(!$this->result || !is_object($this->result)){
			return false;
		}
		try{
			$arr = $this->result->fetchAll();
		}catch(PDOException $e){
			$this->onError($e);
		}
		if($arr === false){
			return null;
		}
		$this->resetInternalBuffers();
		return $arr; 
	}
	/**
	 * Adnavces one row ahead in the results set
	 * @return boolean
	 */
	function nextRow(){
		if(!isset($this->result) || !$this->result || !is_object($this->result)){
			return false;
		}
		try{
			$this->rowArray = $this->result->fetch(PDO::FETCH_ASSOC);
            if(is_array($this->rowArray)){
                foreach($this->rowArray as $key => $val){
                    $this->rowArray[$key] = trim($val);
                }
            }
		}catch(PDOException $e){
			$this->onError($e);
		}
		if($this->rowArray === false){
			return false;
		}else{
			$this->index++;		
			return true;
		}
	}
	/**
	 * Returns the last fetched row from the results
	 * @return Array
	 */
	function getRow(){
		if($this->rowArray === null){
			$this->nextRow();
		}
		return $this->rowArray;
	}
	/**
	 * Returns the value of the $element column from the last fetched row, if column name exists
	 * @param string $element
	 * @return string|null
	 */
	function getElement($element){
		if($this->rowArray === null){
			$this->nextRow();
		}
		if(is_array($this->rowArray) && array_key_exists($element,$this->rowArray)){
			return $this->rowArray[$element];
		}
		return null;
	}
	/**
	 * Tells how many rows were affected by the last sql statement
	 * @return number|NULL
	 */
	function getAffectedRows() {
		if(!$this->result){
			return 0;
		}
		if(is_numeric($this->result)){//after running a manipulating sql statement with exec, the result will be the number of affcted rows
			return $this->result;
		}
	}
	/**
	 * Returns an Array of column names (if the last query returned results)
	 * @return Array
	 */
	function getColumnNames(){
		if(!$this->rowArray){
			$this->nextRow();
		}
		if(!$this->rowArray){
			return Array();
		}
		return array_keys($this->rowArray);
	}
	function getLastInsertID($table, $IDfield){
		if(!is_object($this->database)){
			return '';
		}
        $id = null;
        switch(DB_PHPTYPE){
            case 'dblib':
            case 'odbc':
                $idQuery = 'SELECT @@IDENTITY FROM ['.$table.']';
                $query = $this->database->prepare($idQuery);
                if($this->executeQuery($query)){
                    $id = intval($query->fetchColumn());
                }
                break;
            case 'pgsql':
                $seq = $table.(empty($IDfield) ? '' : '_'.$IDfield.'_seq');
                $id = $this->database->lastInsertId($seq);
                break;
        }
        if(!$id){
			$err = $this->database->errorInfo();
			$err = $err[2];
			$this->onError($err);
		}
		return $id;
	}
	/**
	 * Puts the database in transaction mode
	 */
	function beginTransaction(){
		if(!is_object($this->database)){
			return '';
		}
        if(DB_PHPTYPE != 'dblib'){
            try{
                $this->result = $this->database->beginTransaction();
                if(!$this->result){
                    $err = $this->database->errorInfo();
                    $err = $err[2];
                    $this->onError($err);
                }
                try {
                    $this->database->beginTransaction();
                    $this->onError('Cancelling, Transaction was not properly started');
                } catch (PDOException $e) {
                    //Transaction is running (because trying another one failed
                }
            }catch(PDOException $e){
                $this->onError($e);
            }
            $this->inTransaction = true;
        }
	}
	/**
	 * Commits the last transaction
	 * @return mixed
	 */
	function commit(){
		if(!is_object($this->database)){
			return '';
		}
		if($this->inTransaction){
			try{
				$this->result = $this->database->commit();
				if(!$this->result){
					$err = $this->database->errorInfo();
					$err = $err[2];
					$this->onError($err);
				}
			}catch(PDOException $e){
				$this->onError($e);
			}
			$this->inTransaction = false;
		}else{
			return null;
		}
		return $this->result;
	}
	/**
	* Rolls back the last transaction
	* @return mixed
	*/
	function rollback(){
		if(!is_object($this->database)){
			return '';
		}
		if($this->inTransaction){
			try{
				$this->result = $this->database->rollBack();
				if(!$this->result){
					$err = $this->database->errorInfo();
					$err = $err[2];
					$this->onError($err);
				}
			}catch(PDOException $e){
				$this->onError($e);
			}
			$this->inTransaction = false;
		}else{
			return null;
		}
		return $this->result;
	}
	/**
	 * Frees the results buffer
	 */
	function free(){
		if(is_object($this->result)){
			try{
				$this->result->closeCursor();
				$this->rowArray = null;
			}catch(PDOException $e){
				$this->onError($e);
			}
		}
	}
	/**
	 * Quotes the given string using the driver specific quotes
	 * @param string $string
	 * @param int $parameter_type
	 * @return string
	 */
	function quote($string,$parameter_type = PDO::PARAM_STR){
		if(!is_object($this->database)){
			return '-';
		}
		try{
            if(DB_PHPTYPE == 'odbc'){
                $res = "'".$this->escape($string)."'";
            }else{
			    $res = $this->database->quote($string,$parameter_type);
            }
		}catch(PDOException $e){
			$this->onError($e);
		}	
		return $res;
	}
	/**
	 * Escapes the string
	 * @param string $string
	 * @return string
	 */
	function escape($string){
		if ($this->escape_quotes !== "'") {
			$text = str_replace($this->escape_quotes, $this->escape_quotes.$this->escape_quotes, $string);
		}
		return str_replace("'", $this->escape_quotes . "'", $text);
	}
	/**
	 * Handles errors and exceptions
	 * @param mixed $error
	 */
	function onError($error){
        if(is_numeric($error)){
            $lastSQLStatement = $this->sqlStatement;
            $query = "select m.text from sys.messages m
                        join sys.syslanguages l
                        ON m.language_id = l.msglangid WHERE l.name = 'us_english' AND m.message_id = ?";
            $this->query($query,Array($error));
            $this->errors[] = $this->getElement('text');
            $this->sqlStatement = $lastSQLStatement;
            echo $this->getElement('text');
        }elseif (is_object($error) && get_class($error) == 'PDOException') {
			$this->errors[] = $error->getCode().' '.$error->getMessage()."\n sql: ".$this->sqlStatement;
			echo 'SQL error ('.$error->getCode().'): '.$error->getMessage()."\n sql: ".$this->sqlStatement;
		}
		else{
			if(strpos($error,'duplicate key value violates unique constraint') !== FALSE){
				$error = 'duplicated record found';
			}
			if(strpos($error,'violates foreign key constraint') !== FALSE){
				//show only the detail part
				$pos = strpos($error,'DETAIL:');
				$error = substr($error, $pos + 8);
			}
			$this->errors[] = $error;
		}
	}
	/**
	 * Returns the error array
	 */
	function getErrors(){
		return $this->errors;
	}
	/**
	* Resets errors array
	*/
	function resetErrors(){
		$this->errors = Array();
	}
	/**
	 * Returns the error directly from the database object
	 * @return void|unknown
	 */
	function getDBStatus(){
		if(!is_object($this->database)){
			return;
		}
		$err = $this->database->errorInfo();
		$err = $err[2];
		return $err;
	}
	/**
	 * Returns the index of the current row in the result set
	 * @return int 
	 * 
	 */
	function getResultIndex(){
		return $this->index;
	}
    function getLastSqlStatement(){
        $query = $this->sqlStatement;
        if(is_array($this->lastParams)){
            foreach($this->lastParams as $param){
                if($param === null){
                    $param = 'null';
                }elseif(!is_numeric($param)){
                    $param = $this->quote($param);
                }
                $query = preg_replace('/\?/',$param,$query,1);
            }
        }
        return $query;
    }
	/**
	 * Closes the cursor, resets the index and reintializes the internal row array
	 * Enter description here ...
	 */
	private function resetInternalBuffers(){
		if(isset($this->result) && is_object($this->result)){
            $this->result->fetchAll();
			$this->result->closeCursor();
            unset($this->result);
		}
        $this->result = null;
		$this->rowArray = null;
		$this->index = -1;
	}

    function getObject($className = 'stdClass'){
        $obj = new $className();
        $row = $this->getRow();
        if($row){
            foreach($row as $attribute => $value){
                $obj->$attribute = $value;
            }
        }
        return $obj;
    }

    



}
?>