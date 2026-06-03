<?php

declare(strict_types=1);

namespace Lens\Infer;

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType as PhpUnionType;
use PhpParser\Node\IntersectionType as PhpIntersectionType;

use Lens\Types\NamedObjectType;
use Lens\Types\PropertyType;
use Lens\Types\ScalarType;
use Lens\Types\Nullable;
use Lens\Types\UnionType;
use Lens\Types\IntersectionType;
use Lens\Types\MixedType;

final class Engine
{
    /** @var array<string> */
    private array $sources;

    /** @var array */
    private array $types = [];

    /**
     * @param array<string> $sources
     */
    public function __construct(array $sources = [])
    {
        $this->sources = $sources;
    }

    public function analyze(): void
    {
        $paths = $this->sources === [] ? [getcwd() . '/src'] : $this->sources;

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $collected = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($it as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $code = file_get_contents((string) $file);
                if ($code === false) {
                    continue;
                }

                try {
                    $ast = $parser->parse($code);
                } catch (\Throwable $e) {
                    // skip unparsable file
                    continue;
                }

                $traverser = new NodeTraverser();
                $traverser->addVisitor(new class($collected) extends NodeVisitorAbstract {
                    private array &$collected;

                    public function __construct(array &$collected)
                    {
                        $this->collected = &$collected;
                    }

                    public function enterNode(Node $node)
                    {
                        if ($node instanceof Namespace_) {
                            // let class visitor compute fqcn via namespace + class name
                        }

                        if (! $node instanceof Class_) {
                            return null;
                        }

                        $ns = null;
                        $parent = $node->getAttribute('parent');
                        $cur = $node;
                        while ($parent = $cur->getAttribute('parent')) {
                            if ($parent instanceof Namespace_) {
                                $ns = $parent->name?->toString();
                                break;
                            }
                            $cur = $parent;
                        }

                        $className = $node->name?->toString();
                        if ($className === null) {
                            return null;
                        }

                        $fqcn = $ns ? $ns . '\\' . $className : $className;

                        $props = [];

                        foreach ($node->getProperties() as $prop) {
                            foreach ($prop->props as $p) {
                                $typeNode = $prop->type;
                                $type = $this->mapTypeNode($typeNode);
                                $props[] = new PropertyType($p->name->toString(), $type);
                            }
                        }

                        $this->collected[$fqcn] = new NamedObjectType($fqcn, ...$props);

                        return null;
                    }

                    private function mapTypeNode(?Node $node)
                    {
                        if ($node === null) {
                            return new MixedType();
                        }

                        if ($node instanceof NullableType) {
                            return new Nullable($this->mapTypeNode($node->type));
                        }

                        if ($node instanceof PhpUnionType) {
                            $types = array_map(fn ($t) => $this->mapTypeNode($t), $node->types);
                            return new UnionType(...$types);
                        }

                        if ($node instanceof PhpIntersectionType) {
                            $types = array_map(fn ($t) => $this->mapTypeNode($t), $node->types);
                            return new IntersectionType(...$types);
                        }

                        if ($node instanceof Node\Identifier) {
                            $name = $node->toString();
                            if (in_array($name, ['string','int','float','bool'], true)) {
                                return new ScalarType($name);
                            }
                            // fallback
                            return new MixedType();
                        }

                        if ($node instanceof Node\Name) {
                            $class = $node->toString();
                            return new NamedObjectType($class);
                        }

                        return new MixedType();
                    }
                });

                // set parent attributes for namespace discovery
                $this->setParentAttributes($ast);

                $traverser->traverse($ast);
            }
        }

        $this->types = $collected;
    }

    private function setParentAttributes(array &$ast): void
    {
        $stack = [];

        $visitor = function (&$node, $parent = null) use (&$visitor, &$stack) {
            if (! $node instanceof Node) {
                return;
            }

            $node->setAttribute('parent', $parent);

            foreach ($node->getSubNodeNames() as $name) {
                $sub = $node->$name;
                if (is_array($sub)) {
                    foreach ($sub as $s) {
                        $visitor($s, $node);
                    }
                } elseif ($sub instanceof Node) {
                    $visitor($sub, $node);
                }
            }
        };

        foreach ($ast as $node) {
            $visitor($node, null);
        }
    }

    /** @return array */
    public function getTypes(): array
    {
        return $this->types;
    }
}
