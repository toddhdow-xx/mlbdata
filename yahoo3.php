<?php

// **** POTENTIAL CONFIGURATION STARTS HERE ****

// MODIFY: Insert your own consumer key and secret here!
$consumer_data = array();
$consumer_data['test']['key'] = 'dj0yJmk9bjFhUmtMNE5oQlV6JmQ9WVdrOVNHTmlSMXBqTm1jbWNHbzlNakF5T0RBek1qWXkmcz1jb25zdW1lcnNlY3JldCZ4PTVh';
$consumer_data['test']['secret'] = '2fe4cd85ea2bc3ff81d2725d243d8e63c38d5b5f';


///////////////////////////////////////////////////////////////////////////////
//  FUNCTION make_write_request
/// @brief Make a write (PUT/POST) request
///
/// @param[out] $auth_failure     Sets variable to true on 401 HTTP code (auth)
/// @param[in]  $consumer_key     Application consumer key
/// @param[in]  $consumer_secret  Application consumer secret
/// @param[in]  $access_token     Access token for user/application
/// @param[in]  $access_secret    Access token secret for user/application
/// @param[in]  $method           PUT or POST
/// @param[in]  $url              URL to PUT/POST against
/// @param[in]  $infile           Filename specifiying data to PUT/POST
///////////////////////////////////////////////////////////////////////////////
function make_write_request( &$auth_failure, $consumer_key, $consumer_secret, $access_token, $access_secret, $method, $url, $infile ) {

  // Make sure we can open the infile
  $in_fh = NULL;
  if( file_exists( $infile ) &&
      $in_fh = fopen( $infile, 'r' ) ) {

    $input_data = '';
    while( $line = fgets( $in_fh ) ) {
      $input_data .= $line;
    }
    
  } else {
    print "Cannot open infile: ${infile}\n";
    return false;
  }

  $auth_failure = false;
  $response_success = false;

  $oauth_consumer_key = $consumer_key;
  $oauth_consumer_secret = $consumer_secret;
  $oauth_token = $access_token;
  $oauth_token_secret = $access_secret;

  $oauth_signature_method = 'HMAC-SHA1';
  $oauth_nonce = rand( 0, 999999 );
  $oauth_timestamp = time();
  $oauth_version = "1.0";

  $params = array(
    'oauth_consumer_key'     => $oauth_consumer_key,
    'oauth_nonce'            => $oauth_nonce,
    'oauth_signature_method' => $oauth_signature_method,
    'oauth_timestamp'        => $oauth_timestamp,
    'oauth_token'            => $oauth_token,
    'oauth_version'          => $oauth_version,
  );

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

  // Generate secret and signature
  $secret = urlencode( $consumer_secret ) . '&' . urlencode( $oauth_token_secret );
  $signature = 
    base64_encode( hash_hmac( 'sha1', $base_string, $secret, true ) );

  // Append signature
  $final_url = $url . '?' . $param_string . '&oauth_signature=' . urlencode( $signature );

  // Make the curl call
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/xml' ) );
  if( $method == 'POST' ) { 
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $input_data );
  } else if( $method == 'PUT' ) {
    
    fseek( $in_fh, 0 );
    
    curl_setopt( $ch, CURLOPT_PUT, 1 );
    curl_setopt( $ch, CURLOPT_INFILE, $in_fh );
    curl_setopt( $ch, CURLOPT_INFILESIZE, strlen( $input_data ) );
    
  }
  curl_setopt( $ch, CURLOPT_URL, $final_url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  
  $ycw_result = curl_exec( $ch );
  $ret_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

  fclose( $in_fh );

  curl_close( $ch );

  if( $ret_code == 401 ) {
    $auth_failure = true;
  } else {
    $response_success = true;
  }

  print "Return code: ${ret_code}\n";
  print "Response from API:\n";
  print_r( $ycw_result );

  return $response_success;
}
?>

        