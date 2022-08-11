<?php
class spacefed
{
    public function updateSpaceApi( $_spaceAPI )
    {
        $apiPart = Array();
        // Mandatory
        $apiPart["spacenet"] = true;
        $apiPart["spacesaml"] = false;
        $apiPart["spacephone"] = true;

        // Optional

        // Custom
        $apiPart["ext_spacephone_extension"] = 31979922;
        $apiPart["ext_spacenet5g"] = true;

        return $apiPart;
    }
}
?>