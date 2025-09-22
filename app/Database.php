<?php
/*
declare(strict_types=1);
// https://gist.github.com/bradtraversy/a77931605ba9b7cf3326644e75530464
*/

class Database {
  private $host;
  private $user;
  private $pass;
  private $dbname;
  private $port;
  private $dbh;

  public $error;
  public $stmt;

  private ?array $errorInfo = null;      // [sqlstate, driver_code, message]
  private ?PDOException $lastException = null;
  private $errorCode;

  public function __construct(){
    include("config.php");
    $this->host = $dbHost;
    $this->dbname = $dbDatabase;
    $this->user = $dbUser;
    $this->pass = $dbPass;
    $this->port = $dbPort;

    // Set DSN https://www.php.net/manual/en/pdo.construct.php
    //    $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';port='. $this->port;  // seems to not like port being seporate from host
    $dsn = 'mysql:host=' . $this->host . ';port='. $this->port . ';dbname=' . $this->dbname;

    // SETTING ERRMODE_WARNING CATCHES DUPLICATE KEY VIOLATIONS
    $options = array (
      PDO::ATTR_PERSISTENT => false,
      //PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      //PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
      PDO::ATTR_EMULATE_PREPARES => true,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );

    // Create a new PDO instanace
    try {
      $this->dbh = new PDO ($dsn, $this->user, $this->pass, $options);
    }
    // Catch any errors and return them for error correction
    catch ( PDOException $e ) {
      $this->error = $e->getMessage();
      return $this->error;
    }
  }

  // Prepare statement with query
  public function query($query) {
    $this->stmt = $this->dbh->prepare($query);
  }
  // Run a prepare to add escape sequences to be used elsewhere
  public function prepare($query) {
    $this->stmt = $this->dbh->prepare($query);
    return $this->stmt ;
  }

  public function statement() {
    return $this->stmt ;
  }

  public function dump() {
    return $this->stmt->debugDumpParams();
  }

  // Bind values
  public function bind($param, $value, $type = null) {
    if (is_null ($type)) {
      switch (true) {
      case is_int ($value) :
        $type = PDO::PARAM_INT;
	      break;
      case is_bool ($value) :
        $type = PDO::PARAM_BOOL;
      	break;
      case is_string ($value) :
        $type = PDO::PARAM_STR;
      	break;
      case is_null ($value) :
        $type = PDO::PARAM_NULL;
      	break;
      default :
        $type = PDO::PARAM_STR;
      }
    }
    $this->stmt->bindValue($param, $value, $type);
  }
  
/*
  An ill concieved idea of testing what the binding values and
  types were.  This did not work, sigh...

  public function bindResult($param, $value, $type = null) {
    if (is_null ($type)) {
      switch (true) {
      case is_int ($value) :
        $type = PDO::PARAM_INT;
        break;
      case is_bool ($value) :
        $type = PDO::PARAM_BOOL;
        break;
      case is_string ($value) :
        $type = PDO::PARAM_STR;
        break;
      case is_null ($value) :
        $type = PDO::PARAM_NULL;
        break;
      default :
        $type = PDO::PARAM_STR;
      }
    }
    $this->stmt->bindValue($param, $value, $type);
    return $this->stmt;
  }
*/

  // Adds quotes to string data
  public function quote($arg){
    return $this->dbh->quote($arg);
  }

  // Execute the prepared statement
  public function execute(){
    $this->errorInfo = null;
    $this->errorCode = null;
    $this->lastException = null;
    try {
     return $this->stmt->execute();
    }
    catch(PDOException $e) {
      /*
        Catch exception, set it to our error var for use
        Class does not want to set errorCode value from exception AARRGGH.
        Match against string: 'errorInfo' to see if there is an error
      */
//      $this->errorInfo =  $e->errorInfo ?? [null, (int)$e->getCode(), $e->getMessage()];
//      $this->dbh->errorInfo =  $e->errorInfo;
//      $this->dbh->errorCode = (int)$e->getCode();
      //      return $e->getCode();
      //      $errInfo = json_decode(json_encode($e, 1), true);
      //      return $errInfo[0];
      return json_encode($e, true);
      // Old style adding ERROR as a match string
      // $this->errorInfo =  $e->errorInfo;
      // return 'ERROR: ' . json_encode($this->errorInfo, true);
    }
  }

  // Execute the prepared statement with args..
  // This is not really needed, as bind is used
  // and that is what passes the args into the execute
  public function executeArgs($arg){
    return $this->stmt->execute($arg);
  }

  // Check if the database is connected
  public function ping(){
      return "PONG";
  }

  // Get result set as array of objects (normal?)
  public function resultset(){
    $this->execute();
    return $this->stmt->fetchAll(PDO::FETCH_OBJ);
  }

  // Get result set as associated array of objects
  public function resultsetArray(){
    $this->execute();
    return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Get single record as object (first row returned)
  public function single(){
    $this->execute();
    return $this->stmt->fetch(PDO::FETCH_OBJ);
  }

  // Get record row count
  public function rowCount(){
    return $this->stmt->rowCount();
  }

  // Returns the last inserted ID
  public function lastInsertId(){
    return $this->dbh->lastInsertId();
  }

  // Dump the entire query that prepare created
  public function prepareDump() {
    return $this->stmt->debugDumpParams();
    // return $this->dbh->debugDumpParams();
  }

  public function errorInfo(){
//    return $e->errorInfo();
    return $this->dbh->errorInfo();
  }

  public function errorCode(){
//    return $e->errorCode();
    return $this->dbh->errorCode();
  }
}

?>
