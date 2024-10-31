<?php
/*
===================================================================
Plugin Name:  ONet Header Linkifier
Plugin URI:   http://onetdev.com/repo/onet-header-linkifier
Description:  For advanced users! Github-like header parser for your posts, pages and custom contents. Also lets you fetch TOC for any content.
Git:          https://bitbucket.org/orosznyet/onet-header-linkifier
Version:      1.12
Author:       József Koller
Author URI:   http://www.onetdev.com
Text Domain:  onetaha
Domain Path:  /lang

===================================================================

Copyright (C) 2013 József Koller

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

===================================================================
*/


// Register init function
add_action( 'init', 'ONetHeaderLinkifier_wrap' );
function ONetHeaderLinkifier_wrap() {
global $ONetHeaderLinkifier_inst;
	$ONetHeaderLinkifier_inst = new ONetHeaderLinkifier();
}

// Installation stuff
register_activation_hook(__FILE__,'ONetHeaderLinkifier_install');
function ONetHeaderLinkifier_install () {
	
	# Check if PHP version is too low and throw error if yes.
	if ( version_compare("5.0", phpversion(), '>') ) {
		echo '<strong>'.__('Plugin installation failed.','onetaha').'</strong> '.
			sprintf(__('Sorry, ONet Header Linkifier requires at least PHP 5.0 but your version is PHP %s.','onetaha'),phpversion());
		exit;
	}

	# Otherwise execute little code
	else {
		#@chmod($this->cache_folder,755);// Try to change CHMOD on Cache folder 
	}
}

// Then the class
class ONetHeaderLinkifier {
	protected $cap = null;                 // used capabilities
	protected $opts = array();             // options
	protected $home_folder = null;         // Plugin home directory
	protected $cache_folder = null;        // Cache folder
	protected $prefix = "onetaha_";        // Prefix used for settings and stuffs like that

	

	/******************************************
	* CONSTRUCTING & REGISTERING              *
	******************************************/



	/**
	* Register all important functions and filters
	* @since 1.0
	* @param void
	* @return void
	**/
	public function __construct() {
		# Set some default variable
		$this->plugin = dirname(plugin_basename( __FILE__ ));
		$this->home_folder = __DIR__;  // Plugin home directory
		$this->cache_folder = $this->home_folder."/cache"; // Cache folder

		# Load localizations. You can add you own localisation file (eg: onetrt-hu_HU.mo) into lang folder.
		load_plugin_textdomain('onetaha', false, $this->home_folder.'/lang');

		# Load options
		$default_opts = array(
			"enable" =>       true,                   // Global variable
			"link_style" =>   "git",                  // Linkify method
			"add_link" =>     false,                  // Add a link element to a particular headers
			"smoothy" =>      true,                   // Include Smooty on client side
			"load_css" =>     true,                   // Load CSS style only for GIT link style
			"cache" =>        true,                   // Cache parsed pages
			"taglist" =>      array("h1","h2","h3","h4","h5","h6"), // List of tabs to apply
			"post_type" =>    array("post","page")    // Default post type handler
			);
		$custom_options = array();
		foreach ($default_opts AS $id => $def)
			$custom_options[$id] = get_option($this->prefix.$id, $def);

		# Set instance options variable
		$this->opts = (object)$custom_options;
		
		# Register actions
		add_action('admin_init',                    array($this, 'register_settings') );  // Add menu item under "Tools"
		add_action('wp_enqueue_scripts',            array($this, 'register_client_files') ); // Register all the important js/css

		# Apply filters
		add_filter('plugin_action_links_'.plugin_basename( __FILE__ ), array($this, 'plugin_page_link')); // Link to the settings
		add_filter('the_content',                   array($this, 'pre_parse') );
		add_filter('onet_aha_parse',                array($this, 'perform_parse') );

		# Capabiliti(es)
		$this->cap = apply_filters( 'onetrt_cap', 'manage_options' );
	}



	/******************************************
	* WORDPRESS HACKS                         *
	******************************************/



	/**
	* Adds a simple link in plugin manager
	* @since 1.1
	* @param (array) active links
	* @param (array) extended links
	**/
	function plugin_page_link( $links ) {
		if (current_user_can( $this->cap)) array_push( $links, '<a href="options-reading.php">'.__("Settings","onetaha").'</a>' );
		return $links;
	}



	/******************************************
	* CLIEN SIDE STUFF                        *
	******************************************/



	/**
	* Register all neccessary JS
	**/
	function register_client_files () {
		wp_register_script( 'onet-smoothy', plugins_url($this->plugin."/assets/js/jquery.onet-smoothy.js"), array('jquery'), "1.0" );
		wp_register_style( 'onet-header-linkifier-git', plugins_url($this->plugin."/assets/css/git.css") );

		if ($this->opts->smoothy) wp_enqueue_script('onet-smoothy');
		if ($this->opts->link_style == "git" && $this->opts->load_css) wp_enqueue_style('onet-header-linkifier-git');
	}



	/******************************************
	* ADMINISTRATION INTERFACE                *
	******************************************/


	/**
	* Generates and returns meta data for settings API
	* @since 1.0
	* @param void
	* @return void
	**/
	public function get_settings_meta () {
		# Set each settings fields
		$fields = array();

		# Enable code to run globally
		$fields['enable'] = (object)array(
			"id" => $this->prefix."enable",
			"name" => $this->prefix."enable",
			"label" => '<label for="'.$this->prefix.'enable">'.__('Parser' , 'onetaha' ).'</label>',
			"opt_name" => "enable",
			"html_output" => '<input type="checkbox" name="'.$this->prefix.'enable" id="'.$this->prefix.'enable" '.($this->opts->enable ? ' checked="checked"' : '').'/> '.__('Enable parse','onetaha'),
			"callback" => array($this,"is_on")
			);
		#return $fields; // debug purposes

		# Add self links at the begining of each header
		$fields['link_style'] = (object)array(
			"id" => $this->prefix."link_style",
			"name" => $this->prefix."link_style",
			"label" => __('Linkify style' , 'onetaha' ),
			"opt_name" => "link_style",
			"html_output" =>
				'<input type="radio" name="'.$this->prefix.'link_style" id="'.$this->prefix.'link_style_git" value="git" '.($this->opts->link_style == "git" ? ' checked="checked"' : '').'/> '.__("GitHub like","'.$this->prefix.'").'<br/>'.
				'<input type="radio" name="'.$this->prefix.'link_style" id="'.$this->prefix.'link_style_convert" value="convert" '.($this->opts->link_style == "convert" ? ' checked="checked"' : '').'/> '.__("Convert headers into links",'onetaha').
				'<p class="description">'.__('Please note that in order to avoid conflicts the convert method will overwrite the links in your headers. (In Git mode header\'s content will be untouched.)','onetaha').'</p>',
			"callback" => array($this,"sanitize_link_style")
			);

		# Add option for auto format git style links
		$fields['load_css'] = (object)array(
			"id" => $this->prefix."load_css",
			"name" => $this->prefix."load_css",
			"label" => '<label for="'.$this->prefix.'load_css">'.__('Git style settings' , 'onetaha' ).'</label>',
			"opt_name" => "load_css",
			"html_output" => '<input type="checkbox" name="'.$this->prefix.'load_css" id="'.$this->prefix.'load_css" '.($this->opts->load_css ? ' checked="checked"' : '').'/> '.__('Load predefined style','onetaha').
				'<p class="description">'.__('Turn this option off if you want to use custom link styles.','onetaha').'</p>',
			"callback" => array($this,"is_on")
			);

		# Smoothy loader. Learn more in js/smoothy.js
		$fields['smoothy'] = (object)array(
			"id" => $this->prefix."smoothy",
			"name" => $this->prefix."smoothy",
			"label" => __('Smooth scroll' , 'onetaha' ),
			"opt_name" => "smoothy",
			"html_output" => '<input type="checkbox" name="'.$this->prefix.'smoothy" id="'.$this->prefix.'smoothy" '.($this->opts->smoothy ? ' checked="checked"' : '').'/> '.__('Use smooth scroll for internal links','onetaha').
				'<p class="description">'.__('Smooth scroll will be applied for every link on every page, if the link referring to a particular point of the actual document. (External link\' behaviour will remain the same.)','onetaha').'</p>',
			"callback" => array($this,"is_on")
			);

		# Straightforward. Use cache
		$fields['cache'] = (object)array(
			"id" => $this->prefix."cache",
			"name" => $this->prefix."cache",
			"label" => __('Cache' , 'onetaha' ),
			"opt_name" => "cache",
			"html_output" => '<input type="checkbox" name="'.$this->prefix.'cache" id="'.$this->prefix.'cache" '.($this->opts->cache ? ' checked="checked"' : '').'/> '.__('Use cache','onetaha').
				'<p class="description">'.__('For longer articles it might be better to turn chace on.','onetaha').'</p>',
			"callback" => array($this,"is_on")
			);

		# Taglist
		$fields['taglist'] = (object)array(
			"id" => $this->prefix."taglist",
			"name" => $this->prefix."taglist",
			"label" => '<label for="'.$this->prefix.'taglist">'.__('Tags to parse' , 'onetaha' ).'</label>',
			"opt_name" => "taglist",
			"html_output" => "",
			"callback" => array($this,"sanitize_array_key")
			);
		$html = "";
		for ($i=1;$i<=6;$i++) {
			$html .= '<input type="checkbox" name="'.$this->prefix.'taglist[h'.$i.']" id="'.$this->prefix.'taglist_h'.$i.'" '.(in_array("h".$i,$this->opts->taglist) ? ' checked="checked"' : "").' /> <label for="'.$this->prefix.'taglist_h'.$i.'">'.__('H'.$i,'onetaha').'</label> &nbsp;&nbsp;&nbsp;';
		}
		$fields['taglist']->html_output = $html;


		# Post type content filter
		$fields['post_type'] = (object)array(
			"id" => $this->prefix."post_type",
			"name" => $this->prefix."post_type",
			"label" => '<label for="'.$this->prefix.'post_type">'.__('Add support for' , 'onetaha' ).'</label>',
			"opt_name" => "post_type",
			"html_output" => '',
			"callback" => array($this,"sanitize_array_key")
			);
		$post_types = $this->get_post_types();
		$html = "";
		foreach ($post_types AS $name => $type) $html .= '<input type="checkbox" name="'.$this->prefix.'post_type['.$name.']" '.( in_array($name, $this->opts->post_type) ? ' checked="checked"' : "").'/> '.$type->labels->name.' </br>';
		$fields['post_type']->html_output = $html;

		return $fields;
	}
	
	/**
	* Register extra settings via Settings API
	* @since 1.0
	* @param void
	* @return void
	**/ 
	public function register_settings () {
	global $wpdb,$wp_settings_sections, $wp_settings_fields;

		# Get our own section, cool!
		add_settings_section($this->prefix.'settings', __("ONet Header Linkifier settings","onetaha"), null, 'reading' );

		# Convert to link field
		$fields = $this->get_settings_meta();
		foreach ($fields AS $field_name => $field) {
			add_settings_field(
				$field->id,
				$field->label,
				array($this,"output_settings_field"),
				'reading',
				$this->prefix.'settings',
				(array)$field
			);
			register_setting('reading', $field->id, $field->callback);
		}
	}

	/**
	* Echo the field for each settings
	* @since 1.0
	* @param void
	* @return void
	**/
	public function output_settings_field ($inst) {
		echo $inst['html_output'];
	}

	/**
	* Sanitize settings like post_type, taglist and link_style
	* There are more than one functions below they are almost the same.
	* @since: 1.0
	* @param ()
	**/
	public function is_on ($input) { return $input ? "1" : "0"; }
	public function sanitize_link_style ($input) { return in_array($input,array("git","convert")) ? $input : "git"; }
	public function sanitize_array_key ($input) {
		if (!is_array($input)) return array();
		return array_keys($input);
	}




	/******************************************
	* PARSING POSTS                          *
	******************************************/


	/**
	* Perform parse on post contents based on settings
	* This function called by WP so the first parameter is mandatory alongside the returning HTML.
	* @since 1.0
	* @param (string) HTML input
	* @return (string) updated content
	**/
	public function pre_parse ($content) {
	global $post;
		if (is_single() && $this->opts->enable == 1 && (empty($post) || in_array($post->post_type, $this->opts->post_type)) )
			return $this->perform_parse($content);
		return $content;
	}

	/**
	* Perform parse on post contents based on settings
	* This function called by WP so the first parameter is mandatory alongside the returning HTML.
	* @since 1.0
	* @param (string) HTML input
	* @param [(boolean) force parser]
	* @return (string) updated content
	**/
	public function perform_parse ($content,$force=false) {
		# Serve cached content
		$inputname = $this->cache_filename($content);
		$cached = $this->is_content_cached($content);
		if (!$force && $cached) {
			$from_cache = $this->load_cache($cached)."<!-- ".__("ONet Header Linkifier: Loaded from inner cache","onetaha")." -->";
			if ($from_cache != null) return $from_cache;
		}

		# Otherwise do the stuff
		else if ( is_array($this->opts->taglist) && !empty($this->opts->taglist) ) {
			$replace = $this->get_toc($content,false);

			# Perform final replacement
			foreach ($replace AS $data) {
				$content = str_replace($data->original_html, $data->replace_html, $content);
			}

			# Finally push content into cache (it will handle "cache off" state)
			$this->push_cache($content,$inputname);
		}
		return $content."<!-- ".__("ONet Header Linkifier: Recently parsed","onetaha")." -->";
	}

	/**
	* Get all headers from a article
	* @since 1.0
	* @param (string) HTML input
	* @param [(boolean) preserv hierarchy]
	* @return (string) match set
	**/
	function get_toc ($content, $preserved=true) {
		$matchset = array();
		$used_id = array();

		# Go with tag scanning
		sort($this->opts->taglist);
		# get target elements from the document
		$exp = "/<(".implode("|", $this->opts->taglist).")([^>]*)\>(.{0,1000})<\/(".implode("|", $this->opts->taglist).")>/i";
		$res = preg_match_all($exp,$content,$matches);

		if ($res) :
		for ($i=0;$i<count($matches[0]);$i++) {
			# Generate all inner resources
			$inner_text = preg_replace("/<[^>]*>/"," ",$matches[3][$i]);
			$tag_id = sanitize_title_with_dashes( remove_accents($inner_text) );
			
			# If tag with the same ID already exists find another one
			if (isset($used_id[$tag_id])) {
				$free = 1;
				while ($free > 0) {
					if (isset($used_id[$tag_id."-".$free])) $free++;
					else {
						$tag_id .= "-".$free;
						$free=0;
					}
				}
			}

			# Append matchset queue item
			$used_id[$tag_id] = 1;
			$replace = "";

			# Generate update class for header
			$attr = trim($matches[2][$i]);
			if (preg_match("/class([ ]?)=([ ]?)(\'|\\\")([^\'\"]*)/i",$attr,$attr_match)) {
				$attr = str_replace($attr_match[0], $attr_match[0]." onet-header-anchor-parent ", $attr);
			} else $attr .= ' class="onet-header-anchor-parent" ';

			# Set replacer text based on linkify style
			if ($this->opts->link_style == "git") {
				$replace = '<'.$matches[1][$i].' '.$attr.'>'.
					'<a id="'.$tag_id.'" href="#'.$tag_id.'" class="onet-header-anchor onet-link-style-git from-tagname-'.$matches[1][$i].'"><span></span></a>'.
					$matches[3][$i].
					'</'.$matches[1][$i].'>';
			} else {
				$replace = '<'.$matches[1][$i].' '.$attr.'>'.
					'<a id="'.$tag_id.'" href="#'.$tag_id.'" class="onet-header-anchor onet-link-style-convert from-tagname-'.$matches[1][$i].'">'.
						preg_replace('/<\/?a[^>]*>/i','',$matches[3][$i]).
					'</a>'.
					'</'.$matches[1][$i].'>';
			}

			# Compile actual array
			$res = (object)array(
				"original_html" => $matches[0][$i],
				"replace_html" => $replace,
				"id" => $tag_id,
				"tag_name" => strtolower($matches[1][$i]),
				"title" => $inner_text,
				"child_tags" => array()
				);

			# Append results
			$matchset[] = $res;
		}
		endif;

		# Preserve hierarchical order. This is iteration separated from the previous one for reasons.
		if ($preserved) return $this->toc_convert_to_hierarchical($matchset);
		# Otherwise return results
		else return $matchset;
	}

	/**
	* Convert exported items to hierarchical list
	* @since 1.0
	* @param (string) HTML input
	* @return (string) match set
	**/
	private function toc_convert_to_hierarchical ($items) {
		# get tags ready
		$tags = $this->opts->taglist;
		rsort($tags);

		# Launch organizer routine
		foreach ($tags AS $tag) :
			$move_ids = array();
			$all = count($items)-1;

			for ($i=$all;$i>=0;$i--) :
				$item = $items[$i];
				$id = $item->id;

				# If actual item is invalid skip the rest
				if (empty($item)) {
					unset($items[$i]);
					continue;
				}

				# Create child tag if not exists
				if (empty($item->child_tags)) {
					$items[$i]->child_tags = array();
					$item->child_tags = array();
				}

				# If item's tagname matching the actual tag
				if ($item->tag_name == $tag) {
					$move_ids[] = $i;
				}

				# If current tag is higher
				else if ( version_compare($item->tag_name,$tag,"<") && !empty($move_ids) && count($move_ids) > 0 ) {
					sort($move_ids);
					for ($n=0;$n<count($move_ids);$n++) {
						$tid = $move_ids[$n];
						if (!empty($items[$tid])) {
							$items[$i]->child_tags[] = $items[$tid];
							unset($items[$tid]);
						}
					}
				}

				# Do nothing
				else { }
			endfor;
		endforeach;
		//print_r($items);

		return $items;
	}
	


	/******************************************
	* CACHING METHODS                         *
	******************************************/



	/**
	* Put content into cache
	* @since 1.0
	* @param (string) content
	* @param [(string) request predefined filename]
	* @return (string) name for cached content OR (boolean) false if cache if off
	**/
	public function push_cache ($content,$request_name=null) {
		# Check if cache is on
		if (!$this->opts->cache) return false;
		# Generate custom name hash
		$name = $this->cache_filename($content);
		$file_path = $this->cache_folder."/".(!$request_name ? $name : $request_name).".chc";
		# Push the content to the file and remove previous one for sure
		if (file_exists($file)) unlink($file);

		$file = fopen($file_path,"w+");
		if ($file) {
			fwrite($file,$content);
			fclose($file);
		} else return false;
	
		return $name;
	}

	/**
	* Retrieve content from cache by its name
	* @since 1.0
	* @param (string) filename
	* @return (string) cache content or null if invalid name was provided
	**/
	public function load_cache ($name) {
		# check if extension is missing
		if (!preg_match("/\.chc$/", $name)) $name = $name.".chc";
		# look for the cached file
		if (file_exists($this->cache_folder."/".$name)) return file_get_contents($this->cache_folder."/".$name);
		return null;
	}

	/**
	* Load cache content
	* @since 1.0
	* @param void
	* @return void
	**/
	public function clean_cache () {
		$files = glob($this->cache_folder."/*.chc");
		foreach($files as $file) {
			if (is_file($file) && time() - filemtime($file) >= 2*24*60*60) {
				@unlink($file);
			}
		}
		return null;
	}

	/**
	* Check if cache file exists based on content
	* @since 1.0
	* @param (string) cached content
	* @return (boolean|string) cached content or false if no cached content
	**/
	public function is_content_cached ($content) {
		# Clean cache randomly (1% chance)
		if (rand(1,100) == 50) $this->clean_cache();

		# Check if cache folder is writeable or not
		if ( $this->opts->cache && is_writable($this->cache_folder) ) {
			$file = $this->cache_filename($content).".chc";
			# return the filename if exists
			if (file_exists($this->cache_folder."/".$file)) return $file;
			else return false;
		}
		return false;
	}

	/**
	* Generate unique filename for a content
	* @since 1.0
	* @param (string) content
	* @return (string) unique filename
	**/
	public function cache_filename ($content) {
		return wp_hash(
			$content.serialize($this->opts->taglist).($this->opts->convert_to_link ? "1" : "0").($this->opts->add_link ? "1" : "0"),
			"md5"
		);
	}


	/******************************************
	* UTILITY METHODS                         *
	******************************************/



	/**
	* Get available post types from WP
	* @since 1.0
	* @param (boolean) if match set is filtered or not
	* @return (array) set of build in and custom post types
	**/
	function get_post_types ($filtered=false) {
		$types = get_post_types('','names');
		if ($filtered == false) $forbidden = array('attachment'=>1,'revision'=>1,'nav_menu_item'=>1,'feedback'=>1);
		$ret = array();

		foreach ($types AS $type) {
			if (!isset($forbidden[$type])) $ret[$type] = get_post_type_object($type);
		}
		
		return $ret;
	}
}
?>