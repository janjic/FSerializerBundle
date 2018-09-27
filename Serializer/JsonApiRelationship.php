<?php
namespace FSerializerBundle\Serializer;

/**
 * Class JsonApiRelationship
 * @package FSerializerBundle\Serializer
 */
class JsonApiRelationship
{

    use JsonApiLinkTrait;
    use JsonApiMetaTrait;
    /**
     * The data object.
     *
     * @var JsonApiElementInterface|null
     */
    protected $data;


    /**
     * @var
     */
    protected $name;
    /**
     * Create a new relationship.
     *
     * @param  JsonApiElementInterface|null $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }
    /**
     * Get the data object.
     *
     * @return JsonApiElementInterface|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data object.
     *
     * @param JsonApiElementInterface|null $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Map everything to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];
        if (! empty($this->data)) {
            $array['data'] = $this->data->toIdentifier();
        }
        if (! empty($this->meta)) {
            $array['meta'] = $this->meta;
        }
        if (! empty($this->links)) {
            $array['links'] = $this->links;
        }

        return $array;
    }
    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $relationShips
     * @param $included
     * @return mixed
     */
    public function getDenormalizedData($relationShips, $included)
    {
        return $this->data->getDenormalizedData($relationShips, $included, $this->name);

    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}