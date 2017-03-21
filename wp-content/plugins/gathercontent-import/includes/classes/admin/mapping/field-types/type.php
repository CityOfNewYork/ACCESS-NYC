<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;
use GatherContent\Importer\Views\View;

interface Type {
	public function type_id();
	public function e_type_id();
	public function option_underscore_template( View $view );
	public function underscore_template( View $view );
}
