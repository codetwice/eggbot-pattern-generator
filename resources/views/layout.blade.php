<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>@yield('title', "tml's Eggbot Pattern Generator")</title>
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
		<link href="{{ asset('css/layout-bootstrap.css') }}" rel="stylesheet">
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/knockout/3.3.0/knockout-min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.form/3.51/jquery.form.min.js"></script>
		@yield('head')
	</head>
	<body>
		<div class="container-fluid">
			@yield('content')
		</div>
		<footer class="footer">
			<div class="container">
				<p class="text-muted">Eggbot Pattern Generator v0.9 (c) 2015 tml</p>
			</div>
		</footer>
	</body>
</html>
