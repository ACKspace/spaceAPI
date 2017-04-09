<!DOCTYPE html>
<html>
<head>
<title></title>
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="-1" />
<script type="text/javascript">
/*
    Quick hack that uses the (mobile) browser's geolocation API to update the
    beacon sensor (not part of the spaceAPI yet at the time of writing)
    Simply navigate to:
        geo.php?key=<API_KEY>&address=<SENSOR_ADDRESS>
    where <API_KEY> is the API key defined in index.php
    and <SENSOR_ADDRESS> is the name of the beacon sensor to set or update
*/
function window_load()
{
    if ( navigator.geolocation )
    {
        //navigator.geolocation.getCurrentPosition( message, message, { enableHighAccuracy : true } );
        var watchID = navigator.geolocation.watchPosition( message, message, { enableHighAccuracy : true } );
        //navigator.geolocation.clearWatch(watchID);
    }
    else
    {
        message( "Geolocation not supported" );
    }
}

function message( _message )
{
    var message;

    if ( typeof _message === "string" )
    {
        message = _message;
    }
    else
    {
        if ( _message.message )
        {
            message = _message.message;
        }
        else if ( _message.coords )
        {
            message = "Latitude: " + _message.coords.latitude +
                      "\nLongitude: " + _message.coords.longitude +
                      "\nAccuracy: " + _message.coords.accuracy +
                      "\nAltitude: " + _message.coords.altitude +
                      "\nAccuracy(alt): " + _message.coords.altitudeAccuracy +
                      "\nheading: " + _message.coords.heading +
                      "\nSpeed " + _message.coords.speed +
                      "\ntimestamp: " + new Date( _message.timestamp );

            //console.log( document.location.search );
            if ( !document.location.search )
                message += "\nadd ?key=API_KEY&address=SENSOR_ADDRESS";
            else
            {
                var xhr = new XMLHttpRequest();

                // Fetch (max number, excluding blank line) of lines (TODO: starting from timestamp)
                xhr.open( "GET", "/spaceAPI/" + document.location.search + "&update=sensors&type=beacon&lat=" + _message.coords.latitude + "&lon=" + _message.coords.longitude + "&accuracy=" + _message.coords.accuracy );
                xhr.onload = function()
                {
                    $("info").textContent += "\nok";
                }
                xhr.send( );
            }
        }
        else
        {
            message = "unknown message";
        }

        //console.log( typeof _message, _message );
    }

    $("info").textContent = message;
    //console.log( message );
}

function $( _o )
{
    return document.querySelector( "#" + _o );
}

window.addEventListener( "load", window_load, false );
</script>
</head>
<body>
<pre id="info">initializing..</pre>
</body>
</html>