<div class="space-y-8">
    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Course</p>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $course->title }}</h1>
        <p class="max-w-3xl text-sm text-slate-600">{{ $course->description }}</p>

        <div class="space-y-4 border-t border-slate-100 pt-4">
            <p class="text-base font-semibold text-slate-900">
                ${{ number_format($course->price_amount / 100, 2) }} {{ strtoupper($course->price_currency) }}
            </p>

            <form method="POST" action="{{ route('checkout.start', $course) }}" class="space-y-3" x-data="{ isGift: {{ old('is_gift') ? 'true' : 'false' }} }">
                @csrf

                @guest
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            required
                            value="{{ old('email') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                            placeholder="you@example.com"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endguest

                @if ($giftsEnabled)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                            <input type="checkbox" name="is_gift" value="1" class="rounded border-slate-300" @checked(old('is_gift')) x-model="isGift">
                            Gift this course
                        </label>
                        <p class="mt-1 text-xs text-slate-500">When checked, access is granted to the recipient after they claim the gift.</p>
                    </div>

                    <div class="space-y-3 rounded-lg border border-slate-200 p-3" x-show="isGift" x-cloak>
                        <div>
                            <label for="recipient_email" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Recipient email</label>
                            <input
                                id="recipient_email"
                                name="recipient_email"
                                type="email"
                                value="{{ old('recipient_email') }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                placeholder="friend@example.com"
                            />
                            @error('recipient_email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="recipient_name" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Recipient name (optional)</label>
                            <input
                                id="recipient_name"
                                name="recipient_name"
                                type="text"
                                value="{{ old('recipient_name') }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                placeholder="Jane Doe"
                            />
                        </div>

                        <div>
                            <label for="gift_message" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Message (optional)</label>
                            <textarea
                                id="gift_message"
                                name="gift_message"
                                rows="3"
                                maxlength="500"
                                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                                placeholder="Enjoy this course!"
                            >{{ old('gift_message') }}</textarea>
                            @error('gift_message')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <div>
                    <label for="promotion_code" class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Promotion code (optional)</label>
                    <input
                        id="promotion_code"
                        name="promotion_code"
                        type="text"
                        value="{{ old('promotion_code') }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                        placeholder="promo_xxx"
                    />
                </div>

                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                    {{ $giftsEnabled ? 'Continue to checkout' : 'Buy course' }}
                </button>
            </form>
        </div>
    </div>

    <section class="space-y-4">
        <h2 class="text-xl font-semibold text-slate-900">Curriculum preview</h2>

        @forelse ($course->modules as $module)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">{{ $module->title }}</h3>

                @if ($module->lessons->isEmpty())
                    <p class="mt-2 text-sm text-slate-500">No published lessons in this module yet.</p>
                @else
                    <ol class="mt-3 space-y-2 text-sm text-slate-700">
                        @foreach ($module->lessons as $lesson)
                            <li class="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2">
                                <span>{{ $lesson->title }}</span>
                                @if ($lesson->duration_seconds)
                                    <span class="text-xs font-semibold text-slate-500">
                                        {{ gmdate('i:s', $lesson->duration_seconds) }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                Curriculum will be published soon.
            </div>
        @endforelse
    </section>
</div>
