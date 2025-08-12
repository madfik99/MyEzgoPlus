@extends('layouts.main')

@section('page-title')
    {{ __('Pickup Vehicle') }}
@endsection

@section('page-breadcrumb')
    {{ __('Pickup Vehicle') }}
@endsection

@section('content')
<div class="container mt-4">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="btn-group mb-3 mt-3">
        <a href="{{ route('reservation.view', $booking->id) }}" class="btn btn-secondary">Back</a>
        
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card mb-4">
                <div class="card-header">
                    <h5>Pickup</h5>
                    
                </div>
                <div class="card-body">

                    {{-- <h5>Customer & Vehicle Info</h5>
                    <table class="table table-bordered">
                        <tr><th>Customer</th><td>{{ $booking->customer->firstname }} {{ $booking->customer->lastname }}</td></tr>
                        <tr><th>Phone</th><td>{{ $booking->customer->phone_no }}</td></tr>
                        <tr><th>NRIC</th><td>{{ $booking->customer->nric_no }}</td></tr>
                        <tr><th>Vehicle</th><td>{{ $booking->vehicle->make }} {{ $booking->vehicle->model }}</td></tr>
                        <tr><th>Pickup</th><td>{{ date('d M Y H:i A', strtotime($booking->pickup_date)) }}</td></tr>
                        <tr><th>Return</th><td>{{ date('d M Y H:i A', strtotime($booking->return_date)) }}</td></tr>
                    </table> --}}

                    @if(in_array($booking->available, ['Out', 'Extend']))
                        <div class="alert alert-warning text-center">
                            This vehicle ({{ $booking->vehicle->reg_no }}) is not returned yet. Complete previous agreement to proceed.
                        </div>
                    @endif

    <form id="pickup-form" method="POST" action="{{ route('pickup.update', $booking->id) }}" enctype="multipart/form-data">
                        @csrf

                        <small class="text-danger">Please note: Once pickup has been made, this form cannot be changed.</small>
                        <h5 class="mt-4">Payment & Interior</h5>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Rental Fee (MYR)</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ number_format($booking->est_total, 2) }}" disabled>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Payment Made (MYR)</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ number_format($booking->balance, 2)}} ({{($booking->payment_type) }})" disabled>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Booking Fee (MYR)</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ number_format($booking->refund_dep, 2) }}" disabled>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Balance Payment (MYR)</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ number_format($booking->est_total - $booking->balance, 2) }}" disabled>

                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Payment Status</label>
                            <div class="col-sm-9">
                                <select name="payment_status" class="form-control bg-light" disabled>
                                            <option value="">-- Please Select --</option>
                                            <option value="Unpaid" {{ (old('payment_status', $booking->payment_status ?? '') == 'Unpaid' )? 'selected' : '' }}>Unpaid</option>
                                            <option value="Booking" {{ (old('payment_status', $booking->payment_status ?? '') == 'Booking') ? 'selected' : '' }}>Booking only</option>
                                            <option value="BookingRental" {{ (old('payment_status', $booking->payment_status ?? '') == 'BookingRental') ? 'selected' : '' }}>Booking + Rental (Incomplete Payment)</option>
                                            <option value="FullRental" {{ (old('payment_status', $booking->payment_status ?? '') == 'FullRental') ? 'selected' : '' }}>Booking + Rental (Full Payment)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Payment Type</label>
                            <div class="col-sm-9">
                                <select name="payment_type" class="form-control" required>
                                    <option value="">-- Please Select --</option>
                                    <option value="Cash" {{ (old('payment_type', $booking->payment_type ?? '') == 'Cash') ? 'selected' : '' }}>Cash</option>
                                    <option value="Online" {{ (old('payment_type', $booking->payment_type ?? '') == 'Online') ? 'selected' : '' }}>Online</option>
                                    <option value="Card" {{ (old('payment_type', $booking->payment_type ?? '') == 'Card') ? 'selected' : '' }}>Card</option>
                                    <option value="QRPay" {{ (old('payment_type', $booking->payment_type ?? '') == 'QRPay') ? 'selected' : '' }}>QRPay</option>
                                </select>
                            </div>
                        </div>


                        {{-- <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Payment Receipt</label>
                            <div class="col-sm-9">
                                <input type="file" name="pickup_receipt" class="form-control" accept="image/*" required>
                            </div>
                        </div> --}}
                    
                </div>
            </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mt-4">Interior Checklist</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php
                                $checklistItems = [
                                    'start_engine' => 'Start Engine',
                                    'engine_condition' => 'Engine Condition',
                                    'test_gear' => 'Test Gear',
                                    'no_alarm' => 'No Alarm',
                                    'air_conditioner' => 'Air Conditioner',
                                    'radio' => 'Radio',
                                    'wiper' => 'Wiper',
                                    'window_condition' => 'Window Condition',
                                    'power_window' => 'Power Window',
                                    'perfume' => 'Perfume',
                                    'carpet' => 'Carpet (RM20/pcs)',
                                    'sticker_p' => 'Sticker P (RM5)',
                                    'Jack' => 'Jack (Rm70)',
                                    'Tools' => 'Tools (Rm30)',
                                    'Signage'=> 'Signage (Rm30)',
                                    'Tyre_Spare' => 'Tyre Spare (Rm200)',
                                    'Child_Seat' => 'Child Seat',
                                    'Lamp' => 'Lamp',
                                    'Tyres_Condition' => 'Tyres Condition',

                                ];
                            @endphp

                            @foreach($checklistItems as $name => $label)
                                <div class="col-md-4 mb-3">
                                    <label class="d-flex align-items-center border p-3 rounded shadow-sm w-100" style="cursor: pointer;">
                                        <input type="checkbox" name="{{ $name }}" value="Y" id="{{ $name }}" class="me-2" style="accent-color: #ff4d6d;">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach

                        </div>

                        <div class="row mt-4">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label>Fuel Level</label>
                                            </div>
                                            <div class="col-md-8">
                                                <select name="fuel_level" class="form-control" required>
                                                    <option value="">-- Please Select --</option>
                                                    @for($i = 0; $i <= 6; $i++)
                                                        <option value="{{ $i }}">{{ $i == 0 ? 'Empty' : $i . ' Bar' }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label>Mileage</label>
                                            </div>
                                            <div class="col-md-8">
                                            <input type="number" class="form-control" name="mileage" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label>Seat Condition</label>
                                            </div>
                                            <div class="col-md-8">
                                                <select name="car_seat_condition" class="form-control" required>
                                        <option value="">-- Please select --</option>
                                        @foreach(['Clean', 'Dirty', '1 Cigarettes Bud', '2 Cigarettes Bud', '3 Cigarettes Bud', '4 Cigarettes Bud', '5 Cigarettes Bud'] as $option)
                                            <option value="{{ $option }}" {{ old('car_seat_condition', $car_seat_condition ?? '') == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label class="control-label">Vehicle Cleanliness</label>
                                            </div>
                                            <div class="col-md-8">
                                                <select name="cleanliness" class="form-control" required>
                                                    <option value="">-- Please select --</option>
                                                    @foreach(['Clean', 'Dirty'] as $option)
                                                        <option value="{{ $option }}" {{ old('cleanliness', $cleanliness ?? '') == $option ? 'selected' : '' }}>
                                                            {{ $option }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>



            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mt-4">Interior Images</h5>
                </div>
                <div class="card-body">
                    <div class="row mt-4">
                        {{-- Left column: 3 fields --}}
                        <div class="col-md-6">
                            @php
                                $leftFields = [
                                    ['name' => 'interior0', 'label' => 'Dashboard & Windscreen'],
                                    ['name' => 'interior2', 'label' => 'Second Row Seat'],
                                    ['name' => 'interior4', 'label' => 'Fourth Row Seat (Optional)'],
                                ];
                            @endphp

                            @foreach ($leftFields as $field)
                                <div class="form-group mb-3">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label class="control-label">{{ $field['label'] }}</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="file" name="{{ $field['name'] }}" class="form-control mb-2" >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Right column: 3 fields --}}
                        <div class="col-md-6">
                            @php
                                $rightFields = [
                                    ['name' => 'interior1', 'label' => 'First Row Seat'],
                                    ['name' => 'interior3', 'label' => 'Third Row Seat (Optional)'],
                                ];
                            @endphp

                            @foreach ($rightFields as $field)
                                <div class="form-group mb-3">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label class="control-label">{{ $field['label'] }}</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="file" name="{{ $field['name'] }}" class="form-control mb-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mt-4">Exterior Images</h5>
                </div>
                <div class="card-body">
                    <div class="row mt-4">
                        {{-- Left column: 3 fields --}}
                        <div class="col-md-6">
                            @php
                                $leftFields = [
                                    ['name' => 'front_left', 'label' => 'Front Left'],
                                    ['name' => 'rear', 'label' => 'Rear'],
                                    ['name' => 'front_right', 'label' => 'Front Right'],
                                    ['name' => 'front_with_customer', 'label' => 'Front with Customer'],
                                ];
                            @endphp

                            @foreach ($leftFields as $field)
                                <div class="form-group mb-3">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label class="control-label">{{ $field['label'] }}</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="file" name="{{ $field['name'] }}" class="form-control mb-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Right column: 3 fields --}}
                        <div class="col-md-6">
                            @php
                                $rightFields = [
                                    ['name' => 'rear_left', 'label' => 'Rear Left'],
                                    ['name' => 'rear_right', 'label' => 'Rear Right'],
                                    ['name' => 'front', 'label' => 'Front'], // You can remove or change this
                                ];
                            @endphp

                            @foreach ($rightFields as $field)
                                <div class="form-group mb-3">
                                    <div class="border p-3 rounded shadow-sm">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-md-end">
                                                <label class="control-label">{{ $field['label'] }}</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="file" name="{{ $field['name'] }}" class="form-control mb-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>



            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mt-4">Vehicle Damage Marking</h5>
                </div>
                <div class="card-body">
                    <div class="col-12" id="canvas-holder2">
                        <center>
                            <canvas id="board" class="img-responsive" style="border: 1px solid;" width="300" height="200"></canvas>

                            <div class="form-group mt-3">
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addCircles">+ Broken</button>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addEquals">+ Scratch</button>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addRects">+ Missing</button>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="addTriangles">+ Dent</button>
                                <button type="button" class="btn btn-sm btn-danger mt-2" id="removeShape">- Remove</button>
                            </div>

                        </center>
                        <input name="hidden_datas" id="hidden_datas" type="hidden"/>

                        <h5>Remarks</h5>
                        <textarea class="form-control" id="markingRemarks" name="markingRemarks" rows="3" placeholder="e.g. dented, cracked, missing..."></textarea>
                    </div>
                </div>
            </div>

            {{-- <div class="text-center mb-5">
                <button type="submit" class="btn btn-success btn-lg">Submit Pickup Details</button>
            </div> --}}



            @php
            $hotspotMap = [
                'sedan' => [
                    'front' => [
                        ['top' => '22.5%', 'left' => '47.5%', 'part' => 'Front Glass'],
                        ['top' => '47.5%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '30.5%', 'left' => '5.5%', 'part' => 'Left Side Mirror'],
                        ['top' => '30.5%', 'left' => '90%', 'part' => 'Right Side Mirror'],
                        ['top' => '47.5%', 'left' => '15.5%', 'part' => 'Left Headlight'],
                        ['top' => '66%', 'left' => '47.5%', 'part' => 'Bumper'],
                        ['top' => '40%', 'left' => '47.5%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '30.5%', 'left' => '33.5%', 'part' => 'Front Glass Left'],
                        ['top' => '55.5%', 'left' => '72.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '42.5%', 'left' => '25%', 'part' => 'Left Front Fender'],
                        ['top' => '55.5%', 'left' => '19.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '50.5%', 'part' => 'Left Door'],
                        ['top' => '33.5%', 'left' => '43.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '22.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '40%', 'left' => '80%', 'part' => 'Rear Right Headlight'],
                        ['top' => '40%', 'left' => '15.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '66%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '40%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '30.5%', 'left' => '60.5%', 'part' => 'Front Glass Right'],
                        ['top' => '55.5%', 'left' => '76.5%', 'part' => 'Front Right Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Right Skirt'],
                        ['top' => '42.5%', 'left' => '70%', 'part' => 'Right Front Fender'],
                        ['top' => '55.5%', 'left' => '23.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '45.5%', 'part' => 'Right Door'],
                        ['top' => '33.5%', 'left' => '50.5%', 'part' => 'Right Window'],
                    ],
                ],
                'hatchback' => [
                    'front' => [
                        ['top' => '25%', 'left' => '48%', 'part' => 'Front Glass'],
                        ['top' => '47%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '31.5%', 'left' => '6%', 'part' => 'Left Side Mirror'],
                        ['top' => '31.5%', 'left' => '88%', 'part' => 'Right Side Mirror'],
                        ['top' => '47%', 'left' => '15%', 'part' => 'Left Headlight'],
                        ['top' => '66%', 'left' => '48%', 'part' => 'Bumper'],
                        ['top' => '40%', 'left' => '48%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '30.5%', 'left' => '33.5%', 'part' => 'Front Glass Left'],
                        ['top' => '55.5%', 'left' => '78.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '58%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '43.5%', 'left' => '23%', 'part' => 'Left Front Fender'],
                        ['top' => '55.5%', 'left' => '15.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '54.5%', 'part' => 'Left Door'],
                        ['top' => '33.5%', 'left' => '43.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '30.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '42.5%', 'left' => '80.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '42.5%', 'left' => '13.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '63%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '30.5%', 'left' => '60.5%', 'part' => 'Front Glass Right'],
                        ['top' => '55.5%', 'left' => '76.5%', 'part' => 'Front Right Tire'],
                        ['top' => '57%', 'left' => '45%', 'part' => 'Right Skirt'],
                        ['top' => '42.5%', 'left' => '73%', 'part' => 'Right Front Fender'],
                        ['top' => '55.5%', 'left' => '16.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '40.5%', 'part' => 'Right Door'],
                        ['top' => '33.5%', 'left' => '50.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                'suv' => [
                    'front' => [
                        ['top' => '20%', 'left' => '48%', 'part' => 'Front Glass'],
                        ['top' => '40%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '26.5%', 'left' => '8%', 'part' => 'Left Side Mirror'],
                        ['top' => '26.5%', 'left' => '88%', 'part' => 'Right Side Mirror'],
                        ['top' => '40%', 'left' => '15%', 'part' => 'Left Headlight'],
                        ['top' => '68%', 'left' => '48%', 'part' => 'Bumper'],
                        ['top' => '33%', 'left' => '48%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '28.5%', 'left' => '33.5%', 'part' => 'Front Glass Left'],
                        ['top' => '60.5%', 'left' => '74.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '40.5%', 'left' => '23%', 'part' => 'Left Front Fender'],
                        ['top' => '60.5%', 'left' => '18.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '50.5%', 'part' => 'Left Door'],
                        ['top' => '30.5%', 'left' => '43.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '28.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '39.5%', 'left' => '78.5%', 'part' => 'Rear Right Headlight'],
                        // ['top' => '39.5%', 'left' => '13.5%', 'part' => 'Rear Left Headlight'],
                        // ['top' => '60%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        // ['top' => '45%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    // 'right' => [
                    //     ['top' => '28.5%', 'left' => '60.5%', 'part' => 'Front Glass Right'],
                    //     ['top' => '60.5%', 'left' => '78.5%', 'part' => 'Front Right Tire'],
                    //     ['top' => '60%', 'left' => '50%', 'part' => 'Right Skirt'],
                    //     ['top' => '40.5%', 'left' => '73%', 'part' => 'Right Front Fender'],
                    //     ['top' => '60.5%', 'left' => '20.5%', 'part' => 'Rear Right Tire'],
                    //     ['top' => '45%', 'left' => '45.5%', 'part' => 'Right Door'],
                    //     ['top' => '30.5%', 'left' => '50.5%', 'part' => 'Right Window'],
                    // ],
                    
                ],
                'van' => [
                    'front' => [
                        ['top' => '20%', 'left' => '48%', 'part' => 'Front Glass'],
                        ['top' => '45%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '28%', 'left' => '6%', 'part' => 'Left Side Mirror'],
                        ['top' => '28%', 'left' => '90%', 'part' => 'Right Side Mirror'],
                        ['top' => '45%', 'left' => '15%', 'part' => 'Left Headlight'],
                        ['top' => '70%', 'left' => '48%', 'part' => 'Bumper'],
                        ['top' => '38%', 'left' => '48%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '28.5%', 'left' => '25.5%', 'part' => 'Front Glass Left'],
                        ['top' => '63.5%', 'left' => '75.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '64%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '45.5%', 'left' => '20%', 'part' => 'Left Front Fender'],
                        ['top' => '63.5%', 'left' => '20.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '45.5%', 'part' => 'Left Door'],
                        ['top' => '30.5%', 'left' => '40.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '28.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '45.5%', 'left' => '80.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '45.5%', 'left' => '12.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '63%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '28.5%', 'left' => '68.5%', 'part' => 'Front Glass Right'],
                        ['top' => '63.5%', 'left' => '75.5%', 'part' => 'Front Right Tire'],
                        ['top' => '64%', 'left' => '50%', 'part' => 'Right Skirt'],
                        ['top' => '45.5%', 'left' => '77%', 'part' => 'Right Front Fender'],
                        ['top' => '63.5%', 'left' => '20.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '50.5%', 'part' => 'Right Door'],
                        ['top' => '30.5%', 'left' => '55.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                'mpv' => [
                    'front' => [
                        ['top' => '25%', 'left' => '48%', 'part' => 'Front Glass'],
                        ['top' => '45%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '33%', 'left' => '6%', 'part' => 'Left Side Mirror'],
                        ['top' => '33%', 'left' => '88%', 'part' => 'Right Side Mirror'],
                        ['top' => '45%', 'left' => '15%', 'part' => 'Left Headlight'],
                        ['top' => '64%', 'left' => '48%', 'part' => 'Bumper'],
                        ['top' => '40%', 'left' => '48%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '28.5%', 'left' => '25.5%', 'part' => 'Front Glass Left'],
                        ['top' => '63.5%', 'left' => '72.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '62%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '45.5%', 'left' => '20%', 'part' => 'Left Front Fender'],
                        ['top' => '63.5%', 'left' => '18.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '45.5%', 'part' => 'Left Door'],
                        ['top' => '30.5%', 'left' => '40.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '28.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '42.5%', 'left' => '80.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '42.5%', 'left' => '12.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '63%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '53%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '28.5%', 'left' => '68.5%', 'part' => 'Front Glass Right'],
                        ['top' => '63.5%', 'left' => '77.5%', 'part' => 'Front Right Tire'],
                        ['top' => '62%', 'left' => '50%', 'part' => 'Right Skirt'],
                        ['top' => '45.5%', 'left' => '77%', 'part' => 'Right Front Fender'],
                        ['top' => '63.5%', 'left' => '23.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '50.5%', 'part' => 'Right Door'],
                        ['top' => '30.5%', 'left' => '55.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                
            ];

            $hotspots = $hotspotMap[$layoutType] ?? $hotspotMap['sedan'];
            @endphp


            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mt-2">Damage Marking</h5>
                </div>
                <div class="card-body">
                        <!-- First Row -->
                        <div class="row gy-4">
                            <div class="col-12 col-md-6 d-flex">
                                <!-- FRONT LAYOUT CARD -->
                                <div class="card w-100 h-100">
                                    <div class="card-header">
                                        <h5 class="mt-4">Front Layout</h5>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <p class="text-muted">Tap on any hotspot to upload damage photo for that part of the car.</p>
                                            <div class="position-relative" style="height: 350px; max-width: 600px; margin: auto; overflow: hidden;">
                                            <img src="{{ asset('storage/layout/' . $layoutType . '/front_layout.png') }}" alt="Car Layout" class="img-fluid h-100 w-100 object-fit-cover">


                                                @foreach($hotspots['front'] ?? [] as $hotspot)
                                                    <button type="button" class="position-absolute btn btn-outline-primary rounded-circle"
                                                        style="top: {{ $hotspot['top'] }}; left: {{ $hotspot['left'] }}; width: 16px; height: 16px; padding: 0; font-size: 10px;"
                                                        data-bs-toggle="modal" data-bs-target="#uploadModal" data-part="{{ $hotspot['part'] }}">●</button>
                                                @endforeach

                                            </div>

                                        <div class="mt-4 damage-preview-list"></div>
                                    </div>
                                </div>

                            </div>

                            <div class="col-12 col-md-6 d-flex">
                                <!-- LEFT SIDE LAYOUT CARD -->
                                <div class="card w-100 h-100">
                                    <div class="card-header">
                                        <h5 class="mt-4">Left Side Layout</h5>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <p class="text-muted">Tap on any hotspot to upload damage photo for that part of the car.</p>
                                        <div class="position-relative" style="height: 350px; max-width: 600px; margin: auto; overflow: hidden;">
                                            <img src="{{ asset('storage/layout/' . $layoutType . '/left_side_layout.png') }}" alt="Car Layout" class="img-fluid h-100 w-100 object-fit-cover">
                                            <!-- Hotspots with reduced size -->

                                                @foreach($hotspots['left'] ?? [] as $hotspot)
                                                    <button type="button" class="position-absolute btn btn-outline-primary rounded-circle"
                                                        style="top: {{ $hotspot['top'] }}; left: {{ $hotspot['left'] }}; width: 16px; height: 16px; padding: 0; font-size: 10px;"
                                                        data-bs-toggle="modal" data-bs-target="#uploadModal" data-part="{{ $hotspot['part'] }}">●</button>
                                                @endforeach

                                        </div>
                                        <div class="mt-4 damage-preview-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Second Row -->
                        <div class="row gy-4 mt-2">
                            <div class="col-12 col-md-6 d-flex">
                                <!-- REAR LAYOUT CARD -->
                                <div class="card w-100 h-100">
                                    <div class="card-header">
                                        <h5 class="mt-4">Rear Layout</h5>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <p class="text-muted">Tap on any hotspot to upload damage photo for that part of the car.</p>
                                        <div class="position-relative" style="height: 350px; max-width: 600px; margin: auto; overflow: hidden;">
                                            <img src="{{ asset('storage/layout/' . $layoutType . '/rear_layout.png') }}" alt="Car Layout" class="img-fluid h-100 w-100 object-fit-cover">


                                            @foreach($hotspots['rear'] ?? [] as $hotspot)
                                                <button type="button" class="position-absolute btn btn-outline-primary rounded-circle"
                                                    style="top: {{ $hotspot['top'] }}; left: {{ $hotspot['left'] }}; width: 16px; height: 16px; padding: 0; font-size: 10px;"
                                                    data-bs-toggle="modal" data-bs-target="#uploadModal" data-part="{{ $hotspot['part'] }}">●</button>
                                            @endforeach
                                                    
                                        </div>
                                        <div class="mt-4 damage-preview-list"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="col-12 col-md-6 d-flex">
                                <!-- RIGHT SIDE LAYOUT CARD -->
                                <div class="card w-100 h-100">
                                    <div class="card-header">
                                        <h5 class="mt-4">Right Side Layout</h5>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <p class="text-muted">Tap on any hotspot to upload damage photo for that part of the car.</p>
                                        <div class="position-relative" style="height: 350px; max-width: 600px; margin: auto; overflow: hidden;">
                                            <img src="{{ asset('storage/layout/' . $layoutType . '/right_side_layout.png') }}" alt="Car Layout" class="img-fluid h-100 w-100 object-fit-cover">
                                            
                                            @foreach($hotspots['right'] ?? [] as $hotspot)
                                                <button type="button" class="position-absolute btn btn-outline-primary rounded-circle"
                                                    style="top: {{ $hotspot['top'] }}; left: {{ $hotspot['left'] }}; width: 16px; height: 16px; padding: 0; font-size: 10px;"
                                                    data-bs-toggle="modal" data-bs-target="#uploadModal" data-part="{{ $hotspot['part'] }}">●</button>
                                            @endforeach

                                        </div>
                                        <div class="mt-4 damage-preview-list"></div>
                                    </div>
                                </div>
                            </div> --}}
                        </div>
                    </div>
            </div>

            <!-- Master file input (hidden) -->
            <input type="file" id="masterDamagePhotos" name="damagePhotos[]" multiple hidden>

            <!-- These are REQUIRED and will hold JSON strings -->
            <input type="hidden" id="masterDamageParts" name="damageParts">
            <input type="hidden" id="masterDamageRemarks" name="damageRemarks">

            <!-- This is optional and will be used to inject individual array elements as backup -->
            <div id="damage-input-container"></div>

            <div id="damage-upload-container"></div>


            {{-- === RENTER SIGNATURE === --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mt-2">Renter Signature</h5>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-8">
                            <div class="border rounded p-2 bg-white">
                                {{-- important: width is responsive, JS will scale for high‑DPI devices --}}
                                <canvas id="signaturePad" style="width:100%; height:250px; touch-action:none; display:block;"></canvas>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="sig-undo">Undo</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="sig-clear">Clear</button>
                            </div>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" value="Y" name="renter_ack" id="renter_ack">
                                <label class="form-check-label" for="renter_ack">
                                    I (Renter) received this car in <strong>CLEAN</strong> condition without any
                                    <strong>FORBIDDEN STUFF</strong> or <strong>CRIMINAL ACTIVITY STUFF</strong>.
                                </label>
                            </div>

                            {{-- signature image (base64) will be put here on submit --}}
                            <input type="hidden" name="signature_data" id="signature_data">
                        </div>
                    </div>
                </div>
            </div>


            <div class="text-center mb-5">
                <button type="submit" class="btn btn-success btn-lg">Submit Pickup Details</button>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Upload Damage Photo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            {{-- <input type="hidden" name="damageParts[]" id="damage-part-name">
                            <input type="hidden" name="damageRemarks[]" id="damage-remarks-hidden"> --}}

                            <div class="mb-3">
                                <label for="damagePhoto" class="form-label">Upload Images</label>
                                <input type="file" class="form-control damage-photo-input" multiple>
                                
                            </div>
                            

                            <div class="mb-3">
                                <label for="damageRemarks" class="form-label">Damage Remarks</label>
                                <textarea class="form-control" id="damageRemarks" rows="3"
                                        placeholder="e.g. dented, cracked, missing..."></textarea>
                            </div>

                            <div id="selected-part-display" class="text-muted small"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="saveDamagePhoto" class="btn btn-primary">Save Photo</button>
                        </div>
                    </div>
                </div>
            </div>

    


    </form>
    


</div>






@endsection
@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pickup-form');
    const canvas = document.getElementById('signaturePad');
    const clearBtn = document.getElementById('sig-clear');
    const undoBtn  = document.getElementById('sig-undo');
    const renterAck = document.getElementById('renter_ack');
    const signatureInput = document.getElementById('signature_data');

    // Make the canvas crisp on retina & responsive
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        // keep the visual size (CSS) but scale the backing store
        const rect = canvas.getBoundingClientRect();
        canvas.width  = rect.width * ratio;
        canvas.height = rect.height * ratio;
        const ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);
        // fill white BG so saved image isn't transparent
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, rect.width, rect.height);
        // redraw existing signature if any
        const data = sig.toData();
        sig.clear();
        sig.fromData(data);
    }

    const sig = new SignaturePad(canvas, {
        minWidth: 0.8,
        maxWidth: 2.2,
        backgroundColor: '#ffffff',
        penColor: '#000000',
        throttle: 16
    });

    // initial size
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    clearBtn.addEventListener('click', () => sig.clear());
    undoBtn.addEventListener('click', () => {
        const data = sig.toData();
        if (data.length) { data.pop(); sig.fromData(data); }
    });

    // block submit if signature missing or ack unchecked; attach signature data
    form.addEventListener('submit', function (e) {
        if (sig.isEmpty()) {
            e.preventDefault();
            alert('Please provide a signature.');
            return;
        }
        if (!renterAck.checked) {
            e.preventDefault();
            alert('Please confirm the renter acknowledgement.');
            return;
        }
        signatureInput.value = sig.toDataURL('image/png');
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadModal = document.getElementById('uploadModal');
    const remarksInput = document.getElementById('damageRemarks');
    const fileInput = document.querySelector('.damage-photo-input');
    const saveDamageBtn = document.getElementById('saveDamagePhoto');

    const masterPhotos = document.getElementById('masterDamagePhotos');
    const masterPartsInput = document.getElementById('masterDamageParts');
    const masterRemarksInput = document.getElementById('masterDamageRemarks');
    const damageContainer = document.getElementById('damage-input-container');

    let previewTarget = null;
    let currentPartName = '';
    let allFilesDT = new DataTransfer();
    let allRemarks = [];
    let allParts = [];

    uploadModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        currentPartName = button.getAttribute('data-part');
        previewTarget = button.closest('.card-body').querySelector('.damage-preview-list');

        remarksInput.value = '';
        fileInput.value = '';
        document.getElementById('selected-part-display').textContent = "Selected Part: " + currentPartName;
    });

    saveDamageBtn.addEventListener('click', function () {
        const remarkText = remarksInput.value.trim();
        const files = fileInput.files;

        if (!files.length) {
            alert("Please select at least one photo.");
            return;
        }

        if (!remarkText) {
            alert("Please enter a remark.");
            return;
        }

        Array.from(files).forEach((file) => {
            allFilesDT.items.add(file);
            allRemarks.push(remarkText);
            allParts.push(currentPartName);

            const reader = new FileReader();
            reader.onload = function (e) {
                const wrapper = document.createElement('div');
                wrapper.classList.add('mb-2');
                wrapper.innerHTML = `
                    <strong>${currentPartName}</strong><br>
                    <span>${remarkText}</span><br>
                    <img src="${e.target.result}" style="max-width: 100px;" class="me-2 mb-2">
                `;
                previewTarget.appendChild(wrapper);
            };
            reader.readAsDataURL(file);
        });

        // Assign images
        masterPhotos.files = allFilesDT.files;

        // Encode arrays to JSON
        masterPartsInput.value = JSON.stringify(allParts);
        masterRemarksInput.value = JSON.stringify(allRemarks);

        // Optional backup: append as array inputs
        damageContainer.innerHTML = '';
        for (let i = 0; i < allParts.length; i++) {
            const partInput = document.createElement('input');
            partInput.type = 'hidden';
            partInput.name = 'damageParts[]';
            partInput.value = allParts[i];
            damageContainer.appendChild(partInput);

            const remarkInput = document.createElement('input');
            remarkInput.type = 'hidden';
            remarkInput.name = 'damageRemarks[]';
            remarkInput.value = allRemarks[i];
            damageContainer.appendChild(remarkInput);
        }

        // Close modal
        bootstrap.Modal.getInstance(uploadModal).hide();
    });
});
</script>




<script src="https://cdn.jsdelivr.net/npm/fabric@4.6.0/dist/fabric.min.js"></script>

<script>
    $(function () {
        if (typeof fabric === 'undefined') {
            console.error('Fabric.js failed to load.');
            return;
        }

        var board = new fabric.Canvas('board');

        fabric.Image.fromURL("{{ asset('assets/images/pickup.jpg') }}", function(img) {
            img.scaleX = board.width / img.width;
            img.scaleY = board.height / img.height;

            board.setBackgroundImage(img, board.renderAll.bind(board));
        });

        $('#addCircles').click(function () {
            board.add(new fabric.Circle({ radius: 10, fill: '#000', left: 22, top: 10 }));
            updateObjects();
        });

        $('#addEquals').click(function () {
            board.add(new fabric.Text('=', { left: 20, top: 35, fill: '#000' }));
            updateObjects();
        });

        $('#addRects').click(function () {
            board.add(new fabric.Rect({ top: 75, left: 22, width: 18, height: 18, fill: '#000' }));
            updateObjects();
        });

        $('#addTriangles').click(function () {
            board.add(new fabric.Triangle({ top: 115, left: 22, width: 18, height: 18, fill: '#000' }));
            updateObjects();
        });

        $('#removeShape').click(function () {
            board.remove(board.getActiveObject());
            updateObjects();
        });

        function updateObjects() {
            var bg = board.backgroundImage;
            if (bg) {
                bg.selectable = false;
                bg.evented = false;
            }
        }

        $('form').on('submit', function (e) {
            var canvasData = board.toDataURL({ format: 'jpeg', quality: 0.8 });
            $('#hidden_datas').val(canvasData);
        });

    });
</script>
@endpush