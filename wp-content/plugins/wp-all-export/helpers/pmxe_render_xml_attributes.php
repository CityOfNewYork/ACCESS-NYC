<?php
function pmxe_render_xml_attributes($el, $path = '/')
{
	foreach ($el->attributes as $attr) {
		echo ' <span class="xml-attr" title="' . esc_attr($path . '@' . $attr->nodeName) . '"><span class="xml-attr-name">' . esc_html($attr->nodeName) . '</span>=<span class="xml-attr-value">"' . esc_attr($attr->value) . '"</span></span>';
	}
}