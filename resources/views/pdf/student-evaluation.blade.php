<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Individual Student Evaluation — {{ $student->student_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        .muted { color: #64748b; }
        .header { text-align: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; font-size: 10px; }
        th { background: #f1f5f9; }
        .stats { width: 100%; margin-bottom: 12px; }
        .stats td { border: none; padding: 4px 12px 4px 0; }
        .stats .label { color: #64748b; font-size: 9px; text-transform: uppercase; }
        .stats .value { font-size: 14px; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9px; text-transform: uppercase; background: #e2e8f0; }
        .badge-flagged { background: #fee2e2; color: #991b1b; }
        .bucket-summary { color: #64748b; font-size: 9px; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { border: none; text-align: center; padding-top: 24px; font-size: 10px; }
        .sig-line { border-top: 1px solid #1e293b; padding-top: 4px; margin: 0 12px; }
        .sig-title { color: #64748b; font-size: 9px; text-transform: uppercase; margin-top: 2px; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $college->name ?? 'College of Agriculture' }}</h1>
        <p class="muted">Individual Student Evaluation</p>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="label">Student</div>
                <div class="value">{{ $student->name }}</div>
            </td>
            <td>
                <div class="label">Student No.</div>
                <div class="value">{{ $student->student_number }}</div>
            </td>
            <td>
                <div class="label">Year Level</div>
                <div class="value">{{ $student->yearLevel?->label ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Department / Program</div>
                <div class="value">{{ $student->department?->name }} / {{ $student->program?->name }}</div>
            </td>
            <td>
                <div class="label">Curriculum</div>
                <div class="value">{{ $student->curriculum?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="label">GWA</div>
                <div class="value">{{ $gwa ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Curriculum Complete</div>
                <div class="value">{{ $completionPercentage }}%</div>
            </td>
            <td colspan="2">
                <div class="label">Suggested Classification (evaluator to confirm)</div>
                <div class="value"><span class="badge {{ $suggestedClassification === 'irregular' ? 'badge-flagged' : '' }}">{{ ucfirst($suggestedClassification) }}</span></div>
            </td>
        </tr>
    </table>

    @foreach ($buckets as $group)
        <h2>
            {{ $group['label'] }}
            <span class="bucket-summary">({{ $group['earned_units'] }} / {{ $group['total_units'] }} units earned)</span>
        </h2>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Title</th>
                    <th>Units</th>
                    <th>Year / Sem</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                    <tr>
                        <td>{{ $row['course']['code'] }}</td>
                        <td>{{ $row['course']['title'] }}</td>
                        <td>{{ $row['units'] }}</td>
                        <td>Y{{ $row['year_level'] }} / {{ $row['semester'] }}</td>
                        <td>{{ $row['grade'] ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $row['is_deficiency'] || in_array($row['status'], ['failed', 'incomplete', 'dropped']) ? 'badge-flagged' : '' }}">
                                {{ str_replace('_', ' ', $row['status']) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <h2>Non-Academic Requirements</h2>
    <table>
        <thead>
            <tr>
                <th>Requirement</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Work Experience</td><td></td><td></td></tr>
            <tr><td>Tree Planting</td><td></td><td></td></tr>
            <tr><td>CVAC</td><td></td><td></td></tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">{{ $generatedBy->name }}</div>
                <div class="sig-title">Evaluated by</div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">&nbsp;</div>
                <div class="sig-title">Verified by</div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">&nbsp;</div>
                <div class="sig-title">Noted by</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F j, Y g:i A') }} — CA-APOMS
    </div>
</body>
</html>
