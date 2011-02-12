<?php
/*
Plugin Name: Local Video Player
Plugin URI: 
Description: Plays local flv files.
Version: 1.0
Author: Austin Matzko
Author URI: http://austinmatzko.com
*/

class LocalVideoPlayer {
	public $plugin_dir_url = '';

	public function __construct()
	{
		if ( is_admin() )
			return;

		$this->plugin_dir_url = plugin_dir_url(__FILE__);
		wp_enqueue_script('flowplayer', $this->plugin_dir_url . 'flowplayer-3.2.2.min.js', array(), '3.2.2');

		add_shortcode('local-video', array(&$this, 'shortcode_local_video'));

	}

	public function print_video_player($file_url = '', $id = '', $height = 330, $width = 520)
	{
		$return = '
		<a  
			 href="' . $file_url . '"  
			 style="clear:both;display:block;width:' . $width . 'px;height:' . $height . 'px"  
			 id="player-' . $id . '"> 
		</a> 
	
		<script>
			flowplayer("player-' . $id . '", 
					{ 
						src : "' . $this->plugin_dir_url . 'flowplayer-3.2.2.swf",
						wmode: "transparent"
					},
					{ 
						clip:  { 
							autoPlay: false, 
							autoBuffering: true
						}
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

$local_video_player = new LocalVideoPlayer;
