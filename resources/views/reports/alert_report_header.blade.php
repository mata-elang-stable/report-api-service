<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Alert Report' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .report-header {
            text-align: center;
            padding: 20px;
        }
        .report-header .title {
            font-size: 28px;
            font-weight: bold;
        }
        .report-header .date {
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="title">Mata Elang Report</div>
        <div class="date">Generated on {{ now()->format('D, j M Y H:i:s') }}</div>
    </div>
</body>
