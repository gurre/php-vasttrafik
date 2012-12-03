<?php
/*
Version: 2.0.0

Copyright (c) 2012 Gustav Svalander, Göteborg, Sweden

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class Vasttrafik {

	const	API_BASEURL			= 'https://api.vasttrafik.se/bin/rest.exe',
			API_KEY				= '';

	const	REQUEST_TIMEOUT		= 3,				// seconds
			REQUEST_USER_AGENT	= 'php-vasttrafik',	//
			REQUEST_RETRIES		= 3,				// Careful, the request take up to 10 secs
			REQUEST_RETRY_SLEEP	= 3;				// Don't hammer, 3 seconds is reasonable

	const	EXCLUDE_VAS			= 1,	// Vasttågen
			EXCLUDE_LDTRAIN		= 2,	// Long Distance Trains
			EXCLUDE_REGTRAIN	= 4,	// Regional Trains
			EXCLUDE_BUS			= 8,
			EXCLUDE_BOAT		= 16,
			EXCLUDE_TRAM		= 32,
			EXCLUDE_DR			= 64;	// Doc: journeys which require tel. registration (?)



	public function __construct(){
		echo "This class is not intended to be instantiated. Use Vasttrafik::systemInfo()";
	}

	protected static function log($msg){
		error_log($msg); // implement your own logging here
	}

	protected static function request($pathquery, $retry=0){
		$host=parse_url(self::API_BASEURL,PHP_URL_HOST);
		// finalize request url, add key and json-formatting
		$finalurl=sprintf("%s%s&format=json&authKey=%s", self::API_BASEURL, $pathquery, self::API_KEY);
		$options=stream_context_create(
			array('http'=>
				array(
					'timeout' => self::REQUEST_TIMEOUT,
					'method'=>"GET",
					'header'=>
						"User-Agent: ".self::REQUEST_USER_AGENT."\r\n".
						"Host: ".$host
				)
			)
		);
		//echo "$finalurl\n"; // Great debugging
		$json=@file_get_contents($finalurl,false,$options);	// surpress error since it's too unpredictable.
		if($http_response_header[0]!='HTTP/1.1 200 OK' AND $http_response_header[0]!='HTTP/1.0 200 OK'){
			self::log('Request failed with \"'.$http_response_header[0]."\", retry:$retry, url:".addslashes($finalurl));

			if($retry<self::REQUEST_RETRIES){
				sleep(self::REQUEST_RETRY_SLEEP);	// Don't hammer
				return self::request($pathquery, ++$retry);
			}
		}
		$response=json_decode($json,true);
		if(json_last_error()!=JSON_ERROR_NONE){
			self::log('JSON invalid');
		}
		return $response;
	}

	public static function locationName($input){
		$query=array();
		$query['input']=$input;
		return self::request("/location.name?".http_build_query($query));
	}

	public static function locationAllstops(){
		return self::request("/location.allstops?");
	}

	public static function locationNearbyStops($lat, $lng, $maxNo=10, $maxDist=1000){
		$query=array();
		$query['originCoordLong']=$lat;
		$query['originCoordLat']=$lng;
		$query['maxNo']=$maxNo;
		$query['maxDist']=$maxDist;
		return self::request("/location.nearbystops?".http_build_query($query));
	}

	public static function trip(array $origin, array $dest, $date=null, $time=null, $excludeTrafficMask=0, $searchForArrival=0, $viaId=null, $needGeo=0){
		$query=array();
		if(isset($origin['id'])){
			$query['originId']=$origin['id'];
		}
		elseif(isset($origin['lat']) && isset($origin['long']) && isset($origin['name'])){
			$query['originCoordLat']=$origin['lat'];
			$query['originCoordLong']=$origin['long'];
			$query['originCoordName']=$origin['name'];
		}
		if(isset($dest['id'])){
			$query['destId']=$dest['id'];
		}
		elseif(isset($dest['lat']) && isset($dest['long']) && isset($dest['name'])){
			$query['destCoordLat']=$dest['lat'];
			$query['destCoordLong']=$dest['long'];
			$query['destCoordName']=$dest['name'];
		}
		$query['date']=$date==null?date('Y-m-d'):$date;
		$query['time']=$time==null?date('H:i'):$time;
		$query+=self::doNotUseTrafficWithMask($excludeTrafficMask);
		if($searchForArrival!=0){
			$query['searchForArrival']='1';
		}
		if($viaId!=null){
			$query['viaId']=$viaId;
		}
		$query['needGeo']=$needGeo==true?'1':'0';
		return self::request("/trip?".http_build_query($query));
	}

	public static function departureBoard($id, $date=null, $time=null, $excludeTrafficMask=0, $needJourneyDetail=0, $timeSpan=null, $maxDeparturesPerLine=2, $direction=null){
		$query=array();
		$query['id']=$id;
		$query['date']=$date==null?date('Y-m-d'):$date;
		$query['time']=$time==null?date('H:i'):$time;
		if($timeSpan!=null){
			$query['timeSpan']=$timeSpan;
			$query['maxDeparturesPerLine']=$maxDeparturesPerLine;
		}
		$query['needJourneyDetail']=$needJourneyDetail==true?'1':'0';
		$query['direction']=$direction;
		$query+=self::doNotUseTrafficWithMask($excludeTrafficMask);
		return self::request("/departureBoard?".http_build_query($query));
	}

	public static function arrivalBoard($id, $date, $time, $excludeTrafficMask=0, $timeSpan=1439, $maxDeparturesPerLine=2, $needJourneyDetail=0, $direction=null){
		$query=array();
		$query['id']=$id;
		$query['date']=$date;
		$query['time']=$time;
		if($timeSpan!=null){
			$query['timeSpan']=$timeSpan;
			$query['maxDeparturesPerLine']=$maxDeparturesPerLine;
		}
		$query['needJourneyDetail']=$needJourneyDetail==true?'1':'0';
		$query['direction']=$direction;
		$query+=self::doNotUseTrafficWithMask($excludeTrafficMask);
		return self::request("/arrivalBoard?".http_build_query($query));
	}

	public static function systemInfo(){
		return self::request("/systeminfo?");
	}

	protected static function doNotUseTrafficWithMask($mask){
		//printf("%b",$mask);
		$exclude=array();
		if((self::EXCLUDE_VAS & $mask) == true){
			$exclude['useVas']='0';
		}
		if((self::EXCLUDE_LDTRAIN & $mask) == true){
			$exclude['useLDTrain']='0';
		}
		if((self::EXCLUDE_REGTRAIN & $mask) == true){
			$exclude['useRegTrain']='0';
		}
		if((self::EXCLUDE_BUS & $mask) == true){
			$exclude['useBus']='0';
		}
		if((self::EXCLUDE_BOAT & $mask) == true){
			$exclude['useBoat']='0';
		}
		if((self::EXCLUDE_TRAM & $mask) == true){
			$exclude['useTram']='0';
		}
		if((self::EXCLUDE_DR & $mask) == true){
			$exclude['useTram']='0';
		}
		return $exclude;
	}
}