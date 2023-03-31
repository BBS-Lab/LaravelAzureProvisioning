<?php

namespace BBSLab\LaravelAzureProvisioning\Controllers;

use BBSLab\LaravelAzureProvisioning\Utils\SCIMConstantsV2;
use Illuminate\Routing\Controller;
use Tmilos\ScimSchema\Builder\SchemaBuilderV2;

class SchemaController extends Controller
{
    public function index()
    {
        $schemas = [];
        $schema = (new SchemaBuilderV2())->get(SCIMConstantsV2::SCHEMA_USER);
        if ($schema == null) {
            dd("Schema [".SCIMConstantsV2::SCHEMA_USER."] not found");
        }
        $schema->setDescription("User Account");
        $schema->getMeta()->setLocation(route('AzureProvisioning.Schemas', ['id' => '23']));
        $schemas[] = $schema->serializeObject();

        return (collect($schemas));
    }
}
