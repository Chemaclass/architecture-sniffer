<?php

namespace ArchitectureSniffer\Common\DependencyProvider;

use ArchitectureSniffer\Common\DeprecationTrait;
use PHPMD\AbstractRule;
use PHPMD\Node\AbstractNode;
use PHPMD\Node\MethodNode;

abstract class AbstractDependencyProviderRule extends AbstractRule
{
    use DeprecationTrait;

    const RULE = 'DependencyProvider should only contain additional add*() or get*() methods.';

    /**
     * @param \PHPMD\Node\AbstractNode $node
     * @param string $application
     *
     * @return bool
     */
    protected function isDependencyProvider(AbstractNode $node, $application)
    {
        $className = $node->getFullQualifiedName();
        if ($node instanceof MethodNode) {
            $parent = $node->getNode()->getParent();
            $className = $parent->getNamespaceName() . '\\' . $parent->getName();
        }

        if (preg_match('/\\\\' . $application . '\\\\.*\\\\\w+DependencyProvider$/', $className)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHPMD\Node\MethodNode $method
     * @param array $allowedProvideMethodNames
     *
     * @return void
     */
    protected function applyRule(MethodNode $method, array $allowedProvideMethodNames)
    {
        if ($this->isMethodDeprecated($method)) {
            return;
        }

        if (in_array($method->getName(), $allowedProvideMethodNames)) {
            return;
        }

        if (0 != preg_match('/^(add|get).+/', $method->getName())) {
            return;
        }

        $message = sprintf(
            'The DependencyProvider method %s() violates rule "%s"',
            $method->getName(),
            static::RULE
        );

        $this->addViolation($method, [$message]);
    }
}