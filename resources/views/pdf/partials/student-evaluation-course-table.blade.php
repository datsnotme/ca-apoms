{{-- Expects: $rows, $totalLectureHours, $totalLaboratoryHours, $totalUnits --}}
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
                <td>{{ $row['course']['code'] }}</td>
                <td>{{ $row['course']['title'] }}</td>
                <td class="num">{{ $row['course']['lecture_hours'] }}</td>
                <td class="num">{{ $row['course']['laboratory_hours'] }}</td>
                <td class="num">{{ $row['units'] }}</td>
                <td class="num {{ in_array($row['status'], ['failed', 'incomplete', 'dropped']) ? 'badge-flag' : '' }}">{{ $row['grade'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;color:#64748b;">No courses listed.</td></tr>
        @endforelse
        <tr class="total-row">
            <td colspan="2">TOTAL</td>
            <td class="num">{{ $totalLectureHours }}</td>
            <td class="num">{{ $totalLaboratoryHours }}</td>
            <td class="num">{{ $totalUnits }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
