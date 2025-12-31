<?php

namespace App\Http\Requests\cashier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
            'total_tax' => ['required', 'numeric'],
            'total_discount' => ['required', 'numeric'],

            'bundles' => ['array'],
            'bundles.*.id' => ['required', 'exists:bundles,id'],
            'bundles.*.variation' => ['required', 'array'],
            'bundles.*.variation.*.id' => ['required', 'exists:variation_products,id'],
            'bundles.*.variation.*.options' => ['required', 'array'],
            'bundles.*.variation.*.options.*' => ['required', 'exists:option_products,id'],

            'products' => ['array'],
            'products.*.product_id' => ['exists:products,id', 'required_if:order_pending,false'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id'],
            'products.*.addons.*.count' => ['numeric'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required_if:order_pending,false'],
            'products.*.note' => ['sometimes'],
            'financials' => ['required_if:order_pending,false', 'array'],
            'financials.*.id' => ['required_if:order_pending,false', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required_if:order_pending,false', 'numeric'], 
            'financials.*.description' => ['sometimes'], 
            'financials.*.transition_id' => ['sometimes'], 
            'free_discount' => ['numeric', 'sometimes'],
            'due_module' => ['numeric', 'sometimes'],
        ];
    }
}
