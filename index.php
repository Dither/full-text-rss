<?php
require_once(dirname(__FILE__).'/config.php');
// check for custom index.php (custom_index.php)
if (!defined('_FF_FTR_INDEX')) {
	define('_FF_FTR_INDEX', true);
	if (file_exists(dirname(__FILE__).'/custom_index.php')) {
		include(dirname(__FILE__).'/custom_index.php');
		exit;
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Full-Text RSS Feeds | from fivefilters.org</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	
	<meta name="robots" content="noindex, follow" />
	<link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" media="screen" />
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/bootstrap-tooltip.js"></script>
	<script type="text/javascript" src="js/bootstrap-popover.js"></script>
	<script type="text/javascript" src="js/bootstrap-tab.js"></script>
	<script type="text/javascript">
	var baseUrl = 'http://'+window.location.host+window.location.pathname.replace(/(\/index\.php|\/)$/, '');
	$(document).ready(function() {
		// remove http scheme from urls before submitting
		$('#form').submit(function() {
			$('#url').val($('#url').val().replace(/^http:\/\//i, ''));
			return true;
		});
		// popovers
		$('#url').popover({offset: 10, placement: 'left', trigger: 'focus', html: true});
		$('#key').popover({offset: 10, placement: 'left', trigger: 'focus', html: true});
		$('#max').popover({offset: 10, placement: 'left', trigger: 'focus', html: true});
		$('#links').popover({offset: 10, placement: 'left', trigger: 'focus', html: true});
		$('#exc').popover({offset: 10, placement: 'left', trigger: 'focus', html: true});
		// tooltips
		$('a[rel=tooltip]').tooltip();
	});
	</script>
	<style>
	html, body { background-color: #eee; }
	body { margin: 0; line-height: 1.4em; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; }
	label, input, select, textarea { font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; }
	li { color: #404040; }
	li.active a { font-weight: bold; color: #666 !important; }
	form .controls { margin-left: 220px !important; }
	label { width: 200px !important; }
	fieldset legend { padding-left: 220px; line-height: 20px !important;}
	.form-actions { padding-left: 220px !important; }
	.popover-inner { width: 205px; }
	h1 { margin-bottom: 18px; }
	</style>
  </head>
  <body>
	<div class="container" style="width: 800px; padding-bottom: 60px;">
	<h1 style="padding-top: 5px;">Full-Text RSS <?php echo _FF_FTR_VERSION; ?> <span style="font-size: .7em; font-weight: normal;">&mdash; from <a href="http://fivefilters.org">FiveFilters.org</a></span></h1>
    <form method="get" action="makefulltextfeed.php" id="form" class="form-horizontal">
	<fieldset>
		<legend>Create full-text feed from feed or webpage URL</legend>
		<div class="control-group">
			<label class="control-label" for="url">Enter URL</label>
			<div class="controls"><input type="text" id="url" name="url" style="width: 450px;" title="URL" data-content="Typically this is a URL for a partial feed which we transform into a full-text feed. But it can also be a standard web page URL, in which case we'll extract its content and return it in a 1-item feed." /></div>
		</div>
	</fieldset>
	<fieldset>
	<legend>Options</legend>
	<!--
	<?php if ($options->extraction_pattern == 'user') { ?>
	<div class="control-group">
	<label class="control-label" for="what">Extraction pattern:</label>
	<div class="controls"><input type="text" id="what" name="what" value="auto" style="width: 250px;" /></div>
	</div>
	<?php } ?>	
	-->
	<?php if (isset($options->api_keys) && !empty($options->api_keys)) { ?>
	<div class="control-group">
	<label class="control-label" for="key">Access key</label>
	<div class="controls">
	<input type="text" id="key" name="key" class="input-medium" <?php if ($options->key_required) echo 'required'; ?> title="Access Key" data-content="<?php echo ($options->key_required) ? 'An access key is required to generate a feed' : 'If you have an access key, enter it here.'; ?>" />
	</div>
	</div>
	<?php } ?>
	<div class="control-group">
	<label class="control-label" for="max">Max items</label>
	<div class="controls">
	<?php
	// echo '<select name="max" id="max" class="input-medium">'
	// for ($i = 1; $i <= $options->max_entries; $i++) {
	//	printf("<option value=\"%s\"%s>%s</option>\n", $i, ($i==$options->default_entries) ? ' selected="selected"' : '', $i);
	// } 
	// echo '</select>';
	if (!empty($options->api_keys)) {
		$msg = 'Limit: '.$options->max_entries.' (with key: '.$options->max_entries_with_key.')';
		$msg_more = 'If you need more items, change <tt>max_entries</tt> (and <tt>max_entries_with_key</tt>) in config.';
	} else {
		$msg = 'Limit: '.$options->max_entries;
		$msg_more = 'If you need more items, change <tt>max_entries</tt> in config.';
	}
	?>	
	<input type="text" name="max" id="max" class="input-mini" value="<?php echo $options->default_entries; ?>" title="Feed item limit" data-content="Set the maximum number of feed items we should process. The smaller the number, the faster the new feed is produced.<br /><br />If your URL refers to a standard web page, this will have no effect: you will only get 1 item.<br /><br /> <?php echo $msg_more; ?>" />
	<span class="help-inline" style="color: #888;"><?php echo $msg; ?></span>
	</div>
	</div>
	<div class="control-group">
	<label class="control-label" for="links">Links</label>
	<div class="controls">
	<select name="links" id="links" class="input-medium" title="Link handling" data-content="By default, links within the content are preserved. Change this field if you'd like links removed, or included as footnotes.">
		<option value="preserve" selected="selected">preserve</option>
		<option value="footnotes">add to footnotes</option>
		<option value="remove">remove</option>
	</select>
	</div>
	</div>
	<?php if ($options->exclude_items_on_fail == 'user') { ?>
	<div class="control-group">
	<label class="control-label" for="exc">If extraction fails</label>
	<div class="controls">
	<select name="exc" id="exc" title="Item handling when extraction fails" data-content="If extraction fails, we can remove the item from the feed or keep it in.<br /><br />Keeping the item will keep the title, URL and original description (if any) found in the feed. In addition, we insert a message before the original description notifying you that extraction failed.">
		<option value="" selected="selected">keep item in feed</option>
		<option value="1">remove item from feed</option>
	</select>
	</div>
	</div>
	<?php } ?>
	
	<div class="control-group">
	<label class="control-label" for="json">JSON output</label>
	<div class="controls">
	<input type="checkbox" name="format" value="json" id="json" style="margin-top: 7px;" />
	</div>
	</div>	
	
	</fieldset>
	<div class="form-actions">
		<input type="submit" id="sudbmit" name="submit" value="Create Feed" class="btn btn-primary" />
	</div>
	</form>
	
	
	<ul class="nav nav-tabs">
	<li class="active"><a href="#start" data-toggle="tab">Getting Started</a></li>
	<li><a href="#general" data-toggle="tab">General Info</a></li>
	<li><a href="#updates" data-toggle="tab">Updates</a></li>
	<li><a href="#license" data-toggle="tab">License</a></li>
	</ul>
	
	<div class="tab-content">
	
	<!-- GETTING STARTED TAB -->
	
	<div class="active tab-pane" id="start">
	
	<h3>Thank you!</h3>
	
	<p>Thanks for downloading and setting up Full-Text RSS from FiveFilters.org. The software runs on most web hosting environments, but to make sure everything works as it should, please follow the steps below.</p>
	
	<h3>Quick Start</h3>
	<ol>
		<li><a href="ftr_compatibility_test.php">Check server compatibility</a> to make sure this server meets the requirements</li>
		<li>Enter a feed or article URL in the form above and click 'Create Feed' <a href="http://help.fivefilters.org/customer/portal/articles/223127-suggested-feeds-and-articles" rel="tooltip" title="Need suggestions? We've got a number of feeds and articles you can try" class="label">?</a></li>
		<li>If the generated full-text feed looks okay, copy the URL from your browser's address bar and use it in your news reader or application</li>
		<li><strong>That's it!</strong> (Although see below if you'd like to customise further.)</li>
	</ol>
	
	<h3>Configure</h3>
	<p>In addition to the options above, Full-Text RSS comes with a configuration file which allows you to control how the application works. <a href="http://help.fivefilters.org/customer/portal/articles/223410-configure">Find out more.</a></p>
	<p>Features include:</p>
	<ul>
		<li>Site patterns for better control over extraction (<a href="http://help.fivefilters.org/customer/portal/articles/223153-site-patterns">more info</a>)</li>
		<li>Restrict access to those with an access key and/or to a pre-defined set of URLs</li>
		<li>Restrict the maximum number of feed items to be processed</li>
		<li>Prepend or append an HTML fragment to each feed item processed</li>
		<li>Caching</li>		
	</ul>
	<p><?php if (!file_exists('custom_config.php')) { ?>To change the configuration, save a copy of <tt>config.php</tt> as <tt>custom_config.php</tt> and make any changes you like to it.<?php } else { ?>To change the configuration, edit <tt>custom_config.php</tt> and make any changes you like.<?php } ?></p>

	<h3>Customise this page</h3>
	<p>If everything works fine, feel free to modify this page by following the steps below:</p>
	<ol>
		<li>Save a copy of <tt>index.php</tt> as <tt>custom_index.php</tt></li>
		<li>Edit <tt>custom_index.php</tt></li>
	</ol>
	<p>Next time you load this page, it will automatically load custom_index.php instead.</p>
	
	<h3 id="support">Support</h3>
	<p>Check our <a href="http://help.fivefilters.org">help centre</a> if you need help. You can also email us at <a href="mailto:help@fivefilters.org">help@fivefilters.org</a>.</p>
	
	</div>
	
	<!-- GENERAL TAB -->
	
	<div id="general" class="tab-pane">
	
	<h3>About</h3>
	<p>This is a free software project to enable article extraction from web pages. It can extract content from a standard HTML page and return a 1-item feed or it can transform an existing feed into a full-text feed. It is being developed as part of the <a href="http://fivefilters.org">Five Filters</a> project to promote independent, non-corporate media.</p>

	<h3>Bookmarklet</h3>
	<p>Rather than copying and pasting URLs into this form, you can add the bookmarklet on this page to your browser. Simply drag the link below to your browser's bookmarks toolbar.
	Then whenever you'd like a full-text feed, click the bookmarklet.</p>
	<p>Drag this: 
	<script type="text/javascript">
	document.write('<a class="btn info" style="cursor: move;" onclick="alert(\'drag to bookmarks toolbar\'); return false;" href="javascript:location.href=\''+baseUrl+'/makefulltextfeed.php?url=\'+encodeURIComponent(document.location.href);">Full-Text RSS</a>');
	</script>
	<p>Note: This uses the default options and does not include your access key (if configured).</p>	
	
	<h3>Free Software</h3>
	<p>Note: 'Free' as in 'free speech' (see the <a href="https://www.gnu.org/philosophy/free-sw.html">free software definition</a>)</p>
	
	<p>If you're the owner of this site and you plan to offer this service to others through your hosted copy, please keep a download link so users can grab a copy of the code if they 
	want it (you can either offer a free download yourself, or link to the purchase option on fivefilters.org to support us).</p>
	
	<p>For full details, please refer to the <a href="http://www.gnu.org/licenses/agpl-3.0.html" title="AGPLv3">license</a>.</p>
	
	<p>If you're not the owner of this site (ie. you're not hosting this yourself), you do not have to rely on an external service if you don't want to. You can <a href="http://fivefilters.org/content-only/#download">download your own copy</a> of Full-Text RSS under the AGPL license.</p>
	
	<h3 id="api">URL Construction</h3>
	<p>To extract content from a web page or to transform an existing partial feed to full text, pass the URL (<a href="http://meyerweb.com/eric/tools/dencoder/">encoded</a>) in the querystring to the following URL:</p>
	<ul>
		<li style="font-family: monospace;"><script type="text/javascript">document.write(baseUrl);</script>/makefulltextfeed.php?url=<strong>[url]</strong></li>
	</ul>
	
	<p>All the parameters in the form above can be passed in this way. Examine the URL in the address bar after you click 'Create Feed' to see the values.</p>
	
	<h3>Software Components</h3>
	<p>Full-Text RSS is written in PHP and relies on the following <strong>primary</strong> components:</p>
	<ul>
		<li><a href="http://www.keyvan.net/2010/08/php-readability/">PHP Readability</a></li>
		<li><a href="http://simplepie.org/">SimplePie</a></li>
		<li>FeedWriter</li>
		<li>Humble HTTP Agent</li>
	</ul>
	<p>Depending on your configuration, these <strong>secondary</strong> components may also be used:</p> 
	<ul>
		<li><a href="http://framework.zend.com/manual/en/zend.cache.introduction.html">Zend Cache</a></li>
		<li><a href="http://framework.zend.com/manual/en/zend.dom.query.html">Zend DOM Query</a></li>
		<li><a href="http://code.google.com/p/rolling-curl/">Rolling Curl</a></li>
		<li><a href="http://pear.php.net/package/Text_LanguageDetect">Text_LanguageDetect</a> or <a href="https://github.com/lstrojny/php-cld">PHP-CLD</a> if available</li>
	</ul>

	<h3>System Requirements</h3>
	
	<p>PHP 5.2 or above is required. A simple shared web hosting account will work fine.
	The code has been tested on Windows and Linux using the Apache web server. If you're a Windows user, you can try it on your own machine using <a href="http://www.wampserver.com/en/index.php">WampServer</a>. It has also been reported as working under IIS, but we have not tested this ourselves.</p>
	
	<h3 id="download">Download</h3>
	<p>Download from <a href="http://fivefilters.org/content-only/#download">fivefilters.org</a> &mdash; old versions are available in our <a href="http://code.fivefilters.org">code repository</a>.</p>

	</div>
	
	<!-- UPDATES TAB -->
	<div id="updates" class="tab-pane">
	<?php 
	$site_config_version_file = dirname(__FILE__).'/site_config/standard/version.php';
	if (file_exists($site_config_version_file)) {
		$site_config_version = include($site_config_version_file);
	}
	?>
	<p>Your version of Full-Text RSS: <strong><?php echo _FF_FTR_VERSION; ?></strong><br />
	Your version of Site Patterns: <strong><?php echo (isset($site_config_version) ? $site_config_version : 'Unknown'); ?></strong>
	</p>
	<p>To see if you have the latest versions, and to update your site patterns, please try our <a href="admin/update.php">update tool</a>.</p>
	<p>If you've purchased this from FiveFilters.org, you'll receive notification when we release a new version or update the site patterns.</p>
	</div>	
	
	<!-- LICENSE TAB -->
	<div id="license" class="tab-pane">
	<p><a href="http://en.wikipedia.org/wiki/Affero_General_Public_License" style="border-bottom: none;"><img src="images/agplv3.png" alt="AGPL logo" /></a></p>
	<p>Full-Text RSS is licensed under the <a href="http://en.wikipedia.org/wiki/Affero_General_Public_License">AGPL version 3</a> &mdash; which basically means if you use the code to offer the same or similar service for your users, you are also required to share the code with your users so they can examine the code and run it for themselves. (<a href="http://www.clipperz.com/users/marco/blog/2008/05/30/freedom_and_privacy_cloud_call_action">More on why this is important.</a>)</p> 
	<p>The software components used by the application are licensed as follows...</p>
	<ul>
		<li>PHP Readability: <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License v2</a></li>
		<li>SimplePie: <a href="http://en.wikipedia.org/wiki/BSD_license">BSD</a></li>
		<li>FeedWriter: <a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html">GPL v2</a></li>
		<li>Humble HTTP Agent: <a href="http://en.wikipedia.org/wiki/Affero_General_Public_License">AGPL v3</a></li>
		<li>Zend: <a href="http://framework.zend.com/license/new-bsd">New BSD</a></li>
		<li>Rolling Curl: <a href="http://www.apache.org/licenses/LICENSE-2.0">Apache License v2</a></li>
		<li>Text_LanguageDetect: <a href="http://en.wikipedia.org/wiki/BSD_license">BSD</a></li>		
	</ul>
	</div>
	
	</div>
  </body>
</html>