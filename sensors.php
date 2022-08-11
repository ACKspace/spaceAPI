<?php
/*
  Version : 1.1.2
  Author  : xopr
  Date    : 2015-07-06
  Changes : Added SensorAbstraction
  Date    : 2016-11-05
  Changes : Cleaned. Renamed functions. Prepared for beacon sensor
*/

class sensors
{
    public function updateSpaceApi( $_spaceAPI )
    {
        global $sensorAbstraction;

        $sensors = $sensorAbstraction->getSensors( "temperature" );
        if ( getVar( "debug" ) !== false )
        {
            var_dump( $sensors );
        }

        $apiPart = Array();
        // Mandatory

        if ( getVar( "debug" ) !== false )
            echo "======= update sensors =======\n";

        // Temperature, Optional
        $temperature = Array();
        $power_consumption = Array();
        if ( $sensors && count( $sensors ) )
        {
            foreach ( $sensors as $sensor )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( $sensor );

                switch ( $sensor[ "type" ] ) // TODO: type
                {
                    case "temperature":
                        // Ugly hack, since the webserver doesn't support utf8
                        // See http://allseeing-i.com/How-to-setup-your-PHP-site-to-use-UTF8
                        //$sensor[ "type" ] = "°C";
                        $sensor[ "unit" ] = "°C"; // celcius

                        // Copy over value, unit, location, name and description
                        $apiSensor = Array(
                            "value" => floatval( $sensor[ "value" ] ),
                            "unit" => $sensor[ "unit" ],
                            "location" => $sensor[ "location" ],
                            "ext_lastchange" => (int)$sensor[ "updated" ]
                        );

                        if ( !is_null( $sensor[ "name" ] ) )
                            $apiSensor[ "name" ] = $sensor[ "name" ];

                        if ( !is_null( $sensor[ "description" ] ) )
                            $apiSensor[ "description" ] = $sensor[ "description" ];

                        $temperature[] = $apiSensor;
                        break;

                    case "power_consumption":
                        $apiSensor = Array(
                            "value" => floatval( $sensor[ "value" ] ),
                            "unit" => $sensor[ "unit" ],
                            "location" => $sensor[ "location" ],
                            "ext_lastchange" => (int)$sensor[ "updated" ]
                        );

                        if ( !is_null( $sensor[ "name" ] ) )
                            $apiSensor[ "name" ] = $sensor[ "name" ];

                        if ( !is_null( $sensor[ "description" ] ) )
                            $apiSensor[ "description" ] = $sensor[ "description" ];

                        $power_consumption[] = $apiSensor;
                        break;
                }

            }
        }
        if ( count( $temperature ) )
            $apiPart[ "temperature" ] = $temperature;
        if ( count( $power_consumption ) )
            $apiPart[ "power_consumption" ] = $power_consumption;

/*
    [id] => 1
    [lat] => 0
    [lon] => 1
    [accuracy] => 100001
    [altitude] =>
    [altitudeAccuracy] =>
    [heading] =>
    [speed] =>
    [name] => HoaB
    [description] =>
    [updated] => 1462993990
*/
        if ( getVar( "beacon_log" ) !== false )
            $sensors = $sensorAbstraction->getBeaconSensorLog( getVar( "beacon_log" ) );
        else
            $sensors = $sensorAbstraction->getBeaconSensors();
        // Beacon, Optional
        $beacon = Array();
        if ( $sensors && count( $sensors ) )
        {
            foreach ( $sensors as $sensor )
            {
                $apiSensor = Array(
                    "location" => Array(
                        "lat" => floatval( $sensor[ "lat" ] ),
                        "lon" => floatval( $sensor[ "lon" ] ),
                        "accuracy" => floatval( $sensor[ "accuracy" ] )
                    ),
                    "name" => $sensor[ "name" ],
                    "ext_lastchange" => (int)$sensor[ "updated" ]
                );

                if ( !is_null( $sensor[ "description" ] ) )
                    $apiSensor[ "description" ] = $sensor[ "description" ];

                $beacon[] = $apiSensor;
            }
        }
        if ( count( $beacon ) )
            $apiPart[ "beacon" ] = $beacon;

        $sensors = $sensorAbstraction->getStock();
        // beverage_supply, Optional
        $beverage_supply = Array();
        if ( $sensors && count( $sensors ) )
        {
            foreach ( $sensors as $sensor )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( $sensor );

                $apiSensor = Array(
                    "value"         => intval($sensor[ "value" ]),
                    "unit"          => $sensor[ "unit" ],
                    "location"      => $sensor[ "location" ],
                    "name"          => $sensor[ "name" ],
                    "ext_lastchange" => (int)$sensor[ "updated" ]
                );
                if ( !is_null( $sensor[ "description" ] ) )
                    $apiSensor[ "description" ] = $sensor[ "description" ];

                $beverage_supply[] = $apiSensor;
            }
        }
        if ( count( $beverage_supply ) )
            $apiPart[ "beverage_supply" ] = $beverage_supply;

        $sensors = $sensorAbstraction->getServiceSensors();
        // Service, Optional
        $service = Array();
        if ( $sensors && count( $sensors ) )
        {
            foreach ( $sensors as $sensor )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( $sensor );

                $apiSensor = Array(
                    "name" => $sensor[ "name" ],
                    "source" => $sensor[ "source" ],
                    "status" => $sensor[ "status" ],
                    "ext_lastchange" => (int)$sensor[ "updated" ]
                );

                if ( !is_null( $sensor[ "latency" ] ) )
                    $apiSensor[ "latency" ] = $sensor[ "latency" ];

                $service[] = $apiSensor;
            }
        }

        if ( count( $service ) )
            $apiPart[ "service" ] = $service;

        return $apiPart;

        /*

        // Optional
        $apiPart["door_locked"] = [
            Array(
                "value"
                "location"
                //"name"
                //"description"
            )
        ];

        $apiPart["barometer"] = [
            Array(
                "value"
                "unit"
                "location"
                //"name"
                //"description"
            )
        ];

        $apiPart["radiation"] = [
            Array(
                //"alpha": []
                //"beta": []
                //"gamma": []
                //"beta_gamma": []
            )
        ];

        $apiPart["humidity"] = [
            Array(
                "value"
                "unit"
                "location"
                //"name"
                //"description"
            )
        ];

        $apiPart["beverage_supply"] = [
            Array(
                "value"
                "unit"
                //"location"
                //"name"
                //"description"
            )
        ];

        $apiPart["power_consumption"] = [
            Array(
                "value"
                "unit"
                "location"
                //"name"
                //"description"
            )
        ];

        $apiPart["wind"] = [
            Array(
                "properties"
                "location"
                //"name"
                //"description"
            )
        ];

        $apiPart["network_connections"] = [
            Array(
                //"type"
                "value"
                //"machines": []
                //"location"
                //"name"
                "description"
            )
        ];

        $apiPart["account_balance"] = [
            Array(
                "value"
                "unit"
                //"location"
                //"name"
                //"description"
            )
        ];

        $apiPart["total_member_count"] = [
            Array(
                "value"
                //"location"
                //"name"
                //"description"
            )
        ];

        $apiPart["people_now_present"] = [
            Array(
                "value"
                //"location"
                //"name"
                //"names" : []
                //"description"
            )
        ];

        // Custom
        $apiPart["ext_customtype"] = "http://";

        return $apiPart;
        */
    }

    public function updateDatabase( )
    {
        global $sensorAbstraction;

        //Gather the information to load into the database
        $arrAddress  = getVar( "address" );
        $arrLocation = getVar( "location" );
        $arrValue    = getVar( "value" );
        $arrType     = getVar( "type" );
        $arrUnit     = getVar( "unit" );

        $arrLat         = getVar( "lat" );
        $arrLon         = getVar( "lon" );
        $arrAccuracy    = getVar( "accuracy" );

        if ( !is_array( $arrAddress ) )
            $arrAddress = Array( $arrAddress );
        if ( !is_array( $arrLocation ) )
            $arrLocation = Array( $arrLocation );
        if ( !is_array( $arrValue ) )
            $arrValue = Array( $arrValue );
        if ( !is_array( $arrType ) )
            $arrType = Array( $arrType );
        if ( !is_array( $arrUnit ) )
            $arrUnit = Array( $arrUnit );

        if ( !is_array( $arrLat ) )
            $arrLat = Array( $arrLat );
        if ( !is_array( $arrLon ) )
            $arrLon = Array( $arrLon );
        if ( !is_array( $arrAccuracy ) )
            $arrAccuracy = Array( $arrAccuracy );

        $success = true;

        foreach ( $arrAddress as $idx => $address )
        {
            if ( !isset( $arrType[ $idx ] ) )
                continue;

            $type = $arrType[ $idx ];
            $unit = $arrUnit[ $idx ];

            // NOTE: we're supporting legacy temperature sensor(s) here; oficcially we need to set the unit to "°C" and type to "temperature"
            switch ( $type )
            {
                case "celcius": // legacy
                case "°C": // legacy
                    $unit = "celcius";
                    $type = "temperature";
                    break;
            }

            switch ( $type )
            {
                case "temperature":
                case "door_locked":
                case "barometer":
                case "humidity":
                case "beverage_supply":
                case "power_consumption":
                case "network_connections":
                    if ( !isset( $arrValue[ $idx ] ) )
                        continue;

                    if ( !$sensorAbstraction->updateSensor( $address, $arrValue[ $idx ], $unit, $type, $arrLocation[ $idx ] ) )
                        $success = false;
                    break;

                case "beacon":
                    if ( getVar( "debug" ) !== false )
                        print_r( "UPDATING BEACON\n".$arrLat[ $idx ]."##".$arrLon[ $idx ]."##".$arrAccuracy[ $idx ]."##\n" );

                    if ( !isset( $arrLat[ $idx ] ) )
                        continue;
                    if ( !isset( $arrLon[ $idx ] ) )
                        continue;
                    if ( !isset( $arrAccuracy[ $idx ] ) )
                        continue;

                    if ( !$sensorAbstraction->updateBeaconSensor( $arrLat[ $idx ], $arrLon[ $idx ], $arrAccuracy[ $idx ], null, null, null, null, $address ) )
                        $success = false;
                    break;

                case "service":
                    if ( !$this->handleSensors( $address ) )
                        $success = false;
                    break;

            }
        }

        if ( getVar( "debug" ) !== false )
            print_r( $_GET );

        // Update the values in the database
        return $success;
    }




/*
heartbeat every 5 minutes from 'address'

source address: spacenode, nlnode, denode
budgetphone=>1
ackspace=>1
    latency: 8920
voipbuster=>1
    latency: 16660
intervoip=>1
    latency: 16880
slackspace=>1
hackspace=>
stackspace=>

db:
service, source, status, latency, timestamp

extra insert:
source, source, true, latency, timestamp


       // address, budgetphone, voipbuster, cheapconnect, speakup, intervoip, ackspace, slackspace, hackspace, stackspace, intervoip_lag, voipbuster_lag, ackspace_lag

*/
    private function handleSensors( $_address )
    {
        global $sensorAbstraction;

        if ( getVar( "debug" ) !== false )
            print_r( "\nUPDATING SERVICE\n". $_address ."\n" );

        $success = true;

        // 'Fake' ourselves; since we can connect to the webserver, we can state we're online
        // Except for "annex service"
        if ( $_address !== "annex" && !$sensorAbstraction->updateServiceSensor( $_address, $_address, true, null ) )
            $success = false;

        // Fetch all (tri-state) boolean fields and look for a corresponding _lag field
        $requests = array_merge( $_GET, $_POST );
        foreach ( $requests as $service => $state )
        {
            // Skip debug field
            if ( $service === "debug" )
                continue;

            $state = strtolower( $state );

            if ( $state === "true" )
                $state = true;
            elseif ( $state === "false" )
                $state = false;
            elseif ( $state === "null" )
                $state = null;
            else
                continue;

            $latency = null;

            echo $service . "=>" . $state ."\n";

            if ( array_key_exists( $service."_lag", $requests ) )
                $latency = $requests[ $service."_lag" ];

            if ( !$sensorAbstraction->updateServiceSensor( $service, $_address, $state, $latency ) )
                $success = false;

        }

        return $success;
    }

}
?>