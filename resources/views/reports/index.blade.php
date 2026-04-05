@extends('adminlte::page')
@section('title', $reportTitle)

@section('content_header')
<div class="container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h1 class="mb-1">{{ $reportTitle }}</h1>
            <small class="text-muted">Periode: {{ $dateRangeLabel }}</small>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <div class="btn-group flex-wrap">
                <a href="{{ route('dashboard.rapports', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm {{ $reportType === 'general' ? 'btn-primary' : 'btn-default' }}">General</a>
                <a href="{{ route('rapports.financier', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm {{ $reportType === 'financier' ? 'btn-primary' : 'btn-default' }}">Financier</a>
                <a href="{{ route('rapports.frequentation', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm {{ $reportType === 'frequentation' ? 'btn-primary' : 'btn-default' }}">Frequentation</a>
                <a href="{{ route('rapports.assurances', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm {{ $reportType === 'assurances' ? 'btn-primary' : 'btn-default' }}">Assurances</a>
                <a href="{{ route('rapports.subscriptions', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm {{ $reportType === 'subscriptions' ? 'btn-primary' : 'btn-default' }}">Subscriptions</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ request()->url() }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_from">Date de debut</label>
                            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_to">Date de fin</label>
                            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary mr-2">Afficher</button>
                            <a href="{{ route('rapports.pdf', ['type' => $reportType, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-danger">Exporter PDF</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach($reportCards as $card)
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $card['value'] }}</h3>
                        <p>{{ $card['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $reportTitle }}</h3>
        </div>
        <div class="card-body">
            <p class="text-muted">{{ $reportDescription }}</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            @foreach($reportColumns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportRows as $row)
                            <tr>
                                @foreach($reportColumns as $column)
                                    <td>{{ $row[$column] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($reportColumns) }}" class="text-center text-muted py-4">Aucune donnee pour cette periode</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
