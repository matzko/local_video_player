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

		public function __construct()
		{
			if ( is_admin() )
				return;

			$this->_plugin_dir_url = plugin_dir_url(__FILE__);
			wp_enqueue_script('flowplayer', $this->_plugin_dir_url . 'flowplayer-3.2.6.min.js', array(), '3.2.6');
			add_shortcode('local-video', array($this, 'shortcode_local_video'));
		}

		public function print_video_player($file_url = '', $id = '', $height = 330, $width = 520)
		{
			$return = '
			<div  
				 style="clear:both;display:block;width:' . $width . 'px;height:' . $height . 'px"  
				 id="player-' . $id . '"> 
			</div> 
		
			<script>
				flowplayer("player-' . $id . '", 
						{
							src : "' . $this->_plugin_dir_url . 'flowplayer-3.2.7.swf",
							wmode: "transparent"
						},
						{
							clip:{
								autoPlay: false, 
								autoBuffering: true
							},
							playlist:["' . esc_js( $file_url ) . '"]
						}
					);
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
