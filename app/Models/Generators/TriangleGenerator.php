<?php
	namespace tml\Eggbot\Generators;
	use tml\Eggbot\Utils;
	use tml\Eggbot\Drawing;
	use tml\Eggbot\Shapes\ShapeStyle;
	use tml\Eggbot\Shapes\Point;
	use tml\Eggbot\Shapes\Line;
	use tml\Eggbot\Shapes\Polygon;
	use tml\Eggbot\Shapes\Infill;

	class TriangleGenerator extends GeneratorBase {
		public function __construct() {
			$this->requiredParameters = [
				new GeneratorParameter('squareSize', 'number', '150', 'Size of the base grid, in pixels. The larger the larger the triangles.'),
				new GeneratorParameter('squareRows', 'number', '3', 'Number of triangle rows'),
				new GeneratorParameter('stretchRatio', 'number', '1.5', 'Horizontal : vertical stretch ratio'),
				new GeneratorParameter('randomFactor', 'number', '50', 'Randomness of the triangles (0 to 100)'),
				new GeneratorParameter('strokeWidth', 'number', '5', 'Width of the strokes'),
				new GeneratorParameter('polyOffset', 'number', '15', 'Distance between the line grid and the edges of the triangles'),
				new GeneratorParameter('infillDensity', 'number', '10', 'Density of the infill'),
				new GeneratorParameter('infillDirection', 'number', '0', 'Direction of the infill (degrees)'),
				new GeneratorParameter('infillOffset', 'number', '5', 'Offset of the infill (distance from the triangle edges)'),
				new GeneratorParameter('colors', 'string', '#0094cb, #c73337, #96999f, #73d500, #ad2172, #ff9800, #511700, #fc646f, #41ac3f
', 'Comma separated list of hex values to use as colors for drawing the triangles'),
				new GeneratorParameter('lineColor', 'string', '#000000', 'Color of the grid lines'),
				new GeneratorParameter('drawLines', 'boolean', '1', 'Draw the grid lines'),
				new GeneratorParameter('drawTriangles', 'boolean', '1', 'Draw the triangles'),
				new GeneratorParameter('drawInfill', 'boolean', '1', 'Draw the infill')
			];

			$this->setDefaultParameters();
		}

		public function generate() {
			// pollute the function scope
			foreach ($this->requiredParameters as $parameter) {
				$name = $parameter->name;
				$$name = $this->getParameter($name);
			}

			// expand colors
			$lineColor = Utils::hexToRgb($lineColor);
			$colorStrings = explode(',', $colors);
			$colors = [];
			foreach ($colorStrings as $str) {
				$color = Utils::hexToRgb($str);
				if ($color) {
					$colors[] = $color;
				}
			}


			$drawing = new Drawing(3200, 800);

			// colors
			$default = ShapeStyle::getDefault();
			$default->color = [0, 0, 0];
			$default->strokeWidth = $strokeWidth;

			$lineStyle = new ShapeStyle();
			$lineStyle->color = $lineColor;
			$lineStyle->strokeWidth = $strokeWidth;
			$lineStyle->draw = (bool)$drawLines;

			// create points
			$cols = round(3200 / $squareSize / $stretchRatio);
			$verticalSquareSize = $squareSize;
			$horizontalSquareSize = 3200 / $cols;

			$points = [];
			for ($x = 0; $x <= $cols; $x++) {
				$row = [];
				for ($y = 0; $y <= $squareRows; $y++) {
					$p = new Point($x * $horizontalSquareSize, 400 + ($y-$squareRows/2) * $verticalSquareSize);
					$row[] = $p;
				}

				$points[] = $row;
			}

			if ($randomFactor != 0) {
				$maxDeviationH = $horizontalSquareSize / 2 * $randomFactor / 100;
				$maxDeviationV = $squareSize / 2 * $randomFactor / 100;

				for ($x = 0; $x <= $cols; $x++) {
					for ($y = 0; $y <= $squareRows; $y++) {
						$p = $points[$x][$y];
						$p->x += rand(-$maxDeviationH, $maxDeviationH);
						$p->y += rand(-$maxDeviationV, $maxDeviationV);
					}
				}

				for ($y = 0; $y <= $squareRows; $y++) {
					$points[$cols][$y]->x = $points[0][$y]->x + 3200;
					$points[$cols][$y]->y = $points[0][$y]->y;
				}
			}

			// connect the dots horizontally
			for ($j = 0; $j < $squareRows+1; $j++) {
				for ($i = 0; $i<$cols; $i++) {
					$line = new Line($points[$i][$j], $points[$i+1][$j]);
					$line->setStyle($lineStyle);
					$drawing->append($line);
				}
			}

			//connect the dots vertically
			for ($i = 0; $i < $cols; $i++) {
				for ($j = 0; $j < $squareRows; $j++) {
					$line = new Line($points[$i][$j], $points[$i][$j+1]);
					$line->setStyle($lineStyle);
					$drawing->append($line);
				}
			}

			// create the triangles
			$c = 0;
			for ($i = 0; $i < $cols; $i++) {
				for ($j = 0; $j <= $squareRows-1; $j++) {
					$diagonal = rand(0, 1);
					$triangles = [];
					if ($diagonal) {
						$triangles[] = [
							$points[$i][$j],
							$points[$i+1][$j],
							$points[$i+1][$j+1],
						];

						$triangles[] = [
							$points[$i][$j],
							$points[$i+1][$j+1],
							$points[$i][$j+1]
						];

						$line = new Line($points[$i][$j], $points[$i+1][$j+1]);
						$line->setStyle($lineStyle);
						$drawing->append($line);
					} else {
						$triangles[] = [
							$points[$i][$j],
							$points[$i+1][$j],
							$points[$i][$j+1]
						];

						$triangles[] = [
							$points[$i+1][$j],
							$points[$i+1][$j+1],
							$points[$i][$j+1]
						];

						$line = new Line($points[$i+1][$j], $points[$i][$j+1]);
						$line->setStyle($lineStyle);
						$drawing->append($line);
					}

					foreach ($triangles as $td) {
						$style = $this->getSquareStyle($c+=rand(1,2), $colors);
						$triangle = new Polygon($td);
						$triangle->setStyle($style);
						$triangle->inset($polyOffset);
						$drawing->append($triangle);

						if ($this->getParameter('drawInfill')) {
							$infill = new Infill($triangle->copy()->inset($infillOffset), $infillDensity, $infillDirection);
							$infill->setStyle($style);	
							$drawing->append($infill);
						}
					}
				}
			}

			return $drawing;
		}

		private function getSquareStyle($index, array $colors) {
			$style = clone ShapeStyle::getDefault();
			$style->color = $colors[$index%count($colors)];

			if (!$this->getParameter('drawTriangles')) {
				$style->draw = false;
			}

			return $style;		
		}	
	}
?>