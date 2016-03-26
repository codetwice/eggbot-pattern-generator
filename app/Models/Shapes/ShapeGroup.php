<?php
	namespace tml\Eggbot\Shapes;

	class ShapeGroup extends Shape {
		protected $shapes;

		public function __construct(array $shapes = null) {
			if ($shapes) {
				$this->shapes = $shapes;
			} else {
				$this->shapes = [];
			}
		}

		public function copy($deep = true) {
			$copy = parent::copy($deep);
			if ($deep) {
				$copy->shapes = array_map(function(Shape $shape) {
					return $shape->copy(true);
				}, $this->shapes);
			}

			return $copy;
		}

		public function add(Shape $shape) {
			$this->shapes[] = $shape;
		}

		public function getPoints() {
			$points = [];
			foreach ($this->shapes as $shape) {
				$points = array_merge($points, $shape->getPoints());
			}

			return $points;
		}

		public function getLines() {
			$lines = [];
			foreach ($this->shapes as $shape) {
				$lines = array_merge($lines, $shape->getLines());
			}

			return $lines;
		}

		public function get($index) {
			return $this->shapes[$index];
		}

		public function all() {
			return $this->shapes;
		}
	}
?>