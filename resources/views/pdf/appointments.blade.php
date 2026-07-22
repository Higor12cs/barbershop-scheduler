@extends('pdf.layout')

@section('title', 'Relatório de Agendamentos')

@php
    $rate = fn ($value): string => $value === null ? '—' : number_format((float) $value, 1, ',', '.') . '%';
@endphp

@section('content')
    <section>
        <h2>Resumo</h2>
        <table class="summary">
            <tr>
                <td class="summary-label">Total de Agendamentos</td>
                <td class="summary-value">{{ $appointments['summary']['total'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">Taxa de Comparecimento</td>
                <td class="summary-value">{{ $rate($appointments['summary']['attendance_rate']) }}</td>
            </tr>
        </table>
    </section>

    <section>
        <h2>Agendamentos por Status</h2>
        <table class="data">
            <thead>
                <tr>
                    <th class="name">Status</th>
                    <th class="num">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($appointments['summary']['statuses'] as $status)
                    <tr>
                        <td class="name">{{ $status['label'] }}</td>
                        <td class="num">{{ $status['count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num">{{ $appointments['summary']['total'] }}</td>
                </tr>
            </tfoot>
        </table>
    </section>

    <section>
        <h2>Agendamentos por Funcionário</h2>
        @if (count($appointments['byEmployee']))
            <table class="data">
                <thead>
                    <tr>
                        <th class="name">Funcionário</th>
                        <th class="num">Agendados</th>
                        <th class="num">Confirmados</th>
                        <th class="num">Finalizados</th>
                        <th class="num">Cancelados</th>
                        <th class="num">Não Compareceu</th>
                        <th class="num">Taxa de Conclusão</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments['byEmployee'] as $row)
                        <tr>
                            <td class="name">{{ $row['name'] }}</td>
                            <td class="num">{{ $row['scheduled'] }}</td>
                            <td class="num">{{ $row['confirmed'] }}</td>
                            <td class="num">{{ $row['completed'] }}</td>
                            <td class="num">{{ $row['cancelled'] }}</td>
                            <td class="num">{{ $row['no_show'] }}</td>
                            <td class="num">{{ $rate($row['completion_rate']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Nenhum agendamento no período.</div>
        @endif
    </section>
@endsection
