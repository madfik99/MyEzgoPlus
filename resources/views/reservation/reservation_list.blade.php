@extends('layouts.main')

@section('page-title')
    {{ __('Manage Reservation List') }}
@endsection

@section('page-breadcrumb')
    {{ __('Reservation List') }}
@endsection

@push('css')
    @include('layouts.includes.datatable-css')
    <style>
        .reservation-table th, .reservation-table td {
            vertical-align: middle !important;
        }
        .card-header {
            border-bottom: 1px solid #ececec;
        }
        .card {
            border-radius: 1.2rem;
            box-shadow: 0 2px 8px rgba(80,90,110,.04);
        }
        .pagination.pagination-sm .page-link {
            padding: 0.20rem 0.7rem;
            font-size: 0.92rem;
            min-width: 28px;
        }
        .pagination {
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }
    </style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        {{-- Filter/Search Section --}}
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-search text-primary"></i> Filter Reservation List</h5>
            </div>
            <div class="card-body pb-2">
                <form class="row g-3 align-items-end" method="GET" action="">
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label">Search NRIC/Name/Plate/Agreement</label>
                        <input type="text" class="form-control form-control-sm" name="search_nricno" value="{{ request('search_nricno') }}">
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Class</label>
                        <select name="search_vehicle" class="form-control form-control-sm">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('search_vehicle') == $class->id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Search In</label>
                        <select class="form-control form-control-sm" name="search_type">
                            <option value="" {{ request('search_type') == '' ? 'selected' : '' }}>All</option>
                            <option value="type_agreement" {{ request('search_type') == 'type_agreement' ? 'selected' : '' }}>Agreement</option>
                            <option value="type_name" {{ request('search_type') == 'type_name' ? 'selected' : '' }}>Name</option>
                            <option value="type_nric" {{ request('search_type') == 'type_nric' ? 'selected' : '' }}>NRIC</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label">Status</label>
                        <select class="form-control form-control-sm" name="search_status">
                            <option value="" {{ request('search_status') == '' ? 'selected' : '' }}>All</option>
                            <option value="BookedOutExtend" {{ request('search_status') == 'BookedOutExtend' ? 'selected' : '' }}>Booked/Out/Extend</option>
                            <option value="Booked" {{ request('search_status') == 'Booked' ? 'selected' : '' }}>Booked</option>
                            <option value="Out" {{ request('search_status') == 'Out' ? 'selected' : '' }}>Out</option>
                            <option value="Extend" {{ request('search_status') == 'Extend' ? 'selected' : '' }}>Extend</option>
                            <option value="Park" {{ request('search_status') == 'Park' ? 'selected' : '' }}>Park</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 text-end d-flex gap-2 align-items-end">
    <button type="submit" class="btn btn-primary btn-sm w-100">
        <i class="fa fa-search"></i> 
    </button>
    <a href="{{ route('reservation.reservation_list') }}" class="btn btn-secondary btn-sm w-100">
        <i class="fa fa-eraser"></i> 
    </a>
</div>


                </form>
            </div>
        </div>

        {{-- Reservation Table Section --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-list-alt text-primary"></i> Reservation List</h5>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle reservation-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Agreement No.</th>
                                <th>Vehicle Plate</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($reservations as $i => $reservation)
                            <tr>
                                <td>{{ $loop->iteration + ($reservations->firstItem() - 1) }}</td>
                                <td>
                                    <span class="fw-bold">{{ $reservation->agreement_no }}</span>
                                    @if($reservation->has_incomplete_payment)
                                        <br>
                                        <span class="badge bg-danger mt-1" title="Incomplete Payment">
                                            <i class="fa fa-exclamation-triangle"></i> Incomplete Payment
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $reservation->vehicle?->reg_no ?? '-' }}</span>
                                    <div class="text-muted small">
                                        {{ $reservation->vehicle?->class?->class_name ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold">
                                        {{ $reservation->customer?->firstname ?? '' }} {{ $reservation->customer?->lastname ?? '' }}
                                    </div>
                                    <div class="text-muted small">{{ $reservation->customer?->nric_no ?? '-' }}</div>
                                </td>
                                <td>
                                    <span title="Pickup">
                                        <i class="fa fa-calendar text-primary"></i>
                                        {{ \Carbon\Carbon::parse($reservation->pickup_date)->format('d/m/Y') }}
                                        <i class="fa fa-clock text-primary"></i>
                                        {{ $reservation->pickup_time }}
                                    </span>
                                    <br>
                                    <span title="Return" class="text-muted">
                                        <i class="fa fa-calendar"></i>
                                        {{ \Carbon\Carbon::parse($reservation->return_date)->format('d/m/Y') }}
                                        <i class="fa fa-clock"></i>
                                        {{ $reservation->return_time }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'Booked' => 'warning',
                                            'Out' => 'info',
                                            'Extend' => 'success',
                                            'Park' => 'secondary',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$reservation->available] ?? 'light' }}">
                                        {{ $reservation->available }}
                                    </span>
                                </td>
                                <td>
                                    @if($reservation->delete_status === "pending")
                                        <i><small>Can't view - pending to be deleted</small></i>
                                    @else
                                        <a href="{{ route('reservation.view', $reservation->id) }}"
                                            class="btn btn-outline-primary btn-sm"
                                            title="View Details">
                                            <i class="fa fa-search"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No records found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">
                    {{ $reservations->appends(request()->except('page'))->links('pagination::bootstrap-5') }}

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('layouts.includes.datatable-js')
@endpush
