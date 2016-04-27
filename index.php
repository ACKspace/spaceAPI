<?php
/*
  Version : 1.1.1
  Author  : xopr
  Date    : 2015-07-06
  Changes : Added SensorAbstraction
*/

require "StateAbstraction.php";
require "SensorAbstraction.php";

const APIKEY = "<YOUR_OWN_GENERATED_API_KEY_HERE>";

// Send headers immediately
header('Content-Type: application/json');
header('Content-Type: text/html; charset=utf-8');

$stateAbstraction = new StateAbstraction();
$stateAbstraction->init();

$sensorAbstraction = new SensorAbstraction();
$sensorAbstraction->init();

// Hard-coded static info
// See: http://spaceapi.net/documentation
$spaceAPIjson = <<<EOF
{
    "api" : "0.13",
    "space" : "ACKspace",
    "logo" : "https://ackspace.nl/w/images/3/3b/Wiki_logo.png",
    "url" : "https://ackspace.nl/",
    "location" : 
    {
        "address" : "Kloosterweg 1, 6412 CN Heerlen",
        "lat" : 50.8924807,
        "lon" : 5.9712384,
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
  if ( getVar( "key" ) !== APIKEY )
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

echo json_encode( $spaceAPI, 64 | 128/*JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT*/ );

if ( getVar( "debug" ) !== false )
{
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

