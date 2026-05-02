<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AttendanceEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_clock_in_at' => ['required'],
            'requested_clock_out_at' => ['required'],
            'requested_note' => ['required', 'string', 'max:255'],
            'breaks.*.break_start_at' => ['nullable'],
            'breaks.*.break_end_at' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('requested_clock_in_at');
            $clockOut = $this->input('requested_clock_out_at');

            $clockInValid = $this->isValidTime($clockIn);
            $clockOutValid = $this->isValidTime($clockOut);

            // 出退勤の形式エラーを重複させない
            if ($clockIn && !$clockInValid && $clockOut && !$clockOutValid) {
                $validator->errors()->add('requested_clock_in_at', '出勤時間・退勤時間はHH:MM形式で入力してください');
            } elseif ($clockIn && !$clockInValid) {
                $validator->errors()->add('requested_clock_in_at', '出勤時間はHH:MM形式で入力してください');
            } elseif ($clockOut && !$clockOutValid) {
                $validator->errors()->add('requested_clock_out_at', '退勤時間はHH:MM形式で入力してください');
            }

            // 両方とも形式が正しいときだけ前後関係チェック
            if ($clockIn && $clockOut && $clockInValid && $clockOutValid) {
                $clockInTime = Carbon::createFromFormat('H:i', $clockIn);
                $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);

                if ($clockInTime->gte($clockOutTime)) {
                    if ($this->routeIs('attendance.correction_request.store')) {
                        $validator->errors()->add('requested_clock_in_at', '出勤時間が不適切な値です');
                    } else {
                        $validator->errors()->add('requested_clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                    }
                }
            }

            foreach ($this->input('breaks', []) as $index => $break) {
                $breakStart = $break['break_start_at'] ?? null;
                $breakEnd = $break['break_end_at'] ?? null;

                // 片方だけ入力
                if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                    $validator->errors()->add("breaks.$index.break_start_at", '休憩開始時間と休憩終了時間を入力してください');
                    continue;
                }

                // 両方空欄は無視
                if (!$breakStart && !$breakEnd) {
                    continue;
                }

                $breakStartValid = $this->isValidTime($breakStart);
                $breakEndValid = $this->isValidTime($breakEnd);

                // 休憩の形式エラーも重複させない
                if (!$breakStartValid && !$breakEndValid) {
                    $validator->errors()->add("breaks.$index.break_start_at", '休憩時間はHH:MM形式で入力してください');
                    continue;
                }

                if (!$breakStartValid) {
                    $validator->errors()->add("breaks.$index.break_start_at", '休憩開始時間はHH:MM形式で入力してください');
                    continue;
                }

                if (!$breakEndValid) {
                    $validator->errors()->add("breaks.$index.break_end_at", '休憩終了時間はHH:MM形式で入力してください');
                    continue;
                }

                // 出退勤が正しいときだけ比較
                if ($clockOutValid) {
                    $breakStartTime = Carbon::createFromFormat('H:i', $breakStart);
                    $breakEndTime = Carbon::createFromFormat('H:i', $breakEnd);
                    $clockOutTime = Carbon::createFromFormat('H:i', $clockOut);

                    if ($breakStartTime->gte($clockOutTime)) {
                        $validator->errors()->add("breaks.$index.break_start_at", '休憩時間が不適切な値です');
                    }

                    if ($breakEndTime->gt($clockOutTime)) {
                        $validator->errors()->add("breaks.$index.break_end_at", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'requested_clock_in_at.required' => '出勤時間を入力してください',
            'requested_clock_out_at.required' => '退勤時間を入力してください',
            'requested_note.required' => '備考を記入してください',
            'requested_note.max' => '備考は255文字以内で入力してください',
        ];
    }

    private function isValidTime(?string $value): bool
    {
        if (!$value) {
            return false;
        }

        try {
            return Carbon::createFromFormat('H:i', $value)->format('H:i') === $value;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
