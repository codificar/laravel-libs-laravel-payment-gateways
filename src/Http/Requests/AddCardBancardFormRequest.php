<?php

namespace Codificar\PaymentGateways\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Provider;
use User;

/**
 * Class AddCardBancardFormRequest
 *
 * @package MotoboyApp
 *
 * @author  André Gustavo <andre.gustavo@codificar.com.br>
 */
class AddCardBancardFormRequest extends FormRequest
{
    public $user;
    public $provider;
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
        if (!$this->user && request()->user_id)
            $this->user = User::find(request()->user_id);
        else
            $this->user = null;

        if (!$this->provider && request()->provider_id)
            $this->provider = Provider::find(request()->provider_id);
        else
            $this->provider = null;

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
