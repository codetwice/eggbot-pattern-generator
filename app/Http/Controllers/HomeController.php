<?php 

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use tml\Eggbot\Shapes\Point;

class HomeController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Welcome Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders the "marketing page" for the application and
	| is configured to only allow guests. Like most of the other sample
	| controllers, you are free to modify or remove it as you desire.
	|
	*/

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('guest');
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('welcome');
	}

	public function getGenerators() {
		$classes = $this->getGeneratorClasses();

		$result = [];
		foreach ($classes as $classData) {
			$class = $classData['class'];
			$id = $classData['id'];
			$requiresPreparation = false;

			$instance = new $class;
			$parameters = $instance->getRequiredParameters();
			foreach ($parameters as $parameter) {
				if ($parameter->type == 'file') {
					$requiresPreparation = true;
				}
			}

			$result[] = [
				'id' =>$id,
				'url' => action('HomeController@generateSvg', $id),
				'description' => $classData['description'],
				'parameters' => $parameters, 
				'requiresPreparation' => $requiresPreparation
			];
		}

		return response()->json($result);
	}

	public function generateSvg(Request $request, $id) {
		if ($request->has('randomSeed')) {
			srand($request->input('randomSeed'));
		}

		$parameters = $request->all();
		$generator = $this->getGeneratorClassById($id);
		foreach ($parameters as $name=>$value) {
			$generator->setParameter($name, $value);
		}

		$drawing = $generator->generate();
		$svg = $drawing->getSvg();
		return response($svg->saveXml(), 200)
			->header('Content-Type', 'image/svg+xml');
	}

	public function prepareSvg(Request $request, $id) {
		$parameters = $request->all();

		// load the parameters into the generator
		$generator = $this->getGeneratorClassById($id);
		foreach ($parameters as $name=>$value) {
			$generator->setParameter($name, $value);
		}

		// handle file uploads
		foreach ($generator->getRequiredParameters() as $parameter) {
			if ($parameter->type == 'file' && $request->hasFile($parameter->name)) {
				$file = $request->file($parameter->name);
				$mime = $file->getMimeType();
				if ($mime == 'image/png') {
					$targetFilename = rand(100000, 999999) . '.png';
				} else if ($mime == 'image/gif') {
					$targetFilename = rand(100000, 999999) . '.gif';
				} else {
					$targetFilename = null;
				}

				if ($targetFilename) {
					$path = storage_path() . '/tmp';
					$file->move($path, $targetFilename);
					$generator->setParameter($parameter->name, $targetFilename);
				}
			}
		}

		// create the URL to get the svg from
		$errors = $generator->validate();
		if (count($errors) == 0) {
			$generatorParameters = $generator->getAllParameters();
			$generatorParameters['id'] = $id;
			return action('HomeController@generateSvg', $generatorParameters);
		} else {
			return response('Error', 403);
		}
	}

	public function downloadSvg(Request $request, $id) {
		if ($request->has('randomSeed')) {
			srand($request->input('randomSeed'));
		}

		$parameters = $request->all();
		$generator = $this->getGeneratorClassById($id);
		foreach ($parameters as $name=>$value) {
			$generator->setParameter($name, $value);
		}

		$drawing = $generator->generate();
		$svg = $drawing->getSvg();
		return response($svg->saveXml(), 200)
			->header('Pragma', 'public')
			->header('Expires', '0')
			->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
			->header('Content-Type', 'application/force-download')
			->header('Content-Type', 'application/octet-stream')
			->header('Content-Type', 'application/download')
			->header('Content-Disposition:', 'attachment; filename=' . $id . '.svg')
			->header('Content-Transfer-Encoding', 'binary');
	}

	public function visualizeSvg() {
		return view('visualizer');
	}

	private function getGeneratorClasses() {
		$classes = [ 
			[ 
				'id' => 'triangles', 
				'class' => 'tml\Eggbot\Generators\TriangleGenerator',
				'description' => 'Triangle pattern'
			],
			[ 
				'id' => 'squares', 
				'class' => 'tml\Eggbot\Generators\SquareGenerator',
				'description' => 'Square pattern'
			],
			[ 
				'id' => 'pixelart_v1', 
				'class' => 'tml\Eggbot\Generators\PixelArtGeneratorV1',
				'description' => 'Pixel art (slow and accurate)'
			],
			[ 
				'id' => 'pixelart_v2', 
				'class' => 'tml\Eggbot\Generators\PixelArtGeneratorV2',
				'description' => 'Pixel art (fast and efficient)'
			]			
		];

		$enabled = env('ENABLED_GENERATORS');
		if ($enabled) {
			$enabled = explode(',', $enabled);
			foreach ($classes as $i => $def) {
				if (!in_array($def['id'], $enabled)) {
					unset($classes[$i]);
				}
			}
		}

		return $classes;
	}

	private function getGeneratorClassById($id) {
		$classes = $this->getGeneratorClasses();

		foreach ($classes as $classData) {
			if ($classData['id'] == $id) {
				$className = $classData['class'];
				return new $className;
			}
		}

		return null;
	}
}
