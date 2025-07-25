<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\RenderGraph;

use App\Domain\RenderGraph\RenderGraph;
use App\Domain\RenderGraph\RenderGraphNotFoundException;
use App\Domain\RenderGraph\RenderGraphRepository;
use Database;

/*
  General Graph Rendering class.  Initial support is going to be exclusive
  to RRD, and then Graphite.  DatabaseMetric, and Influx are future
*/

class DatabaseRenderGraphRepository implements RenderGraphRepository {
  public $db;

  public function __construct() {
    $this->db = new Database();
  }

  // Create JPG image and drop in public/static/
  public function createGraph($arr): array {
    if ( ! isset($arr['hostname'] )) { return ['hostname is not set']; } else { $hostname = $arr['hostname']; }
    if ( ! isset($arr['file'] )) { return ['filename is not set']; } else { $file = $arr['file']; }
    if ( ! isset($arr['filter'] )) { return ['filter is unset']; } else { $filter = $arr['filter']; }
    if ( ! isset($arr['start'])) { $start= null; } else { $start = $arr['start']; }
    if ( ! isset($arr['end']) )  { $end = null; } else { $end   = $arr['end']; }
    if ( ! isset($arr['ignoreMatch']) ){ $ignoreMatch = null; } else  { $ignoreMatch = $arr['ignoreMatch']; }
    // then null in renderGraph is so we can use external logging in the functions and classes
    require __DIR__ . '/../../../../templates/generalMetricRender.php';
    $results = renderGraph(null, $hostname, $file, $filter, $start, $end, $ignoreMatch );
    if ( ! is_array($results) ) {
      $results = [$results];  // Return the err if we dont get a valid result.  Use caution, nothing returned IS a valid result
    }
    return $results;
  }

  // Return new list of filenames to templates for rendering
  public function findRrdTemplates($arr): array {
    // Grab all the RrdRender templates as a single query
    $this->db->prepare("SELECT Name, templateValue FROM templates WHERE Class= 'RrdRender'");
    $templateList = json_decode(json_encode($this->db->resultset(),1),true);
    $arr = explode(',',$arr['files']);
    $arr = array_filter($arr);
    $returnMatch = array();
    // Iterate through our filenames and see if we can find a match
    foreach ($arr as $fileList) {
       $matcher = 'no_template';
      //$matcher = 'default';
      $fileList = str_replace(array("\r", "\n", '"'), '',$fileList);  // Clean out cruft from filename
      $fileList = trim($fileList);                                    // remove leading whitespace
      foreach ($templateList as $singleTemplate) {
        $templateJson = json_decode($singleTemplate['templateValue'], true);
        $templateString = $templateJson['match'];
        if (strpos( $fileList, "$templateString") !== false) {
          $matcher = $singleTemplate['Name'];
          array_push ($returnMatch , [$fileList => $matcher] );
        }
      }
       if ( $matcher == 'no_template' ) {
      //if ( $matcher == 'default' ) {
        array_push ($returnMatch , [$fileList => $matcher] );
      }
    }
    return $returnMatch;
  }

  // List of RRD files as array
  public function findRrdDatabases($arr): array {
    $rawPath=__DIR__ . '/../../../../rrd/';
    $hostname = $arr['hostname'];
    $dir=$rawPath . $hostname . '/';
    $results = getDirContents($dir, '/\.rrd$/');
    if ( ! is_array($results) ) {
      $results = [$results];  // Return the err if we dont get a valid result.  Use caution, nothing returned IS a valid result
    }
    return $results;
  }

  // List of Graphite paths  (WIP 06-20-23)
  public function findGraphiteLinks($arr): array {
    if ( ! isset($arr['hostname'] )) {
      return ['hostname is not set'];
    }
    else {
      $hostname = $arr['hostname'];
      $hostname = preg_replace('/./','_',$hostname); // change . to _ char!
    }
  }

  // Query database for Metric "template".  Likely raw PHP code
  public function graphMetricSetup($arr): array {}

  // Return link for a graph that exists
  public function linkGraph($arr): array {}

  // Return paramaters used to create graph
  public function debugGraph($arr): array {}

  // Nuke a graph on the filesystem (rrd only)
  public function deleteGraph($arr): array {}

  // Maybe redundant with debug
  public function parametersGraph($arr): array {}

} // End CLASS
