<?php
    if ( !extension_loaded( "mysqli" ) )
        die( '{ "error" : "no mysqli extension available" }' );

    // Include database credentials
    include( $_SERVER['DOCUMENT_ROOT']."/../spaceAPI_config.php" );

    $dbConn = new mysqli($spaceApi_db_servername, $spaceApi_db_username, $spaceApi_db_password, $spaceApi_db_dbname);
    // Check connection
    if ($dbConn->connect_error)
    {
        die( '{ "error" : "database connection error" }' );
    }

    $month = getVar( "month" );

    if ( getVar( "debug" ) !== false )
    {
        echo "<pre>debug\n";

        print_r( $month );
    }

    if ( $month === false )
    {
        $stmt = $dbConn->prepare(
            "SELECT state, UNIX_TIMESTAMP( created ), UNIX_TIMESTAMP( updated )
            FROM `ackspace_spaceAPI`.`statechanges`
            WHERE (DATE( created ) <= NOW() AND DATE( created ) >= DATE_SUB( NOW(), INTERVAL 7 DAY))
            OR (DATE( updated ) <= NOW() AND DATE( updated ) >= DATE_SUB( NOW(), INTERVAL 7 DAY))"
        );

        if ( !$stmt )
            die( '{ "error" : "database query error" }' );
    }
    else
    {
        $monthEnd = $month + 1;
        $stmt = $dbConn->prepare(
            "SELECT state, UNIX_TIMESTAMP( created ), UNIX_TIMESTAMP( updated )
            FROM `ackspace_spaceAPI`.`statechanges`
            WHERE (DATE( created ) <= DATE_SUB( NOW(), INTERVAL ? MONTH) AND DATE( created ) >= DATE_SUB( NOW(), INTERVAL ? MONTH))
            OR    (DATE( updated ) <= DATE_SUB( NOW(), INTERVAL ? MONTH) AND DATE( updated ) >= DATE_SUB( NOW(), INTERVAL ? MONTH))"
        );

        if ( !$stmt )
            die( '{ "error" : "database query error" }' );

        $stmt->bind_param( "iiii", $month, $monthEnd, $month, $monthEnd );
    }

    $stmt->execute( );
    $stmt->store_result( );

    //if($stmt->num_rows == 0)
    $stmt->bind_result( $state, $created, $updated );

    $states = Array();
    while ( $stmt->fetch() )
    {
        $states[] = Array( "state" => $state, "created" => $created, "updated" => $updated );
    }

    echo json_encode( $states );

    if ( getVar( "debug" ) !== false )
    {
        print_r( $states );
        echo "</pre>";
    }

    exit;



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

echo <<< EOF
[
	{
		"state" : 1,
		"created" : "2016-08-25 12:46:45",
		"wc" : 3,
		"tc" : "12:46:45",
		"updated" : "2016-08-25 13:48:50",
		"wu" : 3,
		"tu" : "13:48:50"
	}, {
		"state" : 0,
		"created" : "2016-08-25 13:49:03",
		"wc" : 3,
		"tc" : "13:49:03",
		"updated" : "2016-08-26 10:37:51",
		"wu" : 4,
		"tu" : "10:37:51"
	}, {
		"state" : 1,
		"created" : "2016-08-26 10:37:52",
		"wc" : 4,
		"tc" : "10:37:52",
		"updated" : "2016-08-26 17:54:01",
		"wu" : 4,
		"tu" : "17:54:01"
	}, {
		"state" : 0,
		"created" : "2016-08-26 17:54:09",
		"wc" : 4,
		"tc" : "17:54:09",
		"updated" : "2016-08-27 07:38:33",
		"wu" : 5,
		"tu" : "07:38:33"
	}, {
		"state" : 1,
		"created" : "2016-08-27 07:38:46",
		"wc" : 5,
		"tc" : "07:38:46",
		"updated" : "2016-08-27 14:51:56",
		"wu" : 5,
		"tu" : "14:51:56"
	}, {
		"state" : 0,
		"created" : "2016-08-27 14:51:59",
		"wc" : 5,
		"tc" : "14:51:59",
		"updated" : "2016-08-28 07:38:39",
		"wu" : 6,
		"tu" : "07:38:39"
	}, {
		"state" : 1,
		"created" : "2016-08-28 07:38:49",
		"wc" : 6,
		"tc" : "07:38:49",
		"updated" : "2016-08-28 16:10:58",
		"wu" : 6,
		"tu" : "16:10:58"
	}, {
		"state" : 0,
		"created" : "2016-08-28 16:11:11",
		"wc" : 6,
		"tc" : "16:11:11",
		"updated" : "2016-08-29 08:49:23",
		"wu" : 0,
		"tu" : "08:49:23"
	}, {
		"state" : 1,
		"created" : "2016-08-29 08:49:32",
		"wc" : 0,
		"tc" : "08:49:32",
		"updated" : "2016-08-29 19:25:41",
		"wu" : 0,
		"tu" : "19:25:41"
	}, {
		"state" : 0,
		"created" : "2016-08-29 19:25:53",
		"wc" : 0,
		"tc" : "19:25:53",
		"updated" : "2016-08-30 11:24:38",
		"wu" : 1,
		"tu" : "11:24:38"
	}, {
		"state" : 1,
		"created" : "2016-08-30 11:24:47",
		"wc" : 1,
		"tc" : "11:24:47",
		"updated" : "2016-08-30 11:24:47",
		"wu" : 1,
		"tu" : "11:24:47"
	}, {
		"state" : 0,
		"created" : "2016-08-30 11:25:00",
		"wc" : 1,
		"tc" : "11:25:00",
		"updated" : "2016-08-30 11:29:42",
		"wu" : 1,
		"tu" : "11:29:42"
	}, {
		"state" : 1,
		"created" : "2016-08-30 11:29:48",
		"wc" : 1,
		"tc" : "11:29:48",
		"updated" : "2016-08-30 12:32:55",
		"wu" : 1,
		"tu" : "12:32:55"
	}, {
		"state" : 0,
		"created" : "2016-08-30 12:33:06",
		"wc" : 1,
		"tc" : "12:33:06",
		"updated" : "2016-08-30 13:00:21",
		"wu" : 1,
		"tu" : "13:00:21"
	}, {
		"state" : 1,
		"created" : "2016-08-30 13:00:27",
		"wc" : 1,
		"tc" : "13:00:27",
		"updated" : "2016-08-30 21:04:11",
		"wu" : 1,
		"tu" : "21:04:11"
	}, {
		"state" : 0,
		"created" : "2016-08-30 21:04:17",
		"wc" : 1,
		"tc" : "21:04:17",
		"updated" : "2016-08-31 02:50:31",
		"wu" : 2,
		"tu" : "02:50:31"
	}
]
EOF
?>