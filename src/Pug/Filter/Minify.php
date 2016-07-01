<?php

namespace Pug\Filter;

use Jade\Compiler;
use Jade\Nodes\Filter;
use Pug\Pug;

class Minify extends AbstractFilter
{
    protected static $dev;
    protected static $assetDirectory;
    protected static $outputDirectory;

    /**
     * Set global dev flag to on.
     */
    public static function devMode()
    {
        static::$dev = true;
    }

    /**
     * Set global dev flag to off.
     */
    public static function prodMode()
    {
        static::$dev = false;
    }

    /**
     * Set global dev flag to off.
     */
    public static function setAssetDirectory($assetDirectory)
    {
        static::$assetDirectory = $assetDirectory;
    }

    /**
     * Set global dev flag to off.
     */
    public static function setOutputDirectory($outputDirectory)
    {
        static::$outputDirectory = $outputDirectory;
    }

    public function __invoke(Filter $node, Compiler $compiler)
    {
        $nodes = $node->block->nodes;
        $indent = strlen($nodes[0]->value) - strlen(ltrim($nodes[0]->value));
        $code = '';
        foreach ($nodes as $line) {
            $code .= substr($compiler->interpolate($line->value), $indent) . "\n";
        }

        $pug = new Pug(array(
            'singleQuote' => false,
        ));

        $html = $pug->render($code);

        $dev = static::$dev;
        $assetDirectory = static::$assetDirectory;
        $outputDirectory = static::$outputDirectory;

        $prepareDirectory = function ($path) {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            return $path;
        };

        $command = function ($input) {
            $output = shell_exec($input);
            if (preg_match('/error|exception/i', $output)) {
                throw new \ErrorException("Command failure\n$input\n$output", 3);
            }

            return $output;
        };

        $js = array();
        $css = array();

        $html = preg_replace_callback(
            '/<script((?:[^\'">]*|([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)*)>\s*<\/script>/i',
            function ($parameters) use (&$js, $dev, $assetDirectory, $outputDirectory, $prepareDirectory, $command) {
                if (preg_match('/src\s*=\s*(([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)/', $parameters[1], $match)) {
                    $path = stripslashes(substr($match[1], 1, -1));
                    if ($dev) {
                        $source = $assetDirectory . DIRECTORY_SEPARATOR . $path;
                        switch (pathinfo($path, PATHINFO_EXTENSION )) {
                            case 'jsx':
                                if (version_compare(shell_exec('babel --version'), '6.0') < 0) {
                                    throw new \ErrorException('You need to install or update babel, please install the last version of node, then execute npm install babel-cli -g', 2);
                                }
                                $path = substr($path, 0, -1);
                                $command('babel ' . escapeshellarg($source) . '  --out-file ' . escapeshellarg($prepareDirectory($outputDirectory . DIRECTORY_SEPARATOR . $path)));
                                break;
                            default:
                                copy($source, $prepareDirectory($outputDirectory . DIRECTORY_SEPARATOR . $path));
                        }

                        return '<script src="' . $path . '?' . time() . '"></script>';
                    }
                    $js[] = $path;

                    return '';
                }

                return $parameters[0];
            },
            $html
        );

        $html = preg_replace_callback(
            '/<link((?:[^\'">]*|([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)*)>/i',
            function ($parameters) use (&$css, $dev, $assetDirectory, $outputDirectory, $prepareDirectory, $command) {
                if (
                    preg_match('/rel\s*=\s*[\'"].*stylesheet/i', $parameters[1]) &&
                    preg_match('/href\s*=\s*(([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)/i', $parameters[1], $match)
                ) {
                    $path = stripslashes(substr($match[1], 1, -1));
                    if ($dev) {
                        $source = $assetDirectory . DIRECTORY_SEPARATOR . $path;
                        switch (pathinfo($path, PATHINFO_EXTENSION )) {
                            case 'styl':
                                if (version_compare(shell_exec('stylus --version'), '0.1') < 0) {
                                    throw new \ErrorException('You need to install or update stylus, please install the last version of node, then execute npm install stylus -g', 2);
                                }
                                $path = substr($path, 0, -5) . '.css';
                                $command('stylus < ' . escapeshellarg($source) . ' > ' . escapeshellarg($prepareDirectory($outputDirectory . DIRECTORY_SEPARATOR . $path)));
                                break;
                            default:
                                copy($source, $prepareDirectory($outputDirectory . DIRECTORY_SEPARATOR . $path));
                        }

                        return '<link rel="stylesheet" href="' . $path . '?' . time() . '">';
                    }
                    $css[] = $path;

                    return '';
                }

                return $parameters[0];
            },
            $html
        );

        if (count($js) || count($css)) {
            $version = preg_split('/\s+/', shell_exec('uglifyjs --version'));
            if ($version[0] !== 'uglify-js' || version_compare($version[1], '2.0.0') < 0) {
                throw new \ErrorException('You need to install or update uglifyjs, please install the last version of node, then execute npm install uglify-js -g', 1);
            }

            if (count($js)) {
                $command((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                    ? 'type ' . implode(' ', $js) . ' > '
                    : 'cat ' . implode(' ', $js) . ' | '
                ) . 'uglifyjs -o ' . escapeshellarg($outputDirectory . DIRECTORY_SEPARATOR . 'app.min.js'));
                $html .= '<script src="js/app.min.js"></script>';
            }

            if (count($css)) {
                $command((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                    ? 'type ' . implode(' ', $css) . ' > '
                    : 'cat ' . implode(' ', $css) . ' | '
                ) . 'uglifyjs -o ' . escapeshellarg($outputDirectory . DIRECTORY_SEPARATOR . 'app.min.css'));
                $html .= '<link rel="stylesheet" href="css/app.min.css">';
            }
        }

        return $html;
    }
}
