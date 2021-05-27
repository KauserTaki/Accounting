<?php

namespace Modules\Parasut\Http\Requests;

use App\Abstracts\Http\FormRequest;

class Setting extends FormRequest
{
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
        $rules = [
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
            'username'      => 'required|string',
            'password'      => 'required|string',
            'company_id'    => 'required|integer',
            'redirect_uri'  => 'required|string'
        ];

        return $rules;
    }
}
