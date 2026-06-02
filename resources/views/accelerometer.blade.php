@extends('layouts.dashboard')

@section('title', 'Sensor ADXL345')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/accelerometer.css') }}">
@endpush

@section('dashboard-content')
<div class="panel-page">
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="content-subtitle">DATA SENSOR KHUSUS</p>
                <h1 class="content-title">Sensor ADXL345</h1>
                <p class="content-desc">Pantau grafik getaran dan log sensor secara realtime langsung dari ESP32.</p>
            </div>
            <div class="datetime-widget">
                <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    <div class="dashboard-grid">
        <div class="dashboard-main">
            <!-- Sensor ADXL345 Section -->
            <section id="accelChart" class="glow-card panel-card accel-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Sensor ADXL345 Realtime</h2>
                        <p class="section-subtitle">Grafik realtime + ringkasan nilai sensor ADXL345.</p>
                    </div>
                    <div class="live-badge">REALTIME</div>
                </div>

                @php
                    $mag = (float) $currentAccel['magnitude'];
                    if ($mag < 0.34)      { $mmiLevel = 'I';      $mmiStatus = 'Aman';    $mmiColor = '#22c55e'; }
                    elseif ($mag < 2.8)   { $mmiLevel = 'II-III'; $mmiStatus = 'Lemah';   $mmiColor = '#86efac'; }
                    elseif ($mag < 7.8)   { $mmiLevel = 'IV';     $mmiStatus = 'Waspada'; $mmiColor = '#f59e0b'; }
                    elseif ($mag < 18.4)  { $mmiLevel = 'V';      $mmiStatus = 'Bahaya!'; $mmiColor = '#f97316'; }
                    else                  { $mmiLevel = 'VI+';    $mmiStatus = 'AWAS!';   $mmiColor = '#ef4444'; }
                @endphp
                <div class="dashboard-info-grid margin-y-1-5">
                    <div class="dashboard-info-card">
                        <p>Level MMI</p>
                        <strong id="currentMmi" style="color: {{ $mmiColor }};">{{ $mmiLevel }} ({{ $mmiStatus }})</strong>
                    </div>
                    <div class="dashboard-info-card">
                        <p>Waktu Sensor</p>
                        <strong id="currentAccelTime">{{ $currentAccel['time'] ?? now()->timezone('Asia/Jakarta')->format('d M Y H:i:s') . ' WIB' }}</strong>
                    </div>
                </div>

                <div class="chart-wrapper">
                    <div id="chart"></div>
                </div>
            </section>

        </div>

        <aside class="dashboard-sidebar">
            <section class="glow-card panel-card summary-card">
                <div class="section-header">
                    <h2 class="section-title">Statistik Getaran</h2>
                </div>
                <div class="summary-grid-vertical grid-gap-1">
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Status Koneksi</p>
                        <p id="connectionStatus" class="dashboard-score-card-value {{ $currentAccel['is_connected'] ? 'status-connected' : 'status-disconnected' }}">{{ $currentAccel['is_connected'] ? 'Terhubung' : 'Terputus' }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Magnitudo Terkini</p>
                        <p id="currentMagnitude" class="dashboard-score-card-value">{{ number_format($currentAccel['magnitude'], 2) }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Magnitudo Maksimum</p>
                        <p id="magnitudeMaximum" class="dashboard-score-card-value">{{ number_format($summary['maximum'], 2) }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Rata-rata Getaran</p>
                        <p id="magnitudeAverage" class="dashboard-score-card-value">{{ number_format($summary['average'], 2) }}</p>
                    </div>
                    <div class="dashboard-score-card">
                        <p class="dashboard-score-card-title">Jumlah Sampel</p>
                        <p id="sampleCount" class="dashboard-score-card-value">{{ $summary['count'] }}</p>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <!-- Table Log Section -->
    <section class="glow-card panel-card log-card mt-2">
        <div class="section-header">
            <div>
                <h2 class="section-title">Log Sensor — 5 Menit Terakhir</h2>
                <p class="section-subtitle">Riwayat data accelerometer dari 5 menit terakhir beserta status MMI.</p>
            </div>
            <span id="log-refresh-badge" class="status-pill-badge">LOG</span>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Magnitudo</th>
                        <th>Level MMI</th>
                        <th class="text-right">Status</th>
                    </tr>
                </thead>
                <tbody id="sample-log-body">
                    @forelse($accelLog as $sample)
                        <tr>
                            <td class="text-muted">{{ $sample['time'] }}</td>
                            <td>{{ number_format($sample['magnitude'], 4) }}</td>
                            <td>
                                <span style="font-weight: 800; color: {{ $sample['mmi_color'] }};">{{ $sample['mmi_level'] }}</span>
                            </td>
                            <td class="text-right">
                                <span style="font-weight: 700; color: {{ $sample['mmi_color'] }};">{{ $sample['mmi_status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted table-empty-row">Belum ada data sensor dalam 5 menit terakhir.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const initialDashboardData = {
            gps: @json($gps),
            currentAccel: @json($currentAccel),
            accelSamples: @json($accelSamples),
            summary: @json($summary),
            lastUpdatedAt: @json($lastUpdatedAt),
        };
        const initialAccelLog = @json($accelLog);
        const dashboardDataUrl = '/panel/data/realtime';
        const logDataUrl = '/panel/data/log';

        // Realtime chart: every 1 second (optimized)
        const CHART_REFRESH_MS = 1000;
        // Log table: every 2 seconds
        const LOG_REFRESH_MS = 2000;

        let accelChart = null;
        let cachedLogDataJson = null;

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

        /**
         * Determine MMI color from magnitude — mirrors main2.ino thresholds.
         */
        function getMmiForMagnitude(magnitude) {
            const m = Number(magnitude);
            if (m < 0.34) return { level: 'I', status: 'Aman', color: '#22c55e' };
            if (m < 2.8) return { level: 'II-III', status: 'Lemah', color: '#86efac' };
            if (m < 7.8) return { level: 'IV', status: 'Waspada', color: '#f59e0b' };
            if (m < 18.4) return { level: 'V', status: 'Bahaya!', color: '#f97316' };
            return { level: 'VI+', status: 'AWAS!', color: '#ef4444' };
        }

        function buildChartOptions(samples) {
            const isMobile = window.innerWidth <= 768;
            const isDark = document.documentElement.classList.contains('dark-mode');
            const labelColor = isDark ? '#c4a98a' : '#6b5545';
            const gridColor  = isDark ? 'rgba(194, 116, 62, 0.15)' : 'rgba(107, 85, 69, 0.12)';

            return {
                chart: {
                    type: 'area',
                    height: isMobile ? 320 : 520,
                    fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif',
                    toolbar: { show: false },
                    animations: {
                        enabled: !isMobile,
                        easing: 'easeinout',
                        speed: 500,
                        dynamicAnimation: { enabled: !isMobile, speed: 350 },
                    },
                    background: 'transparent',
                    dropShadow: {
                        enabled: !isMobile,
                        top: 2,
                        left: 0,
                        blur: 4,
                        color: '#e63946',
                        opacity: 0.18,
                    },
                },
                series: [
                    { name: 'X', data: samples.map((s) => s.x) },
                    { name: 'Y', data: samples.map((s) => s.y) },
                    { name: 'Z', data: samples.map((s) => s.z) },
                    { name: 'Magnitudo', data: samples.map((s) => s.magnitude) },
                ],
                xaxis: {
                    categories: samples.map((s) => s.time || '--'),
                    labels: {
                        style: { colors: labelColor, fontWeight: 600, fontSize: '11px' },
                        hideOverlappingLabels: true,
                        rotate: 0,
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: labelColor, fontWeight: 600, fontSize: '11px' },
                        formatter: (val) => val != null ? val.toFixed(1) : '0',
                    },
                },
                stroke: {
                    curve: isMobile ? 'straight' : 'smooth',
                    width: [2, 2, 2, 3.5],
                    lineCap: 'round',
                },
                colors: ['#b45309', '#f97316', '#fbbf24', '#e63946'],
                fill: {
                    type: ['solid', 'solid', 'solid', 'gradient'],
                    opacity: [0, 0, 0, 1],
                    gradient: {
                        shade: isDark ? 'dark' : 'light',
                        type: 'vertical',
                        opacityFrom: 0.35,
                        opacityTo: 0.02,
                        stops: [0, 95, 100],
                        colorStops: [
                            { offset: 0, color: '#e63946', opacity: 0.35 },
                            { offset: 60, color: '#e63946', opacity: 0.10 },
                            { offset: 100, color: '#e63946', opacity: 0.01 },
                        ],
                    },
                },
                markers: {
                    size: [5],
                    strokeWidth: [2],
                    strokeColors: ['#fff'],
                    hover: { size: 8, sizeOffset: 3 },
                },
                grid: {
                    borderColor: gridColor,
                    strokeDashArray: 4,
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: true } },
                    padding: { left: 8, right: 8, top: 0, bottom: 0 },
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: (val) => val != null ? val.toFixed(4) : '0',
                    },
                    style: { fontSize: '12px' },
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'center',
                    labels: { colors: labelColor },
                    fontWeight: 700,
                    fontSize: '13px',
                    markers: {
                        size: 6,
                        shape: 'circle',
                        strokeWidth: 0,
                    },
                    itemMargin: { horizontal: 12, vertical: 4 },
                },
                dataLabels: { enabled: false },
            };
        }

        function renderChart(samples) {
            const validSamples = samples.length > 0 ? samples : [{ x: 0, y: 0, z: 0, magnitude: 0, time: '--' }];

            if (!accelChart) {
                const options = buildChartOptions(validSamples);
                accelChart = new ApexCharts(document.querySelector('#chart'), options);
                accelChart.render();
            } else {
                accelChart.updateOptions(
                    { xaxis: { categories: validSamples.map((s) => s.time || '--') } },
                    false,
                    false
                );
                accelChart.updateSeries([
                    { name: 'X', data: validSamples.map((s) => s.x) },
                    { name: 'Y', data: validSamples.map((s) => s.y) },
                    { name: 'Z', data: validSamples.map((s) => s.z) },
                    { name: 'Magnitudo', data: validSamples.map((s) => s.magnitude) },
                ]);
            }
        }

        /**
         * Render the log table from the 5-minute log array.
         * Each row includes real server timestamp, X/Y/Z, magnitude, MMI level, and status.
         */
        function renderLogTable(logEntries) {
            const tbody = document.getElementById('sample-log-body');
            if (!tbody) return;

            if (!logEntries || logEntries.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-muted" style="text-align:center;padding:2rem;">Belum ada getaran terdeteksi dalam 5 menit terakhir.</td></tr>`;
                return;
            }

            let html = '';
            logEntries.forEach((sample) => {
                // Use server-provided mmi_* if available (initial SSR), else compute client-side
                const mmi = (sample.mmi_level && sample.mmi_status && sample.mmi_color)
                    ? { level: sample.mmi_level, status: sample.mmi_status, color: sample.mmi_color }
                    : getMmiForMagnitude(sample.magnitude);

                html += `<tr>
                    <td class="text-muted">${sample.time ?? '--'}</td>
                    <td>${formatNumber(sample.magnitude, 4)}</td>
                    <td><span style="font-weight:800;color:${mmi.color};">${mmi.level}</span></td>
                    <td class="text-right"><span style="font-weight:700;color:${mmi.color};">${mmi.status}</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        function applyDashboardData(data) {
            if (!data) return;

            const accel = data.currentAccel || {};
            const summary = data.summary || {};
            const samples = data.accelSamples || [];

            setText('currentMagnitude', formatNumber(accel.magnitude));
            const mmi = getMmiForMagnitude(accel.magnitude);
            const mmiEl = document.getElementById('currentMmi');
            if (mmiEl) {
                mmiEl.textContent = `${mmi.level} (${mmi.status})`;
                mmiEl.style.color = mmi.color;
            }
            setText('currentAccelTime', accel.time ?? '--');
            setText('magnitudeMaximum', formatNumber(summary.maximum));
            setText('magnitudeAverage', formatNumber(summary.average));
            setText('sampleCount', String(summary.count ?? 0));

            const connectionEl = document.getElementById('connectionStatus');
            const liveBadgeEl = document.querySelector('.live-badge');
            if (connectionEl) {
                if (accel.is_connected) {
                    connectionEl.textContent = 'Terhubung';
                    connectionEl.style.color = 'var(--sigma-accent)';
                    if (liveBadgeEl) liveBadgeEl.style.display = 'inline-block';
                } else {
                    connectionEl.textContent = 'Terputus';
                    connectionEl.style.color = 'var(--sigma-muted)';
                    if (liveBadgeEl) liveBadgeEl.style.display = 'none';
                }
            }

            try { renderChart(samples); } catch (e) { console.warn('[SIGMA] Chart error:', e); }
        }

        // ── Realtime chart refresh (1 second) ─────────────────────────────────
        let isChartRefreshing = false;

        async function refreshChartData() {
            if (isChartRefreshing) return;
            if (document.hidden) {
                setTimeout(refreshChartData, CHART_REFRESH_MS);
                return;
            }
            isChartRefreshing = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);

            try {
                const response = await fetch(`${dashboardDataUrl}?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                if (!response.ok) {
                    console.error('[SIGMA] Chart refresh failed with status:', response.status);
                    return;
                }
                const data = await response.json();
                applyDashboardData(data);
            } catch (error) {
                clearTimeout(timeoutId);
                console.error('[SIGMA] Chart refresh exception:', error);
            } finally {
                isChartRefreshing = false;
                setTimeout(refreshChartData, CHART_REFRESH_MS);
            }
        }

        // ── Log table refresh (10 seconds) ────────────────────────────────────
        let isLogRefreshing = false;

        async function refreshLogData() {
            if (isLogRefreshing) return;
            if (document.hidden) {
                setTimeout(refreshLogData, LOG_REFRESH_MS);
                return;
            }
            isLogRefreshing = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);

            try {
                const response = await fetch(`${logDataUrl}?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                if (!response.ok) {
                    console.error('[SIGMA] Log refresh failed with status:', response.status);
                    return;
                }
                const data = await response.json();
                if (data.accelLog) {
                    const dataStr = JSON.stringify(data.accelLog);
                    if (dataStr === cachedLogDataJson) return;
                    cachedLogDataJson = dataStr;
                    renderLogTable(data.accelLog);
                }
            } catch (error) {
                clearTimeout(timeoutId);
                console.error('[SIGMA] Log refresh exception:', error);
            } finally {
                isLogRefreshing = false;
                setTimeout(refreshLogData, LOG_REFRESH_MS);
            }
        }

        // ── Boot ──────────────────────────────────────────────────────────────
        (function startClock() {
            try { updateClock(); } catch (e) {}
            setInterval(function () { try { updateClock(); } catch (e) {} }, 1000);
        })();

        try { applyDashboardData(initialDashboardData); } catch (e) {}
        try { renderLogTable(initialAccelLog); } catch (e) {}

        // Kick off polling
        refreshChartData();
        refreshLogData();

        // Dark mode observer — rebuild chart colors on theme toggle
        const observer = new MutationObserver(() => {
            if (accelChart) {
                try {
                    const isDark = document.documentElement.classList.contains('dark-mode');
                    const labelColor = isDark ? '#c4a98a' : '#6b5545';
                    const gridColor  = isDark ? 'rgba(194, 116, 62, 0.15)' : 'rgba(107, 85, 69, 0.12)';
                    accelChart.updateOptions({
                        tooltip: { theme: isDark ? 'dark' : 'light' },
                        xaxis: { labels: { style: { colors: labelColor } } },
                        yaxis: { labels: { style: { colors: labelColor } } },
                        grid: { borderColor: gridColor },
                        legend: { labels: { colors: labelColor } },
                    }, false, false);
                } catch (e) {}
            }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    });
</script>

<script>
    // ─── Sensor Control Panel JS ───────────────────────────────────────────────
    const POWER_URL      = '/sensor-commands/power';
    const SENS_URL       = '/sensor-commands/sensitivity';
    const RESET_URL      = '/sensor-commands/reset';
    const STATE_URL      = '/sensor-commands/state';
    const CSRF_TOKEN     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Load initial state from server
    (async function loadControlState() {
        try {
            const res = await fetch(STATE_URL, { credentials: 'same-origin' });
            if (!res.ok) return;
            const state = await res.json();

            // Power state
            const power = state.accelerometer?.power ?? 'on';
            updatePowerUI('accelerometer', power);

            // Sensitivity
            const sens = state.accelerometer?.sensitivity ?? { x: 5, y: 5, z: 5 };
            document.getElementById('sens-x').value = sens.x;
            document.getElementById('sens-y').value = sens.y;
            document.getElementById('sens-z').value = sens.z;
            document.getElementById('sens-x-val').textContent = parseFloat(sens.x).toFixed(1);
            document.getElementById('sens-y-val').textContent = parseFloat(sens.y).toFixed(1);
            document.getElementById('sens-z-val').textContent = parseFloat(sens.z).toFixed(1);
        } catch (e) {}
    })();

    // Live slider value display
    ['x', 'y', 'z'].forEach(axis => {
        const slider = document.getElementById(`sens-${axis}`);
        const label  = document.getElementById(`sens-${axis}-val`);
        if (slider && label) {
            slider.addEventListener('input', () => {
                label.textContent = parseFloat(slider.value).toFixed(1);
            });
        }
    });

    function updatePowerUI(sensor, state) {
        const btnOn  = document.getElementById(`${sensor.split('_')[0]}-btn-on`);
        const btnOff = document.getElementById(`${sensor.split('_')[0]}-btn-off`);
        if (btnOn)  btnOn.classList.toggle('active', state === 'on');
        if (btnOff) btnOff.classList.toggle('active', state === 'off');
    }

    function showMsg(elId, text, type = 'success') {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = text;
        el.className = `ctrl-feedback-msg ${type}`;
        setTimeout(() => { el.textContent = ''; el.className = 'ctrl-feedback-msg'; }, 3000);
    }

    async function setSensorPower(sensorType, state) {
        try {
            const res = await fetch(POWER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: sensorType, state }),
            });
            const data = await res.json();
            if (data.success) {
                updatePowerUI(sensorType, state);
                showMsg('accel-ctrl-msg', data.message, 'success');
            } else {
                showMsg('accel-ctrl-msg', 'Gagal mengirim perintah.', 'error');
            }
        } catch (e) {
            showMsg('accel-ctrl-msg', 'Koneksi gagal.', 'error');
        }
    }

    async function applySensitivity() {
        const x = parseFloat(document.getElementById('sens-x').value);
        const y = parseFloat(document.getElementById('sens-y').value);
        const z = parseFloat(document.getElementById('sens-z').value);
        try {
            const res = await fetch(SENS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ x, y, z }),
            });
            const data = await res.json();
            showMsg('accel-ctrl-msg', data.message ?? 'Sensitivitas disimpan.', data.success ? 'success' : 'error');
        } catch (e) {
            showMsg('accel-ctrl-msg', 'Koneksi gagal.', 'error');
        }
    }

    async function resetSensor(sensorType) {
        if (!confirm(`Reset sensor ${sensorType} ke pengaturan default?`)) return;
        try {
            const res = await fetch(RESET_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ sensor_type: sensorType }),
            });
            const data = await res.json();
            if (data.success) {
                // Reset sliders to default 5
                if (sensorType === 'accelerometer') {
                    ['x', 'y', 'z'].forEach(axis => {
                        const s = document.getElementById(`sens-${axis}`);
                        const l = document.getElementById(`sens-${axis}-val`);
                        if (s) s.value = 5;
                        if (l) l.textContent = '5.0';
                    });
                }
                updatePowerUI(sensorType, 'on');
            }
            showMsg('accel-ctrl-msg', data.message ?? 'Sensor direset.', data.success ? 'success' : 'error');
        } catch (e) {
            showMsg('accel-ctrl-msg', 'Koneksi gagal.', 'error');
        }
    }
</script>
@endpush
