<?php
/*
  Version: 1.1
  Author: xopr
  Date: 2015-07-06
*/

const STR_OPEN    = "Open";
const STR_CLOSED  = "Closed";
const STR_UNKNOWN = "Unknown";

class state
{
    public function updateSpaceApi( $_spaceAPI )
    {
        global $stateAbstraction;

        $apiPart = Array();

        /*
        $apiPart["trigger_person"] = "Herman ACKer";
        */

        /*
        //$apiPart["lastchange"] = $stateAbstraction->getStateChangedTimestamp();
        //$apiPart["message"] = "open";
        */

        $state = $stateAbstraction->getState();
        $description = $stateAbstraction->getStateDescription( $state[ "state" ] );

        switch( $state[ "state" ] )
        {
          case "-2":
          case "0":
            // Closed
            $apiPart["open"] = false; // Mandatory
            $apiPart["message"] = STR_CLOSED;
            break;

          case "-1":
          case "1":
            // Open
            $apiPart["open"] = true; // Mandatory
            $apiPart["message"] = STR_OPEN;
            break;

          case "2":
            // Unknown
            $apiPart["open"] = null; // Mandatory
            $apiPart["message"] = STR_UNKNOWN;
            break;
        }

        if ( !is_null( $description ) )
            $apiPart["message"] = $description;

        $apiPart["lastchange"] = (int)$state[ "created" ];

        $apiPart["icon"]["open"] = "https://ackspace.nl/icon/open.png";
        $apiPart["icon"]["closed"] = "https://ackspace.nl/icon/closed.png";
    
        return $apiPart;
    }

    public function updateDatabase( )
    {
        global $stateAbstraction;

        // Read the state parameter
        switch ( getVar( "state" ) )
        {
          case "-2":
          case "-1":
          case "0":
          case "1":
            return $stateAbstraction->updateState( (int)getVar( "state" ) );
        }

        // State not handled correctly
        return false;
    }

}
?>
