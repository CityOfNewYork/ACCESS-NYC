<?php
/**
 * Flippercode Wordpress Widget Builder Class.
 * @author Flipper Code <hello@flippercode.com>
 * @package CORE
 */

if ( ! class_exists( 'Flippercode_Widget_Builder' ) ) {

	/**
	 * Initilize Widget Builder.
	 *
	 * @author Flipper Code <hello@flippercode.com>
	 * @version 1.0.0
	 * @package Core
	 */
	class Flippercode_Widget_Builder extends WP_Widget {
		
		/**
		 * [plugin  class]
		 *
		 * @var [type]
		 */
		private $pluginClass;
		/**
		 * [widget registering class]
		 *
		 * @var [type]
		 */
		private $widgetClass;
		/**
		 * [$widget_label description]
		 *
		 * @var [type]
		 */
		private $widget_label;
		/**
		 * [$applicable_filters description]
		 *
		 * @var [type]
		 */
		private $applicable_filters;
		/**
		 * WP widgets description
		 *
		 * @var [description]
		 */
		private $description;
		/**
		 * FormElements description
		 *
		 * @var formElements
		 */
		private $formElements;
		/**
		 * Is Shortcode Boolean
		 *
		 */
		private $widgets_shortcode;
		/**
		 * Is Shortcode Field Value.
		 *
		 */
		private $widgets_shortcode_field;
		

		public function __construct( $widget_params ) {
			
				
			if ( ! empty( $widget_params ) ) {
				
				foreach( $widget_params as $param => $value ) {
					$this->$param = $value;
				}
				
				parent::__construct( $this->widgetClass,
				      $this->widget_label,
				array( 'description' => $this->description ) );
				
			}
		}
		
		/**
		 * Display widget at frontend.
		 *
		 * @param  array $args     Widget Arguments.
		 * @param  int   $instance Instance of Widget.
		 */

		function widget( $args, $instance ) {

			global $wpdb,$map;
			extract( $args );

			foreach ( $this->applicable_filters as $key => $values ) {
				$instance[ $values ] = apply_filters( $key, $instance[ $values ], $instance );
	      	}

            if ( class_exists( $this->pluginClass ) and method_exists( $this->pluginClass, 'widget_output') ) {
				    
					call_user_func( $this->pluginClass.'::widget_output');
			}else {
				
				if ( $this->widgets_shortcode and ! empty( $this->widgets_shortcode_field ) ) {
					
					$instance[$this->widgets_shortcode_field] = apply_filters( 'shortcode_main_content_output_'.$this->plugin_prefix, $instance[$this->widgets_shortcode_field] , $instance,$this->id_base );
				    echo do_shortcode( $instance[$this->widgets_shortcode_field] );
				
				}
			}
            
	      	
		}
		/**
		 * Update widget options.
		 *
		 * @param  array $new_instance New Options values.
		 * @param  array $old_instance Old Options values.
		 * @return array               Modified Options values.
		 */
		function update( $new_instance, $old_instance ) {
			
			$instance = $old_instance;
			foreach ( $this->formElements as $key => $value ) {
				$instance[ $key ] = strip_tags( $new_instance[ $key ] );
			}
			return $instance;
		}
			/**
			 * Backend Widget Form.
			 *
			 * @param  array $instance Widget options values.
			 */
		function form( $instance ) {

			foreach ( $this->formElements as $key => $element ) {

	   			$elementCurrentValue = $element;

	   			?><span class="wpgmp_widgets" style="font-size: 13px;font-weight: 500; margin-top:6px;">
	   			<?php echo $element['label'];?></span><?php
				if ( class_exists( 'FlipperCode_HTML_Markup' ) ) {

					$atts = array(
					'class' => 'widefat',
					'value' => $instance[ $element['name'] ],
					'id' => $this->get_field_id( $element['name'] ),
					);

					if(array_key_exists('options', $element)) 
					$atts['options'] = $element['options'];
					
					$functionName = 'field_' . $element['type'];
					echo FlipperCode_HTML_Markup::$functionName( $this->get_field_name( $element['name'] ), $atts ) ;
				}
			}
		}
	}
}
				
	
