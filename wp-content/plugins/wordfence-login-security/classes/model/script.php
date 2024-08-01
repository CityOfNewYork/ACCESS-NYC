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

	public function renderInline() {
		if (empty($this->source))
			return;
?>
		<script type="text/javascript" src="<?php echo esc_attr($this->getSourceUrl()) ?>"></script>
<?php
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