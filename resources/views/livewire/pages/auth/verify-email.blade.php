<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.guest-layout')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('my-courses.index', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-slate-600">
        {{ __('Before you begin, please verify your email using the link we just sent. If it did not arrive, we can send another one.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="vc-alert vc-alert-success mb-4">
            {{ __('A new verification email has been sent.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Resend verification email') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="vc-link">
            {{ __('Sign out') }}
        </button>
    </div>
</div>
