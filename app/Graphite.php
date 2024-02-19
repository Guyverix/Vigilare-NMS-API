<?php
//declare(strict_types=1);

// https://gist.github.com/bradtraversy/a77931605ba9b7cf3326644e75530464
// Using example for PDO class to make new generic logger class
class Graphite {
  private $hostname="192.168.15.193";
  private $port="2003";
  private $protocol="plain";
  public $UDP="false";
  public  $prefix="nms";

  // Build out connection defaults
  // Default to TCP connections
//  public function __construct(?string $prefix="nms", ?string $UDP="false"){
  public function __construct(){
    // If we are given a minimum severity to log, update
    // so all functions know it
//    $this->hostname=$hostname;
//    $this->port=$port;
//    $this->protocol=$protocol;
//    $this->UDP=$UDP;
//    $this->$prefix=$prefix;
  }

    public function testMetric( $metric, $value ) {
    if ( $this->UDP == "true" ) {
      try {
        $fp = fsockopen("udp://$this->hostname", $this->port, $errno, $errstr);
        if (!empty($errno)) echo $errno;
        if (!empty($errstr)) echo $errstr;
        $message = "$this->prefix.$metric $value ".time().PHP_EOL;
        $bytes = fwrite($fp, $message);
        return "graphite response: " . $message;
      }
      catch (Exception $e) {
        return "\nNetwork error: ".$e->getMessage();
      }
    }
    else {
      // We know we are using a TCP connection
      try {
        $fp = fsockopen("tcp://$this->hostname", $this->port, $errno, $errstr);
        if (!empty($errno)) echo $errno;
        if (!empty($errstr)) echo $errstr;
        $message = "$this->prefix.$metric $value ".time().PHP_EOL;
        $bytes = fwrite($fp, $message);
        return "graphite response: " . $message;
      }
      catch (Exception $e) {
        return "\nNetwork error: ".$e->getMessage();
      }
    }
  }


  public function postMetric( $metric, $value ) {
    if ( $this->UDP == "true" ) {
      try {
        $fp = fsockopen("udp://$this->hostname", $this->port, $errno, $errstr);
        if (!empty($errno)) echo $errno;
        if (!empty($errstr)) echo $errstr;
        $message = "$prefix.$metric $value ".time().PHP_EOL;
        $bytes = fwrite($fp, $message);
        return $message;
      }
      catch (Exception $e) {
        return "\nNetwork error: ".$e->getMessage();
      }
    }
    else {
      // We know we are using a TCP connection
      try {
        $fp = fsockopen("tcp://$this->hostname", $this->port, $errno, $errstr);
        if (!empty($errno)) echo $errno;
        if (!empty($errstr)) echo $errstr;
        $message = "$prefix.$metric $value ".time().PHP_EOL;
        $bytes = fwrite($fp, $message);
        return $message;
      }
      catch (Exception $e) {
        return "\nNetwork error: ".$e->getMessage();
      }
    }
  }
}
?>
