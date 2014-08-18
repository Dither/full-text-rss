<?php
// Update site config files for Full-Text RSS
// Author: Keyvan Minoukadeh
// Copyright (c) 2013 Keyvan Minoukadeh
// License: AGPLv3
// Date: 2013-05-12
// More info: http://fivefilters.org/content-only/
// Help: http://help.fivefilters.org

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Usage
// -----
// * Access this file in your browser and follow the instructions to update your site config files.
// * See section on automatic updates for a URL you can fetch periodically (e.g. with cron) to update site config files

error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
@set_time_limit(120);

////////////////////////////////
// Load config file
////////////////////////////////
$admin_page = 'update';
require_once('../config.php');
require_once 'template.php';
tpl_header('Update site patterns');

//////////////////////////////////
// Username and password must be available
//////////////////////////////////
if (!isset($options->admin_credentials) || $options->admin_credentials['username'] == '' || $options->admin_credentials['password'] == '') {
	header("X-Robots-Tag: noindex, nofollow", true);

	die('<h2>Username and password not set</h2><p>Full-Text RSS has not been configured with admin credentials.</p><p>If you are the administrator, please edit your <tt>custom_config.php</tt> file and enter the credentials in the appropriate section. When you\'ve done that, this page will prompt you for your admin credentials.</p>');
}
$admin_hash = sha1($options->admin_credentials['username'].'+'.$options->admin_credentials['password']);

$_self_host = $_SERVER['HTTP_HOST'];
$_self_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$self_update_url = 'http://'.htmlspecialchars($_self_host.$_self_path).'/update.php?key='.urlencode($admin_hash);

$latest_remote = 'https://codeload.github.com/fivefilters/ftr-site-config/zip/master';
$version = @file_get_contents('../site_config/standard/version.txt');

/////////////////////////////////
// Check for update key
/////////////////////////////////
if (!isset($_REQUEST['key']) || trim($_REQUEST['key']) == '') {

	require_once 'require_login.php';

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		header('Location: update.php');
		exit;
	}	
	
	$auto = true;
	$no_auto_reasons = array();
	if (!class_exists('ZipArchive')) {
		$auto = false;
		$no_auto_reasons[] = 'zip support (PHP\'s <a href="http://php.net/manual/en/zip.requirements.php">ZipArchive</a> class) is missing';
	}
	if (!is_writable('../site_config')) {
		$auto = false;
		$no_auto_reasons[] = 'your <tt>site_config/</tt> folder is not writable - change permissions to 777 and try again.</p>';
	}
	if (!file_exists('../site_config/standard/version.txt')) {
		die('Could not determine current version of your site pattern files (site_config/standard/version.txt). Make sure you\'re using at least version 3.2 of Full-Text RSS.');
	}
	?>
	<p>You have Full-Text RSS <strong><?php echo _FF_FTR_VERSION; ?></strong>
	(Site Patterns version: <strong><?php echo (isset($version) ? $version : 'Unknown'); ?></strong>)
	</p>
	<p>To see if you have the latest versions, <a href="http://fivefilters.org/content-only/latest_version.php?version=<?php echo urlencode(_FF_FTR_VERSION).'&site_config='.urlencode(@$version); ?>">check for updates</a>.</p>
	<?php
	if ($auto) {
		echo '<p>This update tool will attempt to fetch the latest site patterns from our <a href="https://github.com/fivefilters/ftr-site-config/">GitHub repository</a>.</p>';
		echo '<p><strong>Important: </strong>if you\'ve modified or added your own config files in the <tt>site_config/standard/</tt> folder, please move them to <tt>site_config/custom/</tt> &mdash; the update process will attempt to replace everything in <tt>site_config/standard/</tt> with our updated version.</p>';
		echo '<form method="post" action="update.php" class="well">';
		echo '<input type="hidden" name="key" value="'.$admin_hash.'" />';
		echo '<input type="submit" value="Update site config files" />';
		echo '</form>';
		echo '<h3>Automatic updates</h3>';
		echo '<p>You can schedule automatic updates using something like cron. The URL to call is:</p>';
		echo '<p class="well">'.$self_update_url.'</p>';
		echo '<p>We recommend you schedule this URL to be fetched once a day. If you do not have access to a scheduling service ';
		echo 'you may want to consider one of these online services: <a href="http://www.easycron.com/">Easycron</a>, <a href="https://www.setcronjob.com/">SetCronJob</a>, <a href="http://www.onlinecronjobs.com/">onlinecronjobs.com</a>.</p>';
		echo '<p>Note: the key contained in the URL is a hash value generated from your admin credentials. If you change these, the key will also change.</p>';
	} else {
		echo '<div class="notice">';
		echo '<p>We cannot automatically update your site pattern files because:</p>';
		echo '<ul>';
		foreach ($no_auto_reasons as $reason) {
			echo '<li>',$reason,'</li>';
		}
		echo '</ul>';
		echo '<p>You can still manually update by downloading the zip file and replacing everything in your <tt>site_config/standard/</tt> folder with the contents of the zip file.</p>';
		echo '</div>';
		echo '<p><a href="'.$latest_remote.'">Download site config files (zip)</a></p>';
	}
	echo '<h3>Help</h3>';
	echo '<p>If you have any trouble, please contact us via our <a href="http://help.fivefilters.org">support site</a>.</p>';
	exit;
}

//////////////////////////////////
// Check update key valid
//////////////////////////////////
if ($_REQUEST['key'] !== $admin_hash) {
	println("Sorry, invalid key supplied.");
	exit;
}

//////////////////////////////////
// Check for updates
//////////////////////////////////
//$ff_version = @file_get_contents('http://fivefilters.org/content-only/site_config/standard/version.txt');
$_context = stream_context_create(array('http' => array('user_agent' => 'PHP/5.4')));
$latest_info_json = @file_get_contents('https://api.github.com/repos/fivefilters/ftr-site-config', false, $_context);
if (!$latest_info_json) {
	println("Sorry, couldn't get info on latest site config files. Please try again later or contact us.");
	exit;
}
$latest_info_json = @json_decode($latest_info_json);
if (!is_object($latest_info_json)) {
	println("Sorry, couldn't parse JSON from GitHub. Please try again later or contact us.");
	exit;
}
$ff_version = $latest_info_json->updated_at;
if ($version == $ff_version) {
	die('Your site config files are up to date! If you have trouble extracting from a particular site, please email us: help@fivefilters.org');
} else {
	println("Updated site patterns are available (version $ff_version)...");
}

//////////////////////////////////
// Prepare
//////////////////////////////////
$tmp_latest_local = '../site_config/latest_site_config.zip';
$tmp_latest_local_dir = '../site_config/standard_latest';
$tmp_old_local_dir = '../site_config/standard_old';
if (file_exists($tmp_latest_local)) unlink($tmp_latest_local);
if (file_exists($tmp_latest_local_dir)) {
	if (!rrmdir($tmp_latest_local_dir)) {
		println("Sorry, couldn't remove old folder from last update");
		exit;
	}
}
if (file_exists($tmp_old_local_dir)) {
	rrmdir($tmp_old_local_dir);
}
$standard_local_dir = '../site_config/standard/';
//@copy($latest_remote, $tmp_latest_local);
//copy() does not appear to fill $http_response_header in certain environments
@file_put_contents($tmp_latest_local, @file_get_contents($latest_remote));
$headers = implode("\n", $http_response_header);
//var_dump($headers); exit;
if (strpos($headers, 'HTTP/1.0 200') === false) {
	println("Sorry, something went wrong. Please contact us if the problem persists.");
	exit;
}
if (class_exists('ZipArchive') && file_exists($tmp_latest_local)) {
	println("Downloaded latest copy of the site pattern files to $tmp_latest_local");
	$zip = new ZipArchive;
	if ($zip->open($tmp_latest_local) === TRUE) {
		$zip->extractTo($tmp_latest_local_dir);
		$zip->close();
		@unlink($tmp_latest_local);
		if (file_exists($tmp_latest_local_dir)) {
			println("Unzipped contents to $tmp_latest_local_dir");
			if (!file_exists($tmp_latest_local_dir.'/ftr-site-config-master/README.md')) {
				println("There was a problem extracting the latest site patterns archive - your current site patterns remain untouched.");
				println("Please <a href=\"$latest_remote\">update manually</a>.");
				exit;
			}
			@file_put_contents($tmp_latest_local_dir.'/ftr-site-config-master/version.txt', $ff_version);
			if (!file_exists($tmp_latest_local_dir.'/ftr-site-config-master/version.txt')) {
				println("There was a problem writing the new version number - your current site patterns remain untouched.");
				println("Please <a href=\"$latest_remote\">update manually</a>.");
				exit;
			}
			rename($standard_local_dir, $tmp_old_local_dir);
			if (file_exists($tmp_old_local_dir)) println("Renamed $standard_local_dir to $tmp_old_local_dir");
			rename($tmp_latest_local_dir."/ftr-site-config-master", $standard_local_dir);
			if (file_exists($standard_local_dir)) println("Renamed $tmp_latest_local_dir/ftr-site-config-master to $standard_local_dir");
			rmdir($tmp_latest_local_dir);
			// clear cached site config files from APC
			if ($options->apc && function_exists('apc_delete') && function_exists('apc_cache_info')) {
				$_apc_data = apc_cache_info('user');
				foreach ($_apc_data['cache_list'] as $_apc_item) {
					if (substr($_apc_item['info'], 0, 3) == 'sc.') {
						apc_delete($_apc_item['info']);
					}
				}
				println('Cleared site config cache in APC.');
			}
			// all done!
			println("<strong style=\"color: darkgreen;\">All done!</strong> Your old site config files are in $tmp_old_local_dir &mdash; these will be removed next time you go through the update process.");
		} else {
			if (file_exists($tmp_latest_local)) @unlink($tmp_latest_local);
			println("Failed to unzip to $tmp_latest_local_dir - your current site patterns remain untouched");
		}
	} else {
		if (file_exists($tmp_latest_local)) @unlink($tmp_latest_local);
		println("Failed to extract from $tmp_latest_local - your current site patterns remain untouched");
	}
} else {
	println("Could not download the latest site config files. Please <a href=\"$latest_remote\">update manually</a> - your current site patterns remain untouched.");
}

function println($txt) {
	echo $txt,"<br />\n";
	ob_end_flush(); 
    ob_flush(); 
    flush(); 
}

function rrmdir($dir) {
    foreach(glob($dir . '/{*.txt,*.php,.*.txt,.*.php,.gitattributes,.gitignore,ftr-site-config-master,README.md}', GLOB_BRACE|GLOB_NOSORT) as $file) {
        if(is_dir($file)) {
            rrmdir($file);
        } else {
            unlink($file);
		}
    }
    return rmdir($dir);
}