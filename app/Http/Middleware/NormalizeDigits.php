<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Staff often type Bangla digits (১২,০০০) into number and phone fields; store them as ASCII. */
class NormalizeDigits
{
    private const FIELDS = [
        'course_fee', 'admission_fee', 'discount', 'amount', 'first_payment', 'capacity', 'score', 'max_score',
        'next_installment_amount', 'installments', 'sort', 'class_no', 'max_marks', 'marks', 'time_limit', 'due_days_before', 'mail_port', 'bkash_number', 'nagad_number', 'rocket_number', 'phone', 'whatsapp', 'guardian_phone', 'emergency_phone', 'nid',
    ];

    public function handle(Request $request, Closure $next)
    {
        $clean = [];
        foreach (self::FIELDS as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $value = strtr($value, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
                if (! in_array($field, ['phone', 'whatsapp', 'guardian_phone', 'emergency_phone', 'nid'], true)) {
                    $value = str_replace([',', ' ', '৳'], '', $value);
                }
                $clean[$field] = trim($value);
            }
        }
        // Nested number inputs: settings fee table and bulk marks.
        foreach (['fees', 'marks'] as $field) {
            if (is_array($request->input($field))) {
                $clean[$field] = $this->deep($request->input($field));
            }
        }
        $request->merge($clean);

        return $next($request);
    }

    private function deep(array $values): array
    {
        return array_map(fn ($v) => is_array($v) ? $this->deep($v) : (is_string($v)
            ? trim(str_replace([',', ' ', '৳'], '', strtr($v, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'])))
            : $v), $values);
    }
}
