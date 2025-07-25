
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

    <div class="card mb-4">
        <div class="card-header">
            <h4>Vehicle Pickup - Step 1</h4>
            <small class="text-danger">Please note: Once pickup has been made, this form cannot be changed.</small>
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

            <form method="POST" action="" enctype="multipart/form-data">
                @csrf

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
                        <input type="text" class="form-control" value="{{ number_format($booking->balance, 2) }}" disabled>
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
                    <label class="col-sm-3 col-form-label">Payment Type</label>
                    <div class="col-sm-9">
                        <select name="payment_type" class="form-control" required>
                            <option value="">-- Please Select --</option>
                            <option value="Cash">Cash</option>
                            <option value="Online">Online</option>
                            <option value="Card">Card</option>
                            <option value="QRPay">QRPay</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Payment Receipt</label>
                    <div class="col-sm-9">
                        <input type="file" name="pickup_receipt" class="form-control" accept="image/*" required>
                    </div>
                </div>

                <h5 class="mt-4">Interior Checklist</h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="start_engine" value="Y" id="start_engine">
                    <label class="form-check-label" for="start_engine">Start Engine</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="engine_condition" value="Y" id="engine_condition">
                    <label class="form-check-label" for="engine_condition">Engine Condition</label>
                </div>

                <div class="form-group mt-3">
                    <label>Fuel Level</label>
                    <select name="fuel_level" class="form-control" required>
                        <option value="">-- Select --</option>
                        @for($i = 0; $i <= 6; $i++)
                            <option value="{{ $i }}">{{ $i == 0 ? 'Empty' : $i . ' Bar' }}</option>
                        @endfor
                    </select>
                </div>

                <div class="form-group">
                    <label>Mileage</label>
                    <input type="number" class="form-control" name="mileage" required>
                </div>

                <div class="form-group mt-4">
                    <label>Interior Images</label>
                    <input type="file" name="interior0" class="form-control mb-2" required>
                    <input type="file" name="interior1" class="form-control mb-2" required>
                    <input type="file" name="interior2" class="form-control mb-2" required>
                    <input type="file" name="interior3" class="form-control mb-2">
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary">Submit & Proceed</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
