<?php
	namespace tml\Eggbot;
	use \DOMElement;
	use tml\Eggbot\Shapes\Shape;

	class LayerOptimizerData {
		public $element;
		public $start;
		public $end;
		public $ordered = false;
		public $mergeable = false;

		public function __construct(DOMElement $element) {
			$this->element = $element;

			// determine start position
			if ($element->tagName == 'path') {
				$se = $this->getPathStartEnd($element);
				if ($se) {
					$this->start = $se[0];
					$this->end = $se[1];
					$this->mergeable = true;
				}
			} else if ($element->tagName == 'g') {
				$children = $element->getElementsByTagName('path');
				$first = $this->getPathStartEnd($children->item(0));
				$last = $this->getPathStartEnd($children->item($children->length - 1));

				if ($first && $last) {
					$this->start = $first[0];
					$this->end = $last[1];
				}
			}
		}

		private function getPathStartEnd(DOMElement $element) {
			$command = $element->getAttribute('d');
			$matches = null;
			if (preg_match_all('/[ML](-?\d+\.?\d*) (-?\d+\.?\d*)/', $command, $matches, PREG_SET_ORDER) > 0) {
				$first = $matches[0];
				$last = $matches[count($matches) - 1];
				$start = [ $first[1], $first[2]];
				$end = [ $last[1], $last[2]];

				return [ $start, $end ];
			}

			return null;
		}
	}

	class LayerOptimizer {
		public $mergePathes = true;
		private $elementData;

		/**
		 * Method that orders shapes so that they are drawin in an optimal order
		 * 
		 * @param  array  $shapes Array of shapes to order
		 * @return array Ordered array of shapes
		 */
		public function optimize(DOMElement $element) {
			$this->elementData = [];

			// initialize data
			foreach ($element->childNodes as $node) {
				if ($node->nodeType == XML_ELEMENT_NODE) {
					$this->elementData[] = new LayerOptimizerData($node);
				}
			}

			$this->optimizeOrder();
			$this->mergePathes();
//			$this->printElementData(); die();
			
			// recreate the DOM element
			while ($element->hasChildNodes()){
				$element->removeChild($element->childNodes->item(0));
			}

			foreach ($this->elementData as $data) {
				$element->appendChild($data->element);
			}
		}

		private function optimizeOrder() {
			$lastX = 0;
			$lastY = 0;
			$newOrder = [];

			do {
				$closest = $this->findClosestElement($lastX, $lastY);
				if ($closest) {
					$closest->ordered = true;
					$newOrder[] = $closest;
					$lastX = $closest->end[0];
					$lastY = $closest->end[1];
				}
			} while ($closest != null);
			
			// add unordered elements to the end of the list
			foreach ($this->elementData as $data) {
				if (!$data->ordered) {
					$newOrder[] = $data;
				}
			}

			$this->elementData = $newOrder;
		}

		/**
		 * Go through the current list of pathes and if they are connected, merge them into a single move
		 */
		private function mergePathes() {
			$newOrder = [];
			$elementCount = count($this->elementData);
			$lastX = null;
			$lastY = null;
			$previousData = null;

			foreach ($this->elementData as $data) {
				$merge = false;

				if ($data->mergeable && $previousData && $previousData->mergeable) {
					if ($lastX !== null && $data->start[0] == $lastX && $data->start[1] == $lastY) {
						$merge = true;
					}
				}

				if ($merge) {
					$command = $data->element->getAttribute('d');
					$matches = null;
					if (preg_match('/^M(-?\d+\.?\d*) (-?\d+\.?\d*) (?<moves>.*)/', $command, $matches)) {
						$previousCommand = $previousData->element->getAttribute('d');
						$newCommand = $previousCommand . ' ' . $matches['moves'];
						$previousData->element->setAttribute('d', $newCommand);
						$previousData->end = $data->end;						
					} else {
						throw new Exception("Element has start position while it is not starting with an M command!");
					}
				} else {
					$newOrder[] = $data;
					$previousData = $data;
				}

				if ($data->end) {
					$lastX = $data->end[0];
					$lastY = $data->end[1];
				} else {
					$lastX = null;
					$lastY = null;
				}
			}

			$this->elementData = $newOrder;
		}

		/**
		 * Searches for the closest element that is not drawn yet
		 * 
		 * @param  float $x X position
		 * @param  float $y Y position
		 * @return LayerOptimizerData  The closest element
		 */
		private function findClosestElement($x, $y) {
			$closest = null;
			$distance = PHP_INT_MAX;

			foreach ($this->elementData as $data) {
				if ($data->ordered == false && $data->start != null) {
					$dx = $data->start[0] - $x;
					$dy = $data->start[1] - $y;
					$dist = sqrt($dx*$dx + $dy * $dy);
					if ($dist < $distance) {
						$distance = $dist;
						$closest = $data;
					}
				}
			}

			return $closest;
		}

		private function printElementData() {
			foreach ($this->elementData as $index => $data) {
				$matches = null;
				$orders = preg_match_all('/[ML]/', $data->element->getAttribute('d'), $matches, PREG_SET_ORDER);
				printf('%d: [%f,%f] =&gt; [%f,%f] [c:%d] %s<br />', $index, $data->start[0], $data->start[1], $data->end[0], $data->end[1], $orders, $data->ordered ? 'D' : '');
			}
		}
	}
?>