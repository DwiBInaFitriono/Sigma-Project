@extends('layouts.dashboard')

@section('title', 'Kontroller Sensor')

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
                <h1>Kontroller Sensor</h1>
                <p>Kendalikan status daya dan konfigurasi sensor secara terpusat. Perintah akan diantrekan dan dieksekusi saat ESP32 terhubung.</p>
            </div>
            <div class="datetime-widget">
                <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    {{-- Sensor Cards Grid --}}
    <div class="ctrl-page-grid">

        {{-- ── Accelerometer Card ────────────────────────────────────── --}}
        <div class="sensor-ctrl-card">
            <div class="sensor-ctrl-card-header">
                <div>
                    <h2 class="sensor-ctrl-card-title">Akselerometer</h2>
                    <p class="sensor-ctrl-card-sub">ADXL345 — Sensor Getaran 3-Axis</p>
                </div>
                <span id="accel-conn-badge"
                      class="conn-badge {{ $accelConnected ? 'connected' : 'disconnected' }}">
                    <span class="conn-badge-dot"></span>
                    {{ $accelConnected ? 'Terhubung' : 'Terputus' }}
                </span>
            </div>

            <p class="ctrl-last-update">Data terakhir: <strong id="accel-last-at">{{ $latestAccelAt }}</strong></p>

            {{-- Power Control --}}
            <div>
                <p class="ctrl-section-label">Status Daya</p>
                <div class="power-btn-group">
                    <button id="accel-btn-on" type="button" class="power-btn power-btn-on {{ $accelPower === 'on' ? 'active' : '' }}"
                            onclick="setSensorPower('accelerometer','on','accel-msg')">
                        <i class="fa-solid fa-power-off"></i> Hidupkan
                    </button>
                    <button id="accel-btn-off" type="button" class="power-btn power-btn-off {{ $accelPower === 'off' ? 'active' : '' }}"
                            onclick="setSensorPower('accelerometer','off','accel-msg')">
                        <i class="fa-solid fa-ban"></i> Matikan
                    </button>
                </div>
            </div>

            {{-- Sensitivity Sliders --}}
            <div>
                <p class="ctrl-section-label">Sensitivitas</p>
                <div class="sensitivity-group">
                    @foreach([['x', $sensitivity['x']], ['y', $sensitivity['y']], ['z', $sensitivity['z']]] as [$axis, $val])
                    <div class="sensitivity-row">
                        <span class="axis-label">{{ strtoupper($axis) }}</span>
                        <input type="range" id="sens-{{ $axis }}" class="sens-slider"
                               min="1" max="10" step="0.5" value="{{ $val }}">
                        <span class="sens-value-label" id="sens-{{ $axis }}-val">{{ number_format($val, 1) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="ctrl-action-row">
                <button type="button" class="ctrl-apply-btn" onclick="applySensitivity('accel-msg')">
                    <i class="fa-solid fa-sliders"></i> Terapkan Sensitivitas
                </button>
                <button type="button" class="ctrl-reset-btn" onclick="resetSensor('accelerometer','accel-msg')">
                    <i class="fa-solid fa-rotate-left"></i> Reset Default
                </button>
            </div>

            <p id="accel-msg" class="ctrl-msg"></p>
        </div>

        {{-- ── GPS Card ─────────────────────────────────────────────── --}}
        <div class="sensor-ctrl-card">
            <div class="sensor-ctrl-card-header">
                <div>
                    <h2 class="sensor-ctrl-card-title">GPS</h2>
                    <p class="sensor-ctrl-card-sub">NEO-6M — Modul Lokasi Satelit</p>
                </div>
                <span id="gps-conn-badge"
                      class="conn-badge {{ $gpsConnected ? 'connected' : 'disconnected' }}">
                    <span class="conn-badge-dot"></span>
                    {{ $gpsConnected ? 'Terhubung' : 'Terputus' }}
                </span>
            </div>

            <p class="ctrl-last-update">Data terakhir: <strong id="gps-last-at">{{ $latestGpsAt }}</strong></p>

            {{-- Power Control --}}
            <div>
                <p class="ctrl-section-label">Status Daya</p>
                <div class="power-btn-group">
                    <button id="gps-btn-on" type="button" class="power-btn power-btn-on {{ $gpsPower === 'on' ? 'active' : '' }}"
                            onclick="setSensorPower('gps','on','gps-msg')">
                        <i class="fa-solid fa-power-off"></i> Hidupkan
                    </button>
                    <button id="gps-btn-off" type="button" class="power-btn power-btn-off {{ $gpsPower === 'off' ? 'active' : '' }}"
                            onclick="setSensorPower('gps','off','gps-msg')">
                        <i class="fa-solid fa-ban"></i> Matikan
                    </button>
                </div>
            </div>

            {{-- GPS Info (no sliders) --}}
            <div class="dashboard-score-card" style="background: var(--sigma-surface-2); border-radius: var(--sigma-radius-btn); padding: 1.25rem;">
                <p class="ctrl-section-label" style="margin-bottom: 0.5rem;">Informasi</p>
                <p style="color: var(--sigma-muted); font-size: 0.9rem; line-height: 1.6; margin: 0;">
                    GPS tidak memiliki parameter sensitivitas yang dapat dikonfigurasi.
                    Anda hanya dapat menghidupkan/mematikan modul GPS dari sini.
                </p>
            </div>

            {{-- Actions --}}
            <div class="ctrl-action-row">
                <button type="button" class="ctrl-reset-btn" onclick="resetSensor('gps','gps-msg')">
                    <i class="fa-solid fa-rotate-left"></i> Reset Default
                </button>
            </div>

            <p id="gps-msg" class="ctrl-msg"></p>
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
    const POWER_URL  = @json(route('sensor-commands.power'));
    const SENS_URL   = @json(route('sensor-commands.sensitivity'));
    const RESET_URL  = @json(route('sensor-commands.reset'));
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── Slider Live Display ────────────────────────────────────────────────────
    ['x', 'y', 'z'].forEach(axis => {
        const slider = document.getElementById(`sens-${axis}`);
        const label  = document.getElementById(`sens-${axis}-val`);
        if (slider && label) {
            slider.addEventListener('input', () => {
                label.textContent = parseFloat(slider.value).toFixed(1);
            });
        }
    });

    // ── Show Feedback ──────────────────────────────────────────────────────────
    function showMsg(elId, text, type = 'success') {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = text;
        el.className = `ctrl-msg ${type}`;
        setTimeout(() => { el.textContent = ''; el.className = 'ctrl-msg'; }, 3500);
    }

    // ── Update Power UI ────────────────────────────────────────────────────────
    function updatePowerUI(sensor, state) {
        const prefix = sensor === 'accelerometer' ? 'accel' : 'gps';
        const btnOn  = document.getElementById(`${prefix}-btn-on`);
        const btnOff = document.getElementById(`${prefix}-btn-off`);
        if (btnOn)  btnOn.classList.toggle('active', state === 'on');
        if (btnOff) btnOff.classList.toggle('active', state === 'off');
    }

    // ── Power Toggle ───────────────────────────────────────────────────────────
    async function setSensorPower(sensorType, state, msgEl) {
        try {
            const res = await fetch(POWER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: sensorType, state }),
            });
            const data = await res.json();
            if (data.success) {
                updatePowerUI(sensorType, state);
                showMsg(msgEl, data.message, 'success');
            } else {
                showMsg(msgEl, 'Gagal mengirim perintah.', 'error');
            }
        } catch (e) {
            showMsg(msgEl, 'Koneksi gagal. Coba lagi.', 'error');
        }
    }

    // ── Apply Sensitivity ──────────────────────────────────────────────────────
    async function applySensitivity(msgEl) {
        const x = parseFloat(document.getElementById('sens-x').value);
        const y = parseFloat(document.getElementById('sens-y').value);
        const z = parseFloat(document.getElementById('sens-z').value);
        try {
            const res = await fetch(SENS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ x, y, z }),
            });
            const data = await res.json();
            showMsg(msgEl, data.message ?? 'Sensitivitas disimpan.', data.success ? 'success' : 'error');
        } catch (e) {
            showMsg(msgEl, 'Koneksi gagal. Coba lagi.', 'error');
        }
    }

    // ── Reset Default ──────────────────────────────────────────────────────────
    async function resetSensor(sensorType, msgEl) {
        if (!confirm(`Reset sensor ${sensorType === 'accelerometer' ? 'Akselerometer' : 'GPS'} ke pengaturan default?`)) return;
        try {
            const res = await fetch(RESET_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: sensorType }),
            });
            const data = await res.json();
            if (data.success) {
                updatePowerUI(sensorType, 'on');
                if (sensorType === 'accelerometer') {
                    ['x', 'y', 'z'].forEach(axis => {
                        const s = document.getElementById(`sens-${axis}`);
                        const l = document.getElementById(`sens-${axis}-val`);
                        if (s) s.value = 5;
                        if (l) l.textContent = '5.0';
                    });
                }
            }
            showMsg(msgEl, data.message ?? 'Sensor direset.', data.success ? 'success' : 'error');
        } catch (e) {
            showMsg(msgEl, 'Koneksi gagal. Coba lagi.', 'error');
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

            updateBadge('accel-conn-badge', data.currentAccel?.is_connected);
            updateBadge('gps-conn-badge', data.gps?.is_connected);

            if (data.currentAccel?.sensor_time) {
                const el = document.getElementById('accel-last-at');
                if (el) el.textContent = data.currentAccel.sensor_time;
            }
            if (data.gps?.recorded_at) {
                const el = document.getElementById('gps-last-at');
                if (el) el.textContent = data.gps.recorded_at;
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

    // Poll immediately then recursively every 15 seconds
    pollConnectionStatus();
</script>
@endpush
