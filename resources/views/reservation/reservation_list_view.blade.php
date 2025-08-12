@extends('layouts.main')

@section('page-title')
    {{ __('Reservation List View') }}
@endsection

@section('page-breadcrumb')
    {{ __('Reservation List View') }}
@endsection

@section('content')


<div class="btn-group mb-3 mt-3">

    {{-- Agreement Button --}}
    <a href="{{ route('agreement.generate', $booking->id) }}" target="_blank">
        <button class="btn btn-secondary" type="button">
            <i class="fa fa-clipboard">&nbsp;</i>Agreement
        </button>
    </a>

    @php
        $currentDate = \Carbon\Carbon::now()->format('Y-m-d');
    @endphp

    @if ($booking->available === 'Booked')
        {{-- Booking Receipt --}}
        <a href="{{ route('booking_receipt.generate', $booking->id) }}" target="_blank">
            <button class="btn btn-secondary" type="button">
                <i class="fa fa-file">&nbsp;</i>Booking Receipt
            </button>
        </a>

        {{-- Pickup Button --}}
        <button 
            class="btn btn-secondary"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#pickupModal"
            @if (empty($license_no) || ($license_exp ?? '') < $currentDate || $customer_status !== 'A') disabled @endif>
            <i class="fa fa-external-link">&nbsp;</i>Pickup
        </button>


        {{-- Pre-inspection Button --}}
        <button 
            class="btn btn-secondary"
            type="button"
            data-bs-toggle="modal"
            data-bs-target=".bs-inspect-modal-lg"
            @if (empty($license_no) || ($license_exp ?? '') < $currentDate || $customer_status !== 'A') disabled @endif>
            <i class="fa fa-external-link">&nbsp;</i>Pre-inspection
        </button>

        {{-- Tooltip for restriction --}}
        @if (empty($license_no) || ($license_exp ?? '') < $currentDate || $customer_status !== 'A')
            <abbr title="Customer's details are incomplete or not approved">
                <i class="fa fa-info-circle text-danger"></i>
            </abbr>
        @endif
    @endif

    @if (in_array($booking->available, ['Out', 'Extend']))
        {{-- @if ($excess === 'true' || $excess === 'extend')
            Return with extension modal 
            <button 
                class="btn btn-secondary"
                type="button"
                data-bs-toggle="modal"
                data-bs-target=".bs-return-extend-modal-lg">
                <i class="fa fa-external-link">&nbsp;</i>Return
            </button>
        @else--}}
            {{-- Normal Return 
            <button 
                class="btn btn-secondary"
                type="button"
                data-bs-toggle="modal"
                data-bs-target=".bs-return-modal-lg">
                <i class="fa fa-level-up">&nbsp;</i>Return
            </button>
        @endif--}}

        {{-- Extend Button --}}
        <button 
            class="btn btn-secondary"
            type="button"
            data-bs-toggle="modal"
            data-bs-target=".bs-extend-modal-lg">
            <i class="fa fa-external-link">&nbsp;</i>Extend
        </button>
    @endif

    @if ($booking->available === 'Park')
        {{-- Return Receipt --}}
        <a href="{{ url('returnreceipt?booking_id=' . $booking->id) }}">
            <button class="btn btn-secondary" type="button">
                <i class="fa fa-file">&nbsp;</i>Return Receipt
            </button>
        </a>
    @endif

</div>

{{-- License Warning --}}
@if (empty($license_no) || (($license_exp) < $currentDate && $booking->available !== 'Park'))
    <div class="text-danger mt-2">Please complete License Details by Editing Customer details to continue.</div>
@endif

{{-- Duplicate NRIC Warnings --}}
@if (request()->has('nric_new'))
    <div class="text-danger mt-2">NRIC No. entered has been registered in the system.</div>
@endif

@if (request()->has('nric_blacklisted'))
    <div class="text-danger mt-2">NRIC No. entered has been blacklisted</div>
@endif


<div class="card mb-4 shadow-sm">

    <div class="card-header">
        <h5 class="mb-0">Reservation View - {{ $booking->agreement_no }}</h5>
    </div>
    <div class="card-body pb-2">
        <div class="row">
            <div class="col-md-4 text-center">
                <img width="240px" src="{{ asset('assets/img/company/' . $company['image']) }}?nocache={{ time() }}" alt="Company Logo">
            </div>
            <div class="col-md-4">
                <h4>{{ $company['name'] }}</h4>
                <p>{{ $company['website'] }}</p>
                <p>{{ $company['address'] }}</p>
                <p>{{ $company['phone'] }}</p>
                <p>{{ $company['reg_no'] }}</p>
                <div class="mb-4">
                    <label class="form-label fw-bold">Reference No.</label>
                    <div class="row g-2">
                        <div class="col-12">
                            <input class="form-control" type="text" name="refno" value="{{ $booking->agreement_no }}" disabled>
                        </div>
                        <div class="col-6">
                            <button
                                class="btn btn-outline-primary w-100"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#createPostInspectionModal"
                                onclick="generateLink('postInspection')"
                                title="Create Post-Inspection Link"
                            >
                                <i class="fa fa-clipboard-check"></i>
                                <span class="d-none d-md-inline ms-1">Post-Inspection</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button
                                class="btn btn-outline-success w-100"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#createRegisterModal"
                                onclick="generateLink('register')"
                                title="Create Register Link"
                            >
                                <i class="fa fa-user-plus"></i>
                                <span class="d-none d-md-inline ms-1">Register</span>
                            </button>
                        </div>
                    </div>
                </div>


                @if (!empty($actionLabel))
                <div class="mt-3 mb-3">
                    <span class="text-success fw-bold">{{ $actionLabel }}</span>
                </div>
                @endif

                @if (!empty($dueWarnings))
                    <h5>
                        <span style="color:darkorange">
                            @foreach($dueWarnings as $msg)
                                {!! $msg !!}<br>
                            @endforeach
                        </span>
                    </h5>
                @endif

            </div>
            
        </div>

        {{-- Modal for Post-Inspection Link --}}
        <div class="modal fade" id="createPostInspectionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Create Post-Inspection Link</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea id="postInspectionLink" class="form-control" style="height: 200px;"></textarea>
                        <br>
                        <button class="btn btn-primary" onclick="copyLink('postInspectionLink')">Copy this!</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal for Register Link --}}
        <div class="modal fade" id="createRegisterModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Create Register Link</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea id="registerLink" class="form-control" style="height: 200px;"></textarea>
                        <br>
                        <button class="btn btn-primary" onclick="copyLink('registerLink')">Copy this!</button>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('reservation.reservation_list') }}" class="btn btn-secondary btn-sm w-100 mt-4">
            Back
        </a>
    </div>
</div>


{{-- Customer Part --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Customer Details</h5>
    </div>
    <div class="card-body pb-2">

        {{-- Status & Approve/Reject --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-3 fw-bold">Status:</div>
            <div class="col-md-3">
                @if ($customer_status == 'A')
                    <input class="form-control text-success" value="Active" disabled>
                @elseif ($customer_status == 'P')
                    <input class="form-control text-warning" value="Pending Response" disabled>
                @else
                    <input class="form-control" value="{{ $customer_status }}" disabled>
                @endif
            </div>
            <div class="col-md-6">
                @if ($customer_status == 'P')
                    <button class="btn btn-success me-2 mt-2" id="approveBtn">Approve</button>
                    <button class="btn btn-danger mt-2" type="button" id="rejectBtn">Reject</button>
                @else
                    {{-- No buttons if status is not Pending --}}
                @endif
            </div>
        </div>

        {{-- Remark for Reject (toggle via JS) --}}
        @if ($customer_status == 'P')
        <div class="row mb-2" id="remarkForm" style="display:none;">
            <div class="col-md-3 fw-bold">Remark for Reject:</div>
            <div class="col-md-6">
                <textarea class="form-control mb-2" name="remarkReject"></textarea>
                <button class="btn btn-success" id="submitRejectBtn">Submit</button>
            </div>
        </div>
        @endif


        {{-- Name, Blacklist button, Call & WhatsApp --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-3 fw-bold">Full Name:</div>
            <div class="col-md-6">
                <input class="form-control" value="{{ $fullname }}" disabled>
                @if ($customer_status == 'B')
                    <span class="text-danger fw-bold">Blacklisted</span>
                @endif
            </div>
            <div class="col-md-3 d-flex gap-2">
                @if ($customer_status == 'A' && $customer_status == 'A')
                    {{-- Only show Blacklist when both statuses are Active --}}
                    <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#blacklistModal">
                        <i class="fa fa-ban"></i> Blacklist
                    </button>
                @elseif ($customer_status == 'B' && $customer_status == 'A')
                    {{-- Only show Reason for Blacklist if customer is blacklisted and customer_status is still Active --}}
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#reasonBlacklistModal">
                        Reason for Blacklist
                    </button>
                @endif
            </div>
        </div>


        {{-- NRIC --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">NRIC:</div>
            <div class="col-md-6">
                <input class="form-control" value="{{ $nric_no }}" disabled>
            </div>
        </div>

        {{-- Phone No, Call & WhatsApp --}}
        <div class="row mb-4 align-items-center">
            <div class="col-md-3 fw-bold">Phone No:</div>
            <div class="col-md-4">
                <input class="form-control" value="{{ $phone_no }}" disabled>
            </div>
            <div class="col-md-2">
                <a href="tel:{{ $phone_no }}" target="_blank" class="btn btn-primary w-100 mt-2"><i class="fa fa-phone"></i></a>
            </div>
            <div class="col-md-2">
                <a href="https://wa.me/{{ $phone_no }}?text=SALAM%0a" target="_blank" class="btn btn-success w-100 mt-2"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>

        {{-- Phone No 2 (if available) --}}
        @if(!empty($phone_no2) && $phone_no2 !== '-')
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Phone No 2:</div>
            <div class="col-md-6">
                <input class="form-control" value="{{ $phone_no2 }}" disabled>
            </div>
        </div>
        @endif

        {{-- License --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Driving License:</div>
            <div class="col-md-6">
                <input class="form-control" value="{{ $license_no }}" disabled>
            </div>
        </div>

        {{-- Address --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Address:</div>
            <div class="col-md-6">
                <textarea class="form-control" disabled>{{ $address }}</textarea>
            </div>
        </div>

        {{-- Email --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Email:</div>
            <div class="col-md-6">
                <input class="form-control" value="{{ $email }}" disabled>
            </div>
        </div>

        {{-- Reference --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Reference:</div>
            <div class="col-md-6">
                <input class="form-control mb-1" value="Name: {{ $ref_name }}" disabled>
                <input class="form-control mb-1" value="Phone No: {{ $ref_phoneno }}" disabled>
                <input class="form-control mb-1" value="Relationship: {{ $ref_relationship }}" disabled>
                
            </div>
        </div>

        @if(!empty($allUploads))
            <div class="row mt-4 mb-4">
                @foreach($allUploads as $upload)
                    <div class="col-md-4 mb-3">
                        <label class="fw-bold">{{ $upload['label'] }}</label>
                        <div class="border p-2 rounded bg-light text-center" style="min-height:170px;">
                            @if(!empty($upload['file']))
                                <img src="{{ asset('assets/img/customer/' . $upload['file']) }}?nocache={{ time() }}"
                                    alt="{{ $upload['label'] }}"
                                    style="width:100%;max-width:220px;border:3px solid #ddd;cursor:pointer"
                                    onclick="showModal(this)">
                            @else
                                <div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                                    <i class="fas fa-image fa-2x mb-2 text-muted"></i>
                                    <span class="text-muted">No image Uploaded</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
{{-- End Customer Part --}}


{{--Payment Information --}}

{{-- <form method="POST" action="{{ route('reservation.update', $booking->id) }}">
    @csrf
    @method('POST') or 'PUT' depending on your route --}}

<div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Payment Information</h5>
        </div>
        <div class="card-body">

            {{-- Pickup & Return Schedule --}}
            

            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Pickup Date</div>
                    <div class="col-md-6">
                        <input type="date" class="form-control" name="pickup_date" id="sale_pickup_date"
                            value="{{ \Carbon\Carbon::parse($booking->pickup_date)->format('Y-m-d') }}" disabled>
                    </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Pickup Time</div>
                    <div class="col-md-6">
                        <input type="time" class="form-control" name="pickup_time" id="sale_pickup_time"
                            value="{{ \Carbon\Carbon::parse($booking->pickup_date)->format('H:i') }}" disabled>
                    </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Return Date</div>
                    <div class="col-md-6">
                        <input type="date" class="form-control" name="return_date" id="sale_return_date"
                            value="{{ \Carbon\Carbon::parse($booking->return_date)->format('Y-m-d') }}" disabled>
                    </div>
            </div>
                
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">Return Time</div>
                    <div class="col-md-6">
                        <input type="time" class="form-control" name="return_time" id="sale_return_time"
                            value="{{ \Carbon\Carbon::parse($booking->return_date)->format('H:i') }}" disabled>
                    </div>
            </div>

            <hr class="my-4">

            {{-- Payment & Sale Details --}}

            {{-- Receipts --}}
            <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Booking Receipt</div>
                    <div class="col-md-6">
                        <div class="border p-2 rounded bg-light text-center mb-4" style="min-height:170px;">
                            @if(!empty($booking_receipt))
                                <div>{{ $booking_receipt['created_at']->format('d-m-Y H:i:s a') ?? '' }}</div>
                                
                                <img src="{{ asset('assets/img/receipt/' . $booking_receipt['file']) }}?nocache={{ time() }}"
                                    alt="Booking Receipt"
                                    style="width:100%;max-width:220px;border:3px solid #ddd;cursor:pointer"
                                    onclick="showModal(this)">

                            @else
                                <div class="d-flex flex-column align-items-center justify-content-center" style="height:120px;">
                                                <i class="fas fa-image fa-2x mb-2 text-muted"></i>
                                                <span class="text-muted">No image Uploaded</span>
                                </div>
                            @endif
                        </div>           
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Booking Fee Status (RM)</div>
                            <div class="col-md-3">
                                <input type="number" name="refund_dep" id="booking_fee" class="form-control"
                                    value="{{ $booking->refund_dep ?? '0.00' }}" disabled>
                            </div>
                        <div class="col-md-3">
                            <select class="form-control" name="refund_dep_payment" id="booking_fee_payment" disabled>
                                <option {{ ($booking->refund_dep_payment ?? '') == 'Collect' ? 'selected' : '' }}>Collect</option>
                                <option {{ ($booking->refund_dep_payment ?? '') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option {{ ($booking->refund_dep_payment ?? '') == 'Online' ? 'selected' : '' }}>Online</option>
                                <option {{ ($booking->refund_dep_payment ?? '') == 'Card' ? 'selected' : '' }}>Card</option>
                            </select>
                        </div>
                    </div>
                    {{-- ... Add other sale fields here, all with disabled ... --}}
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">CDW Amount (RM)</div>
                            <div class="col-md-6">
                                <input type="number" name="cdw_amount" class="form-control" id="cdw_amount"
                                   value="{{ number_format($cdwAmount, 2) }}" disabled>
                            </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold" hidden>CDW ID</div>
                            <div class="col-md-6">
                                <input type="hidden" name="cdw_id" class="form-control" id="cdw_id"
                                    value="{{ number_format($cdwId),2 }}" disabled>
                            </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Discount (RM)</div>
                            <div class="col-md-6">
                                <input type="number" name="discount_amount" class="form-control" id="discount_amount"
                                    value="{{ $booking->discount_amount ?? '0.00' }}" disabled>
                            </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Sale (RM)</div>
                            <div class="col-md-6">
                                <input type="number" name="sale_total" class="form-control" id="sale_total"
                                    value="{{ $booking->est_total ?? '0.00' }}" disabled>
                            </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">Payment Made (RM)</div>
                            <div class="col-md-6">
                                <input type="number" name="payment_made" class="form-control" id="payment_made"
                                    value="{{ $booking->balance ?? '0.00' }}" disabled>
                            </div>
                    </div>
            </div>
        </div>
</div>
{{-- </form> --}}

{{-- End Payment Information --}}

{{-- Vehicle Information --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Vehicle Information</h5>
    </div>
    <div class="card-body pb-2">

        {{-- Edit Reservation Button --}}
        @if(
            in_array($occupation, ['Admin', 'Manager', 'Sales Executive', 'company']) ||
            ($booking->available === 'Booked')
        )
            <div class="row mb-4">
                <div class="col-md-12 text-center">
                    <a href="{{ route('reservation.form', ['booking_id' => $booking->id]) }}" class="text-decoration-none">
                        <button type="button" class="btn btn-outline-primary px-4 py-2 shadow-sm" style="min-width:220px;">
                            <i class="fas fa-edit me-2"></i> Edit Reservation
                        </button>
                    </a>
                </div>
            </div>
        @endif


        {{-- Current Vehicle --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Current Vehicle:</div>
            <div class="col-md-6">
                <input class="form-control" 
                       value="{{ $currentVehicle->reg_no }} - {{ $currentVehicle->make }} {{ $currentVehicle->model }}" 
                       disabled>
            </div>
        </div>

        {{-- Coupon --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Coupon:</div>
            <div class="col-md-6">
                <input class="form-control" 
                       value="{{ $booking->discount_coupon ?? 'N/A' }}" 
                       disabled>
            </div>
        </div>

        {{-- Pickup Location --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Pickup Location:</div>
            <div class="col-md-6">
                <input class="form-control" 
                       value="{{ optional($booking->pickupLocation)->description ?? 'N/A' }}" 
                       disabled>
            </div>
        </div>

        {{-- Return Location --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Return Location:</div>
            <div class="col-md-6">
                <input class="form-control" 
                       value="{{ optional($booking->returnLocation)->description ?? 'N/A' }}" 
                       disabled>
            </div>
        </div>

        {{-- Available Vehicles Dropdown --}}
        <div class="row mb-4">
            <div class="col-md-3 fw-bold">Available Vehicles:</div>
            <div class="col-md-6">
                <select class="form-control" readonly>
                    <option disabled selected>Available options:</option>
                    @foreach($availableVehicles as $vehicle)
                        <option>
                            {{ $vehicle->reg_no }} - {{ $vehicle->make }} {{ $vehicle->model }}
                        </option>
                    @endforeach
                </select>

            </div>
        </div>

        {{-- Notice --}}
        <div class="row">
            <div class="col-md-12 text-center">
                <small class="text-muted">
                    To change vehicle or coupon, please click "Edit Reservation".
                </small>
            </div>
        </div>
    </div>
</div>
{{-- End Vehicle Information --}}

{{-- Vehicle Checklist Section --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Vehicle Checklist</h5>
    </div>
    <div class="card-body pb-2">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle text-center text-nowrap">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="text-start fw-semibold">Checklist</th>
                        <th class="fw-semibold">Pickup</th>
                        <th class="fw-semibold">Return</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rows = [
                            ['Start Engine', 'car_out_start_engine', 'car_in_start_engine'],
                            ['Engine Condition', 'car_out_engine_condition', 'car_in_engine_condition'],
                            ['Test Gear', 'car_out_test_gear', 'car_in_test_gear'],
                            ['No Alarm', 'car_out_no_alarm', 'car_in_no_alarm'],
                            ['Wiper', 'car_out_wiper', 'car_in_wiper'],
                            ['Air Conditioner', 'car_out_air_conditioner', 'car_in_air_conditioner'],
                            ['Radio', 'car_out_radio', 'car_in_radio'],
                            ['Power Window', 'car_out_power_window', 'car_in_power_window'],
                            ['Perfumed', 'car_out_perfume', 'car_in_perfume'],
                            ['Carpet', 'car_out_carpet', 'car_in_carpet'],
                            ['Lamp', 'car_out_lamp', 'car_in_lamp'],
                            ['Tyres Condition', 'car_out_tyres_condition', 'car_in_tyres_condition'],
                            ['Jack', 'car_out_jack', 'car_in_jack'],
                            ['Tools', 'car_out_tools', 'car_in_tools'],
                            ['Signage', 'car_out_signage', 'car_in_signage'],
                            ['Tyre Spare', 'car_out_tyre_spare', 'car_in_tyre_spare'],
                            ['Sticker P', 'car_out_sticker_p', 'car_in_sticker_p'],
                            ['Child Seat', 'car_out_child_seat', 'car_in_child_seat'],
                            ['Mileage', 'car_out_mileage', 'car_in_mileage']
                        ];
                    @endphp

                    @foreach ($rows as [$label, $outKey, $inKey])
                        <tr>
                            <td class="text-start">{{ $label }}</td>
                            <td>{{ $checklist->$outKey ?? '-' }}</td>
                            <td>{{ $checklist->$inKey ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Extra Info Section --}}
        <div class="mt-4">
            <table class="table table-bordered table-sm table-striped-columns text-center w-100">
                <tbody>
                    <tr class="align-middle">
                        <th class="bg-light fw-semibold" style="width: 25%;">Additional Driver</th>
                        <td style="width: 25%;">{{ $checklist->car_add_driver ?? '-' }}</td>
                        <th class="bg-light fw-semibold" style="width: 25%;">Driver</th>
                        <td style="width: 25%;">{{ $checklist->car_driver ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
{{-- End Vehicle Checklist Section --}}

{{-- Car Interior Image State Section ----}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Car Interior Image State</h5>
    </div>
    <div class="card-body pb-2">
        <div class="table-responsive">
            <h6 class="mb-2 text-primary">Pickup</h6>
            <div class="row mb-3">
                @php
                    $interiorLabels = [
                        '1' => 'Dashboard & Windscreen',
                        '2' => 'First Row Seat',
                        '3' => 'Second Row Seat',
                        '4' => 'Third Row Seat (Optional)',
                        '5' => 'Fourth Row Seat (Optional)',
                    ];
                @endphp

                @forelse($pickupInteriorImages as $image)
                @php
                    $label = $interiorLabels[$image->no] ?? 'Pickup Interior Image';
                @endphp
                <div class="col-md-3 mb-3">
                    <img src="{{ asset('storage/' . $image->file_name) }}?nocache={{ time() }}"
                        class="img-fluid rounded border shadow-sm image-thumb"
                        alt="{{ $label }}"
                        data-bs-toggle="modal" data-bs-target="#imageModal"
                        data-bs-image="{{ asset('storage/' . $image->file_name) }}">
                </div>
            @empty
                <div class="col-12"><i>Interior image is not yet available</i></div>
            @endforelse

            </div>

            <h6 class="mb-2 text-primary">Return</h6>
            <div class="row">
                @forelse($returnInteriorImages as $index => $image)
                    <div class="col-md-3 mb-3">
                        <img src="{{ asset('assets/img/car_state/return/interior/' . $image) }}?nocache={{ time() }}"
                             class="img-fluid rounded border shadow-sm image-thumb"
                             alt="Return Interior Image {{ $index + 1 }}"
                             data-bs-toggle="modal" data-bs-target="#imageModal"
                             data-bs-image="{{ asset('assets/img/car_state/return/interior/' . $image) }}">
                    </div>
                @empty
                    <div class="col-12"><i>Interior image for return is not yet available</i></div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- End Car Interior Image State Section --}}

{{-- Car Exterior Image State Section --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Car Exterior Image State</h5>
    </div>
    <div class="card-body pb-2">
        <div class="table-responsive">

            {{-- Pickup Section --}}
            <h6 class="mb-2 text-primary">Pickup</h6>
            <div class="row mb-3">
                @php
                    $pickupExteriorImages = \App\Models\UploadData::where('booking_trans_id', $booking->id)
                        ->where('position', 'pickup_exterior')
                        ->where('file_size', '!=', 0)
                        ->orderBy('no')
                        ->get(['file_name', 'no']);

                    $exteriorLabels = [
                        1 => 'Front Left',
                        2 => 'Front Right',
                        3 => 'Rear Left',
                        4 => 'Rear Right',
                        5 => 'Rear',
                        6 => 'Front with Customer',
                        7 => 'Front View',
                    ];
                @endphp

                @forelse($pickupExteriorImages as $image)
                    @php
                        $label = $exteriorLabels[$image->no] ?? 'Pickup Exterior Image';
                    @endphp
                    <div class="col-md-3 mb-3">
                        <img src="{{ asset('storage/' . $image->file_name) }}?nocache={{ time() }}"
                            class="img-fluid rounded border shadow-sm image-thumb"
                            alt="{{ $label }}"
                            data-bs-toggle="modal" data-bs-target="#imageModal"
                            data-bs-image="{{ asset('storage/' . $image->file_name) }}">
                    </div>
                @empty
                    <div class="col-12"><i>Exterior pickup image is not yet available</i></div>
                @endforelse
            </div>

            {{-- Return Section --}}
            <h6 class="mb-2 text-primary">Return</h6>
            <div class="row">
                @php
                    $returnExteriorImages = \App\Models\UploadData::where('booking_trans_id', $booking->id)
                        ->where('position', 'return_exterior')
                        ->where('file_size', '!=', 0)
                        ->latest('id')
                        ->pluck('file_name');
                @endphp

                @forelse($returnExteriorImages as $index => $image)
                    <div class="col-md-3 mb-3">
                        <img src="{{ asset('assets/img/car_state/return/exterior/' . $image) }}?nocache={{ time() }}"
                            class="img-fluid rounded border shadow-sm image-thumb"
                            alt="Return Exterior Image {{ $index + 1 }}"
                            data-bs-toggle="modal" data-bs-target="#imageModal"
                            data-bs-image="{{ asset('assets/img/car_state/return/exterior/' . $image) }}">
                    </div>
                @empty
                    <div class="col-12"><i>Exterior return image is not yet available</i></div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- End Car Exterior Image State Section --}}


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');

        document.querySelectorAll('.image-thumb').forEach(img => {
            img.addEventListener('click', function () {
                modalImage.src = this.getAttribute('data-bs-image');
            });
        });
    });
</script>



{{-- Car Condition Section ----}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Car Condition</h5>
    </div>
    <div class="card-body pb-2">
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Item</th>
                        <th>Remark (Pickup)</th>
                        <th>Remark (Return)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start">Car Seat Condition</td>
                        <td>{{ $checklist->car_out_seat_condition ?? '-' }}</td>
                        <td>{{ $checklist->car_in_seat_condition ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-start">Cleanliness</td>
                        <td>{{ $checklist->car_out_cleanliness ?? '-' }}</td>
                        <td>{{ $checklist->car_in_cleanliness ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-start">Fuel Level</td>
                        <td>{{ $checklist->car_out_fuel_level ?? '-' }}</td>
                        <td>{{ $checklist->car_in_fuel_level ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
{{-- End Car Condition Section --}}

{{-- Start Car Condition Pic Section --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Car Condition Pic</h5>
        <b><a class="text-muted">Remark: | = Scratch &nbsp;&nbsp; O = Broken &nbsp;&nbsp; △ = Dent &nbsp;&nbsp; □ = Missing</a></b>
    </div>
    <div class="card-body pb-2">
    <div class="table-responsive">
        <div class="row g-4">

            {{-- Pickup Image --}}
            <div class="col-md-6 text-center">
                <div class="fw-bold mb-2">Pickup</div>
                <a href="{{ route('pickup.damage', ['id' => $booking->id]) }}"
                   style="display:inline-block; width:450px; max-width:100%; height:300px;  border-radius:8px; overflow:hidden;">
                    <img src="{{ !empty($checklist->car_out_image) ? asset('storage/'.$checklist->car_out_image) : asset('images/pickup.jpg') }}?nocache={{ time() }}"
                         alt="Pickup Condition"
                         style="width:100%; height:100%; object-fit:contain; display:block;">
                </a>
            </div>

            {{-- Return Image --}}
            <div class="col-md-6 text-center">
                <div class="fw-bold mb-2">Return</div>
                <a href="{{ route('return.damage', ['id' => $booking->id]) }}"
                    style="display:inline-block; width:450px; max-width:100%; height:300px; border-radius:8px; overflow:hidden;">
                        <img src="{{ !empty($checklist->car_in_image) ? asset('storage/'.$checklist->car_in_image) : asset('images/pickup.jpg') }}?nocache={{ time() }}"
                             alt="Return Condition"
                             style="width:100%; height:100%; object-fit:contain; display:block;">
                
                </a>
            </div>

        </div>
    </div>
</div>

</div>
{{-- End Car Condition Pic Section --}}

{{-- Extend Information Section --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Extend Information</h5>
    </div>
    <div class="card-body pb-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Extend Date</th>
                        <th>Payment Status</th>
                        <th>Payment Type</th>
                        <th>Payment Date</th>
                        <th>Rental</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($extends as $index => $extend)
                        @php
                            $receipt = \App\Models\UploadData::where('booking_trans_id', $booking->id)
                                ->where('position', 'extend_receipt')
                                ->where('no', $index + 1)
                                ->first();
                        @endphp
                        <tr>
                            <th scope="row">{{ $index + 1 }}</th>
                            <td>{{ \Carbon\Carbon::parse($extend->extend_from_date)->format('d/m/Y') }} @ {{ $extend->extend_from_time }} - {{ \Carbon\Carbon::parse($extend->extend_to_date)->format('d/m/Y') }} @ {{ $extend->extend_to_time }}</td>
                            <td>{{ $extend->payment_status }}</td>
                            <td>{{ $extend->payment_type }}</td>
                            <td>{{ \Carbon\Carbon::parse($extend->c_date)->format('d/m/Y') }}</td>
                            <td>RM {{ number_format($extend->total, 2) }}</td>
                            <td>
                                RM {{ number_format($extend->payment, 2) }}
                                @if($receipt)
                                    <br>
                                    <button class="btn btn-sm btn-primary mt-1" onclick="openReceiptModal('{{ asset('assets/img/receipt/extend/'.$receipt->file_name) }}', 'Extend {{ $index + 1 }}')">
                                        <i class="fa fa-print"></i> Receipt
                                    </button>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('extendreceipt?booking_id='.$booking->id.'&extend_id='.$extend->id) }}" class="btn btn-sm btn-info"><i class="fa fa-print"></i> Print</a>
                                @if(in_array($occupation, ['Admin', 'Manager', 'Sales Executive','company']))
                                    <a href="{{ url('extend_edit?extend_id='.$extend->id) }}" class="btn btn-sm btn-warning"><i class="fa fa-external-link"></i> Edit</a>
                                    <a href="{{ url('delete_extend?extend_id='.$extend->id.'&confirm=pending&booking_id='.$booking->id) }}" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></a>
                                @endif
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
{{-- End Extend Information Section --}}

{{-- Start Receipt Section --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Receipt</h5>
    </div>
    <div class="card-body pb-2">
        <style>
            .mytable {
                border-collapse: collapse;
                width: 100%;
                text-align: center;
            }
            .mytable td, .mytable th {
                border: 1px solid black;
                padding: 6px;
                vertical-align: middle;
            }
        </style>

        <div class="table-responsive">
            <table class="table mytable ">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pay to (Name)</th>
                        <th colspan="4">Return Date @ Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td></td>
                        <td>{{ $fullname ?? '-' }}</td>
                        <td colspan="4">
                            {{ !empty($return_date_final) ? \Carbon\Carbon::parse($return_date_final)->format('d/m/Y H:i:s') : \Carbon\Carbon::parse($booking->return_date)->format('d/m/Y H:i:s') }}
                        </td>
                    </tr>
                </tbody>

                {{-- Refund to Customer --}}
                <thead>
                    <tr>
                        <th width="3%">#</th>
                        <th>Payment To Customer</th>
                        <th>Details</th>
                        <th>Payment Status</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Deposit</td>
                        <td>Pay Deposit To Customer</td>
                        <td>{{ $booking->refund_dep_status ?? '-' }}</td>
                        <td>RM {{ number_format($booking->refund_dep ?? 0, 2) }}</td>
                    </tr>
                    @if(!empty($other_details))
                    <tr>
                        <td>2</td>
                        <td>Others</td>
                        <td>{{ $other_details }}</td>
                        <td>{{ $other_details_payment_type }}</td>
                        <td>RM {{ number_format($other_details_price ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td>
                            RM {{ number_format(($refund_dep ?? 0) + ($other_details_price ?? 0), 2) }}
                        </td>
                    </tr>
                </tbody>

                {{-- Payment From Customer --}}
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment From Customer</th>
                        <th>Details</th>
                        <th>Payment Type</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Outstanding Extend</td>
                        <td>
                            {{-- {{ $outstanding_extend }}
                            @php
                                $outstandingReceipt = \App\Models\UploadData::where('booking_trans_id', $booking->id)
                                    ->where('position', 'outstanding_receipt')
                                    ->first();
                            @endphp
                            @if($outstandingReceipt)
                                <button class="btn btn-sm btn-primary mt-1" onclick="openReceiptModal('{{ asset('assets/img/receipt/outstanding/'.$outstandingReceipt->file_name) }}', 'Outstanding Receipt')">
                                    <i class="fa fa-print"></i> Receipt
                                </button>
                            @endif --}}
                        </td>
                       <td> {{-- {{ $outstanding_extend_type_of_payment }}--}}</td> 
                        <td>RM{{--  {{ number_format($outstanding_extend_cost ?? 0, 2) }}--}}</td> 
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>Charges for Damages</td>
                         <td>{{--{{ $damage_charges_details }}--}}</td> 
                        <td>{{--{{ $damage_charges_payment_type }}--}}</td>
                        <td>RM {{--{{ number_format($damage_charges ?? 0, 2) }}--}}</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>Charges for Missing Items</td>
                        <td>{{--{{ $missing_items_charges_details }}--}}</td>
                        <td>{{--{{ $missing_items_charges_payment_type }}--}}</td>
                        <td>RM{{-- {{ number_format($missing_items_charges ?? 0, 2) }}--}}</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>Additional Cost</td>
                        <td>{{--{{ $additional_cost_details }}--}}</td>
                        <td>{{--{{ $additional_cost_payment_type }}--}}</td>
                        <td>RM {{--{{ number_format($additional_cost ?? 0, 2) }}--}}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td>
                            RM {{--{{ number_format(($outstanding_extend_cost ?? 0) + ($damage_charges ?? 0) + ($missing_items_charges ?? 0) + ($additional_cost ?? 0), 2) }}--}}
                        </td>
                    </tr>
                </tbody>

                {{-- Prepared By --}}
                <thead>
                    <tr>
                        <th colspan="5">Prepared By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5">{{ $car_in_checkby ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Receipt Modal --}}
<div id="receiptModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="receiptTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
            <img id="receiptImage" src="" class="img-fluid" alt="Receipt Image">
        </div>
    </div>
  </div>
</div>

<script>
    function openReceiptModal(imgUrl, title) {
        document.getElementById('receiptImage').src = imgUrl;
        document.getElementById('receiptTitle').innerText = title;
        let modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        modal.show();
    }
</script>
{{-- End Receipt Section --}}




{{-- !!MODEL SECTION!! --}}


{{--Pickup Modal--}}

<div class="modal fade" id="pickupModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel2">Pickup</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                
            </div>

            <div class="modal-body">
                <form method="GET" action="{{ route('agreement.condition') }}" class="form-horizontal form-label-left">
                    <div class="form-group row">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="language">Choose Language</label>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <select name="language" class="form-control" required>
                                <option value="">-- Please select --</option>
                                <option value="english" {{ (old('language', $language ?? '') == 'english') ? 'selected' : '' }}>English</option>
                                <option value="malay" {{ (old('language', $language ?? '') == 'malay') ? 'selected' : '' }}>Malay</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                        <input type="hidden" name="step" value="1">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>



{{-- Receipt Modal --}}
<div id="receiptModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="receiptTitle"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
            <img id="receiptImage" src="" class="img-fluid" alt="Receipt Image">
        </div>
    </div>
  </div>
</div>


<!-- Interior Image Modal -->
<div id="imageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Preview Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="modalImage" class="img-fluid rounded w-75 mx-auto d-block" alt="Interior Image Preview">

            </div>
        </div>
    </div>
</div>


<!-- Car State Image Modal (fixed) -->
<div class="modal fade" id="carStateImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <img src="" id="carStateModalImg" class="img-fluid rounded">
                <div id="carStateCaption" class="mt-2 text-muted"></div>
            </div>
        </div>
    </div>
</div>




{{-- Blacklist Modal --}}
<div class="modal fade" id="blacklistModal" tabindex="-1" aria-labelledby="blacklistModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Blacklist Customer</h5></div>
            <div class="modal-body">
                <p>Are you sure you want to blacklist this customer?</p>
                <button class="btn btn-danger">Confirm Blacklist</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Reason for Blacklist Modal --}}
<div class="modal fade" id="reasonBlacklistModal" tabindex="-1" aria-labelledby="reasonBlacklistModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Reason for Blacklist</h5></div>
            <div class="modal-body">
                <p>Reason for blacklist will be shown here.</p>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal for large image view --}}
<div id="imgModal" class="modal" tabindex="-1" style="display:none; background:rgba(0,0,0,0.6)">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <img id="imgModalSrc" src="" style="width:100%;border-radius:8px;">
        </div>
    </div>
</div>

{{-- Enable Bootstrap tooltips --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@push('scripts')
<script>
    // Enable Bootstrap tooltips globally
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    function generateLink(type) {
        let url = window.location.origin;
        let token = Math.random().toString(36).substr(2, 8);
        let message = '';
        if(type === 'postInspection') {
            message = 'Post Inspection Link:\n\n' + url + '/post-inspection/' + token;
            document.getElementById('postInspectionLink').value = message;
        } else if(type === 'register') {
            message = 'Register Link:\n\n' + url + '/register/' + token;
            document.getElementById('registerLink').value = message;
        }
    }

    function copyLink(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        alert('Link has been copied to clipboard');
    }

    // Modal for large image view
    function showModal(img) {
        document.getElementById('imgModalSrc').src = img.src;
        document.getElementById('imgModal').style.display = 'block';
    }
    document.getElementById('imgModal').onclick = function() {
        this.style.display = 'none';
    };
    // Approve/Reject logic
    document.addEventListener('DOMContentLoaded', function() {
        let rejectBtn = document.getElementById('rejectBtn');
        let remarkForm = document.getElementById('remarkForm');
        if(rejectBtn) {
            rejectBtn.onclick = function() {
                if (remarkForm.style.display === 'none') {
                    remarkForm.style.display = '';
                } else {
                    remarkForm.style.display = 'none';
                }
            }
        }
    });
    // Enable/disable date/time fields for allowed roles
    document.addEventListener('DOMContentLoaded', function() {
    // Date fields
    let dateToggle = document.getElementById('date_toggle2');
    if(dateToggle) {
        dateToggle.addEventListener('change', function() {
            let state = !this.checked ? true : false;
            document.getElementById('sale_pickup_date').disabled = state;
            document.getElementById('sale_pickup_time').disabled = state;
            document.getElementById('sale_return_date').disabled = state;
            document.getElementById('sale_return_time').disabled = state;
        });
    }

    // Sale fields
    let saleToggle = document.getElementById('sale_toggle2');
    if(saleToggle) {
        saleToggle.addEventListener('change', function() {
            let state = !this.checked ? true : false;
            document.getElementById('booking_fee').disabled = state;
            document.getElementById('booking_fee_payment').disabled = state;
            document.getElementById('sale_total').disabled = state;
            document.getElementById('payment_made').disabled = state;
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalTitle = document.getElementById('imageModalLabel');

        document.querySelectorAll('.image-thumb').forEach(img => {
            img.addEventListener('click', function () {
                const src = this.getAttribute('data-bs-image');
                const alt = this.getAttribute('alt');
                modalImage.setAttribute('src', src);
                modalTitle.textContent = alt || 'Image Preview';
            });
        });
    });

    function showModal(img) {
        const modalElement = document.getElementById('carStateImageModal');
        const modal = new bootstrap.Modal(modalElement);
        document.getElementById('carStateModalImg').src = img.src;
        document.getElementById('carStateCaption').innerHTML = img.alt || '';
        modal.show();
    }

    function openReceiptModal(imgUrl, title) {
        document.getElementById('receiptImage').src = imgUrl;
        document.getElementById('receiptTitle').innerText = title;
        var modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        modal.show();
    }
</script>
@endpush

@endsection
