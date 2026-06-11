<?php

namespace App\Modules\Imaging\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmImagingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_item_id' => 'nullable|integer|exists:invoice_items,id',
            'waive' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_item_id.exists' => __('imaging.validation.invoice_item_id_invalid'),
            'waive.boolean' => __('imaging.validation.waive_boolean'),
            'notes.max' => __('imaging.validation.notes_max'),
        ];
    }
}
