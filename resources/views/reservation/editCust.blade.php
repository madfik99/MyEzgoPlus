@extends('layouts.main')

@php
    // Compute dynamic title once and reuse
    $isExist   = ($type === 'exist');
    $pageTitle = $isExist ? __('Edit Current Customer') : __('Change Customer');
@endphp

@section('page-title')
    {{ $pageTitle }}
@endsection

@section('page-breadcrumb')
    {{ $pageTitle }}
@endsection

@section('content')
<div class="container">

  {{-- Flash messages --}}
  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Optional: NRIC blacklisted banner (if you still surface this) --}}
  @isset($nric_blacklisted)
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <i class="fa fa-bell faa-ring animated" style="font-size:16px;color:#f95e5e"></i>
      <span>
        {{ __('ALERT: Customer NRIC :nric is blacklisted.', ['nric' => $nric_blacklisted]) }}
      </span>
    </div>
  @endisset

  {{-- Toggle buttons (navigate to the same page with different type) --}}
  <div class="d-flex justify-content-center gap-2 flex-wrap mb-3 mt-3">
    <a href="{{ route('customers.edit', ['booking' => $booking->id, 'type' => 'exist']) }}"
       class="btn {{ $isExist ? 'btn-secondary' : 'btn-outline-secondary' }}">
      @if (request()->has('nric_blacklisted'))
        <i class="fa fa-bell faa-ring animated" style="font-size:16px;color:#f95e5e"></i>
      @endif
      {{ __('Current Customer') }}
    </a>

    <a href="{{ route('customers.edit', ['booking' => $booking->id, 'type' => 'change']) }}"
       class="btn {{ !$isExist ? 'btn-secondary' : 'btn-outline-secondary' }}">
      @if (request()->has('nric_new'))
        <i class="fa fa-bell faa-ring animated" style="font-size:16px;color:#f95e5e"></i>
      @endif
      {{ __('Change Customer') }}
    </a>

    {{-- (Optional) Button you had for creating a license update link --}}
    {{-- <button type="button"
            class="btn btn-outline-secondary"
            data-bs-toggle="modal"
            data-bs-target="#linkModal"
            onclick="createUpdateLicense()">
      <i class="fa fa-external-link"></i>&nbsp;{{ __('Create link for customer update license') }}
    </button> --}}
  </div>

  {{-- Body swaps based on $type --}}
  @if ($isExist)
      <form action="{{ route('customers.updateExisting', ['booking' => $booking->id]) }}"
      method="POST"
      enctype="multipart/form-data"
      id="editReservation"
      class="form-horizontal form-label-left">
  @csrf

  <div class="card p-3 mb-3">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
            <div class="card-header">
                <b><h5>Customer Information</h5></b>
                <span class="text-primary">This section is to edit current customer. If you're changing to a new customer, please choose "Change Customer" instead.</span>
            </div>
            <div class="card-body">
                {{-- NRIC (editable only when blacklist banner flow is active) --}}
                <div class="form-group mt-3">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="nric_no">NRIC No</label>
                    <div class="col-md-6 col-sm-6 col-xs-12">
                    <input type="text"
                            class="form-control @error('nric_no') is-invalid @enderror"
                            placeholder="NRIC No"
                            name="nric_no"
                            id="nric_no"
                            value="{{ old('nric_no', request('nric_blacklisted', optional($customer)->nric_no)) }}"
                            {{ request()->has('nric_blacklisted') ? '' : 'disabled' }}>
                    @if (request()->has('nric_blacklisted'))
                        <small class="text-danger">ALERT: Customer has been blacklisted **</small>
                    @endif
                    @error('nric_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- NRIC Type --}}
                <div class="form-group">
                    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="nric_type">NRIC Type</label>
                    <div class="col-md-6 col-sm-6 col-xs-12">
                    <select class="form-control @error('nric_type') is-invalid @enderror" name="nric_type" id="nric_type" required>
                        <option value="">-- PLEASE SELECT --</option>
                        @php $nricType = old('nric_type', optional($customer)->nric_type); @endphp
                        <option value="ic_new"    {{ $nricType==='ic_new' ? 'selected' : '' }}>New IC Number</option>
                        <option value="ic_old"    {{ $nricType==='ic_old' ? 'selected' : '' }}>Old IC Number</option>
                        <option value="ic_army"   {{ $nricType==='ic_army' ? 'selected' : '' }}>Army ID</option>
                        <option value="ic_police" {{ $nricType==='ic_police' ? 'selected' : '' }}>Police ID</option>
                        <option value="passport"  {{ $nricType==='passport' ? 'selected' : '' }}>Passport</option>
                    </select>
                    @error('nric_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Title --}}
                <div class="form-group">
                    <label class="control-label col-md-3">Title</label>
                    <div class="col-md-6">
                    @php $title = old('title', optional($customer)->title); @endphp
                    <select name="title" id="title" class="form-control @error('title') is-invalid @enderror" required>
                        <option value="Mr."   {{ $title==='Mr.' ? 'selected' : '' }}>Mr.</option>
                        <option value="Mrs."  {{ $title==='Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        <option value="Miss." {{ $title==='Miss.' ? 'selected' : '' }}>Miss.</option>
                    </select>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Names --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="firstname">First Name</label>
                    <div class="col-md-6">
                    <input type="text" class="form-control @error('firstname') is-invalid @enderror"
                            name="firstname" id="firstname"
                            value="{{ old('firstname', optional($customer)->firstname) }}" required>
                    @error('firstname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="lastname">Last Name</label>
                    <div class="col-md-6">
                    <input type="text" class="form-control @error('lastname') is-invalid @enderror"
                            name="lastname" id="lastname"
                            value="{{ old('lastname', optional($customer)->lastname) }}" required>
                    @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Gender --}}
                @php $gender = old('gender', optional($customer)->gender); @endphp
                <div class="form-group">
                    <label class="control-label col-md-3">Gender</label>
                    <div class="col-md-6">
                    <label class="me-3">
                        <input type="radio" name="gender" value="Male"   {{ $gender==='Male' ? 'checked' : '' }} required> Male
                    </label>
                    <label>
                        <input type="radio" name="gender" value="Female" {{ $gender==='Female' ? 'checked' : '' }}> Female
                    </label>
                    @error('gender') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Race --}}
                @php $race = old('race', optional($customer)->race); @endphp
                <div class="form-group">
                    <label class="control-label col-md-3" for="race">Race</label>
                    <div class="col-md-6">
                    <select name="race" id="race" class="form-control">
                        <option value="">-- PLEASE SELECT --</option>
                        <option value="malay"   {{ $race==='malay' ? 'selected' : '' }}>Malay</option>
                        <option value="chinese" {{ $race==='chinese' ? 'selected' : '' }}>Chinese</option>
                        <option value="indian"  {{ $race==='indian' ? 'selected' : '' }}>Indian</option>
                        <option value="others"  {{ $race==='others' ? 'selected' : '' }}>Others</option>
                    </select>
                    </div>
                </div>

                {{-- DOB --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="dob">Date of Birth</label>
                    <div class="col-md-6">
                    <input type="date" class="form-control"
                            name="dob" id="dob"
                            value="{{ old('dob', optional($customer)->dob ? \Carbon\Carbon::parse($customer->dob)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                {{-- Phone / Email --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="phone_no">Phone No</label>
                    <div class="col-md-6">
                    <input type="text" class="form-control @error('phone_no') is-invalid @enderror"
                            name="phone_no" id="phone_no"
                            value="{{ old('phone_no', optional($customer)->phone_no) }}" required>
                    @error('phone_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="phone_no2">Phone No 2 (Optional)</label>
                    <div class="col-md-6">
                    <input type="text" class="form-control"
                            name="phone_no2" id="phone_no2"
                            value="{{ old('phone_no2', optional($customer)->phone_no2) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="email">Email</label>
                    <div class="col-md-6">
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                            name="email" id="email"
                            value="{{ old('email', optional($customer)->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- License --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="license_no">License Number</label>
                    <div class="col-md-6">
                    <input type="text" class="form-control"
                            name="license_no" id="license_no"
                            value="{{ old('license_no', optional($customer)->license_no) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="license_exp">License Expired</label>
                    <div class="col-md-6">
                    @php
                        $exp = optional($customer)->license_exp;
                        $licenseExp = ($exp && $exp !== '0000-00-00' && $exp !== '1970-01-01')
                            ? \Carbon\Carbon::parse($exp)->format('Y-m-d') : '';
                    @endphp
                    <input type="date" class="form-control"
                            name="license_exp" id="license_exp"
                            value="{{ old('license_exp', $licenseExp) }}">
                    </div>
                </div>

                {{-- Identity / Utility / Working Photos --}}
                @php
                // filenames coming from UploadData (controller)
                $front   = $idPhotoFront ?? null;
                $selfie  = $selfieNric ?? null;
                $licF    = $licenseFront ?? null;
                $licB    = $licenseBack ?? null;
                $util    = $utilityPhoto ?? null;
                $work    = $workingPhoto ?? null;

                // show the “existing photos” UI if at least one file exists
                $hasIdPhotos = $front || $selfie || $licF || $licB || $util || $work;

                // helper to build the public path
                $imgBase = 'assets/img/customer/';
                @endphp

                @if ($hasIdPhotos)
                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo1">NRIC & License Photo (front)</label>
                    <div class="col-md-6">
                    @if ($front)
                        <img src="{{ asset($imgBase.$front) }}?nocache={{ time() }}" style="height:190px;">
                    @endif
                    <input type="file" class="form-control mt-2" name="identity_photo[]" id="identity_photo1" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo2">NRIC (selfie)</label>
                    <div class="col-md-6">
                    @if ($selfie)
                        <img src="{{ asset($imgBase.$selfie) }}?nocache={{ time() }}" style="height:190px;">
                    @endif
                    <input type="file" class="form-control mt-2" name="identity_photo[]" id="identity_photo2" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo3">License Photo (front)</label>
                    <div class="col-md-6">
                    @if ($licF)
                        <img src="{{ asset($imgBase.$licF) }}?nocache={{ time() }}" style="height:190px;">
                    @endif
                    <input type="file" class="form-control mt-2" name="identity_photo[]" id="identity_photo3" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo4">License Photo (back)</label>
                    <div class="col-md-6">
                    @if ($licB)
                        <img src="{{ asset($imgBase.$licB) }}?nocache={{ time() }}" style="height:190px;">
                    @endif
                    <input type="file" class="form-control mt-2" name="identity_photo[]" id="identity_photo4" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo5">Utility Photo</label>
                    <div class="col-md-6">
                    @if ($util)
                        <img src="{{ asset($imgBase.$util) }}?nocache={{ time() }}" style="height:190px;">
                    @endif
                    <input type="file" class="form-control mt-2" name="identity_photo[]" id="identity_photo5" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo6">Working Photo</label>
                    <div class="col-md-6">
                    @if ($work)
                        <img src="{{ asset($imgBase.$work) }}?nocache={{ time() }}" style="height:190px;">
                    @endif
                    <input type="file" class="form-control mt-2" name="identity_photo[]" id="identity_photo6" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>
                @else
                {{-- No existing images: show required uploads for the first two --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo1">NRIC & License Photo (front)</label>
                    <div class="col-md-6">
                    <input type="file" class="btn btn-control" name="identity_photo[]" id="identity_photo1" accept=".jpg,.jpeg,.png,.gif" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo2">NRIC & License Photo (back)</label>
                    <div class="col-md-6">
                    <input type="file" class="btn btn-control" name="identity_photo[]" id="identity_photo2" accept=".jpg,.jpeg,.png,.gif" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo3">Utility Bills (Optional)</label>
                    <div class="col-md-6">
                    <input type="file" class="btn btn-control" name="identity_photo[]" id="identity_photo3" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3" for="identity_photo4">Working Card (Optional)</label>
                    <div class="col-md-6">
                    <input type="file" class="btn btn-control" name="identity_photo[]" id="identity_photo4" accept=".jpg,.jpeg,.png,.gif">
                    </div>
                </div>
                @endif


                {{-- Address --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="address">Address</label>
                    <div class="col-md-6">
                    <input class="form-control" name="address" id="address"
                            value="{{ old('address', optional($customer)->address) }}">
                    </div>
                </div>

                {{-- Postcode / City / Country --}}
                <div class="form-group">
                    <label class="control-label col-md-3" for="postcode">Postcode</label>
                    <div class="col-md-6">
                    <input type="text" class="form-control" name="postcode" id="postcode"
                            value="{{ old('postcode', optional($customer)->postcode) }}">
                    </div>
                </div>

                @php $city = old('city', optional($customer)->city); @endphp
                <div class="form-group">
                    <label class="control-label col-md-3" for="city">City</label>
                    <div class="col-md-6">
                    <select name="city" id="city" class="form-control">
                        <option value="">Please Select</option>
                        @foreach ([
                        'Perlis','Kedah','Pulau Pinang','Perak','Selangor',
                        'Wilayah Persekutuan Kuala Lumpur','Wilayah Persekutuan Putrajaya',
                        'Melaka','Negeri Sembilan','Johor','Pahang','Terengganu',
                        'Kelantan','Sabah','Sarawak'
                        ] as $opt)
                        <option value="{{ $opt }}" {{ $city===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    </div>
                </div>

                @php $country = old('country', optional($customer)->country ?? 'MY'); @endphp
                <div class="form-group">
                    <label class="control-label col-md-3" for="country">Country</label>
                    <div class="col-md-6">
                    <select name="country" id="country" class="form-control">
                        <optgroup label="Alaskan/Hawaiian Time Zone">
                        <option value="AK" {{ $country==='AK' ? 'selected' : '' }}>Alaska</option>
                        <option value="HI" {{ $country==='HI' ? 'selected' : '' }}>Hawaii</option>
                        <option value="MY" {{ $country==='MY' ? 'selected' : '' }}>Malaysia</option>
                        </optgroup>
                        <optgroup label="Pacific Time Zone">
                        <option value="CA" {{ $country==='CA' ? 'selected' : '' }}>California</option>
                        <option value="NV" {{ $country==='NV' ? 'selected' : '' }}>Nevada</option>
                        <option value="OR" {{ $country==='OR' ? 'selected' : '' }}>Oregon</option>
                        <option value="WA" {{ $country==='WA' ? 'selected' : '' }}>Washington</option>
                        </optgroup>
                        <optgroup label="Mountain Time Zone">
                        <option value="AZ" {{ $country==='AZ' ? 'selected' : '' }}>Arizona</option>
                        <option value="CO" {{ $country==='CO' ? 'selected' : '' }}>Colorado</option>
                        <option value="ID" {{ $country==='ID' ? 'selected' : '' }}>Idaho</option>
                        <option value="MT" {{ $country==='MT' ? 'selected' : '' }}>Montana</option>
                        <option value="NE" {{ $country==='NE' ? 'selected' : '' }}>Nebraska</option>
                        <option value="NM" {{ $country==='NM' ? 'selected' : '' }}>New Mexico</option>
                        <option value="ND" {{ $country==='ND' ? 'selected' : '' }}>North Dakota</option>
                        <option value="UT" {{ $country==='UT' ? 'selected' : '' }}>Utah</option>
                        <option value="WY" {{ $country==='WY' ? 'selected' : '' }}>Wyoming</option>
                        </optgroup>
                        <optgroup label="Central Time Zone">
                        <option value="AL" {{ $country==='AL' ? 'selected' : '' }}>Alabama</option>
                        <option value="AR" {{ $country==='AR' ? 'selected' : '' }}>Arkansas</option>
                        <option value="IL" {{ $country==='IL' ? 'selected' : '' }}>Illinois</option>
                        <option value="IA" {{ $country==='IA' ? 'selected' : '' }}>Iowa</option>
                        <option value="KS" {{ $country==='KS' ? 'selected' : '' }}>Kansas</option>
                        <option value="KY" {{ $country==='KY' ? 'selected' : '' }}>Kentucky</option>
                        <option value="LA" {{ $country==='LA' ? 'selected' : '' }}>Louisiana</option>
                        <option value="MN" {{ $country==='MN' ? 'selected' : '' }}>Minnesota</option>
                        <option value="MS" {{ $country==='MS' ? 'selected' : '' }}>Mississippi</option>
                        <option value="MO" {{ $country==='MO' ? 'selected' : '' }}>Missouri</option>
                        <option value="OK" {{ $country==='OK' ? 'selected' : '' }}>Oklahoma</option>
                        <option value="SD" {{ $country==='SD' ? 'selected' : '' }}>South Dakota</option>
                        <option value="TX" {{ $country==='TX' ? 'selected' : '' }}>Texas</option>
                        <option value="TN" {{ $country==='TN' ? 'selected' : '' }}>Tennessee</option>
                        <option value="WI" {{ $country==='WI' ? 'selected' : '' }}>Wisconsin</option>
                        </optgroup>
                        <optgroup label="Eastern Time Zone">
                        <option value="CT" {{ $country==='CT' ? 'selected' : '' }}>Connecticut</option>
                        <option value="DE" {{ $country==='DE' ? 'selected' : '' }}>Delaware</option>
                        <option value="FL" {{ $country==='FL' ? 'selected' : '' }}>Florida</option>
                        <option value="GA" {{ $country==='GA' ? 'selected' : '' }}>Georgia</option>
                        <option value="IN" {{ $country==='IN' ? 'selected' : '' }}>Indiana</option>
                        <option value="ME" {{ $country==='ME' ? 'selected' : '' }}>Maine</option>
                        <option value="MD" {{ $country==='MD' ? 'selected' : '' }}>Maryland</option>
                        <option value="MA" {{ $country==='MA' ? 'selected' : '' }}>Massachusetts</option>
                        <option value="MI" {{ $country==='MI' ? 'selected' : '' }}>Michigan</option>
                        <option value="NH" {{ $country==='NH' ? 'selected' : '' }}>New Hampshire</option>
                        <option value="NJ" {{ $country==='NJ' ? 'selected' : '' }}>New Jersey</option>
                        <option value="NY" {{ $country==='NY' ? 'selected' : '' }}>New York</option>
                        <option value="NC" {{ $country==='NC' ? 'selected' : '' }}>North Carolina</option>
                        <option value="OH" {{ $country==='OH' ? 'selected' : '' }}>Ohio</option>
                        <option value="PA" {{ $country==='PA' ? 'selected' : '' }}>Pennsylvania</option>
                        <option value="RI" {{ $country==='RI' ? 'selected' : '' }}>Rhode Island</option>
                        <option value="SC" {{ $country==='SC' ? 'selected' : '' }}>South Carolina</option>
                        <option value="VT" {{ $country==='VT' ? 'selected' : '' }}>Vermont</option>
                        <option value="VA" {{ $country==='VA' ? 'selected' : '' }}>Virginia</option>
                        <option value="WV" {{ $country==='WV' ? 'selected' : '' }}>West Virginia</option>
                        </optgroup>
                    </select>
                    </div>
                </div>
        </div>
  </div>

  {{-- Additional Driver --}}
  {{-- <b>Additional Driver Information (Optional)</b>
  <div class="form-group mt-2">
    <label class="control-label col-md-3" for="drv_name">Name</label>
    <div class="col-md-6">
      <input type="text" class="form-control" name="drv_name" id="drv_name"
             value="{{ old('drv_name', optional($customer)->drv_name) }}">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-md-3" for="drv_nric">IC No. / Passport</label>
    <div class="col-md-6">
      <input type="text" class="form-control" name="drv_nric" id="drv_nric"
             value="{{ old('drv_nric', optional($customer)->drv_nric) }}">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-md-3" for="drv_address">Address</label>
    <div class="col-md-6">
      <input class="form-control" name="drv_address" id="drv_address"
             value="{{ old('drv_address', optional($customer)->drv_address) }}">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-md-3" for="drv_phoneno">Phone No</label>
    <div class="col-md-6">
      <input type="text" class="form-control" name="drv_phoneno" id="drv_phoneno"
             value="{{ old('drv_phoneno', optional($customer)->drv_phoneno) }}">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-md-3" for="drv_license_no">License No.</label>
    <div class="col-md-6">
      <input class="form-control" type="text" name="drv_license_no" id="drv_license_no"
             value="{{ old('drv_license_no', optional($customer)->drv_license_no) }}">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-md-3" for="drv_license_exp">License Expired</label>
    <div class="col-md-6">
      <input class="form-control" type="date" name="drv_license_exp" id="drv_license_exp"
             value="{{ old('drv_license_exp', optional($customer)->drv_license_exp ? \Carbon\Carbon::parse($customer->drv_license_exp)->format('Y-m-d') : '') }}">
    </div>
  </div> --}}

  {{-- Reference --}}
  <div class="card">
    <div class="card-header">
      <b><h5>Reference Information</h5></b>
    </div>
    <div class="card-body">
  
        @php $refRel = old('ref_relationship', optional($customer)->ref_relationship); @endphp
        <div class="form-group mt-2">
            <label class="control-label col-md-3" for="ref_relationship">Reference Relationship</label>
            <div class="col-md-6">
            <select name="ref_relationship" id="ref_relationship" class="form-control" required>
                <option value="">-- Please Select --</option>
                @foreach (['Husband','Wife','Mother','Father','Brother','Sister','Son','Daughter','Guardian','Company'] as $opt)
                <option value="{{ $opt }}" {{ $refRel===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-md-3" for="ref_name">Reference Name</label>
            <div class="col-md-6">
            <input type="text" class="form-control" name="ref_name" id="ref_name"
                    value="{{ old('ref_name', optional($customer)->ref_name) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-md-3" for="ref_address">Reference Address</label>
            <div class="col-md-6">
            <input class="form-control" name="ref_address" id="ref_address"
                    value="{{ old('ref_address', optional($customer)->ref_address) }}">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-md-3" for="ref_phoneno">Reference Phone No</label>
            <div class="col-md-6">
            <input type="text" class="form-control" name="ref_phoneno" id="ref_phoneno"
                    value="{{ old('ref_phoneno', optional($customer)->ref_phoneno) }}">
            </div>
        </div>
    </div>
  </div>

    <div class="card">
    <div class="card-header">
      <h5><b>Payment Information</b></h5>
    </div>
    <div class="card-body">
        {{-- Payment --}}
        @php $lock = $booking->payment_status === 'FullRental'; @endphp
        
        <div class="form-group mt-2">
            <label class="control-label col-md-3" for="payment_amount">Payment Made (RM)</label>
            <div class="col-md-6">
            <input type="text" class="form-control" name="payment_amount" id="payment_amount"
                    value="{{ old('payment_amount', $booking->balance) }}">
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-md-3" for="payment_status">Agreement Status</label>
            <div class="col-md-6">
            @php $ps = old('payment_status', $booking->payment_status); @endphp
            <select name="payment_status" id="payment_status" class="form-control" {{ $lock ? 'disabled' : '' }}>
                <option value="">-- Please Select --</option>
                <option value="Unpaid"        {{ $ps==='Unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="Booking"       {{ $ps==='Booking' ? 'selected' : '' }}>Booking only</option>
                <option value="BookingRental" {{ $ps==='BookingRental' ? 'selected' : '' }}>Booking + Rental (Incomplete Payment)</option>
                <option value="FullRental"    {{ $ps==='FullRental' ? 'selected' : '' }}>Booking + Rental (Full Payment)</option>
            </select>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-md-3" for="deposit">Booking Fee (RM)</label>
            <div class="col-md-6">
            <input type="text" class="form-control" name="deposit" id="deposit"
                    value="{{ old('deposit', $booking->refund_dep) }}" {{ $lock ? 'disabled' : '' }} required>
            </div>
        </div>

        {{-- OPTIONAL “Booking Deposit in” + “Pickup” display (kept disabled like your page) --}}
        @php
            $booking_payment_status = optional($bookingDepositSale)->payment_status;
            $booking_payment_type   = optional($bookingDepositSale)->payment_type;
            $pickup_payment_status  = optional($pickupSale)->payment_status;
            $pickup_payment_type    = optional($pickupSale)->payment_type;
            $pickup_total_sale      = optional($pickupSale)->total_sale;
        @endphp

        <div class="form-group">
            <label class="control-label col-md-3" for="bookingPaymentStatus">Booking Payment Status</label>
            <div class="col-md-6">
            <select id="bookingPaymentStatus" class="form-control" disabled>
                <option value="" disabled>Please Select</option>
                @foreach (['Unpaid','Collect','Paid'] as $opt)
                <option {{ $booking_payment_status===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-md-3" for="bookingPaymentType">Booking Payment Type</label>
            <div class="col-md-6">
            <select id="bookingPaymentType" class="form-control" disabled>
                <option value="" disabled>Please Select</option>
                @foreach (['Collect','Unpaid','Online','Cash','Card','QRPay'] as $opt)
                <option {{ $booking_payment_type===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            </div>
        </div>

        @if($pickupSale)
            <div class="form-group">
            <label class="control-label col-md-3" for="pickupTotalSale">Pickup Amount (RM)</label>
            <div class="col-md-6">
                <input type="text" class="form-control" id="pickupTotalSale" value="{{ $pickup_total_sale }}" {{ $lock ? 'disabled' : '' }}>
            </div>
            </div>
            <div class="form-group">
            <label class="control-label col-md-3" for="pickupPaymentStatus">Pickup Payment Status</label>
            <div class="col-md-6">
                <select id="pickupPaymentStatus" class="form-control" {{ $lock ? 'disabled' : '' }}>
                <option value="" disabled>Please Select</option>
                @foreach (['Unpaid','Collect','Paid'] as $opt)
                    <option {{ $pickup_payment_status===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
                </select>
            </div>
            </div>
            <div class="form-group">
            <label class="control-label col-md-3" for="pickupPaymentType">Pickup Payment Type</label>
            <div class="col-md-6">
                <select id="pickupPaymentType" class="form-control" {{ $lock ? 'disabled' : '' }}>
                <option value="" disabled>Please Select</option>
                @foreach (['Collect','Unpaid','Online','Cash','Card','QRPay'] as $opt)
                    <option {{ $pickup_payment_type===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
                </select>
            </div>
            </div>
        @endif
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <b><h5>Survey</h5></b>
    </div>
    <div class="card-body">

        @php $survey_type = old('survey_type', optional($customer)->survey_type); @endphp
        <div class="form-group mt-2">
            <label class="control-label col-md-3" for="survey_type">Survey</label>
            <div class="col-md-6">
            <select name="survey_type" id="survey_type" class="form-control">
                @foreach (['Banner','Bunting','Facebook Ads','Freind','Google Ads','Magazine','Others'] as $opt)
                <option value="{{ $opt }}" {{ $survey_type===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            </div>
        </div>

    </div>
  </div>

        {{-- Read-only username + hidden customer_id --}}
        <div class="form-group">
            <label class="control-label col-md-3">&nbsp;</label>
            <div class="col-md-6">
            <input class="form-control" value="{{ auth()->user()->name ?? '' }}" disabled hidden>
            <input type="hidden" name="customer_id" value="{{ old('customer_id', optional($customer)->id) }}">
            </div>
        </div>

        <div class="modal-footer px-0">
            <a href="{{ route('reservation.view', ['booking_id' => $booking->id]) }}" class="btn btn-default">Back</a>
            <button class="btn btn-success" name="btn_registered_cust" type="submit">Update</button>
        </div>
</form>

 @else
<form action="{{ route('customers.updateChange', ['booking' => $booking->id]) }}"
      method="POST" class="form-horizontal form-label-left">
  @csrf

  <div class="card p-3 mb-3">
    <div class="card-header"><h5><b>Change Customer</b></h5></div>
    <div class="card-body">

      {{-- NRIC --}}
      <div class="form-group">
        <label class="control-label col-md-3" for="nric_no">NRIC No</label>
        <div class="col-md-6">
          <input type="text" class="form-control @error('nric_no') is-invalid @enderror"
                 name="nric_no" id="nric_no" value="{{ old('nric_no') }}" required>
          @error('nric_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- NRIC Type --}}
      <div class="form-group">
        <label class="control-label col-md-3" for="nric_type">NRIC Type</label>
        <div class="col-md-6">
          <select class="form-control @error('nric_type') is-invalid @enderror" name="nric_type" id="nric_type" required>
            <option value="">-- PLEASE SELECT --</option>
            <option value="ic_new"    {{ old('nric_type')==='ic_new' ? 'selected' : '' }}>New IC Number</option>
            <option value="ic_old"    {{ old('nric_type')==='ic_old' ? 'selected' : '' }}>Old IC Number</option>
            <option value="ic_army"   {{ old('nric_type')==='ic_army' ? 'selected' : '' }}>Army ID</option>
            <option value="ic_police" {{ old('nric_type')==='ic_police' ? 'selected' : '' }}>Police ID</option>
            <option value="passport"  {{ old('nric_type')==='passport' ? 'selected' : '' }}>Passport</option>
          </select>
          @error('nric_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- Title / First / Last Name --}}
      <div class="form-group">
        <label class="control-label col-md-3">Title</label>
        <div class="col-md-6">
          <select name="title" id="title" class="form-control @error('title') is-invalid @enderror" required>
            <option value="">-- Please Select --</option>
            <option value="Mr."   {{ old('title')==='Mr.' ? 'selected' : '' }}>Mr.</option>
            <option value="Mrs."  {{ old('title')==='Mrs.' ? 'selected' : '' }}>Mrs.</option>
            <option value="Miss." {{ old('title')==='Miss.' ? 'selected' : '' }}>Miss.</option>
          </select>
          @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-md-3" for="firstname">First Name</label>
        <div class="col-md-6">
          <input type="text" class="form-control @error('firstname') is-invalid @enderror"
                 name="firstname" id="firstname" value="{{ old('firstname') }}" required>
          @error('firstname') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-md-3" for="lastname">Last Name</label>
        <div class="col-md-6">
          <input type="text" class="form-control @error('lastname') is-invalid @enderror"
                 name="lastname" id="lastname" value="{{ old('lastname') }}" required>
          @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- Gender --}}
      <div class="form-group">
        <label class="control-label col-md-3">Gender</label>
        <div class="col-md-6">
          <label class="me-3"><input type="radio" name="gender" value="Male"   {{ old('gender')==='Male' ? 'checked' : '' }}> Male</label>
          <label><input type="radio" name="gender" value="Female" {{ old('gender')==='Female' ? 'checked' : '' }}> Female</label>
        </div>
      </div>

      {{-- Race / DOB --}}
      <div class="form-group">
        <label class="control-label col-md-3" for="race">Race</label>
        <div class="col-md-6">
          <select name="race" id="race" class="form-control">
            <option value="">-- Please Select --</option>
            @foreach (['malay','chinese','indian','others'] as $opt)
              <option value="{{ $opt }}" {{ old('race')===$opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="control-label col-md-3" for="dob">Date of Birth</label>
        <div class="col-md-6">
          <input type="date" class="form-control" name="dob" id="dob" value="{{ old('dob') }}">
        </div>
      </div>

      {{-- Phone / Email --}}
      <div class="form-group">
        <label class="control-label col-md-3" for="phone_no">Phone No</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="phone_no" id="phone_no" value="{{ old('phone_no') }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="phone_no2">Phone No 2 (Optional)</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="phone_no2" id="phone_no2" value="{{ old('phone_no2') }}">
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="email">Email</label>
        <div class="col-md-6">
          <input type="email" class="form-control" name="email" id="email" value="{{ old('email') }}">
        </div>
      </div>

      {{-- License --}}
      <div class="form-group">
        <label class="control-label col-md-3" for="license_no">License Number</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="license_no" id="license_no" value="{{ old('license_no') }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="license_exp">License Expired</label>
        <div class="col-md-6">
          <input type="date" class="form-control" name="license_exp" id="license_exp" value="{{ old('license_exp') }}" required>
        </div>
      </div>

      {{-- Address --}}
      <div class="form-group">
        <label class="control-label col-md-3" for="address">Address</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="address" id="address" value="{{ old('address') }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="postcode">Postcode</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="postcode" id="postcode" value="{{ old('postcode') }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="city">City</label>
        <div class="col-md-6">
          <select name="city" id="city" class="form-control" required>
            <option value="">-- Please Select --</option>
            @foreach (['Perlis','Kedah','Pulau Pinang','Perak','Selangor','Wilayah Persekutuan Kuala Lumpur','Wilayah Persekutuan Putrajaya','Melaka','Negeri Sembilan','Johor','Pahang','Terengganu','Kelantan','Sabah','Sarawak'] as $opt)
              <option value="{{ $opt }}" {{ old('city')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="country">Country</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="country" id="country" value="{{ old('country','Malaysia') }}" required>
        </div>
      </div>

      {{-- Reference --}}
      <hr>
      <h6><b>Reference Information</b></h6>
      <div class="form-group">
        <label class="control-label col-md-3" for="ref_relationship">Reference Relationship</label>
        <div class="col-md-6">
          <select name="ref_relationship" id="ref_relationship" class="form-control" required>
            <option value="">-- Please Select --</option>
            @foreach (['Husband','Wife','Mother','Father','Brother','Sister','Son','Daughter','Guardian','Company'] as $opt)
              <option value="{{ $opt }}" {{ old('ref_relationship')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="ref_name">Reference Name</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="ref_name" id="ref_name" value="{{ old('ref_name') }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="ref_address">Reference Address</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="ref_address" id="ref_address" value="{{ old('ref_address') }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="ref_phoneno">Reference Phone No</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="ref_phoneno" id="ref_phoneno" value="{{ old('ref_phoneno') }}" required>
        </div>
      </div>

      {{-- Payment --}}
      <hr>
      <h6><b>Payment Information</b></h6>
      <div class="form-group">
        <label class="control-label col-md-3" for="payment_amount">Payment Made (RM)</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="payment_amount" id="payment_amount" value="{{ old('payment_amount',$booking->balance) }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="payment_status">Agreement Status</label>
        <div class="col-md-6">
          <select name="payment_status" id="payment_status" class="form-control" required>
            <option value="">-- Please Select --</option>
            <option value="Unpaid"        {{ old('payment_status')==='Unpaid' ? 'selected' : '' }}>Unpaid</option>
            <option value="Booking"       {{ old('payment_status')==='Booking' ? 'selected' : '' }}>Booking only</option>
            <option value="BookingRental" {{ old('payment_status')==='BookingRental' ? 'selected' : '' }}>Booking + Rental (Incomplete Payment)</option>
            <option value="FullRental"    {{ old('payment_status')==='FullRental' ? 'selected' : '' }}>Booking + Rental (Full Payment)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="deposit">Booking Fee (RM)</label>
        <div class="col-md-6">
          <input type="text" class="form-control" name="deposit" id="deposit" value="{{ old('deposit',$booking->refund_dep) }}" required>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-md-3" for="refund_dep_payment">Booking Fee Status</label>
        <div class="col-md-6">
          <select name="refund_dep_payment" id="refund_dep_payment" class="form-control" required>
            <option value="">-- Please Select --</option>
            @foreach (['Collect','Unpaid','Cash','Online','Card','QRPay'] as $opt)
              <option value="{{ $opt }}" {{ old('refund_dep_payment')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- Survey --}}
      <hr>
      <h6><b>Survey</b></h6>
      <div class="form-group">
        <label class="control-label col-md-3" for="survey_type">Survey</label>
        <div class="col-md-6">
          <select name="survey_type" id="survey_type" class="form-control">
            <option value="">-- Please Select --</option>
            @foreach (['Banner','Bunting','Facebook Ads','Freind','Google Ads','Magazine','Others'] as $opt)
              <option value="{{ $opt }}" {{ old('survey_type')===$opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
          </select>
        </div>
      </div>

    </div>
  </div>

  <div class="modal-footer px-0">
    <a href="{{ route('reservation.view', ['booking_id' => $booking->id]) }}" class="btn btn-default">Back</a>
    <button class="btn btn-success" name="btn_change_cust" type="submit">Submit</button>
  </div>
</form>
@endif




</div>

<script>
(function () {
  const lookupUrl = "{{ route('customers.lookupByNric') }}";

  // list every field you auto-fill (except nric_no itself)
  const FIELD_IDS = [
    'nric_type','title','firstname','lastname','race','dob',
    'phone_no','phone_no2','email','license_no','license_exp',
    'address','postcode','city','country',
    'ref_relationship','ref_name','ref_address','ref_phoneno',
    'survey_type'/*,'survey_details'*/
  ];

  function setVal(id, val) {
    const el = document.getElementById(id);
    if (!el) return;
    if (el.tagName === 'SELECT') {
      el.value = val ?? '';
      el.dispatchEvent(new Event('change'));
    } else {
      el.value = val ?? '';
    }
  }
  function setGender(val) {
    const male   = document.querySelector('input[name="gender"][value="Male"]');
    const female = document.querySelector('input[name="gender"][value="Female"]');
    if (male)   male.checked   = (val === 'Male');
    if (female) female.checked = (val === 'Female');
  }

  function clearBlacklist() {
    const el = document.getElementById('blacklistBanner');
    if (el) el.innerHTML = '';
  }
  function showBlacklist(reason) {
    const el = document.getElementById('blacklistBanner');
    if (!el) return;
    el.innerHTML = `<div class="alert alert-danger">
      <b>Blacklisted NRIC</b>${reason ? ` — ${reason}` : ''}. Customer cannot be used.
    </div>`;
  }

  // ✨ CLEAR EVERYTHING (except NRIC itself)
  function clearForm() {
    FIELD_IDS.forEach(id => setVal(id, ''));
    setGender(null);
  }

  const nricInput = document.getElementById('nric_no');
  if (!nricInput) return;

  let debounceTimer = null;

  function doLookup() {
    const nric = nricInput.value.trim();
    clearBlacklist();

    // If field is empty -> clear form
    if (nric === '') {
      clearForm();
      return;
    }

    fetch(lookupUrl + '?nric_no=' + encodeURIComponent(nric), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(data => {
        if (!data.found) {
          // Not found -> clear so user can type fresh data
          clearForm();
          return;
        }

        if (data.blacklisted) {
          showBlacklist(data.reason_blacklist || '');
          // optional: also clear the form to avoid leaving stale info
          clearForm();
          return;
        }

        // Fill with existing customer
        const c = data.customer || {};
        setVal('nric_type', c.nric_type);
        setVal('title', c.title);
        setVal('firstname', c.firstname);
        setVal('lastname', c.lastname);
        setGender(c.gender);
        setVal('race', c.race);
        setVal('dob', c.dob);
        setVal('phone_no', c.phone_no);
        setVal('phone_no2', c.phone_no2);
        setVal('email', c.email);
        setVal('license_no', c.license_no);
        setVal('license_exp', c.license_exp);
        setVal('address', c.address);
        setVal('postcode', c.postcode);
        setVal('city', c.city);
        setVal('country', c.country);
        setVal('ref_relationship', c.ref_relationship);
        setVal('ref_name', c.ref_name);
        setVal('ref_address', c.ref_address);
        setVal('ref_phoneno', c.ref_phoneno);
        setVal('survey_type', c.survey_type);
        // setVal('survey_details', c.survey_details);
      })
      .catch(() => {
        // On error, you can choose to clear as well
        // clearForm();
      });
  }

  nricInput.addEventListener('blur', doLookup);
  nricInput.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(doLookup, 350);
  });
})();
</script>


{{-- Optional: your link modal --}}
{{-- @include('reservation.partials.link-modal') --}}

@endsection
