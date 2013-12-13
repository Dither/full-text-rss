Full-Text RSS
=============

### NOTE

This is a our public version of Full-Text RSS available to download for free from <http://code.fivefilters.org>.

For best extraction results, and to help us sustain the project, you can purchase the most up-to-date version at <http://fivefilters.org/content-only/#download> - so if you like this free version, please consider supporting us by purchasing the latest release. 

If you have no need for the latest release, but would still like to contribute something, you can donate via [Gittip](https://www.gittip.com/fivefilters/) or [Flattr](https://flattr.com/profile/k1m).

### About

See <http://fivefilters.org/content-only/> for a description of the code.

### Installation

1. Extract the files in this ZIP archive to a folder on your computer.

2. FTP the files up to your server

3. Access index.php through your browser. E.g. http://example.org/full-text-rss/index.php

4. Enter a URL in the form field to test the code

5. If you get an RSS feed with full-text content, all is working well. :)

### Configuration (optional)

1. Save a copy of config.php as custom_config.php and edit custom_config.php

2. If you decide to enable caching, make sure the cache folder (and its 2 sub folders) is writable. (You might need to change the permissions of these folders to 777 through your FTP client.)

### Site-specific extraction rules

This free version does not contain the site config files we include with purchased copies, but these are now all available [online](https://github.com/fivefilters/ftr-site-config). If you'd like to keep yours up to date using Git, follow the steps below:

1. Change into the site_config/standard/ folder
2. Delete everything in there
3. Using the command line, enter: `git clone https://github.com/fivefilters/ftr-site-config.git .`
4. Git should now download the latest site config files for you.
5. To update the site config files again, you can simply run `git pull` from the directory.

### Code example

If you're developing an application which requires content extraction, you can call Full-Text RSS as a web service from within your application. Here's how to do it in PHP:

	<?php
	// $ftr should be URL where you installed this application
	$ftr = 'http://example.org/full-text-rss/';
	$article = 'http://www.bbc.co.uk/news/world-europe-21936308';

	$request = $ftr.'makefulltextfeed.php?format=json&url='.urlencode($article);

	// Send HTTP request and get response
	$result = @file_get_contents($request);

	if (!$result) die('Failed to fetch content');

	$json = @json_decode($result);

	if (!$json) die('Failed to parse JSON');

	// What do we have?
	// var_dump($json);
	
	// Items?
	// var_dump($json->rss->channel->item);

	$title = $json->rss->channel->item->title;
	// Note: this works when you're processing an article.
	// If the input URL is a feed, ->item will be an array.

	echo $title;

### Different language?

Although we don't have examples in other programming languages, the essential steps should be:

1. Construct the request URL using URL where you installed Full-Text RSS and the article or feed URL (see $ftr, $article, $request in example above).

2. Fetch the resulting URL using an HTTP GET request.

3. Parse the HTTP response body as JSON and grab what you need.