<?php
class StateAbstraction
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

        $this->dbConn->set_charset( "utf8mb4" );
        return true;
    }

    public function getState()
    {
		$fallBack = $array = array(
			"state" => "2",
			"updated" => "0",
			"created" => "0"
		);

		if(is_null($this->dbConn))
		{
			// Database connection not initialised
			return $fallBack;
		}

        // No external input: no need to prepare statement
		$dbResult = $this->dbConn->query("
			SELECT `state`,
			UNIX_TIMESTAMP(`updated`) AS `updated`,
			UNIX_TIMESTAMP(`created`) AS `created`
			FROM `statechanges`
			ORDER BY `statechanges`.`id` DESC
			LIMIT 0,1
		");

		if($dbResult->num_rows == 0)
		{
			return $fallBack;
		}
		else
		{
			$stateInfo = $dbResult->fetch_assoc();

			//check if timed out
			if((time() - $stateInfo['updated']) > 360)
			{
                // Set last known timestamp
                $fallBack['created'] = $stateInfo['updated'];
                $fallBack['updated'] = $stateInfo['updated'];
				return $fallBack;
			}
			else
			{
				return $stateInfo;
			}
		}
	}

    public function getStateDescription( $_nState )
    {
		if(is_null($this->dbConn))
		{
			// Database connection not initialised
			return null;
		}

        // Input validation
		if( $_nState < 0 )
		    $_nState = 2 + $_nState;

		if( $_nState < 0 || $_nState > 2 || !is_numeric( $_nState ))
		{
			return false;
		}

        // Prepare statement for input validation
	    $stmt = $this->dbConn->prepare(
			"SELECT description,
            `to` IS NULL AS empty
            FROM `statedescriptions`
            WHERE state = ?
            AND `from` <= NOW()
            AND (`to` IS NULL OR `to` >= NOW() )
            ORDER BY `empty` ASC, `to` ASC
            LIMIT 0,1"
		);

	    if ( !$stmt )
	    {
		    return false;
	    }
	    else
	    {
		    $stmt->bind_param( "i", $_nState );
		    $stmt->execute( );
            $stmt->store_result( );
	    }

		if($stmt->num_rows == 0)
		{
			return null;
		}

        $stmt->bind_result( $description, $empty );
        $stmt->fetch();

		return $description;
    }

	public function updateState( $_nNewState )
	{
	    // Handle -1 and -2 as 'permanent state', make sure the timestamp updates with every update, and the state resets after a 'normal' updatestate

		// Input validation
		$nNewStateOrg = (int)$_nNewState;
		if( $_nNewState < 0 )
			$_nNewState = 2 + $_nNewState;

		// Input validation
		if( $_nNewState < 0 || $_nNewState > 1 || !is_numeric( $_nNewState ))
			return false;

		$nCurrentState = $this->getRawState()['state'];
		$nCurrentStateOrg = (int)$nCurrentState;
		if( $nCurrentState < 0 )
			$nCurrentState = 2 + $nCurrentState;

		// Update if the new state matches the current state or if the current state is 'permanent', does NOT match the new state
		if( ($nCurrentStateOrg == $nNewStateOrg) || ($nCurrentStateOrg < 0 && $nCurrentState != $_nNewState) )
		{
			// Change last updated time
			// No external input: no need to prepare statement
			$dbResult = $this->dbConn->query("UPDATE `statechanges` SET  `updated` = CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1");
			return $dbResult;
		}
		else
		{
			// Set new state
			return $this->setState( $nNewStateOrg );
		}
	}

	//
	// Private functions
	//
	private function setState( $_nState )
	{
		if(is_null($this->dbConn))
		{
			// Database connection not initialised
			return false;
		}

        // Input validation
		if( $_nState < -2 || $_nState > 1 || !is_numeric( $_nState ))
			return false;

		$stmt = $this->dbConn->prepare(
			"INSERT INTO  `ackspace_spaceAPI`.`statechanges` (
				`id` ,
				`state` ,
				`updated` ,
				`created`
			)
			VALUES (
				NULL ,
				?,
				CURRENT_TIMESTAMP ,
				CURRENT_TIMESTAMP
			);"
		);

		if (!$stmt)
		{
			return false;
		}
		else
		{
			$stmt->bind_param( "i", $_nState );

			$stmt->execute();
		}

		return true;
	}

	private function getRawState()
	{
		$fallBack = $array = array(
			"state" => "2",
			"updated" => "0",
			"created" => "0"
		);

		if(is_null($this->dbConn))
		{
			// Database connection not initialised
			return $fallBack;
		}

        // No external input: no need to prepare statement
		$dbResult = $this->dbConn->query("
			SELECT `state`,
			UNIX_TIMESTAMP(`updated`) AS `updated`,
			UNIX_TIMESTAMP(`created`) AS `created`
			FROM `statechanges`
			ORDER BY `statechanges`.`id` DESC
			LIMIT 0,1
		");

		if($dbResult->num_rows == 0)
		{
			return $fallBack;
		}
		else
		{
			$stateInfo = $dbResult->fetch_assoc();

			return $stateInfo;
		}
	}
}
?>
