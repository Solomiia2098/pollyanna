<?php
/*
	php shim for accepting submissions
	proxies post.html if admin/php/enabled is on
*/

//error_reporting(E_ALL);

include_once('utils.php');
include_once('cookie.php');

$AllVars = array();
function setvar($name, $value) {
	global $AllVars;
	WriteLog('setvar: ' . $name);
	if (array_key_exists($name, $AllVars)) {
		$AllVars[$name] = $value;
	} else {
		WriteLog('setvar: warning: tried to set variable which not initialized: ' . $name);
	}
}

function getvar($name) {
	global $AllVars;
	WriteLog('getvar: ' . $name);
	if (array_key_exists($name, $AllVars)) {
		return $AllVars[$name];
	} else {
		WriteLog('getvar: warning: tried to get variable which not initialied: ' . $name);
	}
}

function makevar($name) {
	global $AllVars;
	WriteLog('makevar: ' . $name);
	if (array_key_exists($name, $AllVars)) {
		WriteLog('makevar: warning: tried to make variable which already initialized: ' . $name);
	} else {
		$AllVars[$name] = '';
	}
	if (!array_key_exists($name, $AllVars)) {
		WriteLog('makevar: warning: just made variable, but it is not initialized: ' . $name);
	}
}

makevar('postPhpStartTime');
setvar('postPhpStartTime', time()); // remember begin time so that we know how long it takes

{ # if the emergency brake has been pulled, posting is not allowed
	$stopTimeConfig = GetConfig('admin/stop');
	if ($stopTimeConfig) {
		if ($stopTimeConfig > time()) {
			if (isset($_COOKIE['test'])) {
			} else {
				print 'Emergency brake has been pulled. Posting is temporarily offline to unregistered visitors.';
				exit;
			}
		}
	}
} # emergency brake

$redirectUrl = ''; // stores location to redirect to when done
$recordFingerprint = 0;

$fileUrlPath = '';     // path to new item's html page
$replyTo = '';         // id of item replied to (parent)
$replyToToken = '';    // token for specifying replied to item, item id with >> prefix
$returnTo = '';        // page to return to, can be different from new item's page, ambsfa $redirectTo

$strSourceUrl = '';    // source document's url, specified as s= parameter in GET
$strSourceTitle = '';  // source document's title, specified as t= parameter in GET

$allParameters = array();

if ($_POST) { // if POST request, populate variables from $_POST
	WriteLog('post.php: $_POST');

	if (isset($_POST['comment'])) {
		$comment = $_POST['comment'];
	}

	if (isset($_POST['replyto']) && $_POST['replyto']) {
		$replyTo = $_POST['replyto'];
	}
	if (isset($_POST['boxes']) && $_POST['boxes']) {
		$boxesCount = $_POST['boxes'];
	}

	if (isset($_POST['s']) && $_POST['s']) { // s=
		$strSourceUrl = $_POST['s'];
	}
	if (isset($_POST['t']) && $_POST['t']) { // t=
		$strSourceTitle = $_POST['t'];
	}

	if (isset($_POST['replyto']) && $_POST['replyto']) {
		$replyTo = $_POST['replyto'];
	}

	if (isset($_POST['returnto']) && $_POST['returnto']) {
		$returnTo = $_POST['returnto'];
	}

	if (isset($_POST['recfing']) && $_POST['recfing']) {
		$recordFingerprint = $_POST['recfing'];
	}

	if (GetConfig('setting/admin/php/post/log_all_parameters')) {
		foreach($_POST as $paramKey => $paramValue) {
			WriteLog('post.php: $_POST: log_all_parameters: $paramKey = ' . $paramKey . '; $paramValue = ' . $paramValue);
			$allParameters[$paramKey] = $paramValue;
		}
	}
} // $_POST

elseif ($_GET) { // if GET request, populate variables from $_GET
	WriteLog('post.php: $_GET found');

	WriteLog('post.php: $_GET = ' . print_r($_GET, 1));

	if (isset($_GET['comment'])) {
		$comment = $_GET['comment'];
	} else {
		if (isset($_GET['txtClock'])) {
			$comment = $_GET['txtClock'];
		} else {
			if (isset($_GET['?comment'])) {
				$comment = $_GET['?comment'];
			} else {
				if (isset($_GET['?txtClock'])) {
					$comment = $_GET['?txtClock'];
				} else {
					$comment = '';
				}
			}
		}
	}

	if (isset($_GET['s']) && $_GET['s']) { // s=
		// used for origin url in bookmarklets
		$strSourceUrl = $_GET['s'];
	}
	if (isset($_GET['t']) && $_GET['t']) { // t=
		// used for origin url title in bookmarklets
		$strSourceTitle = $_GET['t'];
	}

	if (isset($_GET['replyto']) && $_GET['replyto']) {
		$replyTo = $_GET['replyto'];
	}
	if (isset($_GET['boxes']) && $_GET['boxes']) {
		$boxesCount = $_GET['boxes'];
	}

	if (isset($_GET['returnto']) && $_GET['returnto']) {
		$returnTo = $_GET['returnto'];
	}

	if (isset($_GET['recfing']) && $_GET['recfing']) {
		$recordFingerprint = $_GET['recfing'];
	}

	if (GetConfig('setting/admin/php/post/log_all_parameters')) {
		foreach($_GET as $paramKey => $paramValue) {
			WriteLog('post.php: $_POST: log_all_parameters: $paramKey = ' . $paramKey . '; $paramValue = ' . $paramValue);
			$allParameters[$paramKey] = $paramValue;
		}
	}
} # $_GET
elseif ($_REQUEST) { // if HEAD request, populate variables from $_REQUEST
	WriteLog('post.php: $_REQUEST found: ' . print_r($_REQUEST, 1));

	if (isset($_REQUEST['comment'])) {
		$comment = $_REQUEST['comment'];
	} else {
		if (isset($_REQUEST['txtClock'])) {
			$comment = $_REQUEST['txtClock'];
		} else {
			if (isset($_REQUEST['?comment'])) {
				$comment = $_REQUEST['?comment'];
			} else {
				if (isset($_REQUEST['?txtClock'])) {
					$comment = $_REQUEST['?txtClock'];
				} else {
					$comment = '';
				}
			}
		}
	}

	if (isset($_REQUEST['s']) && $_REQUEST['s']) { // s=
		$strSourceUrl = $_REQUEST['s'];
	}
	if (isset($_REQUEST['t']) && $_REQUEST['t']) { // t=
		$strSourceTitle = $_REQUEST['t'];
	}

	if (isset($_REQUEST['replyto']) && $_REQUEST['replyto']) {
		$replyTo = $_REQUEST['replyto'];
	}

	if (isset($_REQUEST['boxes']) && $_REQUEST['boxes']) {
		$boxesCount = $_REQUEST['boxes'];
	}

	if (isset($_REQUEST['returnto']) && $_REQUEST['returnto']) {
		$returnTo = $_REQUEST['returnto'];
	}

	if (isset($_REQUEST['recfing']) && $_REQUEST['recfing']) {
		$recordFingerprint = $_REQUEST['recfing'];
	}

	if (GetConfig('setting/admin/php/post/log_all_parameters')) {
		foreach($_REQUEST as $paramKey => $paramValue) {
			WriteLog('post.php: $_POST: log_all_parameters: $paramKey = ' . $paramKey . '; $paramValue = ' . $paramValue);
			$allParameters[$paramKey] = $paramValue;
		}
	}
} # $_REQUEST

$replyToToken = '';
if (IsItem($replyTo)) {
	$replyToToken = '>>' . $replyTo;
}

#################################################################
# END OF LOOKING FOR ARGUMENTS
#################################################################

if (isset($boxesCount) && $boxesCount && !$comment) {
	$comment = 'Box count at ' . time();
}

if (GetConfig('setting/admin/php/post/handle_browser_test') && isset($comment) && $comment) {
	# if handle_browser_test is on, and comment is not empty,
	# and comment contains two E[0-9]{13} tokens, add GET parameters to comment
	# this is used to record browser test results
	if (preg_match('/(E[0-9]{13})\W+(E[0-9]{13})/', $comment, $browserTestMatches)) {
		if (isset($browserTestMatches[1]) && isset($browserTestMatches[2])) {
			if ($_GET) {
				$comment .= "\n\n";
				foreach($_GET as $getName => $getValue) {
					if ($getName == 'comment') {
						# skip, it's already added
					}
					else {
						$comment .= $getName . ": " . $getValue . "\n";
					}
				}
				$comment .= "\n\n" . '#BrowserTest';
			}
		}
	}
} # handle_browser_test

{ # require_cookie
	if (isset($comment) && $comment && GetConfig('setting/admin/php/post/require_cookie')) {
		if ((!isset($_COOKIE['cookie']) || !isset($_COOKIE['checksum'])) && index($comment, 'SIGNED') == -1 && index($comment, 'PUBLIC') == -1) {
			#todo page does not look right, especially with dark theme
			setcookie2('comment_draft', $comment);

			header('HTTP/1.1 401 Unauthorized');

			$returnMessage = file_get_contents('nocookie.html');
            if (!$returnMessage) {
            	$returnMessage = file_get_contents('./nocookie.html');
            }
            if (!$returnMessage) {
            	WriteLog('post.php; warning: could not find nocookie.html, using fallback markup');

				/*my*/ $bgColor = GetThemeColor('background') || '#c0c0c0';
				/*my*/ $textColor = GetThemeColor('text') || '#000000';
				/*my*/ $linkColor = GetThemeColor('link_text') || '#000080';
				/*my*/ $vlinkColor = GetThemeColor('vlink_text') || '#800080';

				$returnMessage = '<html><body bgcolor="' . $bgColor . '" text="' . $textColor . '" vlink="' . $vlinkColor . '" link="' . $linkColor . '">';
				$returnMessage .= GetDialogX(
					"<p>Please forgive me, friend, <br>but you must <a href=/profile.html>register</a> first, <br>before you do that</p>",
					'No Cookie Haiku'
				);
				#todo this page should also auto-reload
				#todo there should be a body and stuff here
				$returnMessage .= GetDialogX(
					"<p>Return to this page <br>via bookmarks, history, <br>or the Back button.</p>" ,
					'Tips'
				);
				$returnMessage .= '</body></html>';
			}

			print($returnMessage);
			return '';
		} else {
		}
	}
} # require_cookie

if (is_array($comment)) { # comment[]
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD

	########################################

	$allAdded = '';

	$batchTag = ''; #my
	$batchTag = md5(time() . '-' . count($comment));
	$batchTag = substr($batchTag, 0, 8);

	#my
	$hnMode = 0;
	if (index($strSourceUrl, 'news.ycombinator.com') != -1) {
		$hnMode = 1;
	}
	WriteLog('post.php: batch mode: $strSourceUrl = ' . $strSourceUrl);

	#my
	$askMode = 0;
	if (index($strSourceUrl, 'askubuntu.com') != -1) {
		$askMode = 1;
	}

	foreach ($comment as $c) {
		if ($askMode) {
			while (index($c, "\n\n") != -1) {
				$c = str_replace("\n\n", "\n", $c);
			}
		}

		if ($hnMode) { # news.ycombinator.com
			WriteLog('post.php: batch mode: $hnMode activated. length($c) = ' . length($c));
			$c = str_replace('</p><p>', "\n", $c);
			$c = str_replace('<p>', "\n", $c);
			$c = str_replace('</p>', "\n", $c);
			$c = str_replace('<i>', "*", $c);
			$c = str_replace('</i>', "*", $c);
			$c = strip_tags($c);
			$c = html_entity_decode($c);
			WriteLog('post.php: batch mode: $hnMode finished stripping tags. length($c) = ' . length($c));

			#convert to ascii
			if (function_exists('iconv')) {
				$c = iconv('UTF-8', 'ASCII//TRANSLIT', $c);
			}

			$c = trim($c);
			$str= str_replace("\nreply\n", '', $c);
			$c = trim($c);

			WriteLog('post.php: batch mode: $hnMode all done. length($c) = ' . length($c));
		}

		$c = $c . "\n-- \n#$batchTag"; #\n--
		$newFileHash = StoreNewComment($c, '', $recordFingerprint); // batch add
		$allAdded .= ">>" . $newFileHash . "\n";
	}

	#my
	$newComment = '';
	$newComment .= "Comment(s) added in batch: " . count($comment) . "\n";
	$newComment .= "#BatchTag #" . $batchTag . "\n";
	$newComment .= "Server time: " . time();

	print $batchTag;

	if ($newComment) {
		$comment = $newComment;
	} else {
		$comment = '';
	}

	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########### BATCH ADD
	########################################
} # comment[] is array

if (!$comment && $strSourceTitle) {
	// if there is no comment, but there's a source title, use that as the comment
	WriteLog('post.php: setting $comment from $strSourceTitle');
	$comment = $strSourceTitle;
	$strSourceTitle = '';
}
if (!$comment && $strSourceUrl) {
	// if there is no comment, but there's a source url, use that as the comment
	WriteLog('post.php: setting $comment from $strSourceUrl');
	$comment = $strSourceUrl;
}

if (isset($comment) && $comment) {
	if ($comment == 'Update') { #duplicated in route.php
		WriteLog('post.php: found $comment == Update');
		
		$updateStartTime = time();
		DoUpdate();
		$fileUrlPath = '';
		$updateFinishTime = time();
		$updateDuration = $updateFinishTime - $updateStartTime;

		RedirectWithResponse('/stats.html', "Update finished! <small>in $updateDuration"."s</small>");
	} # if ($comment == 'Update')
	elseif (strtolower($comment) == 'stop' && GetConfig('admin/token/stop')) {
		WriteLog('post.php: found $comment == stop');
		
		$stopTime = time();
		$stopTimeConfig = GetConfig('admin/stop');
		if ($stopTimeConfig > $stopTime) {
			$stopTime = $stopTimeConfig;
		}
		$stopTime += 30;
		PutConfig('admin/stop', $stopTime);
		if ($stopTime > time()) {
			print("<span class=dialog>Stop request received. Users without cookie won't be able to post for " . ($stopTime - time()) . ' seconds.</span><br><br>');
		}
	}
	elseif (strtolower($comment) == 'agree' && GetConfig('admin/token/agree')) {
		WriteLog('post.php: found $comment == agree');

		if (GetConfig('config/debug')) {
			print("<script>alert('Thank you for agreeing to terms of service.</script>");
		}
	}
	else {
		// regular comment, not a command

		if (!$comment && $strSourceTitle) {
			// if there is no comment, but there's a source title, use that as the comment
			$comment = $strSourceTitle;
		}
		if (!$comment && $strSourceUrl) {
			// if there is no comment, but there's a source url, use that as the comment
			$comment = $strSourceUrl;
		}

		if ($strSourceUrl || $strSourceTitle) {
			WriteLog('post.php: found $strSourceUrl or $strSourceTitle');

			#todo sanity checks
			$addendum = '';
			$addendumHash = '';

			if ($strSourceUrl) {
				$addendum .= $strSourceUrl;
				$addendum .= "\n";
			}
			if ($strSourceTitle) {
				$addendum .= $strSourceTitle;
				$addendum .= "\n";
			}
			if ($addendum) {
				//if ($newFileHash) {
				//	$addendum = '>>' . $newFileHash . "\n" . $addendum;
				//}
				//$addendumHash = ProcessNewComment($addendum, ''); #PutFile()
				$comment .= "\n\n";
				$comment .= $addendum;
			}
		} else {
			WriteLog('post.php: NOT found $strSourceUrl or $strSourceTitle');
		}

		if ($replyTo && !preg_match('/\>\>' . $replyTo . '/', $comment)) {
			// add >> token to comment if $replyTo is provided, but comment does not have token
			// note that the regex does have a / at the end, it's after $replyTo
			$comment = $replyToToken . "\n\n" . $comment;
		}

		if (isset($boxesCount) && $boxesCount) { #boxes #banana
			$comment = 'boxes: ' . $boxesCount . "\n" . $comment;
		}

		if (isset($allParameters)) {
			#todo this could use more sanity and cleanness
			if (index($comment, "\n-- \n") == -1) {
				$comment = $comment . "\n-- \n";
			}
			foreach ($allParameters as $paramKey => $paramValue) {
				#todo sanity checks
				if ($paramKey == 'comment') {
					# comment is already added by default
				} else {
					$comment = $comment . $paramKey . ': ' . $paramValue . "\n";
				}
			}
		}

		$newFileHash = ProcessNewComment($comment, $replyTo); // post.php // PutFile()

		if ($newFileHash) {
		    #ok
		} else {
		    WriteLog('post.php: warning: ProcessNewComment() returned FALSE');
		    if ($_GET) {
		        print("please forgive this inconvenience, something went wrong. here is everything you sent: " . htmlspecialchars(print_r($_GET, 1)));
		        exit;
            }
            if ($_POST) {
                print("please forgive this inconvenience, something went wrong. here is everything you sent: " . htmlspecialchars(print_r($_POST, 1)));
                exit;
		    }
		    print("please forgive this inconvenience, something went wrong.");
        }
		$fileUrlPath = '/' . GetHtmlFilename($newFileHash); // path for client's (browser's) path to html file

		if (isset($replyTo) && $replyTo) {
			WriteLog('post.php: $replyTo = ' . $replyTo);
			MakePage($replyTo); #todo is this needed here?
		} else {
			WriteLog('post.php: $replyTo not found');
		}

		if (!$redirectUrl && strpos($comment, 'PUBLIC KEY BLOCK')) {
			WriteLog('post.php: strpos($comment, PUBLIC KEY BLOCK)');

			// if user is submitting a public key, chances are
			// they just registered, so lazily redirect them to the profile page instead.
			// #todo relative url support
			// #todo better flow for registration -> profile page
			// #todo sometimes $newFileHash doesn't exist (why?)
			// #todo improve on this very naive way to figure out user id

			$finishTime = time() - getvar('postPhpStartTime');
			WriteLog('post.php: $newFileHash = ' . $newFileHash);
			$newFileHtmlPath = GetHtmlFilename($newFileHash);

			if (file_exists($newFileHtmlPath)) {
				WriteLog('post.php: file_exists($newFileHtmlPath)');

				// naive user identifier finder
				$profileId = preg_match(
					'/[0-9A-F]{16}/',
					file_get_contents(
						$newFileHtmlPath
					),
					$matches
				);

				WriteLog('post.php: $profileId = ' . print_r($profileId, 1) . '; $matches = ' . print_r($matches, 1));

				if ($profileId) {
					$profileId = $matches[0];
				} else {
					$profileId = 0;
				}
				WriteLog('post.php: $profileId = ' . $profileId);
				if ($profileId) {
					MakePage($profileId);
					$redirectUrl = '/author/' . $profileId . '/index.html';
				} else {
					$redirectUrl = $fileUrlPath;
				}
				WriteLog('post.php: $redirectUrl = ' . $redirectUrl);

				if (file_exists('.' . $redirectUrl)) {
					RedirectWithResponse(
						$redirectUrl,
						"Success! Profile created! <small>in $finishTime"." seconds</small>"
					);
				} else {
					WriteLog("post.php: getcwd() = " . getcwd());
					WriteLog("post.php: file missing, no redirect: " . '.' . $redirectUrl);
				}

				WriteLog('post.php: ... continue after redirect? sadface');
			} else {
				WriteLog('post.php: file_exists($newFileHtmlPath) FALSE');
			}
		} # strpos($comment, 'PUBLIC KEY BLOCK')

		if ($replyTo && !$returnTo) {
			// return to parent item's page if no other place to return to is specified
			$returnTo = $replyTo;
		}

		if ($returnTo && GetConfig('admin/php/post/use_return_to')) {
			WriteLog('post.php: $returnTo = ' . $returnTo);
			// $returnTo specifies page/item to return to instead of submitted item
			$returnToHtmlPath = './' . GetHtmlFilename($returnTo); // path for parent html file

			if (file_exists($returnToHtmlPath)) {
				// path for client's (browser's) path to html file
				$returnToUrlPath = '/' . GetHtmlFilename($returnTo); // #todo relativize option
				$newItemAnchor = substr($newFileHash, 0, 8);
				$finishTime = time() - getvar('postPhpStartTime');

				if (GetConfig('admin/php/lazy_page_generation')) {
					WriteLog('post.php: $returnTo and lazy_page_generation leads to unlink($returnToHtmlPath = ' . $returnToHtmlPath . ')');
					unlink($returnToHtmlPath);
				}

				//$responseReplyPosted = StoreServerResponse("Success! Reply posted. <small>in $finishTime"."s</small>");
				//$redirectUrl = $returnToUrlPath . '?message=' . $responseReplyPosted . '&anchorto=' . $newItemAnchor;

				RedirectWithResponse($returnToUrlPath, "Success! Reply posted. <small>in $finishTime"."s</small>");
				// #todo add anchorto support ?
				// #todo it would be nice if this differentiated between text reply and hashtag label
				// issue #2: ie does not like redirecting to a url with an anchor tag, because it tries to include that in the request
				// issue #3: mosaic doesn't like relative redirects, need to include own domain in return url
				// a few other browsers, like early ie versions, also don't like relative redirects
			} # if (file_exists($returnToHtmlPath))
		} # $returnTo
	} # regular comment, not 'update' or 'stop'
} # $comment

if (isset($filePath) && $filePath) {
	WriteLog('post.php: $filePath = ' . ($filePath ? $filePath : 'FALSE') . '; index_file_on_post = ' . GetConfig('admin/php/post/index_file_on_post'));
} else {
	WriteLog('post.php: warning: $filePath is FALSE; index_file_on_post = ' . GetConfig('admin/php/post/index_file_on_post'));
}

WriteLog('post.php: about to look for post.php; `pwd` = ' . `pwd`);

#######################################
$html = file_get_contents('post.html');
if (!$html) {
	$html = file_get_contents('./post.html');
}
if (!$html) {
	$html = '<html><head><title>Received</title><body><table width=99% height=99%><tr><td align=center valign=middle><br><br><center>Your message has been received.<p><a href=/>Continue</a></center><br><br></td></tr></table></body></html>';
}
#######################################

if ($html) {
	WriteLog('post.php: found post.php contents success!');
} else {
	WriteLog('post.php: warning: $html is false after file_get_contents(./post.html)');
}


if (isset($fileUrlPath) && $fileUrlPath) { #todo GetConfig() or GetTemplate()
	if (file_exists('../config/template/php/just_posted.template')) {
		$postedMessage = file_get_contents('../config/template/php/just_posted.template');
	}
	elseif (file_exists('../default/template/php/just_posted.template')) {
		copy ('../default/template/php/just_posted.template', '../config/template/php/just_posted.template');
		// #todo this sometimes throws a warning, probably because subdir doesn't exist
		$postedMessage = file_get_contents('../default/template/php/just_posted.template');
	}
	else {
		$postedMessage = 'Look around and you may see it somewhere.';
	}

	$postedMessage = str_replace('$fileUrlPath', $fileUrlPath, $postedMessage);

	$html = str_replace('<!-- submitted_text -->', $postedMessage, $html);
}

if (!$redirectUrl && $fileUrlPath) {
	$finishTime = time() - getvar('postPhpStartTime');

	if (!isset($redirectMessage)) {
		$redirectMessage = "Success! Item posted. <small class=advanced> in $finishTime s</small>";
	}

	$itemPostedServerResponse = $redirectMessage;
	//$itemPostedServerResponse .= ' <a href=/write.html>Another</a>'; // has bugs, doesn't always work

	if (isset($boxesCount) && $boxesCount) {
		// 		MakePage('boxes');
		require_once('handle_not_found.php');
		$html = HandleNotFound('/boxes.html', './boxes.html'); # cacheOverrideFlag
		RedirectWithResponse('/boxes.html', 'Thank you for counting boxes.');
		#RedirectWithResponse('/boxes.html?time=' . (time()+100), 'Thank you for counting boxes.');
	} else {
		if (GetConfig('admin/php/post/redirect_to_new_item')) {
			RedirectWithResponse($fileUrlPath, $itemPostedServerResponse);
		} else {
			#todo this is a bit of a hack, but it works
			MakePage('welcome');
			RedirectWithResponse('/welcome.html', $itemPostedServerResponse);
		}
	}
}

if (GetConfig('admin/php/debug')) {
	# in php debug mode, append everything stored by WriteLog() to the page
	# WriteLog('') returns this stored internally as a memo
	$html = str_replace('</body>', WriteLog('') . '</body>', $html);
}

print($html);

if (GetConfig('admin/php/debug_phpinfo')) {
	phpinfo();
}
