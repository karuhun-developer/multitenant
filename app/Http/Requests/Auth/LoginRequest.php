<?php

namespace App\Http\Requests\Auth;

use App\Models\Tenant;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        RateLimiter::hit($this->throttleKey());

        $host = $this->getHost();

        // Main domain login
        if(!auth()->check()) {
            if($host === config('app.main_domain')) {
                $this->login();
            }
        }

        // Custom domain & subdomain login
        if(!auth()->check()) {
            $this->customDomainLogin($host);
            $this->subDomainLogin($host);
        }

        RateLimiter::clear($this->throttleKey());
    }

    // Main domain login
    protected function login() {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }
    }

    // Custom domain login
    protected function customDomainLogin($domain) {
        $tenant = Tenant::where('domain', $domain)->first();

        // If tenant is found, continue
        if ($tenant) {
            // Check user is in tenant
            $user = $tenant->users()->where('email', $this->string('email'))->first();

            // If user is not found, throw exception
            if (!$user) throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);

            $this->login();
        }
    }

    // Subdomain login
    protected function subDomainLogin($domain) {
        $host = explode('.', $domain);
        $subdomain = $host[0];

        // If subdomain is not empty, check if it is a valid tenant
        $tenant = Tenant::where('subdomain', $subdomain)->first();

        // If tenant is not found, throw exception
        if (!$tenant) throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);

        // Check user is in tenant
        $user = $tenant->users()->where('email', $this->string('email'))->first();

        // If user is not found, throw exception
        if (!$user) throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);

        $this->login();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
