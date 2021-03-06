<?php
/*
  Version : 1.1.1
  Author  : xopr
  Date    : 2015-07-06
  Changes : Added SensorAbstraction
  Date    : 2016-05-24
  Changes : added prettyprint, listed parameters
*/

/*
optional parameters:

update=<module name>        : updates the given module if API key is valid. true defaults to "state"
key=<APY_KEY>               : API key which allows updating modules
compatibility={true|false}  : reorganises fields to make it compatible with spaceAPI versions 0.8-0.13
prettyprint={true|false}    : adds indentation and newlines for ease of reading
debug                       : adds some debug information (breaks JSON)
*/

// xopr: Enable debugging (when shit hits the fan again)
if ( getVar( "debug" ) !== false )
{
    error_reporting( -1 );
    ini_set( 'display_errors', 1 );
}

require "StateAbstraction.php";
require "SensorAbstraction.php";

// Include API key
include( $_SERVER['DOCUMENT_ROOT']."/../spaceAPI_config.php" );

// Send headers immediately
header( "Access-Control-Allow-Origin: *" );
header( "Access-Control-Allow-Methods: GET" );
header( "Content-Type: application/json" );
header( 'Content-Type: text/javascript; charset="UTF-8"' );
//header( "Content-Type: text/html; charset=utf-8" );


$stateAbstraction = new StateAbstraction();
$stateAbstraction->init();

$sensorAbstraction = new SensorAbstraction();
$sensorAbstraction->init();

// Hard-coded static info
// See: http://spaceapi.net/documentation
// Note: lat/lng was: 50.8924807,5.9712384: just outside of the building
$spaceAPIjson = <<<EOF
{
    "api" : "0.13",
    "space" : "ACKspace",
    "logo" : "https://ackspace.nl/w/images/3/3b/Wiki_logo.png",
    "url" : "https://ackspace.nl/",
    "location" : 
    {
        "address" : "Kloosterweg 1, 6412 CN Heerlen",
        "lat" : 50.8924622,
        "lon" : 5.9712601,
        "ext_floor" : 4,
        "ext_room" : "L406"
    },
    "state" :
    {
        "open" : null
    },
    "contact" :
    {
        "email" : "contact@ackspace.nl",
        "irc" : "irc://freenode/#ACKspace",
        "keymaster" :
        [
          "31457112345, extension 1333"
        ],
        "ml" : "info@lists.ackspace.nl",
        "phone" : "31457112345",
        "sip" : "31457112345@sip1.budgetphone.nl",
        "twitter" : "@ACKspace"
    },
    "issue_report_channels" :
    [
        "email"
    ]
}
EOF;

// NOTE: commented variables are defined in the primary json string
$optionalItems = Array(
  /*"api"                   => true,*/
  /*"space"                 => true,*/
  /*"logo"                  => true,*/
  /*"url"                   => true,*/
  /*"location"              => true,*/
    "spacefed"              => false,
    "cam"                   => false,
    "stream"                => false,
    "state"                 => true,
    "events"                => false,
  /*"contact"               => true,*/
  /*"issue_report_channels" => true,*/
    "sensors"               => false,
    "feeds"                 => false,
    "cache"                 => false,
    "projects"              => false,
    "radio_show"            => false
);

// Create associative array of json space API
$spaceAPI = json_decode( $spaceAPIjson, true/*associative array*/ );

if ( !$spaceAPI )
{
    switch ( json_last_error() )
    {
        case JSON_ERROR_DEPTH:
            die( "JSON_ERROR_DEPTH" );
        case JSON_ERROR_STATE_MISMATCH:
            die( "JSON_ERROR_STATE_MISMATCH" );
        case JSON_ERROR_CTRL_CHAR:
            die( "JSON_ERROR_CTRL_CHAR" );
        case JSON_ERROR_SYNTAX:
            die( "JSON_ERROR_SYNTAX" );
        case JSON_ERROR_UTF8:
            die( "JSON_ERROR_UTF8" );
    }
}

// Fetch the update variable
$update = getVar( "update" );

if ( $update !== false )
{
  // Verify the API key
  if ( getVar( "key" ) !== SPACEAPI_KEY )
  {
    // Delay against brute force
    sleep( 2 );
    echo "{\"message\":\"invalid api key\"}";
    exit( 0 );
  }

  // COMPATIBILITY: Check if update has no value, default to update=state
  if ( !$update )
      $update = "state";
}

foreach( $optionalItems as $optionalItem => $mandatory )
{
    if ( is_readable( $optionalItem . ".php" ) )
    {
        // Use __autoload
        if ( class_exists( $optionalItem ) )
        {
            $instance = new $optionalItem();

            // Did we call the update method (with a verified API key)?
            if ( ($update === $optionalItem) && method_exists( $instance, "updateDatabase" ) )
            {
                if ( $instance->updateDatabase( ) )
                    echo "{\"message\":\"ok\"}";
                else
                    echo "{\"message\":\"update failed\"}";
                exit( 0 );
                //continue;
            }
            else if ( method_exists( $instance, "updateSpaceApi" ) )
            {
                $result = $instance->updateSpaceApi( $spaceAPI );
                if ( $result !== null )
                {
                    // Check for compatibility mode
                    if ( getVar( "compatibility" ) !== false )
                    {
                        if ( $optionalItem === "feeds" )
                        {
                            // Skip feeds since it became an associative array in version 0.13
                            continue;
                        }
                        else if ( $optionalItem === "state" )
                        {
                            // Copy over icon open/closed string as a mandatory root node
                            $spaceAPI[ "icon" ] = $result[ "icon" ];
                        }
                    }

                    $spaceAPI[ $optionalItem ] = $result;
                    // Go to the next item in the list
                    continue;
                }
            }
        }
    }

    // If we reached this, the subclass has not been updated
    if ( $mandatory )
    {
        die( "mandatory fail: " . $optionalItem );
    }
}

if ( getVar( "debug" ) !== false )
{
  echo "<pre>";
}

/*JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT*/
$flags = JSON_UNESCAPED_SLASHES;
if ( getVar( "prettyprint" ) !== false )
    $flags |= JSON_PRETTY_PRINT;
if ( getVar( "debug" ) !== false )
    echo "======= JSON SPACE API =======\n";

$result = json_encode( $spaceAPI, $flags );

if ( $result === FALSE )
{
    $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
    $spaceAPI[ "error" ] = "parser error";
    $result = json_encode( $spaceAPI, $flags );

    if ( $result === FALSE )
        die( '{"error":"parser error"}' );
}

echo $result;

if ( getVar( "debug" ) !== false )
{
    echo "======= RAW SPACE API =======\n";
    var_dump( $spaceAPI );
    echo "</pre>";
}

//$GLOBALS['_global_function_getVar'] = 'getVar';
function getVar( $_name, $_bGet = true, $_bPost = false, $_bSession = false )
{
    if ( $_bGet && array_key_exists( $_name, $_GET ) )
      return $_GET[ $_name ];

    if ( $_bPost && array_key_exists( $_name, $_POST ) )
      return $_POST[ $_name ];

    if ( $_bSession && array_key_exists( $_name, $_SESSION ) )
      return $_SESSION[ $_name ];

    return false;
}

function __autoload( $class )
{
    include($class . '.php');
    // Check to see whether the include declared the class
    /*
    if (!class_exists($class, false))
    {
        trigger_error("Unable to load class: $class", E_USER_WARNING);
    }
    */
}

?>
