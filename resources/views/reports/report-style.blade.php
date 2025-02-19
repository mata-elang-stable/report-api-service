<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: white;
    }

    hr {
        display: block;
        height: 1px;
        border: 0;
        border-top: 1px solid #ccc;
        margin: 1em 0;
        padding: 0;
    }
    .header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
    }

    .logo {
        width: 50px;
        height: 50px;
        margin-right: 15px;
    }

    .title {
        margin: 0;
        font-size: 20px;
        font-weight: bold;
    }

    .subtitle {
        margin: 2px 0;
        font-size: 12px;
        color: #666;
    }

    .grid-container {
        display: grid;
        grid-gap: 15px;
    }

    .top-row {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 15px;
    }

    .box, .middle-row {
        border: 1px solid #ccc;
        border-radius: 2px;
        padding: 15px;
        background-color: white;
    }

    .box h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .card-label {
        font-size: 14px;
        font-weight: bold;
    }

    .priority-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-radius: 0;
        background-color: transparent;
    }

    .priority-label {
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .priority-label.critical {
        background-color: #6813b8;
    }

    .priority-label.high {
        background-color: #dc3545;
    }

    .priority-label.medium {
        background-color: #dc8635;
    }

    .priority-label.low {
        background-color: #ffc107;
    }

    .priority-label.informational {
        background-color: #17a2b8;
    }

    /* Alert Table */
    .alert-table {
        margin-top: 10px;
    }

    .alert-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .alert-table th {
        background-color: #f8f9fa;
        padding: 8px;
        text-align: left;
        font-size: 14px;
        border-bottom: 1px solid #dee2e6;
    }

    .alert-table td {
        padding: 8px;
        border-bottom: 1px solid #dee2e6;
        font-size: 13px;
    }

    .priority-badge {
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 12px;
    }

    .total-events-box .event-count {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        margin-top: 25px;
        line-height: 1;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Specific print styles */
    @media print {
        .priority-label.critical,
        .priority-badge.critical {
            background-color: #6813b8; !important;
            color: white !important;
        }
        .priority-label.high,
        .priority-badge.high {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .priority-label.low,
        .priority-badge.low {
            background-color: #ffc107 !important;
            color: black !important;
        }

        .priority-label.informational,
        .priority-badge.informational {
            background-color: #17a2b8 !important;
            color: white !important;
        }

        /* Ensure other elements print properly */
        body {
            padding: 20px;
            margin: 0;
        }

        .box, .middle-row {
            border: 1px solid #000 !important;
            break-inside: avoid;
        }

        table {
            break-inside: auto;
        }

        tr {
            break-inside: avoid;
            break-after: auto;
        }

        /* Improve table readability in print */
        .alert-table th {
            background-color: #f8f9fa !important;
        }

        .alert-table tr:nth-child(even) {
            background-color: #f9f9f9 !important;
        }
    }

    /* Source IP Box Styles */
    .source-ip-box {
        padding: 15px;
    }

    .source-ip-box h3 {
        margin: 0;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .ip-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .ip-table thead tr {
        border-bottom: 1px solid #dee2e6;
    }

    .ip-table th {
        text-align: left;
        padding: 8px;
        font-weight: normal;
        color: #333;
    }

    .ip-table td {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }

    .ip-table .count-column {
        text-align: right;
        width: 80px;
    }

    .ip-table .ip-column {
        font-family: monospace;
    }

    .ip-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    @media print {
        .source-ip-box {
            break-inside: avoid;
        }

        .ip-table {
            break-inside: auto;
        }

        .ip-table tr {
            break-inside: avoid;
        }
    }

    .bottom-grid {
        display: grid;
        grid-template-columns: 2fr 1fr; /* 2/3 for IP, 1/3 for Country */
        gap: 20px;
        margin-top: 20px;
    }

    /* Common box styles */
    .box {
        border: 1px solid #ccc;
        border-radius: 2px;
        padding: 15px;
        background-color: white;
    }

    /* Box headers */
    .box h3 {
        text-align: center;
        margin: 0 0 15px 0;
        font-size: 16px;
        font-weight: normal;
    }

    .subtitle {
        font-size: 14px;
        color: #666;
    }

    /* Table styles */
    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .ip-table,
    .country-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .ip-table th,
    .country-table th {
        text-align: left;
        padding: 8px;
        border-bottom: 1px solid #dee2e6;
        font-weight: normal;
    }

    .ip-table td,
    .country-table td {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }

    .count-column {
        text-align: right;
        width: 80px;
    }

    .ip-column {
        font-family: monospace;
    }

    /* Hover effect */
    .ip-table tbody tr:hover,
    .country-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Print styles */
    @media print {
        .bottom-grid {
            break-inside: avoid;
        }

        .box {
            break-inside: avoid;
        }
    }

    /* Grid layouts */
    .sensor-grid {
        display: grid;
        grid-gap: 20px;
        margin-top: 20px;
    }

    .ports-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    /* Box styles */
    .box {
        border: 1px solid #ccc;
        border-radius: 2px;
        padding: 15px;
        background-color: white;
    }

    /* Headers and subtitles */
    .box h3 {
        text-align: center;
        margin: 0;
        font-size: 16px;
        font-weight: normal;
    }

    .subtitle {
        text-align: center;
        margin: 5px 0 15px 0;
        font-size: 14px;
        color: #666;
    }

    /* Table styles */
    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    .sensor-table,
    .port-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .sensor-table th,
    .port-table th {
        text-align: left;
        padding: 8px;
        border-bottom: 1px solid #dee2e6;
        font-weight: normal;
    }

    .sensor-table td,
    .port-table td {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }

    .count-column {
        text-align: right;
        width: 80px;
    }

    /* Hover effects */
    .sensor-table tbody tr:hover,
    .port-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Print styles */
    @media print {
        .sensor-grid,
        .ports-grid {
            break-inside: avoid;
        }

        .box {
            break-inside: avoid;
        }
    }

    .page-break {
        page-break-before: always; /* Or use page-break-after: always */
    }
    </style>
