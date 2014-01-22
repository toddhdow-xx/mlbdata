<?php
// **** POTENTIAL CONFIGURATION STARTS HERE ****

// MODIFY: Insert your own consumer key and secret here!
$consumer_data = array();
$consumer_data['test']['key'] = 'dj0yJmk9bjFhUmtMNE5oQlV6JmQ9WVdrOVNHTmlSMXBqTm1jbWNHbzlNakF5T0RBek1qWXkmcz1jb25zdW1lcnNlY3JldCZ4PTVh';
$consumer_data['test']['secret'] = '2fe4cd85ea2bc3ff81d2725d243d8e63c38d5b5f';

// **** HELPER FUNCTIONS START HERE ****

///////////////////////////////////////////////////////////////////////////////
//  FUNCTION _make_signed_request
/// @brief Helper function to make a signed OAuth request. Only allows GET 
///        requests at the moment. Will add on standard OAuth params, but
///        you may need to fill in non-generic ones ahead of time.
///
/// @param[in]  $consumer_key      Application consumer key
/// @param[in]  $consumer_secret   Application consumer secret
/// @param[in]  $token             Token (request or access token)
/// @param[in]  $token_secret      Token secret
/// @param[in]  $signature_method  'PLAINTEXT' or 'HMAC-SHA1'
/// @param[in]  $url               URL to make request to
/// @param[in]  $params            Array of key=>val for params. Don't
///                                urlencode ahead of time, we'll do that here.
///////////////////////////////////////////////////////////////////////////////
function _make_signed_request( $consumer_key, $consumer_secret, $token, $token_secret, $signature_method, $url, $params = array() ) {

  // Only support GET in this function
  $method = 'GET';

  $signature_method = strtoupper( $signature_method );
  if( $signature_method != 'PLAINTEXT' && $signature_method != 'HMAC-SHA1' ) {
    print "Invalid signature method: ${signature_method}\n";
    return false;
  }

  $oauth_nonce = rand( 0, 999999 );
  $oauth_timestamp = time();
  $oauth_version = '1.0';

  $params['oauth_consumer_key'] = $consumer_key;
  $params['oauth_nonce'] = $oauth_nonce;
  $params['oauth_signature_method'] = $signature_method;
  $params['oauth_timestamp'] = $oauth_timestamp;
  $params['oauth_version'] = $oauth_version;

  if( $token ) {
    $params['oauth_token'] = $token;
  }
  if( ! $token_secret ) {
    $token_secret = '';
  }
  
  // Params need to be sorted by key
  ksort( $params, SORT_STRING );

  // Urlencode params and generate param string
  $param_list = array();
  foreach( $params as $key => $value ) {
    $param_list[] = urlencode( $key ) . '=' . urlencode( $value );
  }
  $param_string = join( '&', $param_list );
  
  // Generate base string (needed for SHA1)
  $base_string = urlencode( $method ) . '&' . urlencode( $url ) . '&' . 
    urlencode( $param_string );

  // Generate secret
  $secret = urlencode( $consumer_secret ) . '&' . urlencode( $token_secret );
  if( $signature_method == 'PLAINTEXT' ) {
    $signature = $secret;
  } else if( $signature_method == 'HMAC-SHA1' ) {
    $signature = base64_encode( hash_hmac( 'sha1', $base_string, $secret, true ) );
  }
  
  // Append signature
  $param_string .= '&oauth_signature=' . urlencode( $signature );
  $final_url = $url . '?' . $param_string;

  // Make curl call
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, $final_url );
  curl_setopt( $ch, CURLOPT_AUTOREFERER, 1 );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );

  $timeout = 2; // seconds
  curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
  curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
  
  $contents = curl_exec($ch);
  $ret_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
  $errno = curl_errno($ch);
  $error_str = curl_error($ch);

  if( $errno || $error_str ) {
    //print "Error: ${error_str} (${errno})\n";
  }

  //print "Response code: ${ret_code}\n";
  //print "Contents:\n${contents}\n\n";
 
  curl_close($ch);
 
  $data = array(
    'return_code' => $ret_code,
    'contents'    => $contents,
    'error_str'   => $error_str,
    'errno'       => $errno 
  );

  return $data;
}

///////////////////////////////////////////////////////////////////////////////
//  FUNCTION oauth_response_to_array
/// @brief Break up the oauth response data into an associate array
///////////////////////////////////////////////////////////////////////////////
function oauth_response_to_array( $response ) {
  $data = array();
  foreach( explode( '&', $response ) as $param ) {
    $parts = explode( '=', $param );
    if( count( $parts ) == 2 ) {
      $data[urldecode($parts[0])] = urldecode($parts[1]);
    }
  }
  return $data;
}

///////////////////////////////////////////////////////////////////////////////
//  FUNCTION get_request_token
/// @brief Get a request token for a given application.
///////////////////////////////////////////////////////////////////////////////
function get_request_token( $consumer_key, $consumer_secret ) {

  $url = 'https://api.login.yahoo.com/oauth/v2/get_request_token';
  $signature_method = 'plaintext';

  $token = NULL;
  $token_secret = NULL;

  // Add in the lang pref and callback
  $xoauth_lang_pref = 'en-us';
  $oauth_callback = 'oob';  // Set OOB for ease of use -- could be a URL
  
  $params = array( 'xoauth_lang_pref' => $xoauth_lang_pref,
                   'oauth_callback'   => $oauth_callback );

  // Make the signed request without any token
  $response_data = _make_signed_request( $consumer_key, $consumer_secret, $token, $token_secret, $signature_method, $url, $params );

  if( $response_data && $response_data['return_code'] == 200 ) {

    $contents = $response_data['contents'];
    $data = oauth_response_to_array( $contents );

    //print_r( $data );

    return $data;
  }

  return false;
}

///////////////////////////////////////////////////////////////////////////////
//  FUNCTION get_access_token
/// @brief Get an access token for a certain user and a certain application,
///        based on the request token and verifier
///////////////////////////////////////////////////////////////////////////////
function get_access_token( $consumer_key, $consumer_secret, $request_token, $request_token_secret, $verifier ) {

  $url = 'https://api.login.yahoo.com/oauth/v2/get_token';
  $signature_method = 'plaintext';

  // Add in the oauth verifier
  $params = array( 'oauth_verifier' => $verifier );

  // Make the signed request using the request_token data
  $response_data = _make_signed_request( $consumer_key, $consumer_secret, $request_token, $request_token_secret, $signature_method, $url, $params );
  
  if( $response_data && $response_data['return_code'] == 200 ) {

    $contents = $response_data['contents'];
    $data = oauth_response_to_array( $contents );

    //print_r( $data );

    return $data;
  }

  return false;
}


///////////////////////////////////////////////////////////////////////////////
//  FUNCTION make_request
/// @brief Make an actual request to the fantasy API.
///////////////////////////////////////////////////////////////////////////////
function make_request( $consumer_key, $consumer_secret, $access_token, $access_token_secret, $url ) {

  $signature_method = 'hmac-sha1';
  
  // Make the signed request to fantasy API
  $response_data = _make_signed_request( $consumer_key, $consumer_secret, $access_token, $access_token_secret, $signature_method, $url );

  return $response_data;
}


// **** MAIN PROGRAM STARTS HERE ****

$consumer_key = $consumer_data['test']['key'];
$consumer_secret = $consumer_data['test']['secret'];

// 1. Get Request Token
$request_token_data = get_request_token( $consumer_key, $consumer_secret );

if( ! $request_token_data ) {
  print "Could not retrieve request token data\n";
  exit;
}

$request_token = $request_token_data['oauth_token'];
$request_token_secret = $request_token_data['oauth_token_secret'];
$auth_url = $request_token_data['xoauth_request_auth_url'];

// 2. Direct user to Yahoo! for authorization (retrieve verifier)
print "Hey! Go to this URL and tell us the verifier you get at the end.\n";
print ' ' . $auth_url . "\n\n";

print "Type the verifier and hit enter...\n";
$verifier = fgets( STDIN );

print "Here's the verifier you gave us: ${verifier}\n";

// 3. Get Access Token
$access_token_data =
  get_access_token( $consumer_key, $consumer_secret, $request_token, $request_token_secret, $verifier );

if( ! $access_token_data ) {
  print "Could not get access token\n";
  exit;
}

$access_token = $access_token_data['oauth_token'];
$access_token_secret = $access_token_data['oauth_token_secret'];

// 4. Make request using Access Token
$base_url = 'http://fantasysports.yahooapis.com/';
if( isset( $argv[1] ) ) {
  $request_uri = $argv[1];
} else {
  $request_uri = 'fantasy/v2/game/nfl';
}
$request_url = $base_url . $request_uri;

print "Making request for ${request_url}...\n";

$request_data = make_request( $consumer_key, $consumer_secret, $access_token, $access_token_secret, $request_url );

if( ! $request_data ) {
  print "Request failed\n";  
}

$return_code = $request_data['return_code'];
$contents = $request_data['contents'];

print "Return code: ${return_code}\n";
print "Contents:\n${contents}\n\n";

print "Successful\n";

?>

        