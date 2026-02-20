<div class="vc-panel space-y-4 p-6">
    <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Enrollment</p>
    <p class="text-sm text-slate-600">
        @if ($isPreorderMode ?? false)
            Reserve now and you’ll be charged automatically when the course is released.
        @elseif ($course->is_free)
            Get access now{{ $course->free_access_mode === 'claim_link' ? ' with a secure claim link.' : '.' }}
        @else
                Complete checkout to unlock this course immediately.
        @endif
    </p>

    @if ($isPreorderMode ?? false)
        @error('preorder')
            <p class="vc-error">{{ $message }}</p>
        @enderror

        @if ($course->release_at)
            <p class="text-xs text-slate-600">
                Release date:
                <span class="font-semibold text-slate-800">{{ $course->release_at->toFormattedDateString() }}</span>
            </p>
        @endif

        @if ($isPreorderWindowActive ?? false)
            <form method="POST" action="{{ route('preorder.start', $course) }}" class="space-y-3">
                @csrf
                <div>
                    <label for="email" class="vc-label">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', auth()->user()?->email) }}"
                        class="vc-input"
                        placeholder="you@example.com"
                        @auth
                            readonly
                        @endauth />
                    @error('email')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="vc-btn-primary w-full justify-center py-2.5">Reserve preorder</button>
            </form>
        @else
            <p class="vc-help">Preorders are currently unavailable for this course.</p>
        @endif
    @else
        <form
            method="POST"
            action="{{ route('checkout.start', $course) }}"
            class="space-y-3"
            x-data="{ isGift: {{ old('is_gift') ? 'true' : 'false' }} }">
            @csrf

            @guest
                <div>
                    <label for="email" class="vc-label">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        value="{{ old('email') }}"
                        class="vc-input"
                        placeholder="you@example.com" />
                    @error('email')
                        <p class="vc-error">{{ $message }}</p>
                    @enderror
                </div>
            @endguest

            @if ($giftsEnabled)
                <div class="vc-panel-soft p-3">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                        <input
                            type="checkbox"
                            name="is_gift"
                            value="1"
                            class="rounded border-slate-300"
                            @checked(old('is_gift'))
                            x-model="isGift" />
                        Gift this course
                    </label>
                    <p class="vc-help">Recipient will get a claim link after payment confirmation.</p>
                </div>

                <div class="vc-panel-soft space-y-3 p-3" x-show="isGift" x-cloak>
                    <div>
                        <label for="recipient_email" class="vc-label">Recipient email</label>
                        <input
                            id="recipient_email"
                            name="recipient_email"
                            type="email"
                            value="{{ old('recipient_email') }}"
                            class="vc-input"
                            placeholder="friend@example.com" />
                        @error('recipient_email')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="recipient_name" class="vc-label">Recipient name (optional)</label>
                        <input
                            id="recipient_name"
                            name="recipient_name"
                            type="text"
                            value="{{ old('recipient_name') }}"
                            class="vc-input"
                            placeholder="Jane Doe" />
                    </div>

                    <div>
                        <label for="gift_message" class="vc-label">Message (optional)</label>
                        <textarea
                            id="gift_message"
                            name="gift_message"
                            rows="3"
                            maxlength="500"
                            class="vc-input"
                            placeholder="Enjoy this course!">
{{ old('gift_message') }}</textarea
                        >
                        @error('gift_message')
                            <p class="vc-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            @unless ($course->is_free)
                <div>
                    <label for="promotion_code" class="vc-label">Promotion code (optional)</label>
                    <input
                        id="promotion_code"
                        name="promotion_code"
                        type="text"
                        value="{{ old('promotion_code') }}"
                        class="vc-input"
                        placeholder="promo_xxx" />
                </div>
            @endunless

            <button type="submit" class="vc-btn-primary w-full justify-center py-2.5">
                @if ($course->is_free)
                    {{ $giftsEnabled ? 'Get free access' : 'Enroll for free' }}
                @else
                    {{ $giftsEnabled ? 'Continue to secure checkout' : 'Buy course now' }}
                @endif
            </button>
        </form>
    @endif

    @if (($subscriptionsEnabled ?? false) && !$course->is_subscription_excluded && !$course->is_free && !($isPreorderMode ?? false))
        <div class="border-t border-slate-200 pt-4">
            <p class="text-xs font-semibold tracking-[0.14em] text-slate-500 uppercase">Or subscribe</p>
            <p class="mt-1 text-sm text-slate-600">Get platform-wide access with a monthly or yearly plan.</p>
            @error('subscription')
                <p class="vc-error mt-2">{{ $message }}</p>
            @enderror

            @auth
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <form method="POST" action="{{ route('checkout.subscription.start') }}">
                        @csrf
                        <input type="hidden" name="interval" value="monthly" />
                        <button
                            type="submit"
                            class="vc-btn-secondary w-full justify-center"
                            @disabled(!($subscriptionMonthlyPriceId ?? null))>
                            Subscribe monthly
                        </button>
                    </form>

                    <form method="POST" action="{{ route('checkout.subscription.start') }}">
                        @csrf
                        <input type="hidden" name="interval" value="yearly" />
                        <button
                            type="submit"
                            class="vc-btn-secondary w-full justify-center"
                            @disabled(!($subscriptionYearlyPriceId ?? null))>
                            Subscribe yearly
                        </button>
                    </form>
                </div>
                @if (!($subscriptionMonthlyPriceId ?? null) || !($subscriptionYearlyPriceId ?? null))
                    <p class="vc-help mt-2">Subscription checkout is not fully configured yet.</p>
                @endif
            @else
                <p class="mt-3 text-sm text-slate-600">
                    <a href="{{ route('login') }}" class="vc-link">Sign in</a>
                    to start a subscription.
                </p>
            @endauth
        </div>
    @endif
</div>
