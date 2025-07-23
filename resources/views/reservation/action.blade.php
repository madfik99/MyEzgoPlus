
    <div class="action-btn me-2">
        <a href="#" class="btn btn-sm align-items-center cp_link bg-primary" data-link="{{route('pay.reservationpay',\Illuminate\Support\Facades\Crypt::encrypt($reservation->id))}}" data-bs-toggle="tooltip" title="{{__('Copy')}}" data-original-title="{{__('Click to copy reservation link')}}">
            <i class="ti ti-file text-white"></i>
        </a>
    </div>
    @if($reservation->status != 0 && $reservation->status != 3 )
        @if ($reservation->is_convert == 0 && $reservation->is_convert_retainer ==0)
            @permission('reservation convert invoice')
                <div class="action-btn me-2">
                    {!! Form::open([
                        'method' => 'get',
                        'route' => ['reservation.convert', $reservation->id],
                        'id' => 'reservation-form-' . $reservation->id,
                    ]) !!}
                    <a href="#"
                        class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm bg-success"
                        data-bs-toggle="tooltip" title=""
                        data-bs-original-title="{{ __('Convert to Invoice') }}"
                        aria-label="{{__('Delete')}}"
                        data-text="{{ __('You want to confirm convert to Invoice. Press Yes to continue or No to go back') }}"
                        data-confirm-yes="reservation-form-{{ $reservation->id }}">
                        <i class="ti ti-exchange text-white"></i>
                    </a>
                    {{ Form::close() }}
                </div>
            @endpermission
        @elseif($reservation->is_convert ==1)
            @permission('invoice show')
                <div class="action-btn me-2">
                    <a href="{{ route('invoice.show', \Crypt::encrypt($reservation->converted_invoice_id)) }}"
                        class="mx-3 btn btn-sm  align-items-center bg-success"
                        data-bs-toggle="tooltip"
                        title="{{ __('Already convert to Invoice') }}">
                        <i class="ti ti-eye text-white"></i>
                    </a>
                </div>
            @endpermission
        @endif
    @endif
    @if (module_is_active('Retainer'))
        @include('retainer::setting.convert_retainer', ['reservation' => $reservation ,'type' =>'list'])
    @endif
    
    @permission('reservation duplicate')
        <div class="action-btn me-2">
            {!! Form::open([
                'method' => 'get',
                'route' => ['reservation.duplicate', $reservation->id],
                'id' => 'duplicate-form-' . $reservation->id,
            ]) !!}
            <a href="#"
                class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm bg-secondary"
                data-bs-toggle="tooltip" title=""
                data-bs-original-title="{{ __('Duplicate') }}"
                aria-label="Delete"
                data-text="{{ __('You want to confirm duplicate this reservation. Press Yes to continue or No to go back') }}"
                data-confirm-yes="duplicate-form-{{ $reservation->id }}">
                <i class="ti ti-copy text-white text-white"></i>
            </a>
            {{ Form::close() }}
        </div>
    @endpermission

    @permission('reservation show')
        <div class="action-btn me-2">
            <a href="{{ route('reservation.show', \Crypt::encrypt($reservation->id)) }}"
                class="mx-3 btn btn-sm align-items-center bg-warning"
                data-bs-toggle="tooltip" title="{{ __('View') }}"
                data-original-title="{{ __('Detail') }}">
                <i class="ti ti-eye text-white text-white"></i>
            </a>
        </div>
    @endpermission

    @if (module_is_active('ProductService') && ($reservation->reservation_module == 'taskly' ? module_is_active('Taskly') : module_is_active('Account')) && ($reservation->reservation_module == 'cmms' ? module_is_active('CMMS') : module_is_active('Account')))
        @permission('reservation edit')
            <div class="action-btn me-2">
                <a href="{{ route('reservation.edit', \Crypt::encrypt($reservation->id)) }}"
                    class="mx-3 btn btn-sm  align-items-center bg-info"
                    data-bs-toggle="tooltip"
                    data-bs-original-title="{{ __('Edit') }}">
                    <i class="ti ti-pencil text-white"></i>
                </a>
            </div>
        @endpermission
    @endif

    @permission('reservation delete')
        <div class="action-btn me-2">
            {{ Form::open(['route' => ['reservation.destroy', $reservation->id], 'class' => 'm-0']) }}
            @method('DELETE')
            <a href="#"
                class="mx-3 btn btn-sm  align-items-center bs-pass-para show_confirm bg-danger"
                data-bs-toggle="tooltip" title=""
                data-bs-original-title="{{__('Delete')}}" aria-label="{{__('Delete')}}"
                data-confirm="{{ __('Are You Sure?') }}"
                data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                data-confirm-yes="delete-form-{{ $reservation->id }}"><i
                    class="ti ti-trash text-white text-white"></i></a>
            {{ Form::close() }}
        </div>
    @endpermission
