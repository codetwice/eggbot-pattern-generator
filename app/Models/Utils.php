<?php
	namespace tml\Eggbot;
	use \Exception;
	use tml\Eggbot\Shapes\Line;
	use tml\Eggbot\Shapes\Polygon;
	use tml\Eggbot\Shapes\Point;

	class Utils {
		public static function lineIntersection(Line $line1, Line $line2, $extrapolate = true) {
			$a = clone $line1->pointA;
			$b = clone $line1->pointB;
			$c = clone $line2->pointA;
			$d = clone $line2->pointB;

			//  Fail if either line is undefined.
			if ($a->x == $b->x && $a->y == $b->y || $c->x==$d->x && $c->y==$d->y) {
				return null;
			}

			//  (1) Translate the system so that point A is on the origin.
			$b = $b->translate($a->copy()->mirror());
			$c = $c->translate($a->copy()->mirror());
			$d = $d->translate($a->copy()->mirror());

			//  Discover the length of segment A-B.
			$distAB = $b->length();

			//  (2) Rotate the system so that point B is on the positive X axis.
			$theCos = $b->x / $distAB;
			$theSin = $b->y / $distAB;

			$newX= $c->x * $theCos + $c->y * $theSin;
			$c->y = $c->y * $theCos - $c->x * $theSin; 
			$c->x = $newX;

			$newX = $d->x * $theCos + $d->y * $theSin;
			$d->y = $d->y * $theCos - $d->x * $theSin; 
			$d->x = $newX;

			//  Fail if the lines are parallel.
			if ($c->y==$d->y) {
				return null;
			}

			//  (3) Discover the position of the intersection point along line A-B.
			$ABpos = $d->x + ($c->x - $d->x) * $d->y / ($d->y - $c->y);

			//  (4) Apply the discovered position to line A-B in the original coordinate system.
			$p = new Point($a->x + $ABpos * $theCos, $a->y + $ABpos * $theSin);

			// if extrapolation is off, check whether the lines are really crossing each other or not
			if (!$extrapolate) {
				$x1s = [ $line1->pointA->x, $line1->pointB->x ];
				$y1s = [ $line1->pointA->y, $line1->pointB->y ];
				$x2s = [ $line2->pointA->x, $line2->pointB->x ];
				$y2s = [ $line2->pointA->y, $line2->pointB->y ];

				sort($x1s, SORT_NUMERIC);
				sort($y1s, SORT_NUMERIC);
				sort($x2s, SORT_NUMERIC);
				sort($y2s, SORT_NUMERIC);

				if ($p->x < $x1s[0] || $p->x > $x1s[1] || $p->y < $y1s[0] || $p->y > $y1s[1] ||
					$p->x < $x2s[0] || $p->x > $x2s[1] || $p->y < $y2s[0] || $p->y > $y2s[1]) {
					$p = null;
				}
			}

			return $p;
		}

		public static function matrixmult(array $m1, array $m2){
			$r=count($m1);
			$c=count($m2[0]);
			$p=count($m2);

			if(count($m1[0])!=$p) {
				throw new Exception('Incompatible matrixes');
			}

			$m3=array();

			for ($i=0;$i< $r;$i++) {
				for($j=0;$j<$c;$j++) {
					$m3[$i][$j]=0;
					for($k=0;$k<$p;$k++) {
						$m3[$i][$j]+=$m1[$i][$k]*$m2[$k][$j];
					}
				}
			}

			return($m3);
		}	

		public static function rotationMatrix($alpha) {
			return [
				[cos($alpha), -sin($alpha)],
				[sin($alpha), cos($alpha)],
				[0, 0]
			];
		}

		public static function scalingMatrix($horizontal, $vertical) {
			return [
				[$horizontal, 0],
				[0, $vertical],
				[0, 0]
			];
		}

		public static function translationMatrix($x, $y) {
			return [
				[1, 0],
				[0, 1],
				[$x, $y]
			];
		}

		public static function hexToRgb($hex) {
			$matches = null;

			if (preg_match('/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $hex, $matches)) {
				return [ hexdec($matches[1]), hexdec($matches[2]), hexdec($matches[3])];
			}

			return null;
		}

		/**
		 * Takes an array of lines and returns the polygons they are making up
		 *
		 * Throws an exceptions if they are not making up valid polygons.
		 * 
		 * @param  array  $lines The lines to solve
		 * @return array Array of polygons
		 */
		public static function linesToPolygons(array $lines) {
			$polygons = [];

			while (count($lines) > 0) {
				$item = array_shift($lines);
				$points = [ $item->pointA, $item->pointB ];
				$expanded = true;
				$finished = false;

				while ($expanded) {
					$expanded = false;
					foreach ($lines as $index => $line) {
						if ($line->pointA == $points[count($points)-1]) {
							$points[] = $line->pointB;
							unset($lines[$index]);
							$expanded = true;
							break;
						} else if ($line->pointB == $points[count($points)-1]) {
							$points[] = $line->pointA;
							unset($lines[$index]);
							$expanded = true;
							break;
						}
					}
				}

				if ($points[0] == $points[count($points)-1]) {
					array_pop($points);
					$polygons[] = new Polygon($points);
				} else {
					throw new Exception('The lines passed are not connecting into proper polygons');
				}
			}

			return $polygons;
		}
	}
?>