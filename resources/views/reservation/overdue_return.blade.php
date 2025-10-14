@extends('layouts.main')

@section('page-title')
    {{ $overdueType === 'extend' ? __('Return need Extend') : __('Overdue Return') }}
@endsection

@section('page-breadcrumb')
    {{ $overdueType === 'extend' ? __('Return need Extend') : __('Overdue Return') }}
@endsection

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== Summary cards ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Base Rate</strong></div>
                <div class="card-body">
                    <input class="form-control" value="{{ $baseRateText }}" disabled>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Pickup Date &amp; Time</strong></div>
                <div class="card-body">
                    {{-- Shows "last planned return before extension" --}}
                    <input class="form-control" value="{{ $pickupDateTimeText }}" disabled>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Return Date &amp; Time</strong>
                    @if($exceed)
                        <abbr title="{{ $note }}"><i class="fa fa-info-circle ms-1" style="color:red"></i></abbr>
                    @elseif($extend)
                        <abbr title="{{ $note }}"><i class="fa fa-info-circle ms-1" style="color:green"></i></abbr>
                    @endif
                </div>
                <div class="card-body">
                    {{-- Shows NOW --}}
                    <input class="form-control" value="{{ $returnDateTimeText }}" disabled>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Discount ===== --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Discount</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('overdue.return', $booking->id) }}" class="form-horizontal">
                @csrf
                <input type="hidden" name="form_action" value="redeem">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Overdue Total (MYR)</label>
                        <input type="text" class="form-control" value="{{ number_format($subtotal, 2) }}" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discount (MYR)</label>
                        <input type="text" class="form-control" value="{{ number_format($discountAmount, 2) }}" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discount Code</label>
                        <input type="text"
                               class="form-control"
                               name="discount_code"
                               id="discount_code"
                               value="{{ old('discount_code', $discountCode) }}">
                    </div>
                </div>

                <div class="text-center mt-4">
                    {{-- carry fields (use the RESOLVED values) --}}
                    <input type="hidden" name="pickup_date"  value="{{ $pickupDate }}">
                    <input type="hidden" name="pickup_time"  value="{{ $pickupTime }}">
                    <input type="hidden" name="return_date"  value="{{ $returnDate }}">  {{-- NOW --}}
                    <input type="hidden" name="return_time"  value="{{ $returnTime }}">  {{-- NOW --}}
                    <input type="hidden" name="subtotal"     value="{{ $subtotal }}">
                    <input type="hidden" name="est_total"    value="{{ $estTotal }}">
                    <input type="hidden" name="discount_amount" value="{{ $discountAmount }}">

                    <button type="submit" class="btn btn-primary">Validate Discount</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Payment Details ===== --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Payment Details</h5>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ route('overdue.proceedOutstanding', $booking->id) }}"
                enctype="multipart/form-data"
                class="form-horizontal">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Grand Total (MYR)</label>
                        <input type="text"
                            class="form-control"
                            value="{{ number_format($estTotal, 2) }}"
                            disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Amount (MYR)</label>
                        <input type="number" step="0.01" class="form-control" name="payment">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Receipt (Image)</label>
                        <input type="file" accept="image/png, image/jpeg" class="form-control" name="payment_receipt" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="">-- Please select --</option>
                            <option value="Paid">Paid</option>
                            <option value="Collect">Need to Collect</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" class="form-control" required>
                            <option value="">-- Please select --</option>
                            <option value="Collect">Need to Collect</option>
                            <option value="Cash">Cash</option>
                            <option value="Online">Online Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="QRPay">QRPay</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-4">
                    {{-- carry forward (resolved values) --}}
                    <input type="hidden" name="extend_from_date" value="{{ $pickupDate }}">
                    <input type="hidden" name="extend_from_time" value="{{ $pickupTime }}">
                    <input type="hidden" name="extend_to_date"   value="{{ $returnDate }}">
                    <input type="hidden" name="extend_to_time"   value="{{ $returnTime }}">

                    <input type="hidden" name="est_total"       value="{{ $estTotal }}">
                    <input type="hidden" name="discount_code"   value="{{ $discountCode }}">

                    {{-- optional "type_of_payment" note (falls back to payment_type if omitted) --}}
                    {{-- <input type="hidden" name="type_of_payment" value="Online Transfer"> --}}

                    <a href="{{ route('reservation.view', $booking->id) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">Proceed Outstanding</button>
                </div>
            </form>
        </div>
    </div>


</div>

@push('scripts')
<script>
(function() {
  // Session auto-refresh every 30 minutes (like legacy)
  let last = Date.now();
  document.body.addEventListener('mousemove', ()=>last=Date.now());
  document.body.addEventListener('keypress', ()=>last=Date.now());
  setInterval(function(){
      if (Date.now() - last >= 1800000) location.reload();
  }, 1800000);
})();
</script>
@endpush
@endsection
