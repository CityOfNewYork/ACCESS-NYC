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

	public function isDone() {
		return wp_style_is($this->handle, 'done');
	}

	public function renderInline() {
		static $rendered = array();

		if (isset($rendered[$this->handle])) {
			return;
		}

		$asset = $this;
		if ($this->registered) {
			$registered = wp_styles()->query($this->handle, 'registered');
			if ($registered) {
				$asset = Model_Style::create($this->handle, $registered->src, $registered->deps, $registered->ver)->setRegistered();
			}
		}

		foreach ($asset->dependencies as $dependency) {
			if (wp_style_is($dependency, 'done')) {
				continue;
			}

			$registered = wp_styles()->query($dependency, 'registered');
			if ($registered) {
				Model_Style::create($dependency, $registered->src, $registered->deps, $registered->ver)->setRegistered()->renderInline();
			}
		}

		$source = $asset->getSourceUrl();
		if ($asset->registered) {
			$styles = wp_styles();
			$source = $asset->buildSourceUrl($asset->source, $asset->version, $styles->base_url, $styles->content_url, $styles->default_version);
		}
		if (!empty($source)) {
			$linkTag = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . esc_attr($source) . "\">";
?>
		<script type="text/javascript">
			jQuery('head').append(<?php echo wp_json_encode($linkTag) ?>);
		</script>
<?php
		}

		$rendered[$this->handle] = true;
		$styles = wp_styles();
		if (!in_array($this->handle, $styles->done, true)) {
			$styles->done[] = $this->handle;
		}
	}

	public function register() {
		wp_register_style($this->handle, $this->source, $this->dependencies, $this->version);
		return parent::register();
	}

}
