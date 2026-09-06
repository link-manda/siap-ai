@if (session()->has('success') || session()->has('error') || session()->has('danger') || session()->has('warning') || session()->has('info') || session()->has('status'))
    <script>
        (function() {
            const triggerFlashes = function() {
                if (typeof window.SiapToast !== 'function') {
                    setTimeout(triggerFlashes, 50);
                    return;
                }

                @if (session()->has('success'))
                    window.SiapToast(@json(session('success')), 'success', 'Berhasil', 5000);
                @endif

                @if (session()->has('error'))
                    window.SiapToast(@json(session('error')), 'danger', 'Kesalahan', 0);
                @endif

                @if (session()->has('danger'))
                    window.SiapToast(@json(session('danger')), 'danger', 'Peringatan', 0);
                @endif

                @if (session()->has('warning'))
                    window.SiapToast(@json(session('warning')), 'warning', 'Perhatian', 6000);
                @endif

                @if (session()->has('info'))
                    window.SiapToast(@json(session('info')), 'info', 'Informasi', 5000);
                @endif

                @if (session()->has('status'))
                    window.SiapToast(@json(session('status')), 'info', 'Status', 5000);
                @endif
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', triggerFlashes);
            } else {
                setTimeout(triggerFlashes, 50);
            }

            document.addEventListener('livewire:navigated', triggerFlashes);
        })();
    </script>
@endif
