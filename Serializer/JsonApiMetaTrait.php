<?php

namespace FSerializerBundle\Serializer;

/**
 * Class JsonApiMetaTrait
 * @package FSerializerBundle\Serializer
 */
trait JsonApiMetaTrait
{
    /**
     * The meta data array.
     *
     * @var array
     */
    protected $meta;

    /**
     * Get the meta.
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Set the meta data array.
     *
     * @param array $meta
     *
     * @return $this
     */
    public function setMeta(array $meta)
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Add meta data.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addMeta($key, $value)
    {
        $this->meta[$key] = $value;
        return $this;
    }
}