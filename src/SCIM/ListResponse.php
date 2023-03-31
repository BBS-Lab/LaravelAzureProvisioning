<?php

namespace BBSLab\LaravelAzureProvisioning\SCIM;

use Illuminate\Contracts\Support\Jsonable;
use BBSLab\LaravelAzureProvisioning\Resources\ResourceType;
use BBSLab\LaravelAzureProvisioning\Utils\AzureHelper;
use BBSLab\LaravelAzureProvisioning\Utils\SCIMConstantsV2;

class ListResponse implements Jsonable
{
    private $resourceObjects = [];
    private $startIndex;
    private $totalResults;
    private $attributes;
    private $excludedAttributes;
    private $resourceType = null;

    public function __construct(
        $resourceObjects,
        $startIndex = 1,
        $totalResults = 10,
        $attributes = [],
        $excludedAttributes = [],
        ResourceType $resourceType = null
    ) {
        $this->resourceType = $resourceType;
        $this->resourceObjects = $resourceObjects;
        $this->startIndex = $startIndex;
        $this->totalResults = $totalResults;
        $this->attributes = $attributes;
        $this->excludedAttributes = $excludedAttributes;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toSCIMArray(), $options);
    }

    public function toSCIMArray()
    {
        return [
            "totalResults" => $this->totalResults,
            "itemsPerPage" => count($this->resourceObjects),
            "startIndex" => $this->startIndex,
            "schemas" => [SCIMConstantsV2::MESSAGE_LIST_RESPONSE],
            "Resources" => AzureHelper::prepareReturn(
                $this->resourceObjects,
                $this->resourceType,
                $this->attributes,
                $this->excludedAttributes
            )
        ];
    }
}
