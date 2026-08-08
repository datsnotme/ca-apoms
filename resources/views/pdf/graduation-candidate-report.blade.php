<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Graduation Evaluation Report — {{ $candidate->student->student_number }}</title>
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
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $college->name }}</h1>
        <p class="muted">Graduation Evaluation Report</p>
    </div>

    <table class="stats">
        <tr>
            <td>
                <div class="label">Student</div>
                <div class="value">{{ $candidate->student->first_name }} {{ $candidate->student->middle_name }} {{ $candidate->student->surname }}</div>
            </td>
            <td>
                <div class="label">Student No.</div>
                <div class="value">{{ $candidate->student->student_number }}</div>
            </td>
            <td>
                <div class="label">Status</div>
                <div class="value"><span class="badge">{{ str_replace('_', ' ', $candidate->status->value) }}</span></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Department / Program</div>
                <div class="value">{{ $candidate->student->department?->name }} / {{ $candidate->student->program?->name }}</div>
            </td>
            <td>
                <div class="label">Target Term</div>
                <div class="value">{{ $candidate->academicYear->start_year }}-{{ $candidate->academicYear->end_year }} {{ $candidate->semester->term->label() }}</div>
            </td>
            <td>
                <div class="label">GWA (at nomination)</div>
                <div class="value">{{ $candidate->gwa_snapshot ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <h2>Requirement Checklist</h2>
    @if ($candidate->requirements->isEmpty())
        <p class="muted">No requirement templates applied to this candidate's program.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Requirement</th>
                    <th>Status</th>
                    <th>Satisfied By</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($candidate->requirements as $requirement)
                    <tr>
                        <td>{{ $requirement->template->title }}</td>
                        <td>{{ $requirement->status->value }}</td>
                        <td>{{ $requirement->satisfiedBy?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Competency Evaluation</h2>
    @if ($candidate->competencyEvaluators->isEmpty())
        <p class="muted">No evaluators were assigned.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Evaluator</th>
                    <th>Indicator</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($candidate->competencyEvaluators as $evaluator)
                    @forelse ($evaluator->ratings as $rating)
                        <tr>
                            <td>{{ $evaluator->evaluator->name }}</td>
                            <td>{{ $rating->indicator->title }}</td>
                            <td>{{ $rating->rating }}/5</td>
                            <td>{{ $rating->remarks ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>{{ $evaluator->evaluator->name }}</td>
                            <td colspan="3" class="muted">No ratings submitted.</td>
                        </tr>
                    @endforelse
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Department Recommendation</h2>
    @if ($candidate->recommended_by)
        <p>Recommended by {{ $candidate->recommendedBy->name }} on {{ $candidate->recommended_at->format('F j, Y') }}.</p>
        @if ($candidate->recommendation_remarks)
            <p class="muted">"{{ $candidate->recommendation_remarks }}"</p>
        @endif
    @else
        <p class="muted">Not yet recommended.</p>
    @endif

    <h2>Dean's Decision</h2>
    @if ($candidate->decided_by)
        <p>{{ ucfirst($candidate->status->value) }} by {{ $candidate->decidedBy->name }} on {{ $candidate->decided_at->format('F j, Y') }}.</p>
        @if ($candidate->decision_remarks)
            <p class="muted">"{{ $candidate->decision_remarks }}"</p>
        @endif
    @else
        <p class="muted">No decision recorded yet.</p>
    @endif

    @if ($candidate->graduated_at)
        <h2>Graduation Conferred</h2>
        <p>Confirmed graduated on {{ $candidate->graduated_at->format('F j, Y') }}.</p>
    @endif

    <div class="footer">
        Generated by {{ $generatedBy->name }} on {{ now()->format('F j, Y g:i A') }} — CA-APOMS
    </div>
</body>
</html>
