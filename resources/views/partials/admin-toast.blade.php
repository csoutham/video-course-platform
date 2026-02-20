@php
    $message = session('status') ?? session('error');
    $isError = session()->has('error');
@endphp

@if (request()->routeIs('admin.*') && is_string($message) && trim($message) !== '')
    <div
        class="pointer-events-none fixed top-4 right-4 z-[70] w-[min(92vw,420px)]"
        data-admin-toast-container>
        <div
            class="vc-toast {{ $isError ? 'vc-toast-error' : 'vc-toast-success' }} pointer-events-auto"
            role="status"
            aria-live="polite"
            data-admin-toast>
            <p class="pr-7 text-sm leading-relaxed">{{ $message }}</p>
            <button
                type="button"
                class="absolute top-2 right-2 rounded-md p-1 text-current/80 hover:bg-black/5"
                aria-label="Dismiss notification"
                data-admin-toast-close>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    <script>
        (() => {
            const toast = document.querySelector('[data-admin-toast]');
            if (!toast) return;

            const hide = () => {
                toast.classList.add('vc-toast-leave');
                window.setTimeout(() => {
                    toast.parentElement?.remove();
                }, 220);
            };

            window.setTimeout(hide, 4500);

            const close = toast.querySelector('[data-admin-toast-close]');
            close?.addEventListener('click', hide);
        })();
    </script>
@endif
