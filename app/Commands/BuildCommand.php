<?php

namespace App\Commands;

use hanneskod\classtools\Iterator\ClassIterator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use phpDocumentor\Reflection\DocBlockFactory;
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
     * @var string
     */
    protected $signature = 'build
        {--dest=docs/api   : Write output to this folder}
        {--gists           : Create and sync GitHub Gists for examples}
        {--gists-no-melody : Do not generate Melody links}
        {--whitelist=*     : Namespace whitelist}
    ';

    /**
     * @var string
     */
    protected $description = 'Build documentation.';

    protected function addToNamespaces($entity)
    {
        $namespace = $entity['ns'];

        if (!isset($namespaces[$namespace])) {
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

        if ($this->gists->hasKey($function['name'])) {
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

    protected function shouldGenerateDocs($name)
    {
        $whitelist = explode(',', config('lowdown.whitelist'));

        if (!$whitelist) {
            return true;
        }

        foreach ($whitelist as $ns) {
            $ns = str_replace('\\', '\\\\', $ns);

            if (preg_match("#^{$ns}#i", $name)) {
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

            if ($this->shouldGenerateDocs($parentClass->getName())) {
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
        global $cwd, $options;

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

                if (isset($options['gists'])) {
                    $result['gist'] = $this->getGist($result);
                }
            }
        }

        return $result;
    }

    function transformMethod($method)
    {
        global $options;

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

                if (isset($options['gists'])) {
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
        $this->namespaces = [];

        $finder = new Finder();

        $sources = explode(',', config('lowdown.sources'));

        foreach ($sources as $dir) {
            $classes = new ClassIterator($finder->in($dir));

            foreach ($classes as $class) {
                if ($this->shouldGenerateDocs($class->getName())) {
                    $this->addToNamespaces($this->transform($class, 'transformClass'));
                }
            }
        }

        $srcFolder = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'src';

        $buildFolder = tempnam(sys_get_temp_dir(), 'lowdown');
        unlink($buildFolder);

        File::copyDirectory($srcFolder, $buildFolder);
        file_put_contents($buildFolder . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'namespaces.json', json_encode($this->namespaces, JSON_PRETTY_PRINT));

        $result = `cd $buildFolder; yarn; node_modules/.bin/webpack --mode=production`;

        $this->line($result);

        File::copyDirectory($buildFolder . DIRECTORY_SEPARATOR . 'build', config('lowdown.dest'));
        File::deleteDirectory($buildFolder);
    }
}
