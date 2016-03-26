## tml's Eggbot Pattern Generator

This Eggbot Pattern Generator is a website that can be used to generate various patterns to be printed using an [EggBot](http://egg-bot.com).

At the moment it is able to generate 4 different patterns:

* A colorful pattern made up by randomized 4 cornered shapes
* A colorful pattern made up by randomized triangles
* 2 generators which take low res bitmap files and generate large, oldschool pixely looking SVG based on them that can be plotted right away.

The website itself is a web application running on the Laravel 5.0 framework.

## Installation

The web application is based on the Laravel 5.0 framework. The installation requirements of the pattern generator are
the same as those of the framework itself. You can find the requirements and a Laravel installation guide at the
[Laravel website](https://laravel.com/docs/5.0).

For those who are not interested in deep understanding of Laravel and just want to get this website running, here is the
short guide:

1. Check out this git project to your local machine
2. Install composer as described on [getcomposer.org](https://getcomposer.org/doc/00-intro.md)
3. Open a terminal
4. Change to the root directory of the project
5. Issue the following command: 

        composer install

6. Start the built-in webserver

        php artisan serve

7. Start a browser and go to http://localhost:8000

## Usage

The pattern generator supports 4 patterns at the moment. 

* A square pattern generator
* A triangle pattern generator
* A pixel art generator which is exact but results in slow plotting
* Another pixel art generator which is less exact but is highly optimized for faster printing speed. 

Each of the generators have a lot of options to configure. If you start the website and choose a generator engine, you will see the various parameters you can change around. Changing the parameters changes thing like randomness, colors line widths, infill algorithms, etc. 

When you are happy with the settings, hit the "Generate" button. It will open a new browser tab with the generated pattern, which is an SVG file. Save the file using the browser's save file function. 
Open it in InkScape and send it to the EggBot using the Eggbot Inkscape Extensions (see <https://github.com/evil-mad/EggBot/releases/>)

You can find more information about how to use your Eggbot, please see the [official Eggbot documentation](http://wiki.evilmadscientist.com/The_Original_Egg-Bot_Kit#Tutorials)

## Configuration

The configuration of the website can be done by creating a file called .env in the root of the project. A .env.example file has been supplied with the project to serve as example configuration. 

There is a single configuration setting implemented atm: 

###ENABLED_GENERATORS
This setting can be used to limit the generators available on the website. It takes a comma-separated list of generator names. When set, only the generators listed here will be pickable from the generator select box. 

Example:

    ENABLED_GENERATORS = triangles, squares, pixelart_v1, pixelart_v2


## Official Documentation

Documentation for the Laravel framework can be found on the [Laravel website](http://laravel.com/docs).

### License

The Eggbot Pattern Generator and the Laravel Framework are  open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
