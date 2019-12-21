<?php

// original API endpoint
$base_url = "https://swapi.co/";

// cache proxy api end point
$sub_folder = "\/swproxy\/";
$cache_url = "https://xyz.com".$sub_folder;


// flag for gzip compression
$response_gzip = False;

// get IP address for logging
function get_ip_address() {
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
    }
}

// get timestamp string for logging
function getDatetimeNow() {
    $tz_object = new DateTimeZone('America/Detroit');

    $t = microtime(true);
    $micro = sprintf("%06d",($t - floor($t)) * 1000000);
    $d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
    $d->setTimezone($tz_object);

    return $d->format("Y-m-d H:i:s.u");
}

// wrapper function for getting both response content and header
function getUrl($url) {
    $content = file_get_contents($url);
    return array(
        'headers' => $http_response_header,
        'content' => $content
    );
}

// limiting to only http get request
if( $_SERVER['REQUEST_METHOD'] != 'GET' ){
    echo "Not the right method: ". $_SERVER['REQUEST_METHOD'];
    exit();
}

$request = parse_url($_SERVER['REQUEST_URI']);
$path = $request["path"];
$sub_path =  str_replace($sub_folder, '', $path);
$params_string = $_SERVER['QUERY_STRING'];

parse_str($params_string, $params_array);

// if value is case-insensitive for a certain key, such as "search"
/*
if(strlen($params_array["search"])> 0 ){
    $params_array["search"] = strtolower($params_array["search"]);
}
*/

$params_string = http_build_query($params_array);

// all the / will be replaced as _ for file name
$new_sub_path = str_replace("/", '_', $sub_path);
$new_params_string = $params_string;


$hash_file_name = "";

if(strlen($new_params_string) > 0){
    $hash_file_name = $new_sub_path."!".$new_params_string.".json";
}
else{
    $hash_file_name = $new_sub_path.".json";
}


$hash_file_path = "./json/".$hash_file_name;

$new_hash_file_path = $hash_file_path;

// method 1
$does_file_exist = file_exists($new_hash_file_path);

// Get last modification time of the current PHP file
$file_last_mod_time = filemtime(__FILE__);

if($does_file_exist){
    error_log("--\n"."IP: ".get_ip_address()."\n"."URL: ".$path."?".$params_string."\n"."File: yes\n"."Time: ".getDatetimeNow()."\n", 3, "./tmp/my-errors.log");

    header('Content-type:application/json;charset=utf-8');
    header('Cache-Control: max-age=3600');
    $file_content_string = file_get_contents($new_hash_file_path);


    // Get last modification time of the main content (that user sees)
    // Hardcoded just as an example
    $content_last_mod_time = filemtime($new_hash_file_path);

    // Combine both to generate a unique ETag for a unique content
    // Specification says ETag should be specified within double quotes
    $etag = '"' . $file_last_mod_time . '.' . $content_last_mod_time . '"';
    header('ETag: ' . $etag);

    // Check whether browser had sent a HTTP_IF_NONE_MATCH request header
    if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        // If HTTP_IF_NONE_MATCH is same as the generated ETag => content is the same as browser cache
        // So send a 304 Not Modified response header and exit
        if($_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
            header('HTTP/1.1 304 Not Modified', true, 304);
            exit();
        }
    }

    if( !$response_gzip ){
        echo $file_content_string;
    }
    else{
        $gzipoutput = gzencode($file_content_string,6);
        header('Content-Encoding: gzip'); #
        header('Content-Length: '.strlen($gzipoutput));
        echo $gzipoutput;
    }

    flush();
}
else{
    // file does not exist
    error_log("--\n"."IP: ".get_ip_address()."\n"."URL: ".$path."?".$params_string."\n"."File: no\n"."Time: ".getDatetimeNow()."\n", 3, "./tmp/my-errors.log");
    // query first
    $query_url = $base_url.$sub_path;
    if( strlen($params_string) > 0){
        $query_url = $base_url.$sub_path."?".$params_string;
    }
    $get_response = "";

    $response = getUrl($query_url);

    if ($response['content'] === FALSE){
        $get_response =  $response['headers'][0];   // HTTP/1.1 401 Unauthorized
        echo $get_response;
        flush();
    }
    else{
        $get_response =  $response['content'];
        header('Content-type:application/json;charset=utf-8');
        header('Cache-Control: max-age=3600');

        $new_response =  str_replace($base_url, $cache_url, $get_response);

        $bytes_written = file_put_contents($new_hash_file_path, $new_response);

        // Get last modification time of the main content (that user sees)
        // Hardcoded just as an example
        $content_last_mod_time = filemtime($new_hash_file_path);

        // Combine both to generate a unique ETag for a unique content
        // Specification says ETag should be specified within double quotes
        $etag = '"' . $file_last_mod_time . '.' . $content_last_mod_time . '"';
        header('ETag: ' . $etag);

        if( !$response_gzip ){
            echo $new_response;
        }
        else{
            // method 2
            //ini_set('zlib.output_compression','Off');
            $gzipoutput = gzencode($new_response,9);
            header('Content-Encoding: gzip'); #
            header('Content-Length: '.strlen($gzipoutput));
            echo $gzipoutput;

        }

        flush();s
    }
}
