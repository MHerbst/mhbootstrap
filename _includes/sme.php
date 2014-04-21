<?php 
function smeDebug($type, $ch, $data)
{
	$handle = fopen("sme.log","a");
	fwrite($handle, ">>".$type."<<\n");
	fwrite($handle, "CURL: ".curl_error($ch));
	fwrite($handle, "DATA: ".$data);
	fwrite($handle, "\n");
	fclose($handle);
}

function getSocialData($url, $doit, $debug = false)
{
	$connectionTimeout   = 3; // set your desired connection timeout for external API calls
	$apcKey = "__MH_SME".$url;
	$counter = apc_fetch($apcKey);
	if (!is_array($counter)) 
	{
		$counter = array(
				'go' => 0,
				'fb' => 0,
				'tw' => 0,
				'li' => 0,
				'xi' => 0,
				'pi' => 0,
				'sum' => 0
		);
		
	}
	else
	{ 
		return $counter;
	}
	if ($doit['go'])
	{
		// Google
		// see: http://code.google.com/intl/de-AT/apis/+1button/
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'https://clients6.google.com/rpc',
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url. '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
			CURLOPT_RETURNTRANSFER => 3,
			CURLOPT_TIMEOUT        => $connectionTimeout,
			CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
		));
		$rawData = curl_exec($ch);
		if ($debug) smeDebug("Google", $ch, $rawData);
		curl_close($ch);
		
		if($plusOneData = json_decode($rawData, true)) {
			$counter['go'] = intval($plusOneData[0]['result']['metadata']['globalCounts']['count']);
		} else {
			$counter['go'] = intval(0);
		}
	}

	if ($doit['tw'])
	{
		// Twitter
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'http://urls.api.twitter.com/1/urls/count.json?url=' . $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => $connectionTimeout,
			CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
		));
		$rawData = curl_exec($ch);
		if ($debug) smeDebug("Twitter", $ch, $rawData);
		curl_close($ch);
		if($twitterData = json_decode($rawData)) {
			$counter['tw'] = intval($twitterData->count);
		} else {
			$counter['tw'] = intval(0);
		}
	}
	
	if ($doit['fb'])
	{
		// Facebook
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'http://graph.facebook.com/?ids=' . $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => $connectionTimeout,
			CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
		));
		$rawData = curl_exec($ch);
		if ($debug) smeDebug("Facebook", $ch, $rawData);
		curl_close($ch);
		if($facebookData = json_decode($rawData, true)) {
			$counter['fb'] = intval($facebookData[$permalinkUrl]['shares']);
		} else {
			$counter['fb'] = intval(0);
		}
	}

	if ($doit['li'])
	{
		// LinkedIn
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'http://www.linkedin.com/countserv/count/share?url=' . $url . '&amp;format=json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => $connectionTimeout,
			CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
		));
		$rawData = curl_exec($ch);
		if ($debug) smeDebug("Linkedin", $ch, $rawData);
		curl_close($ch);
		
		if($linkedinData = json_decode($rawData, true)) {
			$counter['li'] = intval($linkedinData['count']);
		} else {
			$counter['li']['count'] = intval(0);
		}
	}
	if ($doit['pi'])
	{	
		// PInterest
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'http://api.pinterest.com/v1/urls/count.json?url=' . $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => $connectionTimeout,
			CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
		));
		$rawData = curl_exec($ch);
		if ($debug) smeDebug("Pinterest", $ch, $rawData);
		curl_close($ch);
		$counter['pi'] = intval(0);
		if (preg_match("/^.*\((.*)\)/", $rawData, $matches) === 1) // bereinigen
		{
			if($pinterestData = json_decode($matches[1], true)) 
			{
				$counter['pi'] = intval($pinterestData['count']);
			}
		} 
	}
	
	if ($doit['xi'])
	{	
		//XING
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => 'https://www.xing-share.com/app/share?op=get_share_button;url=' . urlencode($url). ';counter=right;lang=de;type=iframe;hovercard_position=1;shape=square',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
		));
		$rawData = curl_exec($ch);
		if ($debug) smeDebug("Xing", $ch, $rawData);
		curl_close($ch);
		//Find the interesting part to strip
		preg_match("'<span class=\"xing-count right\">(.*?)</span>'si", $rawData, $matches);
		
		//To make sure there is a count for that site
		if( isset( $matches ) ) {
			$counter['xi'] = intval($matches[1]);
		} else {
			$counter['xi'] = intval(0);
		}
	}
	apc_store($apcKey, $counter, 3600); // 1 Stunde speichern
	return $counter;
}
$templ = Koken::$location['template'];
$doit = array('go' => true, 'fb' => true, 'tw' => true, "li" => false, "xi" => false, "pi" => true, "ki" => false);
if ($templ === "essay")
{
	$doit['pi'] = false; $doit['ki'] = true;
}
if ($templ === "content")
{
	$url = str_replace(":slug",Koken::$routed_variables['slug'], Koken::$location['site_url'].Koken::$location['urls']['content']);
}
else if ($templ == "album")
{
	$url = str_replace(":slug",Koken::$routed_variables['slug'], Koken::$location['site_url'].Koken::$location['urls']['album']);
}
else
{
	$url = Koken::$location['site_url'].Koken::$location['here'];
}
$url_enc = urlencode($url);
$ctr = getSocialData($url, $doit, true);
?>
<section class="smeShare">
<?php if ($doit['go']) {?>
	<a href="https://plus.google.com/share?url=<?php echo $url;?>" class="smeFlatGoogle" rel="sme nofollow" data-service="google">
		<i></i>Google+<span><?php echo $ctr['go'];?></span>
	</a>
<?php } 
  if ($doit['fb']) {?>
	<a href="http://www.facebook.com/sharer.php?u=<?php echo $url;?>&amp;t={{ content.title | content.caption url_encode="true" }}" class="smeFlatFacebook" rel="sme nofollow" data-service="facebook">
		<i></i>Facebook<span><?php echo $ctr['fb'];?></span>
	</a>
<?php } 
  if ($doit['tw']) {?>
	<a href="http://twitter.com/intent/tweet?text={{ content.title | content.caption url_encode="true" }}&url=<?php echo $url;?>&related={{ profile.twitter }}&via={{ profile.twitter }}&lang=de" class="smeFlatTwitter" rel="sme nofollow" data-service="twitter">
		<i></i>Twitter<span><?php echo $ctr['tw'];?></span>
	</a>
<?php } 
  if ($doit['li']) {?>
	<a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo $url_enc;?>&amp;title={{ content.title | content.caption url_encode="true" }}&amp;summary=" class="smeFlatLinkedin" rel="sme nofollow" data-service="linkedin">
		<i></i>LinkedIn<span><?php echo $ctr['li'];?></span>
	</a>
<?php } 
  if ($doit['xi']) {?>
	<a href="https://www.xing-share.com/app/user?op=share;sc_p=xing-share;url=<?php echo $url_enc;?>" class="smeFlatXing" rel="sme nofollow" data-service="xing">
		<i></i>XING<span><?php echo $ctr['xi'];?></span>
	</a>
<?php } 
  if ($doit['pi']) {?>
	<a href="http://pinterest.com/pin/create/button/?url=<?php echo $url_enc;?>&amp;media={{ content.presets.medium.url url_encode="true" }}&amp;description={{ content.caption |content.title url_encode="true" }}" class="smeFlatPinterest" rel="sme nofollow" data-service="pinterest">
		<i></i>Pinterest<span><?php echo $ctr['pi'];?></span>
	</a>
<?php } 
  if ($doit['ki']) {?>
	<span class="kindleWidget" style="display:inline-block;padding:3px;cursor:pointer;font-size:11px;font-family:Arial;white-space:nowrap;line-height:20px;border-radius:0px;border:#ccc thin solid;color:black;background: transparent url('https://d1xnn692s7u6t6.cloudfront.net/button-gradient.png') repeat-x;background-size:contain;"><img style="vertical-align:middle;margin:0;padding:0;border:none;" src="https://d1xnn692s7u6t6.cloudfront.net/white-15.png" /><span style="vertical-align:middle;margin-left:3px;">Send to Kindle</span></span>
	<script type="text/javascript" src="https://d1xnn692s7u6t6.cloudfront.net/widget.js"></script><script type="text/javascript">(function k(){window.$SendToKindle&&window.$SendToKindle.Widget?$SendToKindle.Widget.init({"content":".post"}):setTimeout(k,500);})();</script>
<?php }?>
</section>