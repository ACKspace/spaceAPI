<?php
// NOTE: this is a stub!
//       to be replaced with working version

class SensorAbstraction
{
	// Database settings
	private $servername = "<SERVER>";
	private $username = "<DB_USER>";
	private $password = "<DB_PASS>";
	private $dbname = "<DB_NAME>";

    private $dbConn = null;
	
	//
	// Public functions
	//
	public function init( )
    {
        $this->dbConn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
		// Check connection
		if ($this->dbConn->connect_error) {
			return false;
		}
		
		return true;
	}
	
	public function getSensors( )
	{
        // No external input: no need to prepare statement
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
	}

    public function getSensorByName( $_name )
    {
		if(is_null($this->dbConn))
		{
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

			// Database connection not initialised
			return null;
		}

        // Prepare statement for input validation
	    $stmt = $this->dbConn->prepare(
		    "SELECT `id`,
            `value`,
            `unit`,
            `location`,
            `name`,
            `description`,
            `type`,
			UNIX_TIMESTAMP(`updated`) AS `updated`
			FROM `probes`
            WHERE `name` = ?
			ORDER BY `probes`.`id` DESC
			LIMIT 0,1"
	    );

	    if ( !$stmt )
	    {
		    return false;
	    }
	    else
	    {
		    $stmt->bind_param( "s", $_name );
		    $stmt->execute( );
	    }

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
	
	public function updateSensor( $_strAddress, $_nValue, $_strType )
	{
        $sensorData = $this->getSensorByName( $_strAddress );

        if ( getVar( "debug" ) !== false )
        {
            print_r( "sensor data:" );
            print_r( $sensorData );
        }

        // Add the sensor data if we didn't find anything. Update otherwise.
        if ( $sensorData === null )
        {
		    $stmt = $this->dbConn->prepare(
			    "INSERT INTO `ackspace_spaceAPI`.`probes` (
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
    	    $stmt = $this->dbConn->prepare(
                "UPDATE `probes` SET
                `value` = ?,
                `unit` = ?,
                `updated` = CURRENT_TIMESTAMP
                WHERE `id` = ?
                LIMIT 1";
    	    );

    	    if ( !$stmt )
    	    {
    		    return false;
    	    }
    	    else
    	    {
    		    $stmt->bind_param( "ssd", $_nValue, $_strType, $sensorData["id"] );
    		    $stmt->execute( );
    	    }

            if ( getVar( "debug" ) !== false )
                print_r( $q );
			return $dbResult;
        }
	}
}
?>
