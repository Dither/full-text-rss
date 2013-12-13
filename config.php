<?php
/* Full-Text RSS config */

// ......IMPORTANT......................................
// .....................................................
// Please do not change this file (config.php) directly.
// Save a copy as custom_config.php and make your
// changes to that instead. It will automatically
// override anything in config.php. Because config.php
// always gets loaded anyway, you can simply specify
// options you'd like to override in custom_config.php.
// .....................................................

// Create config object
if (!isset($options)) $options = new stdClass();

// Enable service
// ----------------------
// Set this to false if you want to disable the service.
// If set to false, no feed is produced and users will 
// be told that the service is disabled.
$options->enabled = true;

// Default entries (without access key)
// ----------------------
// The number of feed items to process when no API key is supplied
// and no &max=x value is supplied in the querystring.
$options->default_entries = 5;

// Max entries (without access key)
// ----------------------
// The maximum number of feed items to process when no access key is supplied.
// This limits the user-supplied &max=x value. For example, if the user
// asks for 20 items to be processed (&max=20), if max_entries is set to 
// 10, only 10 will be processed.
$options->max_entries = 10;

// Rewrite relative URLs
// ----------------------
// With this enabled relative URLs found in the extracted content
// block are automatically rewritten as absolute URLs.
$options->rewrite_relative_urls = true;

// Exclude items if extraction fails
// ---------------------------------
// Excludes items from the resulting feed
// if we cannot extract any content from the
// item URL.
// Possible values...
// Enable: true
// Disable: false (default)
// User decides: 'user' (this option will appear on the form)
$options->exclude_items_on_fail = 'user';

// Enable caching
// ----------------------
// Enable this if you'd like to cache results
// for 10 minutes. Initially it's best
// to keep this disabled to make sure everything works
// as expected.
$options->caching = false;

// Cache directory
// ----------------------
// Only used if caching is true
$options->cache_dir = dirname(__FILE__).'/cache';

// Message to prepend (without access key)
// ----------------------
// HTML to insert at the beginning of each feed item when no access key is supplied.
// Substitution tags:
// {url} - Feed item URL
// {effective-url} - Feed item URL after we've followed all redirects
$options->message_to_prepend = '';

// Message to append (without access key)
// ----------------------
// HTML to insert at the end of each feed item when no access key is supplied.
// Substitution tags:
// {url} - Feed item URL
// {effective-url} - Feed item URL after we've followed all redirects
$options->message_to_append = '';

// Error message when content extraction fails (without access key)
// ----------------------
$options->error_message = '[unable to retrieve full-text content]';

// Keep enclosure in feed items
// If enabled, we will try to preserve enclosures if present.
// ----------------------
$options->keep_enclosures = true;

// Detect language
// ---------------
// Should we try and find/guess the language of the article being processed?
// Values will be placed inside the <dc:language> element inside each <item> element
// Possible values:
// * Ignore language: 0
// * Use article/feed metadata (e.g. HTML lang attribute): 1 (default)
// * As above, but guess if not present: 2
// * Always guess: 3
// * User decides: 'user' (value of 0-3 can be passed in querystring: e.g. &l=2)
$options->detect_language = 1;

// Registration key
// ---------------
// The registration key is optional. It is not required to use Full-Text RSS, 
// and does not affect the normal operation of Full-Text RSS. It is currently 
// only used on admin pages which help you update site patterns with the 
// latest version offered by FiveFilters.org. For these admin-related 
// tasks to complete, we will require a valid registration key.
// If you would like one, you can purchase the latest version of Full-Text RSS
// at http://fivefilters.org/content-only/
// Your registration key will automatically be sent in the confirmation email.
// Once you have it, simply copy and paste it here.
$options->registration_key = '';

/////////////////////////////////////////////////
/// RESTRICT ACCESS /////////////////////////////
/////////////////////////////////////////////////

// Admin credentials
// ----------------------
// Certain pages/actions, e.g. updating site patterns with our online tool, will require admin credentials.
// To use these pages, enter a password here and you'll be prompted for it when you try to access those pages.
// If no password or username is set, pages requiring admin privelages will be inaccessible. 
// The default username is 'admin'.
// Example: $options->admin_credentials = array('username'=>'admin', 'password'=>'my-secret-password');
$options->admin_credentials = array('username'=>'admin', 'password'=>'');

// URLs to allow
// ----------------------
// List of URLs (or parts of a URL) which the service will accept.
// If the list is empty, all URLs (except those specified in the blocked list below)
// will be permitted.
// Empty: array();
// Non-empty example: array('example.com', 'anothersite.org');
$options->allowed_urls = array();

// URLs to block
// ----------------------
// List of URLs (or parts of a URL) which the service will not accept.
// Note: this list is ignored if allowed_urls is not empty
$options->blocked_urls = array();

// Key holder(s) only?
// ----------------------
// Set this to true if you want to restrict access only to
// those with a key (see below to specify key(s)).
// If set to true, no feed is produced unless a valid
// key is provided.
$options->key_required = false;

// Access keys (password protected access)
// ------------------------------------
// NOTE: You do not need an API key from fivefilters.org to run your own 
// copy of the code. This is here if you'd like to restrict access to
// _your_ copy.
// Keys let you group users - those with a key and those without - and
// restrict access to the service to those without a key.
// If you want everyone to access the service in the same way, you can
// leave the array below empty and ignore the access key options further down.
// The options further down let you control how the service should behave 
// in each mode.
// Note: Explicitly including the index number (1 and 2 in the examples below) 
// is highly recommended (when generating feeds, we encode the key and 
// refer to it by index number and hash).
$options->api_keys = array();
// Example:
// $options->api_keys[1] = 'secret-key-1';
// $options->api_keys[2] = 'secret-key-2';

// Default entries (with access key)
// ----------------------
// The number of feed items to process when a valid access key is supplied.
$options->default_entries_with_key = 5;

// Max entries (with access key)
// ----------------------
// The maximum number of feed items to process when a valid access key is supplied.
$options->max_entries_with_key = 10;

/////////////////////////////////////////////////
/// ADVANCED OPTIONS ////////////////////////////
/////////////////////////////////////////////////

// Fingerprints
// ----------------------
// key is fingerprint (fragment to find in HTML)
// value is host name to use for site config lookup if fingerprint matches
$options->fingerprints = array(
	// Posterous
	'<meta name="generator" content="Posterous"' => array('hostname'=>'fingerprint.posterous.com', 'head'=>true),
	// Blogger
	'<meta content=\'blogger\' name=\'generator\'' => array('hostname'=>'fingerprint.blogspot.com', 'head'=>true),
	'<meta name="generator" content="Blogger"' => array('hostname'=>'fingerprint.blogspot.com', 'head'=>true),
	// WordPress (hosted)
	// '<meta name="generator" content="WordPress.com"' => array('hostname'=>'fingerprint.wordpress.com', 'head'=>true),
	// WordPress (self-hosted and hosted)
	'<meta name="generator" content="WordPress' => array('hostname'=>'fingerprint.wordpress.com', 'head'=>true)
);

// User Agent strings - mapping domain names
// ----------------------
// e.g. $options->user_agents = array('example.org' => 'PHP/5.2');
$options->user_agents = array( 'lifehacker.com' => 'PHP/5.2',
							   'gawker.com' => 'PHP/5.2',
							   'deadspin.com' => 'PHP/5.2',
							   'kotaku.com' => 'PHP/5.2',
							   'jezebel.com' => 'PHP/5.2',
							   'io9.com' => 'PHP/5.2',
							   'jalopnik.com' => 'PHP/5.2',
							   'gizmodo.com' => 'PHP/5.2',
							   '.wikipedia.org' => 'Mozilla/5.2'
							  );

// URL Rewriting
// ----------------------
// Currently allows simple string replace of URLs.
// Useful for rewriting certain URLs to point to a single page
// or HTML view. Although using the single_page_link site config
// instruction is the preferred way to do this, sometimes, as
// with Google Docs URLs, it's not possible.
// Note: this might move to the site config file at some point.
$options->rewrite_url = array(
	// Rewrite public Google Docs URLs to point to HTML view:
	// if a URL contains docs.google.com, replace /Doc? with /View?
	'docs.google.com' => array('/Doc?' => '/View?'),
	'tnr.com' => array('tnr.com/article/' => 'tnr.com/print/article/'),
	'.m.wikipedia.org' => array('.m.wikipedia.org' => '.wikipedia.org')
);

// Content-Type exceptions
// -----------------------
// We currently treat everything as HTML.
// Here you can define different actions if
// Content-Type returned by server matches.
// MIME type as key, action as value.
// Valid actions:
// * 'exclude' - exclude this item from the result
// * 'link' - create HTML link to the item
$options->content_type_exc = array( 
							   'application/pdf' => array('action'=>'link', 'name'=>'PDF'),
							   'image' => array('action'=>'link', 'name'=>'Image'),
							   'audio' => array('action'=>'link', 'name'=>'Audio'),
							   'video' => array('action'=>'link', 'name'=>'Video')
							  );

// Alternative Full-Text RSS service URL
// ----------------------
// This option is to offer very simple load distribution for the service.
// If you've set up another instance of the Full-Text RSS service on a different
// server, you can enter its full URL here. 
// E.g. 'http://my-other-server.org/full-text-rss/makefulltextfeed.php'
// If you specify a URL here, 50% of the requests to makefulltextfeed.php on
// this server will be redirected to the URL specified here.
$options->alternative_url = '';

// Cache directory level
// ----------------------
// Spread cache files over different directories (only used if caching is enabled).
// Used to prevent large number of files in one directory.
// This corresponds to Zend_Cache's hashed_directory_level
// see http://framework.zend.com/manual/en/zend.cache.backends.html
// It's best not to change this if you're unsure.
$options->cache_directory_level = 0;

// Cache cleanup
// -------------
// 0 = script will not clean cache (rename cachecleanup.php and use it for scheduled (e.g. cron) cache cleanup)
// 1 = clean cache everytime the script runs (not recommended)
// 100 = clean cache roughly once every 100 script runs
// x = clean cache roughly once every x script runs
// ...you get the idea :)
$options->cache_cleanup = 100;

/////////////////////////////////////////////////
/// DEPRECATED OPTIONS
/// THESE OPTIONS MIGHT CHANGE IN VERSION 3.0
/// WE RECOMMEND YOU DO NOT USE THEM
/////////////////////////////////////////////////

// Extraction pattern (deprecated)
// Site configuration files offer a better, 
// more flexible solution - please use those instead.
// ---------------------------------
// Specify what should get extracted
// Possible values:
// Auto detect: 'auto'
// Custom: css string (e.g. 'div#content')
// Element within auto-detected block: 'auto ' + css string (e.g. 'auto p')
// User decides: 'user' (same as 'auto' but CSS selector can be passed in query, e.g. &what=.content)
$options->extraction_pattern = 'user';

// Restrict service (deprecated)
// -----------------------------
// Set this to true if you'd like certain features
// to be available only to key holders.
// Affected features:
// * Link handling (disabled for non-key holders if set to true)
// * Cache time (20 minutes for non-key holders if set to true)
$options->restrict = false;

// Message to prepend (with API key) (deprecated)
// ----------------------
// HTML to insert at the beginning of each feed item when a valid API key is supplied.
$options->message_to_prepend_with_key = '';

// Message to append (with API key) (deprecated)
// ----------------------
// HTML to insert at the end of each feed item when a valid API key is supplied.
$options->message_to_append_with_key = '';

// Error message when content extraction fails (with API key) (deprecated)
// ----------------------
$options->error_message_with_key = '[unable to retrieve full-text content]';

/////////////////////////////////////////////////
/// DO NOT CHANGE ANYTHING BELOW THIS ///////////
/////////////////////////////////////////////////

if (!defined('_FF_FTR_VERSION')) define('_FF_FTR_VERSION', '2.9.5');

if ((basename(__FILE__) == 'config.php') && (file_exists(dirname(__FILE__).'/custom_config.php'))) {
	require_once(dirname(__FILE__).'/custom_config.php');
}