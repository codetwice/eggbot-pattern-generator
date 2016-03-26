<?php
	namespace tml\Eggbot\Shapes\Utilities;

	class PathFindingStatus {
		/**
		 * Array of points that have been reached so far. 
		 * Each entry contains the index of the point and the index of the edge which can be used to reach it
		 * 
		 * @var array
		 */
		public $reachedPoints = []; 
		private $finished = false;

		/**
		 * Adds a new point to the list of reached points
		 *
		 * @param integer $pointIndex Index of the point
		 * @param integer $previousIndex  Index of the point that can be used to reach it
		 * @param boolean $isFinal Specifies whether this is a final point
		 */
		public function addPoint($pointIndex, $previousIndex, $isFinal) {
			foreach ($this->reachedPoints as &$pd) {
				if ($pd['point'] == $pointIndex) {
					if ($isFinal) {
						// we have reached this point before but this time over a mandatory edge
						// update it.
						$pd['final'] = true;
						$pd['previous'] = $previousIndex;
						$this->finished = true;
						return true;
					} else {
						// we have reached this point before 
						// do not add it
						return false;
					}
				}

				unset($pd);
			}

			// we do not have a path to this point yet. Add it to the reached set.
			$this->reachedPoints[] = [
				'point' => $pointIndex,
				'previous' => $previousIndex,
				'new' => true,
				'final' => $isFinal
			];

			return true;
		}

		/**
		 * Marks a point as visited by the Dijstra algorithm
		 * 
		 * @param  int $pointIndex Point index
		 */
		public function markAsVisited($pointIndex) {
			foreach ($this->reachedPoints as &$pd) {
				if ($pd['point'] == $pointIndex) {
					$pd['new'] = false;
				}

				unset($pd);
			}
		}

		/**
		 * Returns the path of points leading to the final point
		 * 
		 * @return array Array of point indexes
		 */
		public function getPathToFinal() {
			$pi = null;

			foreach ($this->reachedPoints as $pd) {
				if ($pd['final']) {
					$pi = $pd['point'];
					break;
				}
			}

			if ($pi === null) {
				return null;
			} else {
				return $this->getPath($pi, [ $pi ]);
			}
		}

		public function getPath($point, array $path) {
			$previous = null;
			foreach ($this->reachedPoints as $pd) {
				if ($pd['point'] == $point) {
					$previous = $pd['previous'];
				}
			}

			if ($previous == -1) {
				// we are done, return
				return $path;
			} else if ($previous === null) {
				// we are stuck, return
				return null;
			} else {
				array_unshift($path, $previous);
				return $this->getPath($previous, $path);
			}
		}
	}
?>