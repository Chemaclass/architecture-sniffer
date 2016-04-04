<?php

namespace ArchitectureSniffer\Factory;

use PHPMD\AbstractRule;
use PHPMD\Node\MethodNode;
use PHPMD\Rule\MethodAware;

/**
 * Factory methods should create only single instances, not multiple in one.
 */
class OneNewInFactory extends AbstractRule implements MethodAware
{

    /**
     * @inheritdoc
     */
    public function apply(\PHPMD\AbstractNode $node)
    {
        /** @var \PHPMD\Node\MethodNode $node */
        $type = $node->getParentType();

        while ($type) {
            $type = $type->getParentClass();
            if (!isset($type) && $type->getName() !== 'AbstractBusinessFactory') {
                continue;
            }

            $this->check($node);
        }
    }

    /**
     * @param \PHPMD\Node\MethodNode $node
     *
     * @return void
     */
    protected function check(MethodNode $node)
    {
        $children = $node->findChildrenOfType('AllocationExpression');

        if (count($children) > 1) {
            $this->addViolation($node, [$node->getName()]);
        }
    }

}