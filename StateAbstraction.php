<?php
class StateAbstraction
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
	public function init()
    {
        //if ( !function_exists( "mysqli_init" ) )
        if ( !extension_loaded( "mysqli" ) )
            return false;

        $this->dbConn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
		// Check connection
		if ($this->dbConn->connect_error) {
			return false;
		}
		
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
			if((time() - $stateInfo['updated']) > 60)
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
		if( $_nState < 0 || $_nState > 1 || !is_numeric( $_nState ))
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
            if ( getVar( "debug" ) !== false )
                print_r( "no state description result" );
			return null;
		}

        $stmt->bind_result( $description, $empty );
        $stmt->fetch();

        if ( getVar( "debug" ) !== false )
            print_r( $description );
		return $description;
    }

	public function updateState( $_nState )
	{
        // Input validation
		if( $_nState < 0 || $_nState > 1 || !is_numeric( $_nState ))
		{
			return false;
		}
		
		$currentState = $this->getRawState();
		
		if($currentState['state'] == $_nState)
		{
			// Change last updated time
            // No external input: no need to prepare statement
			$dbResult = $this->dbConn->query("UPDATE `statechanges` SET  `updated` = CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1");
			return $dbResult;
		}
		else
		{
			// Set new state
			return $this->setState( $_nState );
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
		if( $_nState < 0 || $_nState > 1 || !is_numeric( $_nState ))
		{
			return false;
		}

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
