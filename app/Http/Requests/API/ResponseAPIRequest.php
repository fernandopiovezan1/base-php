<?php

namespace App\Http\Requests\API;

use InfyOm\Generator\Request\APIRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class ResponseAPIRequest
 * @package App\Http\Requests\API
 */
class ResponseAPIRequest extends APIRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     *
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        throw new HttpResponseException(response()->json([
            'errors' => $errors
        ], 422));
    }
}
