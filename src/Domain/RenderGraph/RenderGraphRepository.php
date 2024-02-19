<?php
declare(strict_types=1);

namespace App\Domain\RenderGraph;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// This is used for direct filesystem stuff kinda like ls, etc
include_once __DIR__ . '/../../Infrastructure/Shared/Functions/filesystemFunctions.php';

/*
  This is likely to be a bit more finicky as we are going to have to query
  the database for creation values of graphs.  I expect that the supporting functions
  will be quite rigid in their interpatation of what the user expects to see back.
  RRD for example is going to need to support odd possibilities on demand for
  predictive graphing.  We also need to fully support things like confidence banding
  and have it look close between RRD and graphite when the two mix

  Manditory parameters will also have to be taken into account.
    Graph TYPES: rrd, graphite, influx & databaseMetric (think jquery and client graphing)
    Times: default, to and from (Windows defined on UI side)
    Args: how to render the graph
    Prediction: RRD only
    Confidence banding: Default styles are only ones available!  These are a PITA
    Metric Name[s]: (empty returns ALL for the type?)
      RRD database can have several DS inside one file
      Graphite can have many leaf nodes under one parent
*/



// CRUD  Create Retrieve Update Delete
interface RenderGraphRepository {

    // Create RenderGraph stuff
    public function createGraph($arr): array;       // Create our image and return link

    // Retrieve Information of some kind
    public function findRrdTemplates($arr): array;  // return list of templates matched to filename paths
    public function findRrdDatabases($arr): array;  // return list of rrds
    public function findGraphiteLinks($arr): array; // return list of graphite URLs
    public function graphMetricSetup($arr): array;  // Return data on the metrics themselves to create a graph from
    public function linkGraph($arr): array;         // Return existing link to image
    public function debugGraph($arr): array;        // Return link AND vars used to create image

    // Update RenderGraph?
    // Unused, data changes are done in a different path.  We only consume

    // Delete RenderGraph images
    public function deleteGraph($arr): array;        // filesystem delete graph

}
