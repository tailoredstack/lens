<?php

declare(strict_types=1);

namespace Lens\Infer;

use Lens\Types\IntersectionType;
use Lens\Types\MixedType;
use Lens\Types\NamedObjectType;
use Lens\Types\Nullable;
use Lens\Types\PropertyType;
use Lens\Types\ScalarType;
use Lens\Types\UnionType;
use PhpParser\Node;
use PhpParser\Node\IntersectionType as PhpIntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Node\UnionType as PhpUnionType;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

final class Engine
{
    /** @var array<string> */
    private array $sources;

    /** @var array */
    private array $types = [];

    /** @var array */
    private array $operations = [];

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

        $parser = new ParserFactory()->createForNewestSupportedVersion();

        $collected = [];
        $operations = [];

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
                $traverser->addVisitor(new class($collected, $operations) extends NodeVisitorAbstract {
                    private array $collected;
                    private array $operations;
                    private ?string $currentNamespace = null;
                    /** @var array<string,string> */
                    private array $uses = [];

                    public function __construct(array &$collected, array &$operations)
                    {
                        $this->collected = &$collected;
                        $this->operations = &$operations;
                    }

                    public function enterNode(Node $node)
                    {
                        if ($node instanceof Namespace_) {
                            $this->currentNamespace = $node->name?->toString();
                            $this->uses = [];
                            return null;
                        }

                        if ($node instanceof Use_) {
                            foreach ($node->uses as $u) {
                                $alias = $u->alias?->toString() ?? $u->name->getLast();
                                $this->uses[$alias] = $u->name->toString();
                            }
                            return null;
                        }

                        if (! $node instanceof Class_) {
                            return null;
                        }

                        $ns = $this->currentNamespace;

                        $className = $node->name?->toString();
                        if ($className === null) {
                            return null;
                        }

                        $fqcn = $ns ? $ns . '\\' . $className : $className;

                        $props = [];

                        // gather typed class properties
                        foreach ($node->getProperties() as $prop) {
                            foreach ($prop->props as $p) {
                                $typeNode = $prop->type;
                                $type = $this->mapTypeNode($typeNode);

                                // if name type resolved to NamedObjectType, resolve imports/namespace
                                if ($type instanceof NamedObjectType && ! str_contains($type->className, '\\')) {
                                    $resolved = $this->resolveName($type->className);
                                    $type = new NamedObjectType($resolved);
                                }

                                // if no explicit type, try docblock @var
                                if ($prop->type === null) {
                                    $doc = $prop->getDocComment()?->getText();
                                    if ($doc !== null) {
                                        $docType = $this->mapDocVar($doc);
                                        if ($docType !== null) {
                                            $type = $docType;
                                        }
                                    }
                                }

                                $props[] = new PropertyType($p->name->toString(), $type);
                            }
                        }

                        // gather constructor promoted properties
                        foreach ($node->stmts as $stmt) {
                            if (! $stmt instanceof ClassMethod) {
                                continue;
                            }

                            if ($stmt->name->toString() !== '__construct') {
                                continue;
                            }

                            foreach ($stmt->params as $param) {
                                if (! $param instanceof Param) {
                                    continue;
                                }

                                // promoted if flags set (public/protected/private)
                                if ($param->flags === 0) {
                                    continue;
                                }

                                $vname = $param->var->name;
                                $type = $this->mapTypeNode($param->type);

                                if ($type instanceof NamedObjectType && ! str_contains($type->className, '\\')) {
                                    $resolved = $this->resolveName($type->className);
                                    $type = new NamedObjectType($resolved);
                                }

                                $props[] = new PropertyType(is_string($vname) ? $vname : (string) $vname, $type);
                            }
                        }

                        // gather public methods as operations
                        $methods = [];
                        foreach ($node->stmts as $stmt) {
                            if (! $stmt instanceof ClassMethod) {
                                continue;
                            }

                            // skip constructor and non-public
                            if ($stmt->name->toString() === '__construct') {
                                continue;
                            }

                            if ($stmt->isPrivate() || $stmt->isProtected()) {
                                continue;
                            }

                            $methodName = $stmt->name->toString();
                            $returnType = $this->mapTypeNode($stmt->getReturnType());

                            // attributes -> http verb + path
                            $http = null;
                            $path = null;
                            $throws = [];
                            $meta = [];
                            foreach ($stmt->attrGroups as $ag) {
                                foreach ($ag->attrs as $attr) {
                                    $an = $attr->name->toString();
                                    $lname = strtolower($an);

                                    // Tempest attributes: #[Get], #[Post], #[Put], #[Patch], #[Delete]
                                    if (in_array($lname, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                                        $http = $lname;
                                        $args = $attr->args;
                                        if (isset($args[0]) && $args[0]->value instanceof Node\Scalar\String_) {
                                            $path = $args[0]->value->value;
                                        }
                                    }

                                    // Generic #[Route] attribute
                                    if ($lname === 'route') {
                                        $args = $attr->args;
                                        if (isset($args[0]) && $args[0]->value instanceof Node\Scalar\String_) {
                                            $path = $args[0]->value->value;
                                        }
                                        if (isset($args[1]) && $args[1]->value instanceof Node\Scalar\String_) {
                                            $http = strtolower($args[0]->value->value);
                                            $path = $args[1]->value->value;
                                        }
                                    }

                                    // #[Throws] attribute for exception mapping
                                    if ($lname === 'throws') {
                                        foreach ($attr->args as $arg) {
                                            if ($arg->value instanceof Node\Name) {
                                                $throws[] = $this->resolveName($arg->value->toString());
                                            }
                                            // Handle NotFoundException::class syntax
                                            if ($arg->value instanceof Node\Expr\ClassConstFetch) {
                                                $throws[] = $this->resolveName($arg->value->class->toString());
                                            }
                                        }
                                    }

                                    // #[Auth] attribute for security
                                    if ($lname === 'auth') {
                                        $guard = 'default';
                                        if (isset($attr->args[0]) && $attr->args[0]->value instanceof Node\Scalar\String_) {
                                            $guard = $attr->args[0]->value->value;
                                        }
                                        $meta['auth'] = $guard;
                                    }

                                    // #[AllowGuest] attribute for public access
                                    if ($lname === 'allowguest') {
                                        $meta['allowGuest'] = true;
                                    }

                                    // #[Can] attribute for permissions
                                    if ($lname === 'can') {
                                        if (!isset($meta['permissions'])) {
                                            $meta['permissions'] = [];
                                        }
                                        if (isset($attr->args[0]) && $attr->args[0]->value instanceof Node\Scalar\String_) {
                                            $meta['permissions'][] = $attr->args[0]->value->value;
                                        }
                                    }
                                }
                            }

                            // docblock @route: "@route GET /foo"
                            if ($path === null && $stmt->getDocComment() !== null) {
                                $doc = $stmt->getDocComment()->getText();
                                if (preg_match('/@route\s+([A-Z]+)\s+([^\s]+)/', $doc, $m)) {
                                    $http = strtolower($m[1]);
                                    $path = $m[2];
                                } elseif (preg_match('/@route\s+([^\s]+)/', $doc, $m)) {
                                    $path = $m[1];
                                }
                            }

                            $params = [];
                            foreach ($stmt->params as $p) {
                                $pname = is_string($p->var->name) ? $p->var->name : (string) $p->var->name;
                                $ptype = $this->mapTypeNode($p->type);
                                
                                // Resolve named object types
                                if ($ptype instanceof \Lens\Types\NamedObjectType && ! str_contains($ptype->className, '\\')) {
                                    $resolved = $this->resolveName($ptype->className);
                                    $ptype = new \Lens\Types\NamedObjectType($resolved);
                                }
                                
                                $params[] = [
                                    'name' => $pname,
                                    'type' => $ptype,
                                ];
                            }

                            $methods[$methodName] = [
                                'return' => $returnType,
                                'params' => $params,
                                'http' => $http,
                                'path' => $path,
                                'throws' => $throws !== [] ? $throws : null,
                            ] + $meta;
                        }

                        $this->collected[$fqcn] = new NamedObjectType($fqcn, ...$props);
                        if ($methods !== []) {
                            $this->operations[$fqcn] = $methods;
                        }

                        return null;
                    }

                    private function resolveName(string $name): string
                    {
                        // fully-qualified already
                        if (str_starts_with($name, '\\')) {
                            return ltrim($name, '\\');
                        }

                        // imported alias
                        if (isset($this->uses[$name])) {
                            return $this->uses[$name];
                        }

                        // relative to current namespace
                        if ($this->currentNamespace) {
                            return $this->currentNamespace . '\\' . $name;
                        }

                        return $name;
                    }

                    private function mapDocVar(string $doc)
                    {
                        if (! preg_match('/@var\s+([^\s\|]+)/', $doc, $m)) {
                            return null;
                        }

                        $typeStr = $m[1];

                        // union in docblock
                        if (str_contains($typeStr, '|')) {
                            $parts = explode('|', $typeStr);
                            $mapped = array_map(fn ($p) => $this->mapSimpleStringType(trim($p)), $parts);
                            return new UnionType(...$mapped);
                        }

                        return $this->mapSimpleStringType($typeStr);
                    }

                    private function mapSimpleStringType(string $s)
                    {
                        // array like string[]
                        if (str_ends_with($s, '[]')) {
                            $inner = substr($s, 0, -2);
                            $innerType = $this->mapSimpleStringType($inner);
                            return new \Lens\Types\ArrayType($innerType, true);
                        }

                        // generic-like array<string>
                        if (preg_match('/^(\w+)\<(.+)\>$/', $s, $m)) {
                            $base = $m[1];
                            $inner = $m[2];
                            if (strtolower($base) === 'array' || strtolower($base) === 'list') {
                                $innerType = $this->mapSimpleStringType($inner);
                                return new \Lens\Types\ArrayType($innerType, true);
                            }
                        }

                        // primitives
                        if (in_array($s, ['int', 'integer', 'float', 'string', 'bool', 'boolean'], true)) {
                            $map = ['integer' => 'int', 'boolean' => 'bool'];
                            $s = $map[$s] ?? $s;
                            return new ScalarType($s === 'integer' ? 'int' : ($s === 'boolean' ? 'bool' : $s));
                        }

                        if (strtolower($s) === 'mixed' || strtolower($s) === 'array' || strtolower($s) === 'object') {
                            return new MixedType();
                        }

                        // assume class name
                        $resolved = $this->resolveName($s);
                        return new NamedObjectType($resolved);
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
                            if (in_array($name, ['string', 'int', 'float', 'bool'], true)) {
                                return new ScalarType($name);
                            }
                            // array type
                            if ($name === 'array') {
                                return new \Lens\Types\ArrayType(new MixedType(), true);
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
        // store operations found
        $this->operations = $operations;
    }

    /** @return array */
    public function getOperations(): array
    {
        return $this->operations;
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
