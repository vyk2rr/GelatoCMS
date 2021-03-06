<?php
if(!defined('entry'))define('entry', true);
 /* ===========================

  gelato CMS - A PHP based tumblelog CMS
  development version
  http://www.gelatocms.com/

  gelato CMS is a free software licensed under the GPL 2.0
  Copyright (C) 2007 by Pedro Santana <pecesama at gmail dot com>

  =========================== */
?>
<?php

// Received a valid request
require_once('entry.php');
global $user, $tumble, $conf;

$theme = new themes;
        // Our first approach to MVC... our second? visit http://www.flavorphp.com

		if(isset($_SERVER['PATH_INFO'])) $param_url = explode("/",$_SERVER['PATH_INFO']);

		if (isset($_GET["post"])) {
				$id_post = $_GET["post"];
				if (!is_numeric($id_post) || $id_post < 1 ){
                	header("Location: index.php");
				}
		} else {
			if (isset($param_url[1]) && $param_url[1]=="post") {
 				$id_post = (isset($param_url[2])) ? ((is_numeric($param_url[2])) ? $param_url[2] : NULL) : NULL;
			} else {
				$id_post = NULL;
			}
		}

		$theme->set('id_post',$id_post);
		$theme->set('error','');

		if (isset($_GET["page"])) {
			$page_num = $_GET["page"];
		} else {
			if (isset($param_url[1]) && $param_url[1]=="page") {
				$page_num = (isset($param_url[2])) ? ((is_numeric($param_url[2])) ? $param_url[2] : NULL) : NULL;
			} else {
				$page_num = NULL;
			}
		}

		$feed_url = $conf->urlGelato.($conf->urlFriendly?"/rss/":"/rss.php");

		$gelato_includes = "<meta name=\"generator\" content=\"gelato ".util::codeName()." (".util::version().")\" />\n";
		$gelato_includes .= "\t<link rel=\"shortcut icon\" href=\"".$conf->urlGelato."/images/favicon.ico\" />";

		$page_title = $conf->title;
		$page_title_divisor = " &raquo; "; // it should be set in configuration
		$page_title_len = 50; // it should be set in configuration
		if ($id_post) {
			$register = $tumble->getPost($id_post);
			if (empty($register["title"])) {
				if (!empty($register["description"])) {
					$page_title_data = util::trimString($register["description"], $page_title_len);
				} else {
					$page_title_data =  util::type2Text($register["type"]);
				}
			} else {
				$page_title_data = $register["title"];
			}
			if (!empty($page_title_data)) {
				$page_title .= $page_title_divisor.stripslashes($page_title_data);
			}
		}

		$trigger->call('gelato_includes');
		$theme->set('rssFeed',$feed_url);
		$theme->set('Gelato_includes',$gelato_includes);
		$theme->set('Title',$conf->title);
		$theme->set('Page_Title',$page_title);
		$theme->set('Description',$conf->description);
		$theme->set('URL_Tumble',$conf->urlGelato);
		$theme->set('Template_name',$conf->template);
		$theme->set('allowComments',$conf->allowComments);

		$theme->set('isAuthenticated',$user->isAuthenticated());
		if($user->isAuthenticated()){
			$theme->set('User',$_SESSION["user_login"]);
			$theme->set('URL_Tumble',$conf->urlGelato);
		}

		$rows = array();
		if(!$id_post){
 				$limit=$conf->postLimit;

				if(isset($page_num) && is_numeric($page_num) && $page_num>0) { // Is defined the page and is numeric?
					$from = (($page_num-1) * $limit);
				} else {
					$from = 0;
				}

				$rs = $tumble->getPosts($limit, $from);

				if ($db->contarRegistros()>0) {
						$dateTmp = null;
						while($register = mysql_fetch_assoc($rs)) {
								$formatedDate = gmdate("M d", strtotime($register["date"]) + util::transform_offset($conf->offsetTime));
								if ( $dateTmp != null && $formatedDate == $dateTmp ) { $formatedDate = ""; } else { $dateTmp = $formatedDate; }

								$permalink = $tumble->getPermalink($register["id_post"]);

								$conversation = $register["description"];

								$register["title"] = stripslashes($register["title"]);
								$register["description"] = stripslashes($register["description"]);

								$row['Date_Added'] = $formatedDate;
								$row['Permalink'] = $permalink;
								$row['postType'] = util::type2Text($register["type"]);

								switch ($register['type']){
										case "1":
											$row['Title'] = $register["title"];
											$row['Body'] = $register["description"];
											break;
											case "2":
											$fileName = "uploads/".util::getFileName($register["url"]);
											$x = @getimagesize($fileName);
												if ($x[0] > 500) {
													$photoPath = $conf->urlGelato."/classes/imgsize.php?w=500&img=".$register["url"];
												} else {
													$photoPath = str_replace("../", $conf->urlGelato."/", $register["url"]);
												}

												$effect = " href=\"".str_replace("../", $conf->urlGelato."/", $register["url"])."\" rel=\"lightbox\"";

												$row['PhotoURL'] = $photoPath;
												$row['PhotoAlt'] = strip_tags($register["description"]);
												$row['Caption'] = $register["description"];
												$row['Effect'] = $effect;
												break;
										case "3":
												$row['Quote'] = $register["description"];
												$row['Source'] = $register["title"];
												break;
                                        case "4":
												if($conf->shorten_links){
													$register["url"] = util::_file_get_contents("http://api.abbrr.com/api.php?out=link&url=".$register["url"]);
												}
												$register["title"] = ($register["title"]=="")? $register["url"] : $register["title"];

												$row['URL'] = $register["url"];
												$row['Name'] = $register["title"];
												$row['Description'] = $register["description"];
												break;
												case "5":
												$row['Title'] = $register["title"];
												$row['Conversation'] = $tumble->formatConversation($conversation);
												break;
										case "6":
												$row['Video'] = $tumble->getVideoPlayer($register["url"]);
												$row['Caption'] = $register["description"];
												break;
										case "7":
												$row['Mp3'] = $tumble->getMp3Player($register["url"]);
												$row['Caption'] = $register["description"];
												break;
								}

								$comment = new comments();
								$noComments = $comment->countComments($register["id_post"]);

								$user = new user();
								$username = $user->getUserByID($register["id_user"]);

								$row['User'] = $username["name"];
								$row['Comments_Number'] = $noComments;

								$rows[] = $row;
                        }
						
						$trigger->call('post_content');
						$theme->set('rows',$rows);

						$p = new pagination;
						$p->Items($tumble->getPostsNumber());
						$p->limit($limit);
						if($conf->urlFriendly){
								$p->urlFriendly('[...]');
								$p->target($conf->urlGelato."/page/[...]");
							}else{
								$p->target($conf->urlGelato);
							}

						$p->currentPage(isset($page_num) ? $page_num : 1);
						$theme->set('pagination',$p->getPagination());
					} else {
						$theme->set('error','No posts in this tumblelog.');
					}
			} else {
				$register = $tumble->getPost($id_post);

				$formatedDate = gmdate("M d", strtotime($register["date"]) + util::transform_offset($conf->offsetTime));
				$permalink = $tumble->getPermalink($register["id_post"]);

				$conversation = $register["description"];

				$register["description"] = $register["description"];

				$register["title"] = stripslashes($register["title"]);
				$register["description"] = stripslashes($register["description"]);

				$row['Date_Added'] = $formatedDate;
				$row['Permalink'] = $permalink;
				$row['postType'] = util::type2Text($register["type"]);

				switch ($register['type']) {
						case "1":
								$row['Title'] = $register["title"];
								$row['Body'] = $register["description"];
								break;
						case "2":
								$fileName = "uploads/".util::getFileName($register["url"]);

								$x = @getimagesize($fileName);
								if ($x[0] > 500) {
                                        $photoPath = $conf->urlGelato."/classes/imgsize.php?w=500&img=".$register["url"];
								} else {
										$photoPath = str_replace("../", $conf->urlGelato."/", $register["url"]);
								}

								$effect = " href=\"".str_replace("../", $conf->urlGelato."/", $register["url"])."\" rel=\"lightbox\"";

								$row['PhotoURL'] = $photoPath;
								$row['PhotoAlt'] = strip_tags($register["description"]);
								$row['Caption'] = $register["description"];
								$row['Effect'] = $effect;
								break;
						case "3":
								$row['Quote'] = $register["description"];
								$row['Source'] = $register["title"];
								break;
						case "4":
							if($conf->shorten_links){
									$register["url"] = util::_file_get_contents("http://api.abbrr.com/api.php?out=link&url=".$register["url"]);
								}
								$row['URL'] = $register["url"];
								$row['Name'] = $register["title"];
								$row['Description'] = $register["description"];
								break;
						case "5":
								$row['Title'] = $register["title"];
								$row['Conversation'] = $tumble->formatConversation($conversation);
								break;
						case "6":
								$row['Video'] = $tumble->getVideoPlayer($register["url"]);
								$row['Caption'] = $register["description"];
								break;
						case "7":
								$row['Mp3'] = $tumble->getMp3Player($register["url"]);
								$row['Caption'] = $register["description"];
								break;
                }

					$user = new user();
					$username = $user->getUserByID($register["id_user"]);

					$row["User"] = $username["name"];

					if (empty($register["title"])) {
						if (!empty($register["description"])) {
							$postTitle = util::trimString($register["description"]);
						} else {
							$postTitle =  util::type2Text($register["type"]);
						}
					} else {
						$postTitle = $register["title"];
					}

				$row["Post_Title"] = $postTitle;

				$comment = new comments();
				$row["Comments_Number"] = $comment->countComments($register["id_post"]);

				if ($conf->allowComments) {
					$rsComments = $comment->getComments($register["id_post"]);
					$comments = array();
					while($rowComment = mysql_fetch_assoc($rsComments)) {
						$commentAuthor = ($rowComment["web"]=="") ? $rowComment["username"] : "<a href=\"".$rowComment["web"]."\" rel=\"external\">".$rowComment["username"]."</a>";

						$answers['Id_Comment'] = $rowComment["id_comment"];
						$answers['Comment_Author'] = $commentAuthor;
						$answers['Date'] = gmdate("d.m.y", strtotime($rowComment["comment_date"]) + util::transform_offset($conf->offsetTime));
						$answers['Comment'] = nl2br($rowComment["content"]);

						$comments[] = $answers;
					}
					$theme->set('comments',$comments);

					$whois['User_Cookie'] = isset($_COOKIE['cookie_gel_user'])?$_COOKIE['cookie_gel_user']:'';
					$whois['Email_Cookie'] = isset($_COOKIE['cookie_gel_email'])?$_COOKIE['cookie_gel_email']:'';
					$whois['Web_Cookie'] = isset($_COOKIE['cookie_gel_web'])?$_COOKIE['cookie_gel_web']:'';
					$whois['Id_Post'] = $register["id_post"];

					$theme->set('Date_Added',gmmktime());
					$theme->set('Form_Action',$conf->urlGelato."/admin/comments.php");
					$theme->set('whois',$whois);
				}

				$rows[] = $row;
				
				$trigger->call('post_content');
				$theme->set('rows',$rows);
        }

		$theme->set('URL_Tumble',$conf->urlGelato);
		$theme->display(Absolute_Path.'themes/'.$conf->template.'/index.htm');
?>
