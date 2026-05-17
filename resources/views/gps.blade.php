@extends('layouts.dashboard')

@section('title', 'GPS NEO-6M')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/gps.css') }}">
@endpush

@section('dashboard-content')
<div class="panel-page">
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="content-subtitle">DATA SENSOR KHUSUS</p>
                <h1 class="content-title">GPS NEO-6M</h1>
                <p class="content-desc">Pantau lokasi perangkat dan koordinat satelit secara realtime dari OpenStreetMap.</p>
            </div>
            <div class="datetime-widget">
                <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    <div class="dashboard-grid">
        <div class="dashboard-main">
            <!-- Peta GPS Section -->
            <section id="sensor-gps-card" class="glow-card panel-card map-card" style="height: 100%; display: flex; flex-direction: column;">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Peta Lokasi Live</h2>
                        <p class="section-subtitle">Posisi terbaru perangkat berdasarkan satelit.</p>
                    </div>
                    <div class="live-badge" id="gps-live-badge">REALTIME</div>
                </div>

                <div id="gps-map" class="sensor-map" style="flex-grow: 1; min-height: 400px; border-radius: 8px; margin-top: 1rem; border: 1px solid rgba(214, 196, 176, 0.1);"></div>
            </section>
        </div>

        <aside class="dashboard-sidebar">
            <section class="glow-card panel-card summary-card">
                <div class="section-header">
                    <h2 class="section-title">Statistik GPS</h2>
                </div>
                <div class="summary-grid-vertical" style="display: grid; gap: 1rem;">
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Status Koneksi</p>
                        <p id="connectionStatus" class="dashboard-score-card-value" style="color: {{ $gps['is_connected'] ? 'var(--sigma-accent)' : 'var(--sigma-muted)' }};">{{ $gps['is_connected'] ? 'Terhubung' : 'Terputus' }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Fix Status</p>
                        <p id="gpsStatus" class="dashboard-score-card-value">{{ $gps['status'] }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Satelit Terkunci</p>
                        <p id="gpsSatellites" class="dashboard-score-card-value">{{ $gps['satellites'] }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Ketinggian (Altitude)</p>
                        <p id="gpsAltitude" class="dashboard-score-card-value">{{ number_format($gps['altitude'], 2) }} m</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Waktu Pembaruan</p>
                        <p id="gpsRecordedAt" class="dashboard-score-card-value" style="font-size: 1.25rem;">{{ $gps['recorded_at'] }}</p>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <!-- Grid Detail Koordinat Full Width -->
    <section class="glow-card panel-card" style="margin-top: 2rem;">
        <div class="section-header">
            <div>
                <h2 class="section-title">Detail Koordinat Geografis</h2>
            </div>
        </div>

        <div class="dashboard-info-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="dashboard-info-card">
                <p>Latitude</p>
                <strong id="gpsLatitude">{{ number_format($gps['latitude'], 7) }}</strong>
            </div>
            <div class="dashboard-info-card">
                <p>Longitude</p>
                <strong id="gpsLongitude">{{ number_format($gps['longitude'], 7) }}</strong>
            </div>
        </div>
    </section>

    <!-- GPS Log Table -->
    <section class="glow-card panel-card log-card" style="margin-top: 2rem;">
        <div class="section-header">
            <div>
                <h2 class="section-title">Log GPS — 5 Menit Terakhir</h2>
                <p class="section-subtitle">Riwayat data koordinat dan status GPS dari 5 menit terakhir.</p>
            </div>
            <span id="gps-log-badge" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; color: var(--sigma-muted); padding: 0.25rem 0.6rem; border: 1px solid var(--sigma-border); border-radius: 4px;">LOG</span>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Altitude (m)</th>
                        <th>Satelit</th>
                        <th class="text-right">Fix Status</th>
                    </tr>
                </thead>
                <tbody id="gps-log-body">
                    @forelse($gpsLog as $entry)
                        <tr>
                            <td class="text-muted">{{ $entry['time'] }}</td>
                            <td>{{ number_format($entry['latitude'], 7) }}</td>
                            <td>{{ number_format($entry['longitude'], 7) }}</td>
                            <td>{{ number_format($entry['altitude'], 2) }}</td>
                            <td>{{ $entry['satellites'] }}</td>
                            <td class="text-right">
                                <span style="font-weight: 700; color: {{ $entry['status'] === '3D FIX' ? '#22c55e' : ($entry['status'] === 'NO FIX' ? 'var(--sigma-muted)' : '#f59e0b') }};">
                                    {{ $entry['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted" style="text-align: center; padding: 2rem;">Belum ada data GPS dalam 5 menit terakhir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialDashboardData = {
            gps: @json($gps),
        };
        const initialGpsLog = @json($gpsLog);
        const dashboardDataUrl = @json(route('panel.data.realtime'));
        const logDataUrl = @json($logDataUrl);

        // Realtime map + stats: every 1 second
        const MAP_REFRESH_MS = 1000;
        // Log table: every 10 seconds
        const LOG_REFRESH_MS = 10000;

        let map = null;
        let marker = null;

        function updateClock() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const timeStr = `${hh}:${mm}:${ss}`;

            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dateStr = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()}`;

            setText('realtime-clock', timeStr);
            setText('realtime-date', dateStr);
        }

        function formatNumber(value, digits = 2) {
            const numericValue = Number(value);
            if (!Number.isFinite(numericValue)) return (0).toFixed(digits);
            return numericValue.toFixed(digits);
        }

        function setText(id, value) {
            const element = document.getElementById(id);
            if (element) { element.textContent = value; }
        }

        function renderMap(gps) {
            if (typeof L === 'undefined') return;

            const mapEl = document.getElementById('gps-map');
            if (!mapEl) return;

            const lat = Number(gps.latitude);
            const lng = Number(gps.longitude);

            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

            if (!map) {
                map = L.map('gps-map').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                marker = L.marker([lat, lng]).addTo(map);
            } else {
                map.setView([lat, lng], map.getZoom());
                if (marker) { marker.setLatLng([lat, lng]); }
            }
        }

        function applyDashboardData(data) {
            if (!data) return;

            const gps = data.gps || {};

            setText('gpsLatitude', formatNumber(gps.latitude, 7));
            setText('gpsLongitude', formatNumber(gps.longitude, 7));
            setText('gpsAltitude', formatNumber(gps.altitude, 2) + ' m');
            setText('gpsSatellites', String(gps.satellites ?? 0));
            setText('gpsStatus', gps.status || 'NO FIX');
            setText('gpsRecordedAt', gps.recorded_at || '--');

            const connectionEl = document.getElementById('connectionStatus');
            const liveBadgeEl = document.getElementById('gps-live-badge');
            if (connectionEl) {
                if (gps.is_connected) {
                    connectionEl.textContent = 'Terhubung';
                    connectionEl.style.color = 'var(--sigma-accent)';
                    if (liveBadgeEl) liveBadgeEl.style.display = 'inline-block';
                } else {
                    connectionEl.textContent = 'Terputus';
                    connectionEl.style.color = 'var(--sigma-muted)';
                    if (liveBadgeEl) liveBadgeEl.style.display = 'none';
                }
            }

            try { renderMap(gps); } catch (e) { console.warn('[SIGMA] Map error:', e); }
        }

        /**
         * Render GPS log table entries (5-minute history).
         */
        function renderGpsLogTable(logEntries) {
            const tbody = document.getElementById('gps-log-body');
            if (!tbody) return;

            if (!logEntries || logEntries.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-muted" style="text-align:center;padding:2rem;">Belum ada data GPS dalam 5 menit terakhir.</td></tr>`;
                return;
            }

            let html = '';
            logEntries.forEach((entry) => {
                const statusColor = entry.status === '3D FIX'
                    ? '#22c55e'
                    : (entry.status === 'NO FIX' ? 'var(--sigma-muted)' : '#f59e0b');

                html += `<tr>
                    <td class="text-muted">${entry.time ?? '--'}</td>
                    <td>${formatNumber(entry.latitude, 7)}</td>
                    <td>${formatNumber(entry.longitude, 7)}</td>
                    <td>${formatNumber(entry.altitude, 2)}</td>
                    <td>${entry.satellites ?? 0}</td>
                    <td class="text-right"><span style="font-weight:700;color:${statusColor};">${entry.status ?? 'NO FIX'}</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // ── Realtime map/stats refresh (1 second) ─────────────────────────────
        let isMapRefreshing = false;

        async function refreshMapData() {
            if (isMapRefreshing) return;
            isMapRefreshing = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);

            try {
                const response = await fetch(`${dashboardDataUrl}?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                if (!response.ok) return;
                const data = await response.json();
                applyDashboardData(data);
            } catch (_) {
                clearTimeout(timeoutId);
            } finally {
                isMapRefreshing = false;
            }
        }

        // ── GPS log refresh (10 seconds) ──────────────────────────────────────
        let isLogRefreshing = false;

        async function refreshGpsLog() {
            if (isLogRefreshing) return;
            isLogRefreshing = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            try {
                const response = await fetch(`${logDataUrl}?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                if (!response.ok) return;
                const data = await response.json();
                if (data.gpsLog) { renderGpsLogTable(data.gpsLog); }
            } catch (_) {
                clearTimeout(timeoutId);
            } finally {
                isLogRefreshing = false;
            }
        }

        // ── Boot ──────────────────────────────────────────────────────────────
        (function startClock() {
            try { updateClock(); } catch (e) {}
            setInterval(function () { try { updateClock(); } catch (e) {} }, 1000);
        })();

        try { applyDashboardData(initialDashboardData); } catch (e) {}
        try { renderGpsLogTable(initialGpsLog); } catch (e) {}

        setInterval(refreshMapData, MAP_REFRESH_MS);
        setInterval(refreshGpsLog, LOG_REFRESH_MS);

        refreshMapData();
        refreshGpsLog();
    });
</script>
<script>
    // ─── GPS Control Panel JS ─────────────────────────────────────────────────
    const GPS_POWER_URL  = @json(route('sensor-commands.power'));
    const GPS_RESET_URL  = @json(route('sensor-commands.reset'));
    const GPS_STATE_URL  = @json(route('sensor-commands.state'));
    const GPS_CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Load initial GPS power state
    (async function loadGpsState() {
        try {
            const res = await fetch(GPS_STATE_URL, { credentials: 'same-origin' });
            if (!res.ok) return;
            const state = await res.json();
            updateGpsPowerUI(state.gps?.power ?? 'on');
        } catch (e) {}
    })();

    function updateGpsPowerUI(state) {
        const btnOn  = document.getElementById('gps-btn-on');
        const btnOff = document.getElementById('gps-btn-off');
        if (btnOn)  btnOn.classList.toggle('active', state === 'on');
        if (btnOff) btnOff.classList.toggle('active', state === 'off');
    }

    function showGpsMsg(text, type = 'success') {
        const el = document.getElementById('gps-ctrl-msg');
        if (!el) return;
        el.textContent = text;
        el.className = `ctrl-feedback-msg ${type}`;
        setTimeout(() => { el.textContent = ''; el.className = 'ctrl-feedback-msg'; }, 3000);
    }

    async function setGpsPower(state) {
        try {
            const res = await fetch(GPS_POWER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': GPS_CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: 'gps', state }),
            });
            const data = await res.json();
            if (data.success) { updateGpsPowerUI(state); showGpsMsg(data.message, 'success'); }
            else showGpsMsg('Gagal mengirim perintah.', 'error');
        } catch (e) { showGpsMsg('Koneksi gagal.', 'error'); }
    }

    async function resetGps() {
        if (!confirm('Reset GPS ke pengaturan default?')) return;
        try {
            const res = await fetch(GPS_RESET_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': GPS_CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: 'gps' }),
            });
            const data = await res.json();
            if (data.success) updateGpsPowerUI('on');
            showGpsMsg(data.message ?? 'GPS direset.', data.success ? 'success' : 'error');
        } catch (e) { showGpsMsg('Koneksi gagal.', 'error'); }
    }
</script>
@endpush


@section('dashboard-content')
<div class="panel-page">
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="content-subtitle">DATA SENSOR KHUSUS</p>
                <h1 class="content-title">GPS NEO-6M</h1>
                <p class="content-desc">Pantau lokasi perangkat dan koordinat satelit secara realtime dari OpenStreetMap.</p>
            </div>
            <div class="datetime-widget">
                <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    <div class="dashboard-grid">
        <div class="dashboard-main">
            <!-- Peta GPS Section -->
            <section id="sensor-gps-card" class="glow-card panel-card map-card" style="height: 100%; display: flex; flex-direction: column;">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Peta Lokasi Live</h2>
                        <p class="section-subtitle">Posisi terbaru perangkat berdasarkan satelit.</p>
                    </div>
                    <div class="live-badge" id="gps-live-badge">REALTIME</div>
                </div>

                <div id="gps-map" class="sensor-map" style="flex-grow: 1; min-height: 400px; border-radius: 8px; margin-top: 1rem; border: 1px solid rgba(214, 196, 176, 0.1);"></div>
            </section>
        </div>

        <aside class="dashboard-sidebar">
            <section class="glow-card panel-card summary-card">
                <div class="section-header">
                    <h2 class="section-title">Statistik GPS</h2>
                </div>
                <div class="summary-grid-vertical" style="display: grid; gap: 1rem;">
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Status Koneksi</p>
                        <p id="connectionStatus" class="dashboard-score-card-value" style="color: {{ $gps['is_connected'] ? 'var(--sigma-accent)' : 'var(--sigma-muted)' }};">{{ $gps['is_connected'] ? 'Terhubung' : 'Terputus' }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Fix Status</p>
                        <p id="gpsStatus" class="dashboard-score-card-value">{{ $gps['status'] }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Satelit Terkunci</p>
                        <p id="gpsSatellites" class="dashboard-score-card-value">{{ $gps['satellites'] }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Ketinggian (Altitude)</p>
                        <p id="gpsAltitude" class="dashboard-score-card-value">{{ number_format($gps['altitude'], 2) }} m</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Waktu Pembaruan</p>
                        <p id="gpsRecordedAt" class="dashboard-score-card-value" style="font-size: 1.25rem;">{{ $gps['recorded_at'] }}</p>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <!-- Grid Detail Koordinat Full Width -->
    <section class="glow-card panel-card" style="margin-top: 2rem;">
        <div class="section-header">
            <div>
                <h2 class="section-title">Detail Koordinat Geografis</h2>
            </div>
        </div>

        <div class="dashboard-info-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="dashboard-info-card">
                <p>Latitude</p>
                <strong id="gpsLatitude">{{ number_format($gps['latitude'], 7) }}</strong>
            </div>
            <div class="dashboard-info-card">
                <p>Longitude</p>
                <strong id="gpsLongitude">{{ number_format($gps['longitude'], 7) }}</strong>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialDashboardData = {
            gps: @json($gps),
        };
        const dashboardDataUrl = @json(route('panel.data.realtime'));
        const dashboardRefreshIntervalMs = 300000;
        
        let map = null;
        let marker = null;

        function updateClock() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const timeStr = `${hh}:${mm}:${ss}`;
            
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dateStr = `${days[now.getDay()]}, ${String(now.getDate()).padStart(2, '0')} ${months[now.getMonth()]} ${now.getFullYear()}`;

            setText('realtime-clock', timeStr);
            setText('realtime-date', dateStr);
        }

        function formatNumber(value, digits = 2) {
            const numericValue = Number(value);
            if (!Number.isFinite(numericValue)) return (0).toFixed(digits);
            return numericValue.toFixed(digits);
        }

        function setText(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }

        function renderMap(gps) {
            if (typeof L === 'undefined') return;

            const mapEl = document.getElementById('gps-map');
            if (!mapEl) return;

            const lat = Number(gps.latitude);
            const lng = Number(gps.longitude);

            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

            if (!map) {
                map = L.map('gps-map').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                marker = L.marker([lat, lng]).addTo(map);
            } else {
                map.setView([lat, lng], map.getZoom());
                if (marker) {
                    marker.setLatLng([lat, lng]);
                }
            }
        }

        function applyDashboardData(data) {
            if (!data) return;

            const gps = data.gps || {};

            setText('gpsLatitude', formatNumber(gps.latitude, 7));
            setText('gpsLongitude', formatNumber(gps.longitude, 7));
            setText('gpsAltitude', formatNumber(gps.altitude, 2));
            setText('gpsSatellites', String(gps.satellites ?? 0));
            setText('gpsStatus', gps.status || 'NO FIX');
            setText('gpsRecordedAt', gps.recorded_at || '--');

            const connectionEl = document.getElementById('connectionStatus');
            const liveBadgeEl = document.getElementById('gps-live-badge');
            if (connectionEl) {
                if (gps.is_connected) {
                    connectionEl.textContent = 'Terhubung';
                    connectionEl.style.color = 'var(--sigma-accent)';
                    if (liveBadgeEl) liveBadgeEl.style.display = 'inline-block';
                } else {
                    connectionEl.textContent = 'Terputus';
                    connectionEl.style.color = 'var(--sigma-muted)';
                    if (liveBadgeEl) liveBadgeEl.style.display = 'none';
                }
            }

            try { renderMap(gps); } catch (e) { console.warn('[SIGMA] Map error:', e); }
        }

        let isRefreshing = false;

        async function refreshDashboardData() {
            if (isRefreshing) return;
            isRefreshing = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000);

            try {
                const response = await fetch(`${dashboardDataUrl}?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });

                clearTimeout(timeoutId);

                if (!response.ok) return;

                const data = await response.json();
                applyDashboardData(data);
            } catch (error) {
                clearTimeout(timeoutId);
            } finally {
                isRefreshing = false;
            }
        }

        (function startClock() {
            try { updateClock(); } catch (e) {}
            setInterval(function () {
                try { updateClock(); } catch (e) {}
            }, 1000);
        })();

        try {
            applyDashboardData(initialDashboardData);
        } catch (e) {}

        setInterval(function () {
            refreshDashboardData();
        }, dashboardRefreshIntervalMs);

        refreshDashboardData();
    });
</script>
<script>
    // ─── GPS Control Panel JS ─────────────────────────────────────────────────
    const GPS_POWER_URL  = @json(route('sensor-commands.power'));
    const GPS_RESET_URL  = @json(route('sensor-commands.reset'));
    const GPS_STATE_URL  = @json(route('sensor-commands.state'));
    const GPS_CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Load initial GPS power state
    (async function loadGpsState() {
        try {
            const res = await fetch(GPS_STATE_URL, { credentials: 'same-origin' });
            if (!res.ok) return;
            const state = await res.json();
            updateGpsPowerUI(state.gps?.power ?? 'on');
        } catch (e) {}
    })();

    function updateGpsPowerUI(state) {
        const btnOn  = document.getElementById('gps-btn-on');
        const btnOff = document.getElementById('gps-btn-off');
        if (btnOn)  btnOn.classList.toggle('active', state === 'on');
        if (btnOff) btnOff.classList.toggle('active', state === 'off');
    }

    function showGpsMsg(text, type = 'success') {
        const el = document.getElementById('gps-ctrl-msg');
        if (!el) return;
        el.textContent = text;
        el.className = `ctrl-feedback-msg ${type}`;
        setTimeout(() => { el.textContent = ''; el.className = 'ctrl-feedback-msg'; }, 3000);
    }

    async function setGpsPower(state) {
        try {
            const res = await fetch(GPS_POWER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': GPS_CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: 'gps', state }),
            });
            const data = await res.json();
            if (data.success) { updateGpsPowerUI(state); showGpsMsg(data.message, 'success'); }
            else showGpsMsg('Gagal mengirim perintah.', 'error');
        } catch (e) { showGpsMsg('Koneksi gagal.', 'error'); }
    }

    async function resetGps() {
        if (!confirm('Reset GPS ke pengaturan default?')) return;
        try {
            const res = await fetch(GPS_RESET_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': GPS_CSRF, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: 'gps' }),
            });
            const data = await res.json();
            if (data.success) updateGpsPowerUI('on');
            showGpsMsg(data.message ?? 'GPS direset.', data.success ? 'success' : 'error');
        } catch (e) { showGpsMsg('Koneksi gagal.', 'error'); }
    }
</script>
@endpush
