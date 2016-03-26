<?php
	namespace tml\Eggbot\Shapes;
	use \DOMDocument;
	
	class Dot extends DrawableShape {
		private $point;

		public function __construct(Point $p = null) {
			parent::__construct();
			if ($p == null) {
				$this->point = new Point(0, 0);
			} else {
				$this->point = $p;
			}
		}

		public function copy($deep = true) {
			$copy = parent::copy();

			if ($deep) {
				$copy->point = $this->point->copy($deep);
			}

			return $copy;
		}

		public function createSvgElement(DOMDocument $dom) {
			$commands = [
				sprintf('M%f %f', $this->point->x, $this->point->y),
				sprintf('L%f %f', $this->point->x+$this->style->strokeWidth, $this->point->y)
			];

			return $this->createPathElement($dom, $commands);
		}

		protected function getPoints() {
			return [ $this->point ];
		}

	}
?>