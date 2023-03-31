<?php

namespace BBSLab\LaravelAzureProvisioning\Controllers;

use BBSLab\LaravelAzureProvisioning\Utils\SCIMConstantsV2;
use Carbon\Carbon;
use Illuminate\Routing\Controller;

class ServiceProviderController extends Controller
{
    public function index()
    {
        return [
            "schemas" => [SCIMConstantsV2::SCHEMA_SERVICE_PROVIDER_CONFIG],
            "patch" => [
                "supported" => true,
            ],
            "bulk" => [
                "supported" => false,
            ],
            "filter" => [
                "supported" => true,
                "maxResults" => 100,
            ],
            "changePassword" => [
                "supported" => true,
            ],
            "sort" => [
                "supported" => true,
            ],
            "etag" => [
                "supported" => true,
            ],
            "authenticationSchemes" => [
                [
                    "name" => "OAuth Bearer Token",
                    "description" => "Authentication scheme using the OAuth Bearer Token Standard",
                    "sepcUri" => "http://www.rfc-editor.org/info/rfc6750",
                    "documentationUri" => "http://example.com/help/oauth.html",
                    "type" => "oauthbearertoken",
                    "primary" => true,
                ],
                [
                    "name" => "HTTP Basic",
                    "description" =>
                    "Authentication scheme using the HTTP Basic Standard",
                    "specUri" => "http://www.rfc-editor.org/info/rfc2617",
                    "documentationUri" => "http://example.com/help/httpBasic.html",
                    "type" => "httpbasic",
                ],
            ],
            "meta" => [
                "location" => config('azure-provisioning.routes.ServiceProviderConfig'),
                "resourceType" => "ServiceProviderConfig",
                "created" => Carbon::createFromTimestampUTC(filectime(__FILE__))->format('c'),
                "lastModified" => Carbon::createFromTimestampUTC(filemtime(__FILE__))->format('c'),
                "version" => sprintf('W/"%s"', sha1(filemtime(__FILE__))),
            ]
        ];
    }
}
