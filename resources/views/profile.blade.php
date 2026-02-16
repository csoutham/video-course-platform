<x-public-layout>
    <x-slot:title>Profile</x-slot>

    <div class="space-y-6">
        <div class="vc-heading-block">
            <p class="vc-eyebrow">Account</p>
            <h1 class="vc-title">Profile</h1>
            <p class="vc-subtitle">Manage your account details, password, and security settings.</p>
        </div>

        <div class="vc-panel p-5">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="vc-panel p-5">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="vc-panel p-5">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-public-layout>
