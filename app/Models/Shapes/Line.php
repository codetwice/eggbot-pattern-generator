<?php
	namespace tml\Eggbot\Shapes;
	use \DOMDOcument;
	
	class Line extends DrawableShape {
		public $pointA;
		public $pointB;

		public function __construct(Point $a, Point $b) {
			parent::__construct();
			$this->pointA = $a;
			$this->pointB = $b;
		}

		protected function getPoints() {
			return [ $this->pointA, $this->pointB ];
		}

		protected function getLines() {
			return [ $this ];
		}

		public function copy($deep = true) {
			$copy = parent::copy();

			if ($deep) {
				$copy->pointA = $this->pointA->copy($deep);
				$copy->pointB = $this->pointB->copy($deep);
			}

			return $copy;
		}

		public function createSvgElement(DOMDocument $dom) {
			$commands = [
				sprintf('M%f %f', $this->pointA->x, $this->pointA->y),
				sprintf('L%f %f', $this->pointB->x, $this->pointB->y)
			];

			return $this->createPathElement($dom, $commands);
		}
	}
?>