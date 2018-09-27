<?php

namespace FSerializerBundle\Serializer;

abstract class JsonApiSerializerAbstract implements JsonApiSerializationInterface
{

    /**
     * @return mixed
     */
    public abstract function getDeserializationClass();

    /**
     * The type.
     *
     * @var string
     */
    protected $type;
    /**
     * {@inheritdoc}
     */
    public function getType($model=null)
    {
        return $this->type;
    }
    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        return $model->id;
    }
    /**
     * {@inheritdoc}
     */
    public function getAttributes($model, array $fields = null)
    {
        return [];
    }
    /**
     * {@inheritdoc}
     */
    public function getLinks($model)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta($model)
    {
        return [];
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function getRelationship($model, $name)
    {
        $method = $this->getRelationshipMethodName($name);
        if (method_exists($this, $method)) {
            /** @var JsonApiRelationship $relationship */
            $relationship = $this->$method($model);
            $relationship->setName($method);
            if ($relationship !== null && ! ($relationship instanceof JsonApiRelationship)) {
                throw new \Exception('Relationship method must return null or an instance of JsonApiRelationship');
            }
            return $relationship;
        }
    }

    /**
     * Get the serializer method name for the given relationship.
     *
     * kebab-case is converted into camelCase.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getRelationshipMethodName($name)
    {
        if (stripos($name, '-')) {
            $name = lcfirst(implode('', array_map('ucfirst', explode('-', $name))));
        }
        return $name;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setDocumentType ($type)
    {
        return $this->setType($type);
    }
}