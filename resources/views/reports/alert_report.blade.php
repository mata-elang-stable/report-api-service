<!DOCTYPE html>
<html>
<head>
    <title>Mata Elang Report</title>
    @include('reports.report-style')
</head>
<body>
    <div class="header">
        {{-- <img src="{{ asset('images/logo.png') }}" alt="Logo Mata Elang" class="logo"> --}}
        {{-- <img src="https://drive.fadhilyori.my.id/s/yBQ7YCercsaHNEq/download/mata-elang-csrg-logo.png" alt="Logo" class="logo"> --}}
        <div class="title-section">
            <h1 class="title">Mata Elang Report</h1>
            <p class="subtitle">Generated on {{ now()->format('D, j M Y H:i:s') }}</p>
            {{-- <p class="subtitle">{{ $quarter }} Quartal</p> --}}
        </div>
    </div>
    <hr/>
    <div class="grid-container">
        <div class="top-row">
                <div class="box total-events-box">
                    <h3>Total Events</h3>
                    <p class="event-count">{{ $data['totalEvents'] }}</p>
                </div>
            <div class="box priority-box">
                <h3>Top Priority</h3>
                <div class="priority-list">
                    @foreach($data['priorityCounts'] as $priority => $count)
                        <div class="priority-item">
                            <span class="priority-label {{ strtolower($priority) }}">{{ $priority }}</span>
                            <span class="priority-count">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="middle-row">
            <h3>Top 10 Alert</h3>
            <div class="alert-table">
                <table>
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Classification</th>
                            <th>Alert Message</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['topAlertData'] as $alert)
                            <tr>
                                <td>
                                    <span class="priority-badge {{ strtolower($alert['priority']) }}">
                                        {{ $alert['priority'] }}
                                    </span>
                                </td>
                                <td>{{ $alert['classification'] }}</td>
                                <td>{{ $alert['message'] }}</td>
                                <td class="count-cell">{{ $alert['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="box source-ip-box">
                <h3>Top 10 Source IP</h3>
                <div class="table-container">
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th>Source IP</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['topSourceIps'] as $ip)
                                <tr>
                                    <td class="ip-column">{{ $ip['sourceIp'] }}</td>
                                    <td class="count-column">{{ $ip['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="box country-box">
                <h3>Top 10 Source Country</h3>
                <div class="table-container">
                    <table class="country-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['topSourceCountries'] as $country)
                                <tr>
                                    <td>{{ $country['country'] }}</td>
                                    <td class="count-column">{{ $country['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="box source-ip-box">
                <h3>Top 10 Destination IP</h3>
                <div class="table-container">
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th>Destination IP</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['topDestinationIps'] as $ip)
                                <tr>
                                    <td class="ip-column">{{ $ip['destinationIp'] }}</td>
                                    <td class="count-column">{{ $ip['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="box country-box">
                <h3>Top 10 Destination Country</h3>
                <div class="table-container">
                    <table class="country-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['topDestinationCountries'] as $country)
                                <tr>
                                    <td>{{ $country['country'] }}</td>
                                    <td class="count-column">{{ $country['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sensor-grid">
            <!-- Top Sensor Box -->
            <div class="box sensor-box">
                <h3>Top Sensor</h3>
                <div class="table-container">
                    <table class="sensor-table">
                        <thead>
                            <tr>
                                <th>Sensor ID</th>
                                <th>Sensor Name</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['topSensors'] as $sensor)
                                <tr>
                                    <td>{{ $sensor['sensor_id'] }}</td>
                                    <td>{{ $sensor['sensor_name'] }}</td>
                                    <td class="count-column">{{ $sensor['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Ports Grid -->
            <div class="ports-grid">
                <!-- Source Port Box -->
                <div class="box port-box">
                    <h3>Top 10 Source Port</h3>
                    <div class="table-container">
                        <table class="port-table">
                            <thead>
                                <tr>
                                    <th>Port</th>
                                    <th class="count-column">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['topSourcePorts'] as $port)
                                    <tr>
                                        <td>{{ $port['sourcePort'] }}</td>
                                        <td class="count-column">{{ $port['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Destination Port Box -->
                <div class="box port-box">
                    <h3>Top 10 Destination Port</h3>
                    <div class="table-container">
                        <table class="port-table">
                            <thead>
                                <tr>
                                    <th>Port</th>
                                    <th class="count-column">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['topDestinationPorts'] as $port)
                                    <tr>
                                        <td>{{ $port['destinationPort'] }}</td>
                                        <td class="count-column">{{ $port['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br />
    @foreach ($data['sensors'] as $sensor)
    <div class="page-break">
    <div class="header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Mata Elang" class="logo">
        {{-- <img src="https://media.istockphoto.com/id/1179883860/id/vektor/logo-mata.jpg?s=612x612&w=0&k=20&c=FRVESMKXEo77SOvFaNllZWGi-uKXTav210TJcODM2XI=" alt="Logo" class="logo"> --}}
        <div class="title-section">
            <h1 class="title">Mata Elang Report</h1>
            <p class="subtitle">Generated on {{ now()->format('D, j M Y H:i:s') }}</p>
            {{-- <p class="subtitle">{{ $quarter }} Quartal</p> --}}
        </div>
    </div>
    <hr/>
    <div class="grid-container">
    <div>
    <H2>{{$sensor['sensor_name']}}</H2>
    </div>
        <div class="top-row">
                <div class="box total-events-box">
                    <h3>Total Events</h3>
                    <p class="event-count">{{ $sensor['totalEvents'] }}</p>
                </div>
            <div class="box priority-box">
                <h3>Top Priority</h3>
                <div class="priority-list">
                    @foreach($sensor['priorityCounts'] as $priority => $count)
                        <div class="priority-item">
                            <span class="priority-label {{ strtolower($priority) }}">{{ $priority }}</span>
                            <span class="priority-count">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="middle-row">
            <h3>Top 10 Alert</h3>
            <div class="alert-table">
                <table>
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Classification</th>
                            <th>Alert Message</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sensor['topAlertData'] as $alert)
                            <tr>
                                <td>
                                    <span class="priority-badge {{ strtolower($alert['priority']) }}">
                                        {{ $alert['priority'] }}
                                    </span>
                                </td>
                                <td>{{ $alert['classification'] }}</td>
                                <td>{{ $alert['message'] }}</td>
                                <td class="count-cell">{{ $alert['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="box source-ip-box">
                <h3>Top 10 Source IP <span class="subtitle">(IP and Count)</span></h3>
                <div class="table-container">
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th>Source IP</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sensor['topSourceIps'] as $ip)
                                <tr>
                                    <td class="ip-column">{{ $ip['sourceIp'] }}</td>
                                    <td class="count-column">{{ $ip['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="box country-box">
                <h3>Top 10 Source Country</h3>
                <div class="table-container">
                    <table class="country-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sensor['topSourceCountries'] as $country)
                                <tr>
                                    <td>{{ $country['country'] }}</td>
                                    <td class="count-column">{{ $country['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="box source-ip-box">
                <h3>Top 10 Destination IP <span class="subtitle">(IP and Count)</span></h3>
                <div class="table-container">
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th>Destination IP</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sensor['topDestinationIps'] as $ip)
                                <tr>
                                    <td class="ip-column">{{ $ip['destinationIp'] }}</td>
                                    <td class="count-column">{{ $ip['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="box country-box">
                <h3>Top 10 Destination Country</h3>
                <div class="table-container">
                    <table class="country-table">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="count-column">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sensor['topDestinationCountries'] as $country)
                                <tr>
                                    <td>{{ $country['country'] }}</td>
                                    <td class="count-column">{{ $country['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sensor-grid">
            <!-- Bottom Ports Grid -->
            <div class="ports-grid">
                <!-- Source Port Box -->
                <div class="box port-box">
                    <h3>Top 10 Source Port</h3>
                    <p class="subtitle">Port, Count</p>
                    <div class="table-container">
                        <table class="port-table">
                            <thead>
                                <tr>
                                    <th>Port</th>
                                    <th class="count-column">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sensor['topSourcePorts'] as $port)
                                    <tr>
                                        <td>{{ $port['sourcePort'] }}</td>
                                        <td class="count-column">{{ $port['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Destination Port Box -->
                <div class="box port-box">
                    <h3>Top 10 Destination Port</h3>
                    <p class="subtitle">Port, Count</p>
                    <div class="table-container">
                        <table class="port-table">
                            <thead>
                                <tr>
                                    <th>Port</th>
                                    <th class="count-column">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sensor['topDestinationPorts'] as $port)
                                    <tr>
                                        <td>{{ $port['destinationPort'] }}</td>
                                        <td class="count-column">{{ $port['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endforeach
</body>
</html>
