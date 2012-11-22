<?php
/*
Copyright (c) 2012 Gustav Svalander, Göteborg, Sweden

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/


/* Just some trivial tests. */
date_default_timezone_set("Europe/Dublin");
include "Vasttrafik.php";

$stops=Vasttrafik::locationNearbyStops(11.974624, 57.700570);



if(isset($stops['LocationList']) && isset($stops['LocationList']['StopLocation']) && count($stops['LocationList']['StopLocation'])>0){
	echo "[  OK  ] Got ".count($stops['LocationList']['StopLocation'])." near (11.974624, 57.700570). In 2012 there are 10 stops nearby.\n";

	$mask=Vasttrafik::EXCLUDE_BUS|Vasttrafik::EXCLUDE_BOAT;
	$deptBoard=Vasttrafik::departureBoard($stops['LocationList']['StopLocation'][0]['id'], date('Y-m-d'), date('H:i'), $mask);
	//print_r($deptBoard);
	if(isset($deptBoard['DepartureBoard']) && isset($deptBoard['DepartureBoard']['Departure'])){
		echo "[  OK  ] Got some departures for ".$stops['LocationList']['StopLocation'][0]['name'].".\n";
	}


}
print_r(Vasttrafik::systemInfo());

