<?php
	namespace tml\Eggbot;
	use \DOMDocument;
	use tml\Eggbot\Shapes\Shape;
	use tml\Eggbot\Shapes\ShapeGroup;

	class Drawing extends ShapeGroup {
		private $dom;
		private $root; 
		private $width;
		private $height;

		public function __construct($width = 3200, $height = 800, $centered = true) {
			$this->width = $width;
			$this->height = $height;

			$dom = new DOMDocument('1.0', 'utf-8');
			$root = $dom
				->createElementNS('http://www.w3.org/2000/svg', 'svg');

			$root->setAttribute('version', '1.1');
			$root->setAttribute('width', $this->width);
			$root->setAttribute('height', $this->height);
			$dom->appendChild($root);
			$this->dom = $dom;
			$this->root = $root;
		}

		public function append(Shape $shape) {
			$this->add($shape);
		}

		public function getSvg() {
			// collect elements of different colors
			$layers = [];

			foreach ($this->shapes as $shape) {
				if (is_a($shape, 'tml\Eggbot\Shapes\DrawableShape') && $shape->style != null && $shape->style->draw) {
					$color = implode(',', $shape->style->color);

					if (!isset($layers[$color])) {
						$layers[$color] = [];
					}

					$layers[$color][] = $shape;
				}
			}

			$i = 1;
			$optimizer = new LayerOptimizer();

			foreach ($layers as $color => $layer) {
				$groupElement = $this->dom->createElement('g');

				$labelAttribute = $this->dom->createAttributeNS('http://www.inkscape.org/namespaces/inkscape', 'inkscape:label');
				$labelAttribute->value = sprintf('%d-%s', $i, $color);
				$groupElement->appendChild($labelAttribute);

				$groupModeAttribute = $this->dom->createAttributeNS('http://www.inkscape.org/namespaces/inkscape', 'inkscape:groupmode');
				$groupModeAttribute->value = 'layer';
				$groupElement->appendChild($groupModeAttribute);

				$groupElement->setAttribute('id', 'layer' . $i);

				foreach ($layer as $shape) {
					$element = $shape->createSvgElement($this->dom);
					if ($element) {
						$groupElement->appendChild($element);
					}
				}

				$optimizer->optimize($groupElement);
				$this->root->appendChild($groupElement);
				$i++;
			}


			return $this->dom;
		}

		public function draw() {
			$xml = $this->getSvg();
			$text = $xml->saveXml();
			header('Content-Type: image/svg+xml');
			echo $text;
		}
	}

?>