<?php
/**
 * Content Extractor
 * 
 * Uses patterns specified in site config files and auto detection (hNews/PHP Readability) 
 * to extract content from HTML files.
 * 
 * @version 0.9
 * @date 2013-07-22
 * @author Keyvan Minoukadeh
 * @copyright 2013 Keyvan Minoukadeh
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPL v3
 */

class ContentExtractor
{
	protected $html;
	protected $config;
	protected $title;
	protected $author = array();
	protected $language;
	protected $date;
	protected $body;
	protected $success = false;
	public $allowedParsers = array('libxml', 'html5lib');
	public $fingerprints = array();
	public $readability;
	public $debug = false;

	function __construct($path, $fallback=null) {
		SiteConfig::set_config_path($path, $fallback);	
	}
	
	protected function debug($msg) {
		if ($this->debug) {
			$mem = round(memory_get_usage()/1024, 2);
			$memPeak = round(memory_get_peak_usage()/1024, 2);
			echo '* ',$msg;
			echo ' - mem used: ',$mem," (peak: $memPeak)\n";	
			ob_flush();
			flush();
		}
	}
	
	public function reset() {
		$this->html = null;
		$this->readability = null;
		$this->config = null;
		$this->title = null;
		$this->body = null;
		$this->author = array();
		$this->language = null;
		$this->date = null;
		$this->success = false;
	}

	public function findHostUsingFingerprints($html) {
		$this->debug('Checking fingerprints...');
		$head = substr($html, 0, 8000);
		foreach ($this->fingerprints as $_fp => $_fphost) {
			$lookin = 'html';
			if (is_array($_fphost)) {
				if (isset($_fphost['head']) && $_fphost['head']) {
					$lookin = 'head';
				}
				$_fphost = $_fphost['hostname'];
			}
			if (strpos($$lookin, $_fp) !== false) {
				$this->debug("Found match: $_fphost");
				return $_fphost;
			}
		}
		return false;
	}
	
	// returns true on success, false on failure
	// $smart_tidy indicates that if tidy is used and no results are produced, we will
	// try again without it. Tidy helps us deal with PHP's patchy HTML parsing most of the time
	// but it has problems of its own which we try to avoid with this option.
	public function process($html, $url, $smart_tidy=true) {
		$this->reset();
		// extract host name
		$host = @parse_url($url, PHP_URL_HOST);
		if (!($this->config = SiteConfig::build($host))) {
			// no match, check HTML for fingerprints
			if (!empty($this->fingerprints) && ($_fphost = $this->findHostUsingFingerprints($html))) {
				$this->config = SiteConfig::build($_fphost);
			}
			unset($_fphost);
			if (!$this->config) {
				// no match, so use defaults
				$this->config = new SiteConfig();
			}
		}
		// store copy of config in our static cache array in case we need to process another URL
		SiteConfig::add_to_cache($host, $this->config);
		
		// do string replacements
		foreach ($this->config->replace_string as $_repl) {
			$html = str_replace($_repl[0], $_repl[1], $html);
		}
		unset($_repl);
		
		// load and parse html
		$_parser = $this->config->parser;
		if (!in_array($_parser, $this->allowedParsers)) {
			$this->debug("HTML parser $_parser not listed, using libxml instead");
			$_parser = 'libxml';
		}
		$this->debug("Attempting to parse HTML with $_parser");
		$this->readability = new Readability($html, $url, $_parser, $this->config->tidy && $smart_tidy);
		$tidied = $this->readability->tidied;
		
		
		// we use xpath to find elements in the given HTML document
		// see http://en.wikipedia.org/wiki/XPath_1.0
		$xpath = new DOMXPath($this->readability->dom);
		
		// skip entries (using xpath expressions)
		foreach ($this->config->skip_entry as $pattern) {
			$elems = @$xpath->evaluate($pattern, $this->readability->dom);
			// check for matches
			if (is_string($elems) || ($elems instanceof DOMNodeList && $elems->length > 0)) {
				$this->debug('Skipping entry on pattern.');
				$this->debug("...XPath match: $pattern");
				return false;
			}
		}

		// try to get title
		foreach ($this->config->title as $pattern) {
			// $this->debug("Trying to get title $pattern");
			$elems = @$xpath->evaluate($pattern, $this->readability->dom);
			if (is_string($elems)) {
				$this->title = trim($elems);
				$this->debug('Title expression evaluated as string: '.$this->title);
				$this->debug("...XPath match: $pattern");
				break;
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				$this->title = $elems->item(0)->textContent;
				$this->debug('Title matched: '.$this->title);
				$this->debug("...XPath match: $pattern");
				// remove title from document
				try {
					@$elems->item(0)->parentNode->removeChild($elems->item(0));
				} catch (DOMException $e) {
					// do nothing
				}
				break;
			}
		}
		
		// try to get author (if it hasn't already been set)
		if (empty($this->author)) {
			foreach ($this->config->author as $pattern) {
				$elems = @$xpath->evaluate($pattern, $this->readability->dom);
				if (is_string($elems)) {
					if (trim($elems) != '') {
						$this->author[] = trim($elems);
						$this->debug('Author expression evaluated as string: '.trim($elems));
						$this->debug("...XPath match: $pattern");
						break;
					}
				} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
					foreach ($elems as $elem) {
						if (!isset($elem->parentNode)) continue;
						$this->author[] = trim($elem->textContent);
						$this->debug('Author matched: '.trim($elem->textContent));
					}
					if (!empty($this->author)) {
						$this->debug("...XPath match: $pattern");
						break;
					}
				}
			}
		}
		
		// try to get language
		$_lang_xpath = array('//html[@lang]/@lang', '//body[@lang]/@lang', '//meta[@name="DC.language"]/@content');
		foreach ($_lang_xpath as $pattern) {
			$elems = @$xpath->evaluate($pattern, $this->readability->dom);
			if (is_string($elems)) {
				if (trim($elems) != '') {
					$this->language = trim($elems);
					$this->debug('Language matched: '.$this->language);
					break;
				}
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				foreach ($elems as $elem) {
					if (!isset($elem->parentNode)) continue;
					$this->language = trim($elem->textContent);
					$this->debug('Language matched: '.$this->language);					
				}
				if ($this->language) break;
			}
		}
		
		// try to get date
		foreach ($this->config->date as $pattern) {
			$elems = @$xpath->evaluate($pattern, $this->readability->dom);
			if (is_string($elems)) {
				$this->date = strtotime(trim($elems, "; \t\n\r\0\x0B"));				
			} elseif ($elems instanceof DOMNodeList && $elems->length > 0) {
				$this->date = $elems->item(0)->textContent;
				$this->date = strtotime(trim($this->date, "; \t\n\r\0\x0B"));
				// remove date from document
				// $elems->item(0)->parentNode->removeChild($elems->item(0));
			}
			if (!$this->date) {
				$this->date = null;
			} else {
				$this->debug('Date matched: '.date('Y-m-d H:i:s', $this->date));
				$this->debug("...XPath match: $pattern");
				break;
			}
		}

		// strip elements (using xpath expressions)
		foreach ($this->config->strip as $pattern) {
			$elems = @$xpath->query($pattern, $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Stripping '.$elems->length.' elements (strip)');
				for ($i=$elems->length-1; $i >= 0; $i--) {
					$elems->item($i)->parentNode->removeChild($elems->item($i));
				}
			}
		}
		
		// strip elements (using id and class attribute values)
		foreach ($this->config->strip_id_or_class as $string) {
			$string = strtr($string, array("'"=>'', '"'=>''));
			$elems = @$xpath->query("//*[contains(@class, '$string') or contains(@id, '$string')]", $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Stripping '.$elems->length.' elements (strip_id_or_class)');
				for ($i=$elems->length-1; $i >= 0; $i--) {
					$elems->item($i)->parentNode->removeChild($elems->item($i));
				}
			}
		}
		
		// strip images (using src attribute values)
		foreach ($this->config->strip_image_src as $string) {
			$string = strtr($string, array("'"=>'', '"'=>''));
			$elems = @$xpath->query("//img[contains(@src, '$string')]", $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug('Stripping '.$elems->length.' image elements');
				for ($i=$elems->length-1; $i >= 0; $i--) {
					$elems->item($i)->parentNode->removeChild($elems->item($i));
				}
			}
		}
		// strip elements using Readability.com and Instapaper.com ignore class names
		// .entry-unrelated and .instapaper_ignore
		// See https://www.readability.com/publishers/guidelines/#view-plainGuidelines
		// and http://blog.instapaper.com/post/730281947
		$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' entry-unrelated ') or contains(concat(' ',normalize-space(@class),' '),' instapaper_ignore ')]", $this->readability->dom);
		// check for matches
		if ($elems && $elems->length > 0) {
			$this->debug('Stripping '.$elems->length.' .entry-unrelated,.instapaper_ignore elements');
			for ($i=$elems->length-1; $i >= 0; $i--) {
				$elems->item($i)->parentNode->removeChild($elems->item($i));
			}
		}
		
		// strip elements that contain style 'display: none' or 'visibility:hidden'
		$elems = @$xpath->query("//*[contains(@style,'display:none') or contains(@style,'visibility:hidden')]", $this->readability->dom);
		// check for matches
		if ($elems && $elems->length > 0) {
			$this->debug('Stripping '.$elems->length.' elements with inline display:none or visibility:hidden style');
			for ($i=$elems->length-1; $i >= 0; $i--) {
				$elems->item($i)->parentNode->removeChild($elems->item($i));
			}
		}
		
		// try to get body
		foreach ($this->config->body as $pattern) {
			$elems = @$xpath->query($pattern, $this->readability->dom);
			// check for matches
			if ($elems && $elems->length > 0) {
				$this->debug("Matched $elems->length content element(s)");
				$this->debug("...XPath match: $pattern");
				if ($elems->length == 1) {				
					$this->body = $elems->item(0);
					// prune (clean up elements that may not be content)
					if ($this->config->prune) {
						$this->debug('...pruning content');
						$this->readability->prepArticle($this->body);
					}
					break;
				} else {
					$this->body = $this->readability->dom->createElement('div');
					if ($this->config->prune) {
						$this->debug('...pruning content');
					}
					$len = 0;
					foreach ($elems as $elem) {
						if (!isset($elem->parentNode)) continue;
						$isDescendant = false;
						foreach ($this->body->childNodes as $parent) {
							if ($this->isDescendant($parent, $elem)) {
								$isDescendant = true;
								break;
							}
						}
						if ($isDescendant) {
							$this->debug('...element is child of another body element, skipping.');
						} else {
							// prune (clean up elements that may not be content)
							if ($this->config->prune) {
								$this->readability->prepArticle($elem);
							}
							if ($elem) {
								$len++;
								$this->body->appendChild($elem);
							}
						}
					}
					$this->debug('...'.$len.' elements added to body');
					unset($len);
					if ($this->body->hasChildNodes()) break;
				}
			}
		}		
		
		// auto detect?
		$detect_title = $detect_body = $detect_author = $detect_date = false;
		// detect title?
		if (!isset($this->title)) {
			if (empty($this->config->title) || $this->config->autodetect_on_failure) {
				$detect_title = true;
			}
		}
		// detect body?
		if (!isset($this->body)) {
			if (empty($this->config->body) || $this->config->autodetect_on_failure) {
				$detect_body = true;
			}
		}
		// detect author?
		if (empty($this->author)) {
			if (empty($this->config->author) || $this->config->autodetect_on_failure) {
				$detect_author = true;
			}
		}
		// detect date?
		if (!isset($this->date)) {
			if (empty($this->config->date) || $this->config->autodetect_on_failure) {
				$detect_date = true;
			}
		}

		// check for hNews
		if ($detect_title || $detect_body) {
			// check for hentry
			$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' hentry ')]", $this->readability->dom);
			if ($elems && $elems->length > 0) {
				$this->debug('hNews: found hentry');
				$hentry = $elems->item(0);
				
				if ($detect_title) {
					// check for entry-title
					$elems = @$xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' entry-title ')]", $hentry);
					if ($elems && $elems->length > 0) {
						$this->debug('hNews: found entry-title');
						$this->title = $elems->item(0)->textContent;
						$this->debug('hNews: found entry-title: '.$this->title);
						// remove title from document
						$elems->item(0)->parentNode->removeChild($elems->item(0));
						$detect_title = false;
					}
				}
				
				if ($detect_date) {
					// check for time element with pubdate attribute
					$elems = @$xpath->query(".//time[@pubdate] | .//abbr[contains(concat(' ',normalize-space(@class),' '),' published ')]", $hentry);
					if ($elems && $elems->length > 0) {
						$this->debug('hNews: found publication date');
						$this->date = strtotime(trim($elems->item(0)->textContent));
						// remove date from document
						//$elems->item(0)->parentNode->removeChild($elems->item(0));
						if ($this->date) {
							$detect_date = false;
						} else {
							$this->date = null;
						}
					}
				}

				if ($detect_author) {
					// check for time element with pubdate attribute
					$elems = @$xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' vcard ') and (contains(concat(' ',normalize-space(@class),' '),' author ') or contains(concat(' ',normalize-space(@class),' '),' byline '))]", $hentry);
					if ($elems && $elems->length > 0) {
						$author = $elems->item(0);
						$fn = @$xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' fn ')]", $author);
						if ($fn && $fn->length > 0) {
							foreach ($fn as $_fn) {
								if (trim($_fn->textContent) != '') {
									$this->author[] = trim($_fn->textContent);
									$this->debug('hNews: found author: '.trim($_fn->textContent));
								}
							}
						} else {
							if (trim($author->textContent) != '') {
								$this->author[] = trim($author->textContent);
								$this->debug('hNews: found author: '.trim($author->textContent));
							}
						}
						$detect_author = empty($this->author);
					}
				}
				
				// check for entry-content.
				// according to hAtom spec, if there are multiple elements marked entry-content,
				// we include all of these in the order they appear - see http://microformats.org/wiki/hatom#Entry_Content
				if ($detect_body) {
					$elems = @$xpath->query(".//*[contains(concat(' ',normalize-space(@class),' '),' entry-content ')]", $hentry);
					if ($elems && $elems->length > 0) {
						$this->debug('hNews: found entry-content');
						if ($elems->length == 1) {
							// what if it's empty? (some sites misuse hNews - place their content outside an empty entry-content element)
							$e = $elems->item(0);
							if (($e->tagName == 'img') || (trim($e->textContent) != '')) {
								$this->body = $elems->item(0);
								// prune (clean up elements that may not be content)
								if ($this->config->prune) {
									$this->debug('Pruning content');
									$this->readability->prepArticle($this->body);
								}
								$detect_body = false;
							} else {
								$this->debug('hNews: skipping entry-content - appears not to contain content');
							}
							unset($e);
						} else {
							$this->body = $this->readability->dom->createElement('div');
							$this->debug($elems->length.' entry-content elems found');
							foreach ($elems as $elem) {
								if (!isset($elem->parentNode)) continue;
								$isDescendant = false;
								foreach ($this->body->childNodes as $parent) {
									if ($this->isDescendant($parent, $elem)) {
										$isDescendant = true;
										break;
									}
								}
								if ($isDescendant) {
									$this->debug('Element is child of another body element, skipping.');
								} else {
									// prune (clean up elements that may not be content)
									if ($this->config->prune) {
										$this->debug('Pruning content');
										$this->readability->prepArticle($elem);
									}								
									$this->debug('Element added to body');									
									$this->body->appendChild($elem);
								}
							}
							$detect_body = false;
						}
					}
				}
			}
		}

		// check for elements marked with instapaper_title
		if ($detect_title) {
			// check for instapaper_title
			$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' instapaper_title ')]", $this->readability->dom);
			if ($elems && $elems->length > 0) {
				$this->title = $elems->item(0)->textContent;
				$this->debug('Title found (.instapaper_title): '.$this->title);
				// remove title from document
				$elems->item(0)->parentNode->removeChild($elems->item(0));
				$detect_title = false;
			}
		}
		// check for elements marked with instapaper_body
		if ($detect_body) {
			$elems = @$xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' instapaper_body ')]", $this->readability->dom);
			if ($elems && $elems->length > 0) {
				$this->debug('body found (.instapaper_body)');
				$this->body = $elems->item(0);
				// prune (clean up elements that may not be content)
				if ($this->config->prune) {
					$this->debug('Pruning content');
					$this->readability->prepArticle($this->body);
				}
				$detect_body = false;
			}
		}
		
		// Find author in rel="author" marked element
		// We only use this if there's exactly one.
		// If there's more than one, it could indicate more than
		// one author, but it could also indicate that we're processing
		// a page listing different articles with different authors.
		if ($detect_author) {
			$elems = @$xpath->query("//a[contains(concat(' ',normalize-space(@rel),' '),' author ')]", $this->readability->dom);
			if ($elems && $elems->length == 1) {
				$author = trim($elems->item(0)->textContent);
				if ($author != '') {
					$this->debug("Author found (rel=\"author\"): $author");
					$this->author[] = $author;
					$detect_author = false;
				}
			}
		}

		// Find date in pubdate marked time element
		// For the same reason given above, we only use this
		// if there's exactly one element.
		if ($detect_date) {
			$elems = @$xpath->query("//time[@pubdate]", $this->readability->dom);
			if ($elems && $elems->length == 1) {
				$this->date = strtotime(trim($elems->item(0)->textContent));
				// remove date from document
				//$elems->item(0)->parentNode->removeChild($elems->item(0));
				if ($this->date) {
					$this->debug('Date found (pubdate marked time element): '.date('Y-m-d H:i:s', $this->date));
					$detect_date = false;
				} else {
					$this->date = null;
				}
			}
		}

		// still missing title or body, so we detect using Readability
		if ($detect_title || $detect_body) {
			$this->debug('Using Readability');
			// clone body if we're only using Readability for title (otherwise it may interfere with body element)
			if (isset($this->body)) $this->body = $this->body->cloneNode(true);
			$success = $this->readability->init();
		}
		if ($detect_title) {
			$this->title = $this->readability->getTitle()->textContent;
			$this->debug("Detected title \"$this->title\"");
		}
		if ($detect_body && $success) {
			$this->debug('Detecting body');
			$this->body = $this->readability->getContent();
			if ($this->body->childNodes->length == 1 && $this->body->firstChild->nodeType === XML_ELEMENT_NODE) {
				$this->body = $this->body->firstChild;
			}
			// prune (clean up elements that may not be content)
			if ($this->config->prune) {
				$this->debug('Pruning content');
				$this->readability->prepArticle($this->body);
			}
		}
		if (isset($this->body)) {
			// remove scripts
			$this->readability->removeScripts($this->body);
			// remove any h1-h6 elements that appear as first thing in the body
			// and which match our title
			if (isset($this->title) && ($this->title != '')) {
				$firstChild = $this->body->firstChild;
				while ($firstChild->nodeType && ($firstChild->nodeType !== XML_ELEMENT_NODE)) {
					$firstChild = $firstChild->nextSibling;
				}
				if (($firstChild->nodeType === XML_ELEMENT_NODE)
					&& in_array(strtolower($firstChild->tagName), array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'))
					&& (strtolower(trim($firstChild->textContent)) == strtolower(trim($this->title)))) {
						$this->body->removeChild($firstChild);
				}
			}
			$this->success = true;
		}
		
		// if we've had no success and we've used tidy, there's a chance
		// that tidy has messed up. So let's try again without tidy...
		if (!$this->success && $tidied && $smart_tidy) {
			$this->debug('Trying again without tidy');
			$this->process($original_html, $url, false);
		}

		return $this->success;
	}
	
	private function isDescendant(DOMElement $parent, DOMElement $child) {
		$node = $child->parentNode;
		while ($node != null) {
			if ($node->isSameNode($parent))	return true;
			$node = $node->parentNode;
		}
		return false;
	}

	public function getContent() {
		return $this->body;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getAuthors() {
		return $this->author;
	}
	
	public function getLanguage() {
		return $this->language;
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function getSiteConfig() {
		return $this->config;
	}
}
?>