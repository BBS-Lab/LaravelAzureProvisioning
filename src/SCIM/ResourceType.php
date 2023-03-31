<?php

namespace BBSLab\LaravelAzureProvisioning\SCIM;

use BBSLab\LaravelAzureProvisioning\Utils\SCIMConstantsV2;
use Illuminate\Contracts\Support\Jsonable;

class ResourceType implements Jsonable
{
    public $id;
    public $name;
    public $type;
    public $description;
    public $schema;
    public $schemaExtensions;

    public function __construct($id, $name, $type, $description, $schema, $schemaExtensions = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->schema = $schema;
        $this->schemaExtensions = $schemaExtensions;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray()
    {
        return [
            "schemas" => [SCIMConstantsV2::SCHEMA_RESOURCE_TYPE],
            "id" => $this->id,
            "name" => $this->name,
            "endpoint" => route('AzureProvisioning.Resources', ['resourceType' => $this->type]),
            "description" => $this->description,
            "schema" => $this->schema,
            "schemaExtensions" => $this->schemaExtensions,
            "meta" => [
                "location" => route('AzureProvisioning.ResourceType', ['id' => $this->id]),
                "resourceType" => "ResourceType"
            ]
        ];
    }
}
