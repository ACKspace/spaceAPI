<?php
/*
  Version : 1.1.1
  Author  : xopr
  Date    : 2015-07-06
  Changes : Added SensorAbstraction
*/

class sensors
{
    public function updateSpaceApi( $_spaceAPI )
    {
        global $sensorAbstraction;

        $sensors = $sensorAbstraction->getSensors();

        // If we don't have (valid) sensor data, bail out
        if ( !$sensors || !count( $sensors ) )
          return null;

        $apiPart = Array();
        // Nothing mandatory

        // Optional
        $temperature = Array();
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

        if ( count( $temperature ) )
            $apiPart[ "temperature" ] = $temperature;

        return $apiPart;

        /*
        $apiPart = Array();
        // Mandatory

        // Optional
        $apiPart["temperature"] = [
            Array(
                "value"
                "unit"
                "location"
                //"name"
                //"description"
            )
        ];

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
        $arrAddress = getVar( "address" );
        $arrValue   = getVar( "value" );
        $arrType    = getVar( "type" );

        if ( !is_array( $arrAddress ) )
            $arrAddress = [ $arrAddress ];
        if ( !is_array( $arrValue ) )
            $arrValue = [ $arrValue ];
        if ( !is_array( $arrType ) )
            $arrType = [ $arrType ];

        $success = true;

        foreach ( $arrAddress as $idx => $address )
        {
            if ( !isset( $arrValue[ $idx ] ) )
                continue;

            if ( !isset( $arrType[ $idx ] ) )
                continue;

            if ( !$sensorAbstraction->updateSensor( $address, $arrValue[ $idx ], $arrType[ $idx ] ) )
                $success = false;
        }

        if ( getVar( "debug" ) !== false )
            print_r( $_GET );

        // Update the values in the database
        return $success;
    }
}
?>