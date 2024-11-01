<?php
/*
Plugin Name: ShoppingScout
Plugin URI: http://shoppingscout.com/
Description: ShoppingScout is a grocery price comparison button that saves you time & money, and helps you shop smarter. We compare prices and deals at local and online stores to maximize your savings. 
Author: ShoppingScout
Version: 1.0
Author URI: http://hudnredspires.cz/
*/
	
if ( !class_exists( "ShoppingScout" ) )
{
	class ShoppingScout
	{
		function ShoppingScout() // Constructor
		{
			register_activation_hook( __FILE__, array($this, 'run_on_activate') );
			add_action('admin_init', array($this, 'init_admin'));

			add_action('admin_menu', array($this, 'shopping_scout_addoptions_page_fn'));
			
			add_action('add_meta_boxes', array($this, 'shopping_scout_add_custom_box_fn'));
			add_action('save_post', array($this, 'shopping_scout_save_postdata_fn'));
			
			add_filter( 'the_content', array( $this, 'shoppingscout_addnote' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'shoppingscout_admin_scripts' ) );			

            add_shortcode( 'shoppingscout', array( $this, 'shoppingscout_shortcode_fn' ) );
		}
		
// == Script 
		function shoppingscout_admin_scripts() {

			$seen_it = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
			$do_add_script = false;
			
				if ( ! in_array( 'pksn1', $seen_it ) ) {
				$do_add_script = true;
				add_action( 'admin_print_footer_scripts', array( $this, 'shoppingscout_pksn1_footer_script' ) );
			}
			
			if ( ! in_array( 'pksn2', $seen_it ) ) {
				$do_add_script = true;
				add_action( 'admin_print_footer_scripts', array( $this, 'shoppingscout_pksn2_footer_script' ) );
			}
			
			if ( $do_add_script ) {
				wp_enqueue_script( 'wp-pointer' );
				wp_enqueue_style( 'wp-pointer' );
			}
		}
		
		function shoppingscout_pksn1_footer_script() {
			$pointer_content = '<h3>New Shoppingscout Settings</h3>'; 
			$pointer_content .= '<p>I hope you find Shoppingscout useful. You should probably <a href="';
			$pointer_content .= bloginfo( 'wpurl' );
			$pointer_content .= '/wp-admin/options-general.php?page=ShoppingScout_Options">check your settings</a> before using it.</p>';
			?>
			<script type="text/javascript">// <![CDATA[
			jQuery(document).ready(function($) {
				if(typeof(jQuery().pointer) != 'undefined') {
					$('#menu-plugins').pointer({
						content: '<?php echo $pointer_content; ?>',
						position: {
							edge: 'left',
							align: 'center'
						},
						close: function() {
							$.post( ajaxurl, {
								pointer: 'pksn1',
								action: 'dismiss-wp-pointer'
							});
						}
					}).pointer('open');
				}
			});
			// ]]></script>
			<?php
		} // end shoppingscout_pksn1_footer_script()

		function shoppingscout_pksn2_footer_script() {
			$pointer_content = '<h3>Shoppingscout Entry field</h3>';
			$pointer_content .= '<p>Enter ingredients, to display ShoppingScout button insert shortcode [shoppingscout]</p>';
			?>
			<script type="text/javascript">// <![CDATA[
			jQuery(document).ready(function($) {
				if(typeof(jQuery().pointer) != 'undefined') {
					$('#pksn-box').pointer({
						content: '<?php echo $pointer_content; ?>',
						position: {
							at: 'left bottom',
							my: 'left top'
						},
						close: function() {
							$.post( ajaxurl, {
								pointer: 'pksn2',
								action: 'dismiss-wp-pointer'
							});
						}
					}).pointer('open');
				}
			});
			// ]]></script>
			<?php
		} // end shoppingscout_pksn2_footer_script()


// == Options 

		function draw_set_showwhere_fn()
		{
			$options = get_option('ShoppingScout_Options');
			$items = array(
							array('0', __("Do not show", 'shoppingscout'), __("Do not show button automatically.", 'shoppingscout') ),
							array('1', __("Show before", 'shoppingscout'), __("Show button before content.", 'shoppingscout') ),
							array('2', __("Show after", 'shoppingscout'), __("Show button after content.", 'shoppingscout') )
							);
			foreach( $items as $item )
			{
				$checked = ($options['show-where'] == $item[0] ) ? ' checked="checked" ' : '';
				echo "<label><input ".$checked." value='$item[0]' name='ShoppingScout_Options[show-where]' type='radio' /> $item[1] &mdash; $item[2]</label><br />";
			}
		}

// == Administration
		function init_admin()
		{
			register_setting(
				'ShoppingScout_Options_Group',
				'ShoppingScout_Options');

			add_settings_section(
				'ShoppingScout_Options_ID',
				__('ShoppingScout Options', 'shoppingscout'),
				array($this, 'draw_overview'),
				'ShoppingScout_Page_Title');

			add_settings_field(
				'show-where',
				__('Show note where?', 'shoppingscout'),
				array($this, 'draw_set_showwhere_fn'),
				'ShoppingScout_Page_Title',
				'ShoppingScout_Options_ID');
		}

// == Administration Menus
		function shopping_scout_addoptions_page_fn()
		{
			if (!function_exists('current_user_can') || !current_user_can('manage_options') )
			return;
			if ( function_exists( 'add_options_page' ) )
			{
				add_options_page(
					__('ShoppingScout Options Page', 'shoppingscout'),
					__('Shoppingscout', 'shoppingscout'),
					'manage_options',
					'ShoppingScout_Options',
					array( $this, 'shopping_scout_drawoptions_fn' ) );
			}
		}
		
		function shopping_scout_drawoptions_fn()
		{
			$ShoppingScout_Options = get_option('ShoppingScout_Options');
			?>
			<div class="wrap">
				<?php screen_icon("options-general"); ?>
				<h2>Shoppingscout <?php echo $ShoppingScout_Options['version']; ?></h2>
				<form action="options.php" method="post">
				<?php
				settings_fields('ShoppingScout_Options_Group');
				do_settings_sections('ShoppingScout_Page_Title');
				?>
					<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'contentscheduler'); ?>" />
					</p>
				</form>
			</div>
			<?php
		}
		
		function draw_overview()
		{
			echo "<p>";
			_e( 'Choose where to automatically insert ShoppingScout button.', 'shoppingscout' );
			echo "</p>\n";
		}
		
// == Set options to defaults
		function run_on_activate()
		{
			$options = get_option('ShoppingScout_Options');

			$arr_defaults = array
			(
			    "version" => "1.0.0",
				"show-where" => "1",
			    "show-pointers" => "1"
			);

			if( !is_array( $options )  )
			{
				update_option('ShoppingScout_Options', $arr_defaults);
			}
		}
		
// == Custom Controls / Panels
		function shopping_scout_add_custom_box_fn()
		{
		    add_meta_box( 'pksn-box', 
							__( 'Shoppingscout', 
							'shoppingscout' ), 
							array($this, 'draw_custom_box_fn'), 
							'post' );

		    add_meta_box( 'pksn-box', 
							__( 'Shoppingscout', 
							'shoppingscout' ), 
							array($this, 'draw_custom_box_fn'), 
							'page' );

			$args = array(
				'public'   => true,
				'_builtin' => false
			); 

			$output = 'names'; 
			$operator = 'and'; 
			$post_types = get_post_types( $args, $output, $operator );

			foreach ($post_types  as $post_type )
			{
				// echo '<p>'. $post_type. '</p>';
				add_meta_box( 'pksn-box',
								__( 'Shoppingscout',
								'shoppingscout' ),
								array( $this, 'draw_custom_box_fn'),
								$post_type );
			}
		}
		
		function draw_custom_box_fn()
		{
			global $post;
			wp_nonce_field( 'shoppingscout_values', 'ShoppingScout_noncename' );
			$note_string = ( get_post_meta( $post->ID, '_shoppingscout-note', true) );
			echo '<label for="shoppingscout-note"><p>' . __("Enter your ingredients here. No measurements necessary:", 'shoppingscout' ) . '</p></label>';
			echo '';
			echo '<textarea rows="4" cols="80" id="shoppingscout-note" placeholder="EX. APPLES, CINNAMON, BUTTER, BROWN SUGAR..." name="_shoppingscout-note" >';
			echo $note_string;
			echo "</textarea>\n";
			echo "<p>To display ShoppingScout button insert this shortcode into post <b>[shoppingscout]</b></p>";
		}
		
		// save custom data
		function shopping_scout_save_postdata_fn( $post_id )
		{
			if( !empty( $_POST['ShoppingScout_noncename'] ) )
			{
				if ( !wp_verify_nonce( $_POST['ShoppingScout_noncename'], 'shoppingscout_values' ))
				{
					return $post_id;
				}
			}
			else
			{
				return $post_id;
			}

			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			{
				return $post_id;
			}

			if ( 'page' == $_POST['post_type'] )
			{
				if ( !current_user_can( 'edit_page', $post_id ) )
				return $post_id;
			}
			else
			{
				if ( !current_user_can( 'edit_post', $post_id ) )
				return $post_id;
			}

			$note_string = $_POST['_shoppingscout-note'];
			update_post_meta( $post_id, '_shoppingscout-note', $note_string );
			return true;
		}

// == THE SHOPPINGSCOUT BUTTON

		function shoppingscout_addnote( $the_content ) {
			$options = get_option('ShoppingScout_Options');
			if ( $options['show-where'] > 0 ) {
				if ( $options['show-where'] == 1 ) {
					$the_content = $this->shoppingscout_getnote() . $the_content;
				} else {
					$the_content = $the_content . $this->shoppingscout_getnote();
				}
			}
			return $the_content;
		}
		
		function shoppingscout_getnote( $em=false, $strong=false ) {
			global $post;
			$the_note = get_post_meta( $post->ID, '_shoppingscout-note', true );
			$the_title = trim($post->post_title);

			if( $the_note == '' ) {
				$the_note = "There are no ingredients listed i.e.: APPLES, CINNAMON, BUTTER, BROWN SUGAR";
			}
			return $this->style_content( $em, $strong, $the_note, $the_title );
		}
		
		function style_content( $em=false, $strong=false, $the_note, $the_title ) {
			$style_string = "border:1px solid #000;background:#FFFF7F;padding:8px;";
			$the_note = trim(strip_tags($the_note));

			if( $em ) {
			    $style_string .= "font-style:oblique;";
			}
			if( $strong ) {
			    $style_string .= "font-weight:bold;";
			}

			return '
				<div class="mywld-recipe-btn" style="width: 225px;margin: auto;">
					<link type="text/css" rel="stylesheet" href="http://shoppingscout.com/css/mywld-recipe.css">
					<script type="text/javascript" src="http://shoppingscout.com/js/mywld/mywld-api-recipeEmail.js"></script>
					<div class="mywld-recipe-btn-tooltip"></div>
					<button mywldrecipecvsingredients="'.$the_note.'" mywldrecipetitle="'.$the_title.'" onclick="MyWldRecipe.displayInputForm(this)" onmouseover="MyWldRecipe.Tooltip(this)" type="button">
						<img src="http://shoppingscout.com/images/master/find-local-price-btn.png">
					</button>
				</div>
			';

		}
		
		function shoppingscout_shortcode_fn( $attributes, $content ) {
            extract( shortcode_atts( array(
                'em' => false,
                'strong' => false,
            ), $attributes ) );
            if( $content == '' ) {
                 $current_shoppingscout = $this->shoppingscout_getnote( $em, $strong );
            } else {
                global $post;

                $content = do_shortcode( $content );
                $the_title = $post->post_title;
                $current_shoppingscout = $this->style_content( $em, $strong, $content, $the_title );
            }
            if( $current_shoppingscout ) {
                return $current_shoppingscout;
            }
        }
	}
} // End Class

// ==
if (class_exists("ShoppingScout")) {
	$ShoppingScout = new ShoppingScout();
}

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}