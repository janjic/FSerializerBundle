<?php

namespace FSerializerBundle\Serializer;

interface JsonApiElementInterface
{
    /**
     * Get the resources array.
     *
     * @return array
     */
    public function getResources();

    /**
     * Map to a "resource object" array.
     *
     * @return array
     */
    public function toArray();

    /**
     * Map to a "resource object identifier" array.
     *
     * @return array
     */
    public function toIdentifier();

    /**
     * Request a relationship to be included.
     *
     * @param string|array $relationships
     *
     * @return $this
     */
    public function relations($relationships);

    /**
     * Request a restricted set of fields.
     *
     * @param array|null $fields
     *
     * @return $this
     */
    public function attributes($fields);


    public function getSerializer(): JsonApiSerializationInterface;

    /**
     * @param $relationShips
     * @param $included
     * @param $name
     * @return mixed
     */
    public function getDenormalizedData($relationShips, $included, $name);
}