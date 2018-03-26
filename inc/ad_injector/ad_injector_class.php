<?php
/*
 * Ad Injector
 * Creates an 'ad' content type to inject inside blog paragraphs
 * Uses hooks and tags inside your theme to output relevant information
 */

class ad_injector{
 	
	//variables
	private $directory = '';
	private $singular_name = 'ad';
	private $plural_name = 'ads';
	private $content_type_name = 'ad_injector';
	
	//magic function, called on creation
	public function __construct(){
	
	$this->set_directory_value(); //set the directory url on creation
	add_action('init', array($this,'add_content_type')); //add content type
	add_action('init', array($this,'check_flush_rewrite_rules')); //flush re-write rules for permalinks (because of content type)
	add_action('add_meta_boxes', array($this,'add_meta_boxes_for_content_type')); //add meta boxes 
	add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles')); //enqueue public facing elements
	add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts_and_styles')); //enqueues admin elements
	add_action('save_post_' . $this->content_type_name, array($this,'save_custom_content_type')); //handles saving of content type meta info
	add_action('display_content_type_meta', array($this,'display_additional_meta_data')); //displays the saved content type meta info	
}
	//sets the directory (path) so that we can use this for our enqueuing
	public function set_directory_value(){
		$this->directory = get_stylesheet_directory_uri() . '/inc/ad_injector';
	}
	//check if we need to flush rewrite rules
	public function check_flush_rewrite_rules(){
	$has_been_flushed = get_option($this->content_type_name . '_flush_rewrite_rules');
	//if we haven't flushed re-write rules, flush them (should be triggered only once)
	if($has_been_flushed != true){
		flush_rewrite_rules(true);
		update_option($this->content_type_name . '_flush_rewrite_rules', true);
	}
}
	//enqueue public scripts and styles
	public function enqueue_public_scripts_and_styles(){	
	//public styles
	wp_enqueue_style(
		$this->content_type_name . '_public_styles', 
		$this->directory . '/css/' . $this->content_type_name . '_public_styles.css'
	);
	//public scripts
	wp_enqueue_script(
		$this->content_type_name . '_public_scripts', 
		$this->directory . '/js/' . $this->content_type_name . '_public_scripts.js', 
		array('jquery')
	); 
}
	//enqueue admin scripts and styles
	public function enqueue_admin_scripts_and_styles(){
		
	global $pagenow, $post_type;
	
	//process only on post edit page for custom content type
	if(($post_type == $this->content_type_name) && ($pagenow == 'post-new.php' || $pagenow == 'post.php')){
		
		//admin styles
		wp_enqueue_style(
			$this->content_type_name . '_admin_styles', 
			$this->directory . '/css/' . $this->content_type_name . '_admin_styles.css'
		);
		//jquery ui styles for datepicker
		wp_enqueue_style(
			$this->content_type_name . '_jquery_ui_style',
			'//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css'
		);
		//timepicker styles
		wp_enqueue_style(
			'jquery_ui_timepicker_styles',
			$this->directory . '/css/jquery.ui.timepicker.css'
		);
		//timepicker script
		wp_enqueue_script(
			'jquery_ui_timepicker_script',
			$this->directory . '/js/jquery.ui.timepicker.js'
		);		
		//admin scripts (depends on datepicker and timepicker)
		wp_enqueue_script(
			$this->content_type_name . '_public_scripts', 
			$this->directory . '/js/' . $this->content_type_name . '_admin_scripts.js', 
			array('jquery','jquery-ui-datepicker','jquery_ui_timepicker_script')
		); 	
	}
}
	//adding our new content type
	public function add_content_type(){
	 $labels = array(
           'name'               => ucwords($this->singular_name),
           'singular_name'      => ucwords($this->singular_name),
           'menu_name'          => ucwords($this->plural_name),
           'name_admin_bar'     => ucwords($this->singular_name),
           'add_new'            => ucwords($this->singular_name),
           'add_new_item'       => 'Add New ' . ucwords($this->singular_name),
           'new_item'           => 'New ' . ucwords($this->singular_name),
           'edit_item'          => 'Edit ' . ucwords($this->singular_name),
           'view_item'          => 'View ' . ucwords($this->plural_name),
           'all_items'          => 'All ' . ucwords($this->plural_name),
           'search_items'       => 'Search ' . ucwords($this->plural_name),
           'parent_item_colon'  => 'Parent ' . ucwords($this->plural_name) . ':', 
           'not_found'          => 'No ' . ucwords($this->plural_name) . ' found.', 
           'not_found_in_trash' => 'No ' . ucwords($this->plural_name) . ' found in Trash.',
       );
       
       $args = array(
           'labels'            => $labels,
           'public'            => true,
           'publicly_queryable'=> true,
           'show_ui'           => true,
           'show_in_nav'       => true,
           'query_var'         => true,
           'hierarchical'      => false,
           'supports'          => array('title','editor','thumbnail'), 
           'has_archive'       => true,
           'menu_position'     => 20,
           'show_in_admin_bar' => true,
           'menu_icon'         => 'dashicons-format-status',
           'taxonomies' => array('post_tag'),
           'show_tagcloud'              => true
       );
	
	//register your content type
	register_post_type($this->content_type_name, $args);
	
}
	
	//adding meta box to save additional meta data for the content type
	public function add_meta_boxes_for_content_type(){
	
	//add a meta box
	add_meta_box(
		$this->singular_name . '_meta_box', //id
		ucwords($this->singular_name) . ' Information', //box name
		array($this,'display_function_for_content_type_meta_box'), //display function
		$this->content_type_name, //content type 
		'normal', //context
		'default' //priority
	);
	
}
	//displays the back-end admin output for the event information
	public function display_function_for_content_type_meta_box($post){

	//collect meta information
	$ad_paragraph_after = get_post_meta($post->ID, 'ad_paragraph_after', true);
	$ad_image_width = get_post_meta($post->ID, 'ad_image_width', true);
	$ad_image_height = get_post_meta($post->ID, 'ad_image_height', true);


	//set nonce
	wp_nonce_field($this->content_type_name . '_nonce', $this->content_type_name . '_nonce_field');

	?>
	<p>Enter additional information about your ad below</p>
	<div class="field-container">
		<label for="ad_paragraph_after">Paragraph for Ad to Follow (Number)</label>
		<input type="number" name="ad_paragraph_after" id="ad_paragraph_after" value="<?php echo $ad_paragraph_after; ?>"  required//>
	</div>
	<div class="field-container">
		<label for="ad_image_width">Ad Image Width</label>
		<input type="text" name="ad_image_width" id="ad_image_width" class="admin-datepicker" value="<?php echo $ad_image_width; ?>">
	</div>
	<div class="field-container">
		<label for="ad_image_height">Ad Image Height</label>
		<input type="text" name="ad_image_height" id="ad_image_height" class="admin-datepicker" value="<?php echo $ad_image_height; ?>"/>
	</div>
	<?php
}
	//when saving the custom content type, save additional meta data
	public function save_custom_content_type($post_id){
	
	//check for nonce
	if(!isset($_POST[$this->content_type_name . '_nonce_field'])){
		return $post_id;
	}
	//verify nonce
	if(!wp_verify_nonce($_POST[$this->content_type_name . '_nonce_field'] , $this->content_type_name . '_nonce')){
		return $post_id;
	}
	//check for autosaves
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
		return $post_id;
	}
	//check if the user can edit 
	if(!current_user_can('edit_posts')){
		return $post_id;
	}
	
	//collect sanitized information
	$ad_paragraph_after =sanitize_text_field($_POST['ad_paragraph_after']);
	$ad_image_width =sanitize_text_field($_POST['ad_image_width']);
	$ad_image_height = sanitize_text_field($_POST['ad_image_height']);

	
	//save post meta
	update_post_meta($post_id,'ad_paragraph_after',$ad_paragraph_after);
	update_post_meta($post_id,'ad_image_width',$ad_image_width);
	update_post_meta($post_id,'ad_image_height',$ad_image_height);

	
}} //? Missing bracket somewhere, can't find

	//display additional meta information for the content type
	//@hooked using 'display_additional_meta_data' in theme
	//display additional meta information for the content type
	//@hooked using 'display_additional_meta_data' in theme
	function display_additional_meta_data(){
		global $post, $post_type;
		
		//if we are on our custom post type
		if($post_type == $this->content_type_name){
			
		//collect information
		$ad_paragraph_after = get_post_meta($post->ID,'ad_paragraph_after', true);
		$ad_image_width = get_post_meta($post->ID,'ad_image_width', true);
		$ad_image_height = get_post_meta($post->ID,'ad_image_height', true);

		
		$html = '';
		if(!empty($ad_paragraph_after)){
			$html .= '' . $ad_paragraph_after . '';
		}
		if(!empty($ad_image_width)){
			$html .= '' . $ad_image_width . '';
		}
		if(!empty($ad_image_height)){
			$html .= '' . $ad_image_height . '';
		}
		$html .= '';
		
		echo $html;
		
	
	}
	
}
 	//create new object 
 	$ad_injector = new ad_injector;

 ?>