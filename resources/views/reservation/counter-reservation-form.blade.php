@extends('layouts.main')

@section('page-title')
    {{ __('Reservation Counter') }}
@endsection

@section('page-breadcrumb')
    {{ __('Reservation Counter') }}
@endsection

@push('css')
    @include('layouts.includes.datatable-css')
@endpush

@section('content')
<div class="card p-4">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
    @endif

    <div class="x_panel">
        <div class="x_title card-header">
            <h4 class="card-title">Agreement Details</h4>
        </div>
        <br><br>
        <div class="x_content">
            <form method="POST" action="{{ route('reservation.submit') }}" id="reservationForm">
                @csrf

                {{-- Minimum Rental Time --}}
                <div class="form-group d-flex justify-content-center mb-4 mt-3">
                    <label class="me-3 fw-semibold" style="width:200px;">Minimum Rental Time:</label>
                    <input type="text" class="form-control bg-light text-center" value="{{ $min_rental_time }}" style="max-width:300px;" disabled>
                </div>

                {{-- Pickup & Return Date --}}
                <div class="form-group mb-4">
                    <div class="d-flex justify-content-center mb-3">
                        <label class="me-3 fw-semibold" style="width:200px;">Pickup:</label>
                        <input type="text" class="form-control text-center me-2" value="{{ $search_pickup_date }}" style="max-width:150px;" disabled>
                        <input type="text" class="form-control text-center" value="{{ $search_pickup_time }}" style="max-width:120px;" disabled>
                    </div>
                    <div class="d-flex justify-content-center">
                        <label class="me-3 fw-semibold" style="width:200px;">Return:</label>
                        <input type="text" class="form-control text-center me-2" value="{{ $search_return_date }}" style="max-width:150px;" disabled>
                        <input type="text" class="form-control text-center" value="{{ $search_return_time }}" style="max-width:120px;" disabled>
                    </div>
                </div>

                {{-- SCDW --}}
                <div class="form-group mt-4">
                    <label class="d-block">
                        <a href="#" id="openSCDWModal" style="cursor:pointer;">
                            SCDW <abbr title="Click for more info"><i class="fa fa-info-circle"></i></abbr>
                        </a>
                    </label>
                    <div class="row">
                        @foreach($cdws as $index => $cdw)
                        <div class="col-md-3 col-sm-4 col-6 text-center mb-3">
                            <label class="d-block p-2" style="cursor:pointer;">
                                <img src="{{ asset('images/cdw/' . $cdw->rate->image) }}" class="img-fluid rounded mb-2" style="max-height:120px;">
                                <div class="fw-semibold">{{ $cdw->rate->rate }}%</div>
                                <input type="radio" name="cdw_id" value="{{ $cdw->id }}" class="form-check-input" required>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Modal --}}
                <div class="modal fade" id="scdwInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header d-flex justify-content-center position-relative">
                                <h3 class="modal-title text-center" id="myModalLabel2">
                                    CDW Classes for {{ $vehicle->class->class_name ?? '' }}
                                </h3>
                                <button type="button" class="btn-close position-absolute end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <center>
                                    <h4>Collision Damage Waiver (CDW) & Super Collision Damage Waiver (SCDW)</h4>
                                    <p>
                                        Bad luck on the road can happen when we least expect it. On some occasions, it can even lead to costly repair charges.
                                        <br><br>CDW is provided to Member to reduce the excess liability to minimal. This means that in the event of an accident, MYEZGO members only have to make a payment of a maximum excess amount limit, based on vehicle category or car types.
                                        <br><br>Super Collision Damage Waiver (SCDW) accident protection helps members save even more in the event of an accident, by reducing the maximum payable limit for accident repair charges by a significant amount.
                                        <br><br>CDW and SDW coverage does not cover missing items or accessories or damages on the under carriage and upper carriage of our vehicle, caused by negligent driving as stipulated in our terms and conditions of our rental agreement. For example, should Member intentionally or unintentionally causes damage to MYEZGO.
                                        <br><br><br>
                                        <div class="table-responsive">
                                            <table class="table text-center">
                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <font color="red">The price for maximum charges may vary for different vehicle classes.</font>
                                                        </th>
                                                    </tr>
                                                    @foreach($cdws as $index => $cdw)
                                                    <tr>
                                                        <td>
                                                            @if($index == 0)
                                                                <font style="color:#CD7F32;">CDW {{ $cdw->rate->name ?? '' }}</font>
                                                            @elseif($index == 1)
                                                                <font style="color:silver;">SCDW {{ $cdw->rate->name ?? '' }}</font>
                                                            @elseif($index == 2)
                                                                <font style="color:#B8870F;">SCDW {{ $cdw->rate->name ?? '' }}</font>
                                                            @elseif($index == 3)
                                                                <font style="color:#DC351C;">SCDW {{ $cdw->rate->name ?? '' }}</font>
                                                            @else
                                                                <font>{{ $cdw->rate->name ?? '' }}</font>
                                                            @endif
                                                            <br>
                                                            {{ $cdw->rate->rate ?? '' }}% from total sale. Maximum damage charges = RM{{ number_format($cdw->max_value,2) }}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </p>
                                </center>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hidden Inputs --}}
                <input type="hidden" name="nric" value="{{ $nric }}">
                <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                <input type="hidden" name="search_pickup_date" value="{{ $search_pickup_date }}">
                <input type="hidden" name="search_pickup_time" value="{{ $search_pickup_time }}">
                <input type="hidden" name="search_return_date" value="{{ $search_return_date }}">
                <input type="hidden" name="search_return_time" value="{{ $search_return_time }}">
                <input type="hidden" name="pickup_location_id" value="{{ $search_pickup_location }}">
                <input type="hidden" name="return_location_id" value="{{ $search_return_location }}">
                <input type="hidden" name="coupon" value="{{ $coupon ?? '' }}">

                {{-- Checklist --}}
                <div class="card shadow-sm mt-4">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Checklist</h4>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-striped">
                            <thead class="table-success">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Image</th>
                                    <th>Description</th>
                                    <th>Charge Details</th>
                                    <th class="text-center">Select</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($options as $index => $option)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td><img src="{{ asset('assets/img/rental_option/' . $option->pic) }}" style="height:70px;"></td>
                                    <td>{{ $option->description }}</td>
                                    <td>
                                        @if($option->amount_type == 'RM')
                                            RM{{ number_format($option->amount,2) }} | {{ $option->calculation }}
                                        @else
                                            {{ $option->amount }}% | {{ $option->calculation }}
                                        @endif
                                        @if($option->missing_cond !== '0')
                                            <div class="text-danger small">(If Missing: RM{{ $option->missing_cond }})</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" name="checklist_options[]" value="{{ $option->id }}" class="form-check-input">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="form-group text-center mt-2">
                    <button type="submit" class="btn btn-success">Submit Reservation</button>
                </div>
                
            </form>
        </div>
    </div>
</div>

{{-- Modal Trigger --}}
<script>
    $('#openSCDWModal').click(function(e){
        e.preventDefault();
        $('#scdwInfoModal').modal('show');
    });
</script>

@endsection

@push('scripts')
    @include('layouts.includes.datatable-js')
@endpush
