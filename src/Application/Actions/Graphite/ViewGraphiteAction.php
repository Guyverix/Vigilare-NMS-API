<?php

declare(strict_types=1);

namespace App\Application\Actions\Graphite;

use App\Application\Actions\Graphite\GraphiteAction;
use App\Domain\Graphite\Graphite;
use App\Domain\Graphite\GraphiteRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ViewGraphiteAction extends GraphiteAction {
  protected function action(): Response {
    $data = $this->getFormData();
    $hostname = $this->resolveArg('filter'); // fqdn (get)

    if (! isset($data['prefix']))   { $data['prefix'] = 'nms'; }  // graphite prefix
    if (! isset($data['from']))     { $data['from'] = "-6h"; }    // default from is -24 hours
    if (! isset($data['to']))       { $data['to'] = "-1m"; }      // Safety for null values on fresh data
    if (! isset($data['width']))    { $data['width'] = "586"; }   // Default render width
    if (! isset($data['height']))   { $data['height'] = "308"; }  // Default render height
    if (! isset($data['return']))   { $data['return'] = "json"; } // Default return style
    if (! isset($data['check']))    { $data['check'] = ''; }      // Filter for a service check as a regex

    // This is used for 2 different things.  getting a basic json return as well as the full search
    if (! isset($data['hostname'])) { $data['hostname'] = 'none'; }      // backup hostname not saftied!  remember still need to change . to _
    if (! isset($hostname))         { $hostname=$data['hostname']; }     // Fall through logic as a last ditch effort to get a hostname
    if (! isset($data['job']))      { $data['job'] = 'dumbSearch'; }     // Filtered searches or run the whole list

    $hostname = preg_replace('/\./','_', $hostname); // change . to _
    // $hostname = "guyver-myth_iwillfearnoevil_com";

    $graphiteFrom = $data['from'];
    $graphiteTo = $data['to'];
    $graphiteWidth = $data['width'];
    $graphiteHeight = $data['height'];
    $graphiteReturn = $data['return'];
    $graphitePrefix = $data['prefix'];
    $graphiteRegex = $data['check'];

    $returnUrl='https://graphite.iwillfearnoevil.com/render/?width=' . $graphiteWidth . '&height=' . $graphiteHeight . '&from=' . $graphiteFrom . '&to=' . $graphiteTo . '&target=';
    $errMessage[]='unable to retrieve metric, or metrics do not exist for ' . $hostname;

    if ( $data['job'] == 'source' ) {
      $daemonArray = $this->graphiteRepository->findMonitored($hostname);
      return $this->respondWithData($daemonArray);
    }
    elseif ($data['job'] == 'check' ) {
      $daemonArray = $this->graphiteRepository->findChecks($hostname . '.' . $data['check']);
      return $this->respondWithData($daemonArray);
    }
    elseif ($data['job'] == 'single' ) {
      $daemonArray = $this->graphiteRepository->findMonitored($hostname . '.' . $data['check']);
      return $this->respondWithData($daemonArray);
    }
    elseif ($data['job'] == 'createUrl' ) {
      $singleMapChange=$this->graphiteRepository->findSingleMap($hostname, $data['check']);
      $finalUrl = $returnUrl . $hostname . '.' . $singleMapChange ;
      settype($finalUrl,'array');
      foreach ($finalUrl as $returnList) {
        $breakUrl=explode('target=', $returnList);
        $niceNames=explode("$hostname.", $returnList);
        $finalNames=explode( '.', $niceNames[1]);
        $finalNames[2]=preg_replace('/\)/', '', $finalNames[1]); // if a function was added, dont clobber it
        $cleanFinalNames=$breakUrl[0].'target=alias(' . $breakUrl[1] . ',\'' . $finalNames[1] . " " . $finalNames[2] . '\')';
        $lineMode='&lineMode=connected';
        if (preg_match("/$lineMode/", $cleanFinalNames) !== 0) {
          $cleanFinalNames=preg_replace("/$lineMode/", '', $cleanFinalNames) . $lineMode;
        }
        $graphiteResults[]=$cleanFinalNames;
      }
      return $this->respondWithData($graphiteResults);
    }
    else {
      // Return full list of URL's for ALL metrics for hostname
      // Returns array of daemon names
      $daemonArray = $this->graphiteRepository->findMonitored($hostname);
      foreach ($daemonArray as $daemonObject) {
        $daemon = json_decode(json_encode($daemonObject), true); // convert from object to normal array
        $daemonList[] = explode($hostname.'.', $daemon['text'])[0]; // grab the "text" name, as this IS the daemon name
      }
      if ( empty($daemonList)) {
        return $this->respondWithData($errMessage);
      }
      // return $this->respondWithData($daemonList);

      // Return array of check names for each daemon name
      foreach ($daemonList as $daemonChecks) {
        $checkArray[] = $this->graphiteRepository->findChecks($hostname .'.'.$daemonChecks); // nice clean array return
      }
      foreach ($checkArray as $checkObject) {
        $checks = json_decode(json_encode($checkObject), true);
        foreach ($checks as $check) {
          $checkName[] = $check['id']; // make a nice clean array of ONLY id values
        }
      }
      if ( empty($checkName)) {
        return $this->respondWithData($errMessage);
      }
      // return $this->respondWithData($checkName);

      // Find the metric names for the checks that we found
      foreach ($checkName as $metricParent) {
        $metricParent=preg_replace('/\*\./','', $metricParent); // strip out the *. that was added in id
        $metricList[] = $this->graphiteRepository->findChecks($metricParent);
      }
      if (empty($metricList)) {
        return $this->respondWithData($errMessage);
      }
      // return $this->respondWithData($metricList);

      // We now have a list of metrics, need a double foreach to make URL's
      foreach ($metricList as $daemonMetric) {
        foreach ($daemonMetric as $metricObject) {
          $metricName=json_decode(json_encode($metricObject), true);
          $metricId[]=$metricName['id']; // complete list of hostname >> daemon >> check >> metricName
        }
      }
      if (empty($metricId)) {
        return $this->respondWithData($errMessage);
      }
      // return $this->respondWithData($metricId);


      // Find any mappings we have for checks such as derivative, etc
      $mapChange=$this->graphiteRepository->findMap($hostname, $metricId);
      //return $this->respondWithData($mapChange);

      // Now build URL
      foreach ($mapChange as $baseUrl) {
        $finalUrl[] =$returnUrl.$baseUrl;
      }
      // return $this->respondWithData($finalUrl);
      if ( empty($finalUrl)) {
        return $this->respondWithData($errMessage);
      }

      // Return our data now that logic is done
      if ( $graphiteReturn == 'html' ) {
        $graphiteResults = '';
        foreach ($finalUrl as $returnList) {
          $niceNames=explode("$hostname.", $returnList);
          $finalNames=explode( '.', $niceNames[1]);
          $graphiteResults .="<a href=" . $returnList . ">" . $finalNames[0] . " " . $finalNames[1] . " </a>";
        }
      }
      else {
        foreach ($finalUrl as $returnList) {
          $breakUrl=explode('target=', $returnList);
          $niceNames=explode("$hostname.", $returnList);
          $finalNames=explode( '.', $niceNames[1]);
          $finalNames[2]=preg_replace('/\)/', '', $finalNames[2]); // if a function was added, dont clobber it
          $cleanFinalNames=$breakUrl[0].'target=alias(' . $breakUrl[1] . ',\'' . $finalNames[1] . " " . $finalNames[2] . '\')';
          $lineMode='&lineMode=connected';
          if (preg_match("/$lineMode/", $cleanFinalNames) !== 0) {
            $cleanFinalNames=preg_replace("/$lineMode/", '', $cleanFinalNames) . $lineMode;
          }
          $graphiteResults[]=$cleanFinalNames;
        }
      }
      return $this->respondWithData($graphiteResults);
    } // end if-else
  } // end function
}  // end Class
