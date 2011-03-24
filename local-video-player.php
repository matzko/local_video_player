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

		public function __construct()
		{
			if ( is_admin() )
				return;

			$this->_plugin_dir_url = plugin_dir_url(__FILE__);
			wp_enqueue_script('flowplayer', $this->_plugin_dir_url . 'flowplayer-3.2.6.min.js', array(), '3.2.6');
			add_shortcode('local-video', array($this, 'shortcode_local_video'));

			$this->add_clip_value( 'autoPlay', false );
			$this->add_clip_value( 'autoBuffering', true );
		}

		protected function _build_flowplayer_object( $video_files = array() )
		{
			$swf_src = apply_filters( 'local_video_player_swf_src',  $this->_plugin_dir_url . 'flowplayer-3.2.7.swf' );
			$config_values = array(
				'src' => $swf_src,
				'wmode' => 'transparent',
			);

			$clip_values = apply_filters( 'local_video_player_clip_values', $this->_clip_values );
			
			$video_files = apply_filters( 'local_video_player_video_files', $video_files );

			$player_events = apply_filters( 'local_video_player_player_events', array_merge(
				array(
					'playlist' => $video_files,
				),
				$this->_player_events
			) );


			$config_string = $this->_literalize_strings( json_encode( $config_values ) );
			$general_string = $this->_literalize_strings( json_encode(
				array_merge(
					array( 'clip' => $clip_values ),
					$player_events
				)
			) );

			return array( $config_string, $general_string );
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
					$value = '%%RMS%' . $value . '%%RME%s';
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
					$value = '%%RMS%' . $value . '%%RME%s';
				}
				$this->_player_events[$key] = $value;
			}
		}

		public function print_video_player($file_url = '', $id = '', $height = 330, $width = 520)
		{
			list( $config, $general ) = $this->_build_flowplayer_object( array( $file_url ) );
			$return = '
			<div  
				 style="clear:both;display:block;width:' . $width . 'px;height:' . $height . 'px"  
				 id="player-' . $id . '"> 
			</div> 
		
			<script>
				flowplayer("player-' . $id . '", ' . $config . ',' . $general . ');
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
