<?php


namespace FSerializerBundle\Generators;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Exception;
use FSerializerBundle\Serializer\JsonApiMany;
use FSerializerBundle\Serializer\JsonApiOne;
use ReflectionClass;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Class EmberModelGenerator
 * @package Plugins\EmpirePluginsBundle\Generators
 */
class FJsonApiGenerator extends Generator
{
    /** @var  DisconnectedClassMetadataFactory */
    private  $classMetadataFactory;

    private $associationMappingRules = [
        ClassMetadataInfo::ONE_TO_ONE =>   JsonApiOne::class,
        ClassMetadataInfo::ONE_TO_MANY =>  JsonApiMany::class,
        ClassMetadataInfo::MANY_TO_ONE =>  JsonApiOne::class,
        ClassMetadataInfo::MANY_TO_MANY => JsonApiMany::class,

    ];

    /**
     * FJsonApiGenerator constructor.
     * @param ClassMetadataFactory $classMetadataFactory
     */
    public function __construct(ClassMetadataFactory $classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;
    }


    /**
     * @param $class
     * @return array
     * @throws \ReflectionException
     */
    public function generateMapping($class)
    {
        $reflectionClass = new ReflectionClass($class);
        $attributes = array();
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (
                $reflectionMethod->getNumberOfRequiredParameters() !== 0 ||
                $reflectionMethod->isStatic() ||
                $reflectionMethod->isConstructor() ||
                $reflectionMethod->isDestructor()
            ) {
                continue;
            }

            $name = $reflectionMethod->name;
            $attributeName = null;

            if (0 === strpos($name, 'get') || 0 === strpos($name, 'has')) {
                // getters and hassers
                $attributeName = lcfirst(substr($name, 3));
            } elseif (strpos($name, 'is') === 0) {
                // issers
                $attributeName = lcfirst(substr($name, 2));

            }


            if (null !== $attributeName ) {
                $attributes[$attributeName] = true;
            }
        }

        $relations = array();
        try {
            $metaData = $this->classMetadataFactory->getMetadataFor($class);

            $attributes = array_keys($attributes);

            foreach ($metaData->associationMappings as $key => $association) {
                $relations[$key] = $this->associationMappingRules[$association['type']];
            }
        } catch (Exception $e) {

            return array('attributes'=> array_keys($attributes), 'relationships'=>$relations);
        }

        return array('attributes'=> array_diff($attributes, array_keys($relations)), 'relationships'=>$relations);

    }

}
