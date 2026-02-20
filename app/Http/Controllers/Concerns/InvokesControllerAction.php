<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

trait InvokesControllerAction
{
    abstract protected static function targetClass(): string;

    abstract protected static function targetMethod(): string;

    public function __invoke(Request $request)
    {
        $targetClass = static::targetClass();
        $targetMethod = static::targetMethod();
        $target = app($targetClass);
        $method = new ReflectionMethod($target, $targetMethod);
        $resolvedArgs = [];

        foreach ($method->getParameters() as $parameter) {
            $resolvedArgs[] = $this->resolveParameter($request, $parameter);
        }

        return $target->{$targetMethod}(...$resolvedArgs);
    }

    private function resolveParameter(Request $request, ReflectionParameter $parameter): mixed
    {
        $namedType = $parameter->getType() instanceof ReflectionNamedType
            ? $parameter->getType()
            : null;

        if ($namedType && ! $namedType->isBuiltin()) {
            $typeName = $namedType->getName();

            if (is_a($typeName, Request::class, true)) {
                return $request;
            }

            if (is_a($typeName, Model::class, true)) {
                return $this->resolveModelParameter($request, $parameter, $typeName, $namedType->allowsNull());
            }

            return app($typeName);
        }

        $routeValue = $request->route($parameter->getName());
        if ($routeValue !== null) {
            return $routeValue;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    private function resolveModelParameter(
        Request $request,
        ReflectionParameter $parameter,
        string $modelClass,
        bool $allowsNull
    ): ?Model {
        $name = $parameter->getName();
        $routeValue = $request->route($name);

        if ($routeValue instanceof $modelClass) {
            return $routeValue;
        }

        if ($routeValue === null) {
            if ($allowsNull || $parameter->isDefaultValueAvailable()) {
                return null;
            }

            throw (new ModelNotFoundException())->setModel($modelClass);
        }

        /** @var Model $model */
        $model = app($modelClass);
        $route = $request->route();
        $bindingField = method_exists($route, 'bindingFieldFor') ? $route->bindingFieldFor($name) : null;
        $resolved = $model->resolveRouteBinding($routeValue, $bindingField);

        if (! $resolved instanceof Model) {
            if ($allowsNull) {
                return null;
            }

            throw (new ModelNotFoundException())->setModel($modelClass, [$routeValue]);
        }

        return $resolved;
    }
}
