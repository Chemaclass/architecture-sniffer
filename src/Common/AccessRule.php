<?php

namespace ArchitectureSniffer\Common;

use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Node\ClassNode;
use PHPMD\Rule\ClassAware;

class AccessRule extends AbstractRule implements ClassAware
{

    /**
     * @var array
     */
    private $patterns = [
        [
            '(^Spryker.+)',
            '(^Pyz.+)',
            '{type} {source} accesses {target} which violates rule "No call from Core to Project"'
        ],
        [
            '(Spryker\\\\Yves\\\\.+)',
            '(Spryker\\\\Zed\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Yves to Zed"'
        ],
        [
            '(Spryker\\\\Zed\\\\.+)',
            '(Spryker\\\\Yves\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Zed to Yves"'
        ],
        [
            '(Spryker\\\\Shared\\\\.+)',
            '(Spryker\\\\Zed\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Shared to Zed"'
        ],
        [
            '(Spryker\\\\Shared\\\\.+)',
            '(Spryker\\\\Client\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Shared to Client"'
        ],
        [
            '(Spryker\\\\Shared\\\\.+)',
            '(Spryker\\\\Yves\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Shared to Yves"'
        ],
        [
            '(Spryker\\\\(Shared|Yves|Zed)\\\\Library\\\\.+)',
            '(Spryker\\\\(Shared|Yves|Zed)\\\\(?!Library).+)',
            '{type} {source} accesses {target} which violates rule "No call Library bundle to any other bundle"'
        ],
        [
            '(Spryker\\\\Client\\\\.+)',
            '(Spryker\\\\Zed\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Client to Zed"'
        ],
        [
            '(Spryker\\\\Client\\\\.+)',
            '(Spryker\\\\Yves\\\\.+)',
            '{type} {source} accesses {target} which violates rule "No call from Client to Yves"'
        ],
    ];

    /**
     * @param \PHPMD\AbstractNode $node
     */
    public function apply(AbstractNode $node)
    {
        $patterns = $this->collectPatterns($node);

        $this->applyPatterns($node, $patterns);

        foreach ($node->getMethods() as $method) {
            $this->applyPatterns(
                $method,
                $patterns
            );
        }
    }

    /**
     * @param \PHPMD\AbstractNode $node
     * @param array $patterns
     *
     * @return void
     */
    private function applyPatterns(AbstractNode $node, array $patterns)
    {
        foreach ($node->getDependencies() as $dependency) {
            $targetQName = sprintf('%s\\%s', $dependency->getNamespaceName(), $dependency->getName());

            foreach ($patterns as list($srcPattern, $targetPattern, $message)) {
                if (0 === preg_match($srcPattern, $node->getFullQualifiedName())) {
                    continue;
                }
                if (0 === preg_match($targetPattern, $targetQName)) {
                    continue;
                }

                $this->addViolation(
                    $node,
                    [
                        str_replace(
                            ['{type}', '{source}', '{target}'],
                            [ucfirst($node->getType()), $node->getFullQualifiedName(), $targetQName],
                            $message
                        )
                    ]
                );
            }
        }
    }

    /**
     * @param \PHPMD\Node\ClassNode $class
     *
     * @return array
     */
    private function collectPatterns(ClassNode $class)
    {
        $patterns = [];
        foreach ($this->patterns as list($srcPattern, $targetPattern, $message)) {
            if (preg_match($srcPattern, $class->getNamespaceName())) {
                $patterns[] = [$srcPattern, $targetPattern, $message];
            }
        }

        return $patterns;
    }

}
