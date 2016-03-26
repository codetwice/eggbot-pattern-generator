<?php
	namespace tml\Eggbot\Shapes;

	class ShapeStyle {
		public $color;
		public $strokeWidth;
		public $draw = true;
		
		private static $defaultStyle;

		/**
		 * Returns the default shape style
		 * 
		 * @return ShapeStyle The default shape config
		 */
		public static function getDefault() {
			if (!self::$defaultStyle) {
				self::$defaultStyle = new ShapeStyle();
			}

			return self::$defaultStyle;
		}		
	}
?>