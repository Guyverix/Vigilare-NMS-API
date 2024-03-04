<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';


// CRUD for user access and control
use App\Application\Actions\User\ManageUserAction;
use App\Application\Actions\Admin\ManageAdminAction;

// Show info from the events table
use App\Application\Actions\Event\ListEventsAction;
use App\Application\Actions\Event\ViewEventAction;
use App\Application\Actions\Event\ViewTableEventAction;

// Work with ECE Event Correlation Engine
use App\Application\Actions\EventCorrelation\ManageEventCorrelationAction;

// Show info from the history table
use App\Application\Actions\History\ManageHistoryAction;

// Receive new traps/ events via web interface
use App\Application\Actions\Trap\NewTrapAction;

// Polling daemons
use App\Application\Actions\Poller\ActivePollerAction;

// SNMP info
use App\Application\Actions\Snmp\GetSnmpTableAction;
use App\Application\Actions\Snmp\GetSnmpOidAction;

// Maintenance system
use App\Application\Actions\Maintenance\EndMaintenanceAction;
use App\Application\Actions\Maintenance\SetMaintenanceAction;
use App\Application\Actions\Maintenance\FindMaintenanceAction;

// OID name mappings
use App\Application\Actions\NameMap\FindNameMapAction;
use App\Application\Actions\NameMap\FindAllNameMapAction;
use App\Application\Actions\NameMap\SetNameMapAction;
use App\Application\Actions\NameMap\UpdateNameMapAction;
use App\Application\Actions\NameMap\DeleteNameMapAction;

// deprecated
use App\Application\Actions\ExternalCommand\FindCheck;
use App\Application\Actions\ExternalCommand\CreateCheck;
use App\Application\Actions\ExternalCommand\UpdateCheck;
use App\Application\Actions\ExternalCommand\DeleteCheck;

// API to create new SNMP polling (todo/wip)
use App\Application\Actions\ExternalSNMP\FindSnmpCheck;
use App\Application\Actions\ExternalSNMP\CreateSnmpCheck;
use App\Application\Actions\ExternalSNMP\UpdateSnmpCheck;
use App\Application\Actions\ExternalSNMP\DeleteSnmpCheck;


// Trap OID mapping route (make pretty names)
use App\Application\Actions\Mapping\ViewMappingAction;
use App\Application\Actions\Mapping\ViewAllMappingAction;
use App\Application\Actions\Mapping\CreateMappingAction;
use App\Application\Actions\Mapping\UpdateMappingAction;
use App\Application\Actions\Mapping\DeleteMappingAction;

// NEW mapping API host, hostGroup, hostAttribute, pollers, templates
// While better, probably not the best solution here
use App\Application\Actions\GlobalMapping\CompleteGlobalMappingAction;

/* More like Zenoss 2.5.2 style - breakout to specific jobs for the given Class */

// Device Controls
use App\Application\Actions\Device\ManageDeviceAction;

// Device folder
use App\Application\Actions\DeviceFolder\CreateDeviceFolder;
use App\Application\Actions\DeviceFolder\UpdateDeviceFolder;
use App\Application\Actions\DeviceFolder\DeleteDeviceFolder;
use App\Application\Actions\DeviceFolder\ViewDeviceFolder;

// Device properties
use App\Application\Actions\DeviceProperties\CreateDeviceProperties;
use App\Application\Actions\DeviceProperties\UpdateDeviceProperties;
use App\Application\Actions\DeviceProperties\DeleteDeviceProperties;
use App\Application\Actions\DeviceProperties\ViewDeviceProperties;

// Discovery action which will create folders and properties above
use App\Application\Actions\Discovery\ManageDiscoveryAction;

// Discover deprecated
//use App\Application\Actions\Discover\NewDiscoverAction;

use App\Application\Actions\Reporting\ManageReportingAction;


// MonitoringPoller action which will retrieve values that are used in monitoring
use App\Application\Actions\MonitoringPoller\ManageMonitoringPollerAction;

// Monitors action CRUD for monitors
use App\Application\Actions\Monitors\ManageMonitorsAction;


// API for infrastructure results
// This should be tied to DeviceFolders so we can use it for templates
use App\Application\Actions\Infrastructure\ViewInfrastructureAction;

// API for rendering Graph data
use \App\Application\Actions\RenderGraph\ManageRenderGraphAction;

// Make an API call that will return the graphite URLs
use App\Application\Actions\Graphite\ViewGraphiteAction;
use App\Application\Actions\Graphite\CatchGraphiteAction;
use App\Application\Actions\Graphite\ManageGraphiteAction;


/* API for triggers
   This is still completely TODO
use App\Application\Actions\Triggers\ViewTriggers;
use App\Application\Actions\Triggers\CreateTriggers;
use App\Application\Actions\Triggers\UpdateTriggers;
use App\Application\Actions\Triggers\DeleteTriggers;
use App\Application\Actions\Triggers\TestTriggers;
*/

// Authentication classes
use \Firebase\JWT\JWT;
use App\Application\Actions\Auth\CreateTokenAuthAction;
use App\Application\Middleware\JwtAuthenticationMiddleware;
use Tuupola\Middleware\JwtAuthentication;
use App\Application\Middleware\AccessListMiddleware;
use App\Application\Middleware\ApiKeyMiddleware;

// catchall classes that the others rely on
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Framework includes
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

// This creates the object that the routes get put into from what I can tell.
$app = AppFactory::create();

// This is where all the routing happens
return function (App $app) {

  // A way to add additional options to routes..
  $app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
  });

  // Add headers in to basically allow all for CORS.
  // This can be filtered down for smaller, or discrete networks.
  // If this gets grouchy on the UI side: ->withHeader('Access-Control-Allow-Origin', '.<DOMAIN>')
  $app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
      ->withHeader('Access-Control-Allow-Origin', '*')
      ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, token')
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
  });

  /*
     This should actually by default give a 418 since it is an API
     and perhaps a better response page, as likely a human called it
  */
  $app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('supported routes: /events /history /discovery /host /hostgroup /mapping /trap /dump /monitoring /reporting /remote /snmp /auth /monitorgroup /poller /housekeeping');
    return $response->withStatus(418);
  });

  // user information for user -> admins  # just a JWT is not enough.
  $app->group('/user', function (Group $group) {
    $group->post('/{job}', ManageUserAction::class)->add(new \App\Application\Middleware\AccessListMiddleware('user'))->add(JwtAuthenticationMiddleware::class);
  });

  // This is administrator route only.
  $app->group('/admin', function (Group $group) {
    $group->post('/{job}', ManageAdminAction::class)->add(new \App\Application\Middleware\AccessListMiddleware('admin'))->add(JwtAuthenticationMiddleware::class);
  });

  // Used in account setup.  No auth.  Possible attack vector?
  $app->group('/account', function (Group $group) {
    $group->post('/{job}', ManageUserAction::class);
  });

  // this is our login path to get a token!
  $app->group('/auth', function (Group $group) {
    $group->post('/access_token', CreateTokenAuthAction::class);
    // $group->get('/access_token', CreateTokenAuthAction::class);  // never use GET for this
  });

  // Logout path (kill off cookies)
  $app->group('/logout', function (Group $group) {
    $group->post('/logout', RemoveTokenAuthAction::class);
    $group->get('/logout', RemoveTokenAuthAction::class);
  });

  /*
    ****************** TESTING ROUTES ****************************
    Everything that is "testing" needs to go under a /debug route!
    https://stackoverflow.com/questions/71458970/get-settings-in-slim-4-eg-path-of-application-root-directory
    Proves how to get stuff out of settings.php
  */

  // this is our testing for cookies login path.
  $app->group('/debug/auth2', function (Group $group) {
    $group->post('/access_token', CreateTokenAuthAction::class);
  });

  $app->get('/debug/get/server', function (Request $request, Response $response) {
    $response->getBody()->write( print_r($_SERVER,true));
    return $response->withStatus(418);
  });

  $app->get('/debug/get/cookie', function (Request $request, Response $response) {
    $response->getBody()->write( print_r($_COOKIE,true));
    return $response->withStatus(418);
  });

  $app->post('/debug/get/post', function (Request $request, Response $response) {
    $response->getBody()->write( print_r($_POST,true));
      //    $response->getBody()->write( var_dump($_POST));
      //    return $response->withStatus(418)->withHeader('Access-Control-Allow-Origin', '*');
      //    return $response->withStatus(418);
    return $response;
  });

  $app->get('/debug/get/json', function (Request $request, Response $response) {
    //    $contents = json_decode(file_get_contents('php://input'), true);
    $test = $request->getParsedBody();
    $response->getBody()->write(json_decode($test,1));
    return $response->withStatus(418);
  });

  $app->post('/debug/get/json', function (Request $request, Response $response) {
    //    $contents = json_decode(file_get_contents('php://input'), true);
    $test = '';
    //    $test .= $request->getParsedBody();
    $test = var_dump($_POST);
    $response->getBody()->write($test);
    //    return $response->withStatus(418)->withHeader('Access-Control-Allow-Origin', '*');
    return $response->withStatus(418);
  });

  // got from chatgpt after some arguing
  $app->get('/debug/set/cookie', function (Request $request, Response $response) {
    $response = new \Slim\Psr7\Response();
    $response = $response->withHeader('Set-Cookie', 'my_cookie=cookie_value; Path=/; Expires=' . gmdate('D, d M Y H:i:s T', time() + 30));

    $response->getBody()->write('Cookie set successfully');
    return $response->withAddedHeader('Set-Cookie', 'domain=iwillfearnoevil.com ; Path=/;')
                    ->withStatus(201);
  });

  // brainless example to pull things like passwords, etc when needed.
  // not sure I am happy with this as it seems like a likely attack vector
  $app->get('/debug/settings', function (Request $request, Response $response) {
    $values = $this->get('superSecretSettingsValue');
    $response->getBody()->write("Settings pull result for superSecretSettingsValue: " . json_encode($values,1));
    return $response->withStatus(418);
  });

  // simple show $this object
  $app->get('/debug/this', function (Request $request, Response $response) {
    $values = var_dump($_COOKIE);
    $response->getBody()->write(json_encode($values,1));
    return $response->withStatus(418);
  });

  // playing with finding server variables that are defined
  $app->get('/debug/server', function (Request $request, Response $response) {
    $values = var_dump($_SERVER);
    $response->getBody()->write(json_encode($values,1));
    return $response->withStatus(418);
  });

  $app->group('/debug/events', function (Group $group) {
        $group->get('', ListEventsAction::class);
        $group->get('/{action}', ViewEventAction::class);
        $group->get('/{action}/{column}', ViewEventAction::class);
        $group->get('/{action}/{column}/{direction}/{filter}', ViewEventAction::class);
  })->add(JwtAuthenticationMiddleware::class);

  // Working example of using the JWT tokens
  // Working AUTH against routes using functions
  $app->get('/debug/token-test', function (Request $request, Response $response) {
    $headers = $request->getHeaders();
    if(isset($headers['Authorization'])) {
      $foo = preg_replace('/Bearer /', '', $headers['Authorization'][0]);              // Strip out the string to only have the JWT to decode
    }
    elseif (isset($_COOKIE['Authentication'])) {                                       // Support cookie containing jwt
      $foo = $_COOKIE['Authentication'];
    }
    else {
      return $response->withStatus(401);                                               // Return get stuffed and auth
    }
    $decoded = JWT::decode($foo, $this->get('secret_key'), array("HS256"));            // Eventually get the arr value also from settings.php
    $decoded2 = json_decode(json_encode($decoded,1),true);                             // Convert our object to a basic array, sigh
    $test = explode(',' ,$decoded2['data']['accessList']);                             // Create an array of what we are allowed to access
    $userName = $decoded2['data']['realName'];
    $userId   = $decoded2['data']['username'];
    if ( in_array("admin", $test)) {                                              // If string is in our allowed list pass on
      $response->getBody()->write('Token is right. admin found within accessList: ' . json_encode($decoded2,1));
      return $response->withAddedHeader('Set-Cookie', 'userId=' . $userId . '; Path=/;')
                      ->withAddedHeader('Set-Cookie', 'userName=' . $userName . '; Path=/;')
                      ->withAddedHeader('Set-Cookie', 'domain=iwillfearnoevil.com ; Path=/;')
                      ->withStatus(418);
    }
    else {                                                                             // Token is right, but we are not allowed anyway
      $response->getBody()->write('Token is right but does not match against filter to allowed list of: ' . json_encode($test,1));
      return $response->withAddedHeader('Set-Cookie', 'userId=' . $userId . '; Path=/;')
                      ->withAddedHeader('Set-Cookie', 'userName=' . $userName . '; Path=/;')
                      ->withAddedHeader('Set-Cookie', 'domain=iwillfearnoevil.com ; Path=/;')
                      ->withStatus(418);
    }
  })->add(JwtAuthenticationMiddleware::class);

  // Just show the headers we are working with
  $app->get('/debug/headers', function (Request $request, Response $response) {
    $headers = $request->getHeaders();
    $response->getBody()->write( json_encode($headers,1) );
    return $response;
  });

  // Testing show database schema with API auth only
  $app->group('/debug/dumpApi' , function (Group $group) {
    //$group->get('/{table}', ViewTableEventAction::class)->add(ApiKeyMiddleware::class);               // API only
    $group->get('/{table}', ViewTableEventAction::class)->add(JwtAuthenticationMiddleware::class);      // JWT+API
  });

  // Show database schema for a given table
  $app->group('/debug/dump' , function (Group $group) {
    $group->get('/{table}', ViewTableEventAction::class)->add(new \App\Application\Middleware\AccessListMiddleware('operator'))->add(JwtAuthenticationMiddleware::class);    // JWT+API then accessList
    //$group->get('/{table}', ViewTableEventAction::class)->add(JwtAuthenticationMiddleware::class);                                                                         // JWT+API only
  });

  // Testing auth against API with an api key set
  $app->post('/debug/trap',        NewTrapAction::class)->add(ApiKeyMiddleware::class);

  // TESTING APIS
  // think basic function tests?  oddball tests... Misc?  Donno.
  $app->post('/debug/test/{action}', ManageTestAction::class);

  /******************************* End of debugging area.  Back to useable APIS ****************************/

  // Quick way to stat the pollers and housekeeping daemons
  $app->get('/poller/{poller}/{state}', ActivePollerAction::class);

  // Active Events
  $app->group('/events', function (Group $group) {
        $group->get('', ListEventsAction::class);
        $group->get('/{action}', ViewEventAction::class);
        $group->get('/{action}/{column}', ViewEventAction::class);
        $group->get('/{action}/{column}/{direction}/{filter}', ViewEventAction::class);
  });
  $app->post('/events/{action}', ViewEventAction::class);

  // Historical Events
  $app->group('/history', function (Group $group) {
        $group->get('/{action}', ManageHistoryAction::class);
        $group->get('/{action}/{column}', ManageHistoryAction::class);
        $group->get('/{action}/{column}/{direction}/{filter}', ManageHistoryAction::class);
        $group->post('/{action}', ManageHistoryAction::class);  // Likely not needed (ever)
  });

  // ECE Event Correlation Engine
  $app->post('/ece/{action}', ManageEventCorrelationAction::class);
  /*
    This is future ( if needed )
  $app->group('/ece', function (Group $group) {
        $group->post('/{action}', ManageEventCorrelationAction::class);
  }
  */

  /*
    This will likely need to go elsewhere long term
    Trap specific.  No discrete new as trap will always be new
    This will ALWAYS be a POST.
  */
  $app->post('/trap/map',      MapTrapAction::class);
  $app->post('/trap/hostname', HostnameTrapAction::class);
  $app->post('/trap/update', UpdateTrapAction::class);
  $app->post('/trap/delete', DeleteTrapAction::class);
  $app->post('/trap',        NewTrapAction::class);  // no auth
  // $app->post('/trap',        NewTrapAction::class)->add(ApiKeyMiddleware::class);  // apiKey set in daemonFunction to call this.


  /* hostgroup kinda like Nagios grouping goes to Monitors class */
  $app->post('/hostgroup/new', newHostgroupAction::class);
  $app->post('/hostgroup/update', updateHostgroupAction::class);
  $app->post('/hostgroup/delete', deleteHostgroupAction::class);
  $app->post('/hostgroup', ViewHostgroupAction::class);

  /* relationship define what is above or below an event for Ece, and events TODO */
  $app->post('/relation/new', newRelationAction::class);
  $app->post('/relation/update', updateRelationAction::class);
  $app->post('/relation/delete', deleteRelationAction::class);
  $app->post('/relation', ViewRelationAction::class);

  /*
    Add support for REMOTE instances
    TODO
  */
  $app->post('/remote/new', newRemoteAction::class);
  $app->post('/remote/update', updateRemoteAction::class);
  $app->post('/remote/delete', deleteRemoteAction::class);
  $app->post('/remote', ViewRemoteAction::class);

  /*
    Reporting including csv and possibly XLS?
    Investigate a daemon, or simply have housekeeping
    Either way, save into table reporting, and display
    somewhere that can be easily found.
  */
  $app->group('/reporting' , function (Group $group) {
    $group->post('/{action}', ManageReportingAction::class);
    $group->get('/{action}', ManageReportingAction::class);
  });

  /*
    Rendered images from Rrd reside in public/static
  */
  $app->post('/render/{action}', ManageRenderGraphAction::class);


  /*
    Retrun a list of configured Graphite Graphs filtered by {filter}
    if no arg, just return all
  */
  $app->post('/graphite/test', ManageGraphiteAction::class);
  $app->get('/graphite/{filter}', ViewGraphiteAction::class);
  $app->post('/graphite/{filter}', ViewGraphiteAction::class);
  $app->get('/graphite',          CatchGraphiteAction::class);

  /* Set maintenance states */
  $app->post('/maintenance/{action}',  ManageMaintenanceAction::class);

  /*
    ALL mappings go here:
    action: snmp, nrpe, shell, curl, other?
    job: view, create, update, delete
    post: all vars needed to complete task
    new mapping database completed and standardized, YAY!
  */
  $app->get('/globalMapping/{action}/{job}', CompleteGlobalMappingAction::class);
  $app->post('/globalMapping/{action}/{job}', CompleteGlobalMappingAction::class);

  /* Old style to be retired? WIP */
  $app->post('/mapping/find',    ViewMappingAction::class);
  $app->get('/mapping/findall',  ViewAllMappingAction::class);
  $app->post('/mapping/set',     CreateMappingAction::class);
  $app->post('/mapping/update',  UpdateMappingAction::class);
  $app->post('/mapping/delete',  DeleteMappingAction::class);
  $app->post('/snmp/table', GetSnmpTableAction::class);
  $app->post('/snmp/oid', GetSnmpOidAction::class);


  // Anything dealing directly with monitoring
  $app->post('/monitors/{action}', ManageMonitorsAction::class);
  $app->post('/monitors', ViewMonitorAction::class);

  // Device class.  
  $app->post('/device/{action}', ManageDeviceAction::class);
  $app->post('/device', ManageDeviceAction::class);
  $app->get('/device', ManageDeviceAction::class);

  // This is going to be a pita, but useful.  leverage infrastructure and infrastructureProducts
  // Make VERY tight rules so this cannot hide things
  $app->post('/deviceFolder/{action}/{folder}', ManageDeviceFolderAction::class);

  // All values associated with a given device.  Includes passwords! salt the hell out of them
  // when told to do so.  Salt value will have to be hidden somewhere, but still accessable.
  // initial MVP will have this in a config file.  If a password is defined, or something else
  // sensitive, the user will have to tell us, so we can salt it.  Never double salt something
  // it will have to only be done on create or update specific to the password or sensitive value
  // unsalting the value will have to be on demand.  Think about this more
  $app->post('/deviceProperties/{action}/device/{hostname}', ManageDeviceProperties::class);

  // Networking definitions are more passive and pulled from the devices
  // however being able to visualize things will necessary.
  // network paths, network trace, network view (generic)
  // TODO
  $app->get('/network/{action}/{addressRange}', ViewNetworkRangeAction::class);

  // Templates tie into both deviceProperties and monitoring.  Anything needing
  // template values should go here for defaults in setting values for a host.
  // This will also be used by discovery for default values.
  $app->post('/template/{action}/{templateName}', ManageTemplateAction::class);

  /*
    Discovery results will be kept in the db, and cleaned out
    via housekeeping over time.  This should be long standing
    data kept in the db and exist for the hosts lifetime.
  */
  $app->post('/discovery/{action}', ManageDiscoveryAction::class);
  $app->get('/discovery/{action}', ManageDiscoveryAction::class);                    // should not really be used.

  /*
    monitoringPoller is just a simple API to pull arrays
    of defined monitors just like the daemon does.
    GET will have to add values to URL to work properly ?foo=bar
    heartbeats can also be POSTED here
  */
  $app->get('/monitoringPoller/{action}', ManageMonitoringPollerAction::class);
  $app->post('/monitoringPoller/{action}', ManageMonitoringPollerAction::class);


  // This will show a tree of the mapped infrastructure
  // kinda eye candy, but useful still
  $app->post('/infrastructure/{action}', ViewInfrastructureAction::class);
  $app->get('/infrastructure/{action}', ViewInfrastructureAction::class);

  /*
    This is unused since we have a MAP defined for types
    Going to have to make more use of PUT, DELETE, PATCH
    connections with the API.  Looks like thats how real
    developers do it.  this Use is set at the top anyway
    use Slim\Exception\HttpNotFoundException;
  */
  $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
  });
};
$app->run();
