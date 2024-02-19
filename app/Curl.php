<?php
/*
  This is just a simple Curl class that makes sense to me.
  Likely this will be retired in a future release and use
  the internal Curl class instead.
*/


class Curl{
  // class variable that will hold the curl request handler
  private $handler = null;
  // class variable that will hold the url
  public $url = '';
  // class variable that will hold the info of our request
//  private $info = [];
  public $info = [];
  // class variable that will hold the data inputs of our request
//  private $data = [];
  public $data = [];
  // class variable that will tell us what type of request method to use (defaults to get)
  public $method = 'get';
  // class variable that will hold the response of the request in string
  public $content = '';
  // class variable that will hold any custom headers
  public $headers = [];
  // Get trap URL from config.php
  private $apiUrl;
  private $apiPort;

  // Set some sane defaults
  public function __construct() {
    include("config.php");
    $this->url = $apiUrl . ':' . $apiPort . "/trap";
    $this->method = "get";
  }

  // function to set data inputs to send
  public function url( $url = '' ){
    $this->url = $url;
    return $this;
  }

  public function headers( $headers = []) {
    $this->headers = [$headers];
    return $this;
  }

  // function to set data inputs to send
  public function data( $data = [] ){
    $this->data = $data;
    return $this;
  }

  // function to set request method (defaults to get)
  public function method( $method = 'get' ){
    $this->method = $method;
    return $this;
  }

  public function content() {
    return $this->content;
  }

  // function that will send our request
  public function send(){
    try {
      if( $this->handler == null ) {
        $this->handler = curl_init();
      }
      switch( strtolower( $this->method ) ){
        case 'post':
          curl_setopt_array ( $this->handler , [
          CURLOPT_URL => $this->url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_POST => count($this->data),
          CURLOPT_POSTFIELDS => http_build_query($this->data),
          CURLOPT_HTTPHEADER => $this->headers,
          ] );
          break;
        case 'put':
          curl_setopt_array ( $this->handler , [
          CURLOPT_URL => $this->url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => http_build_query($this->data),
          CURLOPT_HTTPHEADER => $this->headers,
          ] );
          break;
        case 'delete':
          curl_setopt_array ( $this->handler , [
          CURLOPT_URL => $this->url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_POSTFIELDS => http_build_query($this->data),
          CURLOPT_HTTPHEADER => $this->headers,
          ] );
          break;
        default:
          curl_setopt_array ( $this->handler , [
          CURLOPT_URL => $this->url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => $this->headers,
          ] );
          break;
      }
      $this->content = curl_exec ( $this->handler );
      $this->info = curl_getinfo( $this->handler );
     }
     catch( Exception $e ) {
       die( $e->getMessage() );
     }
   }

  // function that will close the connection of the curl handler
  public function close() {
    if( $this->handler !== null ) {
      curl_close ( $this->handler );
      $this->handler = null;
    }
  }
}
?>
