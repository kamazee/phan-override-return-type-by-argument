<?php

namespace Kamazee\PhanPlugin\OverrideReturnTypeByArgument;

use AssertionError;
use ast\Node;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\Element\Method;
use Phan\Language\FQSEN\FullyQualifiedClassConstantName;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\UnionType;
use Phan\PluginV2;
use Phan\PluginV2\ReturnTypeOverrideCapability;
use const ast\AST_METHOD;
use const ast\flags\USE_NORMAL;
use function array_key_exists;
use function assert;
use function ast\get_kind_name;
use function is_int;
use function is_string;
use function strpos;
use function substr;

final class Plugin extends PluginV2 implements ReturnTypeOverrideCapability
{
    private const ANNOTATION_TYPE_OVERRIDE = '@returnTypeArg';

    private const INVALID_ANNOTATION_ISSUE_TYPE = 'PhanPluginReturnTypeOverrideWrongAnnotation';
    private const INVALID_ANNOTATION_MSG = '%s parameter is not found in %s';

    private const CANT_INFER_TYPE_ISSUE_TYPE = 'PhanPluginReturnTypeOverrideWrongAnnotation';
    private const CANT_INFER_TYPE_MSG = 'Return type for {FUNCTIONLIKE} call can\'t be inferred (%s)';

    /**
     * @param CodeBase $code_base
     *
     * @return \Closure[]
     */
    public function getReturnTypeOverrides(CodeBase $code_base): array
    {
        $callbacks = [];
        foreach ($code_base->getMethodSet() as $method) {
            /** @var Method $method */
            if (!$method->hasNode()) {
                continue;
            }

            $parameterName = self::getArgumentIndexWithType($method->getNode());

            // No processable annotation found
            if (null === $parameterName) {
                continue;
            }

            $parameterIndex = null;

            foreach ($method->getParameterList() as $index => $parameter) {
                if ($parameterName === $parameter->getName()) {
                    $parameterIndex = $index;
                    break;
                }
            }

            if (null === $parameterIndex) {
                $this->emitIssue(
                    $code_base,
                    $method->getContext(),
                    self::INVALID_ANNOTATION_ISSUE_TYPE,
                    self::INVALID_ANNOTATION_MSG,
                    [$parameterName, (string) $method->getFQSEN()]
                );
            }

            assert(is_int($parameterIndex));

            $callbacks[(string) $method->getFQSEN()] = function (
                CodeBase $code_base,
                Context $context,
                Method $function,
                array $args
            ) use ($parameterIndex) {
                try {
                    return self::getReturnType(
                        $code_base,
                        $args[$parameterIndex],
                        $context
                    );
                } catch (TypeInferenceFailed $e) {
                    $this->emitIssue(
                        $code_base,
                        $context,
                        self::CANT_INFER_TYPE_ISSUE_TYPE,
                        sprintf(self::CANT_INFER_TYPE_MSG, $e->getMessage()),
                        array_merge(
                            [(string) $function->getFQSEN()],
                            $e->arguments
                        )
                    );

                    return $function->getRealReturnType();
                }
            };
        }

        return $callbacks;
    }

    /**
     * @param CodeBase $codeBase
     * @param string|Node $arg
     * @return UnionType
     * @throws TypeInferenceFailed
     */
    public static function getReturnType(CodeBase $codeBase, $arg, Context $context): UnionType
    {
        if (is_string($arg)) {
            $returnClassName = '\\' . ltrim($arg, '\\');
        } elseif ($arg instanceof Node) {
            $returnClassName = self::getValueFromNode($codeBase, $arg, $context);
        } else {
            throw TypeInferenceFailed::withReason(
                'Unsupported argument type, string or {CLASS} is expected',
                [Node::class]
            );
        }

        $fqsen = FullyQualifiedClassName::fromFullyQualifiedString($returnClassName);
        if (!$codeBase->hasClassWithFQSEN($fqsen)) {
            throw TypeInferenceFailed::withReason(
                'Class {CLASS} does not exist',
                [$returnClassName]
            );
        }

        return $fqsen->asUnionType();
    }

    /**
     * @param CodeBase $codeBase
     * @param $arg
     * @return string
     * @throws TypeInferenceFailed
     * @throws AssertionError
     */
    private static function getValueFromNode(CodeBase $codeBase, Node $arg, Context $context): string
    {
        switch ($arg->kind) {
            case \ast\AST_CLASS_CONST:
                $className = $arg->children['class']->children['name'];
                $classConstantName = $arg->children['const'];
                if ($context->hasNamespaceMapFor(USE_NORMAL, $className)) {
                    $classFullName = $context->getNamespaceMapFor(USE_NORMAL, $className);
                    $namespace = $classFullName->getNamespace();
                    $className = $classFullName->getName();
                } else {
                    $namespace = $context->getNamespace();
                }
                $phanClassName =
                    FullyQualifiedClassName::make(
                        $namespace,
                        $className
                    );

                if (!$codeBase->hasClassWithFQSEN($phanClassName)) {
                    throw TypeInferenceFailed::withReason(
                        "Class {CLASS} doesn't exist",
                        [$phanClassName]
                    );
                }

                /** @var FullyQualifiedClassConstantName $phanConstantName */
                $phanConstantName = FullyQualifiedClassConstantName::make(
                    $phanClassName,
                    $classConstantName
                );


                if (!$codeBase->hasClassConstantWithFQSEN($phanConstantName)) {
                    throw TypeInferenceFailed::withReason(
                        "Constant {CONST} doesn't exist",
                        [$phanConstantName]
                    );
                }

                $valueNode = $codeBase->getClassConstantByFQSEN($phanConstantName)
                    ->getNodeForValue();

                if (!is_string($valueNode)) {
                    throw TypeInferenceFailed::withReason(
                        'Value of {CONST} is not a {TYPE}',
                        [$phanConstantName, 'string']
                    );
                }

                return $valueNode;
            default:
                $nodeKindName = get_kind_name($arg->kind);
                throw TypeInferenceFailed::withReason(
                    "Unsupported node: $nodeKindName ({$arg->kind})"
                );
        }
    }

    public static function getArgumentIndexWithType(Node $node): ?string
    {
        if (AST_METHOD !== $node->kind) {
            return null;
        }

        if (!array_key_exists('docComment', $node->children)) {
            return null;
        }

        $phpdoc = $node->children['docComment'];
        $marker = self::ANNOTATION_TYPE_OVERRIDE . ' ';
        if (false === $pos = strpos($phpdoc, $marker)) {
            return null;
        }

        $eolPos = strpos($phpdoc, "\n", $pos);
        $varNameStart = $pos + strlen($marker);
        $varNameLen = $eolPos - $varNameStart;
        $varName = substr($phpdoc, $varNameStart, $varNameLen);
        return trim(ltrim($varName), '$');
    }
}
