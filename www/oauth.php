<?php 
include('include/inc.php');

$twitter = new Twitter();

$token = $twitter->oauth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
$_SESSION['access_token'] = $token['access_token'];
$_SESSION['access_token_secret'] = $token['access_token_secret'];

#$connection->oauth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $token['access_token'], $token['access_token_secret']);

$obj = $twitter->call('account/verify_credentials');

$_SESSION['username'] = $obj->screen_name;


$query = $db->prepare('SELECT COUNT(1) AS num FROM users WHERE username = :username');		
$query->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
$query->execute();
$row = $query->fetch();

if($row['num'] == 0)
{
	// new user! insert a row
	$geoloqi_response = $geoloqi->request('user/create', array('username' => 'chattercast_' . $_SESSION['username'], 'email' => 'chattercast.' . $_SESSION['username'] . '@pin13.net'));
	
	$query = $db->prepare('INSERT INTO users (username, twitter_token, twitter_secret, date_created, geoloqi_access, geoloqi_refresh) VALUES(:u, :t, :s, :d, :ga, :gr)');
	$query->bindParam(':u', $_SESSION['username'], PDO::PARAM_STR);
	$query->bindParam(':t', $_SESSION['access_token'], PDO::PARAM_STR);
	$query->bindParam(':s', $_SESSION['access_token_secret'], PDO::PARAM_STR);
	$query->bindParam(':d', date('Y-m-d H:i:s'), PDO::PARAM_STR);
	$query->bindParam(':ga', $geoloqi_response->access_token, PDO::PARAM_STR);
	$query->bindParam(':gr', $geoloqi_response->refresh_token, PDO::PARAM_STR);
	$query->execute();
	
	irc('New ChatterCast user! ' . $_SESSION['username']);
}
else
{
	$user = $db->prepare('SELECT * FROM users WHERE username = :username');
	$user->bindParam(':username', $_SESSION['username']);
	$user->execute();
	$user = $user->fetch();
	irc('ChatterCast user ' . $_SESSION['username'] . ' logged back in');
	$_SESSION['geoloqi_token'] = $user['geoloqi_access'];
}

header('Location: /prefs.php');

?>