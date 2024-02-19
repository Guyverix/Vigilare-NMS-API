<?php

/*
  This template is the first createed to return Graphite data and formatting to
  render graphs.

  Manditory parameters:
    Array return from graphite POST which contains
    hostname          (split from id)
    daemonPoller name (split from id)
    checkName         (split from id)
    metric name       (pulled from text)

  Optional parameters: from SECOND ARRAY.  This affects ALL of the data
    from time
    to time
    width
    height
    colors
    bg color
    rawData           (oddball where if !== no will return only raw data URL)
    prepend           (oddball options to do more exotic stuff with URL)
    postpend          (oddball options to do more exotic stuff with URL)

  The return will be straight URLs with all parameters filled out.
  This will be a simple array return, nothing special.
*/

/*
  More complex graphs can be created via templates, including ones with multiple
  metrics within one graph, as well as calculations.

  Graphite will allow this easily, and building the metric URL is going to be the
  most complex part.  Appending the two together SHOULD be trivial
*/




  /*
    This is going to be manditory for all templates to have as a catchall in rendering
    either it does not exist at all, or everything except prepend and postpend will be
    a manditory value
    Colors are defined as the SAME colors as rrd

    This will never look 100% like rrd, but lets get as close as we can without a bunch
    of complex code.
  */


  // This is simply for template buildout:
  // We will need to have them set in the class before this is called
  if ( ! isset($graphiteUrl)) {
    $graphiteUrl = "https://graphite.iwillfearnoevil.com";
    $graphitePort = 443;
  }

  if ( ! is_array($sourceList)) { $sourceList=json_decode($sourceList, true); }

  // Hard coded for now.  Later use will be to return metrics for
  // javascript to parse and display
  $returnFormat='';


  // These are all default values.  Override with the sourceOptions array!
  $from     = "-1d";
  $to       = "-1m";
  $width    = "897";
  $height   = "192";
  $colors   = array('00cc00', '0000ff', '00ffff', 'ff0000','ff9900', 'cc0000', '0000cc', '0080c0', '8080c0', 'ff0080', '800080', '0000a0', '408080', '808000', '000000', '00ff00', 'fb31fb', '0080ff', 'ff8000', '800000');
  $bgColor  = array('000000', 'ffffff');  // black, white
  $fgColor  = array('ffffff', '000000');  // white, black
  $lineWidth= 2;
  $rawData  = 'no';
  $prepend  = null;
  $postpend = null;
  $returnFormat='';                       // default is return a graph url

  if (! empty($sourceOptions) && $sourceOptions !== '') {
    if (isset($sourceOptions['from']))         { $from     = $sourceOptions['from'];}
    if (isset($sourceOptions['to']))           { $to       = $sourceOptions['to'];}
    if (isset($sourceOptions['width']))        { $width    = $sourceOptions['width'];}
    if (isset($sourceOptions['height']))       { $height   = $sourceOptions['height'];}
    if (isset($sourceOptions['colors']))       { $colors   = $sourceOptions['colors'];}        // This will be an array of colors if we are changing defaults, or simply setting defaults
    if (isset($sourceOptions['bgColor']))      { $bgColor  = $sourceOptions['bgColor'];}       // This will be an array of background colors
    if (isset($sourceOptions['returnFormat'])) { $rawData  = $sourceOptions['returnFormat'];}  // '', json, raw are supported
    if (isset($sourceOptions['prepend']))      { $prepend  = $sourceOptions['prepend']; }   else { $prepend = null; }
    if (isset($sourceOptions['postpend']))     { $postpend = $sourceOptions['postpend']; } else { $postpend = null; }
  }

  switch ($returnFormat) {
  case "json":
    $returnFormat='&format=json';
    break;
  case "raw":
    $returnFormat='&rawData';
    break;
  default:
    $returnFormat='';
    break;
  }

  if (empty($sourceList)) {
    return 1;    // Always assume something is borked first
  }

  if (! is_array($sourceList)) {
    $sourceList = json_decode($sourceList, true);
  }

  // Set our background early, since we want all images to be the same background normally
  $background = $bgColor[1];
  $foreground = $fgColor[1];

  // Since we are dealing with only ONE service check, we are going to use that for the title.
  $getId = explode('.',$sourceList[0]['id']);
  $title = $getId['3'];
  $title = preg_replace("/_/"," ", $title);
  $title = urlencode($title);

  // Find our metric names (Also will be title for graph)
  $metricNames = array();
  foreach($sourceList as $sourceListLevel) {
    foreach ( $sourceListLevel as $key => $value ) {
      if ( $key == 'id' ) {
        $keyName = preg_replace('/.*\./','',$value);
        $metricNames[$keyName] = $value;
      }
    }
  }
  // At this point we should have metric names to run through in a case statement
  // as well as enough variables to return URL's

  // Remember to use urlencode against strings that may have funny characters or spaces

  $renderUrl = array();
  $renderLooped ='';
  $colChange = 0;  // Make each metric have its own color

  foreach ($metricNames as $key => $value) {
    // https://stackoverflow.com/questions/15353924/php-check-array-index-out-of-bounds
    if ($colChange >= array_key_last($colors)) { $colChange = 0; }

    switch ($key) {
    case "load1":
      $legend = urlencode("load 1 minute");
      $metricColor = $colors[$colChange];
      $colChange = ++$colChange;
      break;
    case "load5":
      $legend = urlencode("load 5 minute average");
      $metricColor = $colors[$colChange];
      $colChange = ++$colChange;
      break;
    case "load15":
      $legend = urlencode("load 15 minute average");
      $metricColor = $colors[$colChange];
      $colChange = ++$colChange;
      break;
    default:
      $legend = $metricName;
      $metricColor = $colors[$colChange];
      $colChange = ++$colChange;
      break;
    }
    /*
      we are still inside the foreach loop, so create an array with the return data now
      simple example URL: https://graphite.iwillfearnoevil.com:443/render/?width=586&height=308&from=-16d&to=-1m&target=alias(*.guyver-office_iwillfearnoevil_com.nrpe.check_load.load1,%27check_load%20load1%27)
      Given how complex we CAN make things, use an array and implode it after so we can read whaat we are doing
      This will return 3 seporate URL's for graphs
    */
    $renderDisplay = array( "&width=", $width, "&height=", $height, "&from=", $from, "&to=", $to, "&bgcolor=", $background, "&fgcolor=", $foreground, "&majorGridLineColor=", "FF22FF", "&minorGridLineColor=", "darkgrey", "&title=", $title, "&lineWidth=", $lineWidth);
    $renderBegin=implode("", $renderDisplay);
    $renderDetails = array( "&target=color(alias(", $value, ",'", $legend, "'),'", $metricColor, "')");
    $renderEnd = implode("", $renderDetails);
    $renderUrl[$checkName][$checkName] = $graphiteUrl . ":" . $graphitePort . "/render/?" . $renderBegin . $renderEnd . $returnFormat;

    /*
     If we want to have all three on the same graph...
    */

     // $renderAppend = array( "&target=color(alias(", $value, ",'", $legend, "'),'", $metricColor, "')");  // Alias example
     $renderAppend = array( "&target=color(", $value, ",'", $metricColor, "')");
     $renderEndLoop = implode("", $renderAppend);
     $renderLooped .= $renderEndLoop;

  }
  if (isset($renderLooped) && $renderLooped !== '') {
    // Overwrite the array for renderUrl with the single URL of all metrics if it is datafilled
    $renderUrl[$checkName][$checkName] = [$graphiteUrl . ":" . $graphitePort . "/render/?" . $renderBegin . $renderLooped . $returnFormat];
  }


  $this->returnArrayValues=$renderUrl;
  return $this->returnArrayValues;
?>


