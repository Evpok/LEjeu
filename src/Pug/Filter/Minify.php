<?php

namespace Pug\Filter;

use Jade\Compiler;
use Jade\Nodes\Filter;
use Pug\Pug;

class Minify extends AbstractFilter
{
    /**
     * @var bool
     */
    protected $dev;

    /**
     * @var string
     */
    protected $assetDirectory;

    /**
     * @var string
     */
    protected $outputDirectory;

    /**
     * @var string
     */
    protected $appDirectory;

    /**
     * @var array
     */
    protected $js;

    /**
     * @var array
     */
    protected $css;

    protected function path()
    {
        return implode(DIRECTORY_SEPARATOR, func_get_args());
    }

    protected function prepareDirectory($path)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $path;
    }

    protected function isRootPath($path)
    {
        return false === strpos(trim($path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }

    protected function command($input)
    {
        $directory = getcwd();
        chdir($this->appDirectory);
        $output = shell_exec($input);
        chdir($directory);
        if (preg_match('/error|exception/i', $output)) {
            throw new \ErrorException("Command failure\n$input\n$output", 1);
        }
        //echo '<pre>I: ' . $input . '<br>O: '. $output . '</pre>';

        return $output;
    }

    protected function commandAndHandleError($input)
    {
        $logFile = sys_get_temp_dir() . '/error.log';
        $output = $this->command($input . ' > ' . escapeshellarg($logFile));
        if (file_exists($logFile)) {
            $error = file_get_contents($logFile);
            unlink($logFile);
            if (!empty($error)) {
                throw new \ErrorException("Command: $input\nOutput: $error", 2);
                
            }
        }

        return $output;
    }

    protected function nodeModule($cmd)
    {
        return escapeshellarg($this->path($this->appDirectory, 'node_modules', '.bin', $cmd) . '.cmd');
    }

    protected function parsePugInJs($code, $indent = '')
    {
        $code = preg_replace('/(^' . preg_quote($indent) . '|(?<=\n)' . preg_quote($indent) . ')/', '', $code);

        $pug = new Pug(array(
            'singleQuote' => false,
            'prettyprint' => $this->dev,
        ));

        return $pug->render($code);
    }

    protected function parsePugInJsx($parameters)
    {
        return $this->parsePugInJs(str_replace('``', '`', $parameters[2]), $parameters[1]);
    }

    protected function parsePugInCoffee($parameters)
    {
        return $this->parsePugInJs($parameters[2], $parameters[1]);
    }

    protected function parseScript($parameters)
    {
        list($script, $attributes) = $parameters;

        if (preg_match('/src\s*=\s*(([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)/', $attributes, $match)) {
            $path = stripslashes(substr($match[1], 1, -1));
            $source = $this->path($this->assetDirectory, $path);
            switch (pathinfo($path, PATHINFO_EXTENSION)) {
                case 'jsxp':
                    $path = substr($path, 0, -2);
                    $destination = $this->prepareDirectory($this->path($this->outputDirectory, $path));
                    $contents = preg_replace_callback('/(?<!\s)(\s+)::`(([^`]+|(?<!`)`(?!`))*?)`(?!`)/', array($this, 'parsePugInJsx'), file_get_contents($source));
                    file_put_contents($destination, $contents);
                    $outFile = escapeshellarg($destination);
                    $this->commandAndHandleError($this->nodeModule('babel') . ' --plugins transform-react-jsx ' . $outFile . '  --out-file ' . $outFile . ' --source-maps --print');
                    break;
                case 'jsx':
                    $path = substr($path, 0, -1);
                    $this->commandAndHandleError($this->nodeModule('babel') . ' --plugins transform-react-jsx ' . escapeshellarg($source) . '  --out-file ' . escapeshellarg($this->prepareDirectory($this->path($this->outputDirectory, $path))) . ' --source-maps --print');
                    break;
                case 'cofp':
                    $path = substr($path, 0, -4) . 'js';
                    $destination = $this->prepareDirectory($this->path($this->outputDirectory, $path));
                    $contents = preg_replace_callback('/(?<!\s)(\s+)::"""(.*?)"""/', array($this, 'parsePugInCoffee'), file_get_contents($source));
                    file_put_contents($destination, $contents);
                    $outFile = escapeshellarg($destination);
                    $this->commandAndHandleError($this->nodeModule('coffee') . ' ' . $outFile . '  --out-file ' . $outFile . ' --source-maps --print');
                    break;
                case 'coffee':
                    $path = substr($path, 0, -6) . 'js';
                    $this->commandAndHandleError($this->nodeModule('coffee') . ' ' . escapeshellarg($source) . '  --out-file ' . escapeshellarg($this->prepareDirectory($this->path($this->outputDirectory, $path))) . ' --source-maps --print');
                    break;
                default:
                    copy($source, $this->prepareDirectory($this->path($this->outputDirectory, $path)));
            }
            if ($this->dev) {
                return '<script src="' . $path . '?' . time() . '"></script>' . "\n";
            }
            $js[] = $path;

            return '';
        }

        return $script;
    }

    protected function parseStyle($parameters)
    {
        list($style, $attributes) = $parameters;

        if (
            preg_match('/rel\s*=\s*[\'"].*stylesheet/i', $attributes) &&
            preg_match('/href\s*=\s*(([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)/i', $attributes, $match)
        ) {
            $path = stripslashes(substr($match[1], 1, -1));
            $source = $this->path($this->assetDirectory, $path);
            switch (pathinfo($path, PATHINFO_EXTENSION )) {
                case 'styl':
                    $path = substr($path, 0, -5) . '.css';
                    $this->command($this->nodeModule('stylus') . ' < ' . escapeshellarg($source) . ' > ' . escapeshellarg($this->prepareDirectory($this->path($this->outputDirectory, $path))));
                    break;
                default:
                    copy($source, $this->prepareDirectory($this->path($this->outputDirectory, $path)));
            }
            if ($this->dev) {
                return '<link rel="stylesheet" href="' . $path . '?' . time() . '">' . "\n";
            }
            $css[] = $path;

            return '';
        }

        return $style;
    }

    protected function uglify($input, $output)
    {
        $input = implode(' ', $input);

        return $this->command(
            (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                ? 'type ' . $input . ' > '
                : 'cat ' . $input . ' | '
            ) .
            './node_modules/.bin/uglifyjs' .
            ' -o ' .escapeshellarg($output)
        );
    }

    protected function parsePugCode(Filter $node, Compiler $compiler)
    {
        $nodes = $node->block->nodes;
        $indent = strlen($nodes[0]->value) - strlen(ltrim($nodes[0]->value));
        $code = '';
        foreach ($nodes as $line) {
            $code .= substr($compiler->interpolate($line->value), $indent) . "\n";
        }

        $pug = new Pug(array(
            'singleQuote' => false,
            'prettyprint' => $this->dev,
        ));

        return $pug->render($code);
    }

    protected function getOption(Compiler $compiler, $option, $defaultValue = null)
    {
        try {
            return $compiler->getOption($option);
        } catch (\InvalidArgumentException $e) {
            return $defaultValue;
        }
    }

    public function __invoke(Filter $node, Compiler $compiler)
    {
        if (is_null($this->dev)) {
            $this->dev = $this->getOption($compiler, 'environnement') === 'dev';
        }
        if (is_null($this->assetDirectory)) {
            $this->assetDirectory = $this->getOption($compiler, 'assetDirectory', $this->appDirectory);
        }
        if (is_null($this->outputDirectory)) {
            $this->outputDirectory = $this->getOption($compiler, 'outputDirectory', $this->appDirectory);
        }
        $this->appDirectory = __DIR__;
        while (true) {
            $this->appDirectory = dirname($this->appDirectory);
            if ($this->isRootPath($this->appDirectory)) {
                throw new \ErrorException('It seems node.js is not installed, please install it then execute npm install in the project root directory.', 2);
            }

            if (is_dir($this->path($this->appDirectory, 'node_modules'))) {
                break;
            }
        }
        $html = $this->parsePugCode($node, $compiler);

        $this->js = array();
        $this->css = array();

        $html = preg_replace_callback(
            '/<script((?:[^\'">]*|([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)*)>\s*<\/script>/i',
            array($this, 'parseScript'),
            $html
        );

        $html = preg_replace_callback(
            '/<link((?:[^\'">]*|([\'"]).*?(?<!\\\\)(?:\\\\\\\\)*\2)*)>/i',
            array($this, 'parseStyle'),
            $html
        );

        if (count($this->js) || count($this->css)) {
            if (count($this->js)) {
                $this->uglify($this->js, $this->path($this->outputDirectory, 'app.min.js'));
                $html .= '<script src="js/app.min.js"></script>';
            }

            if (count($this->css)) {
                $this->uglify($this->css, $this->path($this->outputDirectory, 'app.min.css'));
                $html .= '<link rel="stylesheet" href="css/app.min.css">';
            }
        }

        return $html;
    }
}
