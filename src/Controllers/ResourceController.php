<?php

namespace BBSLab\LaravelAzureProvisioning\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use BBSLab\LaravelAzureProvisioning\Exceptions\AzureProvisioningException;
use BBSLab\LaravelAzureProvisioning\Resources\ResourceType;
use BBSLab\LaravelAzureProvisioning\SCIM\ListResponse;
use BBSLab\LaravelAzureProvisioning\Utils\AzureHelper;
use BBSLab\LaravelAzureProvisioning\Utils\SCIMConstantsV2;
use Tmilos\ScimFilterParser\Error\FilterException;
use Tmilos\ScimFilterParser\Parser;
use Tmilos\ScimFilterParser\Mode;

class ResourceController extends Controller
{
    public function create(Request $request, ResourceType $resourceType)
    {
        $resourceObject = $this->createObject($request, $resourceType);

        // event(new Create($resourceObject, $resourceType));

        return AzureHelper::objectToSCIMCreateResponse($resourceObject, $resourceType);
    }

    public function show(Request $request, ResourceType $resourceType, Model $resourceObject)
    {
        // event(new Get($resourceObject, $resourceType));

        return AzureHelper::objectToSCIMResponse(
            $resourceObject,
            $resourceType,
            is_null($request->input('attributes')) ? [] : explode(',', $request->input('attributes')),
            is_null($request->input('excludedAttributes')) ? [] : explode(',', $request->input('excludedAttributes')),
        );
    }

    public function delete(Request $request, ResourceType $resourceType, Model $resourceObject)
    {
        $resourceObject->delete();

        // event(new Delete($resourceObject, $resourceType));

        return response(null, 204);
    }

    public function update(Request $request, ResourceType $resourceType, Model $resourceObject)
    {
        $input = $request->input();

        if (!self::isAllowed($request, 'PATCH', $input, $resourceType, $resourceObject)) {
            throw new AzureProvisioningException("This is not allowed.");
        }

        if ($input['schemas'] !== [SCIMConstantsV2::MESSAGE_PATCH_OP]) {
            throw (new AzureProvisioningException(
                sprintf(
                    'Invalid schema "%s". MUST be "%s"',
                    json_encode($input['schemas']),
                    SCIMConstantsV2::MESSAGE_PATCH_OP
                )
            ));
        }

        if (isset($input[SCIMConstantsV2::MESSAGE_PATCH_OP.':Operations'])) {
            $input['Operations'] = $input[SCIMConstantsV2::MESSAGE_PATCH_OP.':Operations'];
            unset($input[SCIMConstantsV2::MESSAGE_PATCH_OP.':Operations']);
        }

        // $oldObject = $resourceObject;

        foreach ($input['Operations'] as $operation) {
            $resourceObject = $resourceType->patch($operation, $resourceObject);
        }

        // event(new Patch($resourceObject, $oldObject, $resourceType));

        return AzureHelper::objectToSCIMResponse($resourceObject, $resourceType);
    }

    public function replace(Request $request, ResourceType $resourceType, Model $resourceObject, $isMe = false)
    {
        if (!self::isAllowed($request, 'PUT', $request->input(), $resourceType, null)) {
            throw new AzureProvisioningException('This is not allowed');
        }

        // $oldObject = $resourceObject;

        $validatedInput = $this->validateSCIM($resourceType, $request->input(), $resourceObject);
        $resourceObject = $resourceType->replaceFromSCIM($validatedInput, $resourceObject);

        // event(new Replace($resourceObject, $oldObject, $resourceType));

        return AzureHelper::objectToSCIMResponse($resourceObject, $resourceType);
    }

    public function index(Request $request, ResourceType $resourceType)
    {
        $model = $resourceType->getModel();

        // A value less than 1 shall be interpreted as 1.
        $startIndex = max(1, intVal($request->input('startIndex', 0)));
        // A negative value shall be interpreted as "0".
        // A value of "0" indicates that no resource results are to be returned except for "totalResults".
        $count = max(0, intVal($request->input('count', 10)));

        $sortBy = is_null($request->input('sortby')) ? ''
                    : $resourceType->getMappingForAttribute($request->input('sortby')) ;

        $resourceObjectBase = $model::when(
            $filter = $request->input('filter'),
            function ($query) use ($filter, $resourceType) {
                $parser = new Parser(Mode::FILTER());

                try {
                    $node = $parser->parse($filter);

                    AzureHelper::filterToQuery($resourceType, $query, $node);
                } catch (FilterException $e) {
                    throw (new AzureProvisioningException($e->getMessage()))
                        ->setCode(400)
                        ->setSCIMType('invalidFilter');
                }
            }
        );

        $resourceObjects = $resourceObjectBase->skip($startIndex - 1)->take($count);
        $resourceObjects = $resourceObjects->with(config('azureprovisioning.'.$resourceType->getName().'.relations'));

        if ($sortBy != null) {
            $direction = $request->input('sortorder') == 'descending' ? 'desc' : 'asc';
            $resourceObjects = $resourceObjects->orderBy($sortBy, $direction);
        }
        $resourceObjects = $resourceObjects->get();

        return new ListResponse(
            $resourceObjects,
            $startIndex,
            $resourceObjectBase->count(),
            is_null($request->input('attributes')) ? [] : explode(',', $request->input('attributes')),
            is_null($request->input('excludedAttributes')) ? [] : explode(',', $request->input('excludedAttributes')),
            $resourceType
        );
    }


    public function createObject(Request $request, ResourceType $resourceType, $allowAlways = false)
    {
        $input = $request->input();
        if (!isset($input['schemas']) || !is_array($input['schemas'])) {
            throw (new AzureProvisioningException('Missing a valid schemas-attribute.'))->setCode(400);
        }

        $flattened = self::validateSCIM($resourceType, $input, null);

        if (!$allowAlways && !self::isAllowed($request, 'POST', $flattened, $resourceType, null)) {
            throw (new AzureProvisioningException('This is not allowed'))->setCode(403);
        }

        $resourceObject = $resourceType->createFromSCIM($flattened);

        return $resourceObject;
    }

    protected static function isAllowed(
        Request $request,
        $operation,
        array $attributes,
        ResourceType $resourceType,
        ?Model $resourceObject
    ) {
        // TODO:
        return true;
    }

    protected static function replaceKeys(array $input)
    {
        $return = [];
        foreach ($input as $key => $value) {
            if (strpos($key, '_') > 0) {
                $key = str_replace('___', '.', $key);
            }

            if (is_array($value)) {
                $value = self::replaceKeys($value);
            }

            $return[$key] = $value;
        }

        return $return;
    }

    protected static function validateSCIM(ResourceType $resourceType, $input, ?Model $resourceObject)
    {
        $validations = $resourceType->getValidations();

        $validator = Validator::make($input, $validations);

        if ($validator->fails()) {
            $e = $validator->errors();

            throw (new AzureProvisioningException('Invalid Data!'))
                ->setCode(400)
                ->setSCIMType('invalidSyntax')
                ->setErrors($e);
        }

        return Arr::dot($validator->validated());
    }
}
