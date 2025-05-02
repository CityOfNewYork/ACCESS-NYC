<?php

namespace WPML\ATE\Proxies;

class Dashboard extends Proxy implements \IWPML_Frontend_Action, \IWPML_DIC_Action
{
	const QUERY_VAR_ATE_WIDGET_SCRIPT = 'wpml-app';
	const SCRIPT_NAME                 = 'ate-dashboard';
}
