<?php
	/**
	 * File storing the ExcelImporter utility class
	 * 
	 * @package Eggbot
	 * @subpackage Generators
	 * @author     tml <marcell.toth@gmail.com>
	 * @copyright  Blue Mushroom Inc.
	 */
	/**
	 * Requires
	 */
	namespace tml\Eggbot\Generators;
	use tml\Eggbot\Utils;
	use tml\Eggbot\Drawing;
	use tml\Eggbot\Shapes\ShapeStyle;
	use tml\Eggbot\Shapes\Point;
	use tml\Eggbot\Shapes\Line;
	use tml\Eggbot\Shapes\Polygon;
	use tml\Eggbot\Shapes\Infill;
	use tml\Eggbot\Shapes\ShapeGroup;

	/**
	 * Class for storing the data required for rendering the pixels
	 * 
	 * @package Eggbot
	 * @subpackage Generators
	 * @author tml
	 * @version 1.0
	 */
	class PixelData {
		public $color = 0;
		public $area = -1;
	}

	/**
	 * Class for storing data about the continuous pixel islands
	 * 
	 * @package Eggbot
	 * @subpackage Generators
	 * @author tml
	 * @version 1.0
	 */
	class PixelArea {
		public $id = 0;
		public $pixels = [];
		public $color;
	}

	/**
	 * Class for creating a drawing based on a bitmap file uploaded by the user
	 *
	 * This generator takes a bitmap image (found in the filename parameter) and calculates a toolpath for the Eggbot to plot it. 
	 * The toolpath generated is optimized for speed. It omits the drawing of the pixel edges if they are next to a pixel of the same 
	 * color. 
	 * 
	 * See the constructor for possible input parameters. 
	 *
	 * @package Eggbot
	 * @subpackage Generators
	 * @author tml
	 * @version 1.0
	 */
	class PixelArtGeneratorV2 extends GeneratorBase {
		/**
		 * Width of the image
		 * @var integer
		 */
		private $imageWidth;

		/**
		 * Height of the image
		 * @var integer
		 */
		private $imageHeight;

		/**
		 * Default constructor
		 */
		public function __construct() {
			$this->requiredParameters = [
				new GeneratorParameter('filename', 'file', 'monster.png', 'Image to plot (GIF, PNG, not too large! 32x32 or so!)'),
				new GeneratorParameter('transparentColor', 'string', '', 'Color to treat as transparent (hex)'),
				new GeneratorParameter('pixelSize', 'number', '25', 'Size of a single pixel.'),
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

		/**
		 * Generates the Drawing based on the image and the parameters supplied
		 * 
		 * @return Drawing The Drawing object generated
		 */
		public function generate() {
			$drawing = new Drawing(3200, 800);

			// colors
			if ($this->getParameter('transparentColor')) {
				$transparentColor = Utils::hexToRgb($this->getParameter('transparentColor'));
			} else {
				$transparentColor = null;
			}

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
			$this->imageWidth = $imageWidth;
			$this->imageHeight = $imageHeight;

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

			$pixelTopLines = [];
			$pixelBottomLines = [];
			$pixelLeftLines = [];
			$pixelRightLines = [];

			// connect the dots horizontally
			for ($i = 0; $i<$imageWidth; $i++) {
				for ($j = 0; $j <= $imageHeight; $j++) {
					$line = new Line($points[$i][$j], $points[$i+1][$j]);
					$line->setStyle($lineStyle);
					$drawing->append($line);

					if ($j < $imageHeight) {
						$pixelTopLines[$j*$imageWidth + $i] = $line;
					}

					if ($j > 0) {
						$pixelBottomLines[($j-1)*$imageWidth + $i] = $line;
					}
				}
			}

			// connect the dots vertically
			for ($i = 0; $i <= $imageWidth; $i++) {
				for ($j = 0; $j < $imageHeight; $j++) {
					$line = new Line($points[$i][$j], $points[$i][$j+1]);
					$line->setStyle($lineStyle);
					$drawing->append($line);

					if ($i < $imageWidth) {
						$pixelLeftLines[$j*$imageWidth + $i] = $line;
					}
					
					if ($i > 0) {
						$pixelRightLines[$j*$imageWidth + $i-1] = $line;
					}
				}
			}

			// isolate the pixel areas
			$i = 0;
			$pixels = $imageWidth * $imageHeight;
			$pixelData = [];
			for ($i=0; $i<$pixels; $i++) {
				$pixelData[$i] = new PixelData();
				$pixelData[$i]->color = imagecolorat($img, $i%$imageWidth, floor($i/$imageWidth));
			}

			$areas = [];

			$i = 0;

			// isolate areas and draw them
			while ($i < $pixels) {
				if ($pixelData[$i]->area < 0) {
					// if the current pixel doesn't belong to an area yet, determine the area
					$area = new PixelArea();
					$area->id = $i;
					$area->color = $pixelData[$i]->color;
					$this->expandArea($area, $pixelData, $i);

					// get it's contour
					$lines = $this->getAreaContour($area, $pixelTopLines, $pixelRightLines, $pixelBottomLines, $pixelLeftLines);

					// create a copy so that we don't mess up the original grid
					$lines = array_map(function($item) { return $item->copy(); }, $lines);

					// convert the outline to polygons
					$polygons = Utils::linesToPolygons($lines);

					// draw the polygons
					$colorData = imagecolorsforindex($img, $area->color);
					$style = new ShapeStyle();
					$style->strokeWidth = $this->getParameter('strokeWidth');
					$style->color = [ $colorData['red'], $colorData['green'], $colorData['blue'] ];

					if ($colorData['alpha'] > 128) {
						$style->draw = false;
					}

					if ($transparentColor && $style->color == $transparentColor) {
						$style->draw = false;
					}

					$infillGroup = new ShapeGroup();

					foreach ($polygons as $index => $polygon) {
						$polygon->setStyle($style);
						if ($index == 0) {
							$polygon->inset($this->getParameter('polyOffset'));
							$infillGroup->add($polygon->copy()->inset($this->getParameter('infillOffset')));
						} else {
							$polygon->outset($this->getParameter('polyOffset'));
							$infillGroup->add($polygon->copy()->outset($this->getParameter('infillOffset')));
						}

						$drawing->append($polygon);
					}

					// generate the infill
					$infill = new Infill($infillGroup, $this->getParameter('infillDensity'), $this->getParameter('infillDirection'));
					$infill->setStyle($style);
					$drawing->append($infill);
				}

				$i++;
			}

			imagedestroy($img);

			$stretch = $this->getParameter('stretchRatio');
			$drawing->scale($stretch, 1);
			$drawing->translate(new Point((3200-$imageWidth*$pixelSize*$stretch)/2, (800-$imageHeight*$pixelSize)/2));

			return $drawing;
		}

		/**
		 * Generates a series of lines around an island of pixels
		 *
		 * This method takes an area as its main input, plus the grid data for the different sides of the pixels. 
		 * It takes the lines from the line data which can be used to surround the area defined by the $area 
		 * parameter and returns them in form of an array
		 * 
		 * @param  PixelArea $area             Area to get the contour of
		 * @param  array     $pixelTopLines    Array of the lines located on the top side of the grid cells
		 * @param  array     $pixelRightLines  Array of the lines located on the right side of the grid cells
		 * @param  array     $pixelBottomLines Array of the lines located on the bottom side of the grid cells
		 * @param  array     $pixelLeftLines   Array of the lines located on the left side of the grid cells
		 * @return array A collection of Line objects
		 */
		private function getAreaContour(PixelArea $area, array $pixelTopLines, array $pixelRightLines, array $pixelBottomLines, array $pixelLeftLines) {
			$lines = [];
			foreach ($area->pixels as $pixel) {
				$x = $pixel % $this->imageWidth;
				$y = floor($pixel / $this->imageWidth);

				if ($x == 0 || !in_array($pixel-1, $area->pixels)) {
					$lines[] = $pixelLeftLines[$pixel];
				}

				if ($x == $this->imageWidth-1 || !in_array($pixel+1, $area->pixels)) {
					$lines[] = $pixelRightLines[$pixel];
				}

				if ($y == 0 || !in_array($pixel-$this->imageWidth, $area->pixels)) {
					$lines[] = $pixelTopLines[$pixel];
				}

				if ($y == $this->imageHeight-1 || !in_array($pixel+$this->imageWidth, $area->pixels)) {
					$lines[] = $pixelBottomLines[$pixel];
				}
			}

			return $lines;
		}

		/**
		 * Expands an area around a pixel so that it encapsulates all neighbouring pixels if they are of the same color as the original pixel
		 * 
		 * @param  PixelArea $area       Area to expand
		 * @param  array     &$pixelData Array of pixel data
		 * @param  integer    $index     Index of the pixel to start the expansion from
		 */
		private function expandArea(PixelArea $area, array &$pixelData, $index) {
			$x = $index%$this->imageWidth;
			$y = floor($index/$this->imageWidth);
			$color = $pixelData[$index]->color;
			$area->pixels[] = $index;
			$pixelData[$index]->area = $area->id;
			$candidates = [];

			if ($x > 0) {
				$candidates[] = $index-1;
			}

			if ($x < $this->imageWidth-1) {
				$candidates[] = $index+1;
			}

			if ($y > 0) {
				$candidates[] = $index-$this->imageWidth;
			}

			if ($y < $this->imageHeight-1) {
				$candidates[] = $index+$this->imageWidth;
			}

			foreach ($candidates as $candidate) {
				if ($pixelData[$candidate]->area < 0 && $pixelData[$candidate]->color == $color) {
					$this->expandArea($area, $pixelData, $candidate);
				}
			}
		}
	}
?>