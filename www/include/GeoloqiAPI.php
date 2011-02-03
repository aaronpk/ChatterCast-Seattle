<?php 
class GeoloqiAPI
{
	private $_clientID;
	private $_clientSecret;
	private $_baseURL;
	private $_baseURLSecure;
	
	public function __construct($clientID, $clientSecret, $baseURL=FALSE, $baseURLSecure=FALSE)
	{
		$this->_clientID = $clientID;
		$this->_clientSecret = $clientSecret;
		
		if($baseURL)
			$this->_baseURL = $baseURL;
		else
			$this->_baseURL = GEOLOQI_API_BASEURL;
			
		if($baseURLSecure)
			$this->_baseURLSecure = $baseURLSecure;
		else
			$this->_baseURLSecure = GEOLOQI_API_BASEURL_SECURE;
			
	}
	
	public function request($method, $post=FALSE)
	{
		ob_start();
		echo '<pre>';
		
		$ch = curl_init();
		
		$httpHeader = array();

		// TODO: Change this timezone to the logged-in user's timezone
		$httpHeader[] = 'Timezone: ' . date('c') . ';;America/Los_Angeles';
		
		if(substr($method, 0, 5) == 'oauth' || substr($method, 0, 4) == 'user')
		{
			$client = array('client_id' => $this->_clientID, 'client_secret' => $this->_clientSecret);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, implode(':', $client));
			$baseURL = $this->_baseURL;
		}
		else
		{
			// Pass the OAuth token in the HTTP headers
			$httpHeader[] = 'Authorization: OAuth ' . $_SESSION['geoloqi_token'];
			
			$baseURL = $this->_baseURLSecure;
		}
		
		curl_setopt($ch, CURLOPT_URL, $baseURL . $method);
	
		if(is_array($post))
		{
			$post = http_build_query($post, '', '&');
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		elseif(is_string($post))
		{
			$httpHeader[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
		
		$response = curl_exec($ch);
	
		echo '<div style="background-color: #ffd; padding: 5px 0;">';
		echo "<b>REQUEST HEADERS:</b>\n";
		echo trim(curl_getinfo($ch, CURLINFO_HEADER_OUT)) . "\n\n";
		if($post)
		{
			echo "<b>REQUEST BODY:</b>\n";
			echo (is_array($post) ? http_build_query($post) : $post) . "\n\n";
		}
		echo '</div>';
		
		echo "<b>RESPONSE HEADERS:</b>\n";
		echo $response . "\n\n";
				
		$headers = array();
		$lines = explode("\n", $response);
		$endHeaders = FALSE;
		while($endHeaders == FALSE && count($lines) > 0)
		{
			$line = array_shift($lines);
			if(substr($line, 0, 1) == '{' || substr($line, 0, 1) == '[')
			{
				$endHeaders = TRUE;
				array_unshift($lines, $line);
			}
			else
			{
				$line = explode(': ', $line);
				if(count($line) == 2)
				{
					list($k, $v) = $line;
					$headers[trim($k)] = trim($v);
				}
			}
		}
	
		$body = implode("\n", $lines);
		
		$data = json_decode($body);
	
		echo "<b>JSON RESPONSE:</b>\n";
		
		if(is_object($data) && property_exists($data, 'debug_output'))
		{
			echo '<pre style="background-color:#eee; padding: 5px;">' . $data->debug_output . '</pre>';
			unset($data->debug_output);
		}	
		pa($data);
			
		if(array_key_exists('WWW-Authenticate', $headers) && preg_match('/error=\'expired_token\'/', $headers['WWW-Authenticate']))
		{
			// If the token expired, use the refresh token to get a new access token
			$response = $this->request('oauth/token', array(
				'grant_type' => 'refresh_token',
				'refresh_token' => $_SESSION['geoloqi_refresh_token']
			));
			
			// Store the tokens in the session
			$_SESSION['geoloqi_token'] = $response->access_token;
			$_SESSION['geoloqi_refresh_token'] = $response->refresh_token;
	
			echo "<b>SESSION</b>\n";
			pa($_SESSION);
			
			// Try the original request again
			// return $this->request($method, $post);
			die('ERROR');
			return FALSE;
		}
	
		echo "\n";

		echo '</pre>';
		$this->log(ob_get_clean());
		
		return $data;
	}
	
	protected function log($msg)
	{
		static $fp = FALSE;
		if($fp == FALSE)
			$fp = fopen('api-log.htm', 'w');
		fwrite($fp, $msg . "\n");
	}
}
?>