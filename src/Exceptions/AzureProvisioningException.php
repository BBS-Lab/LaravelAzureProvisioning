<?php

namespace BBSLab\LaravelAzureProvisioning\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use BBSLab\LaravelAzureProvisioning\SCIM\Error;

class AzureProvisioningException extends Exception
{
    protected $SCIMType = "invalidValue";
    protected $httpCode = 404;

    protected $errors = [];

    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function setSCIMType($SCIMType) : AzureProvisioningException
    {
        $this->SCIMType = $SCIMType;
        return $this;
    }

    public function setCode($code) : AzureProvisioningException
    {
        $this->httpCode = $code;
        return $this;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    public function report()
    {
        Log::debug(
            sprintf(
                "Validation failed. Errors: %s\n\nMessage: %s\n\nBody: %s",
                json_encode($this->errors, JSON_PRETTY_PRINT),
                $this->getMessage(),
                request()->getContent()
            )
        );
    }

    public function render()
    {
        return response(
            (new Error($this->getMessage(), $this->httpCode, $this->SCIMType))->setErrors($this->errors),
            $this->httpCode
        );
    }
}
