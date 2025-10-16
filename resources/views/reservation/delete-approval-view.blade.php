@extends('layouts.main')

@section('page-title', __('Delete Approval'))
@section('page-breadcrumb', __('Delete Approval'))

@section('content')
<div class="container">

  <div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5>{{ __('Delete Approval View') }}</h5>

      <div class="d-flex gap-2">
        {{-- Cancel --}}
        <a href="{{ route('delete.request.index') }}" class="btn btn-secondary">
          &nbsp;Cancel
        </a>
        {{-- Confirm --}}
        <form action="{{ route('delete.request.confirm', $booking) }}" method="POST" onsubmit="return confirm('Confirm delete?');">
          @csrf
          <button class="btn btn-success" type="submit">
            <i class="fa fa-check"></i>&nbsp;Confirm Delete
          </button>
        </form>
        {{-- Decline --}}
        <form action="{{ route('delete.request.decline', $booking) }}" method="POST" onsubmit="return confirm('Decline delete request?');">
          @csrf
          <button class="btn btn-primary" type="submit">
            <i class="fa fa-window-close"></i>&nbsp;Decline
          </button>
        </form>
      </div>
    </div>

    <div class="card-body">
      {{-- Company / Reference / Reason --}}
      <div class="row mb-3">
        <div class="col-md-4 text-center">
          <img width="240" src="{{ $company['logo'] }}" alt="logo">
        </div>
        <div class="col-md-4">
          <h4>{{ $company['name'] }}</h4>
          <p>{{ $company['website'] }}</p>
          <p>{{ $company['address'] }}</p>
          <p>{{ $company['phone'] }}</p>
          <p>{{ $company['registration'] }}</p>

          <div class="mt-3">
            <div>Reference No.</div>
            <input class="form-control" type="text" value="{{ $booking->agreement_no }}" disabled>
            <div class="mt-2"><b>Reason delete:</b> <i><span class="text-danger">{{ $booking->reason }}</span></i></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Customer Information</h5>
    </div>
    <div class="card-body">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name</label>
            <input class="form-control" value="{{ trim(($booking->customer->firstname ?? '').' '.($booking->customer->lastname ?? '')) }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">NRIC</label>
            <input class="form-control" value="{{ $booking->customer->nric_no ?? '' }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Driving License</label>
            <input class="form-control" value="{{ $booking->customer->license_no ?? '' }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Address</label>
            <input class="form-control" value="{{ $booking->customer->address ?? '' }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone No</label>
            <input class="form-control" value="{{ $booking->customer->phone_no ?? '' }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" value="{{ $booking->customer->email ?? '' }}" disabled>
          </div>
        </div>
    </div>
  </div>

   <div class="card mt-3">
    <div class="card-header">
      <h5>Payment Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Amount</label>
            <input class="form-control" value="{{ $booking->sub_total }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Deposit</label>
            <input class="form-control" value="{{ $booking->refund_dep }}" disabled>
          </div>
        </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Vehicle Information</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Model</label>
            <input class="form-control" value="{{ trim(($booking->vehicle->make ?? '').' '.($booking->vehicle->model ?? '')) }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Register Number</label>
            <input class="form-control" value="{{ $booking->vehicle->reg_no ?? '' }}" disabled>
          </div>

          <div class="col-md-6">
            <label class="form-label">Pickup Date</label>
            <input class="form-control" value="{{ $booking->pickup_date ? \Illuminate\Support\Carbon::parse($booking->pickup_date)->format('d/m/Y') : '' }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Pickup Time</label>
            <input class="form-control" value="{{ $booking->pickup_time }}" disabled>
          </div>

          <div class="col-md-6">
            <label class="form-label">Pickup Location</label>
            <input class="form-control" value="{{ $booking->pickup_location == 4 ? 'Port Dickson' : ($booking->pickup_location == 5 ? 'Seremban' : $booking->pickup_location) }}" disabled>
          </div>

          <div class="col-md-6">
            <label class="form-label">Return Date</label>
            <input class="form-control" value="{{ $booking->return_date ? \Illuminate\Support\Carbon::parse($booking->return_date)->format('d/m/Y') : '' }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Return Time</label>
            <input class="form-control" value="{{ $booking->return_time }}" disabled>
          </div>
          <div class="col-md-6">
            <label class="form-label">Return Location</label>
            <input class="form-control" value="{{ $booking->return_location == 4 ? 'Port Dickson' : ($booking->return_location == 5 ? 'Seremban' : $booking->return_location) }}" disabled>
          </div>
        </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Vehicle Checklist</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Checklist</th>
                <th>Pickup</th>
                <th>Return</th>
              </tr>
            </thead>
            <tbody>
              @php $c = $booking->checklist; @endphp
              <tr><th>Start Engine</th><td>{{ $c->car_out_start_engine ?? '' }}</td><td>{{ $c->car_in_start_engine ?? '' }}</td></tr>
              <tr><th>No Alarm</th><td>{{ $c->car_out_no_alarm ?? '' }}</td><td>{{ $c->car_in_no_alarm ?? '' }}</td></tr>
              <tr><th>Wiper</th><td>{{ $c->car_out_wiper ?? '' }}</td><td>{{ $c->car_in_wiper ?? '' }}</td></tr>
              <tr><th>Air Conditioner</th><td>{{ $c->car_out_air_conditioner ?? '' }}</td><td>{{ $c->car_in_air_conditioner ?? '' }}</td></tr>
              <tr><th>Radio</th><td>{{ $c->car_out_radio ?? '' }}</td><td>{{ $c->car_in_radio ?? '' }}</td></tr>
              <tr><th>Power Window</th><td>{{ $c->car_out_power_window ?? '' }}</td><td>{{ $c->car_in_power_window ?? '' }}</td></tr>
              <tr><th>Perfumed</th><td>{{ $c->car_out_perfume ?? '' }}</td><td>{{ $c->car_in_perfume ?? '' }}</td></tr>
              <tr><th>Carpet</th><td>{{ $c->car_out_carpet ?? '' }}</td><td>{{ $c->car_in_carpet ?? '' }}</td></tr>
              <tr><th>Lamp</th><td>{{ $c->car_out_lamp ?? '' }}</td><td>{{ $c->car_in_lamp ?? '' }}</td></tr>
              <tr><th>Engine Condition</th><td>{{ $c->car_out_engine_condition ?? '' }}</td><td>{{ $c->car_in_engine_condition ?? '' }}</td></tr>
              <tr><th>Tyres Condition</th><td>{{ $c->car_out_tyres_condition ?? '' }}</td><td>{{ $c->car_in_tyres_condition ?? '' }}</td></tr>
              <tr><th>Jack</th><td>{{ $c->car_out_jack ?? '' }}</td><td>{{ $c->car_in_jack ?? '' }}</td></tr>
              <tr><th>Tools</th><td>{{ $c->car_out_tools ?? '' }}</td><td>{{ $c->car_in_tools ?? '' }}</td></tr>
              <tr><th>Signage</th><td>{{ $c->car_out_signage ?? '' }}</td><td>{{ $c->car_in_signage ?? '' }}</td></tr>
              <tr><th>Tyre Spare</th><td>{{ $c->car_out_tyre_spare ?? '' }}</td><td>{{ $c->car_in_tyre_spare ?? '' }}</td></tr>
              <tr><th>Sticker P</th><td>{{ $c->car_out_sticker_p ?? '' }}</td><td>{{ $c->car_in_sticker_p ?? '' }}</td></tr>
              <tr><th>USB Charger</th><td>{{ $c->car_out_usb_charger ?? '' }}</td><td>{{ $c->car_in_usb_charger ?? '' }}</td></tr>
              <tr><th>Touch N Go</th><td>{{ $c->car_out_touch_n_go ?? '' }}</td><td>{{ $c->car_in_touch_n_go ?? '' }}</td></tr>
              <tr><th>Smart Tag</th><td>{{ $c->car_out_smart_tag ?? '' }}</td><td>{{ $c->car_in_smart_tag ?? '' }}</td></tr>
              <tr><th>Child Seat</th><td>{{ $c->car_out_child_seat ?? '' }}</td><td>{{ $c->car_in_child_seat ?? '' }}</td></tr>
              <tr><th>GPS</th><td>{{ $c->car_out_gps ?? '' }}</td><td>{{ $c->car_in_gps ?? '' }}</td></tr>
            </tbody>
          </table>
        </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
        <h5>Car Image State</h5>
    </div>
    <div class="card-body">

        {{-- Car Image State (filtered: interior/exterior only, last 6) --}}
        <div class="mb-4">
            <div class="table-responsive">
                @php
                    $posLabel = [
                        'pickup_interior' => 'Pickup – Interior',
                        'return_interior' => 'Return – Interior',
                        'pickup_exterior' => 'Pickup – Exterior',
                        'return_exterior' => 'Return – Exterior',
                    ];
                @endphp

                @if (($booking->uploads ?? collect())->isEmpty())
                  <div class="text-center text-muted p-3">No available images</div>
                @else
                  <table class="table align-middle">
                    <tbody><tr>
                      @foreach ($booking->uploads as $u)
                        @php
                          $path = $u->file_name ?? $u->path ?? null;
                          $label = $posLabel[$u->position] ?? 'Car Image';
                        @endphp
                        <td class="text-center">
                          <img src="{{ Storage::url($path) }}?nocache={{ time() }}"
                              alt="{{ $label }}"
                              style="height:190px; width:280px; object-fit:cover; border-radius:.5rem;">
                          <div class="small text-muted mt-1">{{ $label }}</div>
                        </td>
                      @endforeach
                    </tr></tbody>
                  </table>
                @endif

            </div>
        </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Car Condition</h5>
    </div>
    <div class="card-body">

      {{-- Car Condition --}}
      <div class="mb-4">
        
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Item</th><th>Remark (pickup)</th><th>Remark (return)</th></tr></thead>
            <tbody>
              <tr><th>Car Seat Condition</th><td>{{ $c->car_out_seat_condition ?? '' }}</td><td>{{ $c->car_in_seat_condition ?? '' }}</td></tr>
              <tr><th>Cleanliness</th><td>{{ $c->car_out_cleanliness ?? '' }}</td><td>{{ $c->car_in_cleanliness ?? '' }}</td></tr>
              <tr><th>Fuel Level</th><td>{{ $c->car_out_fuel_level ?? '' }}</td><td>{{ $c->car_in_fuel_level ?? '' }}</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Remark: | = Scratch | O Broken | △ Dent | □ Missing</h5>
    </div>
    <div class="card-body">

      {{-- Car Condition Pic (overlay) --}}
      <div class="mb-4">
          <div class="row g-3">
              <div class="col-md-6">
                  <label class="form-label">Pickup</label>
                  <div class="card" style="width:100%; max-width:500px;">
                      <img src="{{ !empty($c->car_out_image) ? asset('storage/'.$c->car_out_image) : asset('images/pickup.jpg') }}?nocache={{ time() }}"
                          alt="Pickup Condition"
                          class="img-fluid"
                          style="object-fit:contain; max-height:250px; margin:auto;">
                  </div>
              </div>

              <div class="col-md-6">
                  <label class="form-label">Return</label>
                  <div class="card" style="width:100%; max-width:500px;">
                      <img src="{{ !empty($c->car_in_image) ? asset('storage/'.$c->car_in_image) : asset('images/pickup.jpg') }}?nocache={{ time() }}"
                          alt="Return Condition"
                          class="img-fluid"
                          style="object-fit:contain; max-height:250px; margin:auto;">
                  </div>
              </div>
          </div>
      </div>

    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Extend Information</h5>
    </div>
    <div class="card-body">

      {{-- Extend Information --}}
      <div class="mb-4">
        
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Extend Date</th>
                <th>Payment Status</th>
                <th>Payment Type</th>
                <th>Payment Date</th>
                <th>Payment Price</th>
              </tr>
            </thead>
            <tbody>
              @forelse (($booking->extensions ?? collect()) as $idx => $ex)
                <tr>
                  <th scope="row">{{ $idx + 1 }}</th>
                  <td>
                    {{ $ex->extend_from_date ? \Illuminate\Support\Carbon::parse($ex->extend_from_date)->format('d/m/Y') : '' }}
                    @ {{ \Illuminate\Support\Str::of($ex->extend_from_time)->limit(5, '') }}
                    -
                    {{ $ex->extend_to_date ? \Illuminate\Support\Carbon::parse($ex->extend_to_date)->format('d/m/Y') : '' }}
                    @ {{ \Illuminate\Support\Str::of($ex->extend_to_time)->limit(5, '') }}
                  </td>
                  <td>{{ $ex->payment_status }}</td>
                  <td>{{ $ex->payment_type }}</td>
                  <td>{{ $ex->c_date ? \Illuminate\Support\Carbon::parse($ex->c_date)->format('d/m/Y') : '' }}</td>
                  <td>RM {{ $ex->price }}</td>
                </tr>
              @empty
                <tr><td colspan="6">No records found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h5>Receipt</h5>
    </div>
    <div class="card-body">

      {{-- Receipt --}}
      <div class="mb-4">
        
        <div class="table-responsive">
        <table class="table table-bordered" style="text-align: center">
          <thead><tr><th>#</th><th>Pay to (Name)</th><th colspan="4">Return Date @ Time</th></tr></thead>
          <tbody>
            <tr>
              <td></td>
              <td>{{ trim(($booking->customer->firstname ?? '').' '.($booking->customer->lastname ?? '')) }}</td>
              <td colspan="4">
                {{ $booking->return_date ? \Illuminate\Support\Carbon::parse($booking->return_date)->format('d/m/Y') : '' }}
                {{ $booking->return_time }}
              </td>
            </tr>
          </tbody>

          <thead><tr><th width="3%">#</th><th>Payment To Customer</th><th>Details</th><th>Payment Status</th><th>Price</th></tr></thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Deposit</td>
              <td>Pay Deposit To Customer</td>
              <td>{{ $booking->refund_dep_payment ?? '' }}</td>
              <td>{{ 'RM '.$booking->refund_dep }}</td>
            </tr>
            <tr>
              <td>2</td>
              <td>{{ !empty($booking->other_details) ? 'Others' : '' }}</td>
              <td>{{ $booking->other_details ?? '' }}</td>
              <td>{{ $booking->other_details_payment_type ?? '' }}</td>
              <td>{{ !empty($booking->other_details_price) ? 'RM '.$booking->other_details_price : '' }}</td>
            </tr>
            <tr>
              <td></td><td></td><td></td><td>Total</td>
              <td>
                @php
                  $total_receipt = floatval($booking->refund_dep) + floatval($booking->other_details_price ?? 0);
                @endphp
                RM {{ number_format($total_receipt, 2) }}
              </td>
            </tr>
          </tbody>

          <thead><tr><th>#</th><th>Payment From Customer</th><th>Details</th><th>Payment Type</th><th>Price</th></tr></thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Outstanding Extend</td>
              <td>{{ $booking->outstanding_extend ?? '' }}</td>
              <td>{{ $booking->outstanding_extend_type_of_payment ?? '' }}</td>
              <td>{{ 'RM '.($booking->outstanding_extend_cost ?? 0) }}</td>
            </tr>
            <tr>
              <td>2</td>
              <td>Charges for Damages</td>
              <td>{{ $booking->damage_charges_details ?? '' }}</td>
              <td>{{ $booking->damage_charges_payment_type ?? '' }}</td>
              <td>{{ 'RM '.($booking->damage_charges ?? 0) }}</td>
            </tr>
            <tr>
              <td>3</td>
              <td>Charges items missing items</td>
              <td>{{ $booking->missing_items_charges_details ?? '' }}</td>
              <td>{{ $booking->missing_items_charges_payment_type ?? '' }}</td>
              <td>{{ 'RM '.($booking->missing_items_charges ?? 0) }}</td>
            </tr>
            <tr>
              <td>4</td>
              <td>Additional Cost</td>
              <td>{{ $booking->additional_cost_details ?? '' }}</td>
              <td>{{ $booking->additional_cost_payment_type ?? '' }}</td>
              <td>{{ 'RM '.($booking->additional_cost ?? 0) }}</td>
            </tr>
            <tr>
              <td></td><td></td><td></td><td>Total</td>
              <td>
                @php
                  $total_customer_payment = floatval($booking->outstanding_extend_cost ?? 0)
                    + floatval($booking->damage_charges ?? 0)
                    + floatval($booking->missing_items_charges ?? 0)
                    + floatval($booking->additional_cost ?? 0);
                @endphp
                RM {{ number_format($total_customer_payment, 2) }}
              </td>
            </tr>
          </tbody>

          <thead><tr><th colspan="5">Prepared By</th></tr></thead>
          <tbody><tr><td colspan="5">{{ $booking->car_in_checkby ?? '' }}</td></tr></tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
  
</div>
@endsection
