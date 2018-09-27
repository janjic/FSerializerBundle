<?php
namespace FSerializerBundle\Serializer;


class JsonApiMany implements JsonApiElementInterface
{
    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var JsonApiSerializationInterface
     */
    protected $serializer;

    protected $relationsPath;


    /**
     * Create a new collection instance.
     *
     * @param mixed $data
     * @param mixed $serializer
     */
    public function __construct($data, $serializer)
    {
        $this->serializer = $serializer;
        $this->resources = $this->buildResources($data, $serializer);
    }

    /**
     * @return JsonApiSerializationInterface
     */
    public function getSerializer(): JsonApiSerializationInterface
    {
        return $this->serializer;
    }

    /**
     * @param JsonApiSerializationInterface $serializer
     */
    public function setSerializer(JsonApiSerializationInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Set the resources array.
     *
     * @param array $resources
     *
     * @return void
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_map(function (JsonApiOne $resource) {
            return $resource->toArray();
        }, $this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function toIdentifier()
    {
        return array_map(function (JsonApiOne $resource) {
            return $resource->toIdentifier();
        }, $this->resources);
    }

    /**

     * {@inheritdoc}
     */
    public function relations($relationships)
    {
        foreach ($this->resources as $resource) {
            $resource->relations($relationships);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function attributes($attributes)
    {
        foreach ($this->resources as $resource) {
            $resource->attributes($attributes);
        }
        return $this;
    }

    /**
     * Convert an array of raw data to Resource objects.
     *
     * @param mixed $data
     * @param JsonApiSerializationInterface $serializer
     *
     * @return JsonApiOne[]
     */
    protected function buildResources($data, JsonApiSerializationInterface $serializer)
    {
        $resources = [];
        $first = false;
        foreach ($data as $resource) {
            if (! ($resource instanceof JsonApiOne)) {

                $resource = new JsonApiOne($resource, $serializer);
                if ($first) {
                    $this->relationsPath = $resource->getRelationsPath();
                }
                $first = false;
            }
            $resources[] = $resource;
        }
        return $resources;
    }


    /**
     * @param $relationShips
     * @param $included
     * @param $name
     * @return mixed
     */
    public function getDenormalizedData($relationShips, $included, $name)
    {
        if (!count($this->resources) && array_key_exists($name, $relationShips) && ($size = count($relationShips[$name]['data']))) {
            $data = array_fill(0, $size, null);
            $this->resources = $this->buildResources($data, $this->serializer);
        }
        //$objects = array();
        foreach ($this->resources as $resource) {
           //$objects[] = $resource->getDenormalizedData($relationShips, $included, $name);
           //break;
            return  array($resource->getDenormalizedData($relationShips, $included, $name));
        }
        //return $objects;
    }
}