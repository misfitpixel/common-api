<?php

namespace MisfitPixel\Common\Api\Service;


use MisfitPixel\Common\Exception;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ValidatorService
 * @package MisfitPixel\Common\API\Service
 */
class ValidatorService
{
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';

    /**
     * @param array|null $data
     * @param string $schemaName
     * @return bool
     * @throws \Exception
     */
    public function validate(?array $data, string $schemaName): bool
    {
        if($data === null) {
            return false;
        }

        try {
            /**
             * load the schema file.
             */
            $schema = Yaml::parseFile($schemaName);

            if($schema == null) {
                return true;
            }

        } catch(ParseException $e) {
            return false;
        }

        $this->validateNode($data, $schema['root'], $schema);

        return true;
    }

    /**
     * @param array $node
     * @param array $schemaNode
     * @param array $schema
     * @param string|null $child
     * @return void
     */
    private function validateNode(array $node, array $schemaNode, array $schema, string $child = null)
    {
        /**
         * validate required.
         */
        foreach($schemaNode as $schemaField => $rules) {
            $nullable = (isset($rules['nullable']) && (bool)$rules['nullable']);

            /**
             * field is nullable.
             */
            if(
                $nullable &&
                (array_key_exists($schemaField, $node) && $node[$schemaField] === null)
            ) {
                continue;
            }

            /**
             * field is required.
             */
            if(
                (bool)$rules['required'] &&
                (!isset($node[$schemaField]) || $node[$schemaField] === null || $node[$schemaField] === "")
            ) {
                throw new Exception\InvalidFieldException(sprintf('Missing %s%s field',
                        ($child != null) ? sprintf('%s.', $child) : '',
                        $this->getFriendlyFieldName($schemaField)
                ), $schemaField);
            }
        }

        foreach($node as $field => $value) {
            if(isset($schemaNode[$field])) {
                $params = $schemaNode[$field];
                $nullable = (isset($params['nullable']) && (bool)$params['nullable']);

                /**
                 * validate enum list.
                 */
                if(
                    isset($params['in']) &&
                    is_array($params['in']) &&
                    !in_array($node[$field], $params['in'])
                ) {
                    if(
                        $nullable &&
                        $node[$field] === null
                    ) {
                        continue;
                    }

                    throw new Exception\InvalidFieldException(sprintf('Invalid field value for %s%s; must be one of %s',
                        ($child != null) ? sprintf('%s.', $child) : '',
                        $field,
                        implode(', ', $params['in'])
                    ), $field);
                }

                /**
                 * validate types.
                 */
                if(isset($params['type'])) {
                    if(
                        $params['type'] === self::TYPE_STRING &&
                        (
                            (isset($params['min']) && strlen($node[$field]) < $params['min']) ||
                            (isset($params['max']) && strlen($node[$field]) > $params['max']) ||
                            (isset($params['pattern']) && !(bool)preg_match(sprintf('%s', $params['pattern']), $node[$field]))
                        )
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid field value for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    if(
                        $params['type'] === self::TYPE_BOOLEAN &&
                        !is_bool($node[$field])
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid boolean field value for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    if(
                        $params['type'] === self::TYPE_INT &&
                        $nullable &&
                        $node[$field] === null
                    ) {
                        continue;
                    }

                    if(
                        $params['type'] === self::TYPE_INT &&
                        (
                            !is_numeric($node[$field]) ||
                            (
                                (isset($params['min']) && $node[$field] < $params['min']) ||
                                (isset($params['max']) && $node[$field] > $params['max'])
                            )
                        )
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid field value for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    if(
                        $params['type'] === self::TYPE_FLOAT &&
                        $nullable &&
                        $node[$field] === null
                    ) {
                        continue;
                    }

                    if(
                        $params['type'] === self::TYPE_FLOAT &&
                        (
                            !is_numeric($node[$field]) ||
                            (
                                (isset($params['min']) && $node[$field] < $params['min']) ||
                                (isset($params['max']) && $node[$field] > $params['max'])
                            )
                        )
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid field value for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    if(
                        in_array($params['type'], [self::TYPE_ARRAY, self::TYPE_OBJECT]) &&
                        !is_array($node[$field])
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid field value for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    /**
                     * verify min and/or max size of array.
                     */
                    if(
                        $params['type'] === self::TYPE_ARRAY &&
                        (
                            !is_array($node[$field]) ||
                            (
                                (isset($params['min']) && sizeof($node[$field]) < $params['min']) ||
                                (isset($params['max']) && sizeof($node[$field]) > $params['max'])
                            )
                        )
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid field value for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    /**
                     * verify that the child schema is valid.
                     */
                    if(
                        in_array($params['type'], [self::TYPE_ARRAY, self::TYPE_OBJECT]) &&
                        (
                            isset($params['schema']) &&
                            !isset($schema[$params['schema']])
                        )
                    ) {
                        throw new Exception\InvalidFieldException(sprintf('Invalid validation config for %s%s',
                            ($child != null) ? sprintf('%s.', $child) : '',
                            $field
                        ), $field);
                    }

                    /**
                     * validate child schema.
                     */
                    if(
                        in_array($params['type'], [self::TYPE_ARRAY]) &&
                        is_array($node[$field]) &&
                        isset($params['schema'])
                    ) {
                        foreach($node[$field] as $index => $item) {
                            $this->validateNode($item, $schema[$params['schema']], $schema, $params['schema']);
                        }
                    }

                    if(
                        in_array($params['type'], [self::TYPE_OBJECT]) &&
                        is_array($node[$field]) &&
                        isset($params['schema'])
                    ) {
                        $this->validateNode($node[$field], $schema[$params['schema']], $schema, $params['schema']);
                    }
                }
            }
        }
    }

    /**
     * @param string $field
     * @return string
     */
    private function getFriendlyFieldName(string $field): string
    {
        return str_replace('_', ' ', $field);
    }
}
