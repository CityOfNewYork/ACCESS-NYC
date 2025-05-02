<?php
namespace WPML\ATE\Proxies;

class Widget extends Proxy implements \IWPML_Frontend_Action, \IWPML_DIC_Action {
	const QUERY_VAR_ATE_WIDGET_SCRIPT = 'wpml-app';
	const QUERY_VAR_ATE_WIDGET_SECTION = 'section';
	const SCRIPT_NAME                 = 'ate-widget';
}
