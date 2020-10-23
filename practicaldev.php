<?php

namespace Plugins\Practicaldev;

use \Typemill\Plugin;
use Typemill\Models\WriteYaml;

class Practicaldev extends Plugin
{
    public static function getSubscribedEvents()
    {
		return array(
			'onTwigLoaded'      	=> 'onTwigLoaded',
			'onPagePublished' 		=> 'onPagePublished',
	#		'onPageUnpublished' 	=> 'onPageUnpublished',  	// sync not wanted in all cases
	#		'onPageDeleted' 		=> 'onPageDeleted',			// sync not wanted in all cases
		);
    }

    public function onTwigLoaded()
    {
        $this->addEditorJS('/practicaldev/js/tab.js');
    }
	
	public function onPagePublished($article)
	{
		$page 		= $article->getData();
		
		$content 	= $page['content'];
		$meta 		= $page['meta'];
		$item 		= $page['item'];

		# only publish if crosspost is activated
		if(isset($meta['devto']) && isset($meta['devto']['crosspost']))
		{
			$title 			= false;
			$body_markdown 	= false;
			$published 		= false;

			# publish live if crosspost is active and published is choosen
			if($meta['devto']['crosspost'] && isset($meta['devto']['status']) && $meta['devto']['status'] == 'published' )
			{
				$published 	= true;
			}
			else
			{
				# otherwise set it to draft
				$meta['devto']['status'] = 'draft';
			}

			if($content[0] == '#')
			{
				$contentParts 	= explode("\n", $content, 2);
				$title 			= trim($contentParts[0],  "# \t\n\r\0\x0B");
				$body_markdown 	= trim($contentParts[1]);
				$baseUrl 		= $this->getBaseUrl();

				# we have to rewrite the image-sources here
		        $text = str_replace(array("\r\n", "\r"), "\n", $body_markdown);

		        # remove surrounding line breaks
		        $text = trim($text, "\n");

		        # split text into lines
		        $lines = explode("\n", $text);

		        foreach($lines as $key => $line)
		        {
		        	# search for blockImages
		        	if(isset($line[1]) && $line[0] == '!' && $line[1] == '[')
		        	{
        				if(preg_match('/\[((?:[^][]++|(?R))*+)\]/', $line, $matches))
				        {
				            $alt = $matches[0];

				            $remainder = substr($line, strlen($alt)+1);
						}
						else
						{
							continue;
						}
				        if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches))
				        {
							$src = $matches[1];
				        }
				        else
				        {
				        	continue;
				        }
				        $line = '!' . $alt . '(' . $baseUrl . '/' . $src . ')';

				        $lines[$key] = $line;
		        	}
		        }
		       	$body_markdown = implode("\n", $lines);
			}

			$settings 	= $this->getSettings();
			$apikey 	= isset($settings['plugins']['practicaldev']['apikey']) ? $settings['plugins']['practicaldev']['apikey'] : false;

			if($apikey && $title && $title != '' && $body_markdown && $body_markdown != '')
			{
				$data = [
					'article' => [
						'title' 		=> $title,
						'published' 	=> $published,
						'body_markdown'	=> $body_markdown,
						'tags' 			=> [$meta['devto']['tags']],
						'series' 		=> $meta['devto']['series'],
						'canonical_url'	=> $item->urlAbs
					]
				];

				$jsondata = json_encode($data);

				# if article exists already
				if(isset($meta['devto']['response']['id']))
				{
					/* you could check for the article again
					$options = array (
	        			'http' => array ('method' => 'GET')
	        		);

					$context = stream_context_create($options);

					$result = file_get_contents('https://dev.to/api/articles/'. $meta['devto']['response']['id'], false, $context);
					*/

					# make PUT request to dev.to API here
					$options = array (
	        			'http' => array (
	            			'method' 	=> 'PUT',
	       			        'ignore_errors' => true,
	            			'header'	=> "Content-Type: application/json\r\n" .
						                    "Accept: application/json\r\n" .
						                    "Connection: close\r\n" .
						                    "Content-length: " . strlen($jsondata) . "\r\n" .
	                						"api-key: " . $apikey,
	            			'content' 	=> $jsondata
						)
	        		);

					$context = stream_context_create($options);

					$response = file_get_contents('https://dev.to/api/articles/' . $meta['devto']['response']['id'], false, $context);
				}
				else
				{
					# make POST request to dev.to API here
					$options = array (
	        			'http' => array (
	            			'method' 	=> 'POST',
	       			        'ignore_errors' => true,
	            			'header'	=> "Content-Type: application/json\r\n" .
						                    "Accept: application/json\r\n" .
						                    "Connection: close\r\n" .
						                    "Content-length: " . strlen($jsondata) . "\r\n" .
	                						"api-key: " . $apikey,
	            			'content' 	=> $jsondata
						)
	        		);

					$context = stream_context_create($options);

					$response = file_get_contents('https://dev.to/api/articles', false, $context);
				}

				$response = json_decode($response,true);

				# we do not need this
				if(isset($response['body_html'])){ unset($response['body_html']); }
				if(isset($response['body_markdown'])){ unset($response['body_markdown']); }

				# write response into the meta-file...
				$meta['devto']['response'] = $response;

				# store the metadata
				$writeYaml = new WriteYaml();
				$writeYaml->updateYaml($settings['contentFolder'], $item->pathWithoutType . '.yaml', $meta);
			}
		}

		$article->setData(['content' => $content, 'meta' => $meta, 'item' => $item]);
	}

	# dirty, improve in version 2 and use slim baseUrl
	private function getBaseUrl()
	{
	 	return sprintf(
	    	"%s://%s",
	    	isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', 
	    	$_SERVER['SERVER_NAME']
	    );
	}
}