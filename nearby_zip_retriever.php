<?php
  /*array containing Google Maps API keys.
  Replace "XXXXXXXXXXXXXXXXXXXX" with the actual keys.
  */
  $geocode_keys = array("XXXXXXXXXXXXXXXXXXXX","XXXXXXXXXXXXXXXXXXXX","XXXXXXXXXXXXXXXXXXXX");
  $geocode_length = sizeof($geocode_keys)-1;
  $rand_geocode = rand(0,$geocode_length);

  /*
  function to return the latitude and longitude of a place at a distance of '$k' meters and angle '$t'
  This is an implementation of the Haversine Formula.
  */
  function getLatLong($lat1,$long1,$k,$t){
    $r = 6371e3;
    $lat1 = deg2rad($lat1);
    $long1 = deg2rad($long1);
    $d = $k/$r;
    $lat2 = asin(sin(($lat1)*cos($d)) + (cos($lat1)*sin($d)*cos($t)));
    $long2 = $long1 + atan2((sin($t)*sin($d)*cos($lat1)),(cos($d)-sin($lat1)*$lat2));
    $lat2 = rad2deg($lat2);
    $long2 = rad2deg($long2);
    return array('lat'=>$lat2,'lng'=>$long2);
  }

  /*
  main implementation function that retrieves the zip codes
  $k is the distance from location in kilometers
  $lat and $lng is the initial latitude and longitude
  */
  function getZips($k, $lat, $lng){
    global $geocode_keys,$rand_geocode;
    $t = 0; $i = 0;$k *= 1000;
    $location = array('lat' => $lat, 'lng' => $lng
    //$t is the theta angle that the algorithm searches for the zip
    while($t<2*M_PI){
        $points[$i] = getLatLong($location['lat'],$location['lng'],$k,$t);
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?key='.$geocode_keys[$rand_geocode].'&latlng=';
        $url.=$points[$i]['lat'].",".$points[$i]['lng'];
        $content = file_get_contents($url);
        $json = json_decode($content, true);
        $res_zip = parseZip($json['results'][0]['address_components']);
        $zip[$i] = $res_zip;
        $i++;
        // theta ($t) is updated
        // A total of 8 locations are searched - (2*M_PI)/(M_PI/4) = 8
        $t += (M_PI/4);
    }
    //cleansing the output data for unique zip codes
    $zip = array_unique($zip);
    return $zip;
  }


  //function to return latitude & longitude of placeID
  function pIDtolatlng($placeid){
    global $geocode_keys,$rand_geocode;
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?key='.$geocode_keys[$rand_geocode].'&place_id='.$placeid;
    $content = file_get_contents($url);
    $json = json_decode($content, true);
    return array('lat' => $json['results'][0]['geometry']['location']['lat'], 'lng' => $json['results'][0]['geometry']['location']['lng']);
  }

  //function to return address of placeID
  function pIDtoAddress($placeid){
    global $geocode_keys,$rand_geocode;
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?key='.$geocode_keys[$rand_geocode].'&place_id='.$placeid;
    $content = file_get_contents($url);
    $json = json_decode($content, true);
    return $json['results'][0]['formatted_address'];
  }

    //function to retrieve the zip code from JSON data
    function parseZip($address_components){
      $outer_array_size = sizeof($address_components);
        for($i = 0; $i<$outer_array_size;$i++){
            if($address_components[$i]['types'][0] == "postal_code"){
              return $address_components[$i]['long_name'];
            }
        }
    }

    //function to return distance and duration between 2 different placeIDs
    function get_dst_dur($pID1, $pID2){
      $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=place_id:$pID1&destinations=place_id:$pID2&key=AIzaSyAlaH_SoNbTVX8ifshAqSf2cGxUGqgTssI";
      $content = file_get_contents($url);
      $json = json_decode($content, true);
      $distance = $json['rows'][0]['elements'][0]['distance']['text'];
      $duration = $json['rows'][0]['elements'][0]['duration']['text'];
      return array('distance'=>$distance, 'duration'=>$duration);
    }
