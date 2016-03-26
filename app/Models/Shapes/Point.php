<?php
	namespace tml\Eggbot\Shapes;
	use tml\Eggbot\Utils;

	class Point extends Shape {
		public $x;
		public $y;

		public function __construct($x, $y) {
			$this->x = $x;
			$this->y = $y;
		}

		protected function getPoints() {
			return [ $this ];
		}

		public function translate(Point $vector) {
			$this->x += $vector->x;
			$this->y += $vector->y;
			return $this;
		}

		public function length() {
			return sqrt($this->x * $this->x + $this->y * $this->y);
		}

		public function mirror($axis = 'xy') {
			if (strpos($axis, 'x') !== false) {
				$this->y = -$this->y;
			}

			if (strpos($axis, 'y') !== false) {
				$this->x = -$this->x;
			}

			return $this;
		}

		public function equals(Point $p) {
			return ($p->x == $this->x && $p->y == $this->y);
		}

		public function distanceTo(Point $b) {
			$dx = $b->x - $this->x;
			$dy = $b->y - $this->y;
			return sqrt($dx * $dx + $dy * $dy);
		}

		public function transform(array $matrix) {
			$m = [ $this->x, $this->y, 1];
			$result = Utils::matrixmult([$m], $matrix);
			$this->x = $result[0][0];
			$this->y = $result[0][1];
		}

		public function rotate($alpha) {
			$this->transform(Utils::rotationMatrix($alpha));
		}

		public function scale($horizontal, $vertical) {
			$this->transform(Utils::scalingMatrix($horizontal, $vertical));
		}

		public function getPolarCoordinates() {
			$d = $this->length();
			$r = atan2($this->y, $this->x);
			return [ $d, $r ];
		}
	}
?>
