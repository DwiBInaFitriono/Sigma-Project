@extends('layouts.dashboard')

@section('title', 'History Log Sensor')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endpush

@section('dashboard-content')
<div class="panel-page">
    <header class="content-header">
        <div class="content-header-flex">
            <div>
                <p class="content-subtitle">DATA LOG</p>
                <h1 class="content-title">History Sensor & GPS</h1>
                <p class="content-desc">Melihat seluruh riwayat data sensor berdasarkan hari secara spesifik.</p>
            </div>
            <div class="datetime-widget">
                <div id="realtime-clock" class="time-display">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
                <div id="realtime-date" class="date-display">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</div>
            </div>
        </div>
    </header>

    <form method="GET" action="{{ route('history') }}" class="glow-card panel-card history-form">
        <label for="date">Pilih Tanggal Log:</label>
        <input type="date" id="date" name="date" value="{{ $selectedDate }}" class="history-input" required>
        <button type="submit" class="history-btn">Tampilkan Log</button>
        <button type="button" class="history-btn history-btn-export" id="btn-export-pdf" onclick="generatePDF()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
            Export PDF
        </button>
    </form>

    <div class="dashboard-grid grid-cols-1-gap-2">
        
        <!-- Table Log Accelerometer -->
        <section class="glow-card panel-card log-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Sensor ADXL345</h2>
                    <p class="section-subtitle">Riwayat getaran terdeteksi (magnitudo ≥ 0.15) pada tanggal {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}.</p>
                </div>
                <div class="live-badge badge-alt">TOTAL: {{ $accelerometerLogs->total() }} GEMPA</div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal &amp; Waktu (WIB)</th>
                            <th>X</th>
                            <th>Y</th>
                            <th>Z</th>
                            <th>Magnitudo</th>
                            <th>Level MMI</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accelerometerLogs as $sample)
                            @php
                                $mag = (float) $sample->magnitude;
                                if ($mag < 0.15)      { $mmiLevel = 'I';      $mmiStatus = 'Aman';    $mmiColor = '#22c55e'; }
                                elseif ($mag < 0.30)  { $mmiLevel = 'II-III'; $mmiStatus = 'Lemah';   $mmiColor = '#86efac'; }
                                elseif ($mag < 0.60)  { $mmiLevel = 'IV';     $mmiStatus = 'Waspada'; $mmiColor = '#f59e0b'; }
                                elseif ($mag < 1.00)  { $mmiLevel = 'V';      $mmiStatus = 'Bahaya!'; $mmiColor = '#f97316'; }
                                else                  { $mmiLevel = 'VI+';    $mmiStatus = 'AWAS!';   $mmiColor = '#ef4444'; }
                            @endphp
                            <tr>
                                <td class="text-muted">{{ \Carbon\Carbon::parse($sample->recorded_at)->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}</td>
                                <td>{{ number_format($sample->x, 2) }}</td>
                                <td>{{ number_format($sample->y, 2) }}</td>
                                <td>{{ number_format($sample->z, 2) }}</td>
                                <td>{{ number_format($sample->magnitude, 4) }}</td>
                                <td><span style="font-weight: 800; color: {{ $mmiColor }};">{{ $mmiLevel }}</span></td>
                                <td class="text-right"><span style="font-weight: 700; color: {{ $mmiColor }};">{{ $mmiStatus }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted table-empty-row">Tidak ada getaran terdeteksi pada tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($accelerometerLogs->hasPages())
                <div class="pagination-wrapper">
                    {{ $accelerometerLogs->links() }}
                </div>
            @endif
        </section>

        <!-- Table Log GPS -->
        <section class="glow-card panel-card log-card">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Log GPS (NEO-6M)</h2>
                    <p class="section-subtitle">Menampilkan data lokasi untuk tanggal {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}.</p>
                </div>
                <div class="live-badge badge-alt">TOTAL: {{ $gpsLogs->total() }} LOG</div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Waktu (WIB)</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Altitude</th>
                            <th>Satelit</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gpsLogs as $gps)
                            <tr>
                                <td class="text-muted">{{ \Carbon\Carbon::parse($gps->recorded_at)->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}</td>
                                <td>{{ number_format($gps->latitude, 7) }}</td>
                                <td>{{ number_format($gps->longitude, 7) }}</td>
                                <td>{{ number_format($gps->altitude, 2) }} m</td>
                                <td>{{ $gps->satellites }}</td>
                                <td class="text-right {{ str_contains($gps->status, 'FIX') ? 'status-connected' : 'status-disconnected' }}">{{ $gps->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted table-empty-row">Tidak ada data log GPS pada tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($gpsLogs->hasPages())
                <div class="pagination-wrapper">
                    {{ $gpsLogs->links() }}
                </div>
            @endif
        </section>

    </div>
</div>

<!-- Hidden Elements for PDF Rendering -->
<div id="pdf-render-container">
    <canvas id="pdf-chart-canvas" width="800" height="400"></canvas>
    <div id="pdf-map"></div>
</div>

<!-- Loading Modal -->
<div id="pdf-loading-modal">
    <div class="spinner"></div>
    <h2 class="history-loading-modal-title">Membuat Laporan PDF...</h2>
    <p class="history-loading-modal-text" id="pdf-loading-text">Menyiapkan grafik dan peta...</p>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    // Data from Controller
    const accelData = @json($accelDataForPdf ?? []);
    const gpsData = @json($gpsDataForPdf ?? []);
    const selectedDate = "{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}";

    // ── Clock ──────────────────────────────────────────────────────────────────
    (function startClock() {
        const clockEl = document.getElementById('realtime-clock');
        const dateEl = document.getElementById('realtime-date');
        
        setInterval(() => {
            const now = new Date();
            if (clockEl) {
                clockEl.textContent = now.toLocaleTimeString('en-GB', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }
        }, 1000);
    })();
    
    // MMI Helper for PDF
    function getMmi(magnitude) {
        const m = Number(magnitude);
        if (m < 0.15) return { level: 'I', status: 'Aman' };
        if (m < 0.30) return { level: 'II-III', status: 'Lemah' };
        if (m < 0.60) return { level: 'IV', status: 'Waspada' };
        if (m < 1.00) return { level: 'V', status: 'Bahaya!' };
        return { level: 'VI+', status: 'AWAS!' };
    }

    async function generatePDF() {
        const btn = document.getElementById('btn-export-pdf');
        const modal = document.getElementById('pdf-loading-modal');
        const loadingText = document.getElementById('pdf-loading-text');
        
        btn.disabled = true;
        modal.style.display = 'flex';

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 40;
            let currentY = margin;

            // --- 1. Generate Chart Image ---
            loadingText.textContent = "Merender grafik akselerometer...";
            let chartImage = null;
            if (accelData.length > 0) {
                const chartData = [...accelData].reverse(); 
                
                const canvas = document.getElementById('pdf-chart-canvas');
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                const chart = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: chartData.map(d => d.time),
                        datasets: [{
                            label: 'Magnitudo',
                            data: chartData.map(d => d.magnitude),
                            borderColor: '#e11d48',
                            backgroundColor: 'rgba(225, 29, 72, 0.1)',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: false,
                        animation: false,
                        plugins: {
                            legend: { display: true, position: 'bottom' },
                            title: { display: true, text: 'Grafik Magnitudo Gempa', font: { size: 16 } }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
                
                await new Promise(r => setTimeout(r, 100));
                chartImage = canvas.toDataURL('image/jpeg', 1.0);
                chart.destroy();
            }

            // --- 2. Generate Map Image ---
            loadingText.textContent = "Merender peta GPS...";
            let mapImage = null;
            let firstValidGps = gpsData.find(g => g.latitude !== 0 && g.longitude !== 0);
            
            if (firstValidGps) {
                const mapContainer = document.getElementById('pdf-map');
                const map = L.map(mapContainer, {
                    center: [firstValidGps.latitude, firstValidGps.longitude],
                    zoom: 15,
                    zoomControl: false,
                    attributionControl: false
                });

                const tileLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19
                }).addTo(map);

                gpsData.forEach(g => {
                    if (g.latitude !== 0 && g.longitude !== 0) {
                        L.circleMarker([g.latitude, g.longitude], {
                            radius: 4,
                            color: '#e11d48',
                            fillColor: '#e11d48',
                            fillOpacity: 0.7
                        }).addTo(map);
                    }
                });

                await new Promise((resolve) => {
                    let isResolved = false;
                    const finish = () => { if(!isResolved){ isResolved=true; resolve(); }};
                    tileLayer.on('load', finish);
                    setTimeout(finish, 3000); 
                });

                const mapCanvas = await html2canvas(mapContainer, {
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff'
                });
                mapImage = mapCanvas.toDataURL('image/jpeg', 0.9);
                map.remove();
            }

            // --- 3. Build the PDF Document ---
            loadingText.textContent = "Menyusun dokumen PDF...";
            
            doc.setFontSize(20);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 30, 30);
            doc.text('Laporan Log Sensor SIGMA', pageWidth / 2, currentY, { align: 'center' });
            
            currentY += 20;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(100, 100, 100);
            doc.text(`Tanggal Data: ${selectedDate}`, pageWidth / 2, currentY, { align: 'center' });
            
            currentY += 15;
            doc.setFontSize(10);
            doc.text(`Dicetak pada: ${new Date().toLocaleString('id-ID')}`, pageWidth / 2, currentY, { align: 'center' });
            
            currentY += 30;

            // Page 1: Accelerometer
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0);
            doc.text('1. Riwayat Getaran Sensor ADXL345', margin, currentY);
            currentY += 15;

            if (chartImage) {
                const imgWidth = pageWidth - (margin * 2);
                const imgHeight = (400 / 800) * imgWidth;
                doc.addImage(chartImage, 'JPEG', margin, currentY, imgWidth, imgHeight);
                currentY += imgHeight + 20;
            }

            if (accelData.length > 0) {
                const tableBody = accelData.map(d => {
                    const mmi = getMmi(d.magnitude);
                    return [
                        d.time,
                        d.magnitude.toFixed(4),
                        mmi.level,
                        mmi.status,
                        d.x.toFixed(2),
                        d.y.toFixed(2),
                        d.z.toFixed(2)
                    ];
                });

                doc.autoTable({
                    startY: currentY,
                    head: [['Waktu', 'Magnitudo', 'MMI', 'Status', 'X', 'Y', 'Z']],
                    body: tableBody,
                    theme: 'striped',
                    headStyles: { fillColor: [194, 116, 62] },
                    styles: { fontSize: 9, cellPadding: 4 },
                    margin: { left: margin, right: margin },
                    didDrawPage: function (data) {
                        currentY = data.cursor.y;
                    }
                });
                currentY = doc.lastAutoTable.finalY + 30;
            } else {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'italic');
                doc.text('Tidak ada data getaran (gempa) pada tanggal ini.', margin, currentY);
                currentY += 30;
            }

            // Page 2: GPS Data
            doc.addPage();
            currentY = margin;

            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0);
            doc.text('2. Riwayat Lokasi GPS', margin, currentY);
            currentY += 20;

            if (mapImage) {
                const mapImgWidth = pageWidth - (margin * 2);
                const mapImgHeight = (400 / 800) * mapImgWidth;
                doc.addImage(mapImage, 'JPEG', margin, currentY, mapImgWidth, mapImgHeight);
                currentY += mapImgHeight + 20;
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'italic');
                doc.text('* Titik merah menunjukkan koordinat yang direkam.', margin, currentY);
                currentY += 20;
            }

            if (gpsData.length > 0) {
                const gpsTableBody = gpsData.map(d => [
                    d.time,
                    d.latitude.toFixed(7),
                    d.longitude.toFixed(7),
                    `${d.altitude.toFixed(1)} m`,
                    d.satellites,
                    d.status
                ]);

                doc.autoTable({
                    startY: currentY,
                    head: [['Waktu', 'Latitude', 'Longitude', 'Altitude', 'Satelit', 'Status']],
                    body: gpsTableBody,
                    theme: 'striped',
                    headStyles: { fillColor: [194, 116, 62] },
                    styles: { fontSize: 9, cellPadding: 4 },
                    margin: { left: margin, right: margin }
                });
            } else {
                doc.setFontSize(10);
                doc.setFont('helvetica', 'italic');
                doc.text('Tidak ada data lokasi GPS pada tanggal ini.', margin, currentY);
            }

            const formattedDate = "{{ \Carbon\Carbon::parse($selectedDate)->format('Y-m-d') }}";
            doc.save(`SIGMA-Report-${formattedDate}.pdf`);

        } catch (error) {
            console.error("PDF Generation Error:", error);
            alert("Terjadi kesalahan saat membuat PDF. Silakan periksa koneksi atau coba lagi.");
        } finally {
            modal.style.display = 'none';
            btn.disabled = false;
        }
    }
</script>
@endpush
