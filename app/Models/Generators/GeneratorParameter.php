<?php
	namespace tml\Eggbot\Generators;

	class GeneratorParameter {
		public $name;
		public $defaultValue;
		public $type;
		public $description;
		public $values;

		public function __construct($name, $type, $defaultValue, $description, $values = null) {
			$this->name = $name;
			$this->defaultValue = $defaultValue;
			$this->description = $description;
			$this->type = $type;
			$this->values = $values;
		}
	}
?>