<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Inline @error messages are easy to miss on a long form — this adds a toast
 * the moment validation fails, so "why didn't this submit?" has an answer
 * beyond scrolling to find a small red hint.
 *
 * Deliberately does NOT let ValidationException propagate: Livewire drops any
 * $this->js() effects queued earlier in an action that ends up throwing —
 * catch-then-rethrow still loses the toast, even though the error bag itself
 * renders fine either way. Instead this sets the error bag itself and returns
 * false, so the whole request stays on Livewire's normal successful-response
 * path (where effects DO survive) — callers just check the return value.
 */
trait ValidatesWithToast
{
    protected function validateOrToast(array $rules, array $messages = [], array $attributes = []): array|false
    {
        try {
            return $this->validate($rules, $messages, $attributes);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->js("showToast('Please fill in the highlighted field(s) before continuing.')");

            return false;
        }
    }
}
