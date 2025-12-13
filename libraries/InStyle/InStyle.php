<?php
	/**
	* InStyle
	* Embedded CSS to Inline CSS Converter Class
	* @version 0.1
	* @updated 09/18/2009
	* 
	* @author David Lim
	* @email miliak@orst.edu
	* @link http://www.davidandjennilyn.com	
	* @acknowledgements Simple HTML Dom
	*/
	class InStyle {

		function convert($document) {

			// Extract the CSS
			preg_match('/<style[^>]+>(?<css>[^<]+)<\/style>/s', $document, $matches);

			// Strip out extra newlines and tabs from CSS
			$css = preg_replace("/[\n\r\t]+/s", "", $matches['css']);

		// Returns the css after removing media queries
		$refactoredCss = $this->findAndRemoveMediaQueries($css);

		// Extract each CSS declaration
		preg_match_all('/([a-zA-Z0-9_ ,#\.]+){([^}]+)}/s', $refactoredCss, $rules, PREG_SET_ORDER);
		// For each CSS declaration, explode the selector and declaration into an array
		// Array index 1 is the CSS selector
		// Array index 2 is the CSS rule(s)
		foreach ($rules as $rule) {
				$styles[trim($rule['1'])] = $styles[trim($rule['1'])].trim($rule['2']);
		}

		// DEBUG: Show selector and declaration
		if ($debug) {
			echo '<pre>';
				foreach ($styles as $selector=>$styling) {
				echo $selector . ':<br>';
				echo $styling . '<br/><br/>';
			}
			echo '</pre><hr/>';
		}
		$html_dom = new simple_html_dom();
		// Load in the HTML without the head and style definitions
		$html_dom->load($document); // Retaining styles without removing from head tag
		// For each style declaration, find the selector in the HTML and add the inline CSS
		if (!empty($styles)) {
				foreach ($styles as $selector=>$styling) {
				foreach ($html_dom->find($selector) as $element) {
					$elementStyle = $element->style;
						if(substr($elementStyle, -1) == ';'){
						$element->style .= $styling;
					} else {
							$element->style .= ";".$styling;
					}
						}
				}
			$inline_css_message = $html_dom->save();
			return $inline_css_message;
		}
		return false;
	}

	function emailsInlineConversion($document) {
		
		//TODO: After updating Database tables to utf8mb remove below code.
		//http://redmine.vtiger.in/issues/57411
        // Commented removing emoji function as we are supporting emoji characters
        // $document = Vtiger_Functions::removeEmoji($document);
		
		//Check whether html or not
			if(strpos($document, '>') && $document != strip_tags($document)) {
			//If is there any Unclosed and Extra tags is there in content, then It is formatting in correct way.
			$document = str_replace('&nbsp;', '@nbsp;', $document);
		    $document = str_replace("\xc2\xa0",' ',  $document);
            
        //PT69706:Here in the below code we are removing the comments contents but in outlook we are using condition in such syntex, so commenting the replace function.
        // ref:-https://templates.mailchimp.com/development/css/outlook-conditional-css/    
            
			// Removing comment tags from content.
			//$document = preg_replace('/<!--(.*)-->/Uis', '', $document);

			$dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $document);
            $nodeHead = $dom->getElementsByTagName("head");
            $metaList = $dom->getElementsByTagName("meta");
			$convertBig5=false;
            if ($nodeHead->length > 0) {
                $completeAttrString = array();
                //storing content of meta tags
                for ($i = $metaList->length - 1; $i >= 0; $i--) {
                    $nodeMeta = $dom->getElementsByTagName('meta')->item($i);
                    if ($nodeMeta->hasAttributes()) {
                        $attrNameVal = array();
                        foreach ($nodeMeta->attributes as $attr) {
                            $name = $attr->nodeName;
                            $value = $attr->nodeValue;
							//https://stackoverflow.com/questions/8169278/firefox-and-utf-16-encoding
							if(stripos($value, 'utf-16') !== false){
								$value = str_ireplace('utf-16', 'utf-8',$value);
							} elseif(stripos($value, 'big5') !== false){
								$convertBig5 = true;
								$value = str_ireplace('big5', 'utf-8',$value);
							}
                            $attrNameVal[] = array('name' => $name, 'value' => $value);
                        }
                        $completeAttrString[] = $attrNameVal;
                    }
                }
                //removing meta tags
                while ($metaList->length > 0) {
                    $meta = $metaList->item(0);
                    $meta->parentNode->removeChild($meta);
                }
                //creating new meta tags with previous content and appending to head tag
                foreach ($completeAttrString as $attrNameVals) {
                    $element = $dom->createElement('meta');
                    foreach ($attrNameVals as $attrNameVal) {
                        try {
                            $element->setAttribute($attrNameVal['name'], $attrNameVal['value']);
                        } catch (Exception $ex) {
                            
                        }
					}
                    $nodeHead = $dom->getElementsByTagName('head')->item(0);
                    $nodeHead->appendChild($element);
                }
            } else {
                while ($metaList->length > 0) {
                    $meta = $metaList->item(0);
                    $meta->parentNode->removeChild($meta);
                }
            }
			//#5066121 - Added check for supporting big5 charset
			if(!$convertBig5){
                @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' .$document);
				$document = rawurldecode($dom->saveHTML());
			}
            //https://www.w3.org/International/questions/qa-html-encoding-declarations.en
				@$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' .$document);
			//http://stackoverflow.com/questions/21350192/php-domdocument-loadhtml-turns-into-url-encoding24-unexpectedly
			$document = rawurldecode($dom->saveHTML());

			$document = str_replace('@nbsp;', '&nbsp;', $document);
			//Removing script tags from content,using decode_html to convert the encoded script tags
			$formattedContent = preg_replace('#<script(.*?)>(.*?)</script>#is', '', decode_html(decode_html($document)));

			// Extract the CSS
			preg_match('/<style?[^>]+>(?<css>[^<]+)<\/style>/s', $document, $matches);

				if($matches) {
				// Strip out extra newlines and tabs from CSS
				$css = preg_replace("/[\n\r\t]+/s", "", $matches['css']);

				// Returns the css after removing media queries
				$refactoredCss = $this->findAndRemoveMediaQueries($css);

				// Extract each CSS declaration
				preg_match_all('/([a-zA-Z0-9_ ,#-:\.]+){([^}]+)}/s', $refactoredCss, $rules, PREG_SET_ORDER);
				// For each CSS declaration, explode the selector and declaration into an array
				// Array index 1 is the CSS selector
				// Array index 2 is the CSS rule(s)
				foreach ($rules as $rule) {
						$styles[trim($rule['1'])] = $styles[trim($rule['1'])].trim($rule['2']);
				}

				// DEBUG: Show selector and declaration
				if ($debug) {
					echo '<pre>';
						foreach ($styles as $selector=>$styling) {
						echo $selector . ':<br>';
						echo $styling . '<br/><br/>';
					}
					echo '</pre><hr/>';
				}
				$html_dom = new simple_html_dom();
				// Load in the HTML without the head and style definitions
				//PT80128:: bydefault we were strip out the \r \n thats updating the actual view.
				$html_dom->load($document,true,false); // Retaining styles without removing from head tag

				$bodyStyleTag = '';
				// For each style declaration, find the selector in the HTML and add the inline CSS
				if (!empty($styles)) {
						foreach ($styles as $selector=>$styling) {
							// this fix is workaround for #58277
						$selectProperties = explode(' ',$selector);
						if(sizeof($selectProperties) != 0){
							foreach($selectProperties as $key => $property){
								if(strpos($property, ".") === false){
									continue;
								}
								$results = explode('.',$property);
								//Id from selector
								$selectorId = preg_grep('/\#(\w+)/i', $results);
								$array_key = array_search($selectorId[0], $results);
								if ($array_key !== false || !is_bool($array_key)) {
									unset($results[$array_key]);
								}
								if(sizeof($results) > 1){ //if selector has more than one class of same element
									//returns empty if selector has clases like .class1.class2 .. bug reported - https://sourceforge.net/p/simplehtmldom/bugs/3/
									$results = '[class='.trim(implode(' ', $results)).']';
									$selectProperties[$key] = $results;
								}
								if($selectorId[0]){
									$selectProperties[$key] = $selectorId[0].$selectProperties[$key];
								}
							}
							$selector = implode(' ',$selectProperties);
						}
						foreach ($html_dom->find($selector) as $element) {
							$elementStyle = $element->style;
								if(substr($elementStyle, -1) == ';'){
								$element->style .= $styling;
							} else {
									$element->style .= ";".$styling;
							}

								if($selector == 'body'){
								$bodyStyleTag = $element->style;
							}
						}
					}
					$inline_css_message = $html_dom->save();

					//Removing style tags from content.
					$formattedContent = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $inline_css_message);
				}
			}
			
			$bodyTagAttributes = '';
			
			$html_dom = new simple_html_dom();
			// Load in the HTML without the head and style definitions
			$html_dom->load($document);
			//Find only body tag
			$bodyTagElement = $html_dom->getElementByTagName('body');
			//get its attributes
			$bodyAttributes = $bodyTagElement->attr;
			if($bodyAttributes && is_array($bodyAttributes)){
				foreach($bodyAttributes as $key => $value){
					if($key !== 'style'){
						$bodyTagAttributes .= "$key=".'"'.$value.'"  ';
					}
				}
			}
			
			
            //If there are content after closing body tag that is getting removed, so putting closing body tag at the end.
            $formattedContent = str_replace('</body>', '', $formattedContent).'</body>';
			//Is there any inline styling for body tag, placing body in one <div> and applying styling to that <div>. 
			//Due to this, body styling is not effect the other eamils. 
			preg_match("/<body[^>]*>(.*?)<\/body>/is", $formattedContent, $matches);
			$formattedContent = str_replace('<html>', '', $matches[1]);
			$formattedContent = str_replace('</html>', '', $formattedContent);
			//https://github.com/mike42/escpos-php/issues/37 To support Chinese characters with big5 or GBK charset
			if($convertBig5){
				$formattedContent = iconv("UTF-8","GBK//IGNORE",$formattedContent);
			}
			$formattedContent = '<div style="' . $bodyStyleTag . '"  '.$bodyTagAttributes.'  >' . $formattedContent . '</div>';
			return $formattedContent;
		}
		return $document;
	}
	
	function convertStylesToInlineCss($document) {
		//converts styles to inline css
		$inline_css_message = $this->emailsInlineConversion($document);
		//Removing script tags from content.
		$inline_css_message = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $inline_css_message);
		return $inline_css_message;
	}
	
	/**
	 * Function to find and remove media queries and return css without media queries
	 * @param type $css
	 * @return type
	 */
	function findAndRemoveMediaQueries($css){
		$mediaBlocks = array();

		$start = 0;
		$cssLength = strlen($css);
		while (($start = strpos($css, "@media", $start)) !== false) {
// stack to manage brackets
			$s = array();

// get the first opening bracket
			$i = strpos($css, "{", $start);

// if $i is false, then there is probably a css syntax error
			if ($i !== false) {
// push bracket onto stack
				array_push($s, $css[$i]);
// move past first bracket
				$i++;
				while (!empty($s)) {
// if the character is an opening bracket, push it onto the stack, otherwise pop the stack
					if ($css[$i] == "{") {
						array_push($s, "{");
						$i++;
					} elseif ($css[$i] == "}") {
						array_pop($s);
						$i++;
					} else if ($i >= $cssLength) {
//If position of i reaches end of css and still didn't find 
//closing brace, then remove the existing value from array $s
						array_pop($s);
					} else {
						$i++;
					}
				}

// cut the media block out of the css and store
				$mediaBlocks[] = substr($css, $start, ($i) - $start);

// set the new $start to the end of the block
				$start = $i;
				} else {
					// If no starting postion { found, then this loop need to be break. 
					break; 
			}
		}
		foreach ($mediaBlocks as $value) {
			$css = str_replace($value, '', $css);
		}
		return $css;
	}
	//Function to replace body default style to only new content
	function defaultFontStyleModification($emailContent){
		$formattedBody = $emailContent;
		$html_dom = new simple_html_dom();
		$html_dom->load($emailContent);
		if($html_dom->find('#mailExtraContent')){
			foreach($html_dom->find('#mailExtraContent') as $element){
				$extraContent = $element->innertext;
				$element->outertext = '';
			}
			$outerDiv = $html_dom->find('div',0);
			$newEmailContent = $outerDiv->outertext;
			$formattedBody = $newEmailContent.$extraContent;
		}
		return $formattedBody;
	}
}

/* End of file inline_css.php */
