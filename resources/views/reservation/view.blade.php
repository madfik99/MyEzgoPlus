@extends('layouts.main')
@section('page-title')
    {{ __('reservation Detail') }}
@endsection
@section('page-breadcrumb')
    {{ __('reservation') }}
@endsection
@push('scripts')
    <script>
        $(document).on('change', '.status_change', function() {
            var status = this.value;
            var url = $(this).data('url');
            $.ajax({
                url: url + '?status=' + status,
                type: 'GET',
                cache: false,
                success: function(data) {
                    location.reload();
                },
            });
        });

        $('.cp_link').on('click', function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            toastrs('success', '{{ __('Link Copy on Clipboard') }}', 'success')
        });
    </script>
    <script src="{{ asset('assets/js/plugins/dropzone-amd-module.min.js') }}"></script>
    <script>
        Dropzone.autoDiscover = false;
        myDropzone = new Dropzone("#dropzonewidget", {
            maxFiles: 20,
            maxFilesize: 20,
            parallelUploads: 1,
            acceptedFiles: ".jpeg,.jpg,.png,.pdf,.doc,.txt",
            url: "{{ route('reservation.file.upload', [$reservation->id]) }}",
            success: function(file, response) {
                if (response.is_success) {
                    // dropzoneBtn(file, response);
                    location.reload();
                    myDropzone.removeFile(file);
                    toastrs('{{ __('Success') }}', 'File Successfully Uploaded', 'success');
                } else {
                    location.reload();
                    myDropzone.removeFile(response.error);
                    toastrs('Error', response.error, 'error');
                }
            },
            error: function(file, response) {
                myDropzone.removeFile(file);
                location.reload();
                if (response.error) {
                    toastrs('Error', response.error, 'error');
                } else {
                    toastrs('Error', response, 'error');
                }
            }
        });
        myDropzone.on("sending", function(file, xhr, formData) {
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            formData.append("reservation_id", {{ $reservation->id }});
        });
    </script>
@endpush
@section('page-action')
    <div>
        @if ($reservation->is_convert == 0)
            @permission('reservation convert invoice')
                <div class="action-btn mb-1">
                    {!! Form::open([
                        'method' => 'get',
                        'route' => ['reservation.convert', $reservation->id],
                        'id' => 'reservation-form-' . $reservation->id,
                    ]) !!}
                    <a href="#" class="btn btn-sm bg-success align-items-center bs-pass-para show_confirm"
                        data-bs-toggle="tooltip" title="" data-bs-original-title="{{ __('Convert to Invoice') }}"
                        aria-label="Delete" data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                        data-confirm-yes="reservation-form-{{ $reservation->id }}">
                        <i class="ti ti-exchange text-white"></i>
                    </a>
                    {{ Form::close() }}
                </div>
            @endpermission
        @else
            @permission('invoice show')
                <div class="action-btn ms-2">
                    <a href="{{ route('invoice.show', \Crypt::encrypt($reservation->converted_invoice_id)) }}"
                        class="btn btn-sm bg-success align-items-center" data-bs-toggle="tooltip"
                        title="{{ __('Already convert to Invoice') }}">
                        <i class="ti ti-eye text-white"></i>
                    </a>
                </div>
            @endpermission
        @endif

        @if (module_is_active('Retainer'))
            @include('retainer::setting.convert_retainer', ['reservation' => $reservation, 'type' => 'view'])
        @endif
        <div class="action-btn ms-2">
            <a href="#" class="btn btn-sm bg-primary align-items-center cp_link"
                data-link="{{ route('pay.reservationpay', \Illuminate\Support\Facades\Crypt::encrypt($reservation->id)) }}"
                data-bs-toggle="tooltip" title="{{ __('Click to Copy reservation Link') }}"
                data-original-title="{{ __('Click to Copy reservation Link') }}">
                <i class="ti ti-file text-white"></i>
            </a>
        </div>
    </div>
@endsection
@section('content')
    @permission('reservation send')
        @if ($reservation->status != 4)
            <div class="card mt-3">
                <div class="card-body">
                    <div class="row timeline-wrapper">
                        <div class="col-xl-4 col-lg-4 col-sm-6">
                            <div class="progress mb-3">
                                <div class="progress-value"></div>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <div class="timeline-icons ">
                                    <i class="ti ti-plus text-primary"></i>
                                </div>
                                <div class="invoice-content">
                                    <h2 class="text-primary h5 mb-2">{{ __('Create reservation') }}</h2>
                                    <p class="text-sm mb-3">
                                        {{ __('Created on ') }}{{ company_date_formate($reservation->issue_date) }}
                                    </p>
                                    @permission('reservation edit')
                                        <a href="{{ route('reservation.edit', \Crypt::encrypt($reservation->id)) }}"
                                            class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                            data-original-title="{{ __('Edit') }}">
                                            <i class="ti ti-pencil me-1"></i>{{ __('Edit') }}</a>
                                    @endpermission
                                </div>

                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-sm-6">
                            <div class="progress mb-3">
                                <div class="{{ $reservation->status !== 0 ? 'progress-value' : '' }}"></div>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <div class="timeline-icons ">
                                    <i class="ti ti-send text-warning"></i>
                                </div>
                                <div class="invoice-content">
                                    <h6 class="text-warning h5 mb-2">{{ __('Send reservation') }}</h6>
                                    <p class="text-sm mb-2">
                                        @if ($reservation->status != 0)
                                            {{ __('Sent on') }}
                                            {{ company_date_formate($reservation->send_date) }}
                                        @else
                                            @permission('reservation send')
                                                {{ __('Status') }} : {{ __('Not Sent') }}
                                            @endpermission
                                        @endif
                                    </p>
                                    @if ($reservation->status == 0)
                                        @permission('reservation send')
                                            <a href="{{ route('reservation.sent', $reservation->id) }}" class="btn btn-sm btn-warning"
                                                data-bs-toggle="tooltip" data-original-title="{{ __('Mark Sent') }}"><i
                                                    class="ti ti-send me-1"></i>{{ __('Send') }}</a>
                                        @endpermission
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-sm-6">
                            <div class="progress mb-3">
                                <div class="{{ $reservation->status !== 0 ? 'progress-value' : '' }}"></div>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <div class="timeline-icons ">
                                    <i class="ti ti-report-money text-info"></i>
                                </div>
                                <div class="invoice-content">
                                    <h6 class="text-info h5 mb-2">{{ __('reservation Status') }}</h6>
                                    <p class="text-sm mb-3">{{ __('Status') }} :
                                        @if ($reservation->status == 0)
                                            <span>{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                        @elseif($reservation->status == 1)
                                            <span>{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                        @elseif($reservation->status == 2)
                                            <span>{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                        @elseif($reservation->status == 3)
                                            <span>{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                        @elseif($reservation->status == 4)
                                            <span>{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endpermission

    <div class="row row-gap justify-content-between align-items-center mb-3">
        <div class="col-md-6">
            <ul class="nav nav-pills nav-fill cust-nav information-tab reservation-tab" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="reservation-tab" data-bs-toggle="pill" data-bs-target="#reservation"
                        type="button">{{ __('reservation') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reservation-attechment-tab" data-bs-toggle="pill"
                        data-bs-target="#reservation-attechment" type="button">{{ __('Attachment') }}</button>
                </li>
            </ul>
        </div>

        <div class="col-md-6 apply-wrp d-flex align-items-center justify-content-between justify-content-md-end">
            <select class="form-control w-auto apply-credit status_change" name="status"
                data-url="{{ route('reservation.status.change', $reservation->id) }}">
                @foreach ($status as $k => $val)
                    <option value="{{ $k }}" {{ $reservation->status == $k ? 'selected' : '' }}>
                        {{ $val }}</option>
                @endforeach
            </select>
            <div class="all-button-box mx-2">
                <a href="{{ route('reservation.pdf', Crypt::encrypt($reservation->id)) }}" class="btn btn-sm btn-primary"
                    target="_blank">{{ __('Download') }}</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade active show" id="reservation" role="tabpanel"
                    aria-labelledby="pills-user-tab-1">
                    <div class="card">
                        <div class="card-body">
                            <div class="invoice">
                                <div class="invoice-print">
                                    <div class="row row-gap invoice-title border-1 border-bottom  pb-3 mb-3">
                                        <div class="col-sm-4  col-12">
                                            <h2 class="h3 mb-0">{{ __('reservation') }}</h2>
                                        </div>
                                        <div class="col-sm-8  col-12">
                                            <div
                                                class="d-flex invoice-wrp flex-wrap align-items-center gap-md-2 gap-1 justify-content-end">
                                                <div
                                                    class="d-flex invoice-date flex-wrap align-items-center justify-content-end gap-md-3 gap-1">
                                                    <p class="mb-0"><strong>{{('Issue Date')}} :</strong>
                                                        {{ company_date_formate($reservation->issue_date) }}</p>
                                                </div>
                                                <h3 class="invoice-number mb-0">
                                                    {{ \App\Models\reservation::reservationNumberFormat($reservation->reservation_id) }}
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-sm-4 p-3 invoice-billed">
                                        <div class="row row-gap">
                                            <div class="col-lg-4 col-sm-6">
                                                <p class="mb-3">
                                                    <strong class="h5 mb-1">{{ __('Name ') }} :
                                                    </strong>{{ !empty($customer->name) ? $customer->name : '' }}
                                                </p>
                                                @if (!empty($customer->billing_name) && !empty($customer->billing_address) && !empty($customer->billing_zip))
                                                    <div>
                                                        <p class="mb-2"><strong
                                                                class="h5 mb-1 d-block">{{ __('Billed To') }} :</strong>
                                                            <span class="text-muted d-block" style="max-width:80%">
                                                                {{ !empty($customer->billing_name) ? $customer->billing_name : '' }}
                                                                {{ !empty($customer->billing_address) ? $customer->billing_address : '' }}
                                                                {{ !empty($customer->billing_city) ? $customer->billing_city . ' ,' : '' }}
                                                                {{ !empty($customer->billing_state) ? $customer->billing_state . ' ,' : '' }}
                                                                {{ !empty($customer->billing_zip) ? $customer->billing_zip : '' }}
                                                                {{ !empty($customer->billing_country) ? $customer->billing_country : '' }}
                                                            </span>
                                                        </p>
                                                        <p class="mb-1 text-dark">
                                                            {{ !empty($customer->billing_phone) ? $customer->billing_phone : '' }}
                                                        </p>
                                                        <p class="mb-0">
                                                            <strong>{{ __('Tax Number ') }} :
                                                            </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>


                                            <div class="col-lg-4 col-sm-6">
                                                <p class="mb-3">
                                                    <strong class="h5 mb-1">{{ __('Email ') }} :
                                                    </strong>{{ !empty($customer->email) ? $customer->email : '' }}
                                                </p>
                                                @if (!empty($company_settings['reservation_shipping_display']) && $company_settings['reservation_shipping_display'] == 'on')
                                                    @if (!empty($customer->shipping_name) && !empty($customer->shipping_address) && !empty($customer->shipping_zip))
                                                        <div>
                                                            <p class="mb-2">
                                                                <strong class="h5 mb-1 d-block">{{ __('Shipped To') }}
                                                                    :</strong>
                                                                <span class="text-muted d-block" style="max-width:80%">
                                                                    {{ !empty($customer->shipping_name) ? $customer->shipping_name : '' }}
                                                                    {{ !empty($customer->shipping_address) ? $customer->shipping_address : '' }}
                                                                    {{ !empty($customer->shipping_city) ? $customer->shipping_city . ' ,' : '' }}
                                                                    {{ !empty($customer->shipping_state) ? $customer->shipping_state . ' ,' : '' }}
                                                                    {{ !empty($customer->shipping_zip) ? $customer->shipping_zip : '' }}
                                                                    {{ !empty($customer->shipping_country) ? $customer->shipping_country : '' }}
                                                                </span>
                                                            </p>
                                                            <p class="mb-1 text-dark">
                                                                {{ !empty($customer->shipping_phone) ? $customer->shipping_phone : '' }}
                                                            </p>
                                                            <p class="mb-0">
                                                                <strong>{{ __('Tax Number ') }} :
                                                                </strong>{{ !empty($customer->tax_number) ? $customer->tax_number : '' }}
                                                            </p>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>


                                            <div class="col-lg-2 col-sm-6">
                                                <strong class="h5 d-block mb-2">{{ __('Status') }} :</strong>
                                                @if ($reservation->status == 0)
                                                    <span
                                                        class="badge fix_badge bg-primary p-2 px-3">{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                                @elseif($reservation->status == 1)
                                                    <span
                                                        class="badge fix_badge bg-info p-2 px-3">{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                                @elseif($reservation->status == 2)
                                                    <span
                                                        class="badge fix_badge bg-secondary p-2 px-3">{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                                @elseif($reservation->status == 3)
                                                    <span
                                                        class="badge fix_badge bg-warning p-2 px-3">{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                                @elseif($reservation->status == 4)
                                                    <span
                                                        class="badge fix_badge bg-danger p-2 px-3">{{ __(\App\Models\reservation::$statues[$reservation->status]) }}</span>
                                                @endif
                                            </div>

                                            @if (!empty($company_settings['reservation_qr_display']) && $company_settings['reservation_qr_display'] == 'on')
                                                <div class="col-lg-2 col-sm-6">
                                                    <div class="float-sm-end qr-code">
                                                        <div class="col">
                                                            <div class="float-sm-end">
                                                                {!! DNS2D::getBarcodeHTML(
                                                                    route('pay.reservationpay', \Illuminate\Support\Facades\Crypt::encrypt($reservation->id)),
                                                                    'QRCODE',
                                                                    2,
                                                                    2,
                                                                ) !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if (!empty($customFields) && count($reservation->customField) > 0)
                                        <div class="px-4 mt-3">
                                            <div class="row row-gap">
                                                @foreach ($customFields as $field)
                                                    <div class="col-xxl-3 col-sm-6">
                                                        <strong class="d-block mb-1">{{ $field->name }} </strong>

                                                        @if ($field->type == 'attachment')
                                                            <a href="{{ get_file($reservation->customField[$field->id]) }}"
                                                                target="_blank">
                                                                <img src=" {{ get_file($reservation->customField[$field->id]) }} "
                                                                    class="wid-120 rounded">
                                                            </a>
                                                        @else
                                                            <p class="mb-0">
                                                                {{ !empty($reservation->customField[$field->id]) ? $reservation->customField[$field->id] : '-' }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="invoice-summary mt-3">
                                        <div class="invoice-title border-1 border-bottom mb-3 pb-2">
                                            <h3 class="h4 mb-0">{{ __('Item Summary') }}</h3>
                                            <small>{{ __('All items here cannot be deleted.') }}</small>
                                        </div>
                                        <div class="table-responsive mt-2">
                                            <table class="table mb-0 table-striped">
                                                <tr>
                                                    <th data-width="40" class="text-white bg-primary text-uppercase">#</th>
                                                    @if ($reservation->reservation_module == 'account' || $reservation->reservation_module == 'cmms')
                                                        <th class="text-white bg-primary text-uppercase">
                                                            {{ __('Item Type') }}</th>
                                                        <th class="text-white bg-primary text-uppercase">
                                                            {{ __('Item') }}</th>
                                                    @elseif($reservation->reservation_module == 'taskly')
                                                        <th class="text-white bg-primary text-uppercase">
                                                            {{ __('Project') }}</th>
                                                    @endif

                                                    @if ($reservation->reservation_module == 'account' || $reservation->reservation_module == 'cmms')
                                                        <th class="text-white bg-primary text-uppercase">
                                                            {{ __('Quantity') }}
                                                        </th>
                                                    @endif
                                                    <th class="text-white bg-primary text-uppercase">{{ __('Rate') }}
                                                    </th>
                                                    <th class="text-white bg-primary text-uppercase">{{ __('Discount') }}
                                                    </th>
                                                    <th class="text-white bg-primary text-uppercase">{{ __('Tax') }}
                                                    </th>
                                                    <th class="text-white bg-primary text-uppercase">
                                                        {{ __('Description') }}</th>
                                                    <th class="text-right text-white bg-primary text-uppercase"
                                                        width="12%">
                                                        {{ __('Price') }}<br>
                                                        <small
                                                            class="text-danger font-weight-bold">{{ __('After discount & tax') }}</small>
                                                    </th>
                                                </tr>

                                                @php
                                                    $totalQuantity = 0;
                                                    $totalRate = 0;
                                                    $totalTaxPrice = 0;
                                                    $totalDiscount = 0;
                                                    $taxesData = [];
                                                    $TaxPrice_array = [];
                                                @endphp

                                                @foreach ($iteams as $key => $iteam)
                                                    @if (!empty($iteam->tax))
                                                        @php
                                                            $taxes = \App\Models\reservation::tax($iteam->tax);
                                                            $totalQuantity += $iteam->quantity;
                                                            $totalRate += $iteam->price;
                                                            $totalDiscount += $iteam->discount;
                                                            foreach ($taxes as $taxe) {
                                                                $taxDataPrice = \App\Models\reservation::taxRate(
                                                                    $taxe->rate,
                                                                    $iteam->price,
                                                                    $iteam->quantity,
                                                                    $iteam->discount,
                                                                );
                                                                if (array_key_exists($taxe->name, $taxesData)) {
                                                                    $taxesData[$taxe->name] =
                                                                        $taxesData[$taxe->name] + $taxDataPrice;
                                                                } else {
                                                                    $taxesData[$taxe->name] = $taxDataPrice;
                                                                }
                                                            }
                                                        @endphp
                                                    @endif
                                                    <tr>
                                                        <td>{{ $key + 1 }}</td>
                                                        @if ($reservation->reservation_module == 'account')
                                                            <td>{{ !empty($iteam->product_type) ? Str::ucfirst($iteam->product_type) : '--' }}
                                                            </td>
                                                            <td>{{ !empty($iteam->product()) ? $iteam->product()->name : '' }}
                                                            </td>
                                                        @elseif($reservation->reservation_module == 'taskly')
                                                            {{-- <td>{{ !empty($iteam->product_type) ? Str::ucfirst($iteam->product_type) : '-' }}
                                                            </td> --}}
                                                            <td>{{ !empty($iteam->product()) ? $iteam->product()->title : '--' }}
                                                            </td>
                                                        @elseif($reservation->reservation_module == 'cmms')
                                                            <td>{{ !empty($iteam->product_type) ? Str::ucfirst($iteam->product_type) : '--' }}
                                                            </td>
                                                            <td>{{ !empty($iteam->product()) ? $iteam->product()->name : '' }}
                                                            </td>
                                                        @endif
                                                        @if ($reservation->reservation_module == 'account' || $reservation->reservation_module == 'cmms')
                                                            <td>{{ $iteam->quantity }}</td>
                                                        @endif
                                                        <td>{{ currency_format_with_sym($iteam->price) }}</td>
                                                        <td>
                                                            {{ currency_format_with_sym($iteam->discount) }}
                                                        </td>
                                                        <td>
                                                            @if (!empty($iteam->tax))
                                                                <table>
                                                                    @php
                                                                        $totalTaxRate = 0;
                                                                        $data = 0;
                                                                    @endphp
                                                                    @foreach ($taxes as $tax)
                                                                        @php
                                                                            $taxPrice = \App\Models\reservation::taxRate(
                                                                                $tax->rate,
                                                                                $iteam->price,
                                                                                $iteam->quantity,
                                                                                $iteam->discount,
                                                                            );
                                                                            $totalTaxPrice += $taxPrice;
                                                                            $data += $taxPrice;
                                                                        @endphp
                                                                        <tr>
                                                                            <td>{{ $tax->name . ' (' . $tax->rate . '%)' }}
                                                                            </td>
                                                                            <td>{{ currency_format_with_sym($taxPrice) }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                    @php
                                                                        array_push($TaxPrice_array, $data);
                                                                    @endphp
                                                                </table>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        @php
                                                            $tr_tex =
                                                                array_key_exists($key, $TaxPrice_array) == true
                                                                    ? $TaxPrice_array[$key]
                                                                    : 0;
                                                        @endphp
                                                        <td>{{ !empty($iteam->description) ? $iteam->description : '-' }}</td>
                                                        <td class="text-center">
                                                            {{ currency_format_with_sym($iteam->price * $iteam->quantity - $iteam->discount + $tr_tex) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tfoot>
                                                    <tr>
                                                        <td></td>
                                                        @if ($reservation->reservation_module == 'account' || $reservation->reservation_module == 'cmms')
                                                            <td></td>
                                                        @endif

                                                        <td class="bg-color"><b>{{ __('Total') }}</b></td>
                                                        @if ($reservation->reservation_module == 'account' || $reservation->reservation_module == 'cmms')
                                                            <td class="bg-color"><b>{{ $totalQuantity }}</b></td>
                                                        @endif
                                                        <td class="bg-color">
                                                            <b>{{ currency_format_with_sym($totalRate) }}</b>
                                                        </td>
                                                        <td class="bg-color">
                                                            <b>{{ currency_format_with_sym($totalDiscount) }}</b>
                                                        </td>
                                                        <td class="bg-color">
                                                            <b>{{ currency_format_with_sym($totalTaxPrice) }}</b>
                                                        </td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    @php
                                                        $colspan = 7;
                                                        $customerInvoices = ['taskly', 'account', 'cmms', 'cardealership', 'RestaurantMenu', 'rent' , 'Fleet'];

                                                        if (in_array($reservation->invoice_module, $customerInvoices)) {
                                                            $colspan = 7;
                                                        }

                                                        if ($reservation->reservation_module == 'taskly') {
                                                            $colspan = 5;
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td colspan="{{ $colspan }}"></td>
                                                        <td class="text-right">{{ __('Sub Total') }}</td>
                                                        <td class="text-right"><b>{{ currency_format_with_sym($reservation->getSubTotal()) }}</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $colspan }}"></td>
                                                        <td class="text-right">{{ __('Discount') }}</td>
                                                        <td class="text-right"><b>{{ currency_format_with_sym($reservation->getTotalDiscount()) }}</b>
                                                        </td>
                                                    </tr>
                                                    @if (!empty($taxesData))
                                                        @foreach ($taxesData as $taxName => $taxPrice)
                                                            <tr>
                                                                <td colspan="{{ $colspan }}"></td>
                                                                <td class="text-right">{{ $taxName }}</td>
                                                                <td class="text-right"><b>{{ currency_format_with_sym($taxPrice) }}</b></td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    <tr>
                                                        <td colspan="{{ $colspan }}"></td>
                                                        <td class="blue-text text-right">{{ __('Total') }}</td>
                                                        <td class="blue-text text-right"><b>{{ currency_format_with_sym($reservation->getTotal()) }}</b></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="tab-pane fade" id="reservation-attechment" role="tabpanel" aria-labelledby="pills-user-tab-4">
                    <div class="row">
                        <div class="col-3">
                            <div class="card border-primary border">
                                <div class="card-body table-border-style">
                                    <div class="col-md-12 dropzone browse-file" id="dropzonewidget">
                                        <div class="dz-message my-5" data-dz-message>
                                            <span>{{ __('Drop files here to upload') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-9">
                            <div class="card border-primary border">
                                <div class="card-body table-border-style">
                                    <div class="table-responsive">
                                        <table class="table mb-0 pc-dt-simple" id="attachment">
                                            <thead>
                                                <tr>
                                                    <th class="text-dark">{{ __('#') }}</th>
                                                    <th class="text-dark">{{ __('File Name') }}</th>
                                                    <th class="text-dark">{{ __('File Size') }}</th>
                                                    <th class="text-dark">{{ __('Date Created') }}</th>
                                                    <th class="text-dark">{{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                            @forelse($reservation_attachment as $key =>$attachment)
                                                <td>{{ ++$key }}</td>
                                                <td>{{ $attachment->file_name }}</td>
                                                <td>{{ $attachment->file_size }}</td>
                                                <td>{{ company_date_formate($attachment->created_at) }}</td>
                                                <td>
                                                    <div class="action-btn me-2">
                                                        <a href="{{ url($attachment->file_path) }}" data-bs-toggle="tooltip"
                                                            class="mx-3 btn btn-sm align-items-center bg-primary"
                                                            title="{{ __('Download') }}" target="_blank" download>
                                                            <i class="ti ti-download text-white"></i>
                                                        </a>
                                                    </div>
                                                    <div class="action-btn">
                                                        {{ Form::open(['route' => ['reservation.attachment.destroy', $attachment->id], 'class' => 'm-0']) }}
                                                        @method('DELETE')
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm bg-danger"
                                                            data-bs-toggle="tooltip" title=""
                                                            data-bs-original-title="Delete" aria-label="Delete"
                                                            data-confirm="{{ __('Are You Sure?') }}"
                                                            data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="delete-form-{{ $attachment->id }}">
                                                            <i class="ti ti-trash text-white text-white"></i>
                                                        </a>
                                                        {{ Form::close() }}
                                                    </div>
                                                </td>
                                                </tr>
                                            @empty
                                                @include('layouts.nodatafound')
                                            @endforelse
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
