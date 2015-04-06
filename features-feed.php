<?php
add_action( 'init', 'umw_add_feed_with_features' );
function umw_add_feed_with_features() {
	add_feed( 'with-features', 'umw_feed_with_features' );
	add_feed( 'with-thumbs', 'umw_feed_with_thumbs' );
	
	add_image_size( '50px-thumb', 50, 50, true );
	add_image_size( '75px-thumb', 75, 75, true );
}

function umw_feed_with_features( $comment ) {
	if ( ! $comment ) {
		add_action( 'rss2_item', 'umw_insert_featured_image_into_feed', 1 );
		global $umw_upload_basedir, $umw_upload_baseurl;
		$upload_dir = wp_upload_dir();
		$umw_upload_basedir = $upload_dir['basedir'];
		$umw_upload_baseurl = $upload_dir['baseurl'];
	}
	
	do_feed_rss2( $comment );
}

function umw_feed_with_thumbs( $comment ) {
	global $wp_query;
	if ( isset( $_GET['debug'] ) ) {
		print( "\n<!-- Query:\n" );
		var_dump( $wp_query );
		print( "\n-->\n" );
	}
	if ( ! $comment ) {
		add_filter( 'the_excerpt_rss', 'strip_tags' );
		add_action( 'rss2_item', 'umw_insert_thumb_into_feed', 1 );
		global $umw_upload_basedir, $umw_upload_baseurl;
		$upload_dir = wp_upload_dir();
		$umw_upload_basedir = $upload_dir['basedir'];
		$umw_upload_baseurl = $upload_dir['baseurl'];
	}
	
	do_feed_rss2( $comment );
}

function umw_insert_featured_image_into_feed() {
	/*echo '<note>We entered the umw_insert_featured_image_into_feed function</note>';*/
	global $post;
	
	$feature = umw_feed_check_enclosures();
	
	if ( empty( $feature ) ) {
		/*echo '<note>The feature appears to be empty</note>';*/
		return;
	}
	
	echo apply_filters( 'rss_enclosure', '<enclosure url="' . $feature[0] . '" length="' . $feature[1] . '" type="' . $feature[2] . '" />' );
}

function umw_insert_thumb_into_feed() {
	/*echo '<note>We entered the umw_insert_featured_image_into_feed function</note>';*/
	global $post;
	
	$feature = umw_feed_check_thumb_enclosures();
	
	if ( empty( $feature ) ) {
		/*echo '<note>The feature appears to be empty</note>';*/
		return;
	}
	
	echo apply_filters( 'rss_enclosure', '<enclosure url="' . $feature[0] . '" length="' . $feature[1] . '" type="' . $feature[2] . '" />' );
}

function umw_feed_check_enclosures() {
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
	
	global $umw_upload_basedir, $umw_upload_baseurl;
	$enc = array(
		$feature[0], 
		filesize( str_replace( $umw_upload_baseurl, $umw_upload_basedir, $feature[0] ) ), 
		get_post_mime_type( $feature_id )
	);
	update_post_meta( $post->ID, '_umw_enclosures', $enc );
	/*print( '<note>Found a thumbnail and stored it as the appropriate post meta</note>' );*/
	return $enc;
}

function umw_feed_check_thumb_enclosures() {
	global $post;
	if ( ! function_exists( 'has_post_thumbnail' ) || ! has_post_thumbnail( $post->ID ) ) {
		if ( isset( $_GET['debug'] ) ) {
			print( "<!-- This post does not appear to have a featured image -->\n" );
		}
		return null;
	}
	
	if ( isset( $_REQUEST['size'] ) ) {
		$size = $_REQUEST['size'];
		if ( strstr( $size, '|' ) )
			$size = explode( '|', $size );
		else if ( strstr( $size, '*' ) )
			$size = explode( '*', $size );
	}
	
	$feature_id = get_post_thumbnail_id( $post->ID );
	$feature = wp_get_attachment_image_src( $feature_id, $size );
	if ( isset( $_GET['debug'] ) ) {
		print( '<!-- Image Information: ' . "\n" );
		var_dump( $feature );
		print( ' -->' );
	}
	$enc = null;
	if ( empty( $feature ) )
		return false;
	
	global $umw_upload_basedir, $umw_upload_baseurl;
	$enc = array(
		$feature[0], 
		filesize( str_replace( $umw_upload_baseurl, $umw_upload_basedir, $feature[0] ) ), 
		get_post_mime_type( $feature_id )
	);
	return $enc;
}
?>