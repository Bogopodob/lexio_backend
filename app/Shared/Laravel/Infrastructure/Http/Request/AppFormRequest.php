<?php

namespace App\Shared\Laravel\Infrastructure\Http\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base Form Request with header validation support
 */
abstract class AppFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for headers.
     * Override this method in child classes to add header validation.
     *
     * @return array<string, mixed>
     */
    protected function headerRules(): array
    {
        return [];
    }

    /**
     * Get the validator instance with header validation.
     */
    protected function getValidatorInstance(): Validator
    {
        $validator = parent::getValidatorInstance();

        // Add header validation after main validation
        $validator->after(function ($validator) {
            $headerRules = $this->headerRules();

            foreach ($headerRules as $header => $rules) {
                $value = $this->header($header);
                $headerValidator = validator([$header => $value], [$header => $rules]);

                if ($headerValidator->fails()) {
                    foreach ($headerValidator->errors()->all() as $error) {
                        $validator->errors()->add($header, $error);
                    }
                }
            }
        });

        return $validator;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->all();
        $errorMessage = implode('\n', $errors);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => $errorMessage,
                'errors' => $validator->errors()->toArray(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
