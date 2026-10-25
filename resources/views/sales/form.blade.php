@extends('layouts.app')
@section('title', $sale->exists ? 'Edit sale' : 'New sale')
@section('heading', $sale->exists ? 'Edit sale' : 'New sale')

@section('content')
  <div class="toolbar"><a href="{{ route('sales.index') }}" class="link"><x-icon name="arrow-left" size="15" />Back to sales</a></div>
  @include('sales._form', ['modal' => false])
@endsection
