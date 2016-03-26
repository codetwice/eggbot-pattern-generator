<?php
	namespace tml\Eggbot\Shapes;

	abstract class Shape {
		/**
		 * Returns a copy of the shape
		 *
		 * @param boolean $deep Specifies whether this copy is deep or not
		 * 
		 * @return Shape An exact, deep copy except for the shape config
		 */
		public function copy($deep = true) {
			$copy = clone $this;
			return $copy;
		}

		/**
		 * Translates the shape by a vector
		 * 
		 * @param  Point  $v Vector to translate by
		 */
		public function translate(Point $v) {
			$points = $this->getPoints();
			foreach ($points as $p) {
				$p->translate($v);
			}

			return $this;
		}

		/**
		 * Rotates the shape by a degree
		 * 
		 * @param  float  $alpha Amount of rotation, in radian
		 */
		public function rotate($alpha) {
			$points = $this->getPoints();
			foreach ($points as $p) {
				$p->rotate($alpha);
			}

			return $this;
		}

		/**
		 * Scales the shape
		 * 
		 * @param  float  $horizontal Ratio of horizontal scaling
		 * @param  float  $vertical Ratio of vertical scaling
		 */
		public function scale($horizontal, $vertical) {
			$points = $this->getPoints();
			foreach ($points as $p) {
				$p->scale($horizontal, $vertical);
			}

			return $this;
		}

		/**
		 * Applies a transformation matrix to each of the points
		 * 
		 * @param  array $matrix Transformation matrix
		 */
		public function transform(array $matrix) {
			$points = $this->getPoints();
			foreach ($points as $p) {
				$p->transform($matrix);
			}

			return $this;
		}

		/**
		 * Returns an array with the minimum X and Y value of an enclosing rectangle
		 * 
		 * @return Array Associative array with minX, maxX, minY and maxY
		 */
		public function getBoundaries() {
			$minX = $maxX = $minY = $maxY = null;
			$points = $this->getPoints();

			foreach ($points as $p) {
				if ($minX === null || $p->x < $minX) {
					$minX = $p->x;
				}

				if ($maxX === null || $p->x > $maxX) {
					$maxX = $p->x;
				}

				if ($minY === null || $p->y < $minY) {
					$minY = $p->y;
				}

				if ($maxY === null || $p->y > $maxY) {
					$maxY = $p->y;
				}
			}

			return [ 'minX' => $minX, 'maxX' => $maxX, 'minY' => $minY, 'maxY' => $maxY ];
		}

		/**
		 * Returns all points associated with this shape
		 * 
		 * @return array Array of Point objects
		 */
		protected abstract function getPoints();

		/**
		 * Returns all lines associated with this shape
		 * 
		 * @return array Array of line objects
		 */
		protected function getLines() {
			return [];
		}
	}

?>