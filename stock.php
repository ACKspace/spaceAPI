<?php

class StockAbstraction
{
    public function test()
    {
        // Fetch config file just outside of our html root
        include( $_SERVER['DOCUMENT_ROOT']."/../spaceAPI_config.php" );

        echo "const: ".SPACEAPI_TEST_CONST."<br/>\n";
        echo "variable: ".$spaceApi_testVar."<br/>\n";
	}
}

//echo getcwd()."<br/>\n";
//echo $_SERVER['DOCUMENT_ROOT']."<br/>\n";
//echo realpath($_SERVER['DOCUMENT_ROOT'].'/../')."<br/>\n";

$stockAbstraction = new StockAbstraction();

$stockAbstraction->test();

/* correct barcodes[] or audit barcodes[] amounts[] username */
$action = getVar( "action" );
$arrBarcode = getVar( "barcode" );
$arrAmount = getVar( "amount" );
if ( !is_array( $arrBarcode ) )
    $arrBarcode = Array( $arrBarcode );

if ( !is_array( $arrAmount ) )
    $arrAmount = Array( $arrAmount );

echo $action . " !";
switch ( $action )
{
    case "audit":
        echo "auditing";
        break;

    case false:
    default:
        echo "correct";
        break;
}

foreach ( $arrBarcode as $idx => $barcode )
{
    if ( !isset( $arrAmount[ $idx ] ) )
        $arrAmount[ $idx ] = 1;

    echo $barcode . " " . $arrAmount[ $idx ]."<br/>\n";
}

/*


*/

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

?>
