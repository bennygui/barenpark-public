<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * theisleofcats implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

namespace BX\Meta;

class AnnotationElement
{
    private $property;
    private $annotation;
    private $parameters;

    public function __construct(string $property, string $annotation, array $parameters = [])
    {
        $this->property = $property;
        $this->annotation = $annotation;
        $this->parameters = $parameters;
    }

    public function property()
    {
        return $this->property;
    }

    public function annotation()
    {
        return $this->annotation;
    }

    public function parameters()
    {
        return $this->parameters;
    }
}

class Annotation
{
    private $reflect;

    public function __construct(string $className)
    {
        $this->reflect = new \ReflectionClass($className);
    }

    public function getPropertiesWithAnnotation(string $annotation)
    {
        return array_unique(
            array_map(
                function ($e) {
                    return $e->property();
                },
                array_filter($this->getAnnotationElements(), function ($e) use ($annotation) {
                    return $e->annotation() == $annotation;
                })
            )
        );
    }

    public function getAnnotationElementsWithAnnotation(string $annotation)
    {
        return array_filter($this->getAnnotationElements(), function ($e) use ($annotation) {
            return $e->annotation() == $annotation;
        });
    }

    public function getAnnotationElements()
    {
        $elems = [];
        foreach ($this->reflect->getProperties() as $property) {
            $docComment = $property->getDocComment();
            if ($docComment === false) {
                continue;
            }
            $matches = [];
            if (!preg_match_all('/@[-_a-zA-Z0-9(),]+/i', $docComment, $matches)) {
                continue;
            }
            foreach ($matches as $match) {
                foreach ($match as $annotation) {
                    $open = strpos($annotation, '(');
                    if ($open === false) {
                        $elems[] = new AnnotationElement($property->getName(), $annotation);
                    } else {
                        $elems[] = new AnnotationElement($property->getName(), substr($annotation, 0, $open), explode(',', substr($annotation, $open + 1, -1)));
                    }
                }
            }
        }
        return $elems;
    }
}

function extractAllPropertyValues($object)
{
    if (is_array($object)) {
        return array_map(function ($o) {
            return extractAllPropertyValues($o);
        }, $object);
    } else if (!is_object($object)) {
        return $object;
    }
    $allProperties = [
        '@classId' => get_class($object),
    ];

    $reflect = new \ReflectionClass(get_class($object));
    foreach ($reflect->getProperties() as $property) {
        $property->setAccessible(true);
        $value = $property->getValue($object);
        $allProperties[$property->getName()] = extractAllPropertyValues($value);
    }

    return $allProperties;
}

function rebuildAllPropertyValues($values)
{
    if (!\is_array($values)) {
        return $values;
    }
    if (\array_key_exists('@classId', $values)) {
        $object = newWithoutConstructor($values['@classId']);
        $reflect = new \ReflectionClass($values['@classId']);
        unset($values['@classId']);
        foreach ($values as $propertyName => $value) {
            $value = rebuildAllPropertyValues($value);
            $property = $reflect->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
        return $object;
    } else {
        return \array_map(function ($value) {
            return rebuildAllPropertyValues($value);
        }, $values);
    }
}

function deepClone($object)
{
    return rebuildAllPropertyValues(extractAllPropertyValues($object));
}

function setPropertyValue($object, string $propertyName, $value)
{
    $reflect = new \ReflectionClass(get_class($object));
    $property = $reflect->getProperty($propertyName);
    $property->setAccessible(true);
    $property->setValue($object, $value);
}

function newWithoutConstructor(string $className)
{
    $reflect = new \ReflectionClass($className);
    return $reflect->newInstanceWithoutConstructor();
}

function newWithConstructor(string $className, $args = [])
{
    $reflect = new \ReflectionClass($className);
    return $reflect->newInstanceArgs($args);
}
