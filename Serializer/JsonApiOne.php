<?php
namespace FSerializerBundle\Serializer;


use Doctrine\Common\Util\ClassUtils;

class JsonApiOne implements JsonApiElementInterface
{
    use JsonApiLinkTrait;
    use JsonApiMetaTrait;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var JsonApiSerializationInterface
     */
    protected $serializer;

    /**
     * A list of relationships to include.
     *
     * @var array
     */
    protected $includes = [];


    /**
     * A list of fields to restrict to.
     *
     * @var array|null
     */
    protected $attributes;

    /**
     * An array of Resources that should be merged into this one.
     *
     * @var JsonApiOne[]
     */
    protected $merged = [];


    /**
     * @var JsonApiRelationship[]
     */
    private $relationships;


    /**
     * @param mixed $data
     * @param JsonApiSerializerAbstract $serializer
     */
    public function __construct($data, $serializer)
    {
        $this->data = $data;
        $this->serializer = $serializer;
        if (method_exists($this->serializer, 'setDeserializationClass') && is_object($this->data) && !is_null($this->data)) {
            $serializer->setDeserializationClass(ClassUtils::getRealClass(get_class($this->data)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return [$this];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        //First construct basic skeleton
        $array = $this->toIdentifier();
        if (! $this->isIdentifier()) {
            // get attributes
            $array['attributes'] = $this->getAttributes();
        }
        //get relations
        $relationships = $this->getRelationshipsAsArray();
        if (count($relationships)) {
            $array['relationships'] = $relationships;
        }
        if (! empty($this->links)) {
            $links = $this->links;
        }
        $serializerLinks = $this->serializer->getLinks($this->data);
        if (! empty($serializerLinks)) {
            $links = array_merge($serializerLinks, $links ?? []);
        }
        if (! empty($links)) {
            $array['links'] = $links;
        }
        $meta = [];
        if (! empty($this->meta)) {
            $meta = $this->meta;
        }
        $serializerMeta = $this->serializer->getMeta($this->data);
        if (! empty($serializerMeta)) {
            $meta = array_merge($serializerMeta, $meta);
        }
        if (! empty($meta)) {
            $array['meta'] = $meta;
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function toIdentifier()
    {
        if (! $this->data) {
            return;
        }
        $array = [
            'type' => $this->getType(),
            'id' => $this->getId()
        ];
        if (! empty($this->meta)) {
            $array['meta'] = $this->meta;
        }
        return $array;
    }

    /**
     * Get the resource attributes.
     *
     * @return array
     */
    public function getAttributes()
    {

        $attributes = (array) $this->serializer->getAttributes($this->data, $this->getOwnFields());
        $attributes = $this->filterFields($attributes);
        $attributes = $this->mergeAttributes($attributes);
        return $attributes;
    }

    /**
     * Filter the given fields array (attributes or relationships) according
     * to the requested fieldset.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function filterFields(array $fields)
    {
        if ($requested = $this->getOwnFields()) {
            $fields = array_intersect_key($fields, array_flip($requested));
        }
        return $fields;
    }

    /**
     * Get the resource relationships as an array.
     *
     * @return array
     */
    public function getRelationshipsAsArray()
    {
        $relationships = $this->getRelationships();
        $relationships = $this->convertRelationshipsToArray($relationships);

        return $this->mergeRelationships($relationships);
    }

    /**
     * Convert the given array of Relationship objects into an array.
     *
     * @param JsonApiRelationship[] $relationships
     *
     * @return array
     */
    protected function convertRelationshipsToArray(array $relationships)
    {
        return array_map(function (JsonApiRelationship $relationship) {
            return $relationship->toArray();
        }, $relationships);
    }

    /**
     * Merge the relationships of merged resources into an array of
     * relationships.
     *
     * @param array $relationships
     *
     * @return array
     */
    protected function mergeRelationships(array $relationships)
    {
        foreach ($this->merged as $resource) {
            $relationships = array_replace_recursive($relationships, $resource->getRelationshipsAsArray());
        }

        return $relationships;
    }

    /**
     * Get the resource relationships.
     *
     * @return JsonApiRelationship[]
     */
    public function getRelationships()
    {
        $relationships = $this->buildRelationships();
        return $relationships;
    }

    /**
     * Merge the attributes of merged resources into an array of attributes.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function mergeAttributes(array $attributes)
    {
        foreach ($this->merged as $resource) {
            $attributes = array_replace_recursive($attributes, $resource->getAttributes());
        }
        return $attributes;
    }

    /**
     * Get the requested fields for this resource type.
     *
     * @return array|null
     */
    protected function getOwnFields()
    {
        $type = $this->getType();
        if (isset($this->attributes[$type])) {
            return $this->attributes[$type];
        }
    }

    /**
     * Get an array of built relationships.
     *
     * @return JsonApiRelationship[]
     */
    protected function buildRelationships()
    {
        if (isset($this->relationships)) {
            return $this->relationships;
        }
        $paths = JsonApiUtil::parseRelationshipPaths($this->includes);
        $relationships = [];
        foreach ($paths as $name => $nested) {
            $relationship = $this->serializer->getRelationship($this->data, $name);

            if ($relationship) {
                $relationshipData = $relationship->getData();
                if ($relationshipData instanceof JsonApiElementInterface) {
                    $relationshipData->relations($nested)->attributes($this->attributes);
                }
                $relationships[$name] = $relationship;
            }
        }

        return $this->relationships = $relationships;
    }

    /**
     * Get the resource type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->serializer->getType($this->data);
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getId()
    {
        if (! is_object($this->data) && ! is_array($this->data)) {
            return (string) $this->data;
        }
        return (string) $this->serializer->getId($this->data);
    }

    /**
     * Get the resource relationships without considering requested ones.
     *
     * @return JsonApiRelationship[]
     */
    public function getUnfilteredRelationships()
    {
        return $this->buildRelationships();
    }


    /**
     * Check whether or not this resource is an identifier (i.e. does it have
     * any data attached?).
     *
     * @return bool
     */
    public function isIdentifier()
    {
        return ! is_object($this->data) && ! is_array($this->data);
    }

    /**
     * Merge a resource into this one.
     *
     * @param JsonApiOne $resource
     *
     * @return void
     */
    public function merge(JsonApiOne $resource)
    {
        $this->merged[] = $resource;
    }

    /**
     * Request a relationship to be included.
     *
     * @param string|array $relationships
     *
     * @return $this
     */
    public function relations($relationships)
    {
        $this->includes = array_unique(array_merge($this->includes, (array) $relationships));
        $this->relationships = null;
        return $this;
    }

    /**
     * Request a restricted set of fields.
     *
     * @param array|null $fields
     *
     * @return $this
     */
    public function attributes($fields)
    {
        $this->attributes = $fields;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * @param mixed $data
     *
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
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
     *
     * @return void
     */
    public function setSerializer(JsonApiSerializationInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return array
     */
    public function getRelationsPath(): array
    {
        return $paths = JsonApiUtil::parseRelationshipPaths($this->includes);
    }

    /**
     * @param $relationShips
     * @param $included
     * @param $name
     * @return mixed
     */
    public function getDenormalizedData($relationShips, $included, $name)
    {
        if (!array_key_exists($name, $relationShips)) {
            return null;
        }
        $thisRelationship = $relationShips[$name];
        $serializer = $this->serializer;
        $objectDecoded = $thisRelationship['data'];
        if (!$objectDecoded) {
            return null;
        }
        if (array_key_exists('attributes', $thisRelationship['data']?$thisRelationship['data']:array() )) {
            return $objectDecoded;
        }
        $ids = ($isMany = array_key_exists(0,$objectDecoded)) ? array_map(function ($item){
                return $item['id'];
        },$objectDecoded):array($objectDecoded['id']);

        $returnValue = count($array = array_filter(
            $included,
            function ($e) use ($thisRelationship, $serializer, $ids){
                return $e['type'] === $serializer->getType() && in_array($e['id'],$ids);
            }))? ($isMany?array_values($array):reset($array)):$array;

        return $returnValue;

    }

    public function getCreatedRelationships()
    {
        return $this->relationships;
    }
}