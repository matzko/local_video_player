<?php
/*
Plugin Name: Local Video Player
Plugin URI: 
Description: Plays local flv files.
Version: 1.0
Author: Austin Matzko
Author URI: http://austinmatzko.com
*/

if ( ! class_exists( 'LocalVideoPlayer' ) ) {
	class LocalVideoPlayer {
		protected $_plugin_dir_url = '';
		protected $_clip_values = array();
		protected $_player_events = array();
		protected $_playlist_properties = array();

		public $support_ios;

		public function __construct()
		{
			if ( is_admin() )
				return;

			$this->_plugin_dir_url = plugin_dir_url(__FILE__);

			$this->support_ios = apply_filters( 'local_video_player_suppport_ios', true );

			wp_enqueue_script('flowplayer', $this->_plugin_dir_url . 'flowplayer-3.2.6.min.js', array(), '3.2.6');

			if ( $this->support_ios ) {
				wp_enqueue_script('flowplayer-ios-support', $this->_plugin_dir_url . 'plugins/flowplayer.ipad-3.2.2.min.js', array( 'flowplayer' ), '3.2.2');
			}

			add_shortcode('local-video', array($this, 'shortcode_local_video'));

			$this->add_clip_value( 'autoPlay', false );
			$this->add_clip_value( 'autoBuffering', true );
		}

		public function get_flowplayer_params()
		{
			$swf_src = apply_filters( 'local_video_player_swf_src',  $this->_plugin_dir_url . 'flowplayer-3.2.7.swf' );
			return array(
				'src' => $swf_src,
				'wmode' => 'transparent',
			);
		}

		public function get_flowplayer_params_literal()
		{
			$params_values = $this->get_flowplayer_params();
			return $this->_literalize_strings( json_encode( $params_values ) );
		}

		public function get_flowplayer_config( $video_files = array(), $prep_for_literal = false )
		{
			$prep_for_literal = (bool) $prep_for_literal;
			$clip_values = apply_filters( 'local_video_player_clip_values', $this->_clip_values );
			if ( ! $prep_for_literal ) {
				$clip_values = array_map( array( $this, '_unprep_literal_formatting' ), $clip_values );
			}
			
			$video_files = apply_filters( 'local_video_player_video_files', $video_files );
			$playlist = array();
			foreach( (array) $video_files as $playlist_item ) {
				$item_play = array(
					'url' => $playlist_item,
				);
				$props = apply_filters( 
					'local_video_player_playlist_item_properties',
					$this->_get_playlist_item_properties( $playlist_item ),
					$playlist_item
				);

				if ( ! empty( $props ) ) {
					$item_play = array_merge( $item_play, $props );	
				}

				if ( ! $prep_for_literal ) {
					foreach( $item_play as $key => $value ) {
						if ( is_array( $value ) ) {
							$item_play[$key] = array_map( array( $this, '_unprep_literal_formatting' ), $value );
						}
					}
				}

				$playlist[] = $item_play;
			}

			$player_events = apply_filters( 'local_video_player_player_events', array_merge(
				array(
					'playlist' => $playlist,
				),
				$this->_player_events
			) );

			return array_merge(
				array( 'clip' => $clip_values ),
				$player_events
			);
		}

		public function get_flowplayer_config_literal( $video_files = array() )
		{
			$config = $this->get_flowplayer_config( $video_files, true );
			return $this->_literalize_strings( json_encode( $config ) ); 
		}

		protected function _get_playlist_item_properties( $url = '' )
		{
			$props = array();
			if ( ! empty( $this->_playlist_properties[ '*' ] ) ) {
				$props = $this->_playlist_properties[ '*' ];  
			}

			if ( ! empty( $this->_playlist_properties[ $url ] ) ) {
				$props = array_merge(
					$props,
					$this->_playlist_properties[ $url ]
				);
			}
			return $props;
		}

		protected function _literalize_strings( $text = '' )
		{
			$replacements = array(
				'\'%%RMS%' => '',
				'%%RME%s\'' => '',
				'"%%RMS%' => '',
				'%%RME%s"' => '',
			);

			return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
		}

		protected function _unprep_literal_formatting( $text = '' )
		{
			if ( is_string( $text ) ) {
				$replacements = array(
					'%%RMS%' => '',
					'%%RME%s' => '',
				);

				$text = str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
			}
			return $text;
		}

		/**
		 * Add a clip key and value to the config setup.
		 *
		 * @param string $key The clip key.
		 * @param string $value The clip value.  When set to -null- that key is unset.
		 * @param bool $is_js_literal Treat as a JavaScript object literal upon output (no quotation marks).
		 */
		public function add_clip_value( $key = '', $value = null, $is_js_literal = false )
		{
			if ( null === $value && isset( $this->_clip_values[$key] ) ) {
				unset( $this->_clip_values[$key] );
			} else {
				if ( $is_js_literal ) {
					$value = $this->make_js_literal( $value );
				}
				$this->_clip_values[$key] = $value;
			}
		}

		/**
		 * Add a player event key and value to the config setup.
		 *
		 * @param string $key The player event key.
		 * @param string $value The player event value.  When set to -null- that key is unset.
		 * @param bool $is_js_literal Treat as a JavaScript object literal upon output (no quotation marks).
		 */
		public function add_player_event( $key = '', $value = null, $is_js_literal = false )
		{
			if ( null === $value && isset( $this->_player_events[$key] ) ) {
				unset( $this->_player_events[$key] );
			} else {
				if ( $is_js_literal ) {
					$value = $this->make_js_literal( $value );
				}
				$this->_player_events[$key] = $value;
			}
		}

		public function add_playlist_property( $url = '', $key = '', $value = '' )
		{
			if ( empty( $this->_playlist_properties[ $url ] ) ) {
				$this->_playlist_properties[ $url ] = array(); 
			}
			$this->_playlist_properties[ $url ][ $key ] = $value; 
		}

		/**
		 * Wrap the text so that it gets rendered as a JS literal, i.e. not quoted 
		 * when it gets JSON-encoded.
		 *
		 * @param string $text The text to make into a JS literal.
		 * @return string The wrapped text.
		 */
		public function make_js_literal( $text = '' )
		{
			return '%%RMS%' . $text . '%%RME%s';
		}

		public function print_video_player($file_url = '', $id = '', $height = 330, $width = 520)
		{
			$params = $this->get_flowplayer_params_literal();
			$config = $this->get_flowplayer_config_literal( array( $file_url ) );
			$return = '
			<div  
				 style="clear:both;display:block;width:' . $width . 'px;height:' . $height . 'px"  
				 id="player-' . $id . '" class="video-player-wrapper"> 
			</div> 
		
			<script>
				flowplayer("player-' . $id . '", ' . $params . ',' . $config . ')';
				if ( $this->support_ios ) {
					$return .= '.ipad()';
				}
			$return .= '
			</script>';

			return $return;
		
		}

		public function shortcode_local_video($atts)
		{
			extract(shortcode_atts(array(
				'id' => '',
				'height' => 330,
				'width' => 520,
			), $atts ));
			if ( empty( $id ) ) {
				return '';
			} elseif ( file_exists(WP_CONTENT_DIR . '/uploads/videos/' . $id . '.flv') ) {
				$url = WP_CONTENT_URL . '/uploads/videos/' . $id . '.flv';
				return $this->print_video_player($url, $id, $height, $width);
			}
		}
	}

	function load_local_video_player()
	{
		global $local_video_player;
		$local_video_player = new LocalVideoPlayer;
	}

	add_action( 'plugins_loaded', 'load_local_video_player' );
}
