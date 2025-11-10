## tml's Eggbot Pattern Generator

This Eggbot Pattern Generator is a PHP web application that produces SVG toolpaths for an [EggBot](http://egg-bot.com). The app exposes the original four generators:

* A colorful pattern made up by randomized 4 cornered shapes
* A colorful pattern made up by randomized triangles
* Two bitmap-to-SVG pixel art generators (one optimized for accuracy, the other for speed)

The codebase now runs on a lightweight, framework-free runtime that keeps the original generator classes but removes the Laravel dependency so it can be installed without downloading third-party packages.

## Installation

1. Clone the repository
2. Install [Composer](https://getcomposer.org/doc/00-intro.md) if it is not already available
3. From the project root run:

        composer install

   The command only builds the autoloader, so it succeeds even in offline or proxied environments.

4. Copy the provided example configuration if you need to customise the enabled generators:

        cp .env.example .env

5. Launch a development web server pointing at the `public` directory, for example:

        php -S 127.0.0.1:8000 -t public

6. Visit [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

## Configuration

Create a `.env` file alongside `.env.example` to configure the list of enabled generators. When the variable is omitted all generators remain available.

```
ENABLED_GENERATORS=triangles,squares,pixelart_v1,pixelart_v2
```

## Usage

Select a generator on the landing page to configure its parameters. Submitting the form will either stream the generated SVG to a new browser tab or, for upload-based generators, return a download link after the source image is processed. Saved SVG files can be plotted directly from Inkscape using the EggBot extensions (see <https://github.com/evil-mad/EggBot/releases/>).

## License

The Eggbot Pattern Generator is open-source software released under the [MIT license](http://opensource.org/licenses/MIT).
