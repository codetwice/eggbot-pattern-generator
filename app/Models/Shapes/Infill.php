<?php
	namespace tml\Eggbot\Shapes;
	use \DOMDOcument;
	use tml\Eggbot\Shapes\Utilities\InfillSolver;
	use tml\Eggbot\Utils;
	
	class Infill extends DrawableShape {
		private $shape;
		private $density;
		private $direction;

		public function __construct(Shape $shape, $density, $direction = 0) {
			parent::__construct();
			$this->shape = $shape;
			$this->density = $density;
			$this->direction = $direction;
		}

		public function copy($deep = true) {			
			$copy = parent::copy();
			if ($deep) {
				$copy->shape = $this->shape->copy($deep);
			}

			return $copy;
		}

		protected function getPoints() {
			return $this->shape->getPoints();
		}

		public function createSvgElement(DOMDocument $dom) {
			$commands = [];
			
			// if the direction is not null, we have to rotate the polygon
			if ($this->direction != 0) {
				$this->shape->rotate(-$this->direction);
			}

			// generate lines enclosing the polygon
			$boundaries = $this->shape->getBoundaries();
			$infillLines = [];
			$lines = $this->shape->getLines();

			for ($x = $boundaries['minX'] + $this->density/2; $x <= $boundaries['maxX']; $x += $this->density) {
				// draw a vertical line
				$l = new Line(
					new Point($x, $boundaries['minY']), 
					new Point($x, $boundaries['maxY'])
				);

				// calculate intersection points with each of the lines of the polygon
				$intersections = [];
				foreach ($lines as $i => $line) {
					$intersectionPoint = Utils::lineIntersection($l, $line, false);
					if ($intersectionPoint) {
						$intersectionPoint->lineIndex = $i;
						$intersections[] = $intersectionPoint;
					}
				}

				// order the intersection points by Y coordinate
				usort($intersections, function(&$a, &$b) {
					return $a->y - $b->y;
				});

				// generate the infill lines
				for ($i = 1; $i < count($intersections); $i += 2) {
					$infillLines[] = new Line($intersections[$i-1], $intersections[$i]);
				}
			}

			$groupElement = $dom->createElement('g');
			$solver = new InfillSolver($lines, $infillLines);
			$pathes = $solver->calculate();

			// turn the pathes into points
			$pointPathes = [];
			foreach ($pathes as $path) {
				$pp = [];
				foreach ($path as $p) {
					$pp[] = new Point($p[0], $p[1]);
				}

				$pointPathes[] = $pp;
			}

			// if direction is not null, we have to rotate the points back
			if ($this->direction != 0) {
				$matrix = Utils::rotationMatrix($this->direction);
				$this->shape->rotate($this->direction);
				foreach ($pointPathes as $path) {
					foreach ($path as $p) {
						$p->transform($matrix);
					}
				}
			}
			
			foreach ($pointPathes as $pi => $path) {
				$commands = [];
				foreach ($path as $index => $point) {
					$command = $index == 0 ? 'M' : 'L';
					$commands[] = sprintf('%s%f %f', $command, $point->x, $point->y);					
				}

				$pathElement = $this->createPathElement($dom, $commands);
				$groupElement->appendChild($pathElement);
			}

			return $groupElement;
		}
	}
?>