@extends('layouts.main')
@section('page-title')
    {{ __('Manage reservation') }}
@endsection
@section('page-breadcrumb')
    {{ __('Reservation') }}
@endsection
{{-- @section('page-action')
    <div class="d-flex">
        @stack('addButtonHook')
        @if (module_is_active('ProductService'))
            @permission('category create')
                <a href="{{ route('category.index') }}"data-size="md" class="btn btn-sm btn-primary me-2"
                    data-bs-toggle="tooltip"data-title="{{ __('Setup') }}" title="{{ __('Setup') }}"><i
                        class="ti ti-settings"></i></a>
            @endpermission
        @endif
        @if ((module_is_active('ProductService') && module_is_active('Account')) || module_is_active('Taskly'))
            @permission('reservation manage')
                <a href="{{ route('reservation.grid.view') }}" data-bs-toggle="tooltip" data-bs-original-title="{{ __('Grid View') }}"
                    class="btn btn-sm btn-primary btn-icon me-2">
                    <i class="ti ti-layout-grid"></i>
                </a>
                <a href="{{ route('reservation.stats.view') }}" data-bs-toggle="tooltip"
                    data-bs-original-title="{{ __('Quick Stats') }}" class="btn btn-sm btn-primary btn-icon me-2">
                    <i class="ti ti-filter"></i>
                </a>
            @endpermission
            @permission('reservation create')
                <a href="{{ route('reservation.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                    data-bs-original-title="{{ __('Create') }}">
                    <i class="ti ti-plus"></i>
                </a>
            @endpermission
        @endif

    </div>
@endsection --}}
@push('css')
    @include('layouts.includes.datatable-css')
@endpush
@section('content')
    <div class="row">
        

        <div class="card" >
            <div class="right_col" role="main" style="margin-left:5%">
                <div class="">
                    
                    <div class="clearfix"></div>

                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2 class="mt-3">NRIC No.</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <br>
                                    <form class="form-horizontal form-label-left input_mask" method="GET" action="{{ route('reservation.nric.create') }}">
                                        <div class="form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">
                                                NRIC No. 
                                                <font color="blue">
                                                    <abbr title='Please ensure the NRIC No is correct'>
                                                        <i class="fa fa-info-circle"></i>
                                                    </abbr>
                                                </font>
                                            </label>
                                            <div class="col-md-6 col-sm-6 col-xs-12">
                                                <input type="number" class="form-control" name="nric" required>
                                                <i><small>Do not include any symbol.</small></i>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="col-md-9 col-sm-9 col-xs-12 col-md-offset-3 col-sm-offset-3">
                                                <button type="submit" class="btn btn-success">Proceed</button>
                                                <button type="reset" class="btn btn-danger" data-bs-toggle="tooltip" title="Reset">
                                                    <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off"></i></span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        


    </div>
@endsection
@push('scripts')
    @include('layouts.includes.datatable-js')
    {{-- {{ $dataTable->scripts() }} --}}
    <script>
        $(document).on("click", ".cp_link", function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            toastrs('success', '{{ __('Link Copy on Clipboard') }}', 'success')
        });
    </script>
@endpush
