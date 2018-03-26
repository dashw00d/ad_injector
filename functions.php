<?php
function my_theme_enqueue_styles() {

    $parent_style = 'revenue-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );


// ***relevant to ad injector below

// Blog paragraph injection function
function addParagraphs($pcontent) {

    $post_id = get_the_ID();
	$ptag_ids = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) ); //get post tags

	// Get ad ids and add to array
    $args = array( 'post_type' => 'ad_injector','fields' => 'ids');
	$loop = new WP_Query( $args );

	foreach((array) $loop->posts as $id) {
   		$ad_ids[] = $id;
}
	// loop through ads, if tag is in both ad and post, save ad image url as $url and break
	foreach($ad_ids as $ad_id) {
		$atag_ids = wp_get_post_tags( $ad_id, array( 'fields' => 'ids' ) );
		if (array_intersect($atag_ids, $ptag_ids)) {
			$ad_paragraph_after = get_post_meta( $ad_id, 'ad_paragraph_after', true);
			$url = wp_get_attachment_url( get_post_thumbnail_id($ad_id) );

	}

}

    $output = ''; // define variable to avoid PHP warnings
    $parts = explode("</p>", $pcontent);
    $count = count($parts); // call count() only once, it's faster

    for($i=0; $i<$count; $i++) {
    	if ($i == $ad_paragraph_after) {
        	$output .= '<img src="' . $url . '">'; // slips ad url in at supplied paragraph number
        } else {
        $output .= $parts[$i] . '</p>';

    }
}
    return $output;

}
add_filter('p_content','addParagraphs', 10, 1);


// Loads injection classes and files
$ad_injector = get_stylesheet_directory() . '/inc/ad_injector/ad_injector_class.php';
include($ad_injector);

?>