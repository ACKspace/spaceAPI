<?php
/*
  Version : 1.1.1
  Author  : xopr
  Date    : 2016-11-05
  Changes : Cleaned. Renamed functions. Prepared for beacon sensor
*/

class SensorAbstraction
{
    private $dbConn = null;
	
	//
	// Public functions
	//
	public function init()
    {
        //if ( !function_exists( "mysqli_init" ) )
        if ( !extension_loaded( "mysqli" ) )
            return false;

        // Include database credentials
        include( $_SERVER['DOCUMENT_ROOT']."/../spaceAPI_config.php" );

        $this->dbConn = new mysqli($spaceApi_db_servername, $spaceApi_db_username, $spaceApi_db_password, $spaceApi_db_dbname);
		// Check connection
		if ($this->dbConn->connect_error) {
			return false;
		}
		
		return true;
	}
	
    //
    // Temperature
    //
	public function getTemperatureSensors( )
	{
		if(is_null($this->dbConn))
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

			// Database connection not initialised
			return null;
		}

		$dbResult = $this->dbConn->query("
			SELECT `id`,
            `value`,
            `unit`,
            `location`,
            `name`,
            `description`,
            `type`,
			UNIX_TIMESTAMP(`updated`) AS `updated`
			FROM `probes`
			ORDER BY `probes`.`id` DESC
			LIMIT 0,100
		");

		if( $dbResult->num_rows == 0 )
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no result" );

			return null;
		}
		else
		{
            $sensors = Array();
            while ( $sensor = $dbResult->fetch_assoc( ) )
            {
                $sensors[] = $sensor;
            }
			return $sensors;
		}

        // Failed
        return null;
	}

    public function getTemperatureSensorByName( $_name )
    {
		if(is_null($this->dbConn))
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

			// Database connection not initialised
			return null;
		}

		$dbResult = $this->dbConn->query("
			SELECT `id`,
            `value`,
            `unit`,
            `location`,
            `name`,
            `description`,
            `type`,
			UNIX_TIMESTAMP(`updated`) AS `updated`
			FROM `probes`
            WHERE `name` = '".$_name."'
			ORDER BY `probes`.`id` DESC
			LIMIT 0,1
		");

		if( $dbResult->num_rows == 0 )
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no result" );

			return null;
		}
		else
		{
			return $dbResult->fetch_assoc();
		}
    }
	
	public function updateTemperatureSensor( $_strAddress, $_nValue, $_strType )
	{
        $sensorData = $this->getTemperatureSensorByName( $_strAddress );

        if ( getVar( "debug" ) !== false )
        {
            print_r( "sensor data:" );
            print_r( $sensorData );
        }

        // Add the sensor data if we didn't find anything. Update otherwise.
        if ( $sensorData === null )
        {
		    $stmt = $this->dbConn->prepare(
			    "INSERT INTO  `ackspace_spaceAPI`.`probes` (
                    `name`,
				    `value`,
                    `unit`,
				    `updated`
			    )
			    VALUES (
				    ?,
				    ?,
                    ?,
				    CURRENT_TIMESTAMP
			    );"
		    );
		
		    if ( !$stmt )
		    {
			    return false;
		    }
		    else
		    {
			    $stmt->bind_param( "sds", $_strAddress, $_nValue, $_strType );
			    $stmt->execute( );
		    }

		    return true;

        } else {
			// Change last updated time
            $q = "UPDATE `probes` SET `value` = ".$_nValue.", `unit` = '".$_strType."', `updated` = CURRENT_TIMESTAMP WHERE `id` = ".$sensorData["id"]." LIMIT 1";
			$dbResult = $this->dbConn->query( $q );

            if ( getVar( "debug" ) !== false )
                print_r( $q );
			return $dbResult;
        }
        return null;
	}

    //
    // Beacon
    //
	public function getBeaconSensors( )
	{
		if(is_null($this->dbConn))
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

			// Database connection not initialised
			return null;
		}

		$dbResult = $this->dbConn->query("
			SELECT `id`,
            `lat`,
            `lon`,
            `accuracy`,
            `altitude`,
            `altitudeAccuracy`,
            `heading`,
            `speed`,
            `name`,
            `description`,
			UNIX_TIMESTAMP(`updated`) AS `updated`
			FROM `beacons`
			ORDER BY `beacons`.`id` DESC
			LIMIT 0,100
		");

		if( $dbResult->num_rows == 0 )
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no result" );

			return null;
		}
		else
		{
            $sensors = Array();
            while ( $sensor = $dbResult->fetch_assoc( ) )
            {
                $sensors[] = $sensor;
            }
			return $sensors;
		}

        // Failed
        return null;
	}

    public function getBeaconSensorByName( $_name )
    {
		if(is_null($this->dbConn))
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

			// Database connection not initialised
			return null;
		}

		$dbResult = $this->dbConn->query("
			SELECT `id`,
            `lat`,
            `lon`,
            `accuracy`,
            `altitude`,
            `altitudeAccuracy`,
            `heading`,
            `speed`,
            `name`,
            `description`,
			UNIX_TIMESTAMP(`updated`) AS `updated`
			FROM `beacons`
            WHERE `name` = '".$_name."'
			ORDER BY `beacons`.`id` DESC
			LIMIT 0,1
		");

		if( $dbResult->num_rows == 0 )
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no result:".$_name );

			return null;
		}
		else
		{
			return $dbResult->fetch_assoc();
		}
    }
	
	public function updateBeaconSensor( $_nLat, $_nLon, $_nAccuracy, $_nAltitude, $_nAltitudeAccuracy, $_nHeading, $_nSpeed, $_strName )
	{
        $sensorData = $this->getBeaconSensorByName( $_strName );

        if ( getVar( "debug" ) !== false )
        {
            print_r( "sensor data:" );
            print_r( $sensorData );
            print_r( "EOD" );
        }

        // Add the sensor data if we didn't find anything. Update otherwise.
        if ( $sensorData === null )
        {
		    $stmt = $this->dbConn->prepare(
			    "INSERT INTO  `ackspace_spaceAPI`.`beacons` (
                    `lat`,
                    `lon`,
                    `accuracy`,
                    `altitude`,
                    `altitudeAccuracy`,
                    `heading`,
                    `speed`,
                    `name`,
				    `updated`
			    )
			    VALUES (
				    ?,
				    ?,
				    ?,
				    ?,
                    ?,
				    ?,
				    ?,
                    ?,
				    CURRENT_TIMESTAMP
			    );"
		    );
		
		    if ( !$stmt )
		    {
			    return false;
		    }
		    else
		    {
			    $stmt->bind_param( "ddididds", $_nLat, $_nLon, $_nAccuracy, $_nAltitude, $_nAltitudeAccuracy, $_nHeading, $_nSpeed, $_strName );
			    $stmt->execute( );
		    }

		    return true;

        } else {
			// Change last updated time
            if ( !$_nAltitude )
                $_nAltitude = "NULL";
            if ( !$_nAltitudeAccuracy )
                $_nAltitudeAccuracy = "NULL";
            if ( !$_nHeading )
                $_nHeading = "NULL";
            if ( !$_nSpeed )
                $_nSpeed = "NULL";

            $q = "UPDATE `beacons` SET `lat` = ".$_nLat.",".
                                      "`lon` = ".$_nLon.",".
                                      "`accuracy` = ".$_nAccuracy.",".
                                      "`altitude` = ".$_nAltitude.",".
                                      "`altitudeAccuracy` = ".$_nAltitudeAccuracy.",".
                                      "`heading` = ".$_nHeading.",".
                                      "`speed` = ".$_nSpeed.",".
                                      "`name` = '".$_strName."',".
                                      "`updated` = CURRENT_TIMESTAMP WHERE `id` = ".$sensorData["id"]." LIMIT 1";
			$dbResult = $this->dbConn->query( $q );

            if ( getVar( "debug" ) !== false )
                print_r( $q );
			return $dbResult;
        }
        return null;
	}

}
?>
