<?php
if(!defined('entry')) define('entry',true); 
/* ===========================

  gelato CMS - A PHP based tumblelog CMS
  development version
  http://www.gelatocms.com/

  gelato CMS is a free software licensed under the GPL 2.0
  Copyright (C) 2007 by Pedro Santana <pecesama at gmail dot com>

  =========================== */
?>
<?php
	require('entry.php');
	$trigger->call('feed_header');
	
	header("Content-type: text/xml; charset=utf-8");
	$isFeed = true;
	
	global $conf, $tumble;
	
	
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>	
	<rss version="2.0">
	<channel>
		<title><?php echo htmlspecialchars($conf->title);?></title>
		<link><?php echo $conf->urlGelato;?></link>
		<description><?php echo htmlspecialchars($conf->description);?></description>
		<generator>gelato CMS <?php echo util::codeName()." (".util::version().")"; ?></generator>        
		<image>
			<url><?php echo $conf->urlGelato;?>/images/information.png</url>
			<title><?php echo htmlspecialchars($conf->description);?></title>
			<link><?php echo $conf->urlGelato;?></link>
		</image>
<?php
	$rs = $tumble->getPosts("20");
	if ($db->contarRegistros()>0) {		

		while($register = mysql_fetch_array($rs)) {
			$register["description"] = $register["description"];
			
			switch ($register["type"]) {
				case "1":
					$tit = ($register["title"]=="") ? $register["description"] : $register["title"];
					$desc = $register["description"];
					break;
				case "2":
					$photoPath = str_replace("../", $conf->urlGelato."/", $register["url"]);
					$tit = ($register["description"]=="") ? "Photo" : $register["description"];
					$desc = "<img src=\"".$photoPath."\"/>";
					break;
				case "3":
					$tit = "\"".$register["description"]."\"";
					$tmpStr = ($register["title"]!="") ? "<br /><br /> - <em>".$register["title"]."</em>" : "";
					$desc = "\"".$register["description"]."\"".$tmpStr;
					break;
				case "4":
					$tit = ($register["title"]=="") ? $register["url"] : $register["title"];
					$tmpStr = ($register["description"]!="") ? "<br /><br /> - <em>".$register["description"]."</em>" : "";
					$desc = "<a href=\"".$register["url"]."\">".$tit."</a>".$tmpStr;
					break;
				case "5":
					$lines = explode("\n", $register["description"]);
					$line = $lines[0];
					$tit = ($register["title"]=="") ? $line : $register["title"];
					$desc = $tumble->formatConversation($register["description"]);
					break;
				case "6":
					$tit = ($register["description"]=="") ? "Video" : $register["description"];
					$desc = $tumble->getVideoPlayer($register["url"]);
					break;
				case "7":
					$tit = ($register["description"]=="") ? "MP3" : $register["description"];
					$desc = $tumble->getMp3Player($register["url"]);
					break;
			}
			$tit = util::trimString($tit);
			$strEnd = ($conf->urlFriendly) ? "/" : "";			
			$url = $tumble->getPermalink($register["id_post"]);
			$formatedDate = gmdate("r", strtotime($register["date"]) + util::transform_offset($conf->offsetTime));
?>
			<item>
				<title><?php echo $tit;?></title>
				<description><![CDATA[<?php echo $desc;?>]]></description>
				<link><?php echo $url;?></link>
				<guid isPermaLink="true"><?php echo $url;?></guid>				
				<pubDate><?php echo $formatedDate;?></pubDate>				
			</item>

<?php	
		}
	}
?>
	</channel>
</rss>