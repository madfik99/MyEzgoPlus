@extends('layouts.main')

@section('page-title')
    {{ __('Delete Reservatio000000000000000n List') }}
@endsection

@section('page-breadcrumb')
    {{ __('Delete Reservation List') }}
@endsection

@section('content')

<div class="container">


  @if (session('success'))
    <div class="alert alert-success mt-3">{{ session('success') }}</div>
  @endif

  <div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5>{{ __('Delete Approval List') }}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive mt-3">

            <table class="table align-middle">
            <thead>
                <tr>
                <th>#</th>
                <th>Agreement No.</th>
                <th>Vehicle Plate</th>
                <th>Status</th>
                <th>Staff</th>
                <th>From Date & Time</th>
                <th>Reason</th>
                <th>View</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pending as $i => $b)
                <tr>
             
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $b->agreement_no }}</td>
                    <td>{{ $b->reg_no }}</td>
                    <td>{{ $b->available }}</td>
                    <td>{{ optional($b->staff)->name }}</td>
                    <td>
                    {{ $b->pickup_date ? \Carbon\Carbon::parse($b->pickup_date)->format('d/m/Y') : '' }} {{ $b->pickup_time }}
                    |
                    {{ $b->return_date ? \Carbon\Carbon::parse($b->return_date)->format('d/m/Y') : '' }} {{ $b->return_time }}
                    </td>

                    <td>{{ $b->reason }}</td>
                    <td>
                    <a href="{{ route('delete.request.show', $b->id) }}"><i class="fa fa-search"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8">No records found</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>
  </div>

  {{-- {{ $pending->links() }} --}}
</div>
@endsection
