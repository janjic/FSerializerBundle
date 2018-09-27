<?php

namespace FSerializerBundle\Serializer;

/**
 * Class JsonApiLinkTrait
 * @package FSerializerBundle\Serializer
 */
trait JsonApiLinkTrait
{
    /**
     * The links array.
     *
     * @var array
     */
    protected $links;

    /**
     * Get the links.
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Set the links.
     *
     * @param array $links
     *
     * @return $this
     */
    public function setLinks(array $links)
    {
        $this->links = $links;
        return $this;
    }

    /**
     * Add a link.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function addLink($key, $value)
    {
        $this->links[$key] = $value;
        return $this;
    }
}
