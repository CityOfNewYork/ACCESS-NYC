<?php

namespace WordfenceLS;

class Model_Script extends Model_Asset {

	private $translations = array();
	private $translationObjectName = null;

	public function enqueue() {
		if ($this->registered) {
			wp_enqueue_script($this->handle);
		}
		else {
			wp_enqueue_script($this->handle, $this->source, $this->dependencies, $this->version);
		}
		if ($this->translationObjectName && !empty($this->translations)) {
			wp_localize_script($this->handle, $this->translationObjectName, $this->translations);
		}
	}

	public function isEnqueued() {
		return wp_script_is($this->handle);
	}

	public function isDone() {
		return wp_script_is($this->handle, 'done');
	}

	public function renderInline() {
		static $rendered = array();

		if (isset($rendered[$this->handle])) {
			return;
		}

		$asset = $this;
		if ($this->registered) {
			$registered = wp_scripts()->query($this->handle, 'registered');
			if ($registered) {
				$asset = Model_Script::create($this->handle, $registered->src, $registered->deps, $registered->ver)->setRegistered();
			}
		}

		foreach ($asset->dependencies as $dependency) {
			if (wp_script_is($dependency, 'done')) {
				continue;
			}

			$registered = wp_scripts()->query($dependency, 'registered');
			if ($registered) {
				Model_Script::create($dependency, $registered->src, $registered->deps, $registered->ver)->setRegistered()->renderInline();
			}
		}

		if ($this->translationObjectName && !empty($this->translations)) {
?>
		<script type="text/javascript">
			var <?php echo esc_js($this->translationObjectName) ?> = <?php echo wp_json_encode($this->translations); ?>;
		</script>
<?php
		}

		$source = $asset->getSourceUrl();
		if ($asset->registered) {
			$scripts = wp_scripts();
			$source = $asset->buildSourceUrl($asset->source, $asset->version, $scripts->base_url, $scripts->content_url, $scripts->default_version);
		}
		if (!empty($source)) {
?>
		<script type="text/javascript" src="<?php echo esc_attr($source) ?>"></script>
<?php
		}

		$rendered[$this->handle] = true;
		$scripts = wp_scripts();
		if (!in_array($this->handle, $scripts->done, true)) {
			$scripts->done[] = $this->handle;
		}
	}

	public function register() {
		wp_register_script($this->handle, $this->source, $this->dependencies, $this->version);
		return parent::register();
	}

	public function withTranslation($placeholder, $translation) {
		$this->translations[$placeholder] = $translation;
		return $this;
	}

	public function withTranslations($translations) {
		$this->translations = $translations;
		return $this;
	}

	public function setTranslationObjectName($name) {
		$this->translationObjectName = $name;
		return $this;
	}

}
