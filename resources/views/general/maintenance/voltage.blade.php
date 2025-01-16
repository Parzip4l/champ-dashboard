@extends('layouts.vertical', ['title' => 'Voltage Data Mini Excavator'])

@section('content')

<div class="row">
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title">
                        Voltage Data
                    </h4>
                </div>
                <div id="chart-container" style="overflow-x: auto; white-space: nowrap;">
                    <div id="voltageChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    let voltageValues = [];  // Data untuk nilai tegangan
    let createdAtLabels = [];  // Data untuk tanggal/waktu

    // Fungsi untuk memuat data
    function fetchData() {
        fetch('/proxy-api')  // Ganti dengan endpoint yang sesuai
            .then(response => response.json())
            .then(data => {
                // Cek apakah data ada dan valid
                if (Array.isArray(data) && data.length > 0) {
                    const voltageData = data;

                    // Menyaring data hanya untuk hari ini
                    const today = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD
                    const filteredData = voltageData.filter(item => {
                        const createdAtDate = item.created_at.split(' ')[0]; // Mengambil hanya tanggal (YYYY-MM-DD)
                        return createdAtDate === today;  // Memastikan data untuk hari ini
                    });

                    // Menyusun data untuk grafik
                    const voltage = filteredData.map(item => parseFloat(item.voltage)); // Mengambil nilai voltage
                    const createdAt = filteredData.map(item => item.created_at); // Mengambil tanggal

                    // Menambahkan data baru ke array
                    voltageValues.push(...voltage);
                    createdAtLabels.push(...createdAt);

                    // Menjaga agar hanya 10 data terbaru yang ditampilkan
                    if (voltageValues.length > 10) {
                        voltageValues.shift();
                        createdAtLabels.shift();
                    }

                    // Menampilkan grafik
                    updateChart(voltageValues, createdAtLabels);
                } else {
                    console.error("No valid data received", data);
                }
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    // Fungsi untuk memperbarui grafik menggunakan ApexCharts
    function updateChart(voltageValues, createdAtLabels) {
        const options = {
            chart: {
                type: 'line',
                height: 400,
                width: voltageValues.length * 50, // Set lebar chart berdasarkan jumlah data
                zoom: {
                    enabled: true,  // Enable zooming
                    type: 'x',      // Enable zooming on the X-axis
                    zoomedArea: {
                        fill: {
                            color: '#90CAF9',
                            opacity: 0.4
                        },
                        stroke: {
                            color: '#0D47A1',
                            width: 1
                        }
                    }
                },
                toolbar: {
                    show: true
                }
            },
            series: [{
                name: 'Voltage',
                data: voltageValues
            }],
            xaxis: {
                categories: createdAtLabels,
                title: {
                    text: 'Date'
                },
                labels: {
                    rotate: -45,  // Rotasi label agar lebih mudah dibaca
                    formatter: function(value) {
                        var date = new Date(value);
                        
                        // Format the date to '01 June 25, 15:30'
                        var day = date.getDate().toString().padStart(2, '0');
                        var month = date.toLocaleString('default', { month: 'long' });
                        var year = date.getFullYear().toString().slice(-2);
                        
                        // Get hour and minute
                        var hours = date.getHours().toString().padStart(2, '0');
                        var minutes = date.getMinutes().toString().padStart(2, '0');
                        
                        return day + ' ' + month + ' ' + year + ', ' + hours + ':' + minutes;
                    }
                },
                tickAmount: 2,
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Voltage'
                },
                min: 0, // Mulai sumbu Y dari 0
                max: Math.max(...voltageValues) + 2, // Menyesuaikan batas atas sumbu Y
                tickAmount: 5
            },
            title: {
                text: 'Voltage Data',
                align: 'center'
            },
            stroke: {
                curve: 'smooth'
            },
            markers: {
                size: 5
            },
            dataLabels: {
                enabled: false // Menonaktifkan label data di titik-titik
            },
            grid: {
                show: true,  // Menampilkan grid untuk lebih mudah dibaca
                borderColor: '#ccc',
                strokeDashArray: 5
            },
            responsive: [{
                breakpoint: 1000,
                options: {
                    chart: {
                        width: "100%",
                    }
                }
            }]
        };

        // Cek apakah chart sudah ada sebelumnya, jika ada, perbarui
        const chart = ApexCharts.getChartByID("voltageChart");
        if (chart) {
            chart.updateOptions(options);
        } else {
            const newChart = new ApexCharts(document.querySelector("#voltageChart"), options);
            newChart.render();
        }
    }

    // Memuat data pertama kali saat halaman dimuat
    document.addEventListener("DOMContentLoaded", function() {
        fetchData();
    });

    // Menyegarkan data setiap 60 detik
    setInterval(() => {
        fetchData();
    }, 60000); // 60000 ms = 60 detik

</script>
@endsection
