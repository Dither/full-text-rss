<?php
class ImageCaching {

	const UPPER_LIMIT = 256000;
	const UPPER_MAX = 3145728;
	const LOWER_LIMIT = 512;
	const LOWER_MIN = 48;

	public $lower_limit_setting = null;
	public $upper_limit_setting = null;

	protected $images = array();
	protected $context_get = array();

	function __construct() {
		$this->lower_limit_setting = self::LOWER_LIMIT;
		$this->upper_limit_setting = self::UPPER_LIMIT;
        $opts = array( 
             'http' => array( 
                 'method'=>"GET",
                 'header'=>"Accept-language: en\r\n" .
                                    "Connection: close\r\n" .
                                    "User-Agent: Opera/9.80 (Windows NT 6.1; Win64; x64; Edition Next) Presto/2.12.388 Version/12.15\r\n"
             ) 
         );
        $this->context_get = stream_context_create($opts);
	}

	public function setAllowedSizes($lower, $upper) {
		if ($lower > $upper) {
			$tmp = $upper; $upper = $lower; $lower = $tmp;
		}
		if ($lower && $lower > self::LOWER_MIN && $lower < self::UPPER_MAX) $this->lower_limit_setting = $lower;
		if ($upper && $upper > self::LOWER_MIN && $upper < self::UPPER_MAX) $this->upper_limit_setting = $upper;
	}

	public function cacheFromString(&$html) {
		$callback = function ($matches) {
			$data_uri = '';
			$url = $matches[1];
			if (!preg_match('!^https?://!i', $url) || strlen($url) < 12) return $matches[0];
			$url = filter_var($url, FILTER_SANITIZE_URL);
			if (!$this->images[$url]){
				$data_uri = $this->getImageDataURI($url);
				if (strlen($data_uri) > 1)
				    $this->images[$url] = $data_uri;
				else 
					return $matches[0];
			} else {
				$data_uri = $this->images[$url];
			}
			if (strlen($data_uri) > 1)
				return '<img src="' . $data_uri . '" alt="' . $matches[1] . '"/>';
			else
				return $matches[0];
		};

		$html = preg_replace_callback('/< *img[^>]+src *= *["\']?([^"\'>]*)[^>]*>/i', $callback, $html);
	}

	public function cacheFromDocument(&$document) {
		$imageNodes = $document->getElementsByTagName('img');
		for ($node = null, $nodeIndex = 0; ($node = $imageNodes->item($nodeIndex)); $nodeIndex++) {
			$url = $node->getAttribute('src');
			$node->removeAttribute('class');
			$node->removeAttribute('id');
			if (!preg_match('!^https?://!i', $url) || strlen($url) < 12) continue;
			$url = filter_var($url, FILTER_SANITIZE_URL);
			$data_uri = '';
			if (!$this->images[$url]){
				$data_uri = $this->getImageDataURI($url);
				if (strlen($data_uri) > 1)
				    $this->images[$url] = $data_uri;
				else 
				    continue;
			} else {
				$data_uri = $this->images[$url];
			}
			if (strlen($data_uri) > 1) {
				$node->setAttribute('src', $data_uri);
				$node->setAttribute('alt', $url);
			}
		}
	}

	protected function getFileSize($image) {
		$contentLength = 0;
		$status = 0;
		$matches = array();

		if (!function_exists('curl_init'))
			return 0;

		$ch = curl_init($image);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 6.1; Win64; x64; Edition Next) Presto/2.12.388 Version/12.15");
		$data = curl_exec($ch);
		curl_close($ch);
		if ($data === false)
			return 0;

		if (preg_match('/^HTTP\/1\.[01] (\d\d\d)/', $data, $matches))
			$status = (int) $matches[1];
		if ($status === 200 && preg_match('/Content-Length: (\d+)/', $data, $matches))
			$contentLength = (int) $matches[1];

		return $contentLength;
	}

	protected function getImageDataURI($image, $mime = '') {
		$image = filter_var($image, FILTER_SANITIZE_URL);
		if (!preg_match('!^https?://!i', $image))
			return '';

		$ext = substr(strrchr($image, ".") , 1, 3);
		if (strcasecmp($ext, 'jpg') === 0 || strcasecmp($ext, 'png') === 0 || strcasecmp($ext, 'jpe') === 0 || strcasecmp($ext, 'gif') === 0) {
			$size = $this->getFileSize($image);
			if ($size < $this->lower_limit_setting || $size > $this->upper_limit_setting)
				return '';
			$content = @file_get_contents($image, false, $this->context_get);
			if (function_exists('finfo_open')) 
				$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);
			if (!preg_match('/^image/i', $mime)) return '';
			return 'data:' . $mime . ';base64,' . base64_encode($content);
		}
		return '';
	}
}
?>