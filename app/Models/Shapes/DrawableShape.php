<?php
	namespace tml\Eggbot\Shapes;
	use \DOMDOcument;
	
	abstract class DrawableShape extends Shape {
		public $style;

		public function __construct() {
			$this->style = ShapeStyle::getDefault();
		}

		/**
		 * Sets the shape style
		 * 
		 * @param ShapeStyle $style The style
		 * @return Shape The current Shape instance
		 */
		public function setStyle(ShapeStyle $style) {
			$this->style = $style;
			return $this;
		}

		/**
		 * Creates a path with the set of commands passed as parameter
		 *
		 * @param DOMDocument $dom DOM Document
		 * @param  array  $commands Array of commands
		 * @return DOMElement Path SVG element
		 */
		protected function createPathElement(DOMDocument $dom, array $commands) {
			$pathElement = $dom->createElement('path');
			$pathElement->setAttribute('d', implode(' ', $commands));
			$pathElement->setAttribute('stroke', sprintf('rgb(%d,%d,%d)', $this->style->color[0], $this->style->color[1], $this->style->color[2]));
			$pathElement->setAttribute('stroke-width', $this->style->strokeWidth);
			$pathElement->setAttribute('fill', 'none');
			return $pathElement;
		}

		/**
		 * Creates a SVG DOM element based on this shape
		 *
		 * @param DOMDOcument $dom The DOM document
		 * @return DOMElement The DOM Element
		 */
		public abstract function createSvgElement(DOMDocument $dom);
	}

?>