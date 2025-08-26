<?php

namespace App\Http\Requests;

use App\Helpers\ResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Request extends FormRequest
{

    /**
     * Determine if the team is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // all requests are authorized by default
    }

    /**
     * Custom error messages for validation.
     *
     * @return array
     */
    public function messages(): array
    {
        return [

            // Carrega mensagens de validação adicionais do arquivo de configuração
            ...Config::get('laravel-crud.validation.messages', []),

        ];
    }

    /**
     * Handle the validation failure.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ResponseHelper::error(
                last(explode('.', $this->route()->getName())),
                [
                    'request' => $this->all(),
                    'errors' => $validator->errors()->all()
                ]
            )
        );
    }

    protected function validateAction($action): bool
    {
        return Str::afterLast($this->route()->getName(), '.') === $action;
    }
}
