<?php
/**
 * @version   1.8 November 13, 2012
 * @author    RocketTheme, LLC http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2012 RocketTheme, LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 */
/*
Plugin Name: RokNewsFlash
Plugin URI: http://www.rockettheme.com
Description: RokNewsFlash is widget to display brief snippets of an post. The plugin is perfect for Headlines as it can cycle through your chosen posts, displaying the content item title or a preview of the content itself.
Author: RocketTheme, LLC
Version: 1.8
Author URI: http://www.rockettheme.com
License: http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// Define Directory Separator

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

// Globals

global $roknewsflash_plugin_path, $roknewsflash_plugin_url, $browser_platform, $browser_name, $browser_version;
if(!is_multisite()) {
    $roknewsflash_plugin_path = dirname($plugin);
} else {
	if(!empty($network_plugin)) {
		$roknewsflash_plugin_path = dirname($network_plugin);
	} else {
		$roknewsflash_plugin_path = dirname($plugin);
	}
}
$roknewsflash_plugin_url = WP_PLUGIN_URL.'/'.basename($roknewsflash_plugin_path);

require(dirname(__FILE__). DS .'rokbrowsercheck.php');

$browser_check = new RokBrowserCheck;
$browser_platform = $browser_check->platform;
$browser_name = $browser_check->name;
$browser_version = $browser_check->shortversion;

// Widget

class RokNewsFlash extends WP_Widget {

	// RocketTheme RokNewsFlash Widget
	// Port by Jakub Baran

    static $plugin_path;
    static $plugin_url;
    static $global_params = array('load_css', 'theme');

    var $short_name = 'widget-roknewsflash';
    var $long_name = 'RokNewsFlash';
    var $description = 'RocketTheme RokNewsFlash Widget';
    var $css_classname = 'widget_roknewsflash';
    var $width = 200;
    var $height = 400;

    var $_defaults = array(
        'title' => '',
        'load_css' => '1',
        'theme' => 'light',
        'catid' => 'uncategorized',
        'article_count' => '4',
        'itemsOrdering' => 'date',
        'usetitle' => '0',
        'content_type' => 'content',
        'pretext' => 'Newsflash:',
        'controls' => '1',
        'duration' => '600',
        'delay' => '2500',
        'news_indent' => '75',
        'preview_count' => '75'
    );

    function init() {
    	global $browser_platform;
    	
    	// Don't show RokNewsFlash on iPhone or Android platform
    	if($browser_platform != 'iphone' || $browser_platform != 'android') :
	        register_widget("RokNewsFlash");
    	endif;
    }
	
	function roknewsflash_styles() {
		global $roknewsflash_plugin_path, $roknewsflash_plugin_url, $browser_name, $browser_version;
		
		$global_option = get_option('widget_roknewsflash_globals');

		// Enqueue Style

        if($global_option == false) :
        	$global_option = array();
        endif;
        
        if(array_key_exists('theme', $global_option)) :
			$theme = $global_option['theme'];
		else:
			$theme = 'light';
		endif;
        
        if (array_key_exists('load_css', $global_option) && $global_option['load_css'] == '1') {
			if(!is_admin()) :
				wp_enqueue_style('roknewsflash_css', $roknewsflash_plugin_url.'/tmpl/themes/'.$theme.'/roknewsflash.css');
				// Add IE6 and IE7 css style fixes
				if($browser_name == 'ie') :
					$style = $roknewsflash_plugin_url.'/tmpl/themes/'.$theme.'/roknewsflash-ie'.$browser_version;
					$check = $roknewsflash_plugin_path. DS .'tmpl'. DS .'themes'. DS .$theme. DS .'roknewsflash-ie'.$browser_version;
					if(file_exists($check.'.css')) wp_enqueue_style('roknewsflash_css_ie'.$browser_version, $style.'.css');
				endif;
			endif;
		}

	}
	
	function roknewsflash_scripts() {
		global $roknewsflash_plugin_url;
		// Enqueue Script
		if(!is_admin()) :
			wp_enqueue_script('roknewsflash_js', $roknewsflash_plugin_url.'/tmpl/js/roknewsflash.js');
		endif;
	}

    function update($new_instance, $old_instance) {
        $global_instance = array();
        foreach (self::$global_params as $param) {
            if (array_key_exists($param, $new_instance)) {
                $global_instance[$param] = $new_instance[$param];
                unset($new_instance[$param]);
            }
        }
        update_option('widget_roknewsflash_globals', $global_instance);

        return $new_instance;
    }

    function get_settings() {
        $global_option = get_option('widget_roknewsflash_globals');
        if($global_option == false) :
        	$global_option = array();
        endif;
        $settings = parent::get_settings();
        foreach($settings as $key=>$setting) {
            if (count($setting) > 0) {
                $settings[$key] = array_merge($setting, $global_option);
            }
        }
        return $settings;
    }
    
    function prepareRokContent($text, $length = 200) {
		// strips tags won't remove the actual jscript
		$text = preg_replace( "'<script[^>]*>.*?</script>'si", "", $text );
		$text = preg_replace( '/{.+?}/', '', $text);
		// replace line breaking tags with whitespace
		$text = preg_replace( "'<(br[^/>]*?/|hr[^/>]*?/|/(div|h[1-6]|li|p|td))>'si", ' ', $text );
		$text = substr(strip_tags( $text ), 0, $length) ;
		return $text;
	}
	
    function render($args, $instance) {
        global $more, $post, $roknewsflash_plugin_path, $roknewsflash_plugin_url;
        
        // Add inline RokNewsFlash JS init
		$jsinit = "window.addEvent('domready', function() {
			var x = new RokNewsFlash('newsflash', {
				controls: ".(($instance['controls'] == '1') ? "1" : "0").",
				delay: ".$instance['delay'].",
				duration: ".$instance['duration']."
			});
		});";
        
        ob_start();
        
        // Add RokNewsFlash JS code
		echo "<script type=\"text/javascript\">\n/* <![CDATA[ */\n$jsinit\n/* ]]> */\n</script>\n";
        
        // Before Widget
        echo $args['before_widget'];
        
        // Widget Title
        if($instance['title'] != '')
 		echo $args['before_title'] . $instance['title'] . $args['after_title'];
 		
 		// Query Init
 		$catslug = get_category_by_slug($instance['catid']);
		
		$roknewsflash = new WP_Query('cat='.$catslug->term_id.'&posts_per_page='.$instance['article_count'].'&orderby='.$instance['itemsOrdering']);
 		
 		// Load Layouts	
		
		if(file_exists(TEMPLATEPATH. DS .'html'. DS .'plugins'. DS .'wp_roknewsflash'. DS .'default.php')) :	
	  		require(TEMPLATEPATH. DS .'html'. DS .'plugins'. DS .'wp_roknewsflash'. DS .'default.php');
	  	else :
	  		require($roknewsflash_plugin_path. DS .'tmpl'. DS .'default.php'); 
	  	endif;
		
		wp_reset_query();
        
        // After Widget
        echo $args['after_widget'];
        
        echo ob_get_clean();
    }
    
    function form($instance) {
    	global $roknewsflash_plugin_path, $roknewsflash_plugin_url;
        $defaults = $this->_defaults;
  		$instance = wp_parse_args((array) $instance, $defaults);
        foreach ($instance as $variable => $value)
        {
            $$variable = self::_cleanOutputVariable($variable, $value);
            $instance[$variable] = $$variable;
        }
        $this->_values = $instance;
        
        $categories = get_terms('category', 'hide_empty=0&orderby=name');
        
        ob_start();
        
        ?>
		
		<!-- Begin RokNewsFlash Widget Admin -->
		
		<div class="roknewsflash-admin-wrapper">
			<p>
		        <label class="rok-tips" data-tips="Adds the widget title." for="<?php echo $this->get_field_id('title'); ?>">
		        	<?php _e('Title:', 'roknewsflash'); ?>
		        </label>
		    	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
	    	</p>
	    	
	    	<p>
		    	<label class="rok-tips" data-tips="Whether to load builtin CSS files. Useful when you want to write your own style and don't want to overwrite." for="<?php echo $this->get_field_id('load_css'); ?>">
		    		<?php _e('Load built-in StyleSheet:', 'roknewsflash'); ?>
		    	</label>
				<input id="<?php echo $this->get_field_id('load_css'); ?>0" type="radio" value="0" name="<?php echo $this->get_field_name('load_css'); ?>" <?php if($instance['load_css'] == '0') echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('load_css'); ?>0"><?php _e('No', 'roknewsflash'); ?></label>&nbsp;&nbsp;
				<input id="<?php echo $this->get_field_id('load_css'); ?>1" type="radio" value="1" name="<?php echo $this->get_field_name('load_css'); ?>" <?php if($instance['load_css'] == '1') echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('load_css'); ?>1"><?php _e('Yes', 'roknewsflash'); ?></label>
			</p>				
			
			<p>
				<label class="rok-tips" data-tips="Theme type you want to load: light or dark styles." for="<?php echo $this->get_field_id('theme'); ?>">
					<?php _e('Theme:', 'roknewsflash'); ?>
				</label>
		    	<select class="widefat" id="<?php echo $this->get_field_id('theme'); ?>" name="<?php echo $this->get_field_name('theme'); ?>">
		      		<option value="light"<?php selected($instance['theme'], 'light'); ?>><?php _e('Light', 'roknewsflash'); ?></option>
		      		<option value="dark"<?php selected($instance['theme'], 'dark'); ?>><?php _e('Dark', 'roknewsflash'); ?></option>
		        </select>
			</p>
	    	
	    	<p>
		    	<label class="rok-tips" data-tips="Choose the Category holding posts to display." for="<?php echo $this->get_field_id('category'); ?>">
		    		<?php _e('Posts Category:', 'roknewsflash'); ?>
		    	</label>
				<select class="widefat" name="<?php echo $this->get_field_name('catid'); ?>" id="<?php echo $this->get_field_id('catid'); ?>">
					<?php foreach ($categories as $cat) { ?>
						<option value="<?php echo $cat->slug; ?>"<?php if($instance['catid'] == $cat->slug) : echo ' selected="selected"'; endif; ?>><?php echo $cat->name; ?></option>
					<?php } ?>
				</select>
			</p>
			
			<p>
				<label class="rok-tips" data-tips="You can change whether to use the posts content or excerpt." for="<?php echo $this->get_field_id('content_type'); ?>">
					<?php _e('Content Type:', 'roknewsflash'); ?>
				</label>
		    	<select class="widefat" id="<?php echo $this->get_field_id('content_type'); ?>" name="<?php echo $this->get_field_name('content_type'); ?>">
		      		<option value="content"<?php selected($instance['content_type'], 'content'); ?>><?php _e('Content', 'roknewsflash'); ?></option>
		      		<option value="excerpt"<?php selected($instance['content_type'], 'excerpt'); ?>><?php _e('Excerpt', 'roknewsflash'); ?></option>
		        </select>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('article_count'); ?>">
		        	<?php _e('Max Number of Posts:', 'roknewsflash'); ?>
		        </label>
		    	<input class="smallinputbox" id="<?php echo $this->get_field_id('article_count'); ?>" name="<?php echo $this->get_field_name('article_count'); ?>" type="text" value="<?php echo $instance['article_count']; ?>" />
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id('itemsOrdering'); ?>"><?php _e('Order:', 'roknewsflash'); ?></label>
		    	<select class="widefat" id="<?php echo $this->get_field_id('itemsOrdering'); ?>" name="<?php echo $this->get_field_name('itemsOrdering'); ?>">
		      		<option value="author"<?php selected($instance['itemsOrdering'], 'author'); ?>><?php _e('Author', 'roknewsflash'); ?></option>
		      		<option value="date"<?php selected($instance['itemsOrdering'], 'date'); ?>><?php _e('Date', 'roknewsflash'); ?></option>
		      		<option value="title"<?php selected($instance['itemsOrdering'], 'title'); ?>><?php _e('Title', 'roknewsflash'); ?></option>
		      		<option value="modified"<?php selected($instance['itemsOrdering'], 'modified'); ?>><?php _e('Modified', 'roknewsflash'); ?></option>
		      		<option value="menu_order"<?php selected($instance['itemsOrdering'], 'menu_order'); ?>><?php _e('Menu Order', 'roknewsflash'); ?></option>
		      		<option value="parent"<?php selected($instance['itemsOrdering'], 'parent'); ?>><?php _e('Parent', 'roknewsflash'); ?></option>
		      		<option value="id"<?php selected($instance['itemsOrdering'], 'id'); ?>>ID</option>
		        </select>
			</p>
			
			<p>
		    	<label class="rok-tips" data-tips="Display output as the post title or content/excerpt." for="<?php echo $this->get_field_id('usetitle'); ?>">
		    		<?php _e('Use Title or Content:', 'roknewsflash'); ?>
		    	</label>
				<input id="<?php echo $this->get_field_id('usetitle'); ?>0" type="radio" value="0" name="<?php echo $this->get_field_name('usetitle'); ?>" <?php if($instance['usetitle'] == '0') echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('usetitle'); ?>0"><?php _e('Content', 'roknewsflash'); ?></label>&nbsp;&nbsp;
				<input id="<?php echo $this->get_field_id('usetitle'); ?>1" type="radio" value="1" name="<?php echo $this->get_field_name('usetitle'); ?>" <?php if($instance['usetitle'] == '1') echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('usetitle'); ?>1"><?php _e('Title', 'roknewsflash'); ?></label>
			</p>
			
			<p>
		        <label class="rok-tips" data-tips="Text to display before newflashes." for="<?php echo $this->get_field_id('pretext'); ?>">
		        	<?php _e('PreText Label:', 'roknewsflash'); ?>
		        </label>
		    	<input class="widefat" id="<?php echo $this->get_field_id('pretext'); ?>" name="<?php echo $this->get_field_name('pretext'); ?>" type="text" value="<?php echo $instance['pretext']; ?>" />
	    	</p>
	    	
	    	<p>
		    	<label class="rok-tips" data-tips="Show prev/next controls." for="<?php echo $this->get_field_id('controls'); ?>">
		    		<?php _e('Show Controls:', 'roknewsflash'); ?>
		    	</label>
				<input id="<?php echo $this->get_field_id('controls'); ?>0" type="radio" value="0" name="<?php echo $this->get_field_name('controls'); ?>" <?php if($instance['controls'] == '0') echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('controls'); ?>0"><?php _e('No', 'roknewsflash'); ?></label>&nbsp;&nbsp;
				<input id="<?php echo $this->get_field_id('controls'); ?>1" type="radio" value="1" name="<?php echo $this->get_field_name('controls'); ?>" <?php if($instance['controls'] == '1') echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('controls'); ?>1"><?php _e('Yes', 'roknewsflash'); ?></label>
			</p>
			
			<p>
				<label class="rok-tips" data-tips="Duration in ms of cross-blend transition." for="<?php echo $this->get_field_id('duration'); ?>">
		        	<?php _e('Transition Duration (ms):', 'roknewsflash'); ?>
		        </label>
		    	<input class="widefat" id="<?php echo $this->get_field_id('duration'); ?>" name="<?php echo $this->get_field_name('duration'); ?>" type="text" value="<?php echo $instance['duration']; ?>" />
			</p>
			
			<p>
				<label class="rok-tips" data-tips="Time in ms of time between newsflashes." for="<?php echo $this->get_field_id('delay'); ?>">
		        	<?php _e('Transition Delay (ms):', 'roknewsflash'); ?>
		        </label>
		    	<input class="widefat" id="<?php echo $this->get_field_id('delay'); ?>" name="<?php echo $this->get_field_name('delay'); ?>" type="text" value="<?php echo $instance['delay']; ?>" />
			</p>
			
			<p>
				<label class="rok-tips" data-tips="Indent in px." for="<?php echo $this->get_field_id('news_indent'); ?>">
		        	<?php _e('News Indent (px):', 'roknewsflash'); ?>
		        </label>
		    	<input class="widefat" style="padding-right:10px;" id="<?php echo $this->get_field_id('news_indent'); ?>" name="<?php echo $this->get_field_name('news_indent'); ?>" type="text" value="<?php echo $instance['news_indent']; ?>" />
			</p>
			
			<p>
				<label class="rok-tips" data-tips="Length of characters to show in MooTools preview." for="<?php echo $this->get_field_id('preview_count'); ?>">
		        	<?php _e('Preview Length (characters):', 'roknewsflash'); ?>
		        </label>
		    	<input class="widefat" id="<?php echo $this->get_field_id('preview_count'); ?>" name="<?php echo $this->get_field_name('preview_count'); ?>" type="text" value="<?php echo $instance['preview_count']; ?>" />
			</p>
		</div>
		
		<!-- End RokNewsFlash Widget Admin -->
	
		<?php
	
        echo ob_get_clean();
    }
    
    /********** Bellow here should not need to be changed ***********/

    function __construct() {
        if (empty($this->short_name) || empty($this->long_name)) {
            die("A widget must have a valid type and classname defined");
        }
        $widget_options = array('classname' => $this->css_classname, 'description' => __($this->description));
        $control_options = array('width' => $this->width, 'height' => $this->height);
        parent::__construct($this->short_name, $this->long_name, $widget_options, $control_options);
    }

    function _cleanOutputVariable($variable, $value) {
        if (is_string($value)) {
        	return htmlspecialchars($value);
        }
        elseif (is_array($value)) {
            foreach ($value as $subvariable => $subvalue) {
                $value[$subvariable] = GantryWidgetRokMenu::_cleanOutputVariable($subvariable, $subvalue);
            }
            return $value;
        }
        return $value;
    }

    function _cleanInputVariable($variable, $value) {
        if (is_string($value)) {
            return stripslashes($value);
        }
        elseif (is_array($value)) {
            foreach ($value as $subvariable => $subvalue) {
                $value[$subvariable] = GantryWidgetRokMenu::_cleanInputVariable($subvariable, $subvalue);
            }
            return $value;
        }
        return $value;
    }

    function widget($args, $instance){
        global $gantry;
 		extract($args);
        $defaults = $this->_defaults;
        $instance = wp_parse_args((array) $instance, $defaults);
        foreach ($instance as $variable => $value)
        {
            $$variable = self::_cleanOutputVariable($variable, $value);
            $instance[$variable] = $$variable;
        }
        $this->render($args, $instance);
    }

}

RokNewsFlash::$plugin_path = dirname($plugin);
RokNewsFlash::$plugin_url = WP_PLUGIN_URL.'/'.basename(RokNewsFlash::$plugin_path);

add_action('widgets_init', array('RokNewsFlash', 'init'));
add_action('wp_print_styles', array('RokNewsFlash', 'roknewsflash_styles'));
add_action('wp_print_scripts', array('RokNewsFlash', 'roknewsflash_scripts'));

// Load Language

load_plugin_textdomain('roknewsflash', false, basename($roknewsflash_plugin_path). DS .'languages'. DS);

// MooTools Enqueue Script

add_action('init', 'roknewsflash_mootools_init', -50);

function roknewsflash_mootools_init(){
	global $roknewsflash_plugin_url;
    wp_register_script('mootools.js', $roknewsflash_plugin_url.'/tmpl/js/mootools.js');
	wp_enqueue_script('mootools.js');
}


?>