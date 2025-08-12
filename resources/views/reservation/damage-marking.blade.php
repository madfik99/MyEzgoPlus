@extends('layouts.main')

@section('page-title', __('Damage Marking'))
@section('page-breadcrumb', __('Damage Marking'))

@section('content')

@php
// Include your full $hotspotMap here (shortened for demo)
$hotspotMap = [
                'sedan' => [
                    'front' => [
                        ['top' => '25.5%', 'left' => '50.5%', 'part' => 'Front Glass'],
                        ['top' => '48.5%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '32.5%', 'left' => '9.5%', 'part' => 'Left Side Mirror'],
                        ['top' => '32.5%', 'left' => '90%', 'part' => 'Right Side Mirror'],
                        ['top' => '48.5%', 'left' => '18.5%', 'part' => 'Left Headlight'],
                        ['top' => '72%', 'left' => '50.5%', 'part' => 'Bumper'],
                        ['top' => '43%', 'left' => '50.5%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '25.5%', 'left' => '36.5%', 'part' => 'Front Glass Left'],
                        ['top' => '47.5%', 'left' => '75.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '50%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '35.5%', 'left' => '27%', 'part' => 'Left Front Fender'],
                        ['top' => '47.5%', 'left' => '22.5%', 'part' => 'Front Left Tire'],
                        ['top' => '38%', 'left' => '53.5%', 'part' => 'Left Door'],
                        ['top' => '27.5%', 'left' => '47.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '25.5%', 'left' => '48.5%', 'part' => 'Rear Glass'],
                        ['top' => '42%', 'left' => '82%', 'part' => 'Rear Right Headlight'],
                        ['top' => '42%', 'left' => '17.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '68%', 'left' => '48.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '48.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '25.5%', 'left' => '62.5%', 'part' => 'Front Glass Right'],
                        ['top' => '45.5%', 'left' => '76.5%', 'part' => 'Front Right Tire'],
                        ['top' => '50%', 'left' => '55%', 'part' => 'Right Skirt'],
                        ['top' => '35.5%', 'left' => '74%', 'part' => 'Right Front Fender'],
                        ['top' => '45.5%', 'left' => '23.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '38%', 'left' => '46.5%', 'part' => 'Right Door'],
                        ['top' => '27.5%', 'left' => '52.5%', 'part' => 'Right Window'],
                    ],
                ],
                'hatchback' => [
                    'front' => [
                        ['top' => '25%', 'left' => '50%', 'part' => 'Front Glass'],
                        ['top' => '48.5%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '33.5%', 'left' => '9%', 'part' => 'Left Side Mirror'],
                        ['top' => '33.5%', 'left' => '90.5%', 'part' => 'Right Side Mirror'],
                        ['top' => '48.5%', 'left' => '17%', 'part' => 'Left Headlight'],
                        ['top' => '73%', 'left' => '50%', 'part' => 'Bumper'],
                        ['top' => '43%', 'left' => '50%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '32.5%', 'left' => '36.5%', 'part' => 'Front Glass Left'],
                        ['top' => '57.5%', 'left' => '80.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '45.5%', 'left' => '23%', 'part' => 'Left Front Fender'],
                        ['top' => '57.5%', 'left' => '20.5%', 'part' => 'Front Left Tire'],
                        ['top' => '47.5%', 'left' => '56.5%', 'part' => 'Left Door'],
                        ['top' => '35.5%', 'left' => '45.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '33.5%', 'left' => '50.5%', 'part' => 'Rear Glass'],
                        ['top' => '42.5%', 'left' => '83.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '42.5%', 'left' => '16.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '65%', 'left' => '50.5%', 'part' => 'Rear Bumper'],
                        ['top' => '47.5%', 'left' => '50.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '32.5%', 'left' => '60.5%', 'part' => 'Front Glass Right'],
                        ['top' => '57.5%', 'left' => '80.5%', 'part' => 'Front Right Tire'],
                        ['top' => '60%', 'left' => '48%', 'part' => 'Right Skirt'],
                        ['top' => '45.5%', 'left' => '77%', 'part' => 'Right Front Fender'],
                        ['top' => '57.5%', 'left' => '18.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '47%', 'left' => '42.5%', 'part' => 'Right Door'],
                        ['top' => '35.5%', 'left' => '50.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                'suv' => [
                    'front' => [
                        ['top' => '20%', 'left' => '50%', 'part' => 'Front Glass'],
                        ['top' => '42%', 'left' => '83%', 'part' => 'Right Headlight'],
                        ['top' => '28.5%', 'left' => '10%', 'part' => 'Left Side Mirror'],
                        ['top' => '28.5%', 'left' => '90%', 'part' => 'Right Side Mirror'],
                        ['top' => '42%', 'left' => '15%', 'part' => 'Left Headlight'],
                        ['top' => '70%', 'left' => '50%', 'part' => 'Bumper'],
                        ['top' => '35%', 'left' => '50%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '22.5%', 'left' => '37.5%', 'part' => 'Front Glass Left'],
                        ['top' => '50.5%', 'left' => '76.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '49%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '33.5%', 'left' => '23%', 'part' => 'Left Front Fender'],
                        ['top' => '50.5%', 'left' => '20.5%', 'part' => 'Front Left Tire'],
                        ['top' => '38%', 'left' => '53.5%', 'part' => 'Left Door'],
                        ['top' => '25.5%', 'left' => '45.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '30.5%', 'left' => '49.5%', 'part' => 'Rear Glass'],
                        ['top' => '40.5%', 'left' => '82.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '40.5%', 'left' => '17.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '62%', 'left' => '49.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '49.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '22.5%', 'left' => '62.5%', 'part' => 'Front Glass Right'],
                        ['top' => '50.5%', 'left' => '78.5%', 'part' => 'Front Right Tire'],
                        ['top' => '49%', 'left' => '55%', 'part' => 'Right Skirt'],
                        ['top' => '33.5%', 'left' => '75%', 'part' => 'Right Front Fender'],
                        ['top' => '50.5%', 'left' => '20.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '38%', 'left' => '46.5%', 'part' => 'Right Door'],
                        ['top' => '25.5%', 'left' => '52.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                'van' => [
                    'front' => [
                        ['top' => '20%', 'left' => '50%', 'part' => 'Front Glass'],
                        ['top' => '47%', 'left' => '82%', 'part' => 'Right Headlight'],
                        ['top' => '28%', 'left' => '8%', 'part' => 'Left Side Mirror'],
                        ['top' => '28%', 'left' => '92%', 'part' => 'Right Side Mirror'],
                        ['top' => '47%', 'left' => '17%', 'part' => 'Left Headlight'],
                        ['top' => '75%', 'left' => '50%', 'part' => 'Bumper'],
                        ['top' => '38%', 'left' => '50%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '24.5%', 'left' => '28.5%', 'part' => 'Front Glass Left'],
                        ['top' => '52.5%', 'left' => '77.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '52%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '36.5%', 'left' => '22%', 'part' => 'Left Front Fender'],
                        ['top' => '52.5%', 'left' => '22.5%', 'part' => 'Front Left Tire'],
                        ['top' => '40%', 'left' => '47.5%', 'part' => 'Left Door'],
                        ['top' => '27.5%', 'left' => '38.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '32.5%', 'left' => '50.5%', 'part' => 'Rear Glass'],
                        ['top' => '47.5%', 'left' => '83.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '47.5%', 'left' => '15.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '65%', 'left' => '50.5%', 'part' => 'Rear Bumper'],
                        ['top' => '52%', 'left' => '50.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '24.5%', 'left' => '72.5%', 'part' => 'Front Glass Right'],
                        ['top' => '52.5%', 'left' => '77.5%', 'part' => 'Front Right Tire'],
                        ['top' => '52%', 'left' => '58%', 'part' => 'Right Skirt'],
                        ['top' => '36.5%', 'left' => '77%', 'part' => 'Right Front Fender'],
                        ['top' => '52.5%', 'left' => '22.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '40%', 'left' => '52.5%', 'part' => 'Right Door'],
                        ['top' => '27.5%', 'left' => '60.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                'mpv' => [
                    'front' => [
                        ['top' => '28%', 'left' => '50%', 'part' => 'Front Glass'],
                        ['top' => '46%', 'left' => '83%', 'part' => 'Right Headlight'],
                        ['top' => '34%', 'left' => '10%', 'part' => 'Left Side Mirror'],
                        ['top' => '34%', 'left' => '90%', 'part' => 'Right Side Mirror'],
                        ['top' => '46%', 'left' => '16%', 'part' => 'Left Headlight'],
                        ['top' => '68%', 'left' => '50%', 'part' => 'Bumper'],
                        ['top' => '41%', 'left' => '50%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '22.5%', 'left' => '29.5%', 'part' => 'Front Glass Left'],
                        ['top' => '50.5%', 'left' => '74.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '50%', 'left' => '40%', 'part' => 'Left Skirt'],
                        ['top' => '36.5%', 'left' => '23%', 'part' => 'Left Front Fender'],
                        ['top' => '50.5%', 'left' => '20.5%', 'part' => 'Front Left Tire'],
                        ['top' => '38%', 'left' => '46.5%', 'part' => 'Left Door'],
                        ['top' => '25.5%', 'left' => '40.5%', 'part' => 'Left Window'],
                    ],
                    'rear' => [
                        ['top' => '28.5%', 'left' => '50.5%', 'part' => 'Rear Glass'],
                        ['top' => '45.5%', 'left' => '85.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '45.5%', 'left' => '14.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '66%', 'left' => '50.5%', 'part' => 'Rear Bumper'],
                        ['top' => '56%', 'left' => '50.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '22.5%', 'left' => '68.5%', 'part' => 'Front Glass Right'],
                        ['top' => '50.5%', 'left' => '79.5%', 'part' => 'Front Right Tire'],
                        ['top' => '50%', 'left' => '60%', 'part' => 'Right Skirt'],
                        ['top' => '36.5%', 'left' => '80%', 'part' => 'Right Front Fender'],
                        ['top' => '50.5%', 'left' => '25.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '38%', 'left' => '52.5%', 'part' => 'Right Door'],
                        ['top' => '25.5%', 'left' => '60.5%', 'part' => 'Right Window'],
                    ],
                    
                ],
                
            ];

@endphp

{{-- Debug info --}}
<div class="mb-3 mt-3">
    <h5>Layout Type: <code>{{ $layoutType }}</code></h5>
    <p>Vehicle: {{ $pickup->vehicle->class->class_name ?? 'N/A' }}</p>
</div>
<div class="btn-group mb-3 mt-3">
        <a href="{{ route('reservation.view', $pickup->id) }}" class="btn btn-secondary">Back</a>
        
</div>

{{-- Loop through all 4 layout sides --}}
@php
    $sides = ['front' => 'Front Layout', 'left' => 'Left Side Layout', 'rear' => 'Rear Layout', 'right' => 'Right Side Layout'];
    $sideGroups = array_chunk(array_keys($sides), 2); // for 2 columns
@endphp

<div class="row gy-4">
    @foreach($sideGroups as $group)
    <div class="row gy-4">
        @foreach($group as $side)
            <div class="col-12 col-md-6 d-flex">
                <div class="card w-100 h-100 shadow-sm">
                    <div class="card-header">
                        <h5 class="mt-2">{{ $sides[$side] }}</h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="text-muted">Tap a hotspot to view uploaded damage photo.</p>

                        @php
                            $imageName = in_array($side, ['left', 'right']) ? "{$side}_side_layout.png" : "{$side}_layout.png";
                        @endphp

                        {{-- Layout Image with Hotspots --}}
                        <div class="position-relative mx-auto mb-4" style="height: 300px; max-width: 100%; overflow: hidden;">
                            <img src="{{ asset('storage/layout/' . $layoutType . '/' . $imageName) }}"
                                 alt="{{ $sides[$side] }}"
                                 class="img-fluid w-100 border rounded object-fit-cover"
                                 style="max-height: 300px;">

                            @foreach($hotspotMap[$layoutType][$side] ?? [] as $hotspot)
                                @php
                                    $part = $hotspot['part'];
                                    $upload = $damageUploads->get($part)?->first();
                                @endphp

                                {{-- Hotspot Button --}}
                                <div class="position-absolute"
                                     style="top: {{ $hotspot['top'] }};
                                            left: {{ $hotspot['left'] }};
                                            transform: translate(-50%, -50%);
                                            z-index: 5;">
                                    <button type="button"
                                            class="btn btn-outline-primary rounded-circle"
                                            style="width: 14px; height: 14px; padding: 0; font-size: 9px;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewDamageModal"
                                            data-part="{{ $part }}">●</button>
                                </div>

                                {{-- Floating Thumbnail on Layout --}}
                                {{-- @if($upload)
                                    <div class="position-absolute"
                                         style="top: calc({{ $hotspot['top'] }} + 6px);
                                                left: calc({{ $hotspot['left'] }} + 6px);
                                                z-index: 10;">
                                        <img src="{{ asset('storage/' . $upload->file_name) }}"
                                             alt="{{ $part }}"
                                             class="img-thumbnail shadow"
                                             style="max-width: 60px; max-height: 60px;">
                                    </div>
                                @endif --}}
                            @endforeach
                        </div>

                        {{-- Damage Image Cards (3 per row) --}}
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 px-2">
                            @foreach($hotspotMap[$layoutType][$side] ?? [] as $hotspot)
                                @php
                                    $part = $hotspot['part'];
                                    $upload = $damageUploads->get($part)?->first();
                                @endphp
                                @if($upload)
                                    <div class="col">
                                        <div class="card h-100 shadow-sm border-0 bg-light">
                                            <img src="{{ asset('storage/' . $upload->file_name) }}"
                                                 class="card-img-top"
                                                 alt="{{ $upload->label }}"
                                                 style="height: 110px; object-fit: cover;">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1 text-primary small">{{ $upload->label }}</h6>
                                                <p class="card-text text-muted small mb-0">
                                                    {{ $upload->remarks ?: 'No remarks.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
</div>




{{-- Modal for damage preview --}}
<div class="modal fade" id="viewDamageModal" tabindex="-1" aria-labelledby="viewDamageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewDamageModalLabel">Damage Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="damagePreviewImage" src="#" class="img-fluid rounded border" style="max-height: 500px;" alt="Damage Preview">
        <p class="mt-3" id="damagePartLabel"></p>
      </div>
    </div>
  </div>
</div>

{{-- Modal JS --}}
<script>
    const damageModal = document.getElementById('viewDamageModal');
    damageModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const part = button.getAttribute('data-part');
        const uploads = @json($damageUploads);

        const file = uploads[part]?.[0]?.file_name;
        if (file) {
            damageModal.querySelector('#damagePreviewImage').src = "{{ asset('storage') }}/" + file;
            damageModal.querySelector('#damagePartLabel').innerText = part;
        } else {
            damageModal.querySelector('#damagePreviewImage').src = '';
            damageModal.querySelector('#damagePartLabel').innerText = 'No image found.';
        }
    });
</script>

@endsection
