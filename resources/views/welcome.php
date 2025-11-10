<?php
ob_start();
?>
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
            url: "<?= action([\App\Http\Controllers\HomeController::class, 'getGenerators']) ?>",
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
<?php
$head = ob_get_clean();

ob_start();
?>
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
                <!-- ko if: type=='boolean' -->
                <div class="form-group">
                    <label data-bind="attr: { for: name }, text: description"></label>
                    <select class="form-control" data-bind="value: value, attr: { name: name, id: name }">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <!-- /ko -->
            </div>
            <!-- /ko -->
        </div>
    </div>
    <div class="panel panel-default" data-bind="visible: generator">
        <div class="panel-heading">Generate pattern</div>
        <div class="panel-body">
            <button type="submit" class="btn btn-primary" data-bind="click: $parent.openResultWindow, enable: !$parent.hasFileParameter()">Generate SVG</button>
            <button type="submit" class="btn btn-default" data-bind="enable: $parent.hasFileParameter()">Upload Files & Generate SVG</button>
            <a class="btn btn-default" data-bind="attr: { href: generator().url + '/download?' + $.param({ randomSeed: $parent.randomSeed() }) }, enable: $parent.generator">Download SVG</a>
            <a class="btn btn-default" data-bind="attr: { href: generator().url + '?' + $.param({ randomSeed: $parent.randomSeed() }) }, enable: $parent.generator, attr: { target: '_blank' }">Open SVG in new tab</a>
        </div>
    </div>
</form>
<?php
$content = ob_get_clean();
$title = "tml's Eggbot Pattern Generator";
include __DIR__ . '/layout.php';
