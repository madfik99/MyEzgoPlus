@extends('layouts.main')

@section('page-title')
    {{ __('Extend Editing') }}
@endsection

@section('page-breadcrumb')
    {{ __('Extend Editing') }}
@endsection

@section('content')
<div class="container">
    <h3>Extend Edit</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Extend Sale</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('extends.update', ['extend' => $extend->id]) }}" class="form-horizontal">
                @csrf
                @method('PUT')

                <input type="hidden" name="sale_id" value="{{ $sale_id }}">
                <input type="hidden" name="total" value="{{ number_format($total,2,'.','') }}">

                {{-- Edit Date? --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Edit Date?</label>
                    <div class="col-sm-6 d-flex align-items-center gap-3">
                        <input type="hidden" name="date_edit" id="date_edit" disabled value="true">
                        <label class="form-check form-switch m-0">
                            <input id="date_toggle" type="checkbox" class="form-check-input">
                        </label>
                    </div>
                </div>

                {{-- Pickup Date --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Pickup Date</label>
                    <div class="col-sm-6">
                        <input type="date" class="form-control" name="extend_from_date" id="extend_from_date"
                            value="{{ \Carbon\Carbon::parse($extend_from_date)->format('Y-m-d') }}" disabled>
                    </div>
                </div>

                {{-- Pickup Time --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Pickup Time</label>
                    <div class="col-sm-6">
                        @php $fromTime = \Carbon\Carbon::parse($extend_from_date)->format('H:i'); @endphp
                        <select name="extend_from_time" id="extend_from_time" class="form-control" disabled>
                            @foreach([
                              '08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
                              '12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30',
                              '16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30',
                              '20:00','20:30','21:00','21:30','22:00','22:30','23:00'
                            ] as $t)
                                <option value="{{ $t }}" {{ $fromTime === $t ? 'selected' : '' }}>{{ str_replace(':','.', $t) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Return Date --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Return Date</label>
                    <div class="col-sm-6">
                        <input type="date" class="form-control" name="extend_to_date" id="extend_to_date"
                            value="{{ \Carbon\Carbon::parse($extend_to_date)->format('Y-m-d') }}" disabled>
                    </div>
                </div>

                {{-- Return Time --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Return Time</label>
                    <div class="col-sm-6">
                        @php $toTime = \Carbon\Carbon::parse($extend_to_date)->format('H:i'); @endphp
                        <select name="extend_to_time" id="extend_to_time" class="form-control" disabled>
                            @foreach([
                              '08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
                              '12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30',
                              '16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30',
                              '20:00','20:30','21:00','21:30','22:00','22:30','23:00'
                            ] as $t)
                                <option value="{{ $t }}" {{ $toTime === $t ? 'selected' : '' }}>{{ str_replace(':','.', $t) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                {{-- Edit Sale? --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Edit Sale?</label>
                    <div class="col-sm-6 d-flex align-items-center gap-3">
                        <input type="hidden" name="sale_edit" id="sale_edit" value="true" disabled>
                        <input type="hidden" name="sale_extend_from_date" id="sale_extend_from_date" value="{{ $extend_from_date }}" disabled>
                        <input type="hidden" name="sale_extend_to_date"   id="sale_extend_to_date"   value="{{ $extend_to_date }}" disabled>
                        <label class="form-check form-switch m-0">
                            <input id="sale_toggle" type="checkbox" class="form-check-input">
                        </label>
                    </div>
                </div>

                {{-- Sale --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Sale</label>
                    <div class="col-sm-6">
                        <input type="number" step="0.01" class="form-control" name="sale" id="sale"
                               value="{{ number_format($total_sale,2,'.','') }}" disabled>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Payment</label>
                    <div class="col-sm-6">
                        <input type="number" step="0.01" class="form-control" name="payment_extend" id="payment_extend"
                               value="{{ number_format($payment,2,'.','') }}" disabled>
                    </div>
                </div>

                {{-- Payment Status --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Payment Status</label>
                    <div class="col-sm-6">
                        <select name="payment_status" id="payment_status" class="form-control" disabled>
                            <option value="">Please Select</option>
                            <option value="Paid"    {{ $payment_status === 'Paid' ? 'selected' : '' }}>Paid</option>
                            <option value="Collect" {{ $payment_status === 'Collect' ? 'selected' : '' }}>Need To Collect</option>
                        </select>
                    </div>
                </div>

                {{-- Payment Type --}}
                <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">Payment Type</label>
                    <div class="col-sm-6">
                        <select name="payment_type" id="payment_type" class="form-control" disabled>
                            <option value="">Please Select</option>
                            @foreach(['Collect','Unpaid','Cash','Online','Card','QRPay'] as $opt)
                                <option value="{{ $opt }}" {{ $payment_type === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="text-center mt-4">
  <a href="{{ route('reservation.view', $booking_id) }}" class="btn btn-secondary me-2">Back</a>
  <button class="btn btn-success" style="width:200px" type="submit">Submit</button>
</div>

            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('date_toggle').onchange = function() {
    const on = this.checked;
    ['extend_from_date','extend_from_time','extend_to_date','extend_to_time','date_edit']
      .forEach(id => document.getElementById(id).disabled = !on);
};

document.getElementById('sale_toggle').onchange = function() {
    const on = this.checked;
    ['sale','payment_extend','payment_status','payment_type','sale_edit','sale_extend_to_date','sale_extend_from_date']
      .forEach(id => document.getElementById(id).disabled = !on);
};
</script>
@endpush
@endsection
