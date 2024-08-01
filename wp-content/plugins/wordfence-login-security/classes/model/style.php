<?php

namespace WordfenceLS;

class Model_Style extends Model_Asset {

	public function enqueue() {
		if ($this->registered) {
			wp_enqueue_style($this->handle);
		}
		else {
			wp_enqueue_style($this->handle, $this->source, $this->dependencies, $this->version);
		}
	}

	public function isEnqueued() {
		return wp_style_is($this->handle);
	}

	public function renderInline() {
		if (empty($this->source))
			return;
		$url = esc_attr($this->getSourceUrl());
		$linkTag = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$url}\">";
?>
		<script type="text/javascript">
			jQuery('head').append(<?php echo json_encode($linkTag) ?>);
		</script>
<?php
	}

	public function register() {
		wp_register_style($this->handle, $this->source, $this->dependencies, $this->version);
		return parent::register();
	}

}