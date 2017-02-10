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

        $sensors = $sensorAbstraction->getTemperatureSensors();

        $apiPart = Array();
        // Mandatory


        // Temperature, Optional
        $temperature = Array();
        if ( $sensors && count( $sensors ) )
        {
            foreach ( $sensors as $sensor )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( $sensor );
    
                switch ( $sensor[ "unit" ] ) // TODO: type
                {
                    case "celcius":
                    case "°C":
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
                }
    
            }
        }
        if ( count( $temperature ) )
            $apiPart[ "temperature" ] = $temperature;

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
        $sensors = $sensorAbstraction->getBeaconSensors();
        // Beacon, Optional
        $beacon = Array();
        if ( $sensors && count( $sensors ) )
        {
            foreach ( $sensors as $sensor )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( $sensor );

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
                    "value"         => $sensor[ "value" ],
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
        $arrAddress = getVar( "address" );
        $arrValue   = getVar( "value" );
        $arrType    = getVar( "type" );

        $arrLat         = getVar( "lat" );
        $arrLon         = getVar( "lon" );
        $arrAccuracy    = getVar( "accuracy" );

        if ( !is_array( $arrAddress ) )
            $arrAddress = Array( $arrAddress );
        if ( !is_array( $arrValue ) )
            $arrValue = Array( $arrValue );
        if ( !is_array( $arrType ) )
            $arrType = Array( $arrType );

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

            switch ( $arrType[ $idx ] )
            {
                case "celcius":
                case "°C":
                    if ( !isset( $arrValue[ $idx ] ) )
                        continue;

                    if ( !$sensorAbstraction->updateTemperatureSensor( $address, $arrValue[ $idx ], $arrType[ $idx ] ) )
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

				case "audit":
                    if ( getVar( "debug" ) !== false )
                        print_r( "AUDIT\n".$arrAddress[ $idx ]."##".$arrValue[ $idx ]."##\n" );

                    if ( !isset( $arrAddress[ $idx ] ) )
                        continue;
                    if ( !isset( $arrValue[ $idx ] ) )
                        continue;

                    if ( !$sensorAbstraction->updateStock( $arrAddress[ $idx ], $arrValue[ $idx ], true ) )
                        $success = false;
                    break;

				case "correct":
                    if ( getVar( "debug" ) !== false )
                        print_r( "CORRECT\n".$arrAddress[ $idx ]."##".$arrValue[ $idx ]."##\n" );

                    if ( !isset( $arrAddress[ $idx ] ) )
                        continue;
                    if ( !isset( $arrValue[ $idx ] ) )
                        continue;

					// Correct (not audit)
                    if ( !$sensorAbstraction->updateStock( $arrAddress[ $idx ], $arrValue[ $idx ], false ) )
                        $success = false;
                    break;
            }
        }

        if ( getVar( "debug" ) !== false )
            print_r( $_GET );

        // Update the values in the database
        return $success;
    }
}
?>
