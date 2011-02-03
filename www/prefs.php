<?php 
include('include/inc.php');

force_login();

include('header.php');

$query = $db->prepare('SELECT * FROM users WHERE username = :username');		
$query->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
$query->execute();
$user = $query->fetch();

if(strtolower($_SERVER['REQUEST_METHOD']) == 'get')
{
?>
<div style="width: 350px; margin: 0 auto; margin-top: 200px;">

<div style="margin-bottom: 20px;">Need help? <a href="http://geoloqi.com/blog/2010/08/how-do-i-get-my-instamapper-device-and-api-key/" target="_blank">Get your Instamapper API key</a></div>

<form action="prefs.php" method="post">
<table style="width:500px">
	<tr>
		<td style="width:180px;">Phone Number</td>
		<td><input type="text" name="phone" value="<?=$user['phone']?>" style="width: 160px;" /></td>
	</tr>
	<tr>
		<td>Email</td>
		<td><input type="text" name="email" value="<?=$user['email']?>" style="width: 160px;" /></td>
	</tr>
	<tr>
		<td>Instamapper API Key (20 digits)</td>
		<td><input type="text" id="instamapper_key" name="instamapper_key" value="<?=$user['instamapper_key']?>" style="width: 160px;" /><span id="key_error" style="font-size: 10px"></span></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="image" src="/images/Save.png" border="0" width="160" height="82" /></td>
	</tr>
</table>
</form>
</div>


	<div style="width: 606px; margin: 0 auto;">
		<a href="http://www.instamapper.com/fe?page=api" target="_blank"><img src="http://geoloqi.com/blog/wp-content/uploads/2010/08/3.5-instamapper-api-access.jpg" width="606" height="277" /></a>
	</div>

<script type="text/javascript">
$(function(){
	$("#instamapper_key").keyup(function(){
		if($(this).val().length < 16){
			$(this).css({color: "red"});
			$("#key_error").html("Too short!");
		}else{
			$(this).css({color: "black"});
			$("#key_error").html("Ok!");
		}
	});
});
</script>

<?php 
}
else
{
	$query = $db->prepare('UPDATE users SET email = :email, phone = :phone, instamapper_key = :instamapper_key WHERE username = :username');
	foreach(array('phone', 'email', 'instamapper_key') as $p)
		$query->bindParam(':' . $p, $_POST[$p], PDO::PARAM_STR);
	$query->bindParam(':username', $_SESSION['username'], PDO::PARAM_STR);
	$query->execute();

	$user = $db->prepare('SELECT * FROM users WHERE username = :username');
	$user->bindParam(':username', $_SESSION['username']);
	$user->execute();
	$user = $user->fetch();
	$_SESSION['geoloqi_token'] = $user['geoloqi_access'];

	$geoloqi_response = $geoloqi->request('account/set_phone', array('phone' => $_POST['phone']));
	
	irc('[ChatterCast] ' . $_SESSION['username'] . ' Instamapper Key: ' . $_POST['instamapper_key'] . ' Phone: ' . $_POST['phone'] . ' Email: ' . $_POST['email']);
	
	echo '<div style="width: 800px; margin: 0 auto;"><img src="/images/5-ChatterCast-Success.png" width="800" height="600" border="0" /></div>';
}

include('footer.php');
?>