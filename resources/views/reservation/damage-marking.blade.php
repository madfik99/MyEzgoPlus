@extends('layouts.main')

@section('page-title', __('Damage Marking'))
@section('page-breadcrumb', __('Damage Marking'))

@section('content')

@php
// Include your full $hotspotMap here (shortened for demo)
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
                        ['top' => '23.5%', 'left' => '50.5%', 'part' => 'Upper Body Left'],//new
                        ['top' => '57.5%', 'left' => '72.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '42.5%', 'left' => '22%', 'part' => 'Left Front Fender'],
                        ['top' => '57.5%', 'left' => '19.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '40.5%', 'part' => 'Front Left Door'], //new
                        ['top' => '45%', 'left' => '60.5%', 'part' => 'Rear Left Door'], //new
                        ['top' => '42.5%', 'left' => '78.5%', 'part' => 'Left Rear Fender'], //new
                    ],
                    'rear' => [
                        ['top' => '22.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '40%', 'left' => '80%', 'part' => 'Rear Right Headlight'],
                        ['top' => '40%', 'left' => '15.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '66%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '40%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '23.5%', 'left' => '45.5%', 'part' => 'Upper Body Right'], //new
                        ['top' => '57.5%', 'left' => '73.5%', 'part' => 'Front Right Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Right Skirt'],
                        ['top' => '42.5%', 'left' => '75%', 'part' => 'Right Front Fender'],
                        ['top' => '57.5%', 'left' => '20.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '55.5%', 'part' => 'Front Right Door'], //new
                        ['top' => '45%', 'left' => '35.5%', 'part' => 'Rear Right Door'], //new
                        ['top' => '42.5%', 'left' => '18.5%', 'part' => 'Right Rear Fender'], //new
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
                        ['top' => '25.5%', 'left' => '55.5%', 'part' => 'Upper Body Left'],  //new
                        ['top' => '55.5%', 'left' => '78.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '58%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '43.5%', 'left' => '20%', 'part' => 'Left Front Fender'],
                        ['top' => '55.5%', 'left' => '15.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '40.5%', 'part' => 'Front Left Door'], //new
                        ['top' => '45%', 'left' => '60.5%', 'part' => 'Rear Left Door'], //new
                        ['top' => '43.5%', 'left' => '80.5%', 'part' => 'Left Rear Fender'], //new
                    ],
                    'rear' => [
                        ['top' => '30.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '42.5%', 'left' => '80.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '42.5%', 'left' => '13.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '63%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '25.5%', 'left' => '38.5%', 'part' => 'Upper Body Right'], //new
                        ['top' => '55.5%', 'left' => '76.5%', 'part' => 'Front Right Tire'],
                        ['top' => '57%', 'left' => '45%', 'part' => 'Right Skirt'],
                        ['top' => '43.5%', 'left' => '75%', 'part' => 'Right Front Fender'],
                        ['top' => '55.5%', 'left' => '16.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '50.5%', 'part' => 'Front Right Door'], //new
                        ['top' => '45%', 'left' => '30.5%', 'part' => 'Rear Right Door'], //new
                        ['top' => '43.5%', 'left' => '13.5%', 'part' => 'Left Rear Fender'], //new
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
                        ['top' => '20.5%', 'left' => '55.5%', 'part' => 'Upper Body Left'], //new
                        ['top' => '60.5%', 'left' => '74.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '60%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '40.5%', 'left' => '20%', 'part' => 'Left Front Fender'],
                        ['top' => '60.5%', 'left' => '18.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '40.5%', 'part' => 'Front Left Door'], //new
                        ['top' => '45%', 'left' => '60.5%', 'part' => 'Rear Left Door'], //new
                        ['top' => '40.5%', 'left' => '80.5%', 'part' => 'Left Rear Fender'], //new
                    ],
                    'rear' => [
                        ['top' => '28.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '39.5%', 'left' => '78.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '39.5%', 'left' => '13.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '60%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '20.5%', 'left' => '37.5%', 'part' => 'Upper Body Right'], //new
                        ['top' => '60.5%', 'left' => '78.5%', 'part' => 'Front Right Tire'],
                        ['top' => '60%', 'left' => '50%', 'part' => 'Right Skirt'],
                        ['top' => '40.5%', 'left' => '75%', 'part' => 'Right Front Fender'],
                        ['top' => '60.5%', 'left' => '20.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '60.5%', 'part' => 'Front Right Door'], //new
                        ['top' => '45%', 'left' => '40.5%', 'part' => 'Rear Right Door'], //new
                        ['top' => '40.5%', 'left' => '15.5%', 'part' => 'Right Rear Fender'], //new
                    ],
                    
                ],
                'van' => [
                    'front' => [
                        ['top' => '5%', 'left' => '48%', 'part' => 'Front Roof'], //new
                        ['top' => '20%', 'left' => '48%', 'part' => 'Front Glass'],
                        ['top' => '45%', 'left' => '80%', 'part' => 'Right Headlight'],
                        ['top' => '28%', 'left' => '6%', 'part' => 'Left Side Mirror'],
                        ['top' => '28%', 'left' => '90%', 'part' => 'Right Side Mirror'],
                        ['top' => '45%', 'left' => '15%', 'part' => 'Left Headlight'],
                        ['top' => '70%', 'left' => '48%', 'part' => 'Bumper'],
                        ['top' => '38%', 'left' => '48%', 'part' => 'Front Hood'],
                    ],
                    'left' => [
                        ['top' => '20.5%', 'left' => '55.5%', 'part' => 'Upper Body Left'], //new
                        ['top' => '63.5%', 'left' => '75.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '64%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '45.5%', 'left' => '20%', 'part' => 'Left Front Fender'],
                        ['top' => '63.5%', 'left' => '20.5%', 'part' => 'Front Left Tire'],
                        ['top' => '45%', 'left' => '35.5%', 'part' => 'Front Left Door'], //new
                        ['top' => '45%', 'left' => '55.5%', 'part' => 'Rear Left Door'], //new
                        ['top' => '45.5%', 'left' => '77.5%', 'part' => 'Left Rear Fender'], //new
                    ],
                    'rear' => [
                        ['top' => '20.5%', 'left' => '47.5%', 'part' => 'Rear Roof'], //new
                        ['top' => '30.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '45.5%', 'left' => '80.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '45.5%', 'left' => '12.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '63%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '45%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '20.5%', 'left' => '38.5%', 'part' => 'Upper Body Right'], //new
                        ['top' => '63.5%', 'left' => '75.5%', 'part' => 'Front Right Tire'],
                        ['top' => '64%', 'left' => '50%', 'part' => 'Right Skirt'],
                        ['top' => '45.5%', 'left' => '77%', 'part' => 'Right Front Fender'],
                        ['top' => '63.5%', 'left' => '20.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '45%', 'left' => '60.5%', 'part' => 'Front Right Door'], //new
                        ['top' => '45%', 'left' => '35.5%', 'part' => 'Rear Right Door'], //new
                        ['top' => '45.5%', 'left' => '18.5%', 'part' => 'Right Rear Fender'], //new
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
                        ['top' => '19.5%', 'left' => '50.5%', 'part' => 'Upper Body Left'], //new
                        ['top' => '60.5%', 'left' => '71.5%', 'part' => 'Rear Left Tire'],
                        ['top' => '62%', 'left' => '45%', 'part' => 'Left Skirt'],
                        ['top' => '45.5%', 'left' => '18%', 'part' => 'Left Front Fender'],
                        ['top' => '60.5%', 'left' => '17.5%', 'part' => 'Front Left Tire'],
                        ['top' => '48%', 'left' => '35.5%', 'part' => 'Front Left Door'], //new
                        ['top' => '48%', 'left' => '60.5%', 'part' => 'Rear Left Door'], //new
                        ['top' => '45.5%', 'left' => '80.5%', 'part' => 'Left Rear Fender'], //new
                    ],
                    'rear' => [
                        ['top' => '28.5%', 'left' => '47.5%', 'part' => 'Rear Glass'],
                        ['top' => '42.5%', 'left' => '80.5%', 'part' => 'Rear Right Headlight'],
                        ['top' => '42.5%', 'left' => '12.5%', 'part' => 'Rear Left Headlight'],
                        ['top' => '63%', 'left' => '47.5%', 'part' => 'Rear Bumper'],
                        ['top' => '53%', 'left' => '47.5%', 'part' => 'Rear Bonnet'],
                    ],
                    'right' => [
                        ['top' => '19.5%', 'left' => '45.5%', 'part' => 'Upper Body Right'], //new
                        ['top' => '60.5%', 'left' => '77.5%', 'part' => 'Front Right Tire'],
                        ['top' => '62%', 'left' => '50%', 'part' => 'Right Skirt'],
                        ['top' => '45.5%', 'left' => '77%', 'part' => 'Right Front Fender'],
                        ['top' => '60.5%', 'left' => '23.5%', 'part' => 'Rear Right Tire'],
                        ['top' => '48%', 'left' => '60.5%', 'part' => 'Front Right Door'], //new
                        ['top' => '48%', 'left' => '35.5%', 'part' => 'Rear Right Door'], //new
                        ['top' => '45.5%', 'left' => '18.5%', 'part' => 'Right Rear Fender'], //new
                    ],
                    
                ],
                
            ];
@endphp

<div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Hotspot Damage Marking</h5>
    <a href="{{ route('reservation.view', $pickup->id) }}" class="btn btn-secondary">Back</a>
  </div>


  <div class="card-body">
    {{-- Debug info --}}
    {{-- <div class="mb-3 mt-3">
      <h5>Layout Type: <code>{{ $layoutType }}</code></h5>
      <p>Vehicle: {{ $pickup->vehicle->class->class_name ?? 'N/A' }}</p>
    </div> --}}

    @php
      $sides = [
          'front' => 'Front Layout',
          'left'  => 'Left Side Layout',
          'rear'  => 'Rear Layout',
          'right' => 'Right Side Layout'
      ];
      $sideGroups = array_chunk(array_keys($sides), 2); // 2 per row
    @endphp

    @foreach($sideGroups as $group)
      <div class="row gy-4 mb-4">
        @foreach($group as $side)
          <div class="col-12 col-md-6 d-flex">
            <div class="card w-100 h-100">
              <div class="card-header">
                <h5 class="mt-2">{{ $sides[$side] }}</h5>
              </div>

              <div class="card-body d-flex flex-column align-items-start">
                <p class="text-muted">Tap a hotspot to view uploaded damage photo.</p>

                @php
                  $imageName = in_array($side, ['left', 'right'])
                      ? "{$side}_side_layout.png"
                      : "{$side}_layout.png";
                @endphp

                {{-- Layout Image with Hotspots --}}
                <div class="position-relative layout-stage-block my-3"
                     style="height: 350px; max-width: 600px; margin: auto; overflow: hidden;">
                    <img src="{{ asset('storage/layout/' . $layoutType . '/' . $imageName) }}" alt="Car Layout" class="img-fluid h-100 w-100 object-fit-cover">


                  @foreach($hotspotMap[$layoutType][$side] ?? [] as $hotspot)
                    @php
                      $part   = $hotspot['part'];
                      $upload = $damageUploads->get($part)?->first();
                    @endphp
                    <button type="button"
                            class="position-absolute btn btn-outline-primary rounded-circle"
                            style="top: {{ $hotspot['top'] }}; left: {{ $hotspot['left'] }}; width: 16px; height: 16px; padding: 0; font-size: 10px;"
                            data-bs-toggle="modal"
                            data-bs-target="#viewDamageModal"
                            data-part="{{ $part }}">●</button>
                  @endforeach

                </div>

                {{-- Damage Image Cards --}}
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 preview-grid w-100 mt-3">
                  @foreach($hotspotMap[$layoutType][$side] ?? [] as $hotspot)
                    @php
                      $part   = $hotspot['part'];
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
<style>
  /* Ensures a neat gap below the layout image and a subtle divider above previews */
  .layout-stage-block {
    margin-bottom: 1.25rem;              /* gap under the layout image */
  }
  .preview-grid {
    margin-top: .75rem;                  /* extra spacing */
    padding-top: .75rem;
    border-top: 1px solid rgba(255,255,255,.08); /* subtle separator on dark theme */
  }

  /* Optional: make sure the previews always span full width of card body */
  .preview-grid.w-100 { width: 100% !important; }
</style>

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
