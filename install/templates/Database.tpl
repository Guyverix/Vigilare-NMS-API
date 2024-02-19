<?php
/*
declare(strict_types=1);
// https://gist.github.com/bradtraversy/a77931605ba9b7cf3326644e75530464
*/

class Database {
  private \$host = \"${DB_IP}\";
  private \$user = \"${DB_USER}\";
  private \$pass = \"${DB_PASS}\";
  private \$dbname = \"${DB_DATABASE}\";
  private \$dbh;
  public \$error;
  public \$stmt;

  public function __construct(){
    // Set DSN
    // SETTING ERRMODE_WARNING CATCHES DUPLICATE KEY VIOLATIONS
    \$dsn = \'mysql:host=\' . \$this->host . \';dbname=\' . \$this->dbname;


    \$options = array (
      PDO::ATTR_PERSISTENT => false,
      // PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_EMULATE_PREPARES => true,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );

    // Create a new PDO instanace
    try {
      \$this->dbh = new PDO (\$dsn, \$this->user, \$this->pass, \$options);
    }
    // Catch any errors and return them for error correction
    catch ( PDOException \$e ) {
      \$this->error = \$e->getMessage();
    return \$this->error;
    }
  }

  // Prepare statement with query
  public function query(\$query) {
    \$this->stmt = \$this->dbh->prepare(\$query);
  }
  // Run a prepare to add escape sequences to be used elsewhere
  public function prepare(\$query) {
    \$this->stmt = \$this->dbh->prepare(\$query);
    return \$this->stmt ;
  }

    public function statement() {
    return \$this->stmt ;
  }

  public function dump() {
    return \$this->stmt->debugDumpParams();
  }

  // Bind values
  public function bind(\$param, \$value, \$type = null) {
    if (is_null (\$type)) {
      switch (true) {
      case is_int (\$value) :
        \$type = PDO::PARAM_INT;
	break;
      case is_bool (\$value) :
        \$type = PDO::PARAM_BOOL;
	break;
      case is_string (\$value) :
        \$type = PDO::PARAM_STR;
	break;
      case is_null (\$value) :
        \$type = PDO::PARAM_NULL;
	break;
      default :
        \$type = PDO::PARAM_STR;
      }
    }
    \$this->stmt->bindValue(\$param, \$value, \$type);
  }
/*
  An ill concieved idea of testing what the binding values and
  types were.  This did not work, sigh...

  public function bindResult(\$param, \$value, \$type = null) {
    if (is_null (\$type)) {
      switch (true) {
      case is_int (\$value) :
        \$type = PDO::PARAM_INT;
        break;
      case is_bool (\$value) :
        \$type = PDO::PARAM_BOOL;
        break;
      case is_string (\$value) :
        \$type = PDO::PARAM_STR;
        break;
      case is_null (\$value) :
        \$type = PDO::PARAM_NULL;
        break;
      default :
        \$type = PDO::PARAM_STR;
      }
    }
    \$this->stmt->bindValue(\$param, \$value, \$type);
    return \$this->stmt;
  }
*/
  // Adds quotes to string data
  public function quote(\$arg){
    return \$this->dbh->quote(\$arg);
  }

  // Execute the prepared statement
  public function execute(){
    try {
     return \$this->stmt->execute();
    }
    catch(PDOException \$e) {
      // Catch exception, set it to our error var for use
      \$this->error = \$e->getMessage();
      return \'ERROR\';
      // return  \$e->getMessage();
    }
  }

  // Execute the prepared statement with args..
  // This is not really needed, as bind is used
  // and that is what passes the args into the execute
  public function executeArgs(\$arg){
    return \$this->stmt->execute(\$arg);
  }

  // Check if the database is connected
  public function ping(){
      return \"PONG\";
  }

  // Get result set as array of objects
  public function resultset(){
    \$this->execute();
    return \$this->stmt->fetchAll(PDO::FETCH_OBJ);
  }

  // Get single record as object (first row returned)
  public function single(){
    \$this->execute();
    return \$this->stmt->fetch(PDO::FETCH_OBJ);
  }

  // Get record row count
  public function rowCount(){
    return \$this->stmt->rowCount();
  }

  // Returns the last inserted ID
  public function lastInsertId(){
    return \$this->dbh->lastInsertId();
  }

  // Dump the entire query that prepare created
  public function prepareDump() {
    return \$this->stmt->debugDumpParams();
    // return \$this->dbh->debugDumpParams();
  }

  public function errorInfo(){
    return \$this->dbh->errorInfo();
  }

  public function errorCode(){
    return \$this->dbh->errorCode();
  }
}

?>
