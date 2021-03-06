<?php
/**
 * Plugin Name: Featured Images in Feeds
 * Description: Adds new feeds that include featured images as enclosures
 * Version: 0.3
 * Author: Curtiss Grymala
 * Author URI: http://www.umw.edu/
 * License: GPL2
 */

if ( ! class_exists( 'Featured_Images_in_Feeds' ) ) {
	class Featured_Images_in_Feeds {
		private $umw_upload_basedir;
		private $umw_upload_baseurl;
		private $atts=array( 'offset' => 0, 'posts' => 10, 'size' => null, 'debug' => false );
		
		function __construct() {
			add_action( 'init', array( $this, 'add_feeds' ) );
			add_action( 'pre_get_posts', array( $this, 'do_post_offset' ), 99 );
			$this->get_atts();
		}
		
		/**
		 * Process the GET/REQUEST parameters and store them as attributes
		 */
		function get_atts() {
			if ( ! array_key_exists( 'posts', $_REQUEST ) && ! array_key_exists( 'amp;posts', $_REQUEST ) ) {
				$this->atts['posts'] = get_option( 'posts_per_rss', 10 );
			}
			
			foreach ( $_REQUEST as $k=>$v ) {
				if ( substr( $k, 0, strlen( 'amp;' ) ) == 'amp;' ) {
					$tmp = substr( $k, strlen( 'amp;' ) );
				} else {
					$tmp = $k; 
				}
				if ( ! array_key_exists( $tmp, $this->atts ) ) {
					continue;
				}
				switch( $tmp ) {
					case 'offset' : 
					case 'posts' :
						$this->set_att( $tmp, intval( $v ) );
						break;
					case 'debug' : 
						if ( in_array( $v, array( 0, '0', false, 'false' ), true ) ) {
							$this->set_att( $tmp, false );
						} else {
							$this->set_att( $tmp, true );
						}
						break;
					default : 
						$this->set_att( $tmp, $v );
						break;
				}
			}
		}
		
		/**
		 * Retrieve an attribute and return it
		 * @param string $key the key of the option being returned
		 * @return mixed the value of the option
		 */
		function get_att( $key ) {
			switch( $key ) {
				case 'offset' : 
				case 'posts' : 
					return intval( $this->atts[$key] );
					break;
				case 'debug' : 
					return (bool)$this->atts[$key];
					break;
				default : 
					return $this->atts[$key];
					break;
			}
		}
		
		/**
		 * Set an option in the array of attributes
		 * @param string $key the key of the option being stored
		 * @param mixed $val the value being stored for the option
		 * @return void
		 */
		function set_att( $key, $val ) {
			$this->atts[$key] = $val;
		}
		
		function do_post_offset( $query ) {
			if ( ! $query->is_main_query() || ! $query->is_feed() )
				return;
			
			$offset = $this->get_att( 'offset' );
			if ( ! empty( $offset ) ) {
				$query->set( 'offset', $offset );
			}
			$posts = $this->get_att( 'posts' );
			if ( ! empty( $posts ) ) {
				$query->set( 'posts_per_rss', $posts );
			}
		}
		
		function add_feeds() {
			add_feed( 'with-features', array( $this, 'feed_with_features' ) );
			add_feed( 'with-thumbs', array( $this, 'feed_with_thumbs' ) );
			add_feed( 'with-custom-features', array( $this, 'feed_with_custom_features' ) );
			
			add_image_size( '50px-thumb', 50, 50, true );
			add_image_size( '75px-thumb', 75, 75, true );
			
			$upload_dir = wp_upload_dir();
			$this->umw_upload_basedir = $upload_dir['basedir'];
			$this->umw_upload_baseurl = $upload_dir['baseurl'];
		}
		
		function feed_with_features( $comment ) {
			if ( ! $comment ) {
				add_action( 'rss2_item', array( $this, 'insert_feature_into_feed' ), 1 );
			}
			
			do_feed_rss2( $comment );
		}
		
		function insert_feature_into_feed() {
			/*echo '<note>We entered the umw_insert_featured_image_into_feed function</note>';*/
			global $post;
			
			$feature = $this->check_enclosures();
			
			if ( empty( $feature ) ) {
				/*echo '<note>The feature appears to be empty</note>';*/
				return;
			}
			
			echo apply_filters( 'rss_enclosure', vsprintf( '<enclosure url="%1$s" length="%2$d" type="%3$s" />', $feature ) );
			/*echo vsprintf( "\n" . '<media:content url="%1$s" type="%3$s" medium="image" />', $feature );*/
		}
		
		function check_enclosures() {
			global $post;
			if ( ! function_exists( 'has_post_thumbnail' ) || ! has_post_thumbnail( $post->ID ) ) {
				delete_post_meta( $post->ID, '_umw_enclosures' );
				/*print( '<note>Either the has_post_thumbnail function does not exist, or this post does not appear to have a thumbnail</note>' );*/
				return null;
			}
			
			$feature_id = get_post_thumbnail_id( $post->ID );
			$feature = wp_get_attachment_image_src( $feature_id, 'large' );
			$enc = maybe_unserialize( get_post_meta( $post->ID, '_umw_enclosures', true ) );
			
			if ( is_array( $enc ) && $feature[0] == $enc[0] ) {
				/*print( '<note>The _umw_enclosures meta info was an array and the feature URL matched the enc URL</note>' );*/
				return $enc;
			}
			
			$enc = array(
				$feature[0], 
				filesize( str_replace( $this->umw_upload_baseurl, $this->umw_upload_basedir, $feature[0] ) ), 
				get_post_mime_type( $feature_id )
			);
			update_post_meta( $post->ID, '_umw_enclosures', $enc );
			/*print( '<note>Found a thumbnail and stored it as the appropriate post meta</note>' );*/
			return $enc;
		}
		
		function feed_with_thumbs( $comment ) {
			global $wp_query;
			if ( $this->get_att( 'debug' ) ) {
				print( "\n<!-- Query:\n" );
				var_dump( $wp_query );
				print( "\n-->\n" );
			}
			if ( ! $comment ) {
				add_filter( 'the_excerpt_rss', 'strip_tags' );
				add_action( 'rss2_item', array( $this, 'insert_thumb_into_feed' ), 1 );
			}
			
			do_feed_rss2( $comment );
		}
		
		function insert_thumb_into_feed() {
			/*echo '<note>We entered the umw_insert_featured_image_into_feed function</note>';*/
			global $post;
			
			$feature = $this->check_thumb_enclosures();
			
			if ( empty( $feature ) ) {
				/*echo '<note>The feature appears to be empty</note>';*/
				return;
			}
			
			echo apply_filters( 'rss_enclosure', vsprintf( '<enclosure url="%1$s" length="%2$d" type="%3$s" />', $feature ) );
		}
		
		function check_thumb_enclosures() {
			global $post;
			if ( ! function_exists( 'has_post_thumbnail' ) || ! has_post_thumbnail( $post->ID ) ) {
				if ( $this->get_att( 'debug' ) ) {
					print( "<!-- This post does not appear to have a featured image -->\n" );
				}
				return null;
			}
			
			$size = $this->get_att( 'size' );
			if ( ! empty( $size ) ) {
				if ( strstr( $size, '|' ) )
					$size = explode( '|', $size );
				else if ( strstr( $size, '*' ) )
					$size = explode( '*', $size );
			} else {
				$size = 'large';
			}
			
			$feature_id = get_post_thumbnail_id( $post->ID );
			$feature = wp_get_attachment_image_src( $feature_id, $size );
			if ( $this->get_att( 'debug' ) ) {
				print( '<!-- Image Information: ' . "\n" );
				var_dump( $feature );
				print( ' -->' );
			}
			$enc = null;
			if ( empty( $feature ) )
				return false;
			
			$enc = array(
				$feature[0], 
				filesize( str_replace( $this->umw_upload_baseurl, $this->umw_upload_basedir, $feature[0] ) ), 
				get_post_mime_type( $feature_id )
			);
			return $enc;
		}
		
		/**
		 * Output a feed with (optionally) multiple sizes of the featured image
		 */
		function feed_with_custom_features() {
			global $wp_query;
			if ( $this->get_att( 'debug' ) ) {
				print( "\n<!-- Query:\n" );
				var_dump( $wp_query );
				print( "\n-->\n" );
			}
			if ( ! $comment ) {
				add_filter( 'the_excerpt_rss', 'strip_tags' );
				add_action( 'rss2_item', array( $this, 'insert_custom_features_into_feed' ), 1 );
			}
			
			do_feed_rss2( $comment );
		}
		
		/**
		 * Insert multiple custom sizes of the featured image as enclosures
		 */
		function insert_custom_features_into_feed() {
			/*echo '<note>We entered the umw_insert_featured_image_into_feed function</note>';*/
			global $post;
			
			$features = $this->check_custom_feature_enclosures();
			
			if ( empty( $features ) || ! is_array( $features ) ) {
				/*echo '<note>The feature appears to be empty</note>';*/
				return;
			}
			
			foreach ( $features as $feature ) {
				echo apply_filters( 'rss_enclosure', vsprintf( '<enclosure url="%1$s" length="%2$d" type="%3$s" name="%4$s" />', $feature ) );
			}
		}
		
		/**
		 * Grab all of the requested sizes of the featured image
		 */
		function check_custom_feature_enclosures() {
			global $post;
			if ( ! function_exists( 'has_post_thumbnail' ) || ! has_post_thumbnail( $post->ID ) ) {
				if ( $this->get_att( 'debug' ) ) {
					print( "<!-- This post does not appear to have a featured image -->\n" );
				}
				return null;
			}
			
			$size = $this->get_att( 'size' );
			if ( ! empty( $size ) ) {
				$sizes = array();
				// Split out the multiple sizes first
				$tmp = explode( '|', $size );
				if ( ! is_array( $tmp ) )
					return null;
				// Split out the w/h of each request
				foreach ( $tmp as $s ) {
					if ( ! strstr( $s, '*' ) ) {
						$sizes[] = $s;
					} else {
						$t = explode( '*', $s );
						if ( count( $t ) !== 2 ) {
							$sizes[] = $s;
						} else {
							$sizes[] = $t;
						}
					}
				}
			}
			if ( empty( $sizes ) ) {
				$sizes = array( 'large', 'thumbnail' );
			}
				
			$feature_id = get_post_thumbnail_id( $post->ID );
			$encs = array();
			foreach ( $sizes as $size ) {
				$feature = wp_get_attachment_image_src( $feature_id, $size );
				if ( $this->get_att( 'debug' ) ) {
					print( '<!-- Image Information: ' . "\n" );
					var_dump( $feature );
					print( ' -->' );
				}
				if ( empty( $feature ) )
					continue;
				
				$encs[] = array(
					$feature[0], 
					filesize( str_replace( $this->umw_upload_baseurl, $this->umw_upload_basedir, $feature[0] ) ), 
					get_post_mime_type( $feature_id ), 
					is_array( $size ) ? implode( 'x', $size ) : $size
				);
			}
			
			return $encs;
		}
	}
}

function inst_featured_images_in_feeds_obj() {
	global $featured_images_in_feeds_obj;
	$featured_images_in_feeds_obj = new Featured_Images_in_Feeds;
}
inst_featured_images_in_feeds_obj();