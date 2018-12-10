<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace ArchitectureSniffer\Common\Bridge;

use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Node\ClassNode;
use PHPMD\Node\InterfaceNode;
use PHPMD\Node\MethodNode;
use PHPMD\Rule\ClassAware;
use ReflectionClass;
use ReflectionMethod;

class BridgeMethodsRule extends AbstractRule implements ClassAware
{
    protected const RULE = 'All bridge methods must have exactly the same signature as their interface';

    /**
     * @return string
     */
    public function getDescription()
    {
        return static::RULE;
    }

    /**
     * @param \PHPMD\AbstractNode $node
     *
     * @return void
     */
    public function apply(AbstractNode $node)
    {
        if (preg_match('([A-Za-z0-9]+Bridge$)', $node->getName()) === 0 ||
            preg_match('#.*\\\\Dependency\\\\.*#', $node->getNamespaceName()) === 0 ||
            !$node instanceof ClassNode) {
            return;
        }

        $firstInterface = $node->getInterfaces()[0];
        $interfaceNode = new InterfaceNode($firstInterface);

        $this->verifyClass($node, $interfaceNode);
        $this->verifyInterface($node, $interfaceNode);
    }

    /**
     * @param \PHPMD\Node\ClassNode $node
     * @param \PHPMD\Node\InterfaceNode $interfaceNode
     *
     * @return void
     */
    protected function verifyClass(ClassNode $node, InterfaceNode $interfaceNode): void
    {
        $classMethods = $node->getMethods();
        $interfaceMethods = $interfaceNode->getMethods();

        $notMatchingMethods = $this->findNotMatchingMethodsForBridgeClass($classMethods, $interfaceMethods);

        foreach ($notMatchingMethods as $notMatchingMethod) {
            $message = sprintf(
                'The bridge has incorrect method \'%s\' signature. That violates the rule "%s"',
                $notMatchingMethod->getName(),
                static::RULE
            );

            $this->addViolation($node, [$message]);
        }
    }

    /**
     * @param \PHPMD\Node\ClassNode $node
     * @param \PHPMD\Node\InterfaceNode $interfaceNode
     *
     * @return void
     */
    protected function verifyInterface(ClassNode $node, InterfaceNode $interfaceNode): void
    {
        $bridgedInterfaceReflection = $this->getBridgedInterfaceReflection($node->getMethods());

        $notMatchingMethods = $this->findNotMatchingMethodsForBridgeInterface($interfaceNode, $bridgedInterfaceReflection);

        foreach ($notMatchingMethods as $notMatchingMethod) {
            $message = sprintf(
                'The bridge interface has incorrect method \'%s\' signature. That violates the rule "%s"',
                $notMatchingMethod->getName(),
                static::RULE
            );

            $this->addViolation($interfaceNode, [$message]);
        }
    }

    /**
     * @param \PHPMD\Node\MethodNode[] $classMethods
     * @param \PHPMD\Node\MethodNode[] $interfaceMethods
     *
     * @return array|null
     */
    protected function findNotMatchingMethodsForBridgeClass(array $classMethods, array $interfaceMethods): ?array
    {
        $notMatchingMethods = [];

        foreach ($classMethods as $classMethod) {
            if (!$classMethod->isPublic()) {
                continue;
            }

            foreach ($interfaceMethods as $interfaceMethod) {
                if (!$interfaceMethod->isPublic()) {
                    continue;
                }

                if ($classMethod->getName() !== $interfaceMethod->getName()) {
                    continue;
                }

                if ($this->compareTwoMethodForBridgeClass($classMethod, $interfaceMethod)) {
                    continue;
                }

                $notMatchingMethods[] = $classMethod;
            }
        }

        return $notMatchingMethods;
    }

    /**
     * @param \PHPMD\Node\MethodNode $firstMethod
     * @param \PHPMD\Node\MethodNode $secondMethod
     *
     * @return bool
     */
    protected function compareTwoMethodForBridgeClass(MethodNode $firstMethod, MethodNode $secondMethod): bool
    {
        if ($firstMethod->getParameterCount() !== $secondMethod->getParameterCount()) {
            return false;
        }

        $countParameters = $firstMethod->getParameterCount();
        $firstMethodParameters = $firstMethod->getNode()->getParameters();
        $secondMethodParameters = $secondMethod->getNode()->getParameters();

        for ($i = 0; $i < $countParameters; $i++) {
            if ((string)$firstMethodParameters[$i] !== (string)$secondMethodParameters[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \PHPMD\Node\InterfaceNode $interfaceNode
     * @param \ReflectionClass $bridgedInterfaceReflection
     *
     * @return array|null
     */
    protected function findNotMatchingMethodsForBridgeInterface(InterfaceNode $interfaceNode, ReflectionClass $bridgedInterfaceReflection): ?array
    {
        $notMatchingMethods = [];

        foreach ($interfaceNode->getMethods() as $interfaceMethod) {
            $bridgedInterfaceReflectionMethod = $bridgedInterfaceReflection->getMethod($interfaceMethod->getName());

            if (!$bridgedInterfaceReflectionMethod) {
                $notMatchingMethods[] = $interfaceMethod;
                continue;
            }

            $interfaceMethodName = sprintf('%s::%s', $interfaceNode->getFullQualifiedName(), $interfaceMethod->getName());
            $interfaceMethodReflection = new ReflectionMethod($interfaceMethodName);

            if ($this->compareTwoMethodsForBridgeInterface($interfaceMethodReflection, $bridgedInterfaceReflectionMethod)) {
                continue;
            }

            $notMatchingMethods[] = $interfaceMethod;
        }

        return $notMatchingMethods;
    }

    /**
     * @param \ReflectionMethod $firstMethod
     * @param \ReflectionMethod $secondMethod
     *
     * @return bool
     */
    protected function compareTwoMethodsForBridgeInterface(ReflectionMethod $firstMethod, ReflectionMethod $secondMethod): bool
    {
        $firstMethodParameters = $firstMethod->getParameters();
        $secondMethodParameters = $secondMethod->getParameters();

        if (count($firstMethodParameters) !== count($secondMethodParameters)) {
            return false;
        }

        $countParameters = count($firstMethodParameters);

        for ($i = 0; $i < $countParameters; $i++) {
            if ((string)$firstMethodParameters[$i] !== (string)$secondMethodParameters[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \PHPMD\Node\MethodNode[] $classMethods
     *
     * @return \ReflectionClass|null
     */
    protected function getBridgedInterfaceReflection(array $classMethods): ?ReflectionClass
    {
        foreach ($classMethods as $classMethod) {
            if ($classMethod->getName() === '__construct') {
                $constructorComment = $classMethod->getNode()->getComment();

                if (!$constructorComment) {
                    return null;
                }

                $firstParameter = $classMethod->getNode()->getParameters()[0];

                if (!$firstParameter) {
                    return null;
                }

                $pattern = '#@param[\s]+(?<interfaceName>.*)[\s]+' . preg_quote($firstParameter->getName()) . '#is';

                if (preg_match($pattern, $constructorComment, $matches) === 0) {
                    return null;
                }

                return new ReflectionClass($matches['interfaceName']);
            }
        }

        return null;
    }
}
