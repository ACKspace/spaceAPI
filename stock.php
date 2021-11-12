<?php
include( $_SERVER['DOCUMENT_ROOT']."/../spaceAPI_config.php" );

// Send headers immediately
header( "Access-Control-Allow-Origin: *" );
header( "Access-Control-Allow-Methods: GET" );
header( "Content-Type: application/json" );
header( 'Content-Type: text/javascript; charset="UTF-8"' );
//header( "Content-Type: text/html; charset=utf-8" );

$type = getVar( "type" );
$arrBarcode = getVar( "barcode" );
if ( !is_array( $arrBarcode ) )
    $arrBarcode = Array( $arrBarcode );

switch ( $type )
{
    case "product":
        echo generateProductInfo( $arrBarcode );
        break;

    default:
        break;
}

function generateProductInfo( $_arrBarcode )
{
    global $spaceApi_db_servername, $spaceApi_db_username, $spaceApi_db_password, $spaceApi_db_dbname;

    //if ( !function_exists( "mysqli_init" ) )
    if ( !extension_loaded( "mysqli" ) )
        return '{ "error": "mysqli extension not loaded" }';

    $dbConn = new mysqli($spaceApi_db_servername, $spaceApi_db_username, $spaceApi_db_password, $spaceApi_db_dbname);
    // Check connection
    if ($dbConn->connect_error)
    {
        return '{ "error": "could not connect to database" }';
    }

    // Escape the barcodes for the database
    foreach ( $_arrBarcode as $key => $value )
    {
        $_arrBarcode[ $key ] = $dbConn->real_escape_string( $value );
    }

    $strBarcodes = join( ',', $_arrBarcode );

    $stmt = $dbConn->prepare(
        "SELECT name, image, barcode, label, single_amount FROM `items` i
         LEFT JOIN `barcodes` b ON i.id = b.item_id
         LEFT JOIN `units` u ON u.id = b.unit_id
         WHERE b.barcode IN (?)
        "
    );

    if ( !$stmt )
        return '{ "error": "query error" }';


    $stmt->bind_param( "s", $strBarcodes );
    $stmt->execute( );
    $stmt->store_result();

    $products = Array();

    $stmt->bind_result( $name, $image, $barcode, $unit, $amount );

    while ( $data = $stmt->fetch( ) )
    {
        $product = Array();
        $product[ "image" ] = $image;
        $product[ "name" ] = $name;
        $product[ "barcode" ] = $barcode;
        $product[ "unit" ] = $unit;
        $product[ "amount" ] = $amount;
        $products[] = $product;
    }

/*
    // For each barcode, generate a product information list in json
    // Name, image, price,
    foreach ( $_arrBarcode as $idx => $barcode )
    {
        $product = Array();
        $product[ "image" ] = "unknown.png";
        $product[ "name" ] = "unknown product";
        $product[ "barcode" ] = $barcode;

        $products[] = $product;
        //echo '{ "image": "unknown.png", "name": "Unknown product", "barcode": ' . $barcode . "}\n";
    }
*/

    return json_encode( $products );
}




function getVar( $_name, $_bGet = true, $_bPost = true, $_bSession = false )
{
    if ( $_bGet && array_key_exists( $_name, $_GET ) )
      return $_GET[ $_name ];

    if ( $_bPost && array_key_exists( $_name, $_POST ) )
      return $_POST[ $_name ];

    if ( $_bSession && array_key_exists( $_name, $_SESSION ) )
      return $_SESSION[ $_name ];

    return false;
}

?>
