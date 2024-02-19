<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Graphite;

/* Called from Domain\Graphite\GraphiteRepository */

use App\Domain\Graphite\Graphite;
use App\Domain\Graphite\GraphiteNotFoundException;
use App\Domain\Graphite\GraphiteRepository;

use Database;       // set in Repository to use class in app/Database.php
use Curl;           // set in Repostiroy to use class in app/Curl.php
use RenderGraphite; // Be able to render graphite URLs for viewing
//include  __DIR__ . '/../../../../app/config.php';   // Graphite URL and ports are defined in here

class DatabaseGraphiteRepository implements GraphiteRepository {

  public $db;
  public $ch;
  public $graphiteUrl;
  public $graphitePort;
  public $viewGraphite;
  private $count;
  private $depth;

  // https://stackoverflow.com/questions/17094467/use-external-variable-inside-php-class
  public $url;

  public function __construct() {
    $this->db = new Database();
    $this->ch = new Curl();
    $this->viewGraphite = new RenderGraphite();
    include  __DIR__ . '/../../../../app/config.php';   // Graphite URL and ports are defined in here
    $this->url =  $graphiteUrl .":" . $graphitePort;
  }

  public function findAll() {
    // Big query return!  Should only be needed for debugging
    // and creating new graphs
    $url='https://graphite.iwillfearnoevil.com/metrics/index.json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output);
    return $output;
  }


  // We are only taking single check names here. NO ARRAYS!
  public function findGraphs($arr) {
    $hostname = $arr['hostname'];
    $checkType = $arr['checkType'];
    $dataOptions = null;
    if (! is_null($arr['from'])) { $dataOptions['from'] = $arr['from']; }
    if (! is_null($arr['to'])) { $dataOptions['to'] = $arr['to']; }
    if (! is_null($arr['checkName'])) { $checkName = $arr['checkName']; } else { $checkName = 'none'; }
    $searchDepth = 5;
    $depth = $searchDepth;
    $workArray = array( 'hostname' => $hostname, 'checkType' => $checkType, 'checkName' => $checkName, 'depth' => $depth, 'prefix' => 'nms');
    while ( $depth > 0 ) {  // stop once we have no depth left
      $workArray['depth'] = $depth--;
      $findAllMetrics = self::expandMetrics2($workArray);
      $resultFound = json_decode($findAllMetrics['result'], true);
      /*
      $debug[] = "result found " . json_encode($resultFound,1);
      $debug[] = $findAllMetrics['info']['url'];
      $debug[] = json_encode($findAllMetrics,1);
      $debug[] = $findAllMetrics['code'];
      $debug[] = json_encode($findAllMetrics, 1);
      */
      if ( $resultFound['results'] !== [] || count($resultFound['results']) > 0) {
        //       $debug[] = "Not empty list of metrics.";
        //       $debug[] = json_encode($resultFound['results'], 1);
        $listOfMetrics = $resultFound['results'];
        //       $debug[] = json_encode($listOfMetrics,1);
        break;  // First time we get results, we get out of the loop
      }
    }
    if (empty($listOfMetrics)) { return array(); }  // If we cant find stuff, just return empty value
    //    return $debug;

    foreach($listOfMetrics as $singleMetric) {  // This works when we only have one value for a given checkName.metricName
      // EXAMPLE [{\"text\":\"open_fd\",\"id\":\"nms.guyver-office_iwillfearnoevil_com.nrpe.checkOpenFiles.open_fd\",\"leaf\":\"1\"}]";
      // EXAMPLE : [ [id=metricName, id=full path, leaf=1],[id=metricName2, id=full path2, leaf=1] ]
      $debug[] = "SINGLE METRIC " . $singleMetric;
      $filterDetailValue = explode('.',  $singleMetric);
      $filter = $filterDetailValue[4];
      $filter = trim($filter);
      $detailNamesValue[] = array("text" => $filter , "id" => $singleMetric, "leaf" => "1");
      $debug[] = "DETAIL NAMES VALUE " . json_encode($detailNamesValue,1);
      $debug[] = "VALUES PASSED checkType " . $checkType . " checkName " . $checkName . " detailNamesValue " . json_encode($detailNamesValue,1);
    }
    $debug[] = "CONFIRM PASSING ALL METRICS " . json_encode($detailNamesValue,1);
    $renderUrls[] = $this->viewGraphite->graphiteUrls( $checkType, $checkName , $detailNamesValue, $dataOptions);
    $debug[] = "RENDER URLS : " . json_encode($renderUrls, 1);

    if (empty($renderUrls)) { $renderUrls = array(); }
    return $renderUrls;
    return $debug;
  }


  // THIS IS JUST A MESS.. Clean it out later
  public function findGraphs2($arr) {
    /*
      We must confirm we have all the pieces before calling the RenderGraphite class
      Defaults of "none" will not cut it here.  We will have to drill down when we see "nones"
      until we have what we need for Graphite to return the data requested

      This function was a PITA dealing with Graphite.  More of getting the
      nested array to populate correctly.  Likely this COULD have bugs and
      more testing will be needed going forward
    */

    // Make an array for defaults and datafill as we go through the lists (yes this is stupid big)
    $checkType   = array();
    $checkName   = array();
    $checkMetric = array();
    $workArray   = array();
    $debug       = array();
    $findAllMetrics = array();
    $metricList   = array();

    if ( $arr['checkType'] == 'none' ) {                                 // nrpe or snmp currently
      unset ($arr['checkType']);                                         // Must be unset so findMonitored will not search for the string "none"
      $findCheckType = $this->findMonitored($arr);                       // return list of pollers that report to graphite
      $findCheckType = json_decode(json_encode($findCheckType,1),true);  // convert from an object again, sigh...
      foreach ($findCheckType as $singleCheckType) {                     // iterate through each index
        foreach ($singleCheckType as $key => $value) {                   // iterate through each list
          if ( $key == "text" ) {
            $checkType[] = $value;
          }
        }
      }
    }
    else {
      $checkType[] = $arr['checkType'];                                  // A user passed us a specific checkType ( better be a string or ouch! )
    }
    if ( empty($checkType)) {                                            // If we dont have any monitors stop here
      return $checkType;
    }

    // We now have a list of checkTypes (pollers reporting to graphite).  Get the service checks if none are defined
    if ( $arr['checkName'] == 'none' ) {                                 // find our check names if we do not have any yet
      unset($arr['checkName']);
      foreach ($checkType as $singleCheck) {
        $workArray = $arr;
        $workArray['checkType'] = $singleCheck;
        $findCheckName = $this->findChecks($workArray);
        $findCheckName = json_decode(json_encode($findCheckName,1),true);
        foreach ($findCheckName as $singleCheckName) {
          $checkName[$singleCheck][] = $singleCheckName['text'];
          // should give us sometihng like $checkName['nrpe'][0] = "check_disk"
        }
      }
    }
    else {
      $checkName['checkName'] = [ $arr['checkName'] ];
  //    $checkName = $arr['checkName'];
    }
// 01-04?    $arr['checkName'] = [$checkName];
    if (empty($checkName)) {  // This should not happen if we got a checkType back, but meh, who knows what oddities lie in graphite
      return $checkName;
    }
    // At this point we have an array of check names, but not the metric names.  We have to retrieve the metric names for RenderGraphite to use
    // We are going to continue to use the $checkName and add values to that array until we are done
    //return $checkName;  // DEBUG
//return $checkName;
    $idepth = 5; // set a default before the loop
    foreach ($checkName as $key => $value) {
      foreach ($value as $singleValue) {
        // return [ $key  . " " . $singleValue ];  // DEBUG  will return nrpe checkOpenFiles for example
        $findAllMetrics = array();
        $workArray['hostname'] = $arr['hostname'];
        $workArray['prefix'] = 'nms';
        $workArray['checkType'] = $key;
        $workArray['checkName'] = $singleValue;
        $workArray['depth'] = $idepth;  // Under normal coding, it should never be beyond 2, however someone might get really granular and go deep on metric paths
return $workArray;
        $depth = $idepth;       // We want to mess with the value, so keep the origional for the next loop iteration
        while ( $depth > 0 ) {  // stop once we have no depth left
          $workArray['depth']= $depth--;
          $debug[] = 'Work Array ' . json_encode($workArray, 1);
          $findAllMetrics = self::expandMetrics2($workArray);        // can be a json encoded array or empty result
          if ( ! is_array($findAllMetrics)) {
            $findAllMetrics = json_decode($findAllMetrics,true);
          }
            $findAllMetrics2 = json_decode($findAllMetrics['result'], true);
          $debug[] = 'Expand Metrics Result ' .  json_encode($findAllMetrics,1);
          $debug[] = 'Filter Expand Metrics ' . json_encode($findAllMetrics2,1);
return $debug;
          if ( count($findAllMetrics2['results']) > 0 || ! is_null($findAllMetrics2['results']) || ! empty($findAllMetrics2['results'])) {
//          if ( ! empty($findAllMetrics['result']['results'])) {


            $debug[] = "findAllMetric " . json_encode($findAllMetrics2,1);
            foreach ($findAllMetrics2['results'] as $singleMetricList) {
              $debug[] = "singleMetricList " . json_encode($singleMetricList,1);
              $metricDepth = substr_count($singleMetricList,'.');
              $detailValue = explode('.', $singleMetricList);
              $debug[] = "depth " . $metricDepth;
              $debug[] = "detailValue ". json_encode($detailValue,1);
              $poller = $detailValue[2];
              $checkName = $detailValue[3];
              $checkFinal = '';
              if ( isset($detailValue[4])) { $checkOption = $detailValue[4]; } else { $checkOption = $detailValue[2] ; }
              if ( $metricDepth < 5 ) {
                if ( ! isset($metricList[$poller][$checkName][$checkName])) {
                  $metricList[$poller][$checkName][$checkName] = $singleMetricList;
                   $debug[] = "TEST " . $poller . " checkName " . " checkOption " . $checkOption . " checkFinal " . $checkFinal;
                }
                else {
                  $metricList[$poller][$checkName][$checkName] .= ',' . $singleMetricList;
                   $debug[] = "TEST APPEND " . $poller . " checkName " . " checkOption " . $checkOption . " checkFinal " . $checkFinal;
                }
              }
              else {
                if ( isset($detailValue[5])) { $checkFinal = $detailValue[5]; } else { $checkFinal = $detailValue[3] ; }
                if ( ! isset($metricList[$poller][$checkName][$checkOption])) {
                  $metricList[$poller][$checkName][$checkOption] = $singleMetricList;
                   $debug[] = "TEST " . $poller . " checkName " . " checkOption " . $checkOption . " checkFinal " . $checkFinal;
                }
                else {
                  $metricList[$poller][$checkName][$checkOption] .= ', ' . $singleMetricList;
                   $debug[] = "TEST APPEND" . $poller . " checkName " . " checkOption " . $checkOption . " checkFinal " . $checkFinal;
                }
              }
            }  // foreach
          } // fi
        } // while
      } // foreach
    } // foreach
//     return $metricList;
    return $debug;

    // Now we can finally call the templates and render this stuff!
    foreach ($metricList as $pollerKey => $checkNames) {
      // $debug[] = "Working with pollerKey " . $pollerKey;

      foreach ( $checkNames as $namesKey => $namesValue) {
        // $debug[] = "working with namesKey " . $namesKey ;

        foreach ( $namesValue as $nameKey => $nameValue) {
          // $debug[] = "Source for singleName " . json_encode($nameValue,1);
          $nameValue = explode(',',$nameValue);

          foreach ($nameValue as $singleName) {
            // $debug[] = "singleName " . $singleName;
            $textValue = preg_replace('/.*.\./','', $singleName);
            $filterValue = explode('.', $singleName);
            $filter = $filterValue[4];
            $filter = trim($filter);
            // EXAMPLE : [ [id=metricName, id=full path, leaf=1],[id=metricName2, id=full path2, leaf=1] ]
            $detailNamesValue[] = array("text" => $textValue , "id" => $singleName, "leaf" => "1");
            // $debug[] = "filter value " . $filter;
            // $debug[] = "Metrics for filter value " . json_encode($detailNamesValue,1);
          } // end foreach nameValue
        }  // end foreach namevalue
    //     $debug[] = "KEY " . $pollerKey . " checkName " . $namesKey .  " filter (not really used) " . $filter . " array of metrics " . json_encode($detailNamesValue,1);
        $filterValue = array();  // unset for the next loop
  //       $debug[] = json_encode($detailNamesValue,1);
        $renderUrls[] = $this->viewGraphite->graphiteUrls( $pollerKey, $namesKey , $detailNamesValue, null);
        $detailNamesValue = array();  // unset for the next loop
      }  // end foreach checkNames
    }  // end foreach metricList
     return $debug;
//    return $renderUrls;
  }



  private function expandMetrics($arr) {
    $depth = '';
    if ( ! isset($arr['depth'])) { $arr['depth'] = 1; }  // set a default if we did not give one
    for ($count = 1; $count <= $arr['depth']; $count++) {
      $depth = $depth . '.*';
    }
//return $arr;
//    $url = $this->url . '/metrics/expand?leavesOnly=1&query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . '.' . $arr['checkName'] . $depth;
    $url = $this->url . '/metrics/expand?leavesOnly=1&query=nms.guyver-office_iwillfearnoevil_com.nrpe.checkOpenFiles' . $depth;
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output['result'] = $this->ch->content();
//    $output['code'] = $this->ch->curl_getinfo($this->ch, CURLINFO_RESPONSE_CODE);
    $output['info'] = $this->ch->curl_getinfo($this->ch);                                           // Mainly useful for debugging

    $this->ch->close();
    if ( filter_var($output['result'], FILTER_VALIDATE_BOOLEAN)) {  // If we did not get an array return make a fake one
      $output['result'] = array();
    }
    else {
  //    $output = json_decode($output,true);
    }
//    $data[] = "OUTPUT   " .  json_encode($output,1);
//    $data[] = "URL   " . $url;
//    return $data;
    //return [$url];
    return $output;
  }

  private function expandMetrics2($arr) {
    $depth = '';
    if ( ! isset($arr['depth'])) { $arr['depth'] = 1; }  // set a default if we did not give one
    for ($count = 1; $count <= $arr['depth']; $count++) {
      $depth = $depth . '.*';
    }
    $url = $this->url . '/metrics/expand?leavesOnly=1&query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . '.' . $arr['checkName']  . $depth;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output['result'] = curl_exec($ch);
    $output['code'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $output['info'] = curl_getinfo($ch);
    curl_close($ch);
    if ( filter_var($output['result'], FILTER_VALIDATE_BOOLEAN)) {  // If we did not get an array return make a fake one
      $output['result'] = array();
    }
    return $output;
  }

  public function findList($arr) {
    // returns list of metrics monitored, but NOT the metric name
    // overall, this should fit 99% use cases
    $url='https://graphite.iwillfearnoevil.com/metrics/find?query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.*.*.*';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output);
    return $output;
  }

  public function findMonitored($arr) {
    // return $arr;
    // returns list of pollers that GET the metrics.  use for filters!
    // some metric returns may have the same name, so use your head
    $url = $this->url . '/metrics/find?query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.*';
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output = $this->ch->content();
    $this->ch->close();
    $output = json_decode($output,true);
    if ( ! is_array($output)) { $output = [$output]; }
    return $output;
  }

  public function expandChecks($arr) {
    if ( $arr['checkType'] == 'snmp' ) {  // SNMP has 2 levels not one below checkType to give the checkName
      $arr['depth'] = 2;
    }
    $depth = '';
    if ( ! isset($arr['depth'])) { $arr['depth'] = 1; }
    for ($count = 1; $count <= $arr['depth']; $count++) {
      $depth = $depth . '.*';  // Make the levels using append.  Dumb but simple..
    }
    // this will drill down hostname.checkType to get the checkName from source
    $url = $this->url . '/metrics/find?query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . $depth;
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output = $this->ch->content();
    $this->ch->close();
    $output = json_decode($output,true);
    return $output;
  }

  public function findChecks($arr) {
//    if ( $arr['checkType'] == 'snmp' ) {  // SNMP has 2 levels not one below checkType to give the checkName
//      $arr['depth'] = 2;
//    }
    $depth = '';
    if ( ! isset($arr['depth'])) { $arr['depth'] = 1; }
    for ($count = 1; $count <= $arr['depth']; $count++) {
      $depth = $depth . '.*';  // Make the levels using append.  Dumb but simple..
    }
    // this will drill down hostname.checkType to get the checkName from source.  You loose the checkName value but get the checkMetric returned
    $url = $this->url . '/metrics/find?query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . $depth;
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output = $this->ch->content();
    $this->ch->close();
    $output = json_decode($output,true);
    return $output;
  }

  public function expandMetricsExpanded($arr) {
    if ( $arr['checkType'] == 'snmp' ) {  // SNMP has 2 POSSIBLE levels not one below checkType to give the checkMetricName
      $arr['depth'] = 2;
    }
    $depth = '';
    if ( ! isset($arr['depth'])) { $arr['depth'] = 1; }
    for ($count = 1; $count <= $arr['depth']; $count++) {
      $depth = $depth . '.*';  // Make the levels using append.  Dumb but simple..
    }
    $url = $this->url . '/metrics/expand?leavesOnly=1&query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . '.' . $arr['checkName'] . $depth;
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output = $this->ch->content();
    $this->ch->close();
    $output = json_decode($output,true);
    return $output;
  }

  public function findMetrics($arr) {
    $depth = '';
    if ( ! isset($arr['depth'])) { $arr['depth'] = 1; }
    for ($count = 1; $count <= $arr['depth']; $count++) {
      $depth = $depth . '.*';  // Make the levels using append.  Dumb but simple..
    }
    $url = $this->url . '/metrics/find?query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . '.' . $arr['checkName'] . $depth;
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output = $this->ch->content();
    $this->ch->close();
    $output = json_decode($output,true);
    return $output;
  }

  private function findMetricsNested($arr) {
    $url = $this->ch->url . '/metrics/find?query=' . $arr['prefix'] . '.' . $arr['hostname'] . '.' . $arr['checkType'] . '.' . $arr['checkName'] . '.*.*';
    $this->ch->url = $url;
    $this->ch->method="get";
    $this->ch->send();
    $output = $this->ch->content();
    $this->ch->close();
    $output = json_decode($output,true);
    return $output;
  }

//  public function findFunction( $hostname, $graphiteFindList) {
  public function findFunction($arr) {
    // going to need foreach against the graphiteFindList
    // This is actually going to be a db query to find matches
    // return: hostname, graphiteFindList, databaseResult
    $url='https://graphite.iwillfearnoevil.com/metrics/find?query=*.' . $hostname . '.*.*.' .  $list;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output);
    return $output;
  }

//  public function findSiblings( $hostname, $graphiteFindList) {
  public function findSiblings($arr) {
    // going to need foreach against the graphiteFindList
    // This is actually going to be a db query to find matches
    // return: hostname, graphiteFindList, databaseResult
    $url='https://graphite.iwillfearnoevil.com/metrics/find?query=*.' . $hostname . '.*.*.' . $list;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output);
    return $output;
  }

//  public function findRegex( $hostname, $graphiteRegex) {
  public function findRegex($arr) {
    // going to need foreach against the graphiteRegex
    $url='https://graphite.iwillfearnoevil.com/metrics/find?query=*.' . $hostname . '.*.*.' . $list;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output);
    return $output;
  }

  public function findTemplate($arr){}

//  public function findMap($hostname, $list ) {
  public function findMap($arr) {
    foreach ($list as $findMetric) {
      $mergedMetric=explode("$hostname.", $findMetric);
      $checkNames=explode( '.', $mergedMetric[1]);
      $checkName=$checkNames[1];
      $checkMetric=$checkNames[2];
      $value="%". $checkName . '.' . $checkMetric ."%";
      $parentMetric=$checkNames[0];

      if ( $parentMetric == "interfaces" ) {
        $value="enp%s%.%";
      }
      else {
        // This may grow over time to have additional regex changes for things that are named
        // close to other check names, or even metricLabel names
        $value=preg_replace( "/Load-[0-9][0-9]?/", 'Load-%', $value); // change Load returns to be wildcards if possible for database query
        $value=preg_replace( "/enp[0-9][0-9]?s[0-9][0-9]?/", 'enp%s%', $value); // change enp*s* ethernet to be wildcards if possible for database query
        $value=preg_replace( "/.if[CHIOP]/", '.%', $value); // change interface metric values to be wc for query

        $value=preg_replace( "/volt\..*/", 'volt\.%', $value); // change voltage to be wildcards if possible for database query
        $value=preg_replace( "/.temp[0-9][0-9]?.*/", '.%', $value); // change tempatures to be wildcards if possible for database query
        $value=preg_replace( "/temp\..*/", 'temp%', $value); // change tempatures to be wildcards if possible for database query
        $value=preg_replace( "/.fan[0-9][0-9]?.*/", '.%', $value); // change fan speeds to be wildcards if possible for database query
      }

      $this->db->prepare("SELECT begin, end FROM graphiteMap WHERE mapValue LIKE :value OR mapValue ='*' ORDER BY id DESC LIMIT 1");
      $this->db->bind('value', $value);
      $data = $this->db->resultset();
      $data = json_decode(json_encode($data), true);
      // $data[0]['begin'] is return for start of URL $data[0]['end'] end of URL from database
      $results[] = $data[0]['begin'] . $findMetric .  $data[0]['end'];
      //     $test[] = "SELECT begin, end FROM graphiteMap WHERE mapValue LIKE " . $value ." OR mapValue ='*' ORDER BY id DESC LIMIT 1";

    }
    return $results;
    // return $test;
    // return array_values($test);
  }

//  public function findSingleMap($hostname, $list ) {
  public function findSingleMap($arr) {
      $mergedMetric=explode("$hostname.", $list);
      $checkNames=explode( '.', $mergedMetric[0]);
      $checkName=$checkNames[0];
      $checkMetric=$checkNames[1];
      $value="%". $checkName . '.' . $checkMetric ."%";
      $parentMetric=$checkNames[0];

      if ( $parentMetric == "interfaces" ) {
        $value="enp%s%.%";
      }
      else {
        // This may grow over time to have additional regex changes for things that are named
        // close to other check names, or even metricLabel names
        $value=preg_replace( "/Load-[0-9][0-9]?/", 'Load-%', $value); // change Load returns to be wildcards if possible for database query
        $value=preg_replace( "/enp[0-9][0-9]?s[0-9][0-9]?/", 'enp%s%', $value); // change enp*s* ethernet to be wildcards if possible for database query
        $value=preg_replace( "/.if[CHIOP]/", '.%', $value); // change interface metric values to be wc for query

        $value=preg_replace( "/volt\..*/", 'volt\.%', $value); // change voltage to be wildcards if possible for database query
        $value=preg_replace( "/.temp[0-9][0-9]?.*/", '.%', $value); // change tempatures to be wildcards if possible for database query
        $value=preg_replace( "/temp\..*/", 'temp%', $value); // change tempatures to be wildcards if possible for database query
        $value=preg_replace( "/.fan[0-9][0-9]?.*/", '.%', $value); // change fan speeds to be wildcards if possible for database query
      }

      $this->db->prepare("SELECT begin, end FROM graphiteMap WHERE mapValue LIKE :value OR mapValue ='*' ORDER BY id DESC LIMIT 1");
      $this->db->bind('value', $value);
      $data = $this->db->resultset();
      $data = json_decode(json_encode($data), true);
      // $data[0]['begin'] is return for start of URL $data[0]['end'] end of URL from database
      $results = $data[0]['begin'] . $list .  $data[0]['end'];
      //     $test[] = "SELECT begin, end FROM graphiteMap WHERE mapValue LIKE " . $value ." OR mapValue ='*' ORDER BY id DESC LIMIT 1";

    return $results;
    // return $test;
    // return array_values($test);
  }

}
