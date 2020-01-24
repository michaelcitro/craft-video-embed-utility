<?php
	
	namespace Craft;
	
	use Twig_Extension;
	use Twig_Filter_Method;
	
	define("VIMEO",'vimeo.com');
	define("YOUTUBE",'youtube.com');
	define("WISTIA",'wistia.com');
	
	class VideoEmbedUtilityTwigExtension extends Twig_Extension {
		
		private static $KNOWN_HOSTS = array(VIMEO,YOUTUBE,WISTIA);
		
		public function getFilters() {
			return array(
				'videoPlayerUrl' => new Twig_Filter_Method($this,'videoPlayerUrl'),
				'videoEmbed' => new Twig_Filter_Method($this,'videoEmbed'),
				'videoHost' => new Twig_Filter_Method($this,'videoHost')
			);
		}
		/**
		 * Returns a string indicating where this video is hosted (youtube, vimeo, etc.)
		 *
		 * @param string $videoUrl
		 * @return string
		 */
		public function videoHost($videoUrl) {
			$host = parse_url($videoUrl, PHP_URL_HOST);
			// return a sanitized value (no leading www, etc) if it's one we know.
			foreach($this::$KNOWN_HOSTS as $known) {
				if( strpos($videoUrl,$known) !== FALSE ) {
					return $known;
				}
			}
			return $host;
		}
				
		public function videoId($videoUrl) {
			$host = $this->videoHost($videoUrl);
			switch($host) {
				case VIMEO:
					if(preg_match('/\/([0-9]+)\/*(\?.*)?$/',$videoUrl,$matches) !== false) {
						return $matches[1];
					}
				break;
				
				case YOUTUBE:
					if(preg_match('/[&,v]=([^&]+)/',$videoUrl,$matches) !== false)
						return $matches[1];
                                break;

                                case WISTIA:
                                        if(preg_match('/https?:\/\/.+?(wistia\.com|wi\.st)\/(medias|embed)\/([^&]+)/',$videoUrl,$matches) !== false)
                                                return $matches[3];
                                break;
                        }
			return "";
		}
		
		public function videoPlayerUrl($input) {
			$vid = $this->videoId($input);
			switch($this->videoHost($input)) {
				case VIMEO:
					return "//player.vimeo.com/video/$vid";
				break;
				
				case YOUTUBE:
					return "//www.youtube.com/embed/$vid?controls=2";
                                break;

				case WISTIA:
					return "wistia_async_$vid";
				break;
			}
			return "";
		}
		
		/**
		* Returns a boolean indicating whether the string $haystack ends with the string $needle.
		* @param string $haystack the string to be searched
		* @param string $needle the substring we're looking for
		* return boolean
		*/
		private function endsWith($haystack, $needle) {
			return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
		}
		
		public function videoEmbed($input, $options = array()) {
			$width = '100%';
			$height = '148';
			$url = $this->videoPlayerUrl($input);
			
			if(!empty($url)) {
				if(!empty($options)) {
					if(isset($options['width'])) {
						$width = $options['width'];
						unset($options['width']);
					}
					
					if(isset($options['height'])) {
						$height = $options['height'];
						unset($options['height']);
					}
          
                                        if(!empty($options)) {
                                                $url .= '?' . http_build_query($options);
                                        }
				}
				
				$originalPath = craft()->path->getTemplatesPath();
				$myPath = craft()->path->getPluginsPath() . 'videoembedutility/templates/';
                                craft()->path->setTemplatesPath($myPath);

                                if($this->videoHost($input) === WISTIA) {
                                        $templateHtml = '_wistiaEmbed.html';
                                } else {
                                        $templateHtml = '_vimeoEmbed.html';
                                }

				$markup = craft()->templates->render($templateHtml, array(
					'player_url' => $url,
					'width' => $width,
					'height' => $height
				));
				craft()->path->setTemplatesPath($originalPath);
				return TemplateHelper::getRaw($markup);
			}
		}
		
		public function getName() {
			return 'Video Embed Utility Twig Extension';
		}
	}
	
?>
