<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package revenue
 */

get_header(); 

if ( function_exists( 'revenue_set_post_views' ) ) :
	revenue_set_post_views(get_the_ID());
endif;
?>

	<div id="primary" class="content-area">

		<main id="main" class="site-main" >


<!-- look for tags and send content to addparagraph function in functions -->

<?php
	if ( has_tag('') ){
		$pid = get_the_ID();
		$content_post = get_post($pid);
		$pcontent = $content_post->post_content;
		$pcontent = apply_filters('p_content', $pcontent);
		echo $pcontent;

	} else {
		echo get_post_field('post_content', $post->ID);
}
?>

<?php
	if(has_tag()){
	    echo 'this post has tags';
	    } else {
	    echo 'no tags sorry';
}
?>

	</main><!-- #main -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
