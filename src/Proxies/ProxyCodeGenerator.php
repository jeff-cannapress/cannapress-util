<?php

//declare(strict_types=1);

namespace CannaPress\Util\Proxies;

use CannaPress\Util\UUID;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

class ProxyCodeGenerator
{
    private ReflectionClass $class;
    private array $methods;
    public string $proxy_name;
    public string $proxy_full_name;
    public string $output_namespace;
    public function __construct(string $class, private bool $hasTarget)
    {
        $this->class = new ReflectionClass($class);
        $this->methods = array_filter($this->class->getMethods(), fn (ReflectionMethod $m) => (($m->isPublic() || $m->isProtected()) && !$m->isFinal() && !$m->isConstructor()));
        $this->proxy_name = 'P' . ($this->hasTarget ? 'T' : 'U') . (UUID::create());
        $this->output_namespace = 'NS' . (UUID::create());
        $this->proxy_full_name = '\\' . $this->output_namespace . '\\' . $this->proxy_name;
    }
    public function generate(): string
    {
        $result = [];
        $result[] = '<?php';
        $result[] = '//declare(strict_types=1);';
        $result[] = 'namespace ' . $this->output_namespace . ';';
        $extension = $this->class->isInterface() ? 'implements' : 'extends';
        $result[] =  'final class ' . ($this->proxy_name) . ' ' . $extension . ' \\' . ($this->class->getName());
        $result[] = '{';

        $result = array_merge($result, self::indent($this->create_constructor()));

        foreach ($this->methods as
            /** @var ReflectionMethod*/
            $method) {
            $result = array_merge($result, self::indent($this->wrap_method($method)));
        }
        $result[] = '}';


        return implode("\n", $result);
    }
    private function get_named_type_declaration(ReflectionNamedType|null $type)
    {
        $name = '';
        if (!$type->isBuiltin()) {
            $name = '\\';
        }
        $name .= $type->getName();
        if ($name !== 'null') {
            if ($type->allowsNull()) {
                $name = '?' . $name;
            }
        }
        return $name;
    }
    public function get_type_declaration(ReflectionType|null $type,): string|null
    {
        if (is_null($type)) return '';
        if ($type instanceof ReflectionUnionType) {
            $parts = [];
            foreach ($type->getTypes() as $innerType) {
                $parts[] = $this->get_named_type_declaration($innerType);
            }
            return implode('|', $parts);
        } else if ($type instanceof ReflectionNamedType) {
            return $this->get_named_type_declaration($type);
        }
        return '';
    }
    public function get_declaration(ReflectionParameter $param)
    {
        $type = $this->get_type_declaration($param->getType());
        $vari = $param->isVariadic() ? '...' : '';
        $ref = $param->isPassedByReference() ? ' &' : '';
        $name = '$' . $param->getName();
        $default = '';
        try {
            if ($param->isDefaultValueAvailable()) {

                $default .= ' = ' . var_export($param->getDefaultValue(), true);
            } else if ($param->isDefaultValueConstant()) {
                $default .= ' = ' . $param->getDefaultValueConstantName();
            }
        } catch (ReflectionException $ex) {
            //snarf this and hope it works?
        }
        return implode(' ', [$type, $vari, $ref, $name, $default]);
    }
    private function wrap_method(ReflectionMethod $method): array
    {
        $params = $method->getParameters();
        $return_type = $this->get_type_declaration($method->getReturnType());

        $result = [];
        $access = $method->isPublic() ? 'public' : 'protected';
        $result[] = $access . ' function ' . ($method->getName()) . '(';
        $i = 0;
        foreach ($params as $p) {
            if ($i > 0) {
                $result[array_key_last($result)] .= ', ';
            }
            $result[] = '    ' . $this->get_declaration($p);
            $i++;
        }

        $result[] = ') ' . (!empty($return_type) ? (': ' . $return_type) : '');
        $result[] = '{';
        $result[] = '    $invocation = new \\CannaPress\\Util\\Proxies\\Invocation( ';
        $result[] = '        ' . ($this->hasTarget ? '$this->wrapped_instance' : 'null') . ',';
        $result[] = '        \'' . addslashes($method->getName()) . '\',';
        $result[] = '        [' . implode(', ', array_map(fn ($x) => '$' . $x->getName(), $params)) . ' ]';
        $result[] = '    );';
        if ($this->hasTarget) {
            $result[] = '    if($this->interceptor->supports($invocation))';
            $result[] = '    {';
        }
        $result[] = '        $this->interceptor->invoke($invocation);';

        if ($this->hasTarget) {
            $result[] = '    }';
            $result[] = '    else';
            $result[] = '    {';
            $result[] = '        $invocation->proceed();';
            $result[] = '    }';
        }
        if ($return_type !== 'void') {
            $result[] = '        return $invocation->result;';
        }
        $result[] = '}';
        return $result;
    }

    public function create_constructor(): array
    {
        $result = [];
        $result[] = 'public function __construct(';
        if ($this->hasTarget) {
            $result[] = '    private \\' . ($this->class->getName()) . ' $wrapped_instance,';
        }
        $result[] = '    private \\CannaPress\\Util\\Proxies\\Interceptor $interceptor';
        $result[] = ')';
        $result[] = '{';

        $param_count = count($this->class->getConstructor()?->getParameters() ?? []);
        if ($param_count) {
            $result[] = '  try {';
            $result[] = '    parent::__construct(' . (implode(', ', array_map(fn ($x) => 'null', range(1, $param_count)))) . ');';
            $result[] = '  } catch (\TypeError $ex) {';
            $result[] = '    // snarf errors constructing as well be delegating to $this->wrapped_instance';
            $result[] = '  }';
        }
        $result[] = '}';

        return $result;
    }
    public static function indent(array $lines): array
    {
        return array_map(fn ($x) => '    ' . $x, $lines);
    }
}
