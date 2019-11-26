<?php

 // --------------------------------------------------

	class LDC_Vault {

 // --------------------------------------------------
 //
 // static
 //
 // --------------------------------------------------

	private static $custom_post_type = '', $name = '', $prefix = '';

 // --------------------------------------------------

	public static function init_action(){
		if(current_user_can('manage_options')){
			register_post_type(self::$custom_post_type, array(
				'labels' => array(
					'name' => self::$name,
					'singular_name' => self::$name,
				),
				'public' => true,
				'exclude_from_search' => true,
				'show_in_rest' => false,
				'menu_icon' => 'dashicons-vault',
				'capability_type' => 'page',
				'supports' => array('title'),
			));
		}
	}

 // --------------------------------------------------

	public static function setup(){
		self::$name = str_replace('_', ' ', __CLASS__);
		self::$prefix = strtolower(__CLASS__) . '_';
		self::$custom_post_type = self::$prefix . 'post';
		add_action('init', array(__CLASS__, 'init_action'));
		add_filter('template_include', array(__CLASS__, 'template_include_filter'));
		add_filter('rwmb_meta_boxes', array(__CLASS__, 'rwmb_meta_boxes_filter'));
	}

 // --------------------------------------------------

	public static function template_include_filter($original_template = ''){
		if(is_post_type_archive(self::$custom_post_type)){
			return plugin_dir_path(__FILE__) . 'archive.php';
		}
		if(is_singular(self::$custom_post_type)){
			return plugin_dir_path(__FILE__) . 'single.php';
		}
		return $original_template;
	}

 // --------------------------------------------------

	public static function rwmb_meta_boxes_filter($meta_boxes = array()){
		global $wpdb;
		$post_types = array();
		foreach(get_post_types(array(
			'_builtin' => false,
		), 'objects') as $post_type){
			$post_types[$post_type->name] = $post_type->label;
		}
		unset($post_types[self::$custom_post_type]);
		$fields = array('ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_name', 'post_modified', 'post_modified_gmt', 'post_parent', 'guid');
		$fields = array_combine($fields, $fields);
		$meta_fields = array();
		foreach($wpdb->get_col('SELECT DISTINCT meta_key FROM ' . $wpdb->postmeta . ' ORDER BY meta_key ASC') as $meta_field){
			if($meta_field[0] != '_'){
				$meta_fields[$meta_field] = $meta_field;
			}
		}
		$meta_compare = array('=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP', 'RLIKE');
		$meta_compare = array_combine($meta_compare, $meta_compare);
		$meta_type = array('NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED');
		$meta_type = array_combine($meta_type, $meta_type);
		$meta_boxes[] = array(
			'fields' => array(
				array(
					'id' => self::$prefix . 'bootstrap_version',
					'name' => '¿Cuál versión de Bootstrap utiliza el tema <i>' . wp_get_theme() . '</i>?',
					'options' => array(
						4 => 'Bootstrap 4',
						3 => 'Bootstrap 3',
						0 => 'Ninguna',
					),
					'type' => 'select_advanced',
				),
				array(
					'id' => self::$prefix . 'post_type',
					'name' => '¿Cuál <i>post type</i> deseas?',
					'options' => $post_types,
					'type' => 'select_advanced',
				),
				array(
					'id' => self::$prefix . 'fields',
					'multiple' => true,
					'name' => '¿Cuáles campos de la tabla <i>' . $wpdb->posts . '</i> deseas?',
					'options' => $fields,
					'type' => 'select_advanced',
				),
				array(
					'id' => self::$prefix . 'meta_fields',
					'multiple' => true,
					'name' => '¿Cuáles campos de la tabla <i>' . $wpdb->postmeta . '</i> deseas?',
					'options' => $meta_fields,
					'type' => 'select_advanced',
				),
				array(
					'type' => 'divider',
				),
				array(
					'id' => self::$prefix . 'container_class',
					'name' => 'Container class',
					'type' => 'text',
				),
				array(
					'type' => 'divider',
				),
				array(
					'id' => self::$prefix . 'meta_key',
					'name' => 'Custom field key',
					'type' => 'text',
				),
				array(
					'id' => self::$prefix . 'meta_value',
					'name' => 'Custom field value',
					'type' => 'text',
				),
				array(
					'id' => self::$prefix . 'meta_compare',
					'name' => 'Operator to test',
					'options' => $meta_compare,
					'type' => 'select_advanced',
					'std' => '=',
				),
				array(
					'id' => self::$prefix . 'meta_type',
					'name' => 'Custom field type',
					'options' => $meta_type,
					'type' => 'select_advanced',
					'std' => 'CHAR',
				),
			),
			'id' => self::$prefix . 'meta_box',
			'post_types' => self::$custom_post_type,
			'title' => self::$name,
			'validation' => array(
				'rules' => array(
					self::$prefix . 'bootstrap_version' => array(
						'required' => true,
					),
					self::$prefix . 'post_type' => array(
						'required' => true,
					),
				),
			),
		);
		return $meta_boxes;
	}

 // --------------------------------------------------
 //
 // dynamic
 //
 // --------------------------------------------------

	private $bootstrap_version = 0, $container_class = '', $fields = array(), $footer = '', $header = '', $meta_compare = '=', $meta_fields = array(), $meta_key = '', $meta_type = 'CHAR', $meta_value = '', $options = array(), $post_id = 0, $post_type = '';

 // --------------------------------------------------

	public function __construct($post_id = 0){
		if(!current_user_can('manage_options')){
			wp_die(__('You need a higher level of permission.'), self::$name);
		}
		$this->post_id = $post_id;
		if(get_post_type($this->post_id) != self::$custom_post_type){
			wp_die(__('Invalid post type.'), self::$name);
		}
		$bootstrap_version = get_post_meta($post_id, self::$prefix . 'bootstrap_version', true);
		if($bootstrap_version){
			if(!in_array($bootstrap_version, array(3, 4))){
				wp_die(__('Invalid Bootstrap version.'), self::$name);
			}
			$this->bootstrap_version = $bootstrap_version;
		}
		$fields = get_post_meta($post_id, self::$prefix . 'fields');
		if($fields){
			$this->fields = $fields;
		}
		$meta_fields = get_post_meta($post_id, self::$prefix . 'meta_fields');
		if($meta_fields){
			$this->meta_fields = $meta_fields;
		}
		if(!$fields and !$meta_fields){
			wp_die(__('Invalid request.'), self::$name);
		}
		$this->options['stateSave'] = true;
		$this->options['language'] = array(
			'url' => 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json',
		);
		$post_type = get_post_meta($post_id, self::$prefix . 'post_type', true);
		if($post_type){
			$this->post_type = $post_type;
		}
		if(!post_type_exists($this->post_type)){
			wp_die(__('Invalid post type.'), self::$name);
		}
		$this->container_class = get_post_meta($post_id, self::$prefix . 'container_class', true);
		$this->meta_compare = get_post_meta($post_id, self::$prefix . 'meta_compare', true);
		$this->meta_key = get_post_meta($post_id, self::$prefix . 'meta_key', true);
		$this->meta_type = get_post_meta($post_id, self::$prefix . 'meta_type', true);
		$this->meta_value = get_post_meta($post_id, self::$prefix . 'meta_value', true);
	}

 // --------------------------------------------------

	public function enqueue_styles_and_scripts(){
		$bootstrap_version = $this->bootstrap_version;
		$options = $this->options;
		add_action('wp_enqueue_scripts', function() use($bootstrap_version, $options){
			wp_enqueue_script('data-tables', 'https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', array('jquery'), '1.10.20');
			$inline_script = 'jQuery(function($){ $("#' . self::$prefix . 'data_table").DataTable(' . wp_json_encode($options) . '); });';
			switch($bootstrap_version){
				case 0:
					wp_add_inline_script('data-tables', $inline_script);
					wp_enqueue_style('data-tables', 'https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css', array(), '1.10.20');
					break;
				case 3:
					wp_enqueue_script('data-tables-bootstrap', 'https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js', array('data-tables'), '1.10.20');
					wp_add_inline_script('data-tables-bootstrap', $inline_script);
					wp_enqueue_style('data-tables-bootstrap', 'https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap.min.css', array(), '1.10.20');
					break;
				case 4:
					wp_enqueue_script('data-tables-bootstrap-4', 'https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js', array('data-tables'), '1.10.20');
					wp_add_inline_script('data-tables-bootstrap-4', $inline_script);
					wp_enqueue_style('data-tables-bootstrap-4', 'https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css', array(), '1.10.20');
					break;
			}
		});
	}

 // --------------------------------------------------

	public function get_footer(){
		get_footer($this->footer);
	}

 // --------------------------------------------------

	public function get_header(){
		get_header($this->header);
	}

 // --------------------------------------------------

	public function render_data_table(){
		$html = '<div class="' . $this->container_class . '">';
		$html .= '<h2>' . get_the_title($this->post_id) . '</h2>';
		$args = array(
			'post_status' => 'any',
			'post_type' => $this->post_type,
			'posts_per_page' => -1,
			'order' => 'DESC',
		);
		if($this->meta_compare != '='){
			$args['meta_compare'] = $this->meta_compare;
		}
		if($this->meta_key){
			$args['meta_key'] = $this->meta_key;
		}
		if($this->meta_value){
			$args['meta_value'] = $this->meta_value;
		}
		if($this->meta_type != 'CHAR'){
			$args['meta_type'] = $this->meta_type;
		}
		$args = apply_filters(self::$prefix . 'query_args', $args, $this->post_id);
		$posts = get_posts($args);
		$posts = apply_filters(self::$prefix . 'posts', $posts, $this->post_id);
		if($posts){
			if($this->bootstrap_version){
				$html .= '<div class="table-responsive">';
				$html .= '<table id="' . self::$prefix . 'data_table" class="table table-striped table-bordered table-hover">';
			} else {
				$html = '<div>';
				$html .= '<table id="' . self::$prefix . 'data_table">';
			}
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<th>#</th>';
			if($this->fields){
				foreach($this->fields as $key){
					$key = apply_filters(self::$prefix . 'field_key', $key, $this->post_id);
					$html .= '<th>';
					$html .= $key;
					$html .= '</th>';
				}
			}
			if($this->meta_fields){
				foreach($this->meta_fields as $key){
					$key = apply_filters(self::$prefix . 'meta_field_key', $key, $this->post_id);
					$html .= '<th>';
					$html .= $key;
					$html .= '</th>';
				}
			}
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';
			$order = 'DESC';
			if(!empty($args['order'])){
				if($args['order'] == 'ASC'){
					$order = 'ASC';
				}
			}
			if($order == 'DESC'){
				$c = count($posts);
			} else {
				$c = 1;
			}
			foreach($posts as $post){
				$html .= '<tr>';
				$html .= '<td>';
				$html .= $c;
				$html .= '</td>';
				if($this->fields){
					foreach($this->fields as $key){
						$value = $post->$key;
						$value = apply_filters(self::$prefix . 'field_value', $value, $key, $post, $this->post_id);
						$html .= '<td>';
						$html .= $value;
						$html .= '</td>';
					}
				}
				if($this->meta_fields){
					foreach($this->meta_fields as $key){
						if(metadata_exists('post', $post->ID, $key)){
							$value = get_post_meta($post->ID, $key, true);
							$value = maybe_serialize($value);
							$value = apply_filters(self::$prefix . 'meta_field_value', $value, $key, $post, $this->post_id);
						} else {
							$value = 'Metadata does not exist.';
						}
						$html .= '<td>';
						$html .= $value;
						$html .= '</td>';
					}
				}
				$html .= '</tr>';
				if($order == 'DESC'){
					$c --;
				} else {
					$c ++;
				}
			}
			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

 // --------------------------------------------------

	}

 // --------------------------------------------------
