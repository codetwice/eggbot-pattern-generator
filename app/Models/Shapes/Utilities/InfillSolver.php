<?php
	namespace tml\Eggbot\Shapes\Utilities;
	use tml\Eggbot\Shapes\Polygon;
	use tml\Eggbot\Shapes\ShapeGroup;
	use tml\Eggbot\Shapes\Line;

	require_once('PathFindingStatus.php');
	
	// class that does a complete travesal of infill lines and partial traveral of the boundaries
	class InfillSolver {
		private $lines;
		private $infillLines;
		private $points = [];
		private $edges = [];

		public function __construct(array $lines, array $infillLines) {
			$this->lines = array_map(function($item) { return $item->copy(); }, $lines);
			$this->infillLines = $infillLines;
		}

		/**
		 * This method generates an array of point vectors in which the inside of the polygon can be traversed 
		 * so that each infill line is traversed. 
		 * 
		 * @return array Array of array of point data
		 */
		public function calculate() {
			// create a new property on the polygon's points for storing the subpoints
			$group = new ShapeGroup($this->lines);
			$points = $group->getPoints();
			unset($group);
			$lines = $this->lines;

			foreach ($lines as $l) {
				$l->subPoints = [];
			}

			// insert the infill's end points into each of the points they come after when traversing
			foreach ($this->infillLines as $line) {
				$lines[$line->pointA->lineIndex]->subPoints[] = $line->pointA;
				$lines[$line->pointB->lineIndex]->subPoints[] = $line->pointB;
			} 

			// order the subpoints according to the direction of hte line they belong to
			foreach ($lines as $line) {
				if ($line->pointA->x < $line->pointB->x) {
					$line->subPoints = array_reverse($line->subPoints);
				}
			}

			// next create a new set of lines which also contains the infill's endpoints
			$newLines = new ShapeGroup();

			foreach ($lines as $l) {
				$points = $l->subPoints;
				array_unshift($points, $l->pointA);
				array_push($points, $l->pointB);

				$pc = count($points);
				for ($i=0; $i<$pc-1; $i++) {
					$line = new Line($points[$i], $points[$i+1]);
					$newLines->add($line);
				}
			}

			// create a list of points in the graph
			foreach ($newLines->getPoints() as $p) {
				$this->addPoint($p->x, $p->y);
			}

			// create a list of optional lines in the graph
			foreach ($newLines->getLines() as $line) {
				$p1 = $line->pointA;
				$p2 = $line->pointB;
				$i1 = $this->findPoint($p1->x, $p1->y);
				$i2 = $this->findPoint($p2->x, $p2->y);
				$this->addEdge($i1, $i2, false);
			}

			// create a list of mandatory edges in the graph
			foreach ($this->infillLines as $line) {
				$p1 = $line->pointA;
				$p2 = $line->pointB;
				$i1 = $this->findPoint($p1->x, $p1->y);
				$i2 = $this->findPoint($p2->x, $p2->y);

				$this->addEdge($i1, $i2, true);
			}

			// traversal algorithm
			$pathes = [];
			while(true) {
				// find a single path covering at least one mandatory line
				$traversedPoints = $this->traverse();
				if ($traversedPoints === null) {
					break;
				}

				$pathes[] = $traversedPoints;
			}

			// create result
			$result = [];
			foreach ($pathes as $path) {
				$rp = [];
				foreach ($path as $pointIndex) {
					$rp[] = $this->points[$pointIndex];
				}

				$result[] = $rp;
			}

			return $result;
		}

		private function addPoint($x, $y) {
			foreach ($this->points as $index => $p) {
				if ($p[0] == $x && $p[1] == $y) {
					return $index;
				}
			}

			$this->points[] = [$x, $y];
			return count($this->points)-1;
		}

		private function findPoint($x, $y) {
			foreach ($this->points as $index => $p) {
				if ($p[0] == $x && $p[1] == $y) {
					return $index;
				}
			}

			throw new Exception('Point not found!');
		}

		private function addEdge($p1, $p2, $mandatory) {
			if ($p1 == $p2) {
				return -1;
			}

			foreach ($this->edges as $index => $edge) {
				if ($edge['a'] == $p1 && $edge['b'] == $p2 || $edge['a'] == $p2 && $edge['b'] == $p1) {
					return $index;
				}
			}

			/**
			 * a: start point
			 * b: end point
			 * m: mandatory
			 * t: traversed
			 */
			$this->edges[] = [
				'a' => $p1, 
				'b' => $p2,
				'm' => $mandatory,
				't' => false
			];

			return count($this->edges)-1;
		}

		/**
		 * Finds all edges going out from a point
		 * 
		 * @param  integer $pointIndex index of the point
		 * @param boolean $traversed Filter for the traversed flag
		 * @param boolean $mandatory Filter for the mandatory flag
		 * @return array Array of edge indices
		 */
		private function findEdges($pointIndex, $traversed = null, $mandatory = null) {
			$indices = [];
			foreach ($this->edges as $index => $edge) {
				if ($edge['a'] != $pointIndex && $edge['b'] != $pointIndex)  {
					continue;
				}

				if ($traversed !== null && $edge['t'] != $traversed) {
					continue;
				}

				if ($mandatory !== null && $edge['m'] != $mandatory) {
					continue;
				}

				$indices[] = $index;
			}

			return $indices;
		}

		private function findFirstMandatoryEdge($traversed) {
			foreach ($this->edges as $index=>$edge) {
				if ($edge['m'] && $edge['t'] == $traversed) {
					return $index;
				}
			}

			return -1;
		}

		/**
		 * Finds an edge by two point indexes
		 * 
		 * @param  integer $pointA Index of point A
		 * @param  integer $pointB Index of point B
		 * @return integer Index of the edge. -1 if it could not be found
		 */
		private function findEdgeByPoints($pointA, $pointB) {
			foreach ($this->edges as $index => $edge) {
				if ($edge['a'] == $pointA && $edge['b'] == $pointB || $edge['a'] == $pointB && $edge['b'] == $pointA) {
					return $index;
				}
			}

			return -1;
		}

		/**
		 * Traverses the mandatory edges of the graph defined by the $points and $edges properties
		 * 
		 * @return [type] [description]
		 */
		private function traverse() {
			// return value
			$traversedPoints = [];

			// get a starting point
			$edgeIndex = $this->findFirstMandatoryEdge(false);

			// if there is none, we are done
			if ($edgeIndex < 0) {
				return null;
			}
			$edge = $this->edges[$edgeIndex];
			$startingPoint = $edge['a'];

			// DIKJSTRA
			while ($startingPoint !== null) { 
				$pfData = new PathFindingStatus();
				$pfData->addPoint($startingPoint, -1, false);
				$result = $this->dijkstra($pfData);

				if ($result) {
					// we found a mandatory point!
					$pointPath = $pfData->getPathToFinal();		
					$edgePath = [];

					// turn the path into a series of edges
					for ($i = 1; $i < count($pointPath); $i++) {
						$edgeIndex = $this->findEdgeByPoints($pointPath[$i-1], $pointPath[$i]);
						$edgePath[] = $edgeIndex;
					}

					// mark the edges as travesed
					foreach ($edgePath as $index) {
						$this->edges[$index]['t'] = true;
					}

					// new starting point is the last point of the previous path
					$startingPoint = $pointPath[count($pointPath) -1];
					$traversedPoints = array_merge($traversedPoints, $pointPath);
				} else {
					$startingPoint = null;
				}
			}

			return array_unique($traversedPoints);
		}

		/**
		 * Dijkstra iteration
		 * 
		 * @param  PathFindingStatus $pfData Current pathfinding data
		 * @return [type]         [description]
		 */
		private function dijkstra($pfData) {
			// expand the points
			$currentPoints = $pfData->reachedPoints;
			$expandCount = 0;
			$done = false;

			foreach ($currentPoints as &$pd) {
				if ($pd['new']) {
					$point = $pd['point'];
					$pfData->markAsVisited($point);
					$edgeIndexes = $this->findEdges($point, false, null);

					foreach ($edgeIndexes as $i) {
						$edge = $this->edges[$i];

						// expand
						$nextPoint = $edge['a'] == $point ? $edge['b'] : $edge['a'];
						$isFinal = $edge['m'];

						$result = $pfData->addPoint($nextPoint, $point, $isFinal);

						if ($result) {
							$expandCount++;
						}

						if ($isFinal) {
							// we traversed a mandatory edge! We are done. 
							$done = true;
						}
					}
				}

				unset($pd);

				if ($done) {
					break;
				}
			}

			// if we have a final point, we have taversed a mandatory edge, which is what we wanted to do. Return the pf data
			if ($done) {
				return true;
			} else if ($expandCount > 0) {
				// if at least one new point has been added during the expansion, do it again
				return $this->dijkstra($pfData);
			} else {
				// we could not expand and we dont have a final point. Game over.
				return null;
			}
		}
	}
?>