<?php

namespace WPML\Auryn;

class StandardReflector implements Reflector
{
    public function getClass($class)
    {
        return new \ReflectionClass($class);
    }

    public function getCtor($class)
    {
        $reflectionClass = new \ReflectionClass($class);

        return $reflectionClass->getConstructor();
    }

    public function getCtorParams($class)
    {
        return ($reflectedCtor = $this->getCtor($class))
            ? $reflectedCtor->getParameters()
            : null;
    }

	public function getParamTypeHint( \ReflectionFunctionAbstract $function, \ReflectionParameter $param ) {
		if ( version_compare( '7.1.0', phpversion(), '<' ) ) {
			$reflectionClass = $param->getType() && ! $param->getType()->isBuiltin()
				? new \ReflectionClass( $param->getType()->getName() )
				: null;
		} else {
			$reflectionClass = $param->getClass();
		}

		return ( $reflectionClass )
			? $reflectionClass->getName()
			: null;
	}

    public function getFunction($functionName)
    {
        return new \ReflectionFunction($functionName);
    }

    public function getMethod($classNameOrInstance, $methodName)
    {
        $className = is_string($classNameOrInstance)
            ? $classNameOrInstance
            : get_class($classNameOrInstance);

        return new \ReflectionMethod($className, $methodName);
    }
}
