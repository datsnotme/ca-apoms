{{-- Expects: $rows (Collection<StudentHistoricalGrade>), $totalUnits --}}
<table>
    <thead>
        <tr>
            <th class="col-code">Course No.</th>
            <th class="col-title">Descriptive Title</th>
            <th class="col-hours">Lec</th>
            <th class="col-hours">Lab</th>
            <th class="col-units">Units</th>
            <th class="col-grade">Grade</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row->course_code }}</td>
                <td>{{ $row->course_title }}</td>
                <td class="num">{{ $row->lecture_hours ?? '—' }}</td>
                <td class="num">{{ $row->laboratory_hours ?? '—' }}</td>
                <td class="num">{{ $row->units }}</td>
                <td class="num">{{ $row->grade ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#64748b;">No courses listed.</td></tr>
        @endforelse
        <tr class="total-row">
            <td colspan="4">TOTAL</td>
            <td class="num">{{ $totalUnits }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
