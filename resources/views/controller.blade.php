@extends('layouts.dashboard')

@section('title', 'Reset ESP32')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/controller.css') }}">
@endpush

@section('dashboard-content')
<div class="panel-page">
    {{-- Page Header --}}
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="section-kicker">Manajemen Perangkat</p>
                <h1>Reset ESP32</h1>
                <p>Kirim perintah restart ke mikrokontroler ESP32. Perangkat akan melakukan reboot dan kembali ke kondisi awal.</p>
            </div>
            <div class="datetime-widget">
                <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    {{-- ESP32 Reset Card --}}
    <div class="esp-reset-section">
        <div class="esp-card">
            <div class="esp-card-header">
                <div class="esp-card-icon">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div>
                    <h2 class="esp-card-title">Mikrokontroler ESP32 2U</h2>
                    <p class="esp-card-sub">ESP32 2U — Node Sensor SIGMA</p>
                </div>
                <span id="esp-conn-badge"
                      class="conn-badge {{ $espConnected ? 'connected' : 'disconnected' }}">
                    <span class="conn-badge-dot"></span>
                    {{ $espConnected ? 'Terhubung' : 'Terputus' }}
                </span>
            </div>

            <div class="esp-card-body">
                {{-- Info Block --}}
                <div class="esp-info-block">
                    <div class="esp-info-row">
                        <span class="esp-info-label">Data terakhir diterima</span>
                        <span class="esp-info-value" id="esp-last-data">{{ $lastDataAt }}</span>
                    </div>
                    <div class="esp-info-row">
                        <span class="esp-info-label">Status koneksi</span>
                        <span class="esp-info-value" id="esp-status-text">
                            {{ $espConnected ? 'Online — mengirim data' : 'Offline — tidak ada data masuk' }}
                        </span>
                    </div>
                </div>

                {{-- Warning Block --}}
                <div class="esp-warning-block">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Perhatian</strong>
                        <p>Reset akan menghentikan seluruh operasi sensor sementara. ESP32 akan reboot, melakukan kalibrasi ulang, dan kembali mengirim data secara otomatis. Proses ini membutuhkan waktu sekitar 15–30 detik.</p>
                    </div>
                </div>

                {{-- Reset Button --}}
                <div class="esp-action-area">
                    <button type="button" class="esp-reset-btn" id="btn-reset-esp32" onclick="resetEsp32()">
                        <i class="fa-solid fa-rotate-right"></i>
                        Reset ESP32
                    </button>
                    <p class="esp-action-hint">Perintah akan diantrekan dan dijalankan saat ESP32 polling berikutnya.</p>
                </div>

                <p id="esp-msg" class="ctrl-msg"></p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── Clock ──────────────────────────────────────────────────────────────────
    (function startClock() {
        function updateClock() {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            document.getElementById('realtime-clock').textContent =
                `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
            document.getElementById('realtime-date').textContent =
                `${days[now.getDay()]}, ${pad(now.getDate())} ${months[now.getMonth()]} ${now.getFullYear()}`;
        }
        updateClock();
        setInterval(updateClock, 1000);
    })();

    // ── API Config ─────────────────────────────────────────────────────────────
    const RESET_ESP_URL = @json(route('sensor-commands.reset-esp32'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── Show Feedback ──────────────────────────────────────────────────────────
    function showMsg(elId, text, type = 'success') {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = text;
        el.className = `ctrl-msg ${type}`;
        setTimeout(() => { el.textContent = ''; el.className = 'ctrl-msg'; }, 5000);
    }

    // ── Reset ESP32 ────────────────────────────────────────────────────────────
    async function resetEsp32() {
        if (!confirm('Anda yakin ingin mereset ESP32? Semua sensor akan reboot.')) return;

        const btn = document.getElementById('btn-reset-esp32');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim perintah...';

        try {
            const res = await fetch(RESET_ESP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            const data = await res.json();
            if (data.success) {
                showMsg('esp-msg', data.message ?? 'Perintah reset ESP32 berhasil dikirim.', 'success');
            } else {
                showMsg('esp-msg', data.message ?? 'Gagal mengirim perintah reset.', 'error');
            }
        } catch (e) {
            showMsg('esp-msg', 'Koneksi gagal. Periksa jaringan Anda.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate-right"></i> Reset ESP32';
        }
    }

    // ── Live Connection Status Polling (every 15s) ─────────────────────────────
    const STATE_URL = @json(route('panel.data.realtime'));

    async function pollConnectionStatus() {
        try {
            const res = await fetch(`${STATE_URL}?t=${Date.now()}`, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();

            const accelOk = data.currentAccel?.is_connected;
            const gpsOk = data.gps?.is_connected;
            const isConnected = accelOk || gpsOk;

            updateBadge('esp-conn-badge', isConnected);

            const statusText = document.getElementById('esp-status-text');
            if (statusText) {
                statusText.textContent = isConnected
                    ? 'Online — mengirim data'
                    : 'Offline — tidak ada data masuk';
            }

            // Update last data timestamp
            const lastDataEl = document.getElementById('esp-last-data');
            if (lastDataEl) {
                const accelTime = data.currentAccel?.sensor_time;
                const gpsTime = data.gps?.recorded_at;
                if (accelTime) lastDataEl.textContent = accelTime;
                else if (gpsTime) lastDataEl.textContent = gpsTime;
            }
        } catch (e) {
        } finally {
            setTimeout(pollConnectionStatus, 15000);
        }
    }

    function updateBadge(badgeId, isConnected) {
        const badge = document.getElementById(badgeId);
        if (!badge) return;
        if (isConnected) {
            badge.className = 'conn-badge connected';
            badge.innerHTML = '<span class="conn-badge-dot"></span> Terhubung';
        } else {
            badge.className = 'conn-badge disconnected';
            badge.innerHTML = '<span class="conn-badge-dot"></span> Terputus';
        }
    }

    pollConnectionStatus();
</script>
@endpush
