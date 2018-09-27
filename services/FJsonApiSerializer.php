<?php
namespace FSerializerBundle\services;

use ArrayAccess;
use Countable;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Exception;
use FSerializerBundle\Generators\FJsonApiGenerator;
use FSerializerBundle\Serializer\JsonApiDocument;
use FSerializerBundle\Serializer\JsonApiElementInterface;
use FSerializerBundle\Serializer\JsonApiMany;
use FSerializerBundle\Serializer\JsonApiOne;
use FSerializerBundle\Serializer\JsonApiRelationship;
use FSerializerBundle\Serializer\JsonApiSerializerAbstract;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class FJsonApiSerializer
 * @package FSerializerBundle\services
 */
class FJsonApiSerializer extends JsonApiSerializerAbstract
{

    private $deserializationClass;

    /**
     * @var
     */
    private $mappings =array();

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DisconnectedClassMetadataFactory
     */
    private $cmf;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    private  $propertyAccessor;

    /**
     * @var
     */
    private $attributesMapping = array();


    /**
     * @var FJsonApiGenerator
     */
    private $generator;

    private $disabledAttributes = array('__initializer__', '__cloner__', '__isInitialized__');


    /**
     * @param $deserializationClass
     * @return $this
     */
    public function setDeserializationClass($deserializationClass)
    {
        $this->deserializationClass = $deserializationClass;
        return $this;
    }

    /**
     * FJsonApiSerializer constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->cmf = new ClassMetadataFactory();
        $this->cmf->setEntityManager($this->entityManager);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->generator = new FJsonApiGenerator($this->cmf);

    }


    /**
     * @return mixed
     */
    public function getAttributesMapping()
    {
        return $this->attributesMapping;
    }

    /**
     * @param mixed $attributesMapping
     */
    public function setAttributesMapping($attributesMapping)
    {
        $this->attributesMapping = $attributesMapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return $model->getId();
    }


    public function __call($method, $args) {
        switch ($method) {
            case 'getDeserializationClass':
                return null;
            default:
                $object = $args[0];
                if (array_key_exists($method, $this->mappings)) {
                    $data = $this->generator->generateMapping(get_class($object));
                    $relationshipInstance = $this->propertyAccessor->getValue($object,$method);
                    $relationships =  $data['relationships'];
                    if (!$relationships || !array_key_exists($method, $relationships)) {
                        $relationships[$method] = $this->mappings[$method]['jsonApiType'];
                    }
                    $relationshipOnOrMany = new $relationships[$method]( $relationshipInstance, $this->buildSelfInstance($method));
                    $relationshipOnOrMany->attributes($this->attributesMapping);
                    return new JsonApiRelationship($relationshipOnOrMany);
                }
                throw new \Exception('Method does not exist in FJsonApiSerializer');

                break;

        }
    }

    /**
     * @param $mappings
     * @return $this
     */
    public function setMappings($mappings)
    {
        $this->mappings = $mappings;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMappings()
    {
        return $this->mappings;
    }


    public function getRelationship($model, $name)
    {
        $method = $this->getRelationshipMethodName($name);
        $relationship = $this->$method($model);
        if ($relationship !== null && ! ($relationship instanceof JsonApiRelationship)) {
            throw new \Exception('Relationship method must return null or an instance of JsonApiRelationship');
        }

        $relationship->setName($method);
        return $relationship;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($model, array $fields = null)
    {
        $attributes = $fields;
        if (!$fields) {
            $data = $this->generator->generateMapping(get_class($model));
            $attributes =  $data['attributes'];
        }
        $attributes = array_diff($attributes, $this->disabledAttributes);

        $getAttributesReturnValue = array();
        foreach ($attributes as $attribute) {
            $getAttributesReturnValue[$attribute] =   $this->propertyAccessor->getValue($model, $attribute);
        }

        return $getAttributesReturnValue;
    }

    /**
     * @return mixed
     */
    public function getDeserializationClass()
    {
        return $this->deserializationClass;
    }

    /**
     * @return array
     */
    public function getDisabledAttributes(): array
    {
        return $this->disabledAttributes;
    }

    /**
     * @param $data
     * @param array $mappings
     * @param $relations
     * @return mixed
     */
    public function deserialize($data, array $mappings, $relations)
    {
        $jsonApiDocument = new JsonApiDocument((new JsonApiOne(null,  $this->setMappings($mappings)))->relations($relations));
        $jsonApiDocument->setEntityManager($this->entityManager);

        return $jsonApiDocument->deserialize($data);
    }

    /**
     * @param $data
     * @param array $mappings
     * @param $relations
     * @param array $disabledAttributes
     * @param array $attributeMappings
     * @return JsonApiDocument
     * @throws Exception
     */
    public function serialize($data, array $mappings, $relations, $disabledAttributes = array(), $attributeMappings = array())
    {
        if (is_null($data)) {
            return new JsonApiDocument(new JsonApiOne(null, $this));
        }
        if (is_array($data) && empty($data)) {
            return new JsonApiDocument(new JsonApiMany(array(), $this));
        }

        $isArray = (is_array($data) || $data instanceof Countable || $data instanceof ArrayAccess);
        //Make sure object is not proxy
        $class   =  ($isArray && count($data) || is_object($data)) ? ClassUtils::getRealClass($isArray ? get_class($data[0]): get_class($data)):null;
        $this->setMappings($mappings);
        $this->setDisabledAttributes($disabledAttributes);
        $this->setAttributesMapping($attributeMappings);

        if (!$this->type || !$this->deserializationClass) {
            if(!$class) {
                throw new Exception('Please set deserialization class');
            }
            foreach ($mappings as $mapping) {
                if ($mapping['class'] == $class ) {
                    $this->setType($mapping['type']);
                    $this->setDeserializationClass($class);
                    break;
                }
            }
        }
        /** @var JsonApiElementInterface $resourceClass */
        $resourceClass = $isArray ? JsonApiMany::class : JsonApiOne::class;
        return new JsonApiDocument((new $resourceClass($data, $this))->attributes($attributeMappings)->relations($relations));
    }

    /**
     * @param array $disabledAttributes
     * @return $this
     */
    public function setDisabledAttributes(array $disabledAttributes)
    {
        $this->disabledAttributes = array_merge($this->disabledAttributes,$disabledAttributes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLinks($resource)
    {
        $mappings =   $this->getMappings();
        foreach ($mappings as $mapping) {
            if ($mapping['type'] == $this->type ) {
                if (array_key_exists('links', $mapping) && ($function = $mapping['links']['function'])) {
                    $params = array_key_exists('dependency', $mapping['links']) ?$mapping['links']['dependency']:array();
                    $params[] = $resource;
                    return call_user_func_array($function, $params);

                }


            }
        }

        return array();
    }

    public function getMeta($resource) {
        $mappings =   $this->getMappings();
        foreach ($mappings as $mapping) {
            if ($mapping['type'] == $this->type ) {
                if (array_key_exists('meta', $mapping) && ($function = $mapping['meta']['function'])) {
                    $params = array_key_exists('dependency', $mapping['meta']) ?$mapping['meta']['dependency']:array();
                    $params[] = $resource;
                    return call_user_func_array($function, $params);

                }

            }
        }


        return array();
    }

    private function buildSelfInstance($name)
    {
        $instance = (new self($this->entityManager))->setMappings($this->mappings);
        $mappings = $this->getMappings();
        foreach ($mappings as $key =>$mapping) {
            if ($key==$name) {
                $instance->setType($mapping['type']);
                $instance->setDeserializationClass($mapping['class']);
                $instance->setDisabledAttributes($this->disabledAttributes);
                break;
            }
        }

        return $instance;
    }

}