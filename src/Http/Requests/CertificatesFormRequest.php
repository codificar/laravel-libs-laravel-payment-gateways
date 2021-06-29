<?php

namespace Codificar\PaymentGateways\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CertificatesFormRequest extends FormRequest
{
    public $crt;
    public $key;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if (!$this->crt && request()->crt && request()->crt != true)
            $this->crt = request()->crt;
        else
            $this->crt = null;

        if (!$this->key && request()->key && request()->key != true)
            $this->key = request()->key;
        else
            $this->key = null;

        return [];
    }

    public function messages()
    {
        return [];
    }

    /**
     * Retorna um json caso a validação falhe.
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
                'error_code' => \ApiErrors::REQUEST_FAILED
            ])
        );
    }
}
