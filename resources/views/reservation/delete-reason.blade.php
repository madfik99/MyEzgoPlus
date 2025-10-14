@extends('layouts.main')

@section('page-title')
    {{ __('Delete Reservation') }}
@endsection

@section('page-breadcrumb')
    {{ __('Delete Reservation') }}
@endsection

@section('content')
<div class="container">
  <h3>Delete Agreement – {{ $agreementNo ?? '-' }}</h3>

  <div class="card mt-3">
    <div class="card-body">
      <div class="mb-3">
        <strong>Booking ID:</strong> {{ $booking->id }}<br>
        <strong>Status:</strong> {{ $booking->available }}
      </div>

      <form action="{{ route('delete.request.store', $booking) }}" method="POST">
        @csrf
        <div class="mb-3">
          <label for="reason" class="form-label">Reason for deleting this agreement</label>
          <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" rows="5" placeholder="Type your reason here..." required>{{ old('reason') }}</textarea>
          @error('reason')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="d-flex gap-2">
          <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-danger">Confirm</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
