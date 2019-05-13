<?php

namespace Datashaman\Lowdown\Commands;

use Dotenv\Dotenv;
use Exception;
use hanneskod\classtools\Iterator\ClassIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionFunction;
use Symfony\Component\Finder\Finder;

class BuildCommand extends Command
{
    /**
     * Collection of indexed gists
     *
     * @var Collection
     */
    protected $gists;

    /**
     * Namespace metadata
     *
     * @var array
     */
    protected $namespaces;

    /**
     * @var string
     */
    protected $signature = 'build
        {--gists           : Create and sync GitHub Gists for examples}
        {--gists-no-melody : Do not generate Melody links}
    ';

    /**
     * @var string
     */
    protected $description = 'Build documentation.';

    protected function addToNamespaces($entity)
    {
        $namespace = $entity['ns'];

        if (!isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = [];
        }

        $this->namespaces[$namespace][] = $entity;
    }

    protected function getExample($docBlock)
    {
        $description = (string) $docBlock->getDescription();

        if (preg_match('#<pre>\s*(.*)\s*</pre>#s', $description, $match)) {
            return str_replace('\\/', '/', $match[1]);
        }
    }

    protected function getExampleOutput(string $example)
    {
        $output = '';

        ob_start();

        try {
            eval($example);
        }

        catch (Exception $e) {
            $output .= (string) $e . "\n";
        }

        finally {
            $output = ob_get_contents() . $output;
            ob_end_clean();
        }

        return $output;
    }

    protected function getGist($function)
    {
        $code = <<<CODE
<?php
<<<CONFIG
packages:
- "datashaman/logic: dev-master"
CONFIG;
#
# This is a Melody script. http://melody.sensiolabs.org/
#

{$function['example']}
CODE;

        $description = $function['name'] . ' Example';

        $params = [
            'description' => $description,
            'files' => [
                $description => [
                    'content' => $code,
                ],
            ],
            'public' => true,
        ];

        if ($this->gists->contains($function['name'])) {
            $gist = $this->gists->get($function['name']);
            $contents = file_get_contents($gist['files'][$description]['raw_url']);

            if ($contents !== $code) {
                $gist = app('github')->api('gists')->update($gist['id'], $params);
            }
        } else {
            $gist = app('github')->api('gists')->create($params);
        }

        return $gist['html_url'];
    }

    protected function getDocBlock($function)
    {
        $factory = DocBlockFactory::createInstance();
        $docComment = $function->getDocComment();

        return $docComment
            ? $factory->create($docComment)
            : null;
    }

    protected function getParamTag($function, $name)
    {
        $docBlock = $this->getDocBlock($function);

        if ($docBlock) {
            $tags = $docBlock->getTagsByName('param');

            $tags = array_values(array_filter(
                $tags,
                function ($tag) use ($name) {
                    return $tag->getVariableName() === $name;
                }
            ));

            if ($tags) {
                return $tags[0];
            }
        }
    }

    protected function shouldGenerateClass($class)
    {
        $whitelist = trim(env('LOWDOWN_WHITELIST'));

        if (!$whitelist) {
            return true;
        }

        $whitelist = explode(',', $whitelist);

        $classNamespace = $class->getNamespaceName();

        foreach ($whitelist as $ns) {
            if ($classNamespace === $ns) {
                return true;
            }
        }

        return false;
    }

    protected function shouldGenerateFunction($name)
    {
        $whitelist = trim(env('LOWDOWN_WHITELIST'));

        if (!$whitelist) {
            return false;
        }

        $whitelist = explode(',', $whitelist);

        foreach ($whitelist as $ns) {
            $ns = str_replace('\\', '\\\\', $ns);

            if (preg_match("!^$ns!i", $name)) {
                return true;
            }
        }

        return false;
    }

    protected function transform($entity, $method, ...$args)
    {
        $namespace = $entity->getNamespaceName();

        $result = $this->$method($entity, ...$args);
        $result['ns'] = $namespace;

        return $result;
    }

    protected function transformClass($class)
    {
        $classDocBlock = $this->getDocBlock($class);

        $classType = 'class';

        if ($class->isInterface()) {
            $classType = 'interface';
        }

        if ($class->isTrait()) {
            $classType = 'trait';
        }

        $properties = [];

        foreach ($class->getProperties() as $property) {
            $result = [
                'name' => $property->getName(),
                'modifiers' => [
                    'private' => $property->isPrivate(),
                    'protected' => $property->isProtected(),
                    'public' => $property->isPublic(),
                    'static' => $property->isStatic(),
                ],
            ];

            $docBlock = $this->getDocBlock($property);

            if ($docBlock) {
                $tags = $docBlock->getTagsByName('var');

                if ($tags) {
                    $result['type'] = (string) $tags[0]->getType();
                }

                $result['summary'] = $docBlock->getSummary();
            }

            $properties[] = $result;
        }

        $methods = [];

        foreach ($class->getMethods() as $method) {
            $methods[] = $this->transformMethod($method);
        }

        $result = [
            '_type' => $classType,
            'endLine' => $class->getEndLine(),
            'filename' => $class->getFilename(),
            'interfaces' => array_map(
                function ($i) {
                    return $this->transform($i, 'transformClass', 1, 1);
                },
                array_values($class->getInterfaces())
            ),
            'methods' => $methods,
            'name' => $class->getName(),
            'properties' => $properties,
            'shortName' => $class->getShortName(),
            'startLine' => $class->getStartLine(),
            'traits' => array_map(
                function ($t) {
                    return $this->transform($t, 'transformClass', 1, 1);
                },
                $class->getTraits()
            ),
        ];

        $parentClass = $class->getParentClass();
        if ($parentClass) {
            $result['parentClassName'] = $parentClass->getName();
            $result['parentClassShortName'] = $parentClass->getShortName();

            if ($this->shouldGenerateClass($parentClass)) {
                $result['parentClass'] = $this->transform($parentClass, 'transformClass');
            }
        }

        if ($classDocBlock) {
            $result['summary'] = $classDocBlock->getSummary();
        }

        return $result;
    }

    protected function transformFunction($function)
    {
        $result = [
            '_type' => 'function',
            'endLine' => $function->getEndLine(),
            'filename' => $function->getFilename(),
            'name' => $function->getName(),
            'parameters' => array_map(
                function ($param) use ($function) {
                    return $this->transformParameter($param, $function);
                },
                $function->getParameters()
            ),
            'returnType' => (string) $function->getReturnType(),
            'shortName' => $function->getShortName(),
            'startLine' => $function->getStartLine(),
        ];

        $docBlock = $this->getDocBlock($function);

        if ($docBlock) {
            $result['summary'] = $docBlock->getSummary();

            $tags = $docBlock->getTagsByName('return');

            if ($tags) {
                $result['returnType'] = (string) $tags[0]->getType();
            }

            $example = $this->getExample($docBlock);

            if ($example) {
                $result['example'] = $example;
                $result['output'] = $this->getExampleOutput($result['example']);

                if ($this->option('gists')) {
                    $result['gist'] = $this->getGist($result);
                }
            }
        }

        return $result;
    }

    protected function transformMethod($method)
    {
        $result = [
            'name' => $method->getName(),
            'modifiers' => [
                'abstract' => $method->isAbstract(),
                'final' => $method->isFinal(),
                'generator' => $method->isGenerator(),
                'private' => $method->isPrivate(),
                'protected' => $method->isProtected(),
                'public' => $method->isPublic(),
                'static' => $method->isStatic(),
                'variadic' => $method->isVariadic(),
            ],
            'parameters' => array_map(
                function ($param) use ($method) {
                    return $this->transformParameter($param, $method);
                },
                $method->getParameters()
            ),
            'returnType' => (string) $method->getReturnType(),
        ];

        $docBlock = $this->getDocBlock($method);

        if ($docBlock) {
            $result['summary'] = $docBlock->getSummary();
            $result['description'] = (string) $docBlock->getDescription();

            $tags = $docBlock->getTagsByName('return');

            if ($tags) {
                $result['returnType'] = (string) $tags[0]->getType();
            }

            $example = $this->getExample($docBlock);

            if ($example) {
                $result['example'] = $example;
                $result['output'] = $this->getExampleOutput($result['example']);

                if ($this->option('gists')) {
                    $result['gist'] = $this->getGist($result);
                }
            }
        }

        return $result;
    }

    protected function transformParameter($param, $function)
    {
        $result = [
            'name' => $param->getName(),
            'position' => $param->getPosition(),
            'type' => (string) $param->getType(),
            'modifiers' => [
                'array' => $param->isArray(),
                'callable' => $param->isCallable(),
                'defaultValueAvailable' => $param->isDefaultValueAvailable(),
                'defaultValueConstant' => $param->isDefaultValueAvailable() && $param->isDefaultValueConstant(),
                'optional' => $param->isOptional(),
                'passedByReference' => $param->isPassedByReference(),
                'variadic' => $param->isVariadic(),
            ],
        ];

        $tag = $this->getParamTag($function, $param->getName());

        if ($tag) {
            $tagType = (string) $tag->getType();

            if ($tagType) {
                $result['type'] = $tagType;
            }

            $result['description'] = (string) $tag->getDescription();
        }

        $class = $param->getClass();

        if ($class) {
            $result['class'] = $class->getName();
        }

        if ($param->isDefaultValueAvailable()) {
            $result['defaultValue'] = $param->getDefaultValue();
        }

        if ($param->isDefaultValueAvailable() && $param->isDefaultValueConstant()) {
            $result['defaultValueConstantName'] = $param->getDefaultValueConstantName();
        }

        return $result;
    }

    protected function isLowdownGist($gistId)
    {
        $comments = app('github')
            ->api('gist')
            ->comments()
            ->all($gistId);

        if (!$comments) {
            return false;
        }

        return preg_match(
            '#^Generated by Datashaman Lowdown',
            $comments[0]['body']
        );
    }

    public function handle()
    {
        $cwd = getcwd();

        if (file_exists($cwd . DIRECTORY_SEPARATOR . '.env')) {
            $dotenv = Dotenv::create($cwd);
            $dotenv->load();
        }

        // TODO Add bootstrap config instead
        require_once $cwd . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        $this->namespaces = [];

        $finder = new Finder();

        $sources = env('LOWDOWN_SOURCES', 'app,src');
        $sources = explode(',', $sources);

        foreach ($sources as $dir) {
            if (file_exists($dir)) {
                $classes = new ClassIterator($finder->in($dir));

                foreach ($classes as $class) {
                    if ($this->shouldGenerateClass($class)) {
                        $this->addToNamespaces($this->transform($class, 'transformClass'));
                    }
                }
            }
        }

        $functions = get_defined_functions(true);

        foreach($functions['user'] as $name) {
            if ($this->shouldGenerateFunction($name)) {
                $function = new ReflectionFunction($name);
                $this->addToNamespaces($this->transform($function, 'transformFunction'));
            }
        }

        ksort($this->namespaces);

        foreach ($this->namespaces as $ns => &$entities) {
            ksort($entities);
        }

        $templateFolder = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'template';

        $buildFolder = tempnam(sys_get_temp_dir(), 'lowdown');
        unlink($buildFolder);

        File::copyDirectory($templateFolder, $buildFolder);
        file_put_contents($buildFolder . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'namespaces.json', json_encode($this->namespaces, JSON_PRETTY_PRINT));

        $result = `cd $buildFolder; yarn; node_modules/.bin/webpack --mode=production`;

        $this->line($result);

        File::copyDirectory($buildFolder . DIRECTORY_SEPARATOR . 'build', env('LOWDOWN_DEST', 'docs/api'));
        File::deleteDirectory($buildFolder);
    }
}
