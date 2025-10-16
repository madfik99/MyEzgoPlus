@extends('layouts.main')

@section('page-title')
    {{ __('Return Vehicle') }}
@endsection

@section('page-breadcrumb')
    {{ __('Return Vehicle') }}
@endsection

@section('content')
<div class="container mt-4">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    {{-- Start form --}}
    <form id="return-form" action="{{ route('return.update', $booking->id) }}" method="POST" enctype="multipart/form-data">
    @csrf


        <div class="card-body">

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Interior Checklist</h5>
                </div>
                <div class="card-body">
                    @php
                        $occ = auth()->user()->occupation ?? null;
                        $pickupChecked = function ($val) { return $val === 'Y'; };
                        $returnAttrs = function ($name, $inVal, $occ) {
                            $isChecked = old($name, $inVal) === 'Y';
                            $disabled = false;
                            if ($occ === 'Operation Staff') {
                                if ($inVal === 'Y') { $disabled = true; $isChecked = true; }
                                elseif ($inVal === 'X') { $disabled = true; $isChecked = false; }
                            }
                            return ['checked' => $isChecked, 'disabled' => $disabled];
                        };
                        $checks = [
                            ['label' => 'Start Engine',      'out' => 'car_out_start_engine',      'in' => 'car_in_start_engine',      'name' => 'start_engine'],
                            ['label' => 'Engine Condition',  'out' => 'car_out_engine_condition',  'in' => 'car_in_engine_condition',  'name' => 'engine_condition'],
                            ['label' => 'Test Gear',         'out' => 'car_out_test_gear',         'in' => 'car_in_test_gear',         'name' => 'test_gear'],
                            ['label' => 'No Alarm',          'out' => 'car_out_no_alarm',          'in' => 'car_in_no_alarm',          'name' => 'no_alarm'],
                            ['label' => 'Air Conditioner',   'out' => 'car_out_air_conditioner',   'in' => 'car_in_air_conditioner',   'name' => 'air_conditioner'],
                            ['label' => 'Radio',             'out' => 'car_out_radio',             'in' => 'car_in_radio',             'name' => 'radio'],
                            ['label' => 'Wiper',             'out' => 'car_out_wiper',             'in' => 'car_in_wiper',             'name' => 'wiper'],
                            ['label' => 'Window Condition',  'out' => 'car_out_window_condition',  'in' => 'car_in_window_condition',  'name' => 'window_condition'],
                            ['label' => 'Power Window',      'out' => 'car_out_power_window',      'in' => 'car_in_power_window',      'name' => 'power_window'],
                            ['label' => 'Perfume',           'out' => 'car_out_perfume',           'in' => 'car_in_perfume',           'name' => 'perfume'],
                            ['label' => 'Carpet (RM20/pcs)', 'out' => 'car_out_carpet',            'in' => 'car_in_carpet',            'name' => 'carpet'],
                            ['label' => 'Sticker P (RM5)',   'out' => 'car_out_sticker_p',         'in' => 'car_in_sticker_p',         'name' => 'sticker_p'],
                        ];
                        $fuelText = [0=>'Empty',1=>'1 Bar',2=>'2 Bar',3=>'3 Bar',4=>'4 Bar',5=>'5 Bar',6=>'6 Bar'];
                        $pick = fn($key) => optional($checklist)->{$key} ?? '';
                        $ret  = fn($key) => optional($checklist)->{$key} ?? '';
                    @endphp

                    <div class="row">
                        @foreach ($checks as $c)
                            @php
                                $outVal = $pick($c['out']);
                                $inVal  = $ret($c['in']);
                                $attrs  = $returnAttrs($c['name'], $inVal, $occ);
                            @endphp

                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header text-center">
                                        <label class="mb-0">{{ $c['label'] }}</label>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <label class="d-block">Pickup</label>
                                                <input type="checkbox" value="Y" @checked($pickupChecked($outVal)) disabled>
                                            </div>
                                            <div class="col-6">
                                                <label class="d-block" for="return_{{ $c['name'] }}">Return</label>
                                                <input id="return_{{ $c['name'] }}" name="{{ $c['name'] }}" type="checkbox" value="Y" @checked($attrs['checked']) @disabled($attrs['disabled'])>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Fuel Level --}}
                        <div class="col-md-6 col-sm-12 mb-3">
                            <div class="card h-100">
                                <div class="card-header text-center">
                                    <label class="mb-0">Fuel Level</label>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2 text-center"><label class="control-label">Pickup</label></div>
                                    <select class="form-control mb-3" disabled>
                                        @php $pickupFuel = optional($checklist)->car_out_fuel_level; @endphp
                                        <option>{{ $fuelText[$pickupFuel ?? 0] ?? 'Empty' }}</option>
                                    </select>

                                    <div class="mb-2 text-center"><label class="control-label">Return</label></div>
                                    @php $retFuel = old('fuel_level', optional($checklist)->car_in_fuel_level); @endphp
                                    <select name="fuel_level" class="form-control" required>
                                        <option value="">-- Please select --</option>
                                        @foreach ($fuelText as $val => $text)
                                            <option value="{{ $val }}" @selected((string)$retFuel === (string)$val)>{{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Mileage --}}
                        <div class="col-md-6 col-sm-12 mb-3">
                            <div class="card h-100">
                                <div class="card-header text-center">
                                    <label class="mb-0">Mileage</label>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2 text-center"><label class="control-label">Pickup</label></div>
                                    <input type="text" class="form-control mb-3" value="{{ optional($checklist)->car_out_mileage ?? '' }}" disabled>

                                    <div class="mb-2 text-center"><label class="control-label">Return</label></div>
                                    <input type="number" min="0" max="999999" step="1" class="form-control" name="mileage" value="{{ old('mileage', optional($checklist)->car_in_mileage) }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Car Interior Images</h5>
                </div>
                <div class="card-body">
                    @php
                        use App\Models\UploadData;
                        use Illuminate\Support\Facades\Storage;

                        $sections = [
                            ['no' => 1, 'title' => 'Dashboard & Windscreen',     'required' => true],
                            ['no' => 2, 'title' => 'First Row Seat',             'required' => false],
                            ['no' => 3, 'title' => 'Second Row Seat',            'required' => true],
                            ['no' => 4, 'title' => 'Third Row Seat (Optional)',  'required' => false],
                            ['no' => 5, 'title' => 'Fourth Row Seat (Optional)', 'required' => false],
                        ];
                    @endphp

                    <div class="row g-3">
                        @foreach ($sections as $sec)
                            @php
                                $before = UploadData::query()
                                    ->where('booking_trans_id', $booking->id)
                                    ->where('position', 'pickup_interior')
                                    ->where('no', $sec['no'])
                                    ->where('file_size', '!=', 0)
                                    ->orderByDesc('id')
                                    ->first();

                                $beforeUrl = $before && $before->file_name
                                    ? Storage::url($before->file_name) . '?nocache=' . time()
                                    : null;
                            @endphp

                            <div class="col-md-6 col-sm-12">
                                <div class="card h-100">
                                    <div class="card-header text-center">
                                        <h6 class="mb-0">{{ $sec['title'] }}</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="mb-2"><strong>Before:</strong></div>

                                        {{-- BEFORE box (fixed size); if image exists, make it clickable to modal --}}
                                        @if($beforeUrl)
                                            <div class="border bg-light d-flex align-items-center justify-content-center"
                                                 style="width: 90%; height: 290px; margin: 0 auto; cursor: zoom-in;"
                                                 data-img-src="{{ $beforeUrl }}"
                                                 data-img-title="{{ $sec['title'] }} — Before"> {{-- 🔧 For BS5 use data-bs-toggle / data-bs-target --}}
                                                <img src="{{ $beforeUrl }}" alt="Before {{ $sec['title'] }}"
                                                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            </div>
                                        @else
                                            <div class="border bg-light d-flex align-items-center justify-content-center"
                                                 style="width: 90%; height: 290px; margin: 0 auto;">
                                                <span class="text-muted">No image uploaded</span>
                                            </div>
                                        @endif

                                        <div class="mt-3 mb-2"><strong>After:</strong></div>

                                        {{-- AFTER preview box (fixed size). Starts as placeholder; shows preview when file selected --}}
                                        <div id="after-wrap-{{ $sec['no'] }}"
                                             class="border bg-light d-flex align-items-center justify-content-center mb-4"
                                             style="width: 90%; height: 290px; margin: 0 auto;">
                                            <img id="after-img-{{ $sec['no'] }}" src="" alt="After {{ $sec['title'] }}"
                                                 style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                                            <span class="text-muted noimg">No image uploaded</span>
                                        </div>

                                        <input id="interior-input-{{ $sec['no'] }}" type="file" name="interior[]"
                                               class="form-control" accept="image/*" @if($sec['required']) required @endif>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reusable image modal --}}
                    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="imagePreviewModalTitle">Preview</h6>
                            <!-- Bootstrap 4 close: -->
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            
                            </button>
                            <!-- Bootstrap 5 close (use this instead if on BS5):
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            -->
                        </div>
                        <div class="modal-body d-flex justify-content-center">
                            <img id="imagePreviewModalImg" src="" alt="Preview" style="max-width:100%; max-height:80vh; object-fit:contain;">
                        </div>
                        </div>
                    </div>
                    </div>


                    @push('scripts')
                        <script>
                        (function(){
                        function openModal() {
                            var modalEl = document.getElementById('imagePreviewModal');
                            // Bootstrap 5?
                            if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                            var m = bootstrap.Modal.getOrCreateInstance(modalEl);
                            m.show();
                            return;
                            }
                            // Bootstrap 4 (jQuery)?
                            if (window.jQuery && typeof jQuery.fn.modal === 'function') {
                            jQuery('#imagePreviewModal').modal('show');
                            return;
                            }
                            // Fallback (no Bootstrap JS found) — basic show
                            modalEl.classList.add('show');
                            modalEl.style.display = 'block';
                            modalEl.removeAttribute('aria-hidden');
                        }

                        // Delegate clicks from any element with data-img-src
                        document.addEventListener('click', function(e){
                            var el = e.target.closest('[data-img-src]');
                            if(!el) return;

                            var src = el.getAttribute('data-img-src');
                            var title = el.getAttribute('data-img-title') || 'Preview';
                            var modalImg = document.getElementById('imagePreviewModalImg');
                            var modalTitle = document.getElementById('imagePreviewModalTitle');
                            if (modalImg) modalImg.src = src;
                            if (modalTitle) modalTitle.textContent = title;

                            openModal();
                        });

                        // AFTER previews
                        @php
                            // Reuse the same sections array you defined for the cards
                            $__sections = [
                            ['no' => 1], ['no' => 2], ['no' => 3], ['no' => 4], ['no' => 5],
                            ];
                        @endphp
                        @foreach ($__sections as $s)
                            (function(no){
                            var input = document.getElementById('interior-input-' + no);
                            var wrap  = document.getElementById('after-wrap-' + no);
                            var img   = document.getElementById('after-img-' + no);
                            var label = wrap ? wrap.querySelector('.noimg') : null;

                            if(!input || !wrap || !img) return;

                            input.addEventListener('change', function(){
                                if (this.files && this.files[0]) {
                                var url = URL.createObjectURL(this.files[0]);
                                img.src = url;
                                img.style.display = 'block';
                                if (label) label.style.display = 'none';

                                // Make the AFTER box act like the BEFORE (clickable to open modal)
                                wrap.setAttribute('data-img-src', url);
                                wrap.setAttribute('data-img-title', 'After — Interior #' + no);
                                wrap.style.cursor = 'zoom-in';
                                } else {
                                img.src = '';
                                img.style.display = 'none';
                                if (label) label.style.display = '';
                                wrap.removeAttribute('data-img-src');
                                wrap.removeAttribute('data-img-title');
                                wrap.style.cursor = 'default';
                                }
                            });
                            })({{ $s['no'] }});
                        @endforeach
                        })();
                        </script>
                        @endpush


                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Exterior Checklist </h5>
                </div>
                <div class="card-body">
                    @php
                        // Reuse helpers/vars from above section if they exist; otherwise define here
                        $occ = $occ ?? (auth()->user()->occupation ?? null);

                        if (!isset($returnAttrs)) {
                            $returnAttrs = function ($name, $inVal, $occ) {
                                $isChecked = old($name, $inVal) === 'Y';
                                $disabled = false;
                                if ($occ === 'Operation Staff') {
                                    if ($inVal === 'Y') { $disabled = true; $isChecked = true; }
                                    elseif ($inVal === 'X') { $disabled = true; $isChecked = false; }
                                }
                                return ['checked' => $isChecked, 'disabled' => $disabled];
                            };
                        }

                        $pick = $pick ?? fn($key) => optional($checklist)->{$key} ?? '';
                        $ret  = $ret  ?? fn($key) => optional($checklist)->{$key} ?? '';

                        // Exterior items (checkboxes)
                        $extChecks = [
                            // label, pickup key, return key, input name (lowercase like legacy)
                            ['label' => 'Jack (RM70)',          'out' => 'car_out_jack',           'in' => 'car_in_jack',           'name' => 'jack'],
                            ['label' => 'Tools (RM30)',         'out' => 'car_out_tools',          'in' => 'car_in_tools',          'name' => 'tools'],
                            ['label' => 'Signage (RM30)',       'out' => 'car_out_signage',        'in' => 'car_in_signage',        'name' => 'signage'],
                            ['label' => 'Tyre Spare (RM200)',   'out' => 'car_out_tyre_spare',     'in' => 'car_in_tyre_spare',     'name' => 'tyre_spare'],
                            ['label' => 'Child Seat',           'out' => 'car_out_child_seat',     'in' => 'car_in_child_seat',     'name' => 'child_seat'],
                            ['label' => 'Lamp',                 'out' => 'car_out_lamp',           'in' => 'car_in_lamp',           'name' => 'lamp'],
                            ['label' => 'Tyres Condition',      'out' => 'car_out_tyres_condition','in' => 'car_in_tyres_condition','name' => 'tyres_condition'],
                        ];

                        // Seat/cleanliness return values (preselect from old() if any)
                        $seatReturnVal = old('car_seat_condition', optional($checklist)->car_in_seat_condition ?? null);
                        $cleanReturnVal = old('cleanliness', optional($checklist)->car_in_cleanliness ?? null);
                    @endphp

                    <div class="row">
                        {{-- Seat Condition --}}
                        <div class="col-md-6 col-sm-12 mb-3">
                            <div class="card h-100">
                                <div class="card-header text-center">
                                    <label class="mb-0">Seat Condition</label>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2 text-center"><label class="control-label">Pickup</label></div>
                                    <select class="form-control mb-3" disabled>
                                        <option>{{ optional($checklist)->car_out_seat_condition }}</option>
                                    </select>

                                    <div class="mb-2 text-center"><label class="control-label">Return</label></div>
                                    <select name="car_seat_condition" class="form-control" required>
                                        <option value="">-- Please select --</option>
                                        @foreach (['Clean','Dirty','1 Cigarettes Bud','2 Cigarettes Bud','3 Cigarettes Bud','4 Cigarettes Bud','5 Cigarettes Bud'] as $opt)
                                            <option value="{{ $opt }}" @selected($seatReturnVal === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Vehicle Cleanliness --}}
                        <div class="col-md-6 col-sm-12 mb-3">
                            <div class="card h-100">
                                <div class="card-header text-center">
                                    <label class="mb-0">Vehicle Cleanliness</label>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2 text-center"><label class="control-label">Pickup</label></div>
                                    <select class="form-control mb-3" disabled>
                                        <option>{{ optional($checklist)->car_out_cleanliness }}</option>
                                    </select>

                                    <div class="mb-2 text-center"><label class="control-label">Return</label></div>
                                    <select name="cleanliness" class="form-control" required>
                                        <option value="">-- Please select --</option>
                                        @foreach (['Clean','Dirty'] as $opt)
                                            <option value="{{ $opt }}" @selected($cleanReturnVal === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Exterior items (checkbox cards) --}}
                        @foreach ($extChecks as $ix => $c)
                            @php
                                $outVal = $pick($c['out']);   // 'Y' or 'X'
                                $inVal  = $ret($c['in']);     // 'Y' or 'X' or ''
                                $attrs  = $returnAttrs($c['name'], $inVal, $occ);

                                // layout: first 3 as col-md-4, rest as col-md-3 (like legacy)
                                $colClass = $ix < 3 ? 'col-md-4 col-sm-6' : 'col-md-3 col-sm-6';
                            @endphp

                            <div class="{{ $colClass }} mb-3">
                                <div class="card h-100">
                                    <div class="card-header text-center">
                                        <label class="mb-0">{{ $c['label'] }}</label>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <label class="d-block">Pickup</label>
                                                <input type="checkbox" value="Y" @checked($outVal === 'Y') disabled>
                                            </div>
                                            <div class="col-6">
                                                <label class="d-block" for="return_{{ $c['name'] }}">Return</label>
                                                <input
                                                    id="return_{{ $c['name'] }}"
                                                    name="{{ $c['name'] }}"
                                                    type="checkbox"
                                                    value="Y"
                                                    @checked($attrs['checked'])
                                                    @disabled($attrs['disabled'])
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Car Exterior Images</h5>
                </div>
                <div class="card-body">
                    @php

                        // Order from your legacy UI (position determined from inside car)
                        $extSections = [
                            ['no' => 1, 'title' => 'Front Left'],
                            ['no' => 3, 'title' => 'Rear Left'],
                            ['no' => 5, 'title' => 'Rear'],
                            ['no' => 4, 'title' => 'Rear Right'],
                            ['no' => 2, 'title' => 'Front Right'],
                            ['no' => 7, 'title' => 'Front'],
                            ['no' => 6, 'title' => 'Front with Customer'],
                        ];
                    @endphp

                    <p class="text-center mb-3">
                    <small>(Position is determined from inside the car; images should be taken from outside the car)</small>
                    </p>

                    <div class="row g-3">
                    @foreach ($extSections as $sec)
                        @php
                            $before = UploadData::query()
                                ->where('booking_trans_id', $booking->id)
                                ->where('position', 'pickup_exterior')
                                ->where('no', $sec['no'])
                                ->where('file_size', '!=', 0)
                                ->orderByDesc('id')
                                ->first();

                            $beforeUrl = $before && $before->file_name
                                ? Storage::url($before->file_name) . '?nocache=' . time()
                                : null;
                        @endphp

                        <div class="col-md-6 col-sm-12">
                        <div class="card h-100">
                            <div class="card-header text-center">
                            <h6 class="mb-0">{{ $sec['title'] }}</h6>
                            </div>
                            <div class="card-body text-center">
                            <div class="mb-2"><strong>Before:</strong></div>

                            {{-- BEFORE box (fixed size); clickable if image exists --}}
                            @if($beforeUrl)
                                <div class="border bg-light d-flex align-items-center justify-content-center"
                                    style="width: 90%; height: 290px; margin: 0 auto; cursor: zoom-in;"
                                    data-img-src="{{ $beforeUrl }}"
                                    data-img-title="{{ $sec['title'] }} — Before">
                                <img src="{{ $beforeUrl }}"
                                    alt="Before {{ $sec['title'] }}"
                                    style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                            @else
                                <div class="border bg-light d-flex align-items-center justify-content-center"
                                    style="width: 90%; height: 290px; margin: 0 auto;">
                                <span class="text-muted">No image uploaded</span>
                                </div>
                            @endif

                            <div class="mt-3 mb-2"><strong>After:</strong></div>

                            {{-- AFTER preview box (fixed size). Placeholder -> preview on select --}}
                            <div id="after-wrap-exterior-{{ $sec['no'] }}"
                                class="border bg-light d-flex align-items-center justify-content-center mb-2"
                                style="width: 90%; height: 290px; margin: 0 auto;"
                                data-section-title="{{ $sec['title'] }}">
                                <img id="after-img-exterior-{{ $sec['no'] }}"
                                    src=""
                                    alt="After {{ $sec['title'] }}"
                                    style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                                <span class="text-muted noimg">No image uploaded</span>
                            </div>

                            <input
                                id="exterior-input-{{ $sec['no'] }}"
                                type="file"
                                name="exterior[]"
                                class="form-control"
                                accept="image/*"
                                required
                            >
                            </div>
                        </div>
                        </div>
                    @endforeach
                    </div>

                    @push('scripts')
                    <script>
                    (function(){
                    // Reuse the global click->modal opener you already have from the Interior section.
                    // Here we only add "After" preview logic for exterior inputs.
                    @php $__ext = $extSections; @endphp
                    @foreach($__ext as $e)
                        (function(no, title){
                        var input = document.getElementById('exterior-input-' + no);
                        var wrap  = document.getElementById('after-wrap-exterior-' + no);
                        var img   = document.getElementById('after-img-exterior-' + no);
                        var label = wrap ? wrap.querySelector('.noimg') : null;

                        if(!input || !wrap || !img) return;

                        input.addEventListener('change', function(){
                            if (this.files && this.files[0]) {
                            var url = URL.createObjectURL(this.files[0]);
                            img.src = url;
                            img.style.display = 'block';
                            if (label) label.style.display = 'none';

                            // Make the AFTER box clickable to open the same modal
                            wrap.setAttribute('data-img-src', url);
                            wrap.setAttribute('data-img-title', 'After — ' + title);
                            wrap.style.cursor = 'zoom-in';
                            } else {
                            img.src = '';
                            img.style.display = 'none';
                            if (label) label.style.display = '';
                            wrap.removeAttribute('data-img-src');
                            wrap.removeAttribute('data-img-title');
                            wrap.style.cursor = 'default';
                            }
                        });
                        })({{ $e['no'] }}, @json($e['title']));
                    @endforeach
                    })();
                    </script>
                    @endpush

                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Car Condition Images</h5>
                </div>
                <div class="card-body">
                    @php
                        // BEFORE (pickup) overlay saved earlier, e.g. "damage_markings/damage_123.jpg"
                        $pickupDamagePath = optional($checklist)->car_out_image;
                        $pickupDamageUrl  = null;
                        if ($pickupDamagePath && file_exists(public_path('storage/'.$pickupDamagePath))) {
                            $pickupDamageUrl = asset('storage/'.$pickupDamagePath) . '?nocache=' . time();
                        }

                        // Background silhouette under the return canvas
                        $silhouetteUrl = asset('assets/images/pickup.jpg');
                    @endphp

                    <style>
                        .damage-canvas-wrap { width:100%; max-width:300px; margin:0 auto; }
                        .damage-canvas { display:block; border:1px solid rgba(0,0,0,.25); background:transparent; }
                        .img-box { width:100%; }
                        .img-box-inner{
                            position:relative;
                            width:100%;
                            aspect-ratio:16/9;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        }
                        .img-box-inner img{
                            width:100%;
                            height:100%;
                            object-fit:contain;
                        }
                    </style>

                    <div class="text-center mb-3">
                        <label class="d-block mb-2">Before Image</label>

                        <div id="beforeWrap" class="damage-canvas-wrap">
                            @if ($pickupDamageUrl)
                                <div class="img-box border bg-white mx-auto"
                                    style="cursor:zoom-in"
                                    data-img-src="{{ $pickupDamageUrl }}"
                                    data-img-title="Before — Car Condition">
                                    <div class="img-box-inner">
                                        <img src="{{ $pickupDamageUrl }}" alt="Pickup damage">
                                    </div>
                                </div>
                            @else
                                <div class="img-box border bg-light mx-auto">
                                    <div class="img-box-inner">
                                        <span class="text-muted">No image uploaded</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-12 text-center">
                            <label class="d-block mb-2">Return Image</label>

                            <div id="damageCanvasWrap" class="damage-canvas-wrap">
                                <!-- IMPORTANT: real canvas size is exactly 300×200 -->
                                <canvas id="return-damage-canvas" class="damage-canvas" width="300" height="200"></canvas>
                            </div>

                            <div class="form-group mt-3">
                                <button type="button" class="btn btn-sm btn-info me-1 mb-2" id="btn-broken">+ Broken</button>
                                <button type="button" class="btn btn-sm btn-info me-1 mb-2" id="btn-scratch">+ Scratch</button>
                                <button type="button" class="btn btn-sm btn-info me-1 mb-2" id="btn-missing">+ Missing</button>
                                <button type="button" class="btn btn-sm btn-info me-1 mb-2" id="btn-dent">+ Dent</button>
                                <button type="button" class="btn btn-sm btn-danger ms-2 mb-2" id="btn-remove">Remove Selected</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2 mb-2" id="btn-clear">Clear</button>
                            </div>

                            <input type="hidden" name="hidden_datas" id="hidden_datas">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header text-center"><strong>Pickup Remark</strong></div>
                                <div class="card-body">
                                    <textarea class="form-control" rows="4" disabled>{{ trim(optional($checklist)->car_out_remark ?? '') !== '' ? optional($checklist)->car_out_remark : 'No remark' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-header text-center"><strong>Return Remark</strong></div>
                                <div class="card-body">
                                    <textarea class="form-control" id="markingRemarks" name="markingRemarks" rows="4">{{ old('markingRemarks', optional($checklist)->car_in_remark) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Fabric.js --}}
                    <script src="https://cdn.jsdelivr.net/npm/fabric@4.6.0/dist/fabric.min.js"></script>
                    <script>
                        (function(){
                            if (typeof fabric === 'undefined') { console.error('Fabric.js failed to load'); return; }

                            // ==== Logical size is EXACTLY 300 × 200 ====
                            const BASE_W = 300, BASE_H = 200;

                            const wrap   = document.getElementById('damageCanvasWrap');
                            const canvas = new fabric.Canvas('return-damage-canvas', { selection: true });
                            canvas.setWidth(BASE_W);
                            canvas.setHeight(BASE_H);
                            canvas.backgroundColor = null;

                            // Load silhouette and scale to cover 300x200
                            const silhouetteUrl = @json($silhouetteUrl);
                            fabric.Image.fromURL(
                                silhouetteUrl + '?v={{ time() }}',
                                function(img){
                                    img.set({
                                        originX: 'left', originY: 'top',
                                        left: 0, top: 0,
                                        selectable: false, evented: false,
                                        scaleX: BASE_W / img.width,
                                        scaleY: BASE_H / img.height
                                    });
                                    canvas.setBackgroundImage(img, canvas.requestRenderAll.bind(canvas));
                                },
                                { crossOrigin: 'anonymous' }
                            );

                            // Helpers: editable handles
                            function styleEditable(o){
                                o.set({
                                    hasControls: true,
                                    hasBorders:  true,
                                    borderColor: '#0dcaf0',
                                    cornerColor: '#0dcaf0',
                                    cornerStyle: 'circle',
                                    cornerSize: 10,
                                    transparentCorners: false,
                                    lockScalingFlip: true,
                                    minScaleLimit: 0.2
                                });
                                o.setCoords();
                            }

                            // Add markers
                            document.getElementById('btn-broken')?.addEventListener('click', function(){
                                const obj = new fabric.Circle({ radius: 12, fill: '#000', left: 40, top: 30 });
                                styleEditable(obj); canvas.add(obj).setActiveObject(obj);
                            });
                            document.getElementById('btn-scratch')?.addEventListener('click', function(){
                                const obj = new fabric.Text('=', { left: 60, top: 60, fill: '#000', fontSize: 36, fontWeight: 'bold' });
                                styleEditable(obj); canvas.add(obj).setActiveObject(obj);
                            });
                            document.getElementById('btn-missing')?.addEventListener('click', function(){
                                const obj = new fabric.Rect({ left: 50, top: 100, width: 26, height: 26, fill: '#000' });
                                styleEditable(obj); canvas.add(obj).setActiveObject(obj);
                            });
                            document.getElementById('btn-dent')?.addEventListener('click', function(){
                                const obj = new fabric.Triangle({ left: 55, top: 140, width: 28, height: 28, fill: '#000' });
                                styleEditable(obj); canvas.add(obj).setActiveObject(obj);
                            });

                            // Remove / Clear
                            document.getElementById('btn-remove')?.addEventListener('click', function(){
                                const obj = canvas.getActiveObject();
                                if (obj) canvas.remove(obj);
                                canvas.requestRenderAll();
                            });
                            document.getElementById('btn-clear')?.addEventListener('click', function(){
                                const bg = canvas.backgroundImage;
                                canvas.clear();
                                if (bg) canvas.setBackgroundImage(bg, canvas.requestRenderAll.bind(canvas));
                            });

                            // Responsive display (visual zoom only)
                            function resizeCanvas(){
                                const wrapWidth = (wrap?.clientWidth || BASE_W);
                                const scale     = wrapWidth / BASE_W;
                                canvas.setDimensions({ width: wrapWidth, height: BASE_H * scale });
                                canvas.setZoom(scale || 1);
                                canvas.requestRenderAll();
                            }
                            resizeCanvas();
                            window.addEventListener('resize', (function(){ let t; return ()=>{ clearTimeout(t); t=setTimeout(resizeCanvas,120); }; })());

                            // Export EXACT 300×200 at submit (multiplier: 1)
                            const form = document.getElementById('return-form');
                            if (form) {
                                form.addEventListener('submit', function () {
                                    const dataUrl = canvas.toDataURL({ format: 'jpeg', quality: 0.9, multiplier: 1 });
                                    document.getElementById('hidden_datas').value = dataUrl;
                                });
                            }

                            // Modal preview for "Before" image (reuses your global modal if present)
                            document.addEventListener('click', function(e){
                                const el = e.target.closest('[data-img-src]');
                                if(!el) return;
                                const src = el.getAttribute('data-img-src');
                                const title = el.getAttribute('data-img-title') || 'Preview';

                                const modalEl   = document.getElementById('imagePreviewModal');
                                const modalImg  = document.getElementById('imagePreviewModalImg');
                                const modalHead = document.getElementById('imagePreviewModalTitle');

                                if (modalImg)  modalImg.src = src;
                                if (modalHead) modalHead.textContent = title;

                                if (window.bootstrap && window.bootstrap.Modal) {
                                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                                } else if (window.jQuery && jQuery.fn.modal) {
                                    jQuery(modalEl).modal('show');
                                }
                            });
                        })();
                    </script>
                </div>
            </div>


            <div class="card mb-4">
                <div class="card-header">
                    <h5>Return Renter Signature</h5>
                </div>
                <div class="card-body">
                    {{-- ===== Return Renter Signature (drop-in) ===== --}}
                    <div class="row">
                    <div class="col-12">
                        <div class="sig-wrap mx-auto">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" id="sig-pen">Pen</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="sig-eraser">Eraser</button>
                            <button type="button" class="btn btn-sm btn-outline-warning me-1" id="sig-undo">Undo</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="sig-clear">Clear</button>
                            </div>
                            <small class="text-muted" id="sig-mode-label">Mode: Pen</small>
                        </div>

                        <div class="sig-canvas-wrap">
                            <canvas id="return-sign-canvas" class="sig-canvas"></canvas>
                        </div>

                        <input type="hidden" name="signature_return_data" id="signature_return_data">
                        </div>
                    </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="renter_ack_return" name="renter_ack_return" value="Y" {{ old('renter_ack_return') ? 'checked' : '' }} required>
                        <label class="form-check-label" for="renter_ack_return">
                            I (Renter) returned this car in <strong>CLEAN</strong> condition without any <strong>FORBIDDEN STUFF</strong> or <strong>CRIMINAL ACTIVITY STUFF</strong>.
                        </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="has_damage" name="has_damage" value="Y" {{ old('has_damage') ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_damage">
                            (Staff) Any damage/missing details
                        </label>
                        </div>
                    </div>
                    </div>

                    <div id="damageFields" class="mt-3 {{ old('has_damage') ? '' : 'd-none' }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                        <label class="form-label">Damages Details</label>
                        <input class="form-control" name="damage_charges_details" value="{{ old('damage_charges_details') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>
                        <div class="col-md-6">
                        <label class="form-label">Charges for Damages (RM)</label>
                        <input type="number" step="0.01" class="form-control money" id="amount1" name="damage_charges" value="{{ old('damage_charges') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Missing Items Details</label>
                        <input class="form-control" name="missing_items_charges_details" value="{{ old('missing_items_charges_details') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>
                        <div class="col-md-6">
                        <label class="form-label">Charges for Missing Items (RM)</label>
                        <input type="number" step="0.01" class="form-control money" id="amount2" name="missing_items_charges" value="{{ old('missing_items_charges') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Additional Cost Details</label>
                        <input class="form-control" name="additional_cost_details" value="{{ old('additional_cost_details') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>
                        <div class="col-md-6">
                        <label class="form-label">Additional Cost (RM)</label>
                        <input type="number" step="0.01" class="form-control money" id="amount3" name="additional_cost" value="{{ old('additional_cost') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Total Damage Cost (RM)</label>
                        <input type="number" step="0.01" class="form-control bg-light" id="damage_total_cost" name="damage_total_cost" value="{{ old('damage_total_cost') }}" readonly>
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Total Payment Made (RM)</label>
                        <input type="number" step="0.01" class="form-control" id="damage_payment_made" name="damage_payment_made" value="{{ old('damage_payment_made') }}" placeholder="LEAVE BLANK IF UNNECESSARY">
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Payment Type</label>
                        <select name="type_of_payment" id="type_of_payment" class="form-control">
                            <option value="">-- Please Select --</option>
                            <option value="Collect" {{ old('type_of_payment')==='Collect'?'selected':'' }}>Collect</option>
                            <option value="Cash"    {{ old('type_of_payment')==='Cash'?'selected':'' }}>Cash</option>
                            <option value="Online"  {{ old('type_of_payment')==='Online'?'selected':'' }}>Online</option>
                            <option value="Card"    {{ old('type_of_payment')==='Card'?'selected':'' }}>Card</option>
                        </select>
                        </div>

                        <div class="col-md-6">
                        <label class="form-label">Auto Extend</label>
                        <select name="auto_extend" id="auto_extend" class="form-control">
                            <option value="">-- Auto Extend --</option>
                            <option value="none" {{ old('auto_extend')==='none'?'selected':'' }}>0 hour (no auto extend)</option>
                            <option value="+1 hour"  {{ old('auto_extend')==='+1 hour'?'selected':'' }}>1 hour</option>
                            <option value="+2 hours" {{ old('auto_extend')==='+2 hours'?'selected':'' }}>2 hours</option>
                            <option value="+3 hours" {{ old('auto_extend')==='+3 hours'?'selected':'' }}>3 hours</option>
                            <option value="+4 hours" {{ old('auto_extend')==='+4 hours'?'selected':'' }}>4 hours</option>
                            <option value="+5 hours" {{ old('auto_extend')==='+5 hours'?'selected':'' }}>5 hours</option>
                            <option value="+6 hours" {{ old('auto_extend')==='+6 hours'?'selected':'' }}>6 hours</option>
                            <option value="+12 hours"{{ old('auto_extend')==='+12 hours'?'selected':'' }}>12 hours</option>
                            <option value="+1 day"   {{ old('auto_extend')==='+1 day'?'selected':'' }}>1 day</option>
                            <option value="+2 days"  {{ old('auto_extend')==='+2 days'?'selected':'' }}>2 days</option>
                            <option value="+3 days"  {{ old('auto_extend')==='+3 days'?'selected':'' }}>3 days</option>
                            <option value="+4 days"  {{ old('auto_extend')==='+4 days'?'selected':'' }}>4 days</option>
                            <option value="+5 days"  {{ old('auto_extend')==='+5 days'?'selected':'' }}>5 days</option>
                            <option value="+6 days"  {{ old('auto_extend')==='+6 days'?'selected':'' }}>6 days</option>
                            <option value="+7 days"  {{ old('auto_extend')==='+7 days'?'selected':'' }}>7 days</option>
                            <option value="+8 days"  {{ old('auto_extend')==='+8 days'?'selected':'' }}>8 days</option>
                            <option value="+9 days"  {{ old('auto_extend')==='+9 days'?'selected':'' }}>9 days</option>
                            <option value="+10 days" {{ old('auto_extend')==='+10 days'?'selected':'' }}>10 days</option>
                            <option value="+11 days" {{ old('auto_extend')==='+11 days'?'selected':'' }}>11 days</option>
                            <option value="+12 days" {{ old('auto_extend')==='+12 days'?'selected':'' }}>12 days</option>
                            <option value="+13 days" {{ old('auto_extend')==='+13 days'?'selected':'' }}>13 days</option>
                            <option value="+14 days" {{ old('auto_extend')==='+14 days'?'selected':'' }}>14 days</option>
                            <option value="+1 month" {{ old('auto_extend')==='+1 month'?'selected':'' }}>1 month</option>
                            <option value="+2 month" {{ old('auto_extend')==='+2 month'?'selected':'' }}>2 month</option>
                            <option value="+3 month" {{ old('auto_extend')==='+3 month'?'selected':'' }}>3 month</option>
                        </select>
                        </div>
                    </div>
                    </div>

                    {{-- Styles for signature area --}}
                    <style>
                    .sig-wrap{max-width:500px}
                    .sig-canvas-wrap{width:100%; position:relative}
                    .sig-canvas{
                        width:100%;            /* responsive display */
                        height:auto;
                        border:1px solid rgba(0,0,0,.25);
                        touch-action:none;     /* prevents page scroll while drawing */
                        background:#fff;       /* white background so the PNG isn't transparent */
                        display:block;
                    }
                    </style>

                    {{-- Signature pad (vanilla JS, responsive, undo/eraser/clear) --}}
                    <script>
                    (function(){
                    // ==== Canvas setup (base logical size, scaled to wrapper) ====
                    const BASE_W = 700, BASE_H = 430; // keep your legacy proportions
                    const canvas = document.getElementById('return-sign-canvas');
                    const ctx = canvas.getContext('2d', { willReadFrequently:true });

                    // Drawing state
                    let tool = 'pen'; // 'pen' | 'eraser'
                    let drawing = false;
                    let paths = [];   // [{tool,color,size,points:[{x,y}], composite}]
                    let currentPath = null;

                    // Styles
                    const PEN_COLOR = '#000000';
                    const PEN_SIZE  = 3;
                    const ERASER_SIZE = 12;

                    // Init base size (logical)
                    canvas.width  = BASE_W;
                    canvas.height = BASE_H;

                    // Start with white background so PNG has a white canvas
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0,0,BASE_W,BASE_H);

                    // ===== Helpers =====
                    function redraw() {
                        // Clear to white
                        ctx.clearRect(0,0,BASE_W,BASE_H);
                        ctx.fillStyle = '#FFFFFF';
                        ctx.fillRect(0,0,BASE_W,BASE_H);

                        // Draw all paths
                        for (const p of paths) {
                        ctx.save();
                        ctx.globalCompositeOperation = (p.tool === 'eraser') ? 'destination-out' : 'source-over';
                        ctx.strokeStyle = (p.tool === 'eraser') ? '#000' : p.color;
                        ctx.lineWidth = p.size;
                        ctx.lineJoin = 'round';
                        ctx.lineCap  = 'round';
                        ctx.beginPath();
                        for (let i=0;i<p.points.length;i++){
                            const pt = p.points[i];
                            if (i===0) ctx.moveTo(pt.x, pt.y);
                            else ctx.lineTo(pt.x, pt.y);
                        }
                        ctx.stroke();
                        ctx.restore();
                        }
                    }

                    function startPath(x,y){
                        currentPath = {
                        tool: tool,
                        color: PEN_COLOR,
                        size: (tool==='eraser') ? ERASER_SIZE : PEN_SIZE,
                        points: [{x,y}]
                        };
                        paths.push(currentPath);
                        redraw();
                    }

                    function addPoint(x,y){
                        if (!currentPath) return;
                        currentPath.points.push({x,y});
                        redraw();
                    }

                    function endPath(){
                        currentPath = null;
                    }

                    function getPos(evt){
                        const rect = canvas.getBoundingClientRect();
                        const scaleX = canvas.width  / rect.width;
                        const scaleY = canvas.height / rect.height;

                        const clientX = (evt.touches && evt.touches[0]) ? evt.touches[0].clientX : evt.clientX;
                        const clientY = (evt.touches && evt.touches[0]) ? evt.touches[0].clientY : evt.clientY;

                        return {
                        x: (clientX - rect.left) * scaleX,
                        y: (clientY - rect.top)  * scaleY
                        };
                    }

                    // ==== Pointer/touch events ====
                    function down(e){ e.preventDefault(); const p=getPos(e); drawing=true; startPath(p.x,p.y); }
                    function move(e){ if(!drawing) return; e.preventDefault(); const p=getPos(e); addPoint(p.x,p.y); }
                    function up(e){ if(!drawing) return; e.preventDefault(); drawing=false; endPath(); }

                    canvas.addEventListener('mousedown', down);
                    window.addEventListener('mousemove', move);
                    window.addEventListener('mouseup',   up);

                    canvas.addEventListener('touchstart', down, {passive:false});
                    canvas.addEventListener('touchmove',  move, {passive:false});
                    canvas.addEventListener('touchend',   up,   {passive:false});
                    canvas.addEventListener('touchcancel',up,   {passive:false});

                    // ==== Toolbar ====
                    const modeLabel = document.getElementById('sig-mode-label');
                    document.getElementById('sig-pen').addEventListener('click', function(){
                        tool = 'pen'; modeLabel.textContent = 'Mode: Pen';
                    });
                    document.getElementById('sig-eraser').addEventListener('click', function(){
                        tool = 'eraser'; modeLabel.textContent = 'Mode: Eraser';
                    });
                    document.getElementById('sig-undo').addEventListener('click', function(){
                        if (paths.length > 0) { paths.pop(); redraw(); }
                    });
                    document.getElementById('sig-clear').addEventListener('click', function(){
                        paths = [];
                        redraw();
                    });

                    // ==== Damage section toggles & total ====
                    const damageToggle = document.getElementById('has_damage');
                    const damageFields = document.getElementById('damageFields');
                    const payMade = document.getElementById('damage_payment_made');
                    const payType = document.getElementById('type_of_payment');

                    function toggleDamageRequired(on){
                        if (!payMade || !payType) return;
                        payMade.required = !!on;
                        payType.required = !!on;
                    }

                    function updateTotal(){
                        const n = v => parseFloat(v || '0') || 0;
                        const t = n(document.getElementById('amount1')?.value)
                                + n(document.getElementById('amount2')?.value)
                                + n(document.getElementById('amount3')?.value);
                        const total = document.getElementById('damage_total_cost');
                        if (total) total.value = t.toFixed(2);
                    }

                    ['amount1','amount2','amount3'].forEach(id=>{
                        const el = document.getElementById(id);
                        if(el){ el.addEventListener('input', updateTotal); }
                    });

                    if (damageToggle){
                        damageToggle.addEventListener('change', function(){
                        if (this.checked){ damageFields.classList.remove('d-none'); toggleDamageRequired(true); }
                        else { damageFields.classList.add('d-none'); toggleDamageRequired(false); }
                        });
                        // initialize on load
                        toggleDamageRequired(damageToggle.checked);
                        updateTotal();
                    }

                    // ==== Export on form submit ====
                    const form = document.getElementById('return-form');
                    if (form){
                        form.addEventListener('submit', function(){
                        // If the renter acknowledgement is required, keep HTML required attribute on the checkbox.
                        // Export signature only if something was drawn:
                        const hasInk = paths.some(p => (p.points && p.points.length > 1));
                        if (hasInk){
                            const dataUrl = canvas.toDataURL('image/png');
                            document.getElementById('signature_return_data').value = dataUrl;
                        } else {
                            document.getElementById('signature_return_data').value = '';
                        }
                        });
                    }
                    })();
                    </script>

                </div>
            </div>


        </div>

        {{-- Footer with buttons at bottom right --}}
        <div class="d-flex justify-content-end mt-3">
            <a href="{{ route('reservation.view', $booking->id) }}" class="btn btn-secondary me-2">Back</a>
            <button type="submit" class="btn btn-primary">Save Return</button>
        </div>
    </form>
    {{-- End form --}}
</div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
