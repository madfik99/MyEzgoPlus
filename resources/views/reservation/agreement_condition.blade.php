@extends('layouts.main')

@section('page-title')
    {{ __('Agreement') }}
@endsection

@section('page-breadcrumb')
    {{ __('Agreement') }}
@endsection

@section('content')
<div class="container">

    {{-- @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif --}}

    {{-- Vehicle Pickup Inspection Card --}}
    <div class="card mb-4 mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>{{ $language == 'english' ? 'Vehicle Pickup Inspection' : 'Pemeriksaan Pengambilan Kenderaan' }}</h4>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="card-body">
        <!-- Image Display -->
@php
    $imagePath = 'assets/img/customer/' . optional($nricSelfieImage)->file_name;
    $fullPath = public_path($imagePath);
@endphp

@if($nricSelfieImage && file_exists($fullPath))
    <div class="mb-3">
        <img src="{{ asset($imagePath) }}" 
             class="img-thumbnail" 
             style="max-width:300px; cursor:pointer;"
             id="nricImage"
             data-bs-toggle="modal" 
             data-bs-target="#imageModal">
    </div>
@else
    <p class="text-danger">Image not found.</p>
@endif

<!-- Modal HTML (Bootstrap 5) -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $language == 'english' ? 'NRIC Selfie Image' : 'Gambar Swafoto NRIC' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="{{ asset($imagePath) }}" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ $language == 'english' ? 'Close' : 'Tutup' }}
                </button>
            </div>
        </div>
    </div>
</div>


        {{-- Existing Customer Details --}}
        <p><strong>{{ $language == 'english' ? 'Customer Name' : 'Nama Pelanggan' }}:</strong> {{ $customer->firstname }} {{ $customer->lastname }}</p>
        <p><strong>{{ $language == 'english' ? 'NRIC No.' : 'No. NRIC' }}:</strong> {{ $customer->nric_no }}</p>
        <p><strong>{{ $language == 'english' ? 'Phone No.' : 'No. Telefon' }}:</strong> {{ $customer->phone_no }}</p>
        <p><strong>{{ $language == 'english' ? 'Vehicle' : 'Kenderaan' }}:</strong> {{ $vehicle->make }} {{ $vehicle->model }}</p>
        <p><strong>{{ $language == 'english' ? 'Pickup Date' : 'Tarikh Pengambilan' }}:</strong> {{ date('d M Y H:i A', strtotime($booking->pickup_date)) }}</p>
        <p><strong>{{ $language == 'english' ? 'Return Date' : 'Tarikh Pemulangan' }}:</strong> {{ date('d M Y H:i A', strtotime($booking->return_date)) }}</p>
    </div>
</div>


   {{-- Terms & Conditions Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <h4>{{ $language == 'english' ? 'Terms & Conditions' : 'Terma & Syarat' }}</h4>
        </div>
        <div class="card-body">
            @php
                $terms = [
                    [
                        'english' => 'The person who picks up the car is the same person documented above.',
                        'malay' => 'Individu yang mengambil kereta ini adalah individu sama seperti rekod di atas.'
                    ],
                    [
                        'english' => 'Renter must ensure they have filled in Car Pickup and Return forms thoroughly.',
                        'malay' => 'Penyewa harus memastikan mereka mengisi borang "Car Pickup" semasa pengambilan kenderaan dan borang "Car Return" semasa pemulangan kenderaan dengan teliti.'
                    ],
                    [
                        'english' => 'Fuel level during pickup and return must be at the same level. No refund or claim for extra fuel.',
                        'malay' => 'Aras minyak semasa pengambilan dan pemulangan kenderaan haruslah sama. Tiada tuntutan bayaran balik sekiranya ada lebihan minyak.'
                    ],
                    [
                        'english' => 'Cannot cross the country\'s border without company\'s permission.',
                        'malay' => 'Tidak boleh melepasi sempadan negara tanpa mendapat kebenaran dari syarikat.'
                    ],
                    [
                        'english' => 'Only renter and additional driver can drive this car.',
                        'malay' => 'Hanya penyewa dan pemandu tambahan dibenarkan memandu kenderaan ini.'
                    ],
                    [
                        'english' => 'Obey traffic regulation in Malaysia and any criminal activity or wrongdoing is not allowed.',
                        'malay' => 'Patuhi peraturan jalanraya di Malaysia dan sebarang kegiatan jenayah atau salah adalah tidak dibenarkan.'
                    ],
                    [
                        'english' => 'Rental for third parties is not allowed.',
                        'malay' => 'Sewaan untuk pihak ketiga adalah tidak dibenarkan.'
                    ],
                    [
                        'english' => 'For extend, renter must notify company and pay first before extend time.',
                        'malay' => 'Untuk pelanjutan sewaan, anda mesti memaklumkan kepada syarikat dan pembayaran haruslah dibuat terlebih dahulu sebelum masa lanjutan.'
                    ],
                    [
                        'english' => 'No refundable payment for early return and renter need to provide collateral item if fail to make payment.',
                        'malay' => 'Bayaran tidak dikembalikan untuk pulangan awal & jika tidak dapat melunaskan bayaran, penyewa perlu memberi barang cagaran.'
                    ],
                    [
                        'english' => 'No alcohol or drug usage or carrying pet inside car rental during rental period.',
                        'malay' => 'Penggunaan alkohol/dadah atau membawa haiwan peliharaan semasa sewaan adalah tidak dibenarkan.'
                    ],
                    [
                        'english' => 'First action when accident is direct reported to company immediately.',
                        'malay' => 'Jika kemalangan, segera laporkan kepada pihak syarikat.'
                    ],
                    [
                        'english' => 'Can\'t use any tow truck not from company.',
                        'malay' => 'Tidak boleh menggunakan lori tunda bukan dari pihak syarikat.'
                    ],
                    [
                        'english' => 'If accident the charge depends on company loss and the maximum charge is RM3000.',
                        'malay' => 'Jika kemalangan, caj bergantung kepada kerugian syarikat dan caj maksimum adalah RM3000.'
                    ],
                    [
                        'english' => 'Company has the right to inform Police when renter is suspected in doing criminal activity.',
                        'malay' => 'Pihak syarikat berhak memaklumkan kepada pihak Polis jika penyewa disyaki melakukan jenayah.'
                    ],
                    [
                        'english' => 'If breaching terms and condition, renter will be blacklisted (CTOS) with 10% service charge from outstanding payment, maximum penalty of RM3000 & law action will be taken including publishing renter details to website or social medias.',
                        'malay' => 'Pelanggaran terma dan syarat, penyewa akan disenarai hitam (CTOS) termasuk caj perkhidmatan 10%, penalti maksimum RM3000 & tindakan undang-undang termasuk menyiarkan maklumat penyewa di laman web atau media sosial.'
                    ],
                    [
                        'english' => 'I agree with the conditions above and enclosed with this agreement.',
                        'malay' => 'Saya bersetuju dengan syarat-syarat di atas serta syarat-syarat yang dilampirkan bersama dengan perjanjian ini.'
                    ],
                    [
                        'english' => 'I agree to the Terms & Conditions.',
                        'malay' => 'Saya setuju dengan Terma & Syarat.'
                    ],
                ];
            @endphp

            @foreach($terms as $index => $term)
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="term{{ $index }}" required>
                    <label class="form-check-label" for="term{{ $index }}">
                        {{ $language == 'english' ? $term['english'] : $term['malay'] }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>


    {{-- OTP Verification Card --}}
    <div class="card mb-4">
            <div class="card-header">
                <h4>{{ $language == 'english' ? 'OTP Verification' : 'Pengesahan OTP' }}</h4>
            </div>
            <div class="card-body">
                <p><strong>{{ $language == 'english' ? 'Phone No ' : 'No. Telefon' }}:</strong> {{ $customer->phone_no }}</p>

                {{-- OTP Request Buttons (matching your original PHP logic) --}}
                <div class="mb-3">
                    <a href="{{ route('otp.request', ['customer_id' => $customer->id, 'phone_no' => $customer->phone_no, 'type' => 'whatsapp']) }}"
                    target="_blank"
                    class="btn btn-success">
                        {{ $language == 'english' ? 'Send OTP via WhatsApp' : 'Hantar OTP melalui WhatsApp' }}
                        <i class="fa fa-whatsapp"></i>
                    </a>

                    <a href="{{ route('otp.request', ['customer_id' => $customer->id, 'phone_no' => $customer->phone_no, 'type' => 'message']) }}"
                    class="btn btn-primary">
                        {{ $language == 'english' ? 'Send OTP via SMS' : 'Hantar OTP melalui SMS' }}
                        <i class="fa fa-commenting-o"></i>
                    </a>
                </div>


                <form action="{{ route('agreement.verifyOtp', ['booking_id' => $booking->id]) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ $language == 'english' ? 'Enter OTP Code Here' : 'Masukkan Kod OTP di sini' }}</label>
                        <input type="text" name="otp_code"
                            class="form-control @error('otp_code') is-invalid @enderror"
                            maxlength="6" minlength="6" required>
                        @error('otp_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        {{ $language == 'english' ? 'Verify & Proceed' : 'Sahkan & Teruskan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection
