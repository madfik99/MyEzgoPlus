@extends('layouts.main')

@section('page-title')
    {{ __('Reservation Counter') }}
@endsection

@section('page-breadcrumb')
    {{ __('Reservation Counter') }}
@endsection

@push('css')
    @include('layouts.includes.datatable-css')
    <style>
    .pagination {
        flex-wrap: wrap;
    }
    .pagination .page-link {
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="card p-4">
    @if (!empty($isEditing) && $isEditing)
        <h4>Reservation Edit</h4>
    @else
        <h4>{{ $customer ? 'Existing Customer' : 'New Customer' }}</h4>
    @endif


    <form method="GET" action="{{ route('reservation.search') }}">
        @csrf


        
        @if (!empty($bookingSummary))
        <div class="mb-3 border rounded p-3 bg-light">
            <h5 class="text-primary mb-3">Current Booking Information</h5>

            <input type="hidden" name="cdw_id" value="{{ $cdw_id }}">


            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Agreement Reference</div>
                <div class="col-md-9">{{ $bookingSummary['reference'] }} - {{ $bookingSummary['agreement_no'] }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Pickup Date & Time</div>
                <div class="col-md-3">{{ $bookingSummary['pickup_time'] }}</div>
                <div class="col-md-3 fw-bold">Return Date & Time</div>
                <div class="col-md-3">{{ $bookingSummary['return_time'] }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Pickup Location</div>
                <div class="col-md-3">{{ $bookingSummary['pickup_location'] }}</div>
                <div class="col-md-3 fw-bold">Return Location</div>
                <div class="col-md-3">{{ $bookingSummary['return_location'] }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Current Vehicle</div>
                <div class="col-md-9">{{ $bookingSummary['reg_no'] }} - {{ $bookingSummary['car_name'] }}</div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3 fw-bold">Coupon Code</div>
                <div class="col-md-3">{{ $bookingSummary['coupon'] }} </div>
                <div class="col-md-3 fw-bold">Agent Code</div>
                <div class="col-md-3">{{ $bookingSummary['agent'] }}</div>
            </div>

            <div class="mt-2 text-muted">
                <i>Please re-insert the required details for the booking below.</i>
            </div>
        </div>
        @endif



         {{-- NRIC --}}
         <div class="mb-3">
            <label for="nric">NRIC</label>
            <input type="text" name="nric" class="form-control bg-light" value="{{ $nric }}" readonly>
        </div>

        {{-- Show full name if existing customer --}}
        @if ($customer)
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control bg-light" value="{{ $customer->firstname . ' ' . $customer->lastname }}" readonly>
            </div>
        @endif

        {{-- Pickup Date & Time --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="search_pickup_date">Pickup Date & Time</label>
                <div class="input-group">
                    <input type="date" class="form-control" name="search_pickup_date"
                        value="{{ old('search_pickup_date', $search_pickup_date ?? '') }}" required>
                    <select name="search_pickup_time" class="form-select" required>
                        <option value="" disabled {{ old('search_pickup_time') ? '' : 'selected' }}>-- Time --</option>
                        @foreach (generateTimeOptions() as $time)
                            <option value="{{ $time }}"
                                {{ old('search_pickup_time', $search_pickup_time ?? '') === $time ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('H:i', $time)->format('h:i A') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Return Date & Time --}}
            <div class="col-md-6">
                <label for="search_return_date">Return Date & Time</label>
                <div class="input-group">
                    <input type="date" class="form-control" name="search_return_date"
                        value="{{ old('search_return_date', $search_return_date ?? '') }}" required>
                    <select name="search_return_time" class="form-select" required>
                        <option value="" disabled {{ old('search_return_time') ? '' : 'selected' }}>-- Time --</option>
                        @foreach (generateTimeOptions() as $time)
                            <option value="{{ $time }}"
                                {{ old('search_return_time', $search_return_time ?? '') === $time ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('H:i', $time)->format('h:i A') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>



        {{-- Pickup & Return Location --}}
        <div class="row mb-3">
            {{-- Pickup Location --}}
            <div class="col-md-6">
                <label for="search_pickup_location">Pickup Location</label>

                @if (!empty($isEditing) && $isEditing)
                    {{-- Disabled dropdown (readonly view mode) --}}
                    <select class="form-control" disabled>
                        <option value="" disabled>-- Select Pickup Location --</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ $search_pickup_location == $location->id ? 'selected' : '' }}>
                                {{ $location->description }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Hidden input to submit the selected value --}}
                    <input type="hidden" name="search_pickup_location" value="{{ $search_pickup_location }}">
                @else
                    {{-- Editable dropdown --}}
                    <select class="form-control" name="search_pickup_location" required>
                        <option value="" disabled {{ old('search_pickup_location', $search_pickup_location ?? '') == '' ? 'selected' : '' }}>-- Select Pickup Location --</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ old('search_pickup_location', $search_pickup_location ?? '') == $location->id ? 'selected' : '' }}>
                                {{ $location->description }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Return Location --}}
            <div class="col-md-6">
                <label for="search_return_location">Return Location</label>

                @if (!empty($isEditing) && $isEditing)
                    {{-- Disabled dropdown (readonly view mode) --}}
                    <select class="form-control" disabled>
                        <option value="" disabled>-- Select Return Location --</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ $search_return_location == $location->id ? 'selected' : '' }}>
                                {{ $location->description }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Hidden input to submit the selected value --}}
                    <input type="hidden" name="search_return_location" value="{{ $search_return_location }}">
                @else
                    {{-- Editable dropdown --}}
                    <select class="form-control" name="search_return_location" required>
                        <option value="" disabled {{ old('search_return_location', $search_return_location ?? '') == '' ? 'selected' : '' }}>-- Select Return Location --</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ old('search_return_location', $search_return_location ?? '') == $location->id ? 'selected' : '' }}>
                                {{ $location->description }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>


        {{-- Coupon and Agent Code --}}
        <div class="row mb-3">
    <div class="col-md-6">
        <label for="coupon" class="form-label d-flex align-items-center">
            <span>Coupon Code</span>
            @if($errors->has('coupon'))
                <span class="ms-2 text-danger">
                    <!-- X icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.146 10.354a.5.5 0 0 0 .708 0L8 9.207l1.146 1.147a.5.5 0 0 0 .708-.708L8.707 8.5l1.147-1.146a.5.5 0 0 0-.708-.708L8 7.793 6.854 6.646a.5.5 0 1 0-.708.708L7.293 8.5 6.146 9.646a.5.5 0 0 0 0 .708z"/>
                    </svg>
                </span>
            @elseif(!$errors->has('coupon') && isset($couponModel) && old('coupon', $coupon ?? '') == $couponModel->code)
                <span class="ms-2 text-success">
                    <!-- Checkmark icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.03 10.97a.75.75 0 0 0 1.07 0l3.992-3.992a.75.75 0 0 0-1.06-1.06L7.5 9.44l-1.47-1.47a.75.75 0 1 0-1.06 1.06l2 2z"/>
                    </svg>
                </span>
            @endif
        </label>
        <input type="text" name="coupon" class="form-control"
            value="{{ $errors->has('coupon') ? '' : old('coupon', $coupon ?? '') }}">
        @error('coupon')
            <div class="alert alert-danger mt-2 auto-dismiss-alert">{{ $message }}</div>
        @enderror
        @if(!$errors->has('coupon') && isset($couponModel) && old('coupon', $coupon ?? '') == $couponModel->code)
            <div class="alert alert-success mt-2 auto-dismiss-alert">
                Coupon <strong>{{ $couponModel->code }}</strong> is valid and applied.
            </div>
        @endif
    </div>

    <div class="col-md-6">
        <label for="agent_code" class="form-label">Agent Code</label>
        <input type="text" class="form-control bg-light" name="agent_code" value="{{ old('agent_code', $agent_code ?? '') }}" readonly>
    </div>
</div>


        {{-- Vehicle Selection --}}
        <div class="mb-3">
            <label for="search_vehicle">Search Vehicle</label>
            <select class="form-control" name="search_vehicle">
                <option value="">-- Select Vehicle --</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}"
                        {{ old('search_vehicle', $search_vehicle ?? '') == $vehicle->id ? 'selected' : '' }}>
                        {{ $vehicle->class_name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Driver Option --}}
        <div class="mb-3">
            <label for="search_driver">Driver Location</label>
            <select class="form-control" name="search_driver">
                <option value="" selected disabled>-- Select Driver Region --</option>
                <optgroup label="Alaskan/Hawaiian Time Zone">
                                                                <option value="AK">Alaska</option>
                                                                <option value="HI">Hawaii</option>
                                                            </optgroup>
                                                            <optgroup label="Pacific Time Zone">
                                                                <option value="CA">California</option>
                                                                <option value="NV">Nevada</option>
                                                                <option value="OR">Oregon</option>
                                                                <option value="WA">Washington</option>
                                                            </optgroup>
                                                            <optgroup label="Mountain Time Zone">
                                                                <option value="AZ">Arizona</option>
                                                                <option value="CO">Colorado</option>
                                                                <option value="ID">Idaho</option>
                                                                <option value="MT">Montana</option>
                                                                <option value="NE">Nebraska</option>
                                                                <option value="NM">New Mexico</option>
                                                                <option value="ND">North Dakota</option>
                                                                <option value="UT">Utah</option>
                                                                <option value="WY">Wyoming</option>
                                                            </optgroup>
                                                            <optgroup label="Central Time Zone">
                                                                <option value="AL">Alabama</option>
                                                                <option value="AR">Arkansas</option>
                                                                <option value="IL">Illinois</option>
                                                                <option value="IA">Iowa</option>
                                                                <option value="KS">Kansas</option>
                                                                <option value="KY">Kentucky</option>
                                                                <option value="LA">Louisiana</option>
                                                                <option value="MN">Minnesota</option>
                                                                <option value="MS">Mississippi</option>
                                                                <option value="MO">Missouri</option>
                                                                <option value="OK">Oklahoma</option>
                                                                <option value="SD">South Dakota</option>
                                                                <option value="TX">Texas</option>
                                                                <option value="TN">Tennessee</option>
                                                                <option value="WI">Wisconsin</option>
                                                            </optgroup>
                                                            <optgroup label="Asian Time Zone">
                                                                <option value="MY" selected>Malaysia</option>
                                                                <option value="JPN">Japan</option>
                                                                <option value="SGP">Singapore</option>
                                                            </optgroup>
                                                            <optgroup label="Eastern Time Zone">
                                                                <option value="CT">Connecticut</option>
                                                                <option value="DE">Delaware</option>
                                                                <option value="FL">Florida</option>
                                                                <option value="GA">Georgia</option>
                                                                <option value="IN">Indiana</option>
                                                                <option value="ME">Maine</option>
                                                                <option value="MD">Maryland</option>
                                                                <option value="MA">Massachusetts</option>
                                                                <option value="MI">Michigan</option>
                                                                <option value="NH">New Hampshire</option>
                                                                <option value="NJ">New Jersey</option>
                                                                <option value="NY">New York</option>
                                                                <option value="NC">North Carolina</option>
                                                                <option value="OH">Ohio</option>
                                                                <option value="PA">Pennsylvania</option>
                                                                <option value="RI">Rhode Island</option>
                                                                <option value="SC">South Carolina</option>
                                                                <option value="VT">Vermont</option>
                                                                <option value="VA">Virginia</option>
                                                                <option value="WV">West Virginia</option>
                                                            </optgroup>
            </select>
        </div>

        {{-- KLIA and Delivery Term --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="klia">KLIA Price?</label>
                <select class="form-control" name="klia">
                    <option value="" {{ old('klia', $klia ?? '') == '' ? 'selected' : '' }}>-- Please Select --</option>
                    <option value="yes" {{ old('klia', $klia ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no" {{ old('klia', $klia ?? '') == 'no' ? 'selected' : '' }}>No</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="opt">Delivery Term</label>
                <select class="form-control" name="opt">
                    <option value="" {{ old('opt', $opt ?? '') == '' ? 'selected' : '' }}>At Branch</option>
                    <option value="delivery" {{ old('opt', $opt ?? '') == 'delivery' ? 'selected' : '' }}>Delivery</option>
                    <option value="pickup" {{ old('opt', $opt ?? '') == 'pickup' ? 'selected' : '' }}>Pickup</option>
                </select>
            </div>
        </div>

        @if(!empty($isEditing))
            <input type="hidden" name="isEditing" value="1">
            <input type="hidden" name="bookingId" value="{{ $bookingId }}">
        @endif


        <div class="mb-3">
            <button type="submit" name="action" value="search" class="btn btn-info">Search</button>
            {{-- <button type="submit" name="action" value="submit" class="btn btn-primary">Submit Reservation</button> --}}
            @if (!empty($isEditing) && $isEditing)
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
            @else
                <a href="{{ route('reservation.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>

    </form>
    @if(isset($availableVehicles) && $availableVehicles->count() > 0)
        <div class="card mt-5">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Available Vehicles (Total: {{ $availableVehicles->total() }})</h5>
                <span class="text-end text-white-50">Page {{ $availableVehicles->currentPage() }} of {{ $availableVehicles->lastPage() }}</span>
            </div>

            <div class="card-body">
                {{-- Legend --}}
                <div class="mb-3">
                    <strong>Legend:</strong>
                    <span class="badge bg-success">Available (No Booking)</span>
                    <span class="badge bg-warning text-dark">Available (Booked)</span>
                    <span class="badge bg-info text-dark">Available (Out)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Reg No</th>
                                <th>Make & Model</th>
                                <th>Class</th>
                                <th>Color</th>
                                <th>Year</th>
                                <th>Total Sales(RM)</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availableVehicles as $index => $vehicle)
                                <tr>
                                    <td>{{ ($availableVehicles->firstItem() ?? 0) + $index }}</td>
                                    <td><strong>{{ $vehicle->reg_no }}</strong></td>
                                    <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                                    <td>{{ $vehicle->class->class_name ?? 'N/A' }}</td>
                                    <td>{{ $vehicle->color }}</td>
                                    <td>{{ $vehicle->year }}</td>
                                    <td>
                                        @php
                                            $vehicleTotalSale = $vehicle->sales->sum('total_sale');
                                        @endphp
                                        <span class="fw-bold">{{ number_format($vehicleTotalSale, 2) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $booking = $vehicle->latestBooking;
                                            $bookingStatus = $booking ? $booking->available : 'Available';
                                            $normalizedStatus = in_array($bookingStatus, ['Available', 'Park']) ? 'AvailableGroup' : $bookingStatus;
                                        @endphp

                                        @switch($normalizedStatus)
                                            @case('AvailableGroup')
                                                <span class="badge bg-success">{{ $vehicle->availability }} (No Booking)</span>
                                                @break
                                            @case('Booked')
                                                <span class="badge bg-warning text-dark">{{ $vehicle->availability }} (Booked)</span>
                                                @break
                                            @case('Out')
                                                <span class="badge bg-info text-dark">{{ $vehicle->availability }} (Out)</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $bookingStatus }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                    @php
                                        $couponForLink = !$errors->has('coupon') && isset($validCouponCode) ? $validCouponCode : null;
                                    @endphp

                                    @if (!empty($isEditing) && $isEditing)
                                        <a href="{{ route('reservation.change_vehicle', [
                                            'booking_id' => $bookingId,
                                            'vehicle_id' => $vehicle->id,
                                            'search_pickup_date' => $search_pickup_date ?? request('search_pickup_date'),
                                            'search_pickup_time' => $search_pickup_time ?? request('search_pickup_time'),
                                            'search_return_date' => $search_return_date ?? request('search_return_date'),
                                            'search_return_time' => $search_return_time ?? request('search_return_time'),
                                            'search_pickup_location' => $search_pickup_location ?? request('search_pickup_location'),
                                            'search_return_location' => $search_return_location ?? request('search_return_location'),
                                            'class_id' => $vehicle->class_id,
                                            'coupon' => $couponForLink,
                                            'agent_code' => $agent_code ?? request('agent_code'),
                                            'search_vehicle' => $search_vehicle ?? request('search_vehicle'),
                                            'search_driver' => $search_driver ?? request('search_driver'),
                                            'klia' => $klia ?? request('klia'),
                                            'opt' => $opt ?? request('opt'),
                                        ]) }}" class="btn btn-sm btn-warning">
                                            <i class="fa fa-exchange"></i> Change
                                        </a>
                                    @else
                                        <a href="{{ route('reservation.counter_reservation_filter', [
                                            'nric' => $nric ?? request('nric'),
                                            'vehicle_id' => $vehicle->id,
                                            'search_pickup_date' => $search_pickup_date ?? request('search_pickup_date'),
                                            'search_pickup_time' => $search_pickup_time ?? request('search_pickup_time'),
                                            'search_return_date' => $search_return_date ?? request('search_return_date'),
                                            'search_return_time' => $search_return_time ?? request('search_return_time'),
                                            'search_pickup_location' => $search_pickup_location ?? request('search_pickup_location'),
                                            'search_return_location' => $search_return_location ?? request('search_return_location'),
                                            'class_id' => $vehicle->class_id,
                                            'coupon' => $couponForLink,
                                            'agent_code' => $agent_code ?? request('agent_code'),
                                            'search_vehicle' => $search_vehicle ?? request('search_vehicle'),
                                            'search_driver' => $search_driver ?? request('search_driver'),
                                            'klia' => $klia ?? request('klia'),
                                            'opt' => $opt ?? request('opt'),
                                        ]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-pencil"></i> Book Now
                                        </a>
                                    @endif
                                </td>




                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3 d-flex justify-content-center">
                    {{ $availableVehicles->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                </div>
                
                
            </div>
            
        </div>
    @endif




</div>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            document.querySelectorAll('.auto-dismiss-alert').forEach(function(alert) {
                // For Bootstrap 5 fade effect
                alert.classList.add('fade');
                alert.classList.remove('show');
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500); // wait for fade transition to finish
            });
        }, 7000); // 7000 ms = 7 seconds, adjust to 5000 or 10000 as needed
    });
</script>

@push('scripts')
    @include('layouts.includes.datatable-js')
    <script>
        $(document).on("click", ".cp_link", function () {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            toastrs('success', '{{ __('Link Copy on Clipboard') }}', 'success');
        });
    </script>
@endpush

@php
    function generateTimeOptions($start = '08:00', $end = '22:30', $interval = 30) {
        $times = [];
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);

        while ($startTime <= $endTime) {
            $times[] = $startTime->format('H:i'); // e.g. "08:00", "08:30"
            $startTime->addMinutes($interval);
        }

        return $times;
    }
@endphp