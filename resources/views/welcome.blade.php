@extends('layout')
@section('head')
	<script type="text/javascript">
		function GeneratorModel() {
			var self = this;
			self.generators = ko.observableArray();
			self.generator = ko.observable();
			self.randomSeed = ko.observable();
			self.svgLoaded = ko.observable(false);
			self.generatedUrl = ko.observable();
			self.svgTargetWindow = null;

			self.hasFileParameter = ko.pureComputed(function() {
				var generator = self.generator();
				if (generator) {
					for (var i in generator.parameters) {
						if (generator.parameters[i].type == 'file') {
							return true;
						}
					}
				}

				return false;
			});

			self.svgUrl = ko.pureComputed(function() {
				var params = {};

				$.each(self.generator().parameters, function(index, parameter) {
					params[parameter.name] = parameter.value();
				});

				if (self.generator()) {
					return self.generator().url + '?' + $.param(params);
				} else {
					return null;
				}
			});

			self.openResultWindow = function() {
				self.svgTargetWindow = window.open('', '_blank');
				return true;
			}

			$.ajax({
				url: '{{ action('HomeController@getGenerators') }}',
				method: 'get',
				success: function(result) {
					$.each(result, function(index, generator) {
						$.each(generator.parameters, function(index, parameter) {
							var value = parameter.defaultValue;

							if (parameter.type == 'number') {
								value = parseFloat(value);
							} else if (parameter.type == 'boolean') {
								value = parseInt(value);
							} else if (parameter.type == 'enumeration') {
								var values = [];
								for (var i in parameter.values) {
									values.push({ value: i, label: parameter.values[i] });
								}

								parameter.values = values;
							}

							parameter.value = ko.observable(value);
						});

						generator.parameters.push({name: 'randomSeed', description: 'Random seed', value: self.randomSeed, type: 'number' });
					});

					self.generators(result);
				}
			});
		}

		$(function() {
			var model = new GeneratorModel();
			ko.applyBindings(model);
			$('#parameterForm').ajaxForm(function(result) { 
				model.svgTargetWindow.location.href = result;
			});
   		});
	</script>
@stop
@section('content')
	<div class="page-header">
		<h1>tml's Eggbot Pattern Generator</h1>
	</div>
	<p>
		Welcome to my Eggbot pattern generator!
	</p>
		<form>
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="form-group">
						<label for="generatorId">Select a pattern</label>
						<select class="form-control" id="generatorId" data-bind="value: generator, options: generators, optionsText: 'description', optionsCaption: '- Select -'"></select>
					</div>
				</div>
			</div>
		</form>
		<form data-bind="attr: { action: generator() ? generator().url : '' }, with: generator" method="post" id="parameterForm">
			<div class="panel panel-default">
				<div class="panel-heading">Pattern paremeters</div>
				<div class="panel-body">
				<!-- ko foreach: parameters -->
					<div class="col-md-6">
						<!-- ko if: type=='string' -->
						<div class="form-group">
							<label data-bind="attr: { for: name }, text: description"></label>
							<input type="text" class="form-control" data-bind="value: value, attr: { name: name, id: name }"></input>
						</div>
						<!-- /ko -->
						<!-- ko if: type=='number' -->
						<div class="form-group">
							<label data-bind="attr: { for: name }, text: description"></label>
							<input type="number" class="form-control" data-bind="value: value, attr: { name: name, id: name }"></input>
						</div>
						<!-- /ko -->
						<!-- ko if: type=='boolean' -->
						<div class="form-group">
							<label data-bind="attr: { for: name }, text: description"></label>
							<select class="form-control" data-bind="value: value, attr: { name: name, id: name }">
								<option value="0">No</option>
								<option value="1">Yes</option>
							</select>
						</div>
						<!-- /ko -->
						<!-- ko if: type=='enumeration' -->
						<div class="form-group">
							<label data-bind="attr: { for: name }, text: description"></label>
							<select class="form-control" data-bind="value: value, attr: { name: name, id: name }, options: values, optionsText: 'label', optionsValue: 'value'"></select>
						</div>
						<!-- /ko -->
						<!-- ko if: type=='file' -->
						<div class="form-group">
							<label data-bind="attr: { for: name }, text: description"></label>
							<input type="file" class="form-control" data-bind="attr: { name: name, id: name }"></input>
						</div>
						<!-- /ko -->
					</div>
				<!-- /ko -->
				</div>
				<div class="panel-footer text-center">
					<a class="btn btn-primary" onclick="window.open(this.href); return false;" data-bind="attr:{ href: $root.svgUrl() }, visible: requiresPreparation==false">Generate</a>
					<button type="submit" class="btn btn-primary" data-bind="click: $root.openResultWindow, visible: requiresPreparation">Generate</button>
				</div>
			</div>
		</form>
@stop