<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>DriverX Scorecard - {{ $company->name }}</title>
    <style>
        @page {
            margin: 25mm 15mm 20mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header table {
            width: 100%;
        }
        .company-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
        }
        .meta-table {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .meta-table strong {
            color: #0f172a;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .data-table th {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            text-align: left;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .score-pill {
            display: inline-block;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
        }
        .score-elite {
            background-color: #dcfce7;
            color: #15803d;
        }
        .score-standard {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .score-risk {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class