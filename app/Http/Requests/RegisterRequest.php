<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages() {
        return [
            'name.required'                  => 'وارد کردن نام الزامی است',
            'name.string'                    => 'نام باید به صورت متن باشد',
            'name.max'                       => 'نام نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'username.required'              => 'وارد کردن نام کاربری الزامی است',
            'username.string'                => 'نام کاربری باید به صورت متن باشد',
            'username.max'                   => 'نام کاربری نمی‌تواند بیشتر از 255 کاراکتر باشد',
            'username.unique'                => 'این نام کاربری قبلا ثبت شده است',
            'password.required'              => 'وارد کردن رمز عبور الزامی است',
            'password.string'                => 'رمز عبور باید به صورت متن باشد',
            'password.min'                   => 'رمز عبور باید حداقل 6 کاراکتر باشد',
            'password.confirmed'             => 'تأیید رمز عبور با رمز عبور وارد شده مطابقت ندارد',
            'password_confirmation.required' => 'تکرار رمز عبور الزامی است',
            'password_confirmation.string'   => 'تکرار رمز عبور باید به صورت متن باشد',
            'password_confirmation.min'      => 'تکرار رمز عبور باید حداقل 6 کاراکتر باشد',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation( Validator $validator ) {
        $errors = $validator->errors();

        // اگر هم نام کاربری و هم رمز عبور خطا داشته باشند
        if ( $errors->has('username') && $errors->has('password') ) {
            throw new HttpResponseException(response()->json([
                'message' => 'وارد کردن نام کاربری و رمز عبور الزامی است',
            ], 422));
        }

        // برای سایر خطاها
        throw new HttpResponseException(response()->json([
            'message' => implode(' ', $validator->errors()->all()),
        ], 422));
    }
}
