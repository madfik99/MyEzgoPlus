@extends('layouts.main')

@section('page-title', __('Reservation Summary'))
@section('page-breadcrumb', __('Reservation Summary'))

@push('css')
    @include('layouts.includes.datatable-css')
    <style>
        .summary-section-header {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .table th, .table td { vertical-align: middle; }
        .add-ons-total {
            background: #f2f2f2 !important;
            color: #000000;
            font-weight: bold;
        }
        .grand-total {
            background: dimgrey !important;
            color: #fff !important;
            font-weight: bold;
        }
        
    </style>
@endpush

@section('content')

{{-- CARD 1: NRIC --}}
<br>
{{-- <div class="card mb-4">
    <div class="card-header ">
        <h4 class="mb-0">NRIC</h4>
    </div>
    <div class="card-body">
        <div class="row mb-3 justify-content-center">
            <div class="col-md-6 col-sm-8">
                <div class="row align-items-center">
                    <!-- Label on the left -->
                    <label class="col-sm-4 col-form-label">NRIC No</label>

                    <!-- Input on the right -->
                    <div class="col-sm-8">
                        <input type="text" class="form-control bg-light" 
                            value="{{ $customer->nric_no ?? $validated['nric'] }}" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div> --}}

{{-- CARD 2: RESERVATION DETAILS --}}
<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Details</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Left: Vehicle/Charges -->
            <div class="col-md-6 col-12">
                <div class="summary-section-header"><i class="fa fa-car"></i> Vehicle</div>
                <div class="mb-3">
                    <span>{{ $vehicle->reg_no ?? '-' }} - {{ $vehicle->class->class_name ?? '-' }}</span>
                    <span class="float-end badge">RM {{ number_format($rentalTotal, 2) }}</span>
                </div>

                <div class="summary-section-header"><i class="ti ti-ticket"></i> Coupon</div>
                <div class="mb-3">
                    <span>{{ $couponLabel }}</span>
                    <span class="float-end badge text-secondary">
                        {{ $couponValueLabel }}
                    </span>
                </div>

                <div class="summary-section-header"><i class="fa fa-calendar"></i> Date</div>
<div class="mb-3">
    <table class="w-100" >
        <tr>
            <td class="text-nowrap">From:</td>
            <td class="text-end">{{ $validated['search_pickup_date'] }} @ {{ $validated['search_pickup_time'] }}</td>
        </tr>
        <tr>
            <td class="text-nowrap">To:</td>
            <td class="text-end">
                {{ $validated['search_return_date'] }} @ {{ $validated['search_return_time'] }}
                @if(($freeDays ?? 0) > 0 || ($freeHours ?? 0) > 0)
                    <br>
                    <span class="text-success small">
                        Extended: {{ $displayEndDate }} @ {{ $displayEndTime }}
                    </span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-nowrap">Duration:</td>
            <td class="text-end">
                <span>
                    {{ $origDay ?? $rentalDays }} Day(s){{ ($origHour ?? $rentalHours) ? ' '.($origHour ?? $rentalHours).' Hour(s)' : '' }}
                </span>
                @if(($freeDays ?? 0) > 0 || ($freeHours ?? 0) > 0)
                    <br>
                    <span class="text-info small">
                        + 
                        @if(($freeDays ?? 0) > 0)
                            {{ $freeDays }} Day(s)
                        @endif
                        @if(($freeDays ?? 0) > 0 && ($freeHours ?? 0) > 0)
                            &nbsp;
                        @endif
                        @if(($freeHours ?? 0) > 0)
                            {{ $freeHours }} Hour(s)
                        @endif
                        (Free)
                    </span>
                    <br>
                    <span class="fw-bold text-success">
                        {{ $displayDay }} Day(s) {{ $displayHour }} Hour(s)
                    </span>
                @endif
            </td>
        </tr>
    </table>
</div>


                <div class="summary-section-header"><i class="fa fa-map-marker"></i> Location</div>
                <div class="mb-3">
                    <div>Pick-Up <span class="float-end">{{ $pickupLocation->description ?? '-' }}</span></div>
                    <div>Drop-off <span class="float-end">{{ $returnLocation->description ?? '-' }}</span></div>
                </div>
                @if(!empty($validated['agent_code']))
                <div class="alert alert-info">
                    <div class="mb-1 text-primary"><i class="fa fa-user-secret"></i> <b>Agent Details</b></div>
                    <div>Agent Code: <span class="float-end">{{ $validated['agent_code'] }}</span></div>
                </div>
                @endif
            </div>

            <!-- Right: Charges/Total -->
            <div class="col-md-6 col-12">
                <div class="summary-section-header"><i class="fa-solid fa-shield"></i>Collision Damage Waiver (CDW)</div>
                <div class="mb-3">
                    <span>
                        SCDW {{ $cdw->rate->name ?? '-' }} ({{ $cdw->rate->rate ?? '0' }}%)
                    </span>
                    <span class="float-end badge bg-danger">+RM {{ number_format($cdwAmount, 2) }}</span>
                </div>

                <div class="summary-section-header"><i class="fa fa-calculator"></i> Grand Total</div>
                <div>
                    <p>
                        Rental 
                        <span class="float-end">RM {{ number_format($rentalTotal, 2) }}</span>
                    </p>
                    <p>
                        Discount 
                        <span class="float-end">
                            @if($coupon && in_array($coupon->value_in, ['A','P']))
                                -RM {{ number_format($discount, 2) }}
                            @else
                                -
                            @endif
                        </span>
                    </p>
                    <p>
                        SCDW Cost 
                        <span class="float-end">+RM {{ number_format($cdwAmount, 2) }}</span>
                    </p>
                    <p class="grand-total p-2 rounded mt-2">
                        Subtotal 
                        <span class="float-end">
                            RM {{ number_format(($rentalTotal + $cdwAmount) - $discount, 2) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Add-ons (Checklist Items) --}}
        @if($checklistItems && $checklistItems->count())
            <div class="summary-section-header mt-4"><i class="fa fa-plus-circle"></i> Add-ons</div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped summary-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Charge</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($checklistItems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td>
                                    @if($item->amount_type == 'RM')
                                        <span class="badge bg-info text-dark">RM{{ number_format($item->amount, 2) }}</span>
                                    @elseif($item->amount_type == 'P')
                                        <span class="badge bg-secondary">{{ $item->amount }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="add-ons-total">
                            <th colspan="2" class="text-end">Add-ons Total</th>
                            <th>RM{{ number_format($addonsTotal ?? 0, 2) }}</th>
                        </tr>
                        <tr class="grand-total">
                            <th colspan="2" class="text-end">Estimated Grand Total</th>
                            <th>RM{{ number_format($grand_total ?? 0, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <div class="text-center mt-4">
            <a href="{{ route('reservation.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>



<div class="card">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

     @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
    @endif
    <ul class="nav nav-tabs mb-4" id="reservationTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="walkin-tab" data-bs-toggle="tab" data-bs-target="#walkin" type="button" role="tab">Walk In</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="booking-tab" data-bs-toggle="tab" data-bs-target="#booking" type="button" role="tab">Booking</button>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="walkin" role="tabpanel">
            @php
                $license_exp = $customer->license_exp ?? null;
                $search_return_dates = $validated['search_return_date'] ?? null;
            @endphp

            {{-- License Expired: Show License Update Form --}}
            @if($license_exp && $search_return_dates && $license_exp < $search_return_dates)
                <div class="card border-danger mb-3">
                    <div class="card-header bg-danger text-white">
                        <i class="fa fa-exclamation-triangle"></i> License Expired
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('reservation.license_update', ['nric' => $customer->nric_no]) }}?tab=walkin" enctype="multipart/form-data">
                            @csrf

                            {{-- Hidden reservation context fields --}}
                            <input type="hidden" name="vehicle_id" value="{{ $validated['vehicle_id'] ?? '' }}">
                            <input type="hidden" name="pickup_location_id" value="{{ $validated['pickup_location_id'] ?? '' }}">
                            <input type="hidden" name="return_location_id" value="{{ $validated['return_location_id'] ?? '' }}">
                            <input type="hidden" name="search_pickup_date" value="{{ $validated['search_pickup_date'] ?? '' }}">
                            <input type="hidden" name="search_pickup_time" value="{{ $validated['search_pickup_time'] ?? '' }}">
                            <input type="hidden" name="search_return_date" value="{{ $validated['search_return_date'] ?? '' }}">
                            <input type="hidden" name="search_return_time" value="{{ $validated['search_return_time'] ?? '' }}">
                            <input type="hidden" name="cdw_id" value="{{ $validated['cdw_id'] ?? '' }}">
                            <input type="hidden" name="checklist_options" value="{{ $validated['checklist_options'] ?? '' }}">
                            <input type="hidden" name="coupon_id" value="{{ $validated['coupon_id'] ?? '' }}">
                            <input type="hidden" name="agent_code" value="{{ $validated['agent_code'] ?? '' }}">
                            <input type="hidden" name="nric" value="{{ $validated['nric'] ?? $customer->nric_no ?? '' }}">
                            <input type="hidden" name="coupon" value="{{ $validated['coupon'] ?? '' }}">
                            <input type="hidden" name="discount" value="{{ $validated['discount'] ?? '' }}">
                            <input type="hidden" name="est_total" value="{{ $validated['est_total'] ?? '' }}">
                            <input type="hidden" name="subtotal" value="{{ $validated['subtotal'] ?? '' }}">
                            <input type="hidden" name="agent_profit" value="{{ $validated['agent_profit'] ?? '' }}">
                            <input type="hidden" name="agent_id" value="{{ $validated['agent_id'] ?? '' }}">
                            <input type="hidden" name="cdw_amount" value="{{ $validated['cdw_amount'] ?? '' }}">
                            <input type="hidden" name="grand_total" value="{{ $validated['grand_total'] ?? '' }}">

                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="{{ $customer->firstname }}" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="{{ $customer->lastname }}" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NRIC No</label>
                                <input type="text" class="form-control" value="{{ $customer->nric_no }}" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New License Number</label>
                                <input type="text" name="license_no" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New License Expiry</label>
                                <input type="date" name="license_exp" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NRIC & License Photo (front)</label>
                                <input type="file" name="identity_photo_front" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NRIC & License Photo (back)</label>
                                <input type="file" name="identity_photo_back" class="form-control" required>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-danger">Update License</button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('reservation.store1') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Hidden reservation context fields --}}
                    <input type="hidden" name="vehicle_id" value="{{ $validated['vehicle_id'] ?? '' }}">
                    <input type="hidden" name="pickup_location_id" value="{{ $validated['pickup_location_id'] ?? '' }}">
                    <input type="hidden" name="return_location_id" value="{{ $validated['return_location_id'] ?? '' }}">
                    <input type="hidden" name="search_pickup_date" value="{{ $validated['search_pickup_date'] ?? '' }}">
                    <input type="hidden" name="search_pickup_time" value="{{ $validated['search_pickup_time'] ?? '' }}">
                    <input type="hidden" name="search_return_date" value="{{ $validated['search_return_date'] ?? '' }}">
                    <input type="hidden" name="search_return_time" value="{{ $validated['search_return_time'] ?? '' }}">
                    <input type="hidden" name="cdw_id" value="{{ $validated['cdw_id'] ?? '' }}">
                    <input type="hidden" name="checklist_options" value="{{ $validated['checklist_options'] ?? '' }}">
                    <input type="hidden" name="coupon_id" value="{{ $validated['coupon_id'] ?? '' }}">
                    <input type="hidden" name="agent_code" value="{{ $validated['agent_code'] ?? '' }}">
                    <input type="hidden" name="nric" value="{{ $validated['nric'] ?? $customer->nric_no ?? '' }}">
                    <input type="hidden" name="coupon" value="{{ $validated['coupon'] ?? '' }}">
                    <input type="hidden" name="discount" value="{{ $discount ?? $validated['discount'] ?? 0 }}">
                    <input type="hidden" name="est_total" value="{{ $validated['est_total'] ?? '' }}">
                    <input type="hidden" name="subtotal" value="{{ $rentalTotal ?? $validated['subtotal'] ?? 0 }}">
                    <input type="hidden" name="agent_profit" value="{{ $validated['agent_profit'] ?? '' }}">
                    <input type="hidden" name="agent_id" value="{{ $validated['agent_id'] ?? '' }}">
                    {{-- <input type="hidden" name="cdw_amount" value="{{ $cdwAmount ?? $cdw_amount ?? 0 }}">
                    <input type="hidden" name="addons_total" value="{{ $addonsTotal ?? 0 }}">
                    <input type="hidden" name="grand_total" value="{{ $grand_total ?? $validated['grand_total'] ?? 0 }}"> --}}
                    <input type="hidden" name="form_source" value="walkin"> <!-- for walk-in form -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                        <h4> <i class="fa fa-walking"></i> <b>Walk-In</b></h4>
                        </div>
                        <div class="card-body">

                            {{-- --- Customer Information --- --}}
                            <h5 class="mb-3">Customer Information</h5>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">NRIC Type</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="nric_type" required>
                                        <option value="">-- PLEASE SELECT --</option>
                                        <option value="ic_new" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_new' ? 'selected' : '' }}>New IC Number</option>
                                        <option value="ic_old" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_old' ? 'selected' : '' }}>Old IC Number</option>
                                        <option value="ic_army" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_army' ? 'selected' : '' }}>Army ID</option>
                                        <option value="ic_police" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_police' ? 'selected' : '' }}>Police ID</option>
                                        <option value="passport" {{ old('nric_type', $customer->nric_type ?? '') == 'passport' ? 'selected' : '' }}>Passport</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Title</label>
                                <div class="col-sm-6">
                                    <select name="title" class="form-control" id="title">
                                        <option value="">-- PLEASE SELECT --</option>
                                        <option value="Mr." {{ old('title', $customer->title ?? '') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                        <option value="Mrs." {{ old('title', $customer->title ?? '') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                        <option value="Miss." {{ old('title', $customer->title ?? '') == 'Miss.' ? 'selected' : '' }}>Miss.</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">First Name</label>
                                <div class="col-sm-6">
                                    <input type="text" name="firstname" class="form-control bg-light"
                                        value="{{ old('firstname', $customer->firstname ?? '') }}"
                                        required
                                        {{ (!empty($customer->firstname)) ? 'readonly' : '' }}>
                                    </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Last Name</label>
                                <div class="col-sm-6">
                                    <input type="text" name="lastname" class="form-control bg-light"
                                        value="{{ old('lastname', $customer->lastname ?? '') }}"
                                        required
                                        {{ (!empty($customer->lastname)) ? 'readonly' : '' }}>
                                    </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">NRIC No</label>
                                <div class="col-sm-6">
                                    <input type="text" name="nric_no" class="form-control bg-light"
                                        value="{{ old('nric_no', $customer->nric_no ?? '') }}" required readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Gender</label>
                                <div class="col-sm-6">
                                    <select name="gender" class="form-control" required>
                                        <option value="">-- PLEASE SELECT --</option>
                                        <option value="Male" {{ old('gender', $customer->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $customer->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Date of Birth</label>
                                <div class="col-sm-6">
                                    <input type="date" name="dob" class="form-control"
                                        value="{{ old('dob', $customer->dob ?? '') }}" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                    <label class="col-sm-3 col-form-label">Race</label>
                                    <div class="col-sm-6">
                                        <select name="race" class="form-control bg-light" required>
                                            <option value="">-- PLEASE SELECT --</option>
                                            <option value="malay" {{ old('race', $customer->race ?? '') == 'malay' ? 'selected' : '' }}>Malay</option>
                                            <option value="chinese" {{ old('race', $customer->race ?? '') == 'chinese' ? 'selected' : '' }}>Chinese</option>
                                            <option value="indian" {{ old('race', $customer->race ?? '') == 'indian' ? 'selected' : '' }}>Indian</option>
                                            <option value="others" {{ old('race', $customer->race ?? '') == 'others' ? 'selected' : '' }}>Others</option>
                                        </select>
                                    </div>
                                </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Phone No</label>
                                <div class="col-sm-6">
                                    <input type="text" name="phone_no" class="form-control"
                                        value="{{ old('phone_no', $customer->phone_no ?? '') }}" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Phone No 2 <small>(Optional)</small></label>
                                <div class="col-sm-6">
                                    <input type="text" name="phone_no2" class="form-control"
                                        value="{{ old('phone_no2', $customer->phone_no2 ?? '') }}">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-6">
                                    <input type="email" name="email" class="form-control"
                                        value="{{ old('email', $customer->email ?? '') }}" required>
                                </div>
                            </div>

                            {{-- --- License Info --- --}}
                            <h5 class="mt-4 mb-3">License Information</h5>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">License Number</label>
                                <div class="col-sm-6">
                                    <input type="text" name="license_no" class="form-control"
                                        value="{{ old('license_no', $customer->license_no ?? '') }}" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">License Expiry</label>
                                <div class="col-sm-6">
                                    <input type="date" name="license_exp" class="form-control"
                                        value="{{ old('license_exp', $customer->license_exp ?? '') }}" required>
                                </div>
                            </div>
                            {{-- 1. NRIC Photo (Front) --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">NRIC Photo (Front)</label>
                                <div class="col-sm-6">
                                    
                                    @php
                                        $image0 = \App\Models\UploadData::where('customer_id', $customer->id ?? null)
                                            ->where('position', 'customer')
                                            ->where('no', 0)
                                            ->latest('created')
                                            ->first();
                                    @endphp
                                    <input type="file" name="identity_photo_front" class="form-control" {{ $image0 ? '' : 'required' }}>
                                    @if($image0)
                                        <img src="{{ asset('assets/img/customer/' . $image0->file_name) }}" style="height:120px;" class="mt-2">
                                    @endif
                                </div>
                            </div>

                            {{-- 2. Selfie With NRIC --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Selfie With NRIC</label>
                                <div class="col-sm-6">
                                    
                                    @php
                                        $image1 = \App\Models\UploadData::where('customer_id', $customer->id ?? null)
                                            ->where('position', 'customer')
                                            ->where('no', 1)
                                            ->latest('created')
                                            ->first();
                                    @endphp
                                    <input type="file" name="selfie_nric" class="form-control" {{ $image1 ? '' : 'required' }}>
                                    @if($image1)
                                        <img src="{{ asset('assets/img/customer/' . $image1->file_name) }}" style="height:120px;" class="mt-2">
                                    @endif
                                </div>
                            </div>

                            {{-- 3. License Photo (Front) --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">License Photo (Front)</label>
                                <div class="col-sm-6">
                                    
                                    @php
                                        $image2 = \App\Models\UploadData::where('customer_id', $customer->id ?? null)
                                            ->where('position', 'customer')
                                            ->where('no', 2)
                                            ->latest('created')
                                            ->first();
                                    @endphp
                                    <input type="file" name="license_front" class="form-control" {{ $image2 ? '' : 'required' }}>
                                    @if($image2)
                                        <img src="{{ asset('assets/img/customer/' . $image2->file_name) }}" style="height:120px;" class="mt-2">
                                    @endif
                                </div>
                            </div>

                            {{-- 4. License Photo (Back) --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">License Photo (Back)</label>
                                <div class="col-sm-6">
                                    
                                    @php
                                        $image3 = \App\Models\UploadData::where('customer_id', $customer->id ?? null)
                                            ->where('position', 'customer')
                                            ->where('no', 3)
                                            ->latest('created')
                                            ->first();
                                    @endphp
                                    <input type="file" name="license_back" class="form-control" {{ $image3 ? '' : 'required' }}>
                                    @if($image3)
                                        <img src="{{ asset('assets/img/customer/' . $image3->file_name) }}" style="height:120px;" class="mt-2">
                                    @endif
                                </div>
                            </div>

                            {{-- 5. Utility Photo (Optional) --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Utility Photo (Optional)</label>
                                <div class="col-sm-6">
                                    <input type="file" name="utility_photo" class="form-control">
                                    @php
                                        $image4 = \App\Models\UploadData::where('customer_id', $customer->id ?? null)
                                            ->where('position', 'customer')
                                            ->where('no', 4)
                                            ->latest('created')
                                            ->first();
                                    @endphp
                                    @if($image4)
                                        <img src="{{ asset('assets/img/customer/' . $image4->file_name) }}" style="height:120px;" class="mt-2">
                                    @endif
                                </div>
                            </div>

                            {{-- 6. Working Photo (Optional) --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Working Photo (Optional)</label>
                                <div class="col-sm-6">
                                    <input type="file" name="working_photo" class="form-control">
                                    @php
                                        $image5 = \App\Models\UploadData::where('customer_id', $customer->id ?? null)
                                            ->where('position', 'customer')
                                            ->where('no', 5)
                                            ->latest('created')
                                            ->first();
                                    @endphp
                                    @if($image5)
                                        <img src="{{ asset('assets/img/customer/' . $image5->file_name) }}" style="height:120px;" class="mt-2">
                                    @endif
                                </div>
                            </div>


                            {{-- --- Address --- --}}
                            <h5 class="mt-4 mb-3">Address Information</h5>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Address</label>
                                <div class="col-sm-6">
                                    <input type="text" name="address" class="form-control"
                                        value="{{ old('address', $customer->address ?? '') }}" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Postcode</label>
                                <div class="col-sm-6">
                                    <input type="text" name="postcode" class="form-control"
                                        value="{{ old('postcode', $customer->postcode ?? '') }}" required>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">State</label>
                                <div class="col-sm-6">
                                    <select name="city" class="form-control" required>
                                        <option value="">-- PLEASE SELECT --</option>
                                        @foreach(['Perlis','Kedah','Pulau Pinang','Perak','Selangor','Wilayah Persekutuan Kuala Lumpur','Wilayah Persekutuan Putrajaya','Melaka','Negeri Sembilan','Johor','Pahang','Terengganu','Kelantan','Sabah','Sarawak'] as $state)
                                        <option value="{{ $state }}" {{ old('city', $customer->city ?? '') == $state ? 'selected' : '' }}>{{ strtoupper($state) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Country</label>
                                <div class="col-sm-6">
                                    <select name="country" class="form-control" required>
                                        <option value="MY" {{ old('country', $customer->country ?? 'MY') == 'MY' ? 'selected' : '' }}>Malaysia</option>
                                    </select>
                                </div>
                            </div>

                            {{-- --- Reference Information --- --}}
                            <h5 class="mt-4 mb-3">Reference Information</h5>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Reference Name</label>
                                <div class="col-sm-6">
                                    <input type="text" name="ref_name" class="form-control"
                                        value="{{ old('ref_name', $customer->ref_name ?? '') }}">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Reference Phone No</label>
                                <div class="col-sm-6">
                                    <input type="text" name="ref_phoneno" class="form-control"
                                        value="{{ old('ref_phoneno', $customer->ref_phoneno ?? '') }}">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Reference Relationship</label>
                                <div class="col-sm-6">
                                    <select name="ref_relationship" class="form-control">
                                        <option value="">-- PLEASE SELECT --</option>
                                        @foreach(['Husband','Wife','Mother','Father','Brother','Sister','Son','Daughter','Guardian','Company'] as $rel)
                                        <option value="{{ $rel }}" {{ old('ref_relationship', $customer->ref_relationship ?? '') == $rel ? 'selected' : '' }}>{{ strtoupper($rel) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Reference Address</label>
                                <div class="col-sm-6">
                                    <input type="text" name="ref_address" class="form-control"
                                        value="{{ old('ref_address', $customer->ref_address ?? '') }}">
                                </div>
                            </div>

                            {{-- --- Payment Info --- --}}
                            <h5 class="mt-4 mb-3">Payment Information</h5>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Rental Amount (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" value="{{ number_format($rentalTotal ?? 0,2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">CDW Amount (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" value="{{ number_format($cdwAmount ?? 0,2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Add-ons Total (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" value="{{ number_format($addonsTotal ?? 0,2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Estimated Grand Total (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" value="{{ number_format(($rentalTotal ?? 0) + ($cdwAmount ?? 0) + ($addonsTotal ?? 0),2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Discount (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" value="-{{ number_format($discount ?? 0,2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Discounted Amount (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" value="{{ number_format((($rentalTotal ?? 0) + ($cdwAmount ?? 0) + ($addonsTotal ?? 0)) - ($discount ?? 0),2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Booking Fee (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control bg-light" name="bookingfee" value="{{ number_format($booking ?? 0,2) }}" readonly>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Booking Fee Status</label>
                                <div class="col-sm-4">
                                    <select name="refund_dep_status" class="form-control" required>
                                        <option value="">-- Please Select --</option>
                                        <option value="Collect" {{ old('refund_dep_status') == 'Collect' ? 'selected' : '' }}>Collect</option>
                                        <option value="Cash" {{ old('refund_dep_status') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="Online" {{ old('refund_dep_status') == 'Online' ? 'selected' : '' }}>Online</option>
                                        <option value="Card" {{ old('refund_dep_status') == 'Card' ? 'selected' : '' }}>Card</option>
                                        <option value="QRPay" {{ old('refund_dep_status') == 'QRPay' ? 'selected' : '' }}>QRPay</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Payment Made (MYR)</label>
                                <div class="col-sm-4">
                                    <input type="text" name="payment_amount" class="form-control" value="{{ old('payment_amount', '') }}">
                                    <small class="text-danger">*Please include only PAYMENT that has been made, not the grand total rental. Insert "0" if no payment made.</small>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Payment Receipt (Image)</label>
                                <div class="col-sm-4">
                                    <input type="file" name="payment_receipt" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Rental Balance (MYR)</label>
                                <div class="col-sm-4">
                                    <select name="payment_type" class="form-control" required>
                                        <option value="">-- Please Select --</option>
                                        <option value="Collect" {{ old('payment_type') == 'Collect' ? 'selected' : '' }}>Collect</option>
                                        <option value="Cash" {{ old('payment_type') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="Online" {{ old('payment_type') == 'Online' ? 'selected' : '' }}>Online</option>
                                        <option value="Card" {{ old('payment_type') == 'Card' ? 'selected' : '' }}>Card</option>
                                        <option value="QRPay" {{ old('payment_type') == 'QRPay' ? 'selected' : '' }}>QRPay</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Payment Status</label>
                                <div class="col-sm-4">
                                    <select name="payment_status" class="form-control" required>
                                        <option value="">-- Please Select --</option>
                                        <option value="Unpaid" {{ old('payment_status') == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="Booking" {{ old('payment_status') == 'Booking' ? 'selected' : '' }}>Booking only</option>
                                        <option value="BookingRental" {{ old('payment_status') == 'BookingRental' ? 'selected' : '' }}>Booking + Rental (Incomplete Payment)</option>
                                        <option value="FullRental" {{ old('payment_status') == 'FullRental' ? 'selected' : '' }}>Booking + Rental (Full Payment)</option>
                                    </select>
                                </div>
                            </div>

                            {{-- --- Survey --- --}}
                            <h5 class="mt-4 mb-3">Survey</h5>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label">Survey</label>
                                <div class="col-sm-4">
                                    <select name="survey_type" class="form-control">
                                        <option value="">-- PLEASE SELECT --</option>
                                        @php
                                            $selectedSurveyType = old('survey_type', $customer->survey_type ?? '');
                                        @endphp
                                        @foreach(['Banner','Bunting','Facebook Ads','Friends','Google Ads','Magazine','Others'] as $survey)
                                            <option value="{{ $survey }}" {{ $selectedSurveyType == $survey ? 'selected' : '' }}>{{ strtoupper($survey) }}</option>
                                        @endforeach

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-success">Submit</button>
                        </div>
                    </div>
                </form>



            @endif
        </div>

        
        <div class="tab-pane fade" id="booking" role="tabpanel">
            <form method="POST" action="{{ route('reservation.store1') }}" enctype="multipart/form-data">
                @csrf

                {{-- Hidden reservation context fields --}}
                <input type="hidden" name="vehicle_id" value="{{ $validated['vehicle_id'] ?? '' }}">
                <input type="hidden" name="pickup_location_id" value="{{ $validated['pickup_location_id'] ?? '' }}">
                <input type="hidden" name="return_location_id" value="{{ $validated['return_location_id'] ?? '' }}">
                <input type="hidden" name="search_pickup_date" value="{{ $validated['search_pickup_date'] ?? '' }}">
                <input type="hidden" name="search_pickup_time" value="{{ $validated['search_pickup_time'] ?? '' }}">
                <input type="hidden" name="search_return_date" value="{{ $validated['search_return_date'] ?? '' }}">
                <input type="hidden" name="search_return_time" value="{{ $validated['search_return_time'] ?? '' }}">
                <input type="hidden" name="cdw_id" value="{{ $validated['cdw_id'] ?? '' }}">
                <input type="hidden" name="checklist_options" value="{{ $validated['checklist_options'] ?? '' }}">
                <input type="hidden" name="coupon_id" value="{{ $validated['coupon_id'] ?? '' }}">
                <input type="hidden" name="agent_code" value="{{ $validated['agent_code'] ?? '' }}">
                <input type="hidden" name="nric" value="{{ $validated['nric'] ?? $customer->nric_no ?? '' }}">
                <input type="hidden" name="coupon" value="{{ $validated['coupon'] ?? '' }}">
                <input type="hidden" name="discount" value="{{ $discount ?? $validated['discount'] ?? 0 }}">
                <input type="hidden" name="est_total" value="{{ $validated['est_total'] ?? '' }}">
                <input type="hidden" name="subtotal" value="{{ $rentalTotal ?? $validated['subtotal'] ?? 0 }}">
                <input type="hidden" name="agent_profit" value="{{ $validated['agent_profit'] ?? '' }}">
                <input type="hidden" name="agent_id" value="{{ $validated['agent_id'] ?? '' }}">
                <input type="hidden" name="form_source" value="booking"> <!-- for booking form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fa fa-calendar-alt"></i><b> Booking</b></h4>
                    </div>
                    <div class="card-body">
                        {{-- --- Customer Information --- --}}
                        <h5 class="mb-3">Customer Information</h5>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">NRIC Type</label>
                            <div class="col-sm-6">
                                <select class="form-control bg-light" name="nric_type" required>
                                    <option value="">-- PLEASE SELECT --</option>
                                    <option value="ic_new" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_new' ? 'selected' : '' }}>New IC Number</option>
                                    <option value="ic_old" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_old' ? 'selected' : '' }}>Old IC Number</option>
                                    <option value="ic_army" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_army' ? 'selected' : '' }}>Army ID</option>
                                    <option value="ic_police" {{ old('nric_type', $customer->nric_type ?? '') == 'ic_police' ? 'selected' : '' }}>Police ID</option>
                                    <option value="passport" {{ old('nric_type', $customer->nric_type ?? '') == 'passport' ? 'selected' : '' }}>Passport</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Title</label>
                            <div class="col-sm-6">
                                <select name="title" class="form-control bg-light" id="title">
                                    <option value="">-- PLEASE SELECT --</option>
                                    <option value="Mr." {{ old('title', $customer->title ?? '') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                    <option value="Mrs." {{ old('title', $customer->title ?? '') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                    <option value="Miss." {{ old('title', $customer->title ?? '') == 'Miss.' ? 'selected' : '' }}>Miss.</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">First Name</label>
                            <div class="col-sm-6">
                                <input type="text" name="firstname" class="form-control bg-light"
                                    value="{{ old('firstname', $customer->firstname ?? '') }}"
                                    required
                                    {{ (!empty($customer->firstname)) ? 'readonly' : '' }}>
                                </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Last Name</label>
                            <div class="col-sm-6">
                                <input type="text" name="lastname" class="form-control bg-light"
                                    value="{{ old('lastname', $customer->lastname ?? '') }}"
                                    required
                                    {{ (!empty($customer->lastname)) ? 'readonly' : '' }}>
                                </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">NRIC No</label>
                            <div class="col-sm-6">
                                <input type="text" name="nric_no" class="form-control bg-light"
                                    value="{{ old('nric_no', $customer->nric_no ?? '') }}" required readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Gender</label>
                            <div class="col-sm-6">
                                <select name="gender" class="form-control bg-light" required>
                                    <option value="">-- PLEASE SELECT --</option>
                                    <option value="Male" {{ old('gender', $customer->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $customer->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Race</label>
                            <div class="col-sm-6">
                                <select name="race" class="form-control bg-light" required>
                                    <option value="">-- PLEASE SELECT --</option>
                                    <option value="malay" {{ old('race', $customer->race ?? '') == 'malay' ? 'selected' : '' }}>Malay</option>
                                    <option value="chinese" {{ old('race', $customer->race ?? '') == 'chinese' ? 'selected' : '' }}>Chinese</option>
                                    <option value="indian" {{ old('race', $customer->race ?? '') == 'indian' ? 'selected' : '' }}>Indian</option>
                                    <option value="others" {{ old('race', $customer->race ?? '') == 'others' ? 'selected' : '' }}>Others</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Phone No</label>
                            <div class="col-sm-6">
                                <input type="text" name="phone_no" class="form-control bg-light"
                                    value="{{ old('phone_no', $customer->phone_no ?? '') }}" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Phone No 2 <small>(Optional)</small></label>
                            <div class="col-sm-6">
                                <input type="text" name="phone_no2" class="form-control bg-light"
                                    value="{{ old('phone_no2', $customer->phone_no2 ?? '') }}">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-6">
                                <input type="email" name="email" class="form-control bg-light"
                                    value="{{ old('email', $customer->email ?? '') }}" required>
                            </div>
                        </div>

                        

                        {{-- --- Payment Info --- --}}
                        <h5 class="mt-4 mb-3">Payment Information</h5>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Initial Amount (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control bg-light" value="{{ number_format($subtotal ?? 0,2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Discount (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control bg-light" value="- {{ number_format($discount ?? 0,2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Discounted Amount (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control bg-light" value="{{ number_format(($subtotal ?? 0) - ($discount ?? 0),2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">CDW Amount (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control bg-light" value="+ {{ number_format($cdwAmount ?? $cdw_amount ?? 0,2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Grand Total (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control bg-light" value="{{ number_format($grand_total ?? 0,2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Booking Fee (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" class="form-control bg-light" name="bookingfee" value="{{ number_format($booking ?? 0,2) }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Booking Fee Status</label>
                            <div class="col-sm-4">
                                <select name="refund_dep_status" class="form-control bg-light" required>
                                    <option value="">-- Please Select --</option>
                                    <option value="Collect" {{ old('refund_dep_status') == 'Collect' ? 'selected' : '' }}>Collect</option>
                                    <option value="Cash" {{ old('refund_dep_status') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Online" {{ old('refund_dep_status') == 'Online' ? 'selected' : '' }}>Online</option>
                                    <option value="Card" {{ old('refund_dep_status') == 'Card' ? 'selected' : '' }}>Card</option>
                                    <option value="QRPay" {{ old('refund_dep_status') == 'QRPay' ? 'selected' : '' }}>QRPay</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Payment Made (MYR)</label>
                            <div class="col-sm-4">
                                <input type="text" name="payment_amount" class="form-control bg-light" value="{{ old('payment_amount', '') }}">
                                <small class="text-danger">*Please include only PAYMENT that has been made, not the grand total rental. Insert "0" if no payment made.</small>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Payment Receipt (Image)</label>
                            <div class="col-sm-4">
                                <input type="file" name="payment_receipt" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Rental Balance (MYR)</label>
                            <div class="col-sm-4">
                                <select name="payment_type" class="form-control bg-light" required>
                                    <option value="">-- Please Select --</option>
                                    <option value="Collect" {{ old('payment_type') == 'Collect' ? 'selected' : '' }}>Collect</option>
                                    <option value="Cash" {{ old('payment_type') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Online" {{ old('payment_type') == 'Online' ? 'selected' : '' }}>Online</option>
                                    <option value="Card" {{ old('payment_type') == 'Card' ? 'selected' : '' }}>Card</option>
                                    <option value="QRPay" {{ old('payment_type') == 'QRPay' ? 'selected' : '' }}>QRPay</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Payment Status</label>
                            <div class="col-sm-4">
                                <select name="payment_status" class="form-control bg-light" required>
                                    <option value="">-- Please Select --</option>
                                    <option value="Unpaid" {{ old('payment_status') == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="Booking" {{ old('payment_status') == 'Booking' ? 'selected' : '' }}>Booking only</option>
                                    <option value="BookingRental" {{ old('payment_status') == 'BookingRental' ? 'selected' : '' }}>Booking + Rental (Incomplete Payment)</option>
                                    <option value="FullRental" {{ old('payment_status') == 'FullRental' ? 'selected' : '' }}>Booking + Rental (Full Payment)</option>
                                </select>
                            </div>
                        </div>

                        {{-- --- Survey --- --}}
                        <h5 class="mt-4 mb-3">Survey</h5>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label">Survey</label>
                            <div class="col-sm-4">
                                <select name="survey_type" class="form-control bg-light">
                                    <option value="">-- PLEASE SELECT --</option>
                                    @php
                                        $selectedSurveyType = old('survey_type', $customer->survey_type ?? '');
                                    @endphp
                                    @foreach(['Banner','Bunting','Facebook Ads','Friends','Google Ads','Magazine','Others'] as $survey)
                                        <option value="{{ $survey }}" {{ $selectedSurveyType == $survey ? 'selected' : '' }}>{{ strtoupper($survey) }}</option>
                                    @endforeach

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Submit Booking</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

{{-- TAB AUTO-SWITCH LOGIC --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('tab') === 'walkin') {
        let walkinTab = document.getElementById('walkin-tab');
        let walkinPane = document.getElementById('walkin');
        if (walkinTab && walkinPane) {
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('show', 'active'));
            walkinTab.classList.add('active');
            walkinPane.classList.add('show', 'active');
        }
    }
});
</script>
@endpush

@endsection