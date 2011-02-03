<?php 
chdir(dirname(__FILE__));
include('../www/include/inc.php');

$url = 'http://data.seattle.gov/api/views/kzjm-xkqj/rows.json?max_rows=20';

$data = json_decode(file_get_contents($url));

$rows = $data->data;

foreach($rows as $row)
{
	$row_time = $row[10];
	
	// Ignore calls older than 2 hours ago (this shouldn't happen once the script starts running regularly)
#	if($row_time + 7200 < time())
#		continue;
	
	$check = $db->prepare('SELECT COUNT(1) AS num FROM seattle_911 WHERE uuid = :u');
	$check->bindParam(':u', $row[1]);
	$check->execute();
	$check = $check->fetch();
	if($check['num'] == 0)
	{
		$insert = $db->prepare('INSERT INTO seattle_911 (uuid, data) VALUES(:u, :d)');
		$insert->bindParam(':u', $row[1]);
		$insert->bindParam(':d', json_encode($row));
		$insert->execute();
		
		//irc_debug('[ChatterCast] Found new 911 call: ' . $row[9] . ' at ' . $row[8]);

		$query = $db->prepare('SELECT * FROM users WHERE instamapper_key != ""');
		$query->execute();
		foreach($query as $user)
		{
			$_SESSION['geoloqi_token'] = $user['geoloqi_access'];
			$_SESSION['geoloqi_refresh_token'] = $user['geoloqi_refresh'];
			
			$message = 'ChatterCast: ' . $row[9] . ' at ' . $row[8] . ' ' . date('g:ia', $row_time);
			echo 'Setting a geonote for ' . $user['username'] . ': ' . $message . ' expiring at ' . date('c', $row_time + 7200) . "\n";
			
			$response = $geoloqi->request('geonote/create', array(
				'text' => $message,
				'latitude' => $row[11],
				'longitude' => $row[12],
				'radius' => 400,
				'date_to' => date('c', $row_time + 7200)
			));
			print_r($response);
		}
						
	}
	
}

?>
