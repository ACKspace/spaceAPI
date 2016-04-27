<?php
class feeds
{
    public function updateSpaceApi( $_spaceAPI )
    {
        $apiPart = Array(
            "calendar" => Array(
                "type" => "text/calendar",
                "url" => "http://www.google.com/calendar/ical/f3j6egtm35u2v027rog3sob7gk%40group.calendar.google.com/public/basic.ics"
            )
        );

        return $apiPart;
    }
}
?>