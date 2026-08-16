<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Evaluation — {{ $student->student_number }}</title>
    <style>
        /* Long bond paper (8.5 x 13in), matching the college's own
           curriculum prospectus layout — see ASSUMPTIONS.md. Standard
           letter size is too short to fit a multi-year course table. */
        @page { size: 612pt 936pt; margin: 0.25in 0.3in; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #1e293b; line-height: 1.05; }
        .header { text-align: center; margin-bottom: 3px; }
        .header .republic { font-size: 9px; }
        .header .university { font-size: 9.5px; font-weight: bold; }
        .header .college { font-size: 8.5px; }
        .header .subtitle { font-size: 9.5px; font-weight: bold; text-transform: uppercase; margin-top: 2px; border-top: 1.5px solid #235e2f; border-bottom: 1.5px solid #235e2f; padding: 2px 0; }

        .info-table { width: 100%; margin-bottom: 3px; }
        .info-table td { border: none; padding: 0.5px 0; font-size: 7.5px; }
        .info-table .label { color: #64748b; }

        .year-header { background: #123018; color: #ffffff; font-size: 8px; font-weight: bold; text-transform: uppercase; text-align: center; padding: 1.5px; margin-bottom: 1px; letter-spacing: 0.5px; }

        .grid-row { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin-bottom: 2px; }
        .grid-row td { vertical-align: top; padding: 0; }
        .box { border: 1px solid #1e293b; }
        .box-title { background: #235e2f; color: #ffffff; font-size: 7px; text-transform: uppercase; text-align: center; padding: 1.5px; font-weight: bold; }
        .box table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .box th, .box td { border: 1px solid #cbd5e1; padding: 0.5px 2px; font-size: 6.3px; text-align: left; word-wrap: break-word; }
        .box th { background: #f1f5f9; text-transform: uppercase; font-size: 5.5px; text-align: center; }
        .box .col-code { width: 14%; }
        .box .col-title { width: 42%; }
        .box .col-hours { width: 10%; text-align: center; }
        .box .col-units { width: 12%; text-align: center; }
        .box .col-grade { width: 12%; text-align: center; }
        .box td.num { text-align: center; }
        .box .total-row td { border-top: 1.5px solid #1e293b; font-weight: bold; background: #f8fafc; }
        .badge-flag { color: #b91c1c; font-weight: bold; }

        .req-line { padding: 1px 5px; font-size: 7px; }
        .req-line .fill { display: inline-block; border-bottom: 1px solid #64748b; min-width: 55%; }

        .summary-table { width: 100%; }
        .summary-table td { padding: 0.5px 4px; font-size: 7px; border-bottom: 1px dotted #cbd5e1; }
        .summary-table .val { text-align: right; font-weight: bold; width: 30%; }
        .summary-table .section-label { padding-top: 2px; font-weight: bold; text-transform: uppercase; font-size: 6px; color: #235e2f; border-bottom: none; }

        .signatures { width: 100%; margin-top: 6px; }
        .signatures td { border: none; text-align: center; padding-top: 10px; font-size: 7.5px; }
        .sig-line { border-top: 1px solid #1e293b; padding-top: 2px; margin: 0 10px; }
        .sig-title { font-weight: bold; margin-top: 1px; }
        .sig-caption { color: #64748b; font-size: 7px; }

        .footer { margin-top: 8px; font-size: 7px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="republic">Republic of the Philippines</div>
        <div class="university">{{ $college->name ?? 'College of Agriculture' }}</div>
        @if ($college?->address)
            <div class="college">{{ $college->address }}</div>
        @endif
        <div class="subtitle">Individual Student Evaluation</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 25%;"><span class="label">Name:</span> {{ $student->name }}</td>
            <td style="width: 25%;"><span class="label">Student No.:</span> {{ $student->student_number }}</td>
            <td style="width: 25%;"><span class="label">Year Level:</span> {{ $student->yearLevel?->label ?? '—' }}</td>
            <td style="width: 25%;"><span class="label">GWA:</span> {{ $gwa ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Program / Curriculum:</span> {{ $student->program?->name ?? '—' }} / {{ $student->curriculum?->name ?? '—' }}</td>
            <td colspan="2">
                <span class="label">Suggested Classification (evaluator to confirm):</span>
                <strong class="{{ $suggestedClassification === 'irregular' ? 'badge-flag' : '' }}">{{ ucfirst($suggestedClassification) }}</strong>
            </td>
        </tr>
    </table>

    @php
        $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year', 6 => '6th Year'];
        $semesterLabels = ['FIRST' => '1st Semester', 'SECOND' => '2nd Semester', 'SUMMER' => 'Summer'];
    @endphp

    @foreach ($years as $yearGroup)
        <div class="year-header">{{ $yearLabels[$yearGroup['year_level']] ?? "Year {$yearGroup['year_level']}" }}</div>

        @php
            $mainSemesters = $yearGroup['semesters']->whereIn('semester', ['FIRST', 'SECOND'])->values();
            $summerSemester = $yearGroup['semesters']->firstWhere('semester', 'SUMMER');
        @endphp

        @if ($mainSemesters->isNotEmpty())
            <table class="grid-row">
                <tr>
                    @foreach ($mainSemesters as $semGroup)
                        <td style="width: {{ round(100 / max($mainSemesters->count(), 1), 2) }}%;">
                            <div class="box">
                                <div class="box-title">{{ $semesterLabels[$semGroup['semester']] ?? $semGroup['semester'] }}</div>
                                @include('pdf.partials.student-evaluation-course-table', [
                                    'rows' => $semGroup['rows'],
                                    'totalLectureHours' => $semGroup['total_lecture_hours'],
                                    'totalLaboratoryHours' => $semGroup['total_laboratory_hours'],
                                    'totalUnits' => $semGroup['total_units'],
                                ])
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif

        @if ($summerSemester)
            <table class="grid-row">
                <tr>
                    <td style="width: 50%;">
                        <div class="box">
                            <div class="box-title">Summer</div>
                            @include('pdf.partials.student-evaluation-course-table', [
                                'rows' => $summerSemester['rows'],
                                'totalLectureHours' => $summerSemester['total_lecture_hours'],
                                'totalLaboratoryHours' => $summerSemester['total_laboratory_hours'],
                                'totalUnits' => $summerSemester['total_units'],
                            ])
                        </div>
                    </td>
                </tr>
            </table>
        @endif
    @endforeach

    @if ($priorAcademicRecord->isNotEmpty())
        <div class="year-header">Prior Academic Record (Imported)</div>

        @foreach ($priorAcademicRecord->chunk(2) as $pair)
            <table class="grid-row">
                <tr>
                    @foreach ($pair as $termGroup)
                        <td style="width: {{ round(100 / max($pair->count(), 1), 2) }}%;">
                            <div class="box">
                                <div class="box-title">
                                    {{ $termGroup['semester_label'] }} {{ $termGroup['academic_year_label'] }}
                                    @if ($termGroup['program_label'])
                                        — {{ $termGroup['program_label'] }}
                                    @endif
                                </div>
                                @include('pdf.partials.student-evaluation-prior-record-table', [
                                    'rows' => $termGroup['rows'],
                                    'totalUnits' => $termGroup['total_units'],
                                ])
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endforeach
    @endif

    <table class="grid-row">
        <tr>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">Other Requirements</div>
                    <div class="req-line">A. Work Experience <span class="fill">&nbsp;</span></div>
                    <div class="req-line">B. Tree Planting <span class="fill">&nbsp;</span></div>
                    <div class="req-line">C. CVAC <span class="fill">&nbsp;</span></div>
                </div>
            </td>
            <td style="width: 50%;">
                <div class="box">
                    <div class="box-title">Summary</div>
                    <table class="summary-table">
                        <tr><td>Total Units Required</td><td class="val">{{ $summary['total_units_required'] }}</td></tr>
                        <tr><td>Total Units Earned</td><td class="val">{{ $summary['total_units_earned'] }}</td></tr>
                        <tr><td>*Currently Enrolled</td><td class="val">{{ $summary['currently_enrolled_units'] }}</td></tr>
                        <tr><td>**Taken but no grade</td><td class="val">{{ $summary['taken_no_grade_units'] }}</td></tr>
                        <tr><td>***Incomplete</td><td class="val">{{ $summary['incomplete_units'] }}</td></tr>
                        <tr><td>Remaining Units</td><td class="val">{{ $summary['remaining_units'] }}</td></tr>
                        <tr><td class="section-label" colspan="2">By Requirement Category (Earned / Required)</td></tr>
                        @foreach ($bucketSummary as $b)
                            <tr><td>{{ $b['label'] }}</td><td class="val">{{ $b['earned_units'] }} / {{ $b['total_units'] }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">&nbsp;</div>
                <div class="sig-title">Department Chairperson</div>
                <div class="sig-caption">Evaluated by</div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">&nbsp;</div>
                <div class="sig-title">Campus Registrar</div>
                <div class="sig-caption">Verified by</div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">&nbsp;</div>
                <div class="sig-title">Dean</div>
                <div class="sig-caption">Noted by</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F j, Y g:i A') }} — CA-APOMS
    </div>
</body>
</html>
