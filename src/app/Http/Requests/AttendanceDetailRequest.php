<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceDetailRequest extends FormRequest
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
    public function rules(): array
    {
        return [

            // 出勤・退勤
            'requested_clock_in' => [
                'required',
                'date_format:H:i',
            ],

            'requested_clock_out' => [
                'required',
                'date_format:H:i',
                'after:requested_clock_in',
            ],

            // 休憩開始
            'breaks.*.start' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:requested_clock_in',
                'before_or_equal:requested_clock_out',
            ],

            // 休憩終了
            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'after:breaks.*.start',
                'before_or_equal:requested_clock_out',
            ],

            // 備考
            'reason' => [
                'required',
            ],
        ];
    }
    public function messages(): array
    {
        return [

            // 出勤・退勤
            'requested_clock_out.after' =>
            '出勤時間もしくは退勤時間が不適切な値です',

            // フォーマット
            'requested_clock_in.date_format' =>
            '時間はHH:mm形式で入力してください',

            'requested_clock_out.date_format' =>
            '時間はHH:mm形式で入力してください',

            // 休憩
            'breaks.*.start.after_or_equal' =>
            '休憩時間が不適切な値です',

            'breaks.*.start.before_or_equal' =>
            '休憩時間が不適切な値です',

            'breaks.*.end.after' =>
            '休憩時間が不適切な値です',

            'breaks.*.end.before_or_equal' =>
            '休憩時間もしくは退勤時間が不適切な値です',

            'breaks.*.start.date_format' =>
            '時間はHH:mm形式で入力してください',

            'breaks.*.end.date_format' =>
            '時間はHH:mm形式で入力してください',

            // 備考
            'reason.required' =>
            '備考を記入してください',
        ];
    }
}