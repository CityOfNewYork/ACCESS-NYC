<?php

namespace WPML\PB\Elementor\LanguageSwitcher;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes\Color as SchemeColor;

class WidgetAdaptor {

	/** @var Widget $widget */
	private $widget;

	public function setTarget( Widget $widget ) {
		$this->widget = $widget;
	}

	/** @return string */
	public function getName() {
		return 'wpml-language-switcher';
	}

	/** @return string */
	public function getTitle() {
		return __( 'WPML Language Switcher', 'sitepress' );
	}

	/** @return string */
	public function getIcon() {
		return 'fa fa-globe';
	}

	/** @return array */
	public function getCategories() {
		return [ 'general' ];
	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type. For example, external
	 * developers use this method to register controls in a widget.
	 *
	 * Should be inherited and register new controls using `add_control()`,
	 * `add_responsive_control()` and `add_group_control()`, inside control
	 * wrappers like `start_controls_section()`, `start_controls_tabs()` and
	 * `start_controls_tab()`.
	 */
	public function registerControls() {
		//Content Tab
		$this->widget->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'sitepress' ),
				'type'  => Controls_Manager::SECTION,
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->widget->add_control(
			'style',
			[
				'label'   => __('Language switcher type', 'sitepress'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => [
					'custom'            => __( 'Custom', 'sitepress' ),
					'footer'            => __( 'Footer', 'sitepress' ),
					'post_translations' => __( 'Post Translations', 'sitepress' ),
				],
			]
		);

		$this->widget->add_control(
			'display_flag',
			[
				'label'        => __( 'Display Flag', 'sitepress' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 1,
				'default'      => 1,
			]
		);

		$this->widget->add_control(
			'link_current',
			[
				'label'        => __( 'Show Active Language - has to be ON with Dropdown', 'sitepress' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 1,
				'default'      => 1,
			]
		);

		$this->widget->add_control(
			'native_language_name',
			[
				'label'        => __( 'Native language name', 'sitepress' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 1,
				'default'      => 1,
			]
		);

		$this->widget->add_control(
			'language_name_current_language',
			[
				'label'        => __( 'Language name in current language', 'sitepress' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 1,
				'default'      => 1,
			]
		);

		$this->widget->end_controls_section();

		$this->widget->start_controls_section(
			'style_section',
			[
				'label' => __( 'Style', 'sitepress' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->widget->start_controls_tabs( 'style_tabs' );

		$this->widget->start_controls_tab(
			'style_normal_tab',
			[
				'label' => __( 'Normal', 'sitepress' ),
			]
		);

		$this->widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'switcher_typography',
				'selector' => '{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item',
			]
		);

		$this->widget->add_control(
			'switcher_text_color',
			[
				'label'     => __( 'Text Color', 'sitepress' ),
				'type'      => Controls_Manager::COLOR,
				'scheme'    => [
					'type'  => SchemeColor::get_type(),
					'value' => SchemeColor::COLOR_3,
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item .wpml-ls-link, 
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-legacy-dropdown a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->widget->add_control(
			'switcher_bg_color',
			[
				'label'     => __( 'Background Color', 'sitepress' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item .wpml-ls-link, 
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-legacy-dropdown a' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->widget->end_controls_tab();

		$this->widget->start_controls_tab(
			'style_hover_tab',
			[
				'label' => __( 'Hover', 'sitepress' ),
			]
		);
		$this->widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'switcher_hover_typography',
				'selector' => '{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item:hover,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item.wpml-ls-item__active,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item.highlighted,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item:focus',
			]
		);

		$this->widget->add_control(
			'switcher_hover_color',
			[
				'label'     => __( 'Text Color', 'sitepress' ),
				'type'      => Controls_Manager::COLOR,
				'scheme'    => [
					'type'  => SchemeColor::get_type(),
					'value' => SchemeColor::COLOR_4,
				],
				'selectors' => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-legacy-dropdown a:hover,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-legacy-dropdown a:focus,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-legacy-dropdown .wpml-ls-current-language:hover>a,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item .wpml-ls-link:hover,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item .wpml-ls-link.wpml-ls-link__active,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item .wpml-ls-link.highlighted,
					{{WRAPPER}} .wpml-elementor-ls .wpml-ls-item .wpml-ls-link:focus' => 'color: {{VALUE}}',
				],
			]
		);

		$this->widget->end_controls_tab();

		$this->widget->end_controls_tabs();

		$this->widget->end_controls_section();

		$this->widget->start_controls_section(
			'language_flag',
			[
				'label'     => __( 'Language Flag', 'sitepress' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_flag' => [ 1 ],
				],
			]
		);

		$this->widget->add_control(
			'flag_margin',
			[
				'label'      => __( 'Margin', 'sitepress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-flag' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->widget->end_controls_section();

		$this->widget->start_controls_section(
			'post_translation_text',
			[
				'label'     => __( 'Post Translation Text', 'sitepress' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'style' => [ 'post_translations' ],
				],
			]
		);

		$this->widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'post_translation_typography',
				'selector' => '{{WRAPPER}} .wpml-elementor-ls .wpml-ls-statics-post_translations',
			]
		);

		$this->widget->add_control(
			'post_translation_color',
			[
				'label'     => __( 'Text Color', 'sitepress' ),
				'type'      => Controls_Manager::COLOR,
				'scheme'    => [
					'type'  => SchemeColor::get_type(),
					'value' => SchemeColor::COLOR_3,
				],
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-statics-post_translations' => 'color: {{VALUE}}',
				],
			]
		);

		$this->widget->add_control(
			'post_translation_bg_color',
			[
				'label'     => __( 'Background Color', 'sitepress' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-statics-post_translations' => 'background-color: {{VALUE}}',
				],
			]
		);

		$this->widget->add_control(
			'post_translation_padding',
			[
				'label'      => __( 'Padding', 'sitepress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-statics-post_translations' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->widget->add_control(
			'post_translation_margin',
			[
				'label'      => __( 'Margin', 'sitepress' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .wpml-elementor-ls .wpml-ls-statics-post_translations' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->widget->end_controls_section();

	}

	/**
	 * Render element.
	 *
	 * Generates the final HTML on the frontend.
	 */
	public function render() {
		$settings = $this->widget->get_settings_for_display();

		$this->widget->add_render_attribute('wpml-elementor-ls', 'class', [
			'wpml-elementor-ls',
		]);

		$args = array(
			'display_link_for_current_lang' => $settings['link_current'],
			'flags'                         => $settings['display_flag'],
			'native'                        => $settings['native_language_name'],
			'translated'                    => $settings['language_name_current_language'],
			'type'                          => $settings['style'],
		);

		if ( 'custom' === $settings['style'] ) {
			//forcing in dropdown case
			$args['display_link_for_current_lang'] = 1;
		}

		echo "<div " . $this->widget->get_render_attribute_string('wpml-elementor-ls') . ">";
		do_action('wpml_language_switcher', $args);
		echo "</div>";
	}
}
