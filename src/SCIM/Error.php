<?php

namespace BBSLab\LaravelAzureProvisioning\SCIM;

use Illuminate\Contracts\Support\Jsonable;
use BBSLab\LaravelAzureProvisioning\Utils\SCIMConstantsV2;

class Error implements Jsonable
{
    protected $detail;
    protected $status;
    protected $SCIMType;
    protected $errors;

    public function toJson($options = 0)
    {
        return json_encode(
            array_filter(
                [
                    "schemas" => [SCIMConstantsV2::SCHEMA_ERROR],
                    "detail" => $this->detail,
                    "status" => $this->status,
                    "scimType" => ($this->status === 400 ? $this->SCIMType : null),
                    // Not defined in SCIM v2.0
                    "error" => $this->errors
                ]
            ),
            $options
        );
    }

    public function __construct($detail, $status = "404", $SCIMType = "invalidValue")
    {
        $this->detail = $detail;
        $this->status = $status;
        $this->SCIMType = $SCIMType;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
        return $this;
    }
}
