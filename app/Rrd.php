<?php
//declare(strict_types=1);

// https://gist.github.com/bradtraversy/a77931605ba9b7cf3326644e75530464
// Using example for PDO class to make new generic logger class
class Rrd {
  private $path=__DIR__ . "/../rrd/";

  // Build out connection defaults
  // Default to TCP connections
  public function __construct(?string $path=__DIR__ . "/../rrd/"){
    $this->path=$path;
  }

  public function update( $hostname, $metric, $value ) {
  // update the rrd file
  // $this->path . /$hostname . $metricName.rrd
  }
}
?>
