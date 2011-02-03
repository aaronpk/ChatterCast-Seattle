<?php 
include('include/inc.php');

include('header.php');

$twitter = new Twitter();
$url = $twitter->oauth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
echo '<div style="width: 800px; margin: 0 auto;"><a href="' . $url . '"><img src="/images/1-ChatterCast-Index.png" width="800" height="600" border="0" /></a></div>';

include('footer.php');

?>