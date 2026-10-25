@extends('layouts.app')
@section('title', $expense->exists ? 'Edit expense' : 'New expense')
@section('heading', $expense->exists ? 'Edit expense' : 'New expense')

@section('content')
  <div class="toolbar"><a href="{{ route('expenses.index') }}" class="link"><x-icon name="arrow-left" size="15" />Back to expenses</a></div>
  @include('expenses._form', ['modal' => false])
@endsection
