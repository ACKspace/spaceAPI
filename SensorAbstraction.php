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
        
        $this->dbConn->set_charset( "utf8mb4" );
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
                print_r( "no connection\n" );

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
                print_r( "temperature: no result\n" );

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
                print_r( "no connection\n" );

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
                print_r( "Temperature no result:".$_name."\n" );

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
                print_r( "Beacon: no result\n" );

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
                print_r( "Beacon: no result:".$_name."\n" );

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


    //
    // Stock
    //
    public function getStock( )
    {
        if(is_null($this->dbConn))
        {
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

            // Database connection not initialised
            return null;
        }

        /* show the corrected amounts in single entity, respecting empty corrections, allowing visibility of all registered items, ignoring old corrections  */
        $dbResult = $this->dbConn->query("
            SELECT SUM( u.single_amount * s.amount ) + IF( c.last_changed >= MAX( s.date ), c.amount, 0 ) value, MIN( u.label ) unit, 'ACKspace' location, i.name
            FROM items i
            LEFT JOIN corrections c ON c.item_id=i.id
            LEFT JOIN stock s ON s.item_id=i.id
            LEFT JOIN units u ON s.unit_id=u.id
            WHERE s.id IN ( SELECT MAX( s.id ) FROM stock s GROUP BY s.item_id, s.unit_id, s.location_id )
            GROUP BY i.id
            LIMIT 0,100
        ");

        if( $dbResult->num_rows == 0 )
        {
            if ( getVar( "debug" ) !== false )
                print_r( "stock: no result\n" );

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

    public function updateStock( $_strBarcode, $_Value, $_bAudit, $_nLocation, $_strUser )
    {
        // determine if we already have the barcode stored in our system
        $stmt = $this->dbConn->prepare(
            "SELECT item_id
                FROM `ackspace_spaceAPI`.`barcodes` b
                WHERE b.barcode = ?
                LIMIT 1
            "
        );
    
        $num_rows = 0;

        if ( !$stmt )
        {
            if ( getVar( "debug" ) !== false )
                print_r( "Query error: barcodes\n" );
            return false;
        }
        else
        {
            $stmt->bind_param( "s", $_strBarcode );
            $stmt->execute( );

            /* store result */
            $stmt->store_result();
            $num_rows = $stmt->num_rows;

            // Close off previous statement
            $stmt->close( );
        }

        if ( getVar( "debug" ) !== false )
        {
            print_r( "num rows:" );
            var_dump( $num_rows );
        }

        // No result? add the barcode as "Unknown product", single unit (id 1)
        if( $num_rows == 0 )
        {

            $stmt = $this->dbConn->prepare(
                "INSERT INTO `ackspace_spaceAPI`.`items` (`name`) VALUES (?)"
            );

            if ( !$stmt )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( "Query error: items\n" );
                return false;
            }
            else
            {
                $product = "Unknown product";
                $stmt->bind_param( "s", $product );
                $stmt->execute( );

                // Close off previous statement
                $stmt->close( );
            }

            $stmt = $this->dbConn->prepare(
                "INSERT INTO `ackspace_spaceAPI`.`barcodes` ( barcode, item_id, unit_id )
                    VALUES ( ?, LAST_INSERT_ID(), 1 )
                "
            );

            if ( !$stmt )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( "Query error: barcodes last_insert_id\n" );
                return false;
            }
            else
            {
                $stmt->bind_param( "s", $_strBarcode );
                $stmt->execute( );

                // Close off previous statement
                $stmt->close( );
            }
        }

        // Actual audit/correction
        if ( $_bAudit )
        {
            /* audit stock */
            $stmt = $this->dbConn->prepare(
                "INSERT INTO `ackspace_spaceAPI`.`stock` (item_id, unit_id, location_id, amount, destination_id, user)
                    SELECT b.item_id, b.unit_id, ?, ?, ?, ?
                    FROM `ackspace_spaceAPI`.`barcodes` b
                    WHERE b.barcode = ?
                "
            );

            if ( !$stmt )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( "Query error: stock\n" );
                return false;
            }
            else
            {
                // Location id, amount, destination id, user, barcode
                $destination = null;
                $stmt->bind_param( "iiiss", $_nLocation, $_Value, $destination, $_strUser, $_strBarcode );
                $stmt->execute( );

                // Close off previous statement
                $stmt->close( );
            }

            /* remove current item's corrections */
            $stmt = $this->dbConn->prepare(
                "DELETE FROM c USING corrections c INNER JOIN barcodes b ON b.item_id=c.item_id WHERE b.barcode = ?"
            );

            if ( !$stmt )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( "Query error: clear corrections\n" );
                return false;
            }
            else
            {
                $stmt->bind_param( "s", $_strBarcode );
                $stmt->execute( );

                // Close off previous statement
                $stmt->close( );
            }
        }
        else
        {
            /* correct per barcode */
            /* TODO: ignore value before audit date */
            $stmt = $this->dbConn->prepare(
                "INSERT INTO `ackspace_spaceAPI`.`corrections` ( item_id, amount ) 
                SELECT b.item_id, -1
                FROM barcodes b
                LEFT JOIN units u ON u.id=b.unit_id
                WHERE b.barcode = ?
                ON DUPLICATE KEY UPDATE amount = amount - u.single_amount
                "
            );

            if ( !$stmt )
            {
                if ( getVar( "debug" ) !== false )
                    print_r( "Query error: corrections\n" );
                return false;
            }
            else
            {
                $stmt->bind_param( "s", $_strBarcode );
                $stmt->execute( );

                // Close off previous statement
                $stmt->close( );
            }
        }

        // Unknown
        //return null;

        // Succeeded
        return true;
    }

    //
    // Service
    //
    public function getServiceSensors( )
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
            `name`,
            `source`,
            `status`,
            `latency`,
            UNIX_TIMESTAMP(`updated`) AS `updated`
            FROM `services`
            WHERE id IN (SELECT MAX(`id`) FROM `services` GROUP BY name, source ORDER BY `id` DESC)
            LIMIT 0,100
        ");

        if( $dbResult->num_rows == 0 )
        {
            if ( getVar( "debug" ) !== false )
                print_r( "Service: no result\n" );

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

    public function getServiceSensorByName( $_name, $_source )
    {
        /*
        if(is_null($this->dbConn))
        {
            if ( getVar( "debug" ) !== false )
                print_r( "no connection" );

            // Database connection not initialised
            return null;
        }

        $dbResult = $this->dbConn->query("
            SELECT `id`,
            `name`,
            `source`,
            `status`,
            `latency`,
            UNIX_TIMESTAMP(`updated`) AS `updated`
            FROM `services`
            WHERE `name` = '".$_name."'
            AND `source` = '".$_source."'
            ORDER BY `id` DESC
            LIMIT 0,1
        ");

        if( $dbResult->num_rows == 0 )
        {
            if ( getVar( "debug" ) !== false )
                print_r( "Service: no result:".$_name."\n" );

            return null;
        }
        else
        {
            return $dbResult->fetch_assoc();
        }
        */

        return null;
    }
    
    public function updateServiceSensor( $_strName, $_strSource, $_bState, $_nLatency )
    {
        $sensorData = $this->getServiceSensorByName( $_strName, $_strSource );

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
                "INSERT INTO  `ackspace_spaceAPI`.`services` (
                    `name`,
                    `source`,
                    `status`,
                    `latency`,
                    `updated`
                )
                VALUES (
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
                $stmt->bind_param( "ssii", $_strName, $_strSource, $_bState, $_nLatency );
                $stmt->execute( );
            }

            return true;

        } else {
            // Change last updated time

            $q = "UPDATE `services` SET `name` = ".$_strName.",".
                                      "`source` = ".$_strSource.",".
                                      "`status` = ".$_bState.",".
                                      "`latency` = ".$_nLatency.",".
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
