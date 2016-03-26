<?php
	namespace tml\Eggbot\Generators;
	use tml\Eggbot\Utils;
	use tml\Eggbot\Drawing;
	use tml\Eggbot\Shapes\ShapeStyle;
	use tml\Eggbot\Shapes\Point;
	use tml\Eggbot\Shapes\Line;
	use tml\Eggbot\Shapes\Polygon;
	use tml\Eggbot\Shapes\Infill;

	class PixelArtGeneratorV1 extends GeneratorBase {
		public function __construct() {
			$this->requiredParameters = [
				new GeneratorParameter('filename', 'file', 'monster.png', 'Image to plot (GIF, PNG, not too large! 32x32 or so!)'),
				new GeneratorParameter('transparentColor', 'string', '', 'Color to treat as transparent (hex)'),
				new GeneratorParameter('pixelSize', 'number', '25 ', 'Size of a single pixel.'),
				new GeneratorParameter('stretchRatio', 'number', '1.5', 'Horizontal : vertical stretch ratio'),
				new GeneratorParameter('strokeWidth', 'number', '5', 'Width of the strokes'),
				new GeneratorParameter('polyOffset', 'number', '3', 'Distance between the line grid and the edges of the pixels'),
				new GeneratorParameter('infillDensity', 'number', '10', 'Density of the infill'),
				new GeneratorParameter('infillDirection', 'number', '0', 'Direction of the infill (degrees)'),
				new GeneratorParameter('infillOffset', 'number', '5', 'Offset of the infill (distance from the pixel edges)'),
				new GeneratorParameter('drawLines', 'boolean', '0', 'Draw grid lines'),
				new GeneratorParameter('lineColor', 'string', '#000000', 'Color of the grid lines'),
			];

			$this->setDefaultParameters();
		}

		public function generate() {
			$drawing = new Drawing(3200, 800);

			if ($this->getParameter('transparentColor')) {
				$transparentColor = Utils::hexToRgb($this->getParameter('transparentColor'));
			} else {
				$transparentColor = null;
			}

			// colors
			$default = ShapeStyle::getDefault();
			$default->color = [0, 0, 0];
			$default->strokeWidth = $this->getParameter('strokeWidth');

			$lineStyle = new ShapeStyle();
			$lineStyle->color = Utils::hexToRgb($this->getParameter('lineColor'));;
			$lineStyle->strokeWidth = $this->getParameter('strokeWidth');
			$lineStyle->draw = (bool)$this->getParameter('drawLines');

			// load the image
			$img = imagecreatefrompng(storage_path() . '/tmp/' . $this->getParameter('filename'));

			// calculate the image size
			$imageWidth = imagesx($img);
			$imageHeight = imagesy($img);
			
			// create the base grid
			$pixelSize = $this->getParameter('pixelSize');
			$points = [];
			for ($x = 0; $x <= $imageWidth; $x++) {
				$row = [];
				for ($y = 0; $y <= $imageHeight; $y++) {
					$p = new Point($x * $pixelSize, $y * $pixelSize);
					$row[] = $p;
				}

				$points[] = $row;
			}

			// draw the pixels
			for ($x=0; $x<$imageWidth; $x++) {
				for ($y=0; $y<$imageHeight; $y++) {
					$color = imagecolorat($img, $x, $y);
					$colors = imagecolorsforindex($img, $color);

					$style = new ShapeStyle();
					$style->color = [ $colors['red'], $colors['green'], $colors['blue']];
					$style->strokeWidth = $this->getParameter('strokeWidth');

					if ($colors['alpha'] > 128) {
						$style->draw = false;
					}

					if ($transparentColor && $transparentColor == $style->color) {
						$style->draw = false;
					}

					$this->createPixel($drawing, [ $points[$x][$y], $points[$x+1][$y], $points[$x+1][$y+1], $points[$x][$y+1] ], $style);
				}
			}			

			imagedestroy($img);

			$stretch = $this->getParameter('stretchRatio');
			$drawing->scale($stretch, 1);
			$drawing->translate(new Point((3200-$imageWidth*$pixelSize*$stretch)/2, (800-$imageHeight*$pixelSize)/2));

			return $drawing;
		}

		private function createPixel(Drawing $drawing, $points, ShapeStyle $style) {
			$square = (new Polygon($points))
				->copy()
				->inset($this->getParameter('polyOffset'))
				->setStyle($style);

			$drawing->append($square);

			$infill = new Infill(
				$square->copy()->inset($this->getParameter('polyOffset')),
				$this->getParameter('infillDensity'),
				$this->getParameter('infillDirection')
			);
			$infill->setStyle($style);

			$drawing->append($infill);
		}
	}
?>