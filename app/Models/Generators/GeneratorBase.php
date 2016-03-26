<?php
	namespace tml\Eggbot\Generators;

	abstract class GeneratorBase {
		private $parameters = [];
		protected $requiredParameters = [];
		
		public function getParameter($name) {
			return $this->parameters[$name];
		}

		public function setParameter($name, $value) {
			$this->parameters[$name] = $value;
		}

		public function getRequiredParameters() {
			return $this->requiredParameters;
		}

		public function setDefaultParameters() {
			foreach ($this->requiredParameters as $parameter) {
				$this->parameters[$parameter->name] = $parameter->defaultValue;
			}
		}

		public function getAllParameters() {
			return $this->parameters;
		}

		public abstract function generate();

		public function validate() {
			return [];
		}
	}

?>