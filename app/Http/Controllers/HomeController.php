<?php

namespace App\Http\Controllers;

use App\Eggbot\Generators\GeneratorBase;
use App\Eggbot\Generators\GeneratorParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index(): Response
    {
        return response()->view('welcome');
    }

    public function getGenerators(): Response
    {
        $definitions = $this->availableGenerators();

        $generators = collect($definitions)->map(function (array $definition) {
            /** @var GeneratorBase $instance */
            $instance = app($definition['class']);

            $parameters = collect($instance->getRequiredParameters());
            $requiresPreparation = $parameters->contains(fn (GeneratorParameter $parameter) => $parameter->type === 'file');

            return [
                'id' => $definition['id'],
                'url' => action([self::class, 'generateSvg'], ['id' => $definition['id']]),
                'description' => $definition['description'],
                'parameters' => $parameters->values()->all(),
                'requiresPreparation' => $requiresPreparation,
            ];
        })->values();

        return response()->json($generators);
    }

    public function generateSvg(Request $request, string $id): Response
    {
        if ($request->filled('randomSeed')) {
            srand((int) $request->input('randomSeed'));
        }

        $generator = $this->resolveGenerator($id, $request->all());

        $drawing = $generator->generate();
        $svg = $drawing->getSvg();

        return response($svg->saveXml(), 200)
            ->header('Content-Type', 'image/svg+xml');
    }

    public function prepareSvg(Request $request, string $id): Response
    {
        $generator = $this->resolveGenerator($id, $request->except(['_token']));

        foreach ($generator->getRequiredParameters() as $parameter) {
            if ($parameter->type === 'file' && $request->hasFile($parameter->name)) {
                $file = $request->file($parameter->name);
                $extension = match ($file?->getMimeType()) {
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    default => null,
                };

                if ($extension) {
                    $filename = sprintf('%06d.%s', random_int(100000, 999999), $extension);
                    $file->move(storage_path('tmp'), $filename);
                    $generator->setParameter($parameter->name, $filename);
                }
            }
        }

        $errors = $generator->validate();
        if (empty($errors)) {
            $parameters = Arr::add($generator->getAllParameters(), 'id', $id);

            return response(action([self::class, 'generateSvg'], $parameters));
        }

        return response('Error', 403);
    }

    public function downloadSvg(Request $request, string $id): Response
    {
        if ($request->filled('randomSeed')) {
            srand((int) $request->input('randomSeed'));
        }

        $generator = $this->resolveGenerator($id, $request->all());

        $drawing = $generator->generate();
        $svg = $drawing->getSvg();

        return response($svg->saveXml(), 200)
            ->header('Pragma', 'public')
            ->header('Expires', '0')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename=' . $id . '.svg')
            ->header('Content-Transfer-Encoding', 'binary');
    }

    public function visualizeSvg(): Response
    {
        return response()->view('visualizer');
    }

    /**
     * @return array<int, array{id: string, class: class-string<GeneratorBase>, description: string}>
     */
    protected function availableGenerators(): array
    {
        $generators = [
            [
                'id' => 'triangles',
                'class' => \App\Eggbot\Generators\TriangleGenerator::class,
                'description' => 'Triangle pattern',
            ],
            [
                'id' => 'squares',
                'class' => \App\Eggbot\Generators\SquareGenerator::class,
                'description' => 'Square pattern',
            ],
            [
                'id' => 'pixelart_v1',
                'class' => \App\Eggbot\Generators\PixelArtGeneratorV1::class,
                'description' => 'Pixel art (slow and accurate)',
            ],
            [
                'id' => 'pixelart_v2',
                'class' => \App\Eggbot\Generators\PixelArtGeneratorV2::class,
                'description' => 'Pixel art (fast and efficient)',
            ],
        ];

        $enabled = config('app.enabled_generators');

        if ($enabled) {
            $allowed = Collection::make(explode(',', $enabled))
                ->map(fn ($id) => trim($id))
                ->filter()
                ->all();

            $generators = array_values(array_filter(
                $generators,
                fn ($generator) => in_array($generator['id'], $allowed, true)
            ));
        }

        return $generators;
    }

    protected function resolveGenerator(string $id, array $parameters): GeneratorBase
    {
        $generator = Collection::make($this->availableGenerators())
            ->firstWhere('id', $id);

        abort_unless($generator, 404);

        /** @var GeneratorBase $instance */
        $instance = app($generator['class']);

        foreach ($parameters as $name => $value) {
            $instance->setParameter($name, $value);
        }

        return $instance;
    }
}
