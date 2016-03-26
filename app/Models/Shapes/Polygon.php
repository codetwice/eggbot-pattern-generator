<?php
	namespace tml\Eggbot\Shapes;
	use tml\Eggbot\Utils;
	use \DOMDOcument;

	class Polygon extends DrawableShape {
		public $points = [];

		public function __construct(array $points = null) {
			parent::__construct();

			if ($points) {
				$this->points = $points;		
			}
		}

		protected function getPoints() {
			return $this->points;
		}

		protected function getLines() {
			$lines = [];
			$c = count($this->points);
			for ($i = 0; $i < $c; $i++) {
				$line = new Line(
					$this->points[($i+1) % $c],
					$this->points[$i]
				);

				$lines[] = $line;
			}

			return $lines;
		}

		public function copy($deep = true) {
			$copy = parent::copy();

			if ($deep) {
				$points = [];
				foreach ($this->points as $point) {
					$points[] = $point->copy($deep);
				}

				$copy->points = $points;
			}

			return $copy;
		}

		public function createSvgElement(DOMDocument $dom) {
			$last = $this->points[count($this->points) - 1];
			$commands = [];

			$commands[] = sprintf('M%f %f', $last->x, $last->y);

			foreach ($this->points as $index => $point) {
				$commands[] = sprintf('L %f %f', $point->x, $point->y);
			}

			return $this->createPathElement($dom, $commands);
		}

		/**
		 * Insets the polygon
		 * @param  int $distance Amount to inset by
		 * @return Polygon The inset polygon
		 */
		public function inset($distance) {
			if (!$this->isClockwise()) {
				$distance *= -1;
			}

			$points = $this->points;
			$newPoints = [];
			$start = $points[0];

			//  Polygon must have at least three corners to be inset.
			if (count($this->points)<3) {
				return;
			}

			//  Inset the polygon.
			$cd = $points[count($points) - 1];
			$ef = $points[0];

			for ($i = 0; $i < count($points)-1; $i++) {
				$ab = $cd; 
				$cd = $ef;
				$ef = $points[$i+1];
				$newPoints[] = self::insetCorner($ab, $cd, $ef, $distance); 
			}

			$newPoints[] = self::insetCorner($cd, $ef, $start, $distance); 
			$this->points = $newPoints;

			return $this;
		}

		/**
		 * Outset the polygon
		 * @param  int $distance Amount to outset by
		 * @return Polygon The outset polygon
		 */
		public function outset($distance) {
			return $this->inset(-$distance);
		}

		private function isClockwise() {
			$pc = count($this->points);
			$sum = 0;

			for ($i=0; $i<$pc; $i++) {
				$p1 = $this->points[$i];
				$p2 = $this->points[($i+1)%$pc];

				$sum += ($p2->x - $p1->x) * ($p2->y + $p1->y);
			}

			return $sum >= 0;
		}

		/**
		 * Calculates a new, inset position of a middle point of a 3-point path
		 * 
		 * @param  Point  $ab       First point
		 * @param  Point  $cd       Second point (will be inset)
		 * @param  Point  $ef       Third point
		 * @param  integer $distance Amount to inset by
		 * @return Point          The inset point 
		 */
		private static function insetCorner(Point $ab, Point $cd, Point $ef, $distance) {
			$a = $ab->x; $b = $ab->y; 
			$c = $cd->x; $d = $cd->y;
			$e = $ef->x; $f = $ef->y;

			$c1 = $c2 = $c;
			$d1 = $d2 = $d;

			//  Calculate length of line segments.
			$dx1 = $c - $a; $dy1 = $d - $b; $dist1 = sqrt($dx1*$dx1 + $dy1*$dy1);
			$dx2 = $e - $c; $dy2 = $f - $d; $dist2 = sqrt($dx2*$dx2 + $dy2*$dy2);

			//  Exit if either segment is zero-length.
			if ($dist1 == 0 || $dist2 == 0) {
				return;
			}

			//  Inset each of the two line segments.
			$insetX = $dy1 / $dist1*$distance; $a += $insetX; $c1 += $insetX;
			$insetY = -$dx1 / $dist1*$distance; $b += $insetY; $d1 += $insetY;
			$insetX = $dy2 / $dist2*$distance; $e += $insetX; $c2 += $insetX;
			$insetY = -$dx2 / $dist2*$distance; $f += $insetY; $d2 += $insetY;

			//  If inset segments connect perfectly, return the connection point.
			if ($c1 == $c2 && $d1 == $d2) {
				return new Point($c1, $d1);
			}

			//  Return the intersection point of the two inset segments (if any).
			$l1 = new Line($ab, new Point($c1, $d1));
			$l2 = new Line(new Point($c2, $d2), $ef);

			return Utils::lineIntersection($l1, $l2);
		}					
	}
?>
