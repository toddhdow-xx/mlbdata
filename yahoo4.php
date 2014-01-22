<?php
$consumer_key = '<INSERT CONSUMER KEY HERE>';  
$consumer_secret = '<INSERT CONSUMER SECRET HERE>';  

$consumer_key = 'dj0yJmk9bjFhUmtMNE5oQlV6JmQ9WVdrOVNHTmlSMXBqTm1jbWNHbzlNakF5T0RBek1qWXkmcz1jb25zdW1lcnNlY3JldCZ4PTVh';
$consumer_secret = '2fe4cd85ea2bc3ff81d2725d243d8e63c38d5b5f';

$o = new OAuth( $consumer_key, $consumer_secret, 
                OAUTH_SIG_METHOD_HMACSHA1,
                OAUTH_AUTH_TYPE_URI );

$url = 'http://fantasysports.yahooapis.com/fantasy/v2/game/mlb';

try {
  if( $o->fetch( $url ) ) {
  
    print $o->getLastResponse();
    
    print "Successful fetch\n";
  } else {
    print "Couldn't fetch\n";
  }
} catch( OAuthException $e ) {
  print 'Error: ' . $e->getMessage() . "\n";
  print 'Response: ' . $e->lastResponse . "\n";

}
?>     
