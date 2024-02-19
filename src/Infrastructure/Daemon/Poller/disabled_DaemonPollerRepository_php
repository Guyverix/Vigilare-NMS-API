<?php
declare(strict_types=1);

namespace App\Infrastructure\Daemon\Poller;

/* Called from Domain\Poller\PollerRepository */
use App\Domain\Poller\Poller;
use App\Domain\Poller\PollerNotFoundException;
use App\Domain\Poller\PollerRepository;
//require __DIR__ . '/../../../../app/Database.php';

class DaemonPollerRepository implements PollerRepository {
  private $path=__DIR__ . '/../../../../poller/';

  public function __construct() {
  }

  public function statusPid() {
    $status = file_get_contents( $this->path . 'snmpPoller.php.3.pid') ;
    if(! $status) {
      $status="no pidfile found";
    }
    else {
      $status="running pid for snmpPoller.php iteration blah is " . file_get_contents( $this->path . 'snmpPoller.php.3.pid');
    }
    return "$status";
  }
/*
  public function pollerIteration() {
    $database = new Database();
    $database->query("SELECT distinct(iteration) FROM snmpPoller");
    $result=$database->execute();
    return $result;
  }
*/
  public function pollerStatus() {
    $iterationList=$this->pollerIteration();
    $results=[];
    foreach ($iterationList as $iteration) {
      // $status = file_get_contents('/opt/nmsApi/src/Infrastructure/Daemon/Poller/../../../../poller/snmpPoller.php.' . $iteration . '.pid') ;
      $status = file_get_contents($this->path . 'snmpPoller.php.' . $iteration . '.pid') ;
      if(! $status) {
        $results="no pidfile found";
      }
      else {
        $res=file_get_contents( $this->path . 'snmpPoller.php.' . $iteration . '.pid');
        $status="running pid for snmpPoller.php iteration " . $iteration . " is " . $res;
      }
    }
    return "$results";
  }

  public function pollerRun() {
    $cmd='nohup nice -n 10 /usr/bin/php ./snmpPoller.php -i 3 -s start > /dev/null & printf "%u" $!';
    chdir($this->path);
    $proc=shell_exec($cmd);
    $status="Poller started with pid: $proc";
    return "$status";
  }

  public function pollerKill() {
    $cmd='/usr/bin/php ./snmpPoller.php -i 3 -s stop';
    chdir($this->path);
    $proc=shell_exec($cmd);
    return "Stopping Process id: $proc";
  }
}
