<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class LoginRequest extends FormRequest {
    public function authorize() {
        return true;
    }

    public function rules() {
        // اگر hash وجود داشت فقط آن را اعتبارسنجی کنید
        if ( $this->has('hash') ) {
            return [
                'hash' => 'required|string',
            ];
        }

        // در غیر این صورت نام کاربری و رمز عبور را اعتبارسنجی کنید
        return [
            'username' => 'required',
            'password' => 'required',
        ];
    }

    public function messages() {
        return [
            'hash.required'     => 'ارسال هش الزامی است',
            'username.required' => 'وارد کردن نام کاربری الزامی است',
            'password.required' => 'وارد کردن رمز عبور الزامی است',
        ];
    }

    protected function failedValidation( Validator $validator ) {
        $errors = $validator->errors();

        // اگر خطا مربوط به hash باشد
        if ( $errors->has('hash') ) {
            throw new HttpResponseException(response()->json([
                'message' => 'ارسال هش الزامی است',
            ], 422));
        }

        // اگر خطا مربوط به نام کاربری و رمز عبور باشد
        if ( $errors->has('username') && $errors->has('password') ) {
            throw new HttpResponseException(response()->json([
                'message' => 'وارد کردن نام کاربری و رمز عبور الزامی است',
            ], 422));
        }

        throw new HttpResponseException(response()->json([
            'message' => implode(' ', $validator->errors()->all()),
        ], 422));
    }
}