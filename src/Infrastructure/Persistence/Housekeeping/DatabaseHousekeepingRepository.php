<?php
declare(strict_types=1);


namespace App\Infrastructure\Persistence\Housekeeping;

/* Called from Domain\Housekeeping\HousekeepingRepository */

use App\Domain\Housekeeping\Housekeeping;
use App\Domain\Housekeeping\HousekeepingNotFoundException;
use App\Domain\Housekeeping\HousekeepingRepository;

require __DIR__ . '/../../../../app/Database.php';
use Database;

class DatabaseHousekeepingRepository implements HousekeepingRepository {
  private $path=__DIR__ . '/../../../../daemon/housekeeping/';

  public function __construct() {
    $this->db = new Database();
  }


  // Hard setting this as we should not have multiple daemons running
  public function housekeepingIteration() {
    // We should NOT have more than one housekeeper running at a time
    $results[]=["iteration" => '60']; 
    return $results;
  }

  // Pull pid files for housekeeping
  public function housekeepingStatus() {
    $iterationList=$this->housekeepingIteration();
    foreach ($iterationList as $iteration) {
      $status = file_exists($this->path . 'housekeeping.php.' . $iteration['iteration'] . '.pid') ;
      if(! $status) {
        $result ="Housekeeping iteration ". $iteration['iteration'] . " no pidfile found";
      }
      else {
        $res=file_get_contents( $this->path . 'housekeeping.php.' . $iteration['iteration'] . '.pid');
        if (empty($res)) {
          $result = "Housekeeping iteration " . $iteration['iteration'] . " pidfile found.  No pid running";
        }
        else {
          $result ="running pid for housekeeping.php iteration " . $iteration['iteration'] . " is " . $res;
        }
      }
      $results[] = $result;
    }
    return array_values($results);
  }


  /*
     shell_exec can be a pita!
     https://www.php.net/manual/en/function.shell-exec.php
     https://linuxhint.com/redirect-nohup-file-output/
  */


  // Start the daemon
  public function housekeepingRun() {
    $iterationList=$this->housekeepingIteration();
    $iterationList = json_encode($iterationList);
    $iterationList = json_decode($iterationList, true);
    foreach ($iterationList as $iteration) {
//      $cmd="('nohup nice -n 10 /usr/bin/php ./housekeeping.php -i ' . $iteration['iteration'] . ' -s start > /opt/nmsApi/logs/housekeeping.log & printf "%u" $!') ";
      $cmd='nohup nice -n 10 /usr/bin/php ./housekeeping.php -i ' . $iteration['iteration'] . ' -s start > /opt/nmsApi/logs/housekeeping.log & printf "%u" $!';
      chdir($this->path);
      $proc=shell_exec($cmd);
      $status[]="Iteration " . $iteration['iteration'] . " housekeeping started with pid: $proc";
    }
    return array_values($status);
  }

  // List of all known daemons returning heartbeats
  public function housekeepingKnown() {
    $this->db->prepare("SELECT * from heartbeat");
    $this->db->execute();
    $results = $this->db->resultset();
    return array_values($results);
  }

  public function cleanPerformanceTable($days) {

  }

  // Stop the daemon
  public function housekeepingKill() {
    $iterationList=$this->housekeepingIteration();
    $iterationList = json_encode($iterationList);
    $iterationList = json_decode($iterationList, true);
    foreach ($iterationList as $iteration) {
//      $cmd="('/usr/bin/php ./housekeeping.php -i ' . $iteration['iteration'] .' -s stop 2>\&1 > /opt/nmsApi/logs/housekeeping.log')";
//      $cmd="('/usr/bin/php ./housekeeping.php -i ' . $iteration['iteration'] .' -s stop > /opt/nmsApi/logs/housekeeping.log')";
      $cmd='/usr/bin/php ./housekeeping.php -i ' . $iteration['iteration'] .' -s stop > /opt/nmsApi/logs/housekeeping.log';
      chdir($this->path);
      $proc=shell_exec($cmd);
      $status[]="Iteration " . $iteration['iteration'] . " housekeeping stopped";
    }
    return array_values($status);
  }
}

