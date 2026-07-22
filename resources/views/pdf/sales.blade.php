@extends('pdf.layout')

@section('title', 'Relatório de Vendas')

@php
    $brl = fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $pct = fn ($value): string => number_format((float) $value, 1, ',', '.') . '%';
@endphp

@section('content')
    <section>
        <h2>Resumo</h2>
        <table class="summary">
            <tr>
                <td class="summary-label">Total Faturado</td>
                <td class="summary-value">{{ $brl($sales['summary']['total']) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Quantidade de Vendas</td>
                <td class="summary-value">{{ $sales['summary']['count'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">Ticket Médio</td>
                <td class="summary-value">{{ $brl($sales['summary']['average']) }}</td>
            </tr>
        </table>
    </section>

    <section>
        <h2>Vendas por Funcionário</h2>
        @if (count($sales['byEmployee']))
            <table class="data">
                <thead>
                    <tr>
                        <th class="name">Funcionário</th>
                        <th class="num">Vendas</th>
                        <th class="num">Itens</th>
                        <th class="num">Total</th>
                        <th class="num">Ticket Médio</th>
                        <th class="num">% do Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales['byEmployee'] as $row)
                        <tr>
                            <td class="name">{{ $row['name'] }}</td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="num">{{ $row['items'] }}</td>
                            <td class="num">{{ $brl($row['total']) }}</td>
                            <td class="num">{{ $brl($row['average']) }}</td>
                            <td class="num">{{ $pct($row['percent']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="num">{{ $sales['summary']['count'] }}</td>
                        <td class="num"></td>
                        <td class="num">{{ $brl($sales['summary']['total']) }}</td>
                        <td class="num"></td>
                        <td class="num"></td>
                    </tr>
                </tfoot>
            </table>
        @else
            <div class="empty">Nenhuma venda no período.</div>
        @endif
    </section>

    <section>
        <h2>Vendas por Produto e Serviço</h2>
        @if (count($sales['byProduct']))
            <table class="data">
                <thead>
                    <tr>
                        <th class="name">Produto</th>
                        <th class="num">Quantidade</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales['byProduct'] as $row)
                        <tr>
                            <td class="name">{{ $row['name'] }}</td>
                            <td class="num">{{ $row['quantity'] }}</td>
                            <td class="num">{{ $brl($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Nenhum item vendido no período.</div>
        @endif
    </section>
@endsection
