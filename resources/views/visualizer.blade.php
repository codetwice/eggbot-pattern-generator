<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>@yield('title', "tml's Eggbot Pattern Generator")</title>
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/knockout/3.3.0/knockout-min.js"></script>
		<script type="text/javascript">
			var speed = 50;
			$(function() {
				var $svg = null;
				var $currentElement;
				var $lastInsertedElement;

				function step() {
					if ($currentElement.length == 0) {						
						return;
					}

					var $children = $currentElement.children();
					var $next = $currentElement.next();
					var $parentNext = $currentElement.parent().next();

					if ($children.length > 0) {
						$currentElement = $children.first();
						var $copy = $currentElement.clone();
						$copy.empty();
						$lastInsertedElement.append($copy);
						$lastInsertedElement = $copy;
					} else {
						$target = $lastInsertedElement;
						while ($currentElement.length > 0) {
							var $next = $currentElement.next();

							if ($next.length > 0) {
								var $copy = $next.clone();
								$copy.empty();
								$target.after($copy);
								$lastInsertedElement = $copy;
								$currentElement = $next;
								break;
							} else {
								$currentElement = $currentElement.parent();
								$target = $lastInsertedElement.parent();
							}
						}
					}

					window.setTimeout(step, speed)
				}

				$.ajax({
					url: '{{ asset('triangles.svg')}}',
					method: 'get',
					success: function(result) {
						var s = new XMLSerializer().serializeToString(result);
						$svg = $(s);
						$currentElement = $($svg.get(0));
						$lastInsertedElement = $currentElement.clone();
						$('body').append($lastInsertedElement);
						window.setTimeout(step, speed);
					}
				});
			});
		</script>
	</head>
	<body>
	</body>
</html>
