<?php

namespace App\Http\Controllers;

use App\DataTables\reservationDataTable;
use App\Events\ConvertToInvoice;
use App\Events\Createreservation;
use App\Events\Destroyreservation;
use App\Events\Duplicatereservation;
use App\Events\Resentreservation;
use App\Events\Sentreservation;
use App\Events\StatusChangereservation;
use App\Events\Updatereservation;
use App\Models\Invoice;
use App\Models\reservation;
use App\Models\reservationProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\InvoiceProduct;
use App\Models\EmailTemplate;
use App\Models\reservationAttechment;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\ClassModel;
use App\Models\LocationBooking;
use App\Models\Vehicle;
use App\Models\Extend;
use App\Models\SeasonRental;
use App\Models\BookingTrans;
use App\Models\OptionalRental;
use App\Models\CDW;
use App\Models\CDWLog;
use App\Models\ChecklistItem;
use App\Models\Sale;
use App\Models\UploadData;
use App\Models\Checklist;
use App\Models\Discount;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingDetailsMail;
use App\Models\FleetMaintenanceAutoFilter;
use App\Models\FleetMaintenanceBattery;
use App\Models\FleetMaintenanceEngineOil;
use App\Models\FleetMaintenanceGearOil;
use App\Models\FleetMaintenanceSparkPlug;
use Workdo\CMMS\Entities\Location;
use Workdo\CMMS\Entities\Workorder;
use Workdo\ProductService\Entities\ProductService;
use Illuminate\Support\Facades\Validator;
use Workdo\Account\Entities\ChartOfAccount;
use Workdo\Account\Entities\ChartOfAccountSubType;
use Workdo\Account\Entities\ChartOfAccountType;
use Carbon\Carbon;
use Google\Service\Analytics\Upload;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
     /**
     * Display a listing of the resource.
     * @return Renderable
     */
    
    // public function index(reservationDataTable $dataTable)
    // {
    //     if (Auth::user()->isAbleTo('reservation manage'))
    //     {
    //         $status = reservation::$statues;
    //         $customer  = User::where('workspace_id', '=', getActiveWorkSpace())->where('type','Client')->get()->pluck('name', 'id');

    //         return $dataTable->render('reservation.index', compact('customer', 'status'));
    //     } else {
    //         return redirect()->back()->with('error', __('Permission Denied.'));
    //     }
    // }

    public function index()
    {
        // Just show the NRIC form. Remove $dataTable->render()
        return view('reservation.index');
    }

    public function createWithNric(Request $request)
    {
        $nric = $request->query('nric');

        $customer = Customer::where('nric_no', $nric)->first();

        $locations = LocationBooking::where('status', 'A')->get();
        $vehicles = ClassModel::where('status', 'A')->get(); // or whatever model holds vehicle classes

        return view('reservation.form', compact('customer', 'nric', 'locations', 'vehicles'));
    }


  public function search(Request $request)
{   
    $booking = BookingTrans::all();

    $cdw_id = $request->input('cdw_id');

    // 1. Validate the input
    $validated = $request->validate([
        'nric' => 'required|string|max:20',
        'search_pickup_date' => 'required|date',
        'search_pickup_time' => 'required',
        'search_return_date' => 'required|date',
        'search_return_time' => 'required',
        'search_pickup_location' => 'nullable|integer',
        'search_return_location' => 'nullable|integer',
        'coupon' => [
            'nullable',
            'string',
            'max:50',
            function ($attribute, $value, $fail) {
                if ($value) {
                    $today = now()->toDateString();
                    $coupon = Discount::where('code', $value)
                        ->where('start_date', '<=', $today)
                        ->where(function ($q) use ($today) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $today);
                        })
                        ->first();
                    if (!$coupon) {
                        $fail('The coupon code is invalid or expired.');
                    }
                }
            }
        ],
        'agent_code' => 'nullable|string|max:50',
        'search_driver' => 'nullable|string|max:100',
        'klia' => 'nullable|in:yes,no',
        'opt' => 'nullable|in:delivery,pickup',
        'search_vehicle' => 'nullable',
        'isEditing' => 'nullable|in:1',
        'bookingId' => 'nullable|integer',
    ]);

    // Normalize empty string to null for search_vehicle
    if (empty($validated['search_vehicle'])) {
        $validated['search_vehicle'] = null;
    }

    // Detect editing mode
    $isEditing = $request->input('isEditing') == '1';
    $bookingId = $request->input('bookingId');

    $customer = Customer::where('nric_no', $validated['nric'])->first();

    $pickupDateTime = Carbon::parse("{$validated['search_pickup_date']} {$validated['search_pickup_time']}");
    $returnDateTime = Carbon::parse("{$validated['search_return_date']} {$validated['search_return_time']}");

    // Get conflicting vehicles from bookings
    $conflictingBookingVehicles = BookingTrans::whereIn('available', ['Booked', 'Out', 'Extend'])
        ->where(function ($q) use ($pickupDateTime, $returnDateTime) {
            $q->whereBetween('pickup_date', [$pickupDateTime, $returnDateTime])
                ->orWhereBetween('return_date', [$pickupDateTime, $returnDateTime])
                ->orWhere(function ($q2) use ($pickupDateTime, $returnDateTime) {
                    $q2->where('pickup_date', '<=', $pickupDateTime)
                        ->where('return_date', '>=', $returnDateTime);
                });
        })
        ->pluck('vehicle_id');

    // Get conflicting vehicles from extensions
    $conflictingExtendedVehicles = Extend::whereNotNull('sale_id')
        ->where(function ($q) use ($pickupDateTime, $returnDateTime) {
            $q->whereBetween('extend_from_date', [$pickupDateTime, $returnDateTime])
                ->orWhereBetween('extend_to_date', [$pickupDateTime, $returnDateTime])
                ->orWhere(function ($q2) use ($pickupDateTime, $returnDateTime) {
                    $q2->where('extend_from_date', '<=', $pickupDateTime)
                        ->where('extend_to_date', '>=', $returnDateTime);
                });
        })
        ->pluck('vehicle_id');

    $unavailableVehicleIds = $conflictingBookingVehicles
        ->merge($conflictingExtendedVehicles)
        ->unique()
        ->toArray();

    // Build the query
    // $vehicleQuery = Vehicle::with(['class', 'sales','latestBooking'])
    //     ->whereNotIn('id', $unavailableVehicleIds)
    //     ->whereIn('availability', ['Available', 'Booked', 'Out'])
    //     ->when($validated['search_vehicle'], fn($q, $v) => $q->where('class_id', $v))
    //     ->orderBy('reg_no');
   $vehicleQuery = Vehicle::query()
    // keep these so Blade can read relations without N+1
    ->with([
        'class',
        'latestBooking',
        // load only the columns you need from sales to reduce payload
        'sales:id,vehicle_id,total_sale',
    ])
    // make sure we don't drop the alias by putting select FIRST (optional)
    ->select('vehicle.*')

    // sum of sales per vehicle (alias: sale_sum) — used only for ORDER BY
    ->withSum('sales as sale_sum', 'total_sale')

    ->whereNotIn('vehicle.id', $unavailableVehicleIds)
    ->whereIn('vehicle.availability', ['Available', 'Booked', 'Out'])
    ->when($validated['search_vehicle'], fn ($q, $v) => $q->where('vehicle.class_id', $v))

    // ORDER: no sales (NULL) first, then low → high, then stable tie-breaker
    ->orderByRaw('CASE WHEN sale_sum IS NULL THEN 0 ELSE 1 END')
    ->orderBy('sale_sum', 'ASC')
    ->orderBy('vehicle.reg_no', 'ASC');

$availableVehicles = $vehicleQuery->paginate(10)->onEachSide(2);

    // Initialize
    $currentVehicle = null;
    $bookingSummary = null;

    // If editing, fetch the booking
    if ($isEditing && $bookingId) {
        $currentBooking = BookingTrans::with(['vehicle', 'pickupLocation', 'returnLocation'])->find($bookingId);

        if ($currentBooking) {
            // Keep booking summary for the header
            $bookingSummary = [
                'reference'       => $currentBooking->id ?? '',
                'agreement_no'    => $currentBooking->agreement_no ?? '',
                'pickup_time'     => $currentBooking->pickup_date ? \Carbon\Carbon::parse($currentBooking->pickup_date)->format('Y-m-d H:i') : '',
                'return_time'     => $currentBooking->return_date ? \Carbon\Carbon::parse($currentBooking->return_date)->format('Y-m-d H:i') : '',
                'pickup_location' => optional($currentBooking->pickupLocation)->description ?? '',
                'return_location' => optional($currentBooking->returnLocation)->description ?? '',
                'reg_no'          => $currentBooking->vehicle?->reg_no ?? '',
                'car_name'        => trim(($currentBooking->vehicle?->make ?? '') . ' ' . ($currentBooking->vehicle?->model ?? '')),
                'coupon'          => strtoupper($currentBooking->discount_coupon ?? ''),
                'agent'           => strtoupper($currentBooking->agent_code ?? ''),
            ];

            // Also add the current vehicle back in if it's unavailable
            if ($currentBooking->vehicle && in_array($currentBooking->vehicle->id, $unavailableVehicleIds)) {
                $currentVehicle = $currentBooking->vehicle;
                $collection = collect($availableVehicles->items());
                $collection->prepend($currentVehicle);
                $availableVehicles = new \Illuminate\Pagination\LengthAwarePaginator(
                    $collection,
                    $availableVehicles->total() + 1,
                    $availableVehicles->perPage(),
                    $availableVehicles->currentPage(),
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            }
        }
    }

    // Coupon info
    $couponModel = null;
    $validCouponCode = null;
    if (!empty($validated['coupon'])) {
        $couponModel = Discount::where('code', $validated['coupon'])->first();
        $validCouponCode = $couponModel ? $couponModel->code : null;
    }

    return view('reservation.form', array_merge($validated, [
        'cdw_id' => $cdw_id,
        'customer' => $customer,
        'vehicles' => ClassModel::where('status', 'A')->get(),
        'locations' => LocationBooking::where('status', 'A')->get(),
        'availableVehicles' => $availableVehicles,
        'couponModel' => $couponModel,
        'validCouponCode' => $validCouponCode,
        'isEditing' => $isEditing,
        'bookingId' => $bookingId,
        'currentVehicleId' => $currentVehicle ? $currentVehicle->id : null,
        'bookingSummary' => $bookingSummary,
        'booking' => $booking,
    ]));
}




    public function counterReservationFilter(Request $request)
    {
        // Required parameters from your form URL
        $nric = $request->input('nric');  // 'nric' in form, not 'nric_no'
        $vehicle_id = $request->input('vehicle_id');

        if (!$nric || !$vehicle_id) {
            return redirect()->back()->with('error', 'Missing required reservation details.');
        }

        $customer = Customer::where('nric_no', $nric)->first();
        
        if (!$customer && !empty($nric)) {
            $customer = new \App\Models\Customer();
            $customer->nric_no = $nric;
        }

        $vehicle = Vehicle::with('class')->findOrFail($vehicle_id);
        $min_rental_time = $vehicle->class->min_rental_time ?? null;
        // Fetch CDW options based on class_id
        $class_id = $vehicle->class->id ?? null;


        $cdws = CDW::with(['rate'])
        ->where('cdw_class_id', $vehicle->class->cdw_class_id)
        ->get();



        // All optional inputs as per form
        $search_pickup_date = $request->input('search_pickup_date');
        $search_pickup_time = $request->input('search_pickup_time');
        $search_return_date = $request->input('search_return_date');
        $search_return_time = $request->input('search_return_time');
        $search_pickup_location = $request->input('search_pickup_location');
        $search_return_location = $request->input('search_return_location');
        $class_id = $request->input('class_id');
        $coupon = $request->input('coupon');
        $agent_code = $request->input('agent_code');
        $AgentName = $request->input('AgentName');
        $search_vehicle = $request->input('search_vehicle');
        $search_driver = $request->input('search_driver');
        $klia = $request->input('klia');
        $opt = $request->input('opt');
       

        // NO address variables here since form doesn’t have them
        $options = OptionalRental::all();


        return view('reservation.counter-reservation-form', compact(
            'nric', 'vehicle', 'customer',
            'search_pickup_date', 'search_pickup_time',
            'search_return_date', 'search_return_time',
            'search_pickup_location', 'search_return_location',
            'class_id', 'coupon', 'agent_code', 'AgentName',
            'search_vehicle', 'search_driver',
            'klia', 'opt','min_rental_time','cdws','options'
        ));
    }



    public function submitReservation(Request $request)
    {
        $validated = $request->validate([
            'nric' => 'required|string',
            'vehicle_id' => 'required|exists:vehicle,id',
            'search_pickup_date' => 'required|date',
            'search_pickup_time' => 'required',
            'search_return_date' => 'required|date',
            'search_return_time' => 'required',
            'pickup_location_id' => 'required|exists:location,id',
            'return_location_id' => 'required|exists:location,id',
            'cdw_id' => 'nullable|exists:cdw,id',
            'coupon' => 'nullable|string',
            'checklist_options' => 'nullable|array',
        ]);

        // checklist_options must be serialized to be passed in URL
        if (isset($validated['checklist_options'])) {
            $validated['checklist_options'] = implode(',', $validated['checklist_options']);
        }

        // Redirect to GET summary page with all details as query string
        return redirect()->route('reservation.summary', $validated);
    }

    public function showReservationSummary(Request $request)
    {
        $data = $request->all();

        // checklist_options will be comma-separated string; turn it into array if present
        $checklistIds = isset($data['checklist_options']) ? explode(',', $data['checklist_options']) : [];

        // Load customer and vehicle (with relations!)
        $customer = Customer::where('nric_no', $data['nric'] ?? '')->first();
        if (!$customer && !empty($data['nric'])) {
            $customer = new \App\Models\Customer();
            $customer->nric_no = $data['nric'];
        }

        $vehicle = Vehicle::with('class.priceClass')->find($data['vehicle_id'] ?? null);
        if (!$vehicle) {
            return back()->with('error', 'Vehicle not found. Please re-select the vehicle.');
        }

        // Load CDW
        $cdw = null;
        if (!empty($data['cdw_id'])) {
            $cdw = CDW::with('rate')->find($data['cdw_id']);
        }

        $pickupLocation = LocationBooking::find($data['pickup_location_id'] ?? null);
        $returnLocation = LocationBooking::find($data['return_location_id'] ?? null);

        $checklistItems = count($checklistIds) ? OptionalRental::whereIn('id', $checklistIds)->get() : collect();

        // Calculate original rental duration
        $start = \Carbon\Carbon::parse(($data['search_pickup_date'] ?? '') . ' ' . ($data['search_pickup_time'] ?? ''));
        $end = \Carbon\Carbon::parse(($data['search_return_date'] ?? '') . ' ' . ($data['search_return_time'] ?? ''));
        $totalMinutes = $start->diffInMinutes($end);
        $origDay = intdiv($totalMinutes, 1440);      // 1440 minutes in a day
        $origHour = intdiv($totalMinutes % 1440, 60); // Remaining hours

        // Handle more than 30 days
        $monthcount = 0;
        $monthly_subtotal = 0;
        $priceClass = optional(optional($vehicle->class)->priceClass);
        $booking = $priceClass->booking ?? 0;

        $day = $origDay;
        $hour = $origHour;
        if ($day > 30) {
            $monthcount = intdiv($day, 30);
            $monthly_subtotal = $monthcount * ($priceClass->monthly ?? 0);
            $day = $day - ($monthcount * 30);
        }

        $rentalTotal = $this->calculateRentalSubtotal($vehicle, $day, $hour, $monthly_subtotal);

        // Calculate CDW Amount
        $cdwAmount = ($cdw && $cdw->rate && $cdw->rate->rate) ? ($cdw->rate->rate / 100) * $rentalTotal : 0;

        // Calculate Addons Total
        $addonsTotal = $checklistItems->where('amount_type', 'RM')->sum('amount');

        // --- Coupon/Discount Logic ---
        $couponCode = $data['coupon'] ?? null;
        $coupon = null;
        $discount = 0;
        $couponLabel = '-';
        $couponValueLabel = 'N/A';
        $freeDays = 0;
        $freeHours = 0;

        if ($couponCode) {
            $coupon = \App\Models\Discount::where('code', $couponCode)
                ->where('start_date', '<=', now())
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->first();

            if ($coupon) {
                switch ($coupon->value_in) {
                    case 'A': // Fixed RM
                        $discount = $coupon->rate;
                        $couponLabel = $coupon->code;
                        $couponValueLabel = '-RM ' . number_format($discount, 2);
                        break;
                    case 'P': // Percent
                        $discount = ($rentalTotal * $coupon->rate) / 100;
                        $couponLabel = $coupon->code . " (-{$coupon->rate}%)";
                        $couponValueLabel = '-RM ' . number_format($discount, 2);
                        break;
                    case 'D': // Free days
                        $freeDays = (int)$coupon->rate;
                        $couponLabel = $coupon->code . " (Free {$freeDays} Day(s))";
                        $couponValueLabel = "Free {$freeDays} Day(s)";
                        break;
                    case 'H': // Free hours
                        $freeHours = (int)$coupon->rate;
                        $couponLabel = $coupon->code . " (Free {$freeHours} Hour(s))";
                        $couponValueLabel = "Free {$freeHours} Hour(s)";
                        break;
                    default:
                        $couponLabel = $coupon->code;
                }
            }
        }

        // Calculate Grand Total (only actual RM/PERCENT coupon affects total)
        $grand_total = $rentalTotal + $cdwAmount + $addonsTotal - $discount;

        // Adjust display for free days/hours
        $displayDay = $origDay + $freeDays;
        $displayHour = $origHour + $freeHours;

        // Calculate adjusted end datetime for display
        $displayEnd = $end->copy();
        if ($freeDays > 0) $displayEnd->addDays($freeDays);
        if ($freeHours > 0) $displayEnd->addHours($freeHours);

        return view('reservation.reservation-summary', [
            'validated'      => $data,
            'customer'       => $customer,
            'vehicle'        => $vehicle,
            'cdw'            => $cdw,
            'pickupLocation' => $pickupLocation,
            'returnLocation' => $returnLocation,
            'checklistItems' => $checklistItems,
            'origDay'        => $origDay,
            'origHour'       => $origHour,
            'freeDays'       => $freeDays,
            'freeHours'      => $freeHours,
            'displayDay'     => $displayDay,
            'displayHour'    => $displayHour,
            'displayEndDate' => $displayEnd->format('Y-m-d'),
            'displayEndTime' => $displayEnd->format('H:i'),
            'rentalTotal'    => $rentalTotal,
            'booking'        => $booking,
            'coupon'         => $coupon,
            'couponLabel'    => $couponLabel,
            'couponValueLabel'=> $couponValueLabel,
            'discount'       => $discount,
            'est_total'      => $data['est_total'] ?? null,
            'subtotal'       => $rentalTotal,
            'agent_profit'   => $data['agent_profit'] ?? null,
            'agent_id'       => $data['agent_id'] ?? null,
            'cdw_id'         => $data['cdw_id'] ?? null,
            'cdwAmount'      => $cdwAmount,
            'addonsTotal'    => $addonsTotal,
            'grand_total'    => $grand_total,
        ]);
    }





    public function licenseUpdate(Request $request, $nric)
    {
        $customer = Customer::where('nric_no', $nric)->firstOrFail();

        $request->validate([
            'license_no' => 'required|string',
            'license_exp' => 'required|date',
            'identity_photo_front' => 'required|image',
            'identity_photo_back' => 'required|image',
        ]);

        // Handle image uploads
        if ($request->hasFile('identity_photo_front')) {
            $front = $request->file('identity_photo_front');
            $frontName = time().'_front.'.$front->getClientOriginalExtension();
            $front->move(public_path('assets/img/customer/'), $frontName);
            $customer->identity_photo_front = $frontName;
        }
        if ($request->hasFile('identity_photo_back')) {
            $back = $request->file('identity_photo_back');
            $backName = time().'_back.'.$back->getClientOriginalExtension();
            $back->move(public_path('assets/img/customer/'), $backName);
            $customer->identity_photo_back = $backName;
        }

        // Update license info
        $customer->license_no = $request->input('license_no');
        $customer->license_exp = $request->input('license_exp');
        $customer->save();

        // Collect all the old parameters
        $queryParams = $request->except(['_token']); // Exclude the token
        $queryParams['tab'] = 'walkin'; // Make sure tab is set

        return redirect()
            ->route('reservation.summary', $queryParams)
            ->with('success', 'License information updated successfully!');

    }


    public function store1(Request $request)
    {
        // 1. Validation (as per your Blade file)
        $validated = $request->validate([
            'cdw_id'               => 'nullable|integer|exists:cdw,id',
            'vehicle_id'           => 'required|integer|exists:vehicle,id',
            'pickup_location_id'   => 'required|integer|exists:location,id',
            'return_location_id'   => 'required|integer|exists:location,id',
            'search_pickup_date'   => 'required|date',
            'search_pickup_time'   => 'required',
            'search_return_date'   => 'required|date',
            'search_return_time'   => 'required',
            'nric_no'              => 'required|string',
            'nric_type'            => 'nullable|string',
            'title'                => 'nullable|string',
            'firstname'            => 'required|string',
            'lastname'             => 'required|string',
            'gender'               => 'required|string',
            'dob'                  => 'nullable|date',
            'race'                 => 'required|string',
            'phone_no'             => 'required|string',
            'phone_no2'            => 'nullable|string',
            'email'                => 'required|email',
            'license_no'           => 'nullable|string',
            'license_exp'          => 'nullable|date',
            'identity_photo_front' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'selfie_nric'          => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'license_front'        => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'license_back'         => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'utility_photo'        => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'working_photo'        => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'address'              => 'nullable|string',
            'postcode'             => 'nullable|string',
            'city'                 => 'nullable|string',
            'country'              => 'nullable|string',
            'ref_name'             => 'nullable|string',
            'ref_phoneno'          => 'nullable|string',
            'ref_relationship'     => 'nullable|string',
            'ref_address'          => 'nullable|string',
            'payment_receipt'      => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'coupon'               => 'nullable|string',
            'discount'             => 'nullable|numeric',
            'subtotal'             => 'nullable|numeric',
            'est_total'            => 'nullable|numeric',
            'agent_id'             => 'nullable|integer',
            'agent_code'           => 'nullable|string',
            'refund_dep_status'    => 'nullable|string',
            'bookingfee'           => 'nullable|numeric',
            'payment_amount'       => 'required|numeric',
            'payment_status'       => 'required|string',
            'payment_type'         => 'required|string',
            'survey_type'          => 'nullable|string',
            'checklist_options'    => 'nullable|string', // Submitted as comma-separated
        ]);

        // 2. Customer Insert/Update (as per your logic)
        $age = null;
        if (!empty($validated['dob'])) {
            $age = Carbon::parse($validated['dob'])->age;
        }

        $customer = Customer::firstOrNew(['nric_no' => $validated['nric_no']]);
        $formSource = $request->input('form_source', 'booking');
        $isNew = !$customer->exists;

        // Status logic
        if ($isNew) {
            $customer->status = ($formSource === 'walkin') ? 'A' : 'P';
        } else {
            if ($customer->status === 'A') {
                // Stay as 'A'
            } elseif ($customer->status === 'P' && $formSource === 'walkin') {
                $customer->status = 'A';
            } elseif (is_null($customer->status)) {
                $customer->status = ($formSource === 'walkin') ? 'A' : 'P';
            }
        }

        // Field update logic
        $customer->nric_type        = $validated['nric_type']      ?? $customer->nric_type;
        $customer->title            = $validated['title']          ?? $customer->title;
        $customer->firstname        = strtoupper($validated['firstname'] ?? $customer->firstname);
        $customer->lastname         = strtoupper($validated['lastname'] ?? $customer->lastname);
        $customer->gender           = $validated['gender']         ?? $customer->gender;
        if (isset($validated['dob']) && $validated['dob']) {
            $customer->dob = $validated['dob'];
            $customer->age = Carbon::parse($validated['dob'])->age;
        }
        $customer->race             = $validated['race']           ?? $customer->race;
        $customer->phone_no         = $validated['phone_no']       ?? $customer->phone_no;
        $customer->phone_no2        = $validated['phone_no2']      ?? $customer->phone_no2;
        $customer->email            = $validated['email']          ?? $customer->email;
        $customer->license_no       = $validated['license_no']     ?? $customer->license_no;
        $customer->license_exp      = $validated['license_exp']    ?? $customer->license_exp;
        $customer->address          = $validated['address']        ?? $customer->address;
        $customer->postcode         = $validated['postcode']       ?? $customer->postcode;
        $customer->city             = $validated['city']           ?? $customer->city;
        $customer->country          = $validated['country']        ?? $customer->country;
        $customer->ref_name         = $validated['ref_name']       ?? $customer->ref_name;
        $customer->ref_phoneno      = $validated['ref_phoneno']    ?? $customer->ref_phoneno;
        $customer->ref_relationship = $validated['ref_relationship'] ?? $customer->ref_relationship;
        $customer->ref_address      = $validated['ref_address']    ?? $customer->ref_address;
        $customer->survey_type      = $validated['survey_type']    ?? $customer->survey_type;
        $customer->cid              = auth()->id();
        $customer->cdate            = now();
        $customer->mid              = auth()->id();
        $customer->mdate            = now();
        $customer->save();

        // 3. Get original rental period (before coupon extension)
        $pickupDatetime = $validated['search_pickup_date'] . ' ' . $validated['search_pickup_time'];
        $returnDatetime = $validated['search_return_date'] . ' ' . $validated['search_return_time'];
        $pickupCarbon = Carbon::parse($pickupDatetime);
        $returnCarbon = Carbon::parse($returnDatetime);

        $origDay = $pickupCarbon->diffInDays($returnCarbon);
        $origHour = $pickupCarbon->copy()->addDays($origDay)->diffInHours($returnCarbon);

        // 4. Coupon/discount calculation (for accurate duration and payment)
        $couponCode = $validated['coupon'] ?? null;
        $discount = 0;
        $freeDays = 0;
        $freeHours = 0;

        if ($couponCode) {
            $coupon = \App\Models\Discount::where('code', $couponCode)
                ->where('start_date', '<=', now())
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->first();

            if ($coupon) {
                switch ($coupon->value_in) {
                    case 'A': // Absolute RM
                        $discount = $coupon->rate;
                        break;
                    case 'P': // Percent
                        // We'll apply this after rental calculation below
                        break;
                    case 'D':
                        $freeDays = (int)$coupon->rate;
                        break;
                    case 'H':
                        $freeHours = (int)$coupon->rate;
                        break;
                }
            }
        }

        
        // 5. Calculate the *final* rental period (with free days/hours if any)
        $finalReturn = $returnCarbon->copy();
        if ($freeDays > 0) {
            $finalReturn = $finalReturn->addDays($freeDays);
        }
        if ($freeHours > 0) {
            $finalReturn = $finalReturn->addHours($freeHours);
        }
        $finalDay = $pickupCarbon->diffInDays($finalReturn);
        $finalHour = $pickupCarbon->copy()->addDays($finalDay)->diffInHours($finalReturn);

        // 5.1 Use original duration for calculation if Day or Hour Coupon applied
        if (isset($coupon) && in_array($coupon->value_in, ['D', 'H'])) {
            $effectiveDay = $origDay;
            $effectiveHour = $origHour;
        } else {
            $effectiveDay = $finalDay;
            $effectiveHour = $finalHour;
        }


        // 6. Pricing: recalculate rental, cdw, add-ons
        $vehicle = Vehicle::with('class.priceClass')->find($validated['vehicle_id']);
        $priceClass = optional(optional($vehicle->class)->priceClass);
        $bookingFee = $priceClass->booking ?? 0;

        // Calculate the total rental (YOUR business logic)
        $monthly_subtotal = 0;
        $monthcount = 0;
        $day = $effectiveDay;     // Use effective duration
        $hour = $effectiveHour;

        if ($day > 30) {
            $monthcount = intdiv($day, 30);
            $monthly_subtotal = $monthcount * ($priceClass->monthly ?? 0);
            $day = $day - ($monthcount * 30);
        }
        // You can reuse your subtotal logic:
        $rentalTotal = $this->calculateRentalSubtotal($vehicle, $day, $hour, $monthly_subtotal);

        // CDW
        $cdw = null;
        $cdwAmount = 0;
        if (!empty($validated['cdw_id'])) {
            $cdw = CDW::with('rate')->find($validated['cdw_id']);
            if ($cdw && $cdw->rate && $cdw->rate->rate) {
                $cdwAmount = ($cdw->rate->rate / 100) * $rentalTotal;
            }
        }
        
        // Addons
        $checklistIds = isset($validated['checklist_options']) ? explode(',', $validated['checklist_options']) : [];
        $checklistItems = count($checklistIds) ? OptionalRental::whereIn('id', $checklistIds)->get() : collect();
        $addonsTotal = $checklistItems->where('amount_type', 'RM')->sum('amount');

        // Calculate discount for percent after subtotal calculated
        if (isset($coupon) && $coupon->value_in == 'P') {
            $discount = (($rentalTotal + $cdwAmount + $addonsTotal) * $coupon->rate) / 100;
        }

        // 7. Grand total
        $grand_total = $rentalTotal + $cdwAmount + $addonsTotal - $discount;

        // 8. BookingTrans Insert (use final/extended period and new totals)
        $pickupLoc = LocationBooking::find($validated['pickup_location_id']);
        $mymonth = now()->format('m');
        $myyear = now()->format('Y');
        $yearLetter = chr(65 + (now()->format('Y') - 2022)); // A=2022, B=2023, ...
        $monthLetter = chr(64 + (int)$mymonth);

        $lastBooking = BookingTrans::where('month', $mymonth)->where('year', $myyear)->orderByDesc('sequence')->first();
        $sequence = $lastBooking ? ($lastBooking->sequence + 1) : 1;
        $agr_no = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        $agreement_no = ($pickupLoc->initial ?? 'X') . $yearLetter . $monthLetter . $agr_no;

        $refund_dep_payment = $validated['refund_dep_status'] ?? null;
        $refund_dep_status = $refund_dep_payment === 'Collect' ? 'Collect' : 'Paid';

        $booking = BookingTrans::create([
            'sequence'          => $sequence,
            'month'             => $mymonth,
            'year'              => $myyear,
            'agreement_no'      => $agreement_no,
            'pickup_date'       => $pickupCarbon->format('Y-m-d H:i:s'),
            'pickup_time'       => $pickupCarbon->format('H:i:s'),
            'pickup_location'   => $validated['pickup_location_id'],
            'return_date'       => $finalReturn->format('Y-m-d H:i:s'), // extended
            'return_time'       => $finalReturn->format('H:i:s'),
            'return_location'   => $validated['return_location_id'],
            'vehicle_id'        => $validated['vehicle_id'],
            'day'               => $finalDay,
            'sub_total'         => $rentalTotal,
            'est_total'         => $grand_total-$cdwAmount,
            'discount_coupon'   => $validated['coupon'] ?? null,
            'discount_amount'   => $discount,
            'payment_status'    => $validated['payment_status'],
            'payment_type'      => $validated['payment_type'],
            'created'           => now(),
            'available'         => 'Booked',
            'customer_id'       => $customer->id,
            'refund_dep'        => $validated['bookingfee'] ?? null,
            'refund_dep_payment'=> $refund_dep_payment,   // user selected value
            'refund_dep_status' => $refund_dep_status,    // only 'Collect' or 'Paid'
            'balance'           => $validated['payment_amount'],
            'staff_id'          => auth()->id(),
            'cdate'             => now(),
            'mid'               => auth()->id(),
            'mdate'             => now(),
            'branch'            => $pickupLoc->description ?? '',
            'agent_id'          => $validated['agent_id'] ?? 0,
            'survey_type'       => $validated['survey_type'] ?? null,
        ]);
        $booking->agreement_no = $agreement_no;
        $booking->save();

        // 9. Handle 6 Customer Photos (same as before)
        $photoFields = [
            'identity_photo_front', // 0
            'selfie_nric',          // 1
            'license_front',        // 2
            'license_back',         // 3
            'utility_photo',        // 4
            'working_photo',        // 5
        ];
        foreach ($photoFields as $no => $imgField) {
            if ($request->hasFile($imgField)) {
                $file = $request->file($imgField);
                $filename = $customer->nric_no . '-' . $imgField . '.' . $file->extension();
                $fileSize = $file->getSize();
                $fileType = $file->getClientMimeType();
                $file->move(public_path('assets/img/customer/'), $filename);
                if (class_exists(\App\Models\UploadData::class)) {
                    \App\Models\UploadData::create([
                        'position'           => 'customer',
                        'no'                 => $no,
                        'customer_id'        => $customer->id,
                        'booking_trans_id'   => $booking->id,
                        'file_name'          => $filename,
                        'file_size'          => $fileSize,
                        'file_type'          => $fileType,
                        'created'            => now(),
                        'cid'                => auth()->id(),
                    ]);
                }
            }
        }

        // 10. Update Vehicle Status


        // $vehicle->availability = 'Booked';
        // $vehicle->save();




        // 11. Payment Receipt Upload (GET INFO BEFORE MOVE)
        $receipt_name = null;
        $receipt_size = null;
        $receipt_type = null;
        if ($request->hasFile('payment_receipt')) {
            $receipt = $request->file('payment_receipt');
            $receipt_name = 'booking_receipt-' . $booking->id . '.' . $receipt->extension();
            $receipt_size = $receipt->getSize();          // GET BEFORE MOVE
            $receipt_type = $receipt->getClientMimeType();// GET BEFORE MOVE
            $receipt->move(public_path('assets/img/receipt/'), $receipt_name);
            if (class_exists(\App\Models\UploadData::class)) {
                \App\Models\UploadData::create([
                    'position' => 'booking_receipt',
                    'booking_trans_id' => $booking->id,
                    'file_name' => $receipt_name,
                    'file_size' => $receipt_size,
                    'file_type' => $receipt_type,
                    'vehicle_id' => $vehicle->id,
                    'created' => now(),
                    'cid' => auth()->id(),
                ]);
            }
        }

        // 12. Add-ons/Checklist - Set Y or X for each add-on column
        $optionColumnMap = [
            1  => 'car_add_driver',         // Additional Driver
            3  => 'car_in_sticker_p',       // Sticker P 2 units
            4  => 'car_in_touch_n_go',      // Touch n Go Card
            6  => 'car_driver',             // Driver
            8  => 'car_in_usb_charger',     // USB Charger
            9  => 'car_in_smart_tag',       // Smart Tag
            10 => 'car_in_child_seat',      // Child Seat
        ];
        // Get selected options (as array of IDs)
        $selectedOptionIds = [];
        if (!empty($validated['checklist_options'])) {
            $selectedOptionIds = is_array($validated['checklist_options'])
                ? $validated['checklist_options']
                : explode(',', $validated['checklist_options']);
        }
        // Prepare checklist data: all X by default
        $checklistData = [
            'booking_trans_id' => $booking->id,
        ];
        foreach ($optionColumnMap as $optionId => $columnName) {
            $checklistData[$columnName] = in_array($optionId, $selectedOptionIds) ? 'Y' : 'X';
        }
        Checklist::create($checklistData);


        // 13. Sale/Deposit (Booking Fee Sale)
        $bookingFee = $validated['bookingfee'] ?? 0;
        if ($bookingFee > 0) {
            Sale::create([
                'title'             => 'Booking Deposit in',
                'type'              => 'Booking',
                'booking_trans_id'  => $booking->id,
                'vehicle_id'        => $vehicle->id,
                'total_day'         => $finalDay,
                'total_sale'        => $bookingFee,
                'payment_status'    => $refund_dep_status,
                'payment_type'      => $refund_dep_payment,
                'image'             => $receipt_name,
                'pickup_date'       => $pickupCarbon->format('Y-m-d H:i:s'),
                'return_date'       => $finalReturn->format('Y-m-d H:i:s'),
                'staff_id'          => auth()->id(),
                'created'           => now(),
            ]);
        }

        // 14. CDW Sale (if needed)
        if ($cdwAmount > 0 && !empty($validated['cdw_id'])) {
            // $cdwSale = Sale::create([
            //     'title'             => 'CDW log',
            //     'type'              => 'CDW',
            //     'booking_trans_id'  => $booking->id,
            //     'vehicle_id'        => $vehicle->id,
            //     'total_day'         => $finalDay,
            //     'total_sale'        => $cdwAmount,
            //     'payment_status'    => $refund_dep_status,
            //     'payment_type'      => $refund_dep_payment,
            //     'pickup_date'       => $pickupCarbon->format('Y-m-d H:i:s'),
            //     'return_date'       => $finalReturn->format('Y-m-d H:i:s'),
            //     'staff_id'          => auth()->id(),
            //     'created'           => now(),
            // ]);

            $status = (isset($validated['payment_status']) && $validated['payment_status'] === 'FullRental')
                ? 'Paid'
                : 'Collect';

            CdwLog::create([
                'booking_trans_id' => $booking->id,
                'cdw_id'           => $validated['cdw_id'],
                // 'sale_id'          => $cdwSale->id,
                'amount'           => $cdwAmount,
                'status'           => $status,
                'cid'              => auth()->id(),
                'created'          => now(),
                'mid'              => auth()->id(),
                'modified'         => now(),
            ]);
        }

        // 15. Extend Insert/Update (AUTO)
        // Get the date range for the auto extend
        $extend_from_date = $finalReturn->format('Y-m-d') . ' ' . $validated['search_return_time'];
        $extend_to_date   = $finalReturn->copy()->addDay()->format('Y-m-d') . ' ' . $validated['search_return_time'];

        $extend = Extend::where('vehicle_id', $vehicle->id)
            ->where('extend_type', 'auto')
            ->first();

        if (!$extend) {
            // Insert new extend
            Extend::create([
                'vehicle_id'         => $vehicle->id,
                'booking_trans_id'   => $booking->id,
                'extend_type'        => 'auto',
                'auto_extend_status' => 'Pickup',
                'extend_from_date'   => $extend_from_date,
                'extend_from_time'   => $validated['search_return_time'],
                'extend_to_date'     => $extend_to_date,
                'extend_to_time'     => $validated['search_return_time'],
                'cid'                => auth()->id(),
                'c_date'             => now(),
            ]);
        } else {
            // Update existing extend
            $extend->update([
                'booking_trans_id'   => $booking->id,
                'auto_extend_status' => 'Pickup',
                'extend_from_date'   => $extend_from_date,
                'extend_from_time'   => $validated['search_return_time'],
                'extend_to_date'     => $extend_to_date,
                'extend_to_time'     => $validated['search_return_time'],
                'mid'                => auth()->id(),
                'm_date'             => now(),
            ]);
        }

        // Build email payload
        // $pickupLocDesc  = optional(\App\Models\LocationBooking::find($validated['pickup_location_id']))->description ?? '';
        // $returnLocDesc  = optional(\App\Models\LocationBooking::find($validated['return_location_id']))->description ?? '';
        // $vehicleMake    = optional($vehicle)->make ?? '';
        // $vehicleModel   = optional($vehicle)->model ?? '';
        // $createOnline   = $request->boolean('createonline'); // align with your legacy flag

        // $tempPassword = null;
        // if ($createOnline) {
        //     // Generate temp password, store hashed (never plain)
        //     $tempPassword = Str::random(8);
        //     $customer->password = Hash::make($tempPassword);
        //     $customer->save();
        // }

        // $emailData = [
        //     'agreement_no'   => $booking->agreement_no,
        //     'pickup_date'    => $pickupCarbon->format('Y-m-d'),
        //     'pickup_time'    => $pickupCarbon->format('H:i'),
        //     'pickup_location'=> $pickupLocDesc,
        //     'return_date'    => $finalReturn->format('Y-m-d'),
        //     'return_time'    => $finalReturn->format('H:i'),
        //     'return_location'=> $returnLocDesc,
        //     'vehicle_make'   => $vehicleMake,
        //     'vehicle_model'  => $vehicleModel,
        //     'fullname'       => trim(($customer->title ? $customer->title.' ' : '').$customer->firstname.' '.$customer->lastname),
        //     'phone_no'       => $customer->phone_no,
        //     'address'        => $customer->address,
        //     'postcode'       => $customer->postcode,
        //     'city'           => $customer->city,
        //     'country'        => $customer->country,
        //     'sub_total'      => $rentalTotal,
        //     'est_total'      => $grand_total,   // or $grand_total - $cdwAmount if that’s what you show
        //     'create_online'  => $createOnline,
        //     'temp_password'  => $tempPassword,
        //     // link to your Laravel route instead of raw PHP redirect
        //     'agreement_url'  => route('reservation.view', ['booking_id' => $booking->id]),
        // ];

        // try {
        //     // send to customer
        //     Mail::to($customer->email)->send(new BookingDetailsMail($emailData));

        //     // optional: CC staff / branch inbox
        //     // Mail::to($customer->email)->cc('branch@example.com')->send(new BookingDetailsMail($emailData));

        // } catch (\Throwable $e) {
        //     report($e);
        //     // Don’t block the booking flow—just flash a warning
        //     return redirect()
        //         ->route('reservation.view', ['booking_id' => $booking->id])
        //         ->with('warning', 'Reservation created but email failed: '.$e->getMessage());
        // }

        // Optional customer online creation flag (align with your legacy "createonline=true")
        $createOnline = $request->boolean('createonline');

        $tempPassword = null;
        if ($createOnline) {
            $tempPassword = Str::random(8);   // like your legacy random hex, but readable
            $customer->password = Hash::make($tempPassword); // store hashed, never plaintext
            $customer->save();
        }

        // Resolve human-readable pickup/return location like your CASE in legacy
        $pickupLocDesc = optional(\App\Models\LocationBooking::find($validated['pickup_location_id']))->description ?? '';
        $returnLocDesc = optional(\App\Models\LocationBooking::find($validated['return_location_id']))->description ?? '';

        // Company logo absolute URL (use DB if you store it)
        $companyImageName = \DB::table('company')->where('id', 1)->value('image'); // adjust to your model if exists
        $companyImageUrl  = $companyImageName
            ? url('dashboard/assets/img/'.$companyImageName) // absolute URL
            : url('dashboard/assets/img/logo.png');

        $emailData = [
            'agreement_no'     => $booking->agreement_no,
            'pickup_date'      => \Carbon\Carbon::parse($booking->pickup_date)->format('d/m/Y'),
            'pickup_time'      => \Carbon\Carbon::parse($booking->pickup_time)->format('H:i:s'),
            'pickup_location'  => $pickupLocDesc,
            'return_date'      => \Carbon\Carbon::parse($booking->return_date)->format('d/m/Y'),
            'return_time'      => \Carbon\Carbon::parse($booking->return_time)->format('H:i:s'),
            'return_location'  => $returnLocDesc,

            'make'             => optional($vehicle)->make ?? '',
            'model'            => optional($vehicle)->model ?? '',

            'fullname'         => trim(($customer->title ? $customer->title.' ' : '').$customer->firstname.' '.$customer->lastname),
            'phone_no'         => $customer->phone_no,
            'address'          => $customer->address,
            'postcode'         => $customer->postcode,
            'city'             => $customer->city,
            'country'          => $customer->country,

            'sub_total'        => $rentalTotal,
            'est_total'        => $grand_total,         // or $grand_total - $cdwAmount if that’s what you show
            'refund_dep'       => $validated['bookingfee'] ?? null,

            // Links/images used in the legacy design
            'company_image_url'   => $companyImageUrl,
            'company_base_url'    => config('app.url') ?? 'https://www.myezgo.com',
            'product_listing_url' => (config('app.url') ? rtrim(config('app.url'),'/').'/product_listing.php' : 'https://www.myezgo.com/product_listing.php'),
            'login_url'           => (config('app.url') ? rtrim(config('app.url'),'/').'/login.php' : 'https://www.myezgo.com/login.php'),
            'about_url'           => (config('app.url') ? rtrim(config('app.url'),'/').'/about_us.php' : 'https://www.myezgo.com/about_us.php'),
            'agreement_url'       => route('reservation.view', ['booking_id' => $booking->id]),

            // Account activation block
            'create_online'    => $createOnline,
            'temp_password'    => $tempPassword,
        ];

        // Send to customer (match legacy “mail2” for booking)
        Mail::to($customer->email)->send(new BookingDetailsMail($emailData));

        // (Optional) staff notification (match your legacy “mail”)
        // You can reuse the same template or create a separate one for staff:
        # Mail::to('staff@example.com')->send(new BookingDetailsMail($emailData));


        
        // 16. Redirect to reservation summary/index with success
        return redirect()->route('reservation.view', ['booking_id' => $booking->id])
            ->with('success', 'Reservation successful! Agreement No: ' . $agreement_no);
    }




    /**
     * Calculate rental subtotal based on PriceClass logic
     */
    protected function calculateRentalSubtotal($vehicle, $day, $hour, $monthly_subtotal = 0)
    {
        // Defensive: Make sure relations are loaded and exist
        $priceClass = optional(optional($vehicle->class)->priceClass);

        // Map hour keys to price class fields
        $hourlyRates = [
            0 => 0,
            1 => $priceClass->hour,
            2 => $priceClass->hour2,
            3 => $priceClass->hour3,
            4 => $priceClass->hour4,
            5 => $priceClass->hour5,
            6 => $priceClass->hour6,
            7 => $priceClass->hour7,
            8 => $priceClass->hour8,
            9 => $priceClass->hour9,
            10 => $priceClass->hour10,
            11 => $priceClass->hour11,
            12 => $priceClass->halfday,
            13 => $priceClass->hour13,
            14 => $priceClass->hour14,
            15 => $priceClass->hour15,
            16 => $priceClass->hour16,
            17 => $priceClass->hour17,
            18 => $priceClass->hour18,
            19 => $priceClass->hour19,
            20 => $priceClass->hour20,
            21 => $priceClass->hour21,
            22 => $priceClass->hour22,
            23 => $priceClass->hour23,
        ];

        $dailyRates = [
            0 => 0,
            1 => $priceClass->oneday,
            2 => $priceClass->twoday,
            3 => $priceClass->threeday,
            4 => $priceClass->fourday,
            5 => $priceClass->fiveday,
            6 => $priceClass->sixday,
            7 => $priceClass->weekly,
            8 => $priceClass->eightday,
            9 => $priceClass->nineday,
            10 => $priceClass->tenday,
            11 => $priceClass->elevenday,
            12 => $priceClass->twelveday,
            13 => $priceClass->thirteenday,
            14 => $priceClass->fourteenday,
            15 => $priceClass->fifteenday,
            16 => $priceClass->sixteenday,
            17 => $priceClass->seventeenday,
            18 => $priceClass->eighteenday,
            19 => $priceClass->nineteenday,
            20 => $priceClass->twentyday,
            21 => $priceClass->twentyoneday,
            22 => $priceClass->twentytwoday,
            23 => $priceClass->twentythreeday,
            24 => $priceClass->twentyfourday,
            25 => $priceClass->twentyfiveday,
            26 => $priceClass->twentysixday,
            27 => $priceClass->twentysevenday,
            28 => $priceClass->twentyeightday,
            29 => $priceClass->twentynineday,
            30 => $priceClass->monthly,
        ];

        $time_subtotal = $hourlyRates[$hour] ?? 0;
        $daycalculate = $day;

        if ($daycalculate == 0) {
            $day_subtotal = 0;
            $time_day_subtotal = $time_subtotal + $day_subtotal;
        } else {
            $day_subtotal = $dailyRates[$daycalculate] ?? 0;
            $time_day_subtotal = $time_subtotal + $day_subtotal;

            // Cap at the next daily tier if time_day_subtotal >= next daily rate
            $nextTier = $dailyRates[$daycalculate + 1] ?? null;
            if ($nextTier && $time_day_subtotal >= $nextTier) {
                $time_day_subtotal = $nextTier;
            }

            // Special case for day 30
            if ($daycalculate == 30) {
                $car_rate_extra = ($priceClass->monthly ?? 0) + ($priceClass->oneday ?? 0);
                if ($time_day_subtotal >= $car_rate_extra) {
                    $time_day_subtotal = $car_rate_extra;
                }
            }
        }

        $subtotal = $time_day_subtotal + ($monthly_subtotal ?? 0);
        return $subtotal;
    }

    


    public function ReservationList(Request $request)
{
    $query = BookingTrans::with(['vehicle', 'customer']);

    // Filtering
    if ($request->filled('search_nricno')) {
        $search = $request->search_nricno;
        if ($request->search_type == 'type_agreement') {
            $query->where('agreement_no', 'like', "%$search%");
        } elseif ($request->search_type == 'type_name') {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                  ->orWhere('lastname', 'like', "%$search%");
            });
        } elseif ($request->search_type == 'type_nric') {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('nric_no', 'like', "%$search%");
            });
        } else {
            $query->where(function ($q) use ($search) {
                $q->where('agreement_no', 'like', "%$search%")
                  ->orWhereHas('customer', function ($qq) use ($search) {
                      $qq->where('firstname', 'like', "%$search%")
                         ->orWhere('lastname', 'like', "%$search%")
                         ->orWhere('nric_no', 'like', "%$search%");
                  })
                  ->orWhereHas('vehicle', function ($qq) use ($search) {
                      $qq->where('reg_no', 'like', "%$search%");
                  });
            });
        }
    }
    if ($request->filled('search_vehicle')) {
        $query->whereHas('vehicle', function ($q) use ($request) {
            $q->where('class_id', $request->search_vehicle);
        });
    }
    if ($request->filled('search_status')) {
        if ($request->search_status == 'BookedOutExtend') {
            $query->whereIn('available', ['Booked', 'Out', 'Extend']);
        } else {
            $query->where('available', $request->search_status);
        }
    }

    $query->orderByDesc('created');
    $reservations = $query->paginate(10)->onEachSide(2);

    // Add a flag for incomplete payment
    foreach ($reservations as $row) {
        $row->has_incomplete_payment = Sale::where('booking_trans_id', $row->id)
            ->where('title', 'Outstanding Extend')
            ->where('payment_status', 'Collect')
            ->exists();
    }

    $classes = ClassModel::where('status', 'A')->orderBy('class_name')->get();

    return view('reservation.reservation_list', compact('reservations', 'classes'));
}



public function ReservationView($booking_id)
{
    // Load booking and related data
    $booking = BookingTrans::with(['customer', 'vehicle', 'sales', 'user'])->findOrFail($booking_id);

    // CDW Amount
    $cdwAmount = CDWLog::where('booking_trans_id', $booking_id)->sum('amount');
    $cdwLog = CDWLog::where('booking_trans_id', $booking_id)->first();
    $cdwId = $cdwLog?->cdw_id;

    // Company Info
    $companyRow = Company::first();
    $company = [
        'image'   => $companyRow->image ?? 'default-logo.png',
        'name'    => $companyRow->company_name ?? 'Your Company Name',
        'website' => $companyRow->website_name ?? 'example.com',
        'address' => $companyRow->address ?? 'Your Address',
        'phone'   => $companyRow->phone_no ?? '0123456789',
        'reg_no'  => $companyRow->registration_no ?? '123456-X',
    ];

    // Customer Info
    $customer = $booking->customer;
    $fullname = trim(($customer->firstname ?? '') . ' ' . ($customer->lastname ?? ''));
    $customer_status = $customer->status ?? '-';
    $nric_no = $customer->nric_no ?? '-';
    $phone_no = $customer->phone_no ?? '-';
    $phone_no2 = $customer->phone_no2 ?? '-';
    $email = $customer->email ?? '-';
    $address = $customer->address ?? '-';
    $license_no = $customer->license_no ?? '-';
    $license_exp = $customer->license_exp ?? '-';
    $ref_name = $customer->ref_name ?? '-';
    $ref_phoneno = $customer->ref_phoneno ?? '-';
    $ref_relationship = $customer->ref_relationship ?? '-';

    // Uploads
    $uploads = UploadData::where('customer_id', $customer->id)
        ->where('position', 'customer')
        ->whereNotNull('file_name')
        ->where('file_size', '!=', 0)
        ->pluck('file_name')
        ->toArray();

    $allLabels = [
        'NRIC Front Photo',
        'Selfie with NRIC Photo',
        'License Photo (front)',
        'License Photo (back)',
        'Utility Photo',
        'Working Card/Proof Photo',
    ];
    $allUploads = [];
    foreach ($allLabels as $i => $label) {
        $file = $uploads[$i] ?? null;
        $allUploads[] = [
            'label' => $label,
            'file'  => $file
        ];
    }

    // Booking Receipt
    $booking_receipt_db = UploadData::where('booking_trans_id', $booking->id)
        ->where('position', 'booking_receipt')
        ->where('file_size', '!=', 0)
        ->orderByDesc('id')
        ->first();
    $booking_receipt = $booking_receipt_db
        ? [
            'file' => $booking_receipt_db->file_name,
            'created_at' => \Carbon\Carbon::parse($booking_receipt_db->created),
        ]
        : null;

    // Action Label
    $available = $booking->available;
    $insert_type = $booking->insert_type;
    $actionLabel = null;
    if ($available == "Booked") {
        if ($insert_type == "Customer") {
            $actionLabel = "Booked Online";
        } else {
            $sale = Sale::where('booking_trans_id', $booking_id)->where('type', 'Booking')->first();
            if ($sale) {
                $user = User::find($sale->staff_id);
                $actionLabel = "Booked by: " . ($user?->name ?? '');
            }
        }
    } elseif ($available == "Out") {
        $sale = Sale::where('booking_trans_id', $booking_id)->where('type', 'Sale')->first();
        if ($sale) {
            $user = User::find($sale->staff_id);
            $actionLabel = "Pickup by: " . ($user?->name ?? '');
        }
    } elseif ($available == "Park") {
        $sale = Sale::where('booking_trans_id', $booking_id)->where('type', 'Return')->first();
        if ($sale) {
            $user = User::find($sale->staff_id);
            $actionLabel = "Return by: " . ($user?->name ?? '');
        }
    } elseif ($available == "Extend") {
        $sale = Sale::where('booking_trans_id', $booking_id)->where('type', 'Extend')->orderByDesc('id')->first();
        if ($sale) {
            $user = User::find($sale->staff_id);
            $actionLabel = "Latest Extend by: " . ($user?->name ?? '');
        }
    }

    // Maintenance Warnings
    $vehicle_id = $booking->vehicle?->id;
    $dueWarnings = [];
    $maintenanceChecks = [
        FleetMaintenanceEngineoil::class => 'engine oil',
        FleetMaintenanceGearoil::class => 'gear oil',
        FleetMaintenanceSparkplug::class => 'spark plug replacement',
        FleetMaintenanceAutofilter::class => 'auto filter replacement',
    ];
    foreach ($maintenanceChecks as $model => $label) {
        $row = $model::where('vehicle_id', $vehicle_id)->with('vehicle')->first();
        if ($row && $row->status == 'Due') {
            $dueWarnings[] = "This vehicle ({$row->vehicle->reg_no}) is currently exceeding <b>{$label}</b> mileage.<br>Mileage: {$row->mileage}<br>Due: {$row->next_due}";
        }
    }
    $battery = FleetMaintenanceBattery::where('vehicle_id', $vehicle_id)->with('vehicle')->first();
    if ($battery && $battery->status == 'Due') {
        $dueMsg = "This vehicle ({$battery->vehicle->reg_no}) is currently exceeding <b>battery due date</b>.<br>Battery Last Change: " . ($battery->last_update ? date('d/m/Y', strtotime($battery->last_update)) : "-");
        $dueMsg .= "<br>Battery Due: " . ($battery->next_update && $battery->next_update != "0000-00-00 00:00:00" ? date('d/m/Y', strtotime($battery->next_update)) : "<i>Unset</i>");
        $dueWarnings[] = $dueMsg;
    }

    // Vehicle Information
    $currentVehicle = $booking->vehicle;
    $discount_coupon = $booking->discount_coupon ?? '';

    // Date only filtering
    $pickupDateOnly = \Carbon\Carbon::parse($booking->pickup_date)->format('Y-m-d');
    $returnDateOnly = \Carbon\Carbon::parse($booking->return_date)->format('Y-m-d');

    // Conflicting bookings
    $conflictingBookingVehicles = BookingTrans::where('id', '!=', $booking->id)
        ->whereIn('available', ['Booked', 'Out', 'Extend'])
        ->where(function ($q) use ($pickupDateOnly, $returnDateOnly) {
            $q->whereBetween('pickup_date', [$pickupDateOnly, $returnDateOnly])
              ->orWhereBetween('return_date', [$pickupDateOnly, $returnDateOnly])
              ->orWhere(function ($q2) use ($pickupDateOnly, $returnDateOnly) {
                  $q2->where('pickup_date', '<=', $pickupDateOnly)
                     ->where('return_date', '>=', $returnDateOnly);
              });
        })
        ->pluck('vehicle_id');

    // Conflicting extends
    $conflictingExtendedVehicles = Extend::whereNotNull('sale_id')
        ->where(function ($q) use ($pickupDateOnly, $returnDateOnly) {
            $q->whereBetween('extend_from_date', [$pickupDateOnly, $returnDateOnly])
              ->orWhereBetween('extend_to_date', [$pickupDateOnly, $returnDateOnly])
              ->orWhere(function ($q2) use ($pickupDateOnly, $returnDateOnly) {
                  $q2->where('extend_from_date', '<=', $pickupDateOnly)
                     ->where('extend_to_date', '>=', $returnDateOnly);
              });
        })
        ->pluck('vehicle_id');

    // Merge all conflicts
    $unavailableVehicleIds = collect()
        ->merge($conflictingBookingVehicles)
        ->merge($conflictingExtendedVehicles)
        ->unique()
        ->toArray();

    // Get available vehicles
    $availableVehicles = Vehicle::whereNotIn('id', $unavailableVehicleIds)
        ->whereIn('availability', ['Available', 'Booked', 'Out'])
        ->orderBy('reg_no')
        ->get();

    // Ensure current vehicle appears
    if ($currentVehicle && in_array($currentVehicle->id, $unavailableVehicleIds)) {
        $availableVehicles->prepend($currentVehicle);
    }

    // Role
    $occupation = auth()->user()->type ?? 'company';

    $checklist = Checklist::where('booking_trans_id', $booking->id)->first();

    // pickup interior
    $pickupInteriorImages = UploadData::where('booking_trans_id', $booking_id)
        ->where('position', 'pickup_interior')
        ->where('file_size', '!=', 0)
        ->orderByDesc('id')
        ->get(['file_name','no']);

    $returnInteriorImages = UploadData::where('booking_trans_id', $booking_id)
        ->where('position', 'return_interior')
        ->where('file_size', '!=', 0)
        ->orderByDesc('id')
        ->get(['file_name','no']);

    $pickupExteriorImages = UploadData::where('booking_trans_id', $booking_id)
        ->where('position', 'pickup_exterior')
        ->where('file_size', '!=', 0)
        ->orderByDesc('id')
        ->get(['file_name','no']);

    $returnExteriorImages = UploadData::where('booking_trans_id', $booking_id)
        ->where('position', 'return_exterior')
        ->where('file_size', '!=', 0)
        ->orderByDesc('id')
        ->get(['file_name','no']);

    $extends = Extend::where('extend_type', 'manual')
        ->where('booking_trans_id', $booking_id)
        ->orderBy('id')
        ->get();

    // ===== Added: variables needed by Blade modals =====

    // Count of manual extends for this booking (to block >= 9)
    $count = Extend::where('extend_type', 'manual')
        ->where('booking_trans_id', $booking_id)
        ->count();

    // Latest extend (fallback to booking return datetime)
    $latestExtend = Extend::where('booking_trans_id', $booking_id)
        ->where('extend_type', 'manual')
        ->orderByDesc('id')
        ->first();

    // Derive latest extend DATE and TIME
    if ($latestExtend) {
        $latest_extend_date = $latestExtend->extend_to_date
            ? \Carbon\Carbon::parse($latestExtend->extend_to_date)->format('Y-m-d')
            : null;

        // If you have a separate time column use it; else derive from datetime
        $latest_extend_time = $latestExtend->extend_to_time
            ?? (\Carbon\Carbon::parse($latestExtend->extend_to_date)->format('H:i'));
    } else {
        $rd = $booking->return_date ? \Carbon\Carbon::parse($booking->return_date) : null;
        $latest_extend_date = $rd ? $rd->format('Y-m-d') : null;
        $latest_extend_time = $rd ? $rd->format('H:i') : null;
    }

    // Build timestamps / diffs
    $timereturn  = $latest_extend_date
        ? strtotime(trim($latest_extend_date . ' ' . ($latest_extend_time ?? '00:00')))
        : null;

    $timecurrent = time();

    $currenthour = 0;
    $currentday  = 0;
    $totalhour   = 0;

    if ($timereturn) {
        $return_asal = \Carbon\Carbon::createFromTimestamp($timereturn);
        $return_skrg = \Carbon\Carbon::createFromTimestamp($timecurrent);
        $diff        = $return_asal->diff($return_skrg);
        $currenthour = (int) $diff->h;
        $currentday  = (int) $diff->d;
        $totalhour   = ($currentday * 24) + $currenthour;
    }

    // Keep DB value if present; else compute like legacy
    $excess = $booking->excess ?? null;
    if (empty($excess)) {
        if ($currentday < 1 && $timereturn && $timecurrent > $timereturn && $currenthour > 1 && $currenthour < 13) {
            $excess = 'true';
        } elseif (!$timereturn || $timecurrent < $timereturn || $totalhour < 2) {
            $excess = 'early';
        } else {
            $excess = 'extend';
        }
    }

    // Half-hour slots (08:00–23:00)
    $slots = [];
    for ($h = 8; $h <= 23; $h++) {
        foreach ([0, 30] as $m) {
            $slots[] = sprintf('%02d:%02d', $h, $m);
        }
    }

    // Current rounded half-hour in KL time (only if within 08:00–22:59:59)
    $now      = \Carbon\Carbon::now('Asia/Kuala_Lumpur')->second(0);
    $open     = $now->copy()->setTime(8, 0, 0);
    $close    = $now->copy()->setTime(23, 0, 0); // exclusive
    $rounded  = $now->copy()->minute($now->minute < 30 ? 0 : 30);
    $currenth = $now->between($open, $close->copy()->subSecond()) ? $rounded->format('H:i:s') : null;

    $pickup_receipt = UploadData::where('booking_trans_id', $booking->id)
    ->where('position', 'pickup_receipt')
    ->latest('created')
    ->first();


    // Return view
    return view('reservation.reservation_list_view', compact(
        'booking', 'company', 'actionLabel', 'dueWarnings',
        'fullname', 'customer_status', 'nric_no', 'phone_no', 'phone_no2', 'email', 'address', 'license_no','license_exp',
        'ref_name', 'ref_phoneno', 'ref_relationship',
        'allUploads', 'booking_receipt', 'occupation', 'cdwAmount',
        'currentVehicle', 'discount_coupon', 'availableVehicles',
        'cdwId','checklist','pickupInteriorImages','returnInteriorImages',
        'pickupExteriorImages','returnExteriorImages','extends', 'excess',
        // Added for modals
        'count', 'latest_extend_date', 'latest_extend_time',
        'timereturn', 'timecurrent', 'currenthour', 'currentday', 'totalhour',
        'slots', 'currenth', 'pickup_receipt'
    ));
}


public function ReservationForm($booking_id)
{
    $booking = BookingTrans::with(['customer', 'vehicle', 'pickupLocation', 'returnLocation'])->findOrFail($booking_id);

    // Get the related customer (if any)
    $customer = $booking->customer;

    // Get NRIC if customer exists
    $nric = $customer ? $customer->nric_no : null;

    // Pre-fill dates and times
    $pickupDateTime = \Carbon\Carbon::parse($booking->pickup_date);
    $returnDateTime = \Carbon\Carbon::parse($booking->return_date);

    // Pre-fill vehicle selection
    $search_vehicle = $booking->vehicle ? $booking->vehicle->class_id : null;

    // Other dropdowns (adjust if needed)
    $search_pickup_location = $booking->pickup_location ?? null;
    $search_return_location = $booking->return_location?? null;

    // Locations & Vehicles for select dropdowns
    $locations = LocationBooking::where('status', 'A')->get();
    $vehicles = ClassModel::where('status', 'A')->get();

    // 🔹 CDW ID from cdw_log
    $cdwLog = CDWLog::where('booking_trans_id', $booking->id)->first();
    $cdw_id = $cdwLog?->cdw_id;

    // Prepare booking summary to display on the form
    $bookingSummary = [
        'reference'        => $booking->id ?? '',
        'agreement_no'     => $booking->agreement_no ?? '',
        'pickup_time' => $booking->pickup_date ? \Carbon\Carbon::parse($booking->pickup_date)->format('Y-m-d H:i') : '',
        'return_time' => $booking->return_date ? \Carbon\Carbon::parse($booking->return_date)->format('Y-m-d H:i') : '',
        'pickup_location'  => optional($booking->pickupLocation)->description ?? '',
        'return_location'  => optional($booking->returnLocation)->description ?? '',
        'reg_no'           => $booking->vehicle?->reg_no ?? '',
        'car_name'         => trim(($booking->vehicle?->make ?? '') . ' ' . ($booking->vehicle?->model ?? '')),
        'coupon'           => strtoupper($booking->discount_coupon ?? 'N/A'),
        'agent'            => strtoupper($booking->agent_code ?? 'N/A'),
    ];

    return view('reservation.form', [
        'customer' => $customer,
        'nric' => $nric,
        'locations' => $locations,
        'vehicles' => $vehicles,
        'search_pickup_date' => $pickupDateTime->format('Y-m-d'),
        'search_pickup_time' => $pickupDateTime->format('H:i'),
        'search_return_date' => $returnDateTime->format('Y-m-d'),
        'search_return_time' => $returnDateTime->format('H:i'),
        'search_pickup_location' => $search_pickup_location,
        'search_return_location' => $search_return_location,
        'search_vehicle' => $search_vehicle,
        'agent_code' => $booking->agent_code ?? '',
        'search_driver' => $booking->driver_region ?? '',
        'klia' => $booking->klia ?? '',
        'opt' => $booking->delivery_term ?? '',
        'availableVehicles' => null,
        'couponModel' => null,
        'validCouponCode' => null,
        'isEditing' => true,
        'bookingId' => $booking->id,
        'bookingSummary' => $bookingSummary, // 👈 added this
        'cdw_id' => $cdw_id, // ✅ passed to Blade
    ]);
}

public function changeVehicle(Request $request, $booking_id)
{
    $validated = $request->validate([
        'vehicle_id' => 'required|exists:vehicle,id',
        'search_pickup_date' => 'required|date',
        'search_pickup_time' => 'required',
        'search_return_date' => 'required|date',
        'search_return_time' => 'required',
        'search_pickup_location' => 'nullable|integer',
        'search_return_location' => 'nullable|integer',
        'coupon' => 'nullable|string|max:50',
        'cdw_id' => 'nullable|integer|exists:cdw,id',
    ]);

    $booking = BookingTrans::with(['sales'])->findOrFail($booking_id);

    $pickupCarbon = Carbon::parse($validated['search_pickup_date'] . ' ' . $validated['search_pickup_time']);
    $returnCarbon = Carbon::parse($validated['search_return_date'] . ' ' . $validated['search_return_time']);

    // === ORIGINAL DURATION (WITHOUT COUPON) ===
    $origDay  = $pickupCarbon->diffInDays($returnCarbon);
    $origHour = $pickupCarbon->copy()->addDays($origDay)->diffInHours($returnCarbon);

    // === COUPON LOGIC ===
    $couponCode = $validated['coupon'] ?? null;
    $discount = 0;
    $coupon = null;

    if ($couponCode) {
        $coupon = Discount::where('code', strtoupper($couponCode))
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })->first();

        if ($coupon && $coupon->value_in === 'A') {
            $discount = $coupon->rate;
        }
    }

    // === Vehicle and Pricing Details ===
    $vehicle    = Vehicle::with('class.priceClass')->findOrFail($validated['vehicle_id']);
    $priceClass = optional(optional($vehicle->class)->priceClass);
    $bookingfee = $priceClass->booking ?? 0;

    // === ORIGINAL RENTAL TOTAL (USED FOR SUBTOTAL, CDW, and EST_TOTAL) ===
    $monthly_subtotal_original = 0;
    $day_orig  = $origDay;
    $hour_orig = $origHour;

    if ($day_orig > 30) {
        $monthcount_orig           = intdiv($day_orig, 30);
        $monthly_subtotal_original = $monthcount_orig * ($priceClass->monthly ?? 0);
        $day_orig                 -= $monthcount_orig * 30;
    }

    $originalRentalTotal = $this->calculateRentalSubtotal($vehicle, $day_orig, $hour_orig, $monthly_subtotal_original);

    // === CDW AMOUNT BASED ON ORIGINAL RENTAL TOTAL ===
    $cdwLog = CDWLog::where('booking_trans_id', $booking->id)->first();
    $cdwId  = $validated['cdw_id'] ?? ($cdwLog->cdw_id ?? null);

    $cdwAmount = 0;
    if ($cdwId) {
        $cdw = CDW::with('rate')->find($cdwId);
        if ($cdw && $cdw->rate && $cdw->rate->rate) {
            $cdwAmount = round(($cdw->rate->rate / 100) * $originalRentalTotal, 2);
        }
    }

    // === APPLY PERCENTAGE DISCOUNT (IF ANY) ON ORIGINAL RENTAL TOTAL ===
    if (isset($coupon) && $coupon->value_in === 'P') {
        $discount = round(($originalRentalTotal * $coupon->rate) / 100, 2);
    }

    // === ESTIMATED TOTAL CALCULATION ===
    $estTotal = round($originalRentalTotal + $cdwAmount - $discount, 2);
    $estTotal = max($estTotal, 0);

    // === BOOKING STATUS UPDATE ===
    if ($booking->vehicle_id && $booking->vehicle_id !== $vehicle->id) {
        BookingTrans::where('vehicle_id', $booking->vehicle_id)
            ->where('id', '!=', $booking->id)
            ->update(['available' => 'Available']);
    }

    // Final Return Date includes free days/hours for record only (no price impact)
    $freeDays = ($coupon && $coupon->value_in == 'D') ? (int)$coupon->rate : 0;
    $freeHours = ($coupon && $coupon->value_in == 'H') ? (int)$coupon->rate : 0;
    $finalReturn = $returnCarbon->copy()->addDays($freeDays)->addHours($freeHours);
    $finalDay = $pickupCarbon->diffInDays($finalReturn); // ← ADD THIS LINE

    $booking->available          = 'Booked';
    $booking->vehicle_id         = $vehicle->id;
    $booking->pickup_date        = $pickupCarbon;
    $booking->return_date        = $finalReturn; // Only this date reflects coupon days/hours
    $booking->pickup_location    = $validated['search_pickup_location'];
    $booking->return_location    = $validated['search_return_location'];
    $booking->discount_coupon    = $coupon->code ?? null;
    $booking->discount_amount    = $discount;
    $booking->day                = $finalDay;
    $booking->sub_total          = $originalRentalTotal; // Original subtotal
    $booking->est_total          = $estTotal;            // Final total without free coupon impact
    $booking->refund_dep         = $bookingfee;
    $booking->save();

    // === UPDATE MAIN BOOKING SALE ONLY ===
    $sale = Sale::where('booking_trans_id', $booking->id)
                ->where('type', 'Booking')
                ->first();

    if ($sale) {
        $sale->vehicle_id   = $vehicle->id;
        $sale->total_day    = $finalDay;             // After coupon
        $sale->total_sale   = $bookingfee;          // Recalculated booking fee
        $sale->pickup_date  = $pickupCarbon;
        $sale->return_date  = $finalReturn;          // Includes free day/hour
        $sale->save();
    }


    // === UPDATE EXISTING CDW_LOG ONLY ===
    if ($cdwLog) {
        $status = ($booking->payment_status === 'FullRental') ? 'Paid' : 'Collect';

        $cdwLog->update([
            'cdw_id'   => $cdwId,
            'amount'   => $cdwAmount,
            'status'   => $status,
            'mid'      => auth()->id(),
            'modified' => now(),
        ]);
    }

    return redirect()->route('reservation.view', ['booking_id' => $booking->id])
        ->with('success', 'Booking updated successfully. Agreement No: ' . $booking->agreement_no);
}



    public function editCust(Request $request, BookingTrans $booking)
    {
        $booking->load('customer');
        $customer = $booking->customer;

        // grab related sales as you already do...
        $pickupSale = Sale::where('booking_trans_id', $booking->id)
            ->where('title', 'Pickup')->first();
        $bookingDepositSale = Sale::where('booking_trans_id', $booking->id)
            ->where('title', 'Booking Deposit in')->first();

        $type = $request->query('type', 'exist');

        // pull filenames from upload_data by category (adjust names if yours differ)
        $front = $util = $work = $selfie = $licenseFront = $licenseBack = null;

        if ($customer) {
            $filenames = UploadData::query()
                ->where('position', 'customer')
                ->where('customer_id', $customer->id)
                ->pluck('file_name'); // Collection<string>

            foreach ($filenames as $name) {
                $n = strtolower($name);
                if (str_contains($n, 'identity_photo_front')) {
                    $front = $name;
                } elseif (str_contains($n, 'utility_photo')) {
                    $util = $name;
                } elseif (str_contains($n, 'working_photo')) {
                    $work = $name;
                } elseif (str_contains($n, 'selfie_nric')) {
                    $selfie = $name;
                } elseif (str_contains($n, 'license_front')) {
                    $licenseFront = $name;
                } elseif (str_contains($n, 'license_back')) {
                    $licenseBack = $name;
                }
            }
        }


        $hasIdPhotos = !empty($front) || !empty($selfie) || !empty($licenseFront) || !empty($licenseBack) || !empty($util) || !empty($work);

        return view('reservation.editCust', [
            'type'               => $type,
            'booking'            => $booking,
            'customer'           => $customer,
            'pickupSale'         => $pickupSale,
            'bookingDepositSale' => $bookingDepositSale,
            'nric_blacklisted'   => $request->query('nric_blacklisted'),

            // send the 4 filenames to Blade
            'idPhotoFront'       => $front,
            'selfieNric'         => $selfie,
            'licenseFront'       => $licenseFront,
            'licenseBack'        => $licenseBack,
            'utilityPhoto'       => $util,
            'workingPhoto'       => $work,

            'hasIdPhotos'        => $hasIdPhotos
        ]);
    }


    public function updateExistingCust(Request $request, BookingTrans $booking)
    {
        // Validate only what this form actually edits
        $validated = $request->validate([
            'customer_id' => ['required','integer','exists:customer,id'],
            'nric_no'            => ['nullable','string'], // it’s disabled normally, enabled only if blacklisted banner flow
            'nric_type'          => ['required','string'],
            'title'              => ['required','string'],
            'firstname'          => ['required','string'],
            'lastname'           => ['required','string'],
            'gender'             => ['required','in:Male,Female'],
            'race'               => ['nullable','string'],
            'dob'                => ['nullable','date'],
            'phone_no'           => ['required','string'],
            'phone_no2'          => ['nullable','string'],
            'email'              => ['nullable','email'],
            'license_no'         => ['nullable','string'],
            'license_exp'        => ['nullable','date'],
            'address'            => ['nullable','string'],
            'postcode'           => ['nullable','string'],
            'city'               => ['nullable','string'],
            'country'            => ['nullable','string'],

            // Reference
            'ref_name'           => ['nullable','string'],
            'ref_phoneno'        => ['nullable','string'],
            'ref_relationship'   => ['nullable','string'],
            'ref_address'        => ['nullable','string'],

            // Additional driver
            'drv_name'           => ['nullable','string'],
            'drv_nric'           => ['nullable','string'],
            'drv_address'        => ['nullable','string'],
            'drv_phoneno'        => ['nullable','string'],
            'drv_license_no'     => ['nullable','string'],
            'drv_license_exp'    => ['nullable','date'],

            // Payment
            'payment_amount'     => ['nullable','numeric'],
            'payment_status'     => ['nullable','string'],
            'payment_type'       => ['nullable','string'],
            'deposit'            => ['nullable','numeric'],
            'refund_dep_payment' => ['nullable','string'],

            // Survey
            'survey_type'        => ['nullable','string'],
            'survey_details'     => ['nullable','string'],

            // Files (array with 0..3 indexes like old page)
            'identity_photo'     => ['array'],
            'identity_photo.*'   => ['nullable','file','mimes:jpg,jpeg,png,gif','max:8192'],
        ]);

        // 1) Blacklist check (if NRIC editable in your flow)
        if ($request->filled('nric_no')) {
            $isBlacklisted = Customer::where('status', 'B')
                ->where('nric_no', $request->input('nric_no'))
                ->exists();

            if ($isBlacklisted) {
                return redirect()
                    ->route('customers.edit', ['booking' => $booking->id, 'type' => 'exist', 'nric_blacklisted' => $request->input('nric_no')])
                    ->with('error', 'Rental could not be continued as the NRIC No. entered has been blacklisted.');
            }
        }

        DB::transaction(function () use ($request, $booking, $validated) {
            // 2) Load current booking/customer
            $booking->load('customer');
            $customer = Customer::findOrFail($validated['customer_id']); // this is the posted one

            // 3) If posted customer_id differs from booking->customer_id, update booking’s customer_id
            if ($booking->customer_id !== (int)$validated['customer_id']) {
                $booking->customer_id = (int)$validated['customer_id'];
                $booking->save();
            }

            // 4) Handle identity photos (index 0..3 map)
            // 0: identity_photo_front.jpg
            // 1: identity_photo_back.jpg
            // 2: utility_photo.jpg
            // 3: working_photo.jpg
            if ($request->hasFile('identity_photo')) {
                $this->saveIdentityPhotos($customer, $request->file('identity_photo'));
            }

            // 5) Update customer details
            $customer->fill([
                'title'            => $validated['title'],
                'firstname'        => $validated['firstname'],
                'lastname'         => $validated['lastname'],
                'dob'              => $validated['dob'] ?? null,
                'gender'           => $validated['gender'] ?? null,
                'race'             => $validated['race'] ?? null,
                'license_no'       => $validated['license_no'] ?? null,
                'license_exp'      => $validated['license_exp'] ?? null,
                'age'              => isset($validated['dob']) ? Carbon::parse($validated['dob'])->age : $customer->age,
                'phone_no'         => $validated['phone_no'],
                'phone_no2'        => $validated['phone_no2'] ?? null,
                'email'            => $validated['email'] ?? null,
                'address'          => $validated['address'] ?? null,
                'postcode'         => $validated['postcode'] ?? null,
                'city'             => $validated['city'] ?? null,
                'country'          => $validated['country'] ?? null,
                'status'           => 'A',
                'ref_name'         => $validated['ref_name'] ?? null,
                'ref_phoneno'      => $validated['ref_phoneno'] ?? null,
                'ref_relationship' => $validated['ref_relationship'] ?? null,
                'ref_address'      => $validated['ref_address'] ?? null,
                'drv_name'         => $validated['drv_name'] ?? null,
                'drv_nric'         => $validated['drv_nric'] ?? null,
                'drv_address'      => $validated['drv_address'] ?? null,
                'drv_phoneno'      => $validated['drv_phoneno'] ?? null,
                'drv_license_no'   => $validated['drv_license_no'] ?? null,
                'drv_license_exp'  => $validated['drv_license_exp'] ?? null,
                'survey_type'      => $validated['survey_type'] ?? null,
                'survey_details'   => $validated['survey_details'] ?? null,
                'mid'              => auth()->id(), // maps $_SESSION['cid']
                'mdate'            => now(),
            ]);
            $customer->save();

            // 6) Update booking payment fields
            $booking->balance            = $validated['payment_amount'] ?? $booking->balance;
            $booking->refund_dep_payment = $validated['refund_dep_payment'] ?? $booking->refund_dep_payment;
            $booking->payment_status     = $validated['payment_status'] ?? $booking->payment_status;
            $booking->payment_type       = $validated['payment_type'] ?? $booking->payment_type;
            $booking->refund_dep         = $validated['deposit'] ?? $booking->refund_dep;
            $booking->save();

            // 7) Update sale row for Pickup (if exists)
            Sale::where('booking_trans_id', $booking->id)
                ->where('title', 'Pickup')
                ->where('type', 'Sale')
                ->update([
                    'payment_type' => $validated['payment_type'] ?? null,
                    'mid'          => auth()->id(),
                    'modified'     => now(),
                ]);
        });

        return redirect()
        ->route('reservation.view', ['booking_id' => $booking->id])
        ->with('success', 'Customer has been edited.');

    }

    /**
     * Save up to 4 identity photos with Intervention (quality ~40) and static filenames.
     * public/storage/customer/{id}-identity_photo_front.jpg etc.
     */
    private function saveIdentityPhotos(Customer $customer, array $files): void
    {
        $map = [
            0 => ['field' => 'identity_photo_front', 'name' => "{$customer->id}-identity_photo_front.jpg"],
            1 => ['field' => 'identity_photo_back',  'name' => "{$customer->id}-identity_photo_back.jpg"],
            2 => ['field' => 'utility_photo',        'name' => "{$customer->id}-utility_photo.jpg"],
            3 => ['field' => 'working_photo',        'name' => "{$customer->id}-working_photo.jpg"],
        ];

        foreach ($files as $idx => $file) {
            if (!$file) continue;
            if (!isset($map[$idx])) continue;

            $targetName = $map[$idx]['name'];
            $diskPath   = "customer/{$targetName}";
            $absPath    = storage_path("app/public/{$diskPath}");

            // Ensure directory exists
            if (!is_dir(dirname($absPath))) {
                @mkdir(dirname($absPath), 0775, true);
            }

            // Re-encode to jpg @ quality 40 (approx your compressImage(..., 40))
            Image::make($file->getRealPath())
                ->encode('jpg', 40)
                ->save($absPath);

            // Update DB field with filename
            $customer->{$map[$idx]['field']} = $targetName;
        }

        $customer->save();
    }

    // in CustomerController

public function updateChangeCust(Request $request, BookingTrans $booking)
{
    $data = $request->validate([
        'nric_no'            => ['required','string'],
        'nric_type'          => ['required','string'],
        'title'              => ['required','string'],
        'firstname'          => ['required','string'],
        'lastname'           => ['required','string'],
        'gender'             => ['required','in:Male,Female'],
        'race'               => ['nullable','string'],
        'dob'                => ['nullable','date'],
        'phone_no'           => ['required','string'],
        'phone_no2'          => ['nullable','string'],
        'email'              => ['nullable','email'],
        'license_no'         => ['required','string'],
        'license_exp'        => ['required','date'],
        'address'            => ['required','string'],
        'postcode'           => ['required','string'],
        'city'               => ['required','string'],
        'country'            => ['required','string'],

        // reference
        'ref_name'           => ['required','string'],
        'ref_phoneno'        => ['required','string'],
        'ref_relationship'   => ['required','string'],
        'ref_address'        => ['required','string'],

        // payment
        'payment_amount'     => ['required','numeric'],
        'payment_status'     => ['required','string'],
        'payment_type'       => ['nullable','string'],
        'deposit'            => ['required','numeric'],
        'refund_dep_payment' => ['required','string'],

        // optional survey
        'survey_type'        => ['nullable','string'],
        'survey_details'     => ['nullable','string'],
    ]);

    // we’ll capture the redirect after the transaction
    $redirect = DB::transaction(function () use ($data, $booking) {

        // 1) check if customer exists by NRIC (table is `customer` via the model)
        /** @var Customer|null $existing */
        $existing = Customer::where('nric_no', $data['nric_no'])->first();

        if ($existing) {
            // 1a) blacklist gate
            if ($existing->status === 'B') {
                // throw to rollback & bubble up a redirect message after tx
                throw new \RuntimeException(
                    'BLACKLIST::' . ($existing->reason_blacklist ?: 'Customer is blacklisted and cannot be used for rental.')
                );
            }

            // 1b) update existing (don’t change nric_no here)
            $existing->fill([
                'title'            => $data['title'],
                'firstname'        => $data['firstname'],
                'lastname'         => $data['lastname'],
                'dob'              => $data['dob'] ?? null,
                'age'              => !empty($data['dob']) ? Carbon::parse($data['dob'])->age : $existing->age,
                'gender'           => $data['gender'],
                'race'             => $data['race'] ?? null,
                'nric_type'        => $data['nric_type'],
                'license_no'       => $data['license_no'],
                'license_exp'      => $data['license_exp'],
                'phone_no'         => $data['phone_no'],
                'phone_no2'        => $data['phone_no2'] ?? null,
                'email'            => $data['email'] ?? null,
                'address'          => $data['address'],
                'postcode'         => $data['postcode'],
                'city'             => $data['city'],
                'country'          => $data['country'],
                'status'           => 'A',
                'ref_name'         => $data['ref_name'],
                'ref_phoneno'      => $data['ref_phoneno'],
                'ref_relationship' => $data['ref_relationship'],
                'ref_address'      => $data['ref_address'],
                'survey_type'      => $data['survey_type'] ?? $existing->survey_type,
                'survey_details'   => $data['survey_details'] ?? $existing->survey_details,
                'mid'              => auth()->id(),
                'mdate'            => now(),
            ])->save();

            $customer = $existing;
        } else {
            // 2) create brand-new customer (table is `customer`)
            $customer = Customer::create([
                'title'            => $data['title'],
                'firstname'        => $data['firstname'],
                'lastname'         => $data['lastname'],
                'dob'              => $data['dob'] ?? null,
                'age'              => !empty($data['dob']) ? Carbon::parse($data['dob'])->age : null,
                'gender'           => $data['gender'],
                'race'             => $data['race'] ?? null,
                'nric_no'          => $data['nric_no'],
                'nric_type'        => $data['nric_type'],
                'license_no'       => $data['license_no'],
                'license_exp'      => $data['license_exp'],
                'phone_no'         => $data['phone_no'],
                'phone_no2'        => $data['phone_no2'] ?? null,
                'email'            => $data['email'] ?? null,
                'address'          => $data['address'],
                'postcode'         => $data['postcode'],
                'city'             => $data['city'],
                'country'          => $data['country'],
                'status'           => 'A',
                'ref_name'         => $data['ref_name'],
                'ref_phoneno'      => $data['ref_phoneno'],
                'ref_relationship' => $data['ref_relationship'],
                'ref_address'      => $data['ref_address'],
                'survey_type'      => $data['survey_type'] ?? null,
                'survey_details'   => $data['survey_details'] ?? null,
                'cid'              => auth()->id(),
                'cdate'            => now(),
            ]);
        }

        // 3) attach customer to booking + update financials (booking_trans table via model)
        $booking->fill([
            'customer_id'        => $customer->id,
            'balance'            => $data['payment_amount'],
            'refund_dep_payment' => $data['refund_dep_payment'],
            'payment_status'     => $data['payment_status'],
            'payment_type'       => $data['payment_type'] ?? null,
            'refund_dep'         => $data['deposit'],
        ])->save();

        // 4) touch Pickup sale row’s payment_type (sales table via model)
        Sale::where('booking_trans_id', $booking->id)
            ->where('title', 'Pickup')
            ->where('type', 'Sale')
            ->update([
                'payment_type' => $data['payment_type'] ?? null,
                'mid'          => auth()->id(),
                'modified'     => now(),
            ]);

        // return success signal
        return ['ok' => true];
    });

    // commit succeeded
    if (!empty($redirect['ok'])) {
        return redirect()
            ->route('reservation.view', ['booking_id' => $booking->id])
            ->with('success', 'Customer has been changed.');
    }

    // if we ever get here due to thrown BLACKLIST:: error, catch and redirect nicely
    // (Laravel will have already rolled back)
    return redirect()
        ->route('customers.edit', ['booking' => $booking->id, 'type' => 'change'])
        ->with('error', 'Unable to change customer.');
}









    public function Grid(Request $request)
    {
        if (Auth::user()->isAbleTo('reservation manage'))
        {
            $status = reservation::$statues;
            $customer  = User::where('workspace_id', '=', getActiveWorkSpace())->where('type','Client')->get()->pluck('name', 'id');
            if(Auth::user()->type != 'company')
            {
                $query = reservation::join('users', 'reservations.customer_id', '=', 'users.id')
                        ->where('users.id',Auth::user()->id)->select('reservations.*')
                        ->where('reservations.workspace',getActiveWorkSpace());
            }
            else
            {
                $query = reservation::where('workspace',getActiveWorkSpace());
            }
            if (!empty($request->customer))
            {
                $query->where('customer_id', '=', $request->customer);

            }
            if (!empty($request->issue_date))
            {
                $date_range = explode('to', $request->issue_date);
                if(count($date_range) == 2)
                {
                    $query->whereBetween('issue_date',$date_range);
                }
                else
                {
                    $query->where('issue_date',$date_range[0]);
                }
            }

            if (!empty($request->status)) {

                $query->where('status', $request->status);
            }
            $reservations = $query;
            $reservations = $reservations->paginate(11);

            return view('reservation.grid', compact('reservations', 'customer', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create($customerId)
    {
        if(module_is_active('ProductService'))
        {
            if (Auth::user()->isAbleTo('reservation create'))
            {

                $reservation_number = reservation::reservationNumberFormat($this->reservationNumber());

                $customers   = User::where('workspace_id', '=', getActiveWorkSpace())->where('type','Client')->get()->pluck('name', 'id');
                $category=[];
                $product_services=[];
                $projects=[];
                $taxs=[];
                $incomeChartAccounts = [];
                if(module_is_active('Account'))
                {
                    if ($customerId > 0) {
                        $temp_cm = \Workdo\Account\Entities\Customer::where('id',$customerId)->first();
                        if($temp_cm)
                        {
                            $customerId = $temp_cm->user_id;
                        }
                        else
                        {
                            return redirect()->back()->with('error', __('Something went wrong please try again!'));
                        }
                    }
                    $category = \Workdo\ProductService\Entities\Category::where('created_by', '=', creatorId())->where('workspace_id', getActiveWorkSpace())->where('type', 1)->get()->pluck
                    ('name', 'id');
                    $product_services = \Workdo\ProductService\Entities\ProductService::where('workspace_id', getActiveWorkSpace())->get()->pluck('name', 'id');

                    $incomeTypes = ChartOfAccountType::where('created_by', '=', creatorId())
                    ->where('workspace', getActiveWorkSpace())
                    ->whereIn('name', ['Assets', 'Liabilities', 'Income'])
                    ->get();

                    foreach ($incomeTypes as $type) {
                        $accountTypes = ChartOfAccountSubType::where('type', $type->id)
                            ->where('created_by', '=', creatorId())
                            ->whereNotIn('name', ['Accounts Receivable' , 'Accounts Payable'])
                            ->get();

                        $temp = [];

                        foreach ($accountTypes as $accountType) {
                            $chartOfAccounts = ChartOfAccount::where('sub_type', $accountType->id)->where('parent', '=', 0)
                                ->where('created_by', '=', creatorId())
                                ->get();

                            $incomeSubAccounts = ChartOfAccount::where('sub_type', $accountType->id)->where('parent', '!=', 0)
                            ->where('created_by', '=', creatorId())
                            ->get();

                            $tempData = [
                                'account_name' => $accountType->name,
                                'chart_of_accounts' => [],
                                'subAccounts' => [],
                            ];
                            foreach ($chartOfAccounts as $chartOfAccount) {
                                $tempData['chart_of_accounts'][] = [
                                    'id' => $chartOfAccount->id,
                                    'account_number' => $chartOfAccount->account_number,
                                    'account_name' => $chartOfAccount->name,
                                ];
                            }

                            foreach ($incomeSubAccounts as $chartOfAccount) {
                                $tempData['subAccounts'][] = [
                                    'id' => $chartOfAccount->id,
                                    'account_number' => $chartOfAccount->account_number,
                                    'account_name' => $chartOfAccount->name,
                                    'parent'=>$chartOfAccount->parent
                                ];
                            }
                            $temp[$accountType->id] = $tempData;
                        }

                        $incomeChartAccounts[$type->name] = $temp;
                    }
                }
                if(module_is_active('Taskly'))
                {
                    if(module_is_active('ProductService'))
                    {
                        $taxs = \Workdo\ProductService\Entities\Tax::where('workspace_id', getActiveWorkSpace())->get()->pluck('name', 'id');
                    }
                    $projects = \Workdo\Taskly\Entities\Project::select('projects.*')->join('user_projects', 'projects.id', '=', 'user_projects.project_id')->where('user_projects.user_id', '=', Auth::user()->id)->where('workspace', getActiveWorkSpace())->projectonly()->get()->pluck('name', 'id');
                }

                $work_order = [];

                if(module_is_active('CMMS'))
                {
                    $work_order = Workorder::with('getLocation')->where(['company_id' => creatorId(), 'workspace' => getActiveWorkSpace(),  'status' => 1])->get()->pluck('wo_name','id');
                }
                if(module_is_active('CustomField')){
                    $customFields =  \Workdo\CustomField\Entities\CustomField::where('workspace_id',getActiveWorkSpace())->where('module', '=', 'Base')->where('sub_module','reservation')->get();
                }else{
                    $customFields = null;
                }
                return view('reservation.create', compact('customers', 'reservation_number', 'product_services', 'category', 'customerId','projects','taxs','customFields','work_order','incomeChartAccounts'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }
        else
        {
            return redirect()->route('reservation.index')->with('error', __('Please Enable Product & Service Module'));
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if (\Auth::user()->isAbleTo('reservation create'))
        {
            if($request->reservation_type == "product")
            {

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'customer_id' => 'required',
                        'issue_date' => 'required',
                        'category_id' => 'required',
                        'items' => 'required',

                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                foreach ($request->items as $item) {
                    if (empty($item['item']) && $item['item'] == 0 ) {
                        return redirect()->back()->with('error', __('Please select an item'));
                    }
                }
                $status = reservation::$statues;
                $reservation                 = new reservation();
                $reservation->reservation_id    = $this->reservationNumber();
                $reservation->customer_id    = $request->customer_id;
                $reservation->account_type   = $request->account_type;
                $reservation->status         = 0;
                $reservation->issue_date     = $request->issue_date;
                $reservation->category_id    = $request->category_id;
                $reservation->reservation_template    = $request->reservation_template;
                $reservation->workspace       = getActiveWorkSpace();
                $reservation->created_by      = creatorId();
                $reservation->save();
                Invoice::starting_number($reservation->reservation_id + 1, 'reservation');

                if(module_is_active('CustomField'))
                    {
                        \Workdo\CustomField\Entities\CustomField::saveData($reservation, $request->customField);
                    }
                $products = $request->items;
                for ($i = 0; $i < count($products); $i++)
                {
                    $reservationProduct                = new reservationProduct();
                    $reservationProduct->reservation_id   = $reservation->id;
                    $reservationProduct->product_type  = $products[$i]['product_type'];
                    $reservationProduct->product_id    = $products[$i]['item'];
                    $reservationProduct->quantity      = $products[$i]['quantity'];
                    $reservationProduct->tax           = $products[$i]['tax'];
                    $reservationProduct->discount      = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                    $reservationProduct->price         = $products[$i]['price'];
                    $reservationProduct->description   = str_replace( array( '\'', '"', '`','{',"\n"), ' ', $products[$i]['description']);
                    $reservationProduct->save();
                }
                  // first parameter request second parameter reservation
                event(new Createreservation($request, $reservation));
                return redirect()->route('reservation.index')->with('success', __('The reservation has been created successfully'));
            }
            else if($request->reservation_type == "project")
            {
                $validator = \Validator::make(
                    $request->all(), [

                                    'customer_id' => 'required',
                                    'issue_date' => 'required',
                                    'project' => 'required',
                                    'tax_project' => 'required',
                                    'items' => 'required',

                                ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $reservation                 = new reservation();
                if(module_is_active('Account'))
                {
                    $customer = \Workdo\Account\Entities\Customer::where('user_id', '=', $request->customer_id)->first();
                    $reservation->customer_id    = !empty($customer) ?  $customer->id : null;
                }

                $status = reservation::$statues;
                $reservation->reservation_id       = $this->reservationNumber();
                $reservation->customer_id       = $request->customer_id;
                $reservation->status            = 0;
                $reservation->account_id        = $request->sale_chartaccount_id;
                $reservation->account_type      = $request->account_type;
                $reservation->reservation_module   = 'taskly';
                $reservation->issue_date        = $request->issue_date;
                $reservation->category_id       = $request->project;
                $reservation->reservation_template = $request->reservation_template;
                $reservation->workspace         = getActiveWorkSpace();
                $reservation->created_by        = creatorId();

                $reservation->save();

                $products = $request->items;

                Invoice::starting_number( $reservation->reservation_id + 1, 'reservation');

                if(module_is_active('CustomField'))
                {
                    \Workdo\CustomField\Entities\CustomField::saveData($reservation, $request->customField);
                }
                $project_tax = implode(',',$request->tax_project);

                for($i = 0; $i < count($products); $i++)
                {
                    $reservationProduct               = new reservationProduct();
                    $reservationProduct->reservation_id  = $reservation->id;
                    $reservationProduct->product_id   = $products[$i]['item'];
                    $reservationProduct->quantity     = 1;
                    $reservationProduct->tax          = $project_tax;
                    $reservationProduct->discount     = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                    $reservationProduct->price        = $products[$i]['price'];
                    $reservationProduct->description  = $products[$i]['description'];
                    $reservationProduct->save();
                }
                // first parameter request second parameter reservation
                event(new Createreservation($request, $reservation));

                if(!empty($request->redirect_route)){
                    return redirect()->to($request->redirect_route)->with('success', __('The reservation has been created successfully'));
                }else{
                return redirect()->route('reservation.index', $reservation->id)->with('success', __('The reservation has been created successfully'));
                }            }
            else if($request->reservation_type == "parts")
            {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'customer_id' => 'required',
                        'issue_date' => 'required',
                        'work_order' => 'required',
                        'items' => 'required',

                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }
                $status = reservation::$statues;
                $reservation                 = new reservation();
                $reservation->reservation_id    = $this->reservationNumber();
                $reservation->customer_id    = $request->customer_id;
                $reservation->account_type   = $request->account_type;
                $reservation->reservation_module = 'cmms';
                $reservation->status         = 0;
                $reservation->issue_date     = $request->issue_date;
                $reservation->category_id    = $request->work_order;
                $reservation->reservation_template    = $request->reservation_template;
                $reservation->workspace       = getActiveWorkSpace();
                $reservation->created_by      = creatorId();
                $reservation->save();
                Invoice::starting_number($reservation->reservation_id + 1, 'reservation');

                if(module_is_active('CustomField'))
                    {
                        \Workdo\CustomField\Entities\CustomField::saveData($reservation, $request->customField);
                    }
                $products = $request->items;
                for ($i = 0; $i < count($products); $i++)
                {
                    $reservationProduct                = new reservationProduct();
                    $reservationProduct->reservation_id   = $reservation->id;
                    $reservationProduct->product_type  = $products[$i]['product_type'];
                    $reservationProduct->product_id    = $products[$i]['item'];
                    $reservationProduct->quantity      = $products[$i]['quantity'];
                    $reservationProduct->tax           = $products[$i]['tax'];
                    $reservationProduct->discount      = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                    $reservationProduct->price         = $products[$i]['price'];
                    $reservationProduct->description   = str_replace( array( '\'', '"', '`','{',"\n"), ' ', $products[$i]['description']);
                    $reservationProduct->save();
                }
                  // first parameter request second parameter reservation
                event(new Createreservation($request, $reservation));
                return redirect()->route('reservation.index')->with('success', __('The reservation has been created successfully'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($e_id)
    {
        if (Auth::user()->isAbleTo('reservation show'))
        {
            try {
                $id       = Crypt::decrypt($e_id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('reservation Not Found.'));
            }
            $reservation = reservation::find($id);
            if(!empty($reservation) && $reservation->workspace == getActiveWorkSpace())
            {
                $company_settings = getCompanyAllSetting();
                $user = $reservation->customer;
                $reservation_attachment = reservationAttechment::where('reservation_id', $reservation->id)->get();
                $customer = [];
                if(module_is_active('Account') && !empty($user->id))
                {
                    $customer    = \Workdo\Account\Entities\Customer::where('user_id',$user->id)->where('workspace',getActiveWorkSpace())->first();
                }
                if (!empty($customer)) {
                    $customer->model = 'Customer';
                } else {
                    $customer = $reservation->customer;
                    if (!empty($customer)) {
                        $customer->model = 'User';
                    }
                }
                $iteams   = $reservation->items;
                $status   = reservation::$statues;
                if(module_is_active('CustomField')){
                    $reservation->customField = \Workdo\CustomField\Entities\CustomField::getData($reservation, 'Base','reservation');
                    $customFields      = \Workdo\CustomField\Entities\CustomField::where('workspace_id', '=', getActiveWorkSpace())->where('module', '=', 'Base')->where('sub_module','reservation')->get();
                }else{
                    $customFields = null;
                }

                return view('reservation.view', compact('reservation', 'customer', 'iteams', 'status','customFields','reservation_attachment','company_settings'));
            } else {
                return redirect()->route('reservation.index')->with('error', __('reservation Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($e_id)
    {
        if (Auth::user()->isAbleTo('reservation edit'))
        {
            try {
                $id       = Crypt::decrypt($e_id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('reservation Not Found.'));
            }
            $reservation        = reservation::find($id);
            if($reservation->workspace == getActiveWorkSpace())
            {
                $reservation_number = reservation::reservationNumberFormat($reservation->reservation_id);
                $customers       = User::where('workspace_id', '=', getActiveWorkSpace())->where('type','Client')->get()->pluck('name', 'id');
                if(module_is_active('ProductService'))
                {
                    $category = \Workdo\ProductService\Entities\Category::where('created_by', '=', creatorId())->where('workspace_id', getActiveWorkSpace())->where('type', 1)->get()->pluck('name', 'id');

                }
                else
                {
                    $category=[];
                    $product_services=[];
                }

                $incomeChartAccounts = [];

                $incomeTypes = ChartOfAccountType::where('created_by', '=', creatorId())
                ->where('workspace', getActiveWorkSpace())
                ->whereIn('name', ['Assets', 'Liabilities', 'Income'])
                ->get();

                foreach ($incomeTypes as $type) {
                    $accountTypes = ChartOfAccountSubType::where('type', $type->id)
                        ->where('created_by', '=', creatorId())
                        ->whereNotIn('name', ['Accounts Receivable' , 'Accounts Payable'])
                        ->get();

                    $temp = [];

                    foreach ($accountTypes as $accountType) {
                        $chartOfAccounts = ChartOfAccount::where('sub_type', $accountType->id)->where('parent', '=', 0)
                            ->where('created_by', '=', creatorId())
                            ->get();

                        $incomeSubAccounts = ChartOfAccount::where('sub_type', $accountType->id)->where('parent', '!=', 0)
                        ->where('created_by', '=', creatorId())
                        ->get();

                        $tempData = [
                            'account_name' => $accountType->name,
                            'chart_of_accounts' => [],
                            'subAccounts' => [],
                        ];
                        foreach ($chartOfAccounts as $chartOfAccount) {
                            $tempData['chart_of_accounts'][] = [
                                'id' => $chartOfAccount->id,
                                'account_number' => $chartOfAccount->account_number,
                                'account_name' => $chartOfAccount->name,
                            ];
                        }

                        foreach ($incomeSubAccounts as $chartOfAccount) {
                            $tempData['subAccounts'][] = [
                                'id' => $chartOfAccount->id,
                                'account_number' => $chartOfAccount->account_number,
                                'account_name' => $chartOfAccount->name,
                                'parent'=>$chartOfAccount->parent
                            ];
                        }
                        $temp[$accountType->id] = $tempData;
                    }

                    $incomeChartAccounts[$type->name] = $temp;
                }
                $items = [];
                $taxs = [];
                $projects = [];
                if(module_is_active('Taskly'))
                {
                    if(module_is_active('ProductService'))
                    {
                        $taxs = \Workdo\ProductService\Entities\Tax::where('workspace_id', getActiveWorkSpace())->get()->pluck('name', 'id');
                    }
                    $projects = \Workdo\Taskly\Entities\Project::where('workspace', getActiveWorkSpace())->projectonly()->get()->pluck('name', 'id');
                }
                foreach ($reservation->items as $reservationItem)
                {
                    $itemAmount               = $reservationItem->quantity * $reservationItem->price;
                    $reservationItem->itemAmount = $itemAmount;
                    $reservationItem->taxes      = reservation::tax($reservationItem->tax);
                    $items[]                  = $reservationItem;
                }

                $work_order = [];
                if(module_is_active('CMMS'))
                {
                    $work_order = WorkOrder::with('getLocation')->where(['company_id' => creatorId(), 'workspace' => getActiveWorkSpace(),  'status' => 1])->get()->pluck('wo_name','id');
                }
                if(module_is_active('CustomField')){
                    $reservation->customField = \Workdo\CustomField\Entities\CustomField::getData($reservation, 'Base','reservation');
                    $customFields             = \Workdo\CustomField\Entities\CustomField::where('workspace_id', '=', getActiveWorkSpace())->where('module', '=', 'Base')->where('sub_module','reservation')->get();
                }else{
                    $customFields = null;
                }
                return view('reservation.edit', compact('customers','reservation', 'reservation_number', 'category', 'items','taxs','projects','customFields','work_order','incomeChartAccounts'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, reservation $reservation)
    {
        if (Auth::user()->isAbleTo('reservation edit'))
        {
            if($reservation->workspace == getActiveWorkSpace())
            {
                if($request->reservation_type == "product")
                {
                    $validator = \Validator::make(
                        $request->all(),
                        [
                            'customer_id' => 'required',
                            'issue_date' => 'required',
                            'category_id' => 'required',
                            'items' => 'required',
                        ]
                    );
                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();

                        return redirect()->route('reservation.index')->with('error', $messages->first());
                    }
                    $reservation->customer_id        = $request->customer_id;
                    $reservation->issue_date         = $request->issue_date;
                    $reservation->category_id        = $request->category_id;
                    $reservation->account_type   = $request->account_type;
                    $reservation->reservation_module    = 'account';
                    $reservation->reservation_template    = $request->reservation_template;
                    $reservation->save();
                    if(module_is_active('CustomField'))
                    {
                        \Workdo\CustomField\Entities\CustomField::saveData($reservation, $request->customField);
                    }
                    $products = $request->items;

                    for ($i = 0; $i < count($products); $i++)
                    {
                        $reservationProduct = reservationProduct::find($products[$i]['id']);
                        if ($reservationProduct == null) {
                            $reservationProduct                = new reservationProduct();
                            $reservationProduct->reservation_id   = $reservation->id;
                        }

                        if (isset($products[$i]['item'])) {
                            $reservationProduct->product_id    = $products[$i]['item'];
                        }
                        $reservationProduct->product_type      = $products[$i]['product_type'];
                        $reservationProduct->quantity          = $products[$i]['quantity'];
                        $reservationProduct->tax               = $products[$i]['tax'];
                        $reservationProduct->discount          = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                        $reservationProduct->price             = $products[$i]['price'];
                        $reservationProduct->description       = str_replace( array( '\'', '"', '`','{',"\n"), ' ', $products[$i]['description']);
                        $reservationProduct->save();
                    }

                    // first parameter request second parameter reservation
                    event(new Updatereservation($request, $reservation));

                    return redirect()->route('reservation.index')->with('success', __('The reservation details are updated successfully'));

                }
                else if($request->reservation_type == "project")
                {
                    $validator = \Validator::make(
                        $request->all(), [
                                        'customer_id' => 'required',
                                        'issue_date' => 'required',
                                        'project' => 'required',
                                        'tax_project' => 'required',
                                        'items' => 'required',

                                    ]
                    );
                    if($validator->fails())
                    {
                        $messages = $validator->getMessageBag();

                        return redirect()->back()->with('error', $messages->first());
                    }

                    if(module_is_active('Account'))
                    {
                        $customer = \Workdo\Account\Entities\Customer::where('user_id', '=', $request->customer_id)->first();
                        $reservation->customer_id    = !empty($customer) ?  $customer->id : null;
                    }
                    if($request->reservation_type != $reservation->reservation_module)
                    {
                        reservationProduct::where('reservation_id', '=', $reservation->id)->delete();
                    }

                    $status = reservation::$statues;
                    $reservation->reservation_id       = $reservation->reservation_id;
                    $reservation->customer_id       = $request->customer_id;
                    $reservation->account_id        = $request->sale_chartaccount_id;
                    $reservation->issue_date        = $request->issue_date;
                    $reservation->account_type      = $request->account_type;
                    $reservation->category_id       = $request->project;
                    $reservation->reservation_module   = 'taskly';
                    $reservation->reservation_template = $request->reservation_template;
                    $reservation->save();

                    $products = $request->items;

                    if(module_is_active('CustomField'))
                    {
                        \Workdo\CustomField\Entities\CustomField::saveData($reservation, $request->customField);
                    }
                    $project_tax = implode(',',$request->tax_project);
                    for($i = 0; $i < count($products); $i++)
                    {
                        $reservationProduct = reservationProduct::find($products[$i]['id']);
                        if($reservationProduct == null)
                        {
                            $reservationProduct             = new reservationProduct();
                            $reservationProduct->reservation_id = $reservation->id;
                        }
                        $reservationProduct->product_id  = $products[$i]['item'];
                        $reservationProduct->quantity    = 1;
                        $reservationProduct->tax         = $project_tax;
                        $reservationProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                        $reservationProduct->price       = $products[$i]['price'];
                        $reservationProduct->description = $products[$i]['description'];
                        $reservationProduct->save();
                    }
                     // first parameter request second parameter reservation
                     event(new Updatereservation($request, $reservation));
                }
                else if($request->reservation_type == "parts")
                {
                    $validator = \Validator::make(
                        $request->all(), [
                            'customer_id' => 'required',
                            'issue_date' => 'required',
                            'work_order' => 'required',
                            'items' => 'required',
                        ]
                    );

                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();

                        return redirect()->route('reservation.index')->with('error', $messages->first());
                    }
                    $reservation->customer_id        = $request->customer_id;
                    $reservation->issue_date         = $request->issue_date;
                    $reservation->category_id        = $request->work_order;
                    $reservation->account_type   = $request->account_type;
                    $reservation->reservation_module    = 'cmms';
                    $reservation->reservation_template    = $request->reservation_template;
                    $reservation->save();
                    if(module_is_active('CustomField'))
                    {
                        \Workdo\CustomField\Entities\CustomField::saveData($reservation, $request->customField);
                    }
                    $products = $request->items;

                    for ($i = 0; $i < count($products); $i++)
                    {
                        $reservationProduct = reservationProduct::find($products[$i]['id']);
                        if ($reservationProduct == null) {
                            $reservationProduct                = new reservationProduct();
                            $reservationProduct->reservation_id   = $reservation->id;
                        }

                        if (isset($products[$i]['item'])) {
                            $reservationProduct->product_id    = $products[$i]['item'];
                        }
                        $reservationProduct->product_type      = $products[$i]['product_type'];
                        $reservationProduct->quantity          = $products[$i]['quantity'];
                        $reservationProduct->tax               = $products[$i]['tax'];
                        $reservationProduct->discount          = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                        $reservationProduct->price             = $products[$i]['price'];
                        $reservationProduct->description       = str_replace( array( '\'', '"', '`','{',"\n"), ' ', $products[$i]['description']);
                        $reservationProduct->save();
                    }

                    // first parameter request second parameter reservation
                    event(new Updatereservation($request, $reservation));

                    return redirect()->route('reservation.index')->with('success', __('The reservation details are updated successfully'));
                }
                return redirect()->route('reservation.index')->with('success', __('The reservation details are updated successfully'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy(reservation $reservation)
    {
        if (Auth::user()->isAbleTo('reservation delete'))
        {
            if($reservation->workspace == getActiveWorkSpace())
            {
                reservationProduct::where('reservation_id', '=', $reservation->id)->delete();
                reservationAttechment::where('reservation_id', $reservation->id)->delete();

                if(module_is_active('CustomField')){
                    $customFields = \Workdo\CustomField\Entities\CustomField::where('module','Base')->where('sub_module','reservation')->get();
                    foreach($customFields as $customField)
                    {
                        $value = \Workdo\CustomField\Entities\CustomFieldValue::where('record_id', '=', $reservation->id)->where('field_id',$customField->id)->first();
                        if(!empty($value)){
                            $value->delete();
                        }
                    }
                }
                // first parameter reservation
                event(new Destroyreservation($reservation));
                $reservation->delete();

                return redirect()->route('reservation.index')->with('success', __('The reservation has been deleted'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    function reservationNumber()
    {

        $latest = company_setting('reservation_starting_number');
        if($latest == null)
        {
            return 1;
        }
        else
        {
            return $latest;
        }
    }
    public function customer(Request $request)
    {
        if(module_is_active('Account'))
        {
            $customer = \Workdo\Account\Entities\Customer::where('user_id', '=', $request->id)->first();
            if(empty($customer))
            {
                $user = User::where('id',$request->id)->where('workspace_id',getActiveWorkSpace())->where('created_by',creatorId())->first();
                $customer['name'] = !empty($user->name) ? $user->name : '';
                $customer['email'] = !empty($user->email) ? $user->email : '';
            }
        }
        else
        {
            $user = User::where('id',$request->id)->where('workspace_id',getActiveWorkSpace())->where('created_by',creatorId())->first();
            $customer['name'] = !empty($user->name) ? $user->name : '';
            $customer['email'] = !empty($user->email) ? $user->email : '';
        }
        return view('reservation.customer_detail', compact('customer'));
    }
    public function product(Request $request)
    {
        $data['product']     = $product = \Workdo\ProductService\Entities\ProductService::find($request->product_id);
        $data['unit']        = !empty($product) ? ((!empty($product->unit())) ? $product->unit()->name : '') : '';
        $data['taxRate']     = $taxRate = !empty($product) ? (!empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0 ): 0;
        $data['taxes']       =  !empty($product) ? ( !empty($product->tax_id) ? $product->tax($product->tax_id) : 0) : 0;
        $salePrice           = !empty($product) ?  $product->sale_price : 0;
        $quantity            = 1;
        $taxPrice            = !empty($product) ? (($taxRate / 100) * ($salePrice * $quantity)) : 0;
        $data['totalAmount'] = !empty($product) ?  ($salePrice * $quantity + $taxPrice) : 0;

        return json_encode($data);
    }

    public function convert($reservation_id)
    {
        if(Auth::user()->isAbleTo('reservation convert invoice'))
        {
            $reservation                     = reservation::where('id', $reservation_id)->first();
            $reservation->is_convert         = 1;
            $convertInvoice                      = new Invoice();

            if($reservation->account_type == 'Projects')
            {
                $account_type = 'Taskly';
            }
            else
            {
                $account_type = 'Account';
            }

            if(module_is_active('Account'))
            {
                $customer = \Workdo\Account\Entities\Customer::where('user_id',$reservation['customer_id'])->first();
                $convertInvoice->customer_id    = !empty($customer) ?  $customer->id : null;
            }
            $convertInvoice->invoice_id          = $this->invoiceNumber();
            $convertInvoice->user_id             = $reservation->customer_id;
            $convertInvoice->account_type        = $account_type;
            $convertInvoice->account_id          = $reservation->account_id;
            $convertInvoice->issue_date          = date('Y-m-d');
            $convertInvoice->due_date            = date('Y-m-d');
            $convertInvoice->send_date           = null;
            $convertInvoice->category_id         = $reservation['category_id'];
            $convertInvoice->status              = 0;
            $convertInvoice->invoice_module      = $reservation['reservation_module'];
            $convertInvoice->invoice_template    = $reservation['reservation_template'];
            $convertInvoice->workspace           = $reservation['workspace'];
            $convertInvoice->created_by          = $reservation['created_by'];
            $convertInvoice->save();
            Invoice::starting_number( $convertInvoice->invoice_id + 1, 'invoice');
            $reservation->converted_invoice_id = $convertInvoice->id;
            $reservation->save();


            if($convertInvoice)
            {
                $reservationProduct = reservationProduct::where('reservation_id', $reservation_id)->get();
                foreach($reservationProduct as $product)
                {
                    $duplicateProduct             = new InvoiceProduct();
                    $duplicateProduct->invoice_id = $convertInvoice->id;
                    $duplicateProduct->product_type = $product->product_type;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity   = $product->quantity;
                    $duplicateProduct->tax        = $product->tax;
                    $duplicateProduct->discount   = $product->discount;
                    $duplicateProduct->price      = $product->price;
                    $duplicateProduct->description    = str_replace( array( '\'', '"', '`','{',"\n"), ' ', $product->description);


                    $duplicateProduct->save();


                    //inventory management (Quantity)
                    if($reservation['reservation_module'] == 'account'){
                        Invoice::total_quantity('minus',$duplicateProduct->quantity,$duplicateProduct->product_id);
                    }

                    //Product Stock Report
                    if(module_is_active('Account'))
                    {
                        $type='invoice';
                        $type_id = $convertInvoice->id;
                        $description= $duplicateProduct->quantity.''.__(' quantity sold in').' ' . reservation::reservationNumberFormat($reservation->reservation_id).' '.__('reservation convert to invoice').' '. Invoice::invoiceNumberFormat($convertInvoice->invoice_id);
                        \Workdo\Account\Entities\AccountUtility::addProductStock( $duplicateProduct->product_id,$duplicateProduct->quantity,$type,$description,$type_id);
                    }

                    //Warehouse Stock Report
                    $product = ProductService::find($duplicateProduct->product_id);
                    if(!empty($product) && !empty($product->warehouse_id))
                    {
                        reservation::warehouse_quantity('minus',$duplicateProduct->quantity,$duplicateProduct->product_id,$product->warehouse_id);
                    }


                }
            }
            event(new ConvertToInvoice($convertInvoice));

            return redirect()->back()->with('success', __('reservation to invoice convert successfully.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function duplicate($reservation_id)
    {
        if (Auth::user()->isAbleTo('reservation duplicate'))
        {
            $reservation                               = reservation::where('id', $reservation_id)->first();
            $duplicatereservation                      = new reservation();
            $duplicatereservation->account_type      =  $reservation['account_type'];
            $duplicatereservation->reservation_id         = $this->reservationNumber();
            $duplicatereservation->customer_id         = $reservation['customer_id'];
            $duplicatereservation->issue_date          = date('Y-m-d');
            $duplicatereservation->send_date           = null;
            $duplicatereservation->category_id         = $reservation['category_id'];
            $duplicatereservation->status              = 0;
            $duplicatereservation->reservation_module     = $reservation['reservation_module'];
            $duplicatereservation->reservation_template     = $reservation['reservation_template'];
            $duplicatereservation->created_by          = $reservation['created_by'];
            $duplicatereservation->workspace           = $reservation['workspace'];
            $duplicatereservation->save();
            Invoice::starting_number($duplicatereservation->reservation_id + 1, 'reservation');

            if ($duplicatereservation)
            {
                $reservationProduct = reservationProduct::where('reservation_id', $reservation_id)->get();
                foreach ($reservationProduct as $product)
                {
                    $duplicateProduct                   = new reservationProduct();
                    $duplicateProduct->reservation_id      = $duplicatereservation->id;
                    $duplicateProduct->product_type     = $product->product_type;
                    $duplicateProduct->product_id       = $product->product_id;
                    $duplicateProduct->quantity         = $product->quantity;
                    $duplicateProduct->tax              = $product->tax;
                    $duplicateProduct->discount         = $product->discount;
                    $duplicateProduct->price            = $product->price;
                    $duplicateProduct->save();
                }
            }
            event(new Duplicatereservation($duplicatereservation));
            return redirect()->back()->with('success', __('reservation duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function items(Request $request)
    {
        $items = reservationProduct::where('reservation_id', $request->reservation_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }
    public function payreservation($reservation_id)
    {
        if(!empty($reservation_id))
        {
            try {
                $id = \Illuminate\Support\Facades\Crypt::decrypt($reservation_id);
            } catch (\Throwable $th) {
                return redirect('login');
            }

            $reservation = reservation::where('id',$id)->first();
            if(!is_null($reservation))
            {
                $items         = [];
                $totalTaxPrice = 0;
                $totalQuantity = 0;
                $totalRate     = 0;
                $totalDiscount = 0;
                $taxesData     = [];

                foreach($reservation->items as $item)
                {
                    $totalQuantity += $item->quantity;
                    $totalRate     += $item->price;
                    $totalDiscount += $item->discount;
                    $taxes         = reservation::tax($item->tax);
                    $itemTaxes = [];
                    foreach($taxes as $tax)
                    {

                        if(!empty($tax))
                        {
                            $taxPrice            = reservation::taxRate($tax->rate, $item->price, $item->quantity,$item->discount);
                            $totalTaxPrice       += $taxPrice;
                            $itemTax['tax_name'] = $tax->tax_name;
                            $itemTax['tax']      = $tax->rate . '%';
                            $itemTax['price']    = currency_format_with_sym($taxPrice,$reservation->created_by);
                            $itemTaxes[]         = $itemTax;

                            if(array_key_exists($tax->name, $taxesData))
                            {
                                $taxesData[$itemTax['tax_name']] = $taxesData[$tax->tax_name] + $taxPrice;
                            }
                            else
                            {
                                $taxesData[$tax->tax_name] = $taxPrice;
                            }

                        }
                        else
                        {
                            $taxPrice            = reservation::taxRate(0, $item->price, $item->quantity,$item->discount);
                            $totalTaxPrice       += $taxPrice;
                            $itemTax['tax_name'] = 'No Tax';
                            $itemTax['tax']      = '';
                            $itemTax['price']    = currency_format_with_sym($taxPrice,$reservation->created_by);
                            $itemTaxes[]         = $itemTax;

                            if(array_key_exists('No Tax', $taxesData))
                            {
                                $taxesData[$tax->tax_name] = $taxesData['No Tax'] + $taxPrice;
                            }
                            else
                            {
                                $taxesData['No Tax'] = $taxPrice;
                            }

                        }
                    }

                    $item->itemTax = $itemTaxes;
                    $items[]       = $item;
                }
                $reservation->items         = $items;
                $reservation->totalTaxPrice = $totalTaxPrice;
                $reservation->totalQuantity = $totalQuantity;
                $reservation->totalRate     = $totalRate;
                $reservation->totalDiscount = $totalDiscount;
                $reservation->taxesData     = $taxesData;
                $ownerId = $reservation->created_by;

                $users = User::where('id',$reservation->created_by)->first();

                if(!is_null($users))
                {
                    \App::setLocale($users->lang);
                }
                else
                {
                    \App::setLocale('en');
                }

                $reservation    = reservation::where('id', $id)->first();
                $customer = $reservation->customer;
                $item   = $reservation->items;

                if(module_is_active('Account'))
                {
                    $customer = \Workdo\Account\Entities\Customer::where('user_id',$reservation->customer_id)->first();
                }
                if (!empty($customer)) {
                    $customer->model = 'Customer';
                } else {
                    $customer = $reservation->customer;
                    if (!empty($customer)) {
                        $customer->model = 'User';
                    }
                }
                if(module_is_active('CustomField')){
                    $reservation->customField = \Workdo\CustomField\Entities\CustomField::getData($reservation, 'Base','reservation');
                    $customFields             = \Workdo\CustomField\Entities\CustomField::where('workspace_id', '=', $reservation->workspace)->where('module', '=', 'Base')->where('sub_module','reservation')->get();
                }else{
                    $customFields = null;
                }

                $company_settings = getCompanyAllSetting($reservation->created_by,$reservation->workspace);
                $company_id = $reservation->created_by;
                $workspace_id = $reservation->workspace;
                return view('reservation.reservationpay',compact('reservation','item','customer','users','customFields','company_id','workspace_id','company_settings'));
            }
            else
            {
                return abort('404', 'The Link You Followed Has Expired');
            }
        }else{
            return abort('404', 'The Link You Followed Has Expired');
        }
    }

    public function statusChange(Request $request, $id)
    {
        $status           = $request->status;
        $reservation         = reservation::find($id);
        $reservation->status = $status;
        $reservation->save();

         // first parameter request second  reservation
        event(new StatusChangereservation($request,$reservation));

        return redirect()->back()->with('success', __('reservation status changed successfully.'));
    }
    public function resent($id)
    {
        if (Auth::user()->isAbleTo('reservation send'))
        {
            $reservation = reservation::where('id', $id)->first();

            $customer           = User::where('id', $reservation->customer_id)->first();
            $reservation->name     = !empty($customer) ? $customer->name : '';
            $reservation->reservation = reservation::reservationNumberFormat($reservation->reservation_id);

            $reservationId    = Crypt::encrypt($reservation->id);
            $reservation->url = route('reservation.pdf', $reservationId);

            // first parameter reservation
            event(new Resentreservation($reservation));
            //Email notification
            if((!empty(company_setting('reservation Status Updated')) && company_setting('reservation Status Updated')  == true) )
            {
                $uArr = [
                    'reservation_name' => $reservation->name,
                    'reservation_number' => $reservation->reservation,
                    'reservation_url' => $reservation->url,
                ];

                try
                {
                    $resp = EmailTemplate::sendEmailTemplate('reservation Send', [$customer->id => $customer->email],$uArr);
                }
                catch(\Exception $e)
                {
                    $resp['error'] = $e->getMessage();
                }
                return redirect()->back()->with('success', __('reservation successfully sent.') . ((isset($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
            return redirect()->back()->with('success', __('reservation sent email notification is off.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function sent($id)
    {

        if (\Auth::user()->isAbleTo('reservation send'))
        {
            $reservation            = reservation::where('id', $id)->first();
            $reservation->send_date = date('Y-m-d');
            $reservation->status    = 1;
            $reservation->save();
            if(module_is_active('Account'))
            {
                $customer         = \Workdo\Account\Entities\Customer::where('user_id', $reservation->customer_id)->first();
                if(empty($customer))
                {
                    $customer         = User::where('id', $reservation->customer_id)->first();
                }
            }
            else
            {
                $customer         = User::where('id', $reservation->customer_id)->first();

            }
            $reservation->name     = !empty($customer) ? $customer->name : '';

            $reservation->reservation = reservation::reservationNumberFormat($reservation->reservation_id);

            $reservationId    = Crypt::encrypt($reservation->id);
            $reservation->url = route('reservation.pdf', $reservationId);

            // first parameter reservation
            event(new Sentreservation($reservation));

            //Email notification
            if(!empty(company_setting('reservation Status Updated')) && company_setting('reservation Status Updated')  == true)
            {
                $uArr = [
                    'reservation_name' => $reservation->name,
                    'reservation_number' => $reservation->reservation,
                    'reservation_url' => $reservation->url,
                ];
                try
                {
                    $resp = EmailTemplate::sendEmailTemplate('reservation Send', [$customer->id => $customer->email],$uArr);
                }
                catch(\Exception $e)
                {
                    $resp['error'] = $e->getMessage();
                }
                return redirect()->back()->with('success', __('reservation successfully sent.') . ((isset($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
            return redirect()->back()->with('success', __('reservation sent email notification is off.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function reservation($reservation_id)
    {
        try {
            $reservationId = Crypt::decrypt($reservation_id);
            $reservation   = reservation::where('id', $reservationId)->first();

            if(module_is_active('Account'))
            {
                $customer         = \Workdo\Account\Entities\Customer::where('user_id', $reservation->customer_id)->first();
            }
            if (!empty($customer)) {
                $customer->model = 'Customer';
            } else {
                $customer = $reservation->customer;
                if (!empty($customer)) {
                    $customer->model = 'User';
                }
            }

            $items         = [];
            $totalTaxPrice = 0;
            $totalQuantity = 0;
            $totalRate     = 0;
            $totalDiscount = 0;
            $taxesData     = [];
            foreach ($reservation->items as $product) {
                $item              = new \stdClass();
                if($reservation->reservation_module == "taskly")
                {
                    $item->name        = !empty($product->product())?$product->product()->title:'';
                }
                elseif($reservation->reservation_module == "account")
                {
                    $item->name        = !empty($product->product()) ? $product->product()->name : '';
                    $item->product_type   = !empty($product->product_type) ? $product->product_type : '';
                }
                $item->quantity    = $product->quantity;
                $item->tax         = $product->tax;
                $item->discount    = $product->discount;
                $item->price       = $product->price;
                $item->description = $product->description;

                $totalQuantity += $item->quantity;
                $totalRate     += $item->price;
                $totalDiscount += $item->discount;

                if(module_is_active('ProductService'))
                {
                    $taxes = \Workdo\ProductService\Entities\Tax::tax($product->tax);

                    $itemTaxes = [];
                    if(!empty($item->tax))
                    {
                        $tax_price = 0;
                        foreach($taxes as $tax)
                        {
                            $taxPrice      = Invoice::taxRate($tax->rate, $item->price, $item->quantity,$item->discount);
                            $tax_price  += $taxPrice;
                            $totalTaxPrice += $taxPrice;

                            $itemTax['name']  = $tax->name;
                            $itemTax['rate']  = $tax->rate . '%';
                            $itemTax['price'] = currency_format_with_sym($taxPrice,$reservation->created_by);
                            $itemTaxes[]      = $itemTax;


                            if(array_key_exists($tax->name, $taxesData))
                            {
                                $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                            }
                            else
                            {
                                $taxesData[$tax->name] = $taxPrice;
                            }
                        }
                        $item->itemTax = $itemTaxes;
                        $item->tax_price = $tax_price;

                    }
                    else
                    {
                        $item->itemTax = [];
                    }

                    $items[] = $item;
                }
            }
            $reservation->itemData      = $items;
            $reservation->totalTaxPrice = $totalTaxPrice;
            $reservation->totalQuantity = $totalQuantity;
            $reservation->totalRate     = $totalRate;
            $reservation->totalDiscount = $totalDiscount;
            $reservation->taxesData     = $taxesData;

            if(module_is_active('CustomField')){
                $reservation->customField = \Workdo\CustomField\Entities\CustomField::getData($reservation, 'Base','reservation');
                $customFields             = \Workdo\CustomField\Entities\CustomField::where('workspace_id', '=', $reservation->workspace)->where('module', '=', 'Base')->where('sub_module','reservation')->get();
            }else{
                $customFields = null;
            }


            //Set your logo
            $company_logo = get_file(sidebar_logo());
            $company_settings = getCompanyAllSetting($reservation->created_by,$reservation->workspace);
            $reservation_logo = isset($company_settings['reservation_logo']) ? $company_settings['reservation_logo'] : '';
            if(isset($reservation_logo) && !empty($reservation_logo))
            {
                $img  = get_file($reservation_logo);
            }
            else{
                $img  = $company_logo;
            }
            if ($reservation) {
                $color      = '#'.(!empty($company_settings['reservation_color']) ? $company_settings['reservation_color'] : 'ffffff');
                $font_color = User::getFontColor($color);


                if(!empty($reservation->reservation_template))
                {
                    $reservation_template = $reservation->reservation_template;
                }
                else{
                $reservation_template  = (!empty($company_settings['reservation_template']) ? $company_settings['reservation_template'] : 'template1');
                }

                $settings['site_rtl'] = isset($company_settings['site_rtl']) ? $company_settings['site_rtl'] : '';
                $settings['company_name'] = isset($company_settings['company_name']) ? $company_settings['company_name'] : '';
                $settings['company_email'] = isset($company_settings['company_email']) ? $company_settings['company_email'] : '';
                $settings['company_telephone'] = isset($company_settings['company_telephone']) ? $company_settings['company_telephone'] : '';
                $settings['company_address'] = isset($company_settings['company_address']) ? $company_settings['company_address'] : '';
                $settings['company_city'] = isset($company_settings['company_city']) ? $company_settings['company_city'] : '';
                $settings['company_state'] = isset($company_settings['company_state']) ? $company_settings['company_state'] : '';
                $settings['company_zipcode'] = isset($company_settings['company_zipcode']) ? $company_settings['company_zipcode'] : '';
                $settings['company_country'] = isset($company_settings['company_country']) ? $company_settings['company_country'] : '';
                $settings['registration_number'] = isset($company_settings['registration_number']) ? $company_settings['registration_number'] : '';
                $settings['tax_type'] = isset($company_settings['tax_type']) ? $company_settings['tax_type'] : '';
                $settings['vat_number'] = isset($company_settings['vat_number']) ? $company_settings['vat_number'] : '';
                $settings['reservation_footer_title'] = isset($company_settings['reservation_footer_title']) ? $company_settings['reservation_footer_title'] : '';
                $settings['reservation_footer_notes'] = isset($company_settings['reservation_footer_notes']) ? $company_settings['reservation_footer_notes'] : '';
                $settings['reservation_shipping_display'] = isset($company_settings['reservation_shipping_display']) ? $company_settings['reservation_shipping_display'] : '';
                $settings['reservation_template'] = isset($company_settings['reservation_template']) ? $company_settings['reservation_template'] : '';
                $settings['reservation_color'] = isset($company_settings['reservation_color']) ? $company_settings['reservation_color'] : '';
                $settings['reservation_qr_display'] = isset($company_settings['reservation_qr_display']) ? $company_settings['reservation_qr_display'] : '';

                return view('reservation.templates.' .$reservation_template, compact('reservation', 'color', 'settings', 'customer', 'img', 'font_color','customFields'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('reservation Not Found.'));
        }

    }
    function invoiceNumber()
    {
        $invoice = Invoice::where('workspace',getActiveWorkSpace())->where('created_by',creatorId())->latest()->first();
        if(empty($invoice))
        {
            $latest = company_setting('invoice_starting_number');
            if ($latest == null) {
                $latest = 1;
            } else {
                $latest = $latest;
            }
        }
        else
        {
            $latest= $invoice->invoice_id +1;
        }
        return $latest;
    }
    public function saveTemplateSettings(Request $request)
    {
        $user = Auth::user();
        if($request->hasFile('reservation_logo'))
        {
            $reservation_logo         = $user->id.'_reservation_logo'.time().'.png';

            $uplaod = upload_file($request,'reservation_logo',$reservation_logo,'reservation_logo');
            if($uplaod['flag'] == 1)
            {
                $url = $uplaod['url'];
                $old_reservation_logo = company_setting('reservation_logo');
                if(!empty($old_reservation_logo) && check_file($old_reservation_logo))
                {
                    delete_file($old_reservation_logo);
                }
            }
            else
            {
                return redirect()->back()->with('error',$uplaod['msg']);
            }
        }
        $post = $request->all();
        unset($post['_token']);
        if(isset($post['reservation_footer_notes']))
        {
            $validator = Validator::make($request->all(),
            [
                'reservation_footer_notes' => 'required|string|regex:/^[^\r\n]*$/',
            ]);
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
        }
        if (isset($post['reservation_template']) && (!isset($post['reservation_color']) || empty($post['reservation_color'])))
        {
            $post['reservation_color'] = "ffffff";
        }
        if(isset($post['reservation_logo']))
        {
            $post['reservation_logo'] = $url;
        }
        if(!isset($post['reservation_shipping_display']))
        {
            $post['reservation_shipping_display'] = 'off';
        }
        if(!isset($post['reservation_qr_display']))
        {
            $post['reservation_qr_display'] = 'off';
        }
        foreach ($post as $key => $value) {
            // Define the data to be updated or inserted
            $data = [
                'key' => $key,
                'workspace' => getActiveWorkSpace(),
                'created_by' => creatorId(),
            ];
            // Check if the record exists, and update or insert accordingly
            Setting::updateOrInsert($data, ['value' => $value]);
        }
        // Settings Cache forget
        comapnySettingCacheForget();
        return redirect()->back()->with('success', __('reservation Print setting save sucessfully.'));
    }
    public function previewInvoice($template, $color)
    {
        $reservation = new reservation();

        $customer                   = new \stdClass();
        $customer->name             = '<Name>';
        $customer->email            = '<Email>';
        $customer->shipping_name    = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state   = '<State>';
        $customer->shipping_city    = '<City>';
        $customer->shipping_phone   = '<Customer Phone Number>';
        $customer->shipping_zip     = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name     = '<Customer Name>';
        $customer->billing_country  = '<Country>';
        $customer->billing_state    = '<State>';
        $customer->billing_city     = '<City>';
        $customer->billing_phone    = '<Customer Phone Number>';
        $customer->billing_zip      = '<Zip>';
        $customer->billing_address  = '<Address>';

        $totalTaxPrice = 0;
        $taxesData     = [];

        $items = [];
        for($i = 1; $i <= 3; $i++)
        {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;
            $item->description    = 'In publishing and graphic design, Lorem ipsum is a placeholder';

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach($taxes as $k => $tax)
            {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTaxes[]      = $itemTax;
                if(array_key_exists('Tax ' . $k, $taxesData))
                {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                }
                else
                {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $item->tax_price = 10;
            $items[]       = $item;
        }

        $reservation->reservation_id = 1;
        $reservation->issue_date  = date('Y-m-d H:i:s');
        $reservation->due_date    = date('Y-m-d H:i:s');
        $reservation->itemData    = $items;

        $reservation->totalTaxPrice = 60;
        $reservation->totalQuantity = 3;
        $reservation->totalRate     = 300;
        $reservation->totalDiscount = 10;
        $reservation->taxesData     = $taxesData;
        $reservation->customField   = [];
        $customFields            = [];

        $preview    = 1;
        $color      = '#' . $color;
        $font_color = User::getFontColor($color);

        $company_logo = get_file(sidebar_logo());
        $company_settings = getCompanyAllSetting();

        $reservation_logo =  isset($company_settings['reservation_logo']) ? $company_settings['reservation_logo'] : '';

        if(!empty($reservation_logo))
        {
            $img = get_file($reservation_logo);
        }
        else{
            $img          =  $company_logo;
        }
        $settings['site_rtl'] = isset($company_settings['site_rtl']) ? $company_settings['site_rtl'] : '';
        $settings['company_name'] = isset($company_settings['company_name']) ? $company_settings['company_name'] : '';
        $settings['company_email'] = isset($company_settings['company_email']) ? $company_settings['company_email'] : '';
        $settings['company_telephone'] = isset($company_settings['company_telephone']) ? $company_settings['company_telephone'] : '';
        $settings['company_address'] = isset($company_settings['company_address']) ? $company_settings['company_address'] : '';
        $settings['company_city'] = isset($company_settings['company_city']) ? $company_settings['company_city'] : '';
        $settings['company_state'] = isset($company_settings['company_state']) ? $company_settings['company_state'] : '';
        $settings['company_zipcode'] = isset($company_settings['company_zipcode']) ? $company_settings['company_zipcode'] : '';
        $settings['company_country'] = isset($company_settings['company_country']) ? $company_settings['company_country'] : '';
        $settings['registration_number'] = isset($company_settings['registration_number']) ? $company_settings['registration_number'] : '';
        $settings['tax_type'] = isset($company_settings['tax_type']) ? $company_settings['tax_type'] : '';
        $settings['vat_number'] = isset($company_settings['vat_number']) ? $company_settings['vat_number'] : '';
        $settings['reservation_footer_title'] = isset($company_settings['reservation_footer_title']) ? $company_settings['reservation_footer_title'] : '';
        $settings['reservation_footer_notes'] = isset($company_settings['reservation_footer_notes']) ? $company_settings['reservation_footer_notes'] : '';
        $settings['reservation_shipping_display'] = isset($company_settings['reservation_shipping_display']) ? $company_settings['reservation_shipping_display'] : '';
        $settings['reservation_template'] = isset($company_settings['reservation_template']) ? $company_settings['reservation_template'] : '';
        $settings['reservation_color'] = isset($company_settings['reservation_color']) ? $company_settings['reservation_color'] : '';
        $settings['reservation_qr_display'] = isset($company_settings['reservation_qr_display']) ? $company_settings['reservation_qr_display'] : '';

        return view('reservation.templates.' . $template, compact('reservation', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }
    public function reservationSectionGet(Request $request)
    {
        $type = $request->type;
        $acction = $request->acction;
        $reservation = [];
        if($acction == 'edit')
        {
            $reservation = reservation::find($request->reservation_id);
        }

        if($request->type == "product" && module_is_active('Account'))
        {
            $product_services = \Workdo\ProductService\Entities\ProductService::where('workspace_id', getActiveWorkSpace())->get()->pluck('name', 'id');
            $product_services_count =$product_services->count();
            $product_type = ProductService::$product_type;
            $returnHTML = view('reservation.section',compact('product_services','product_type','type' ,'acction','reservation','product_services_count'))->render();
                $response = [
                    'is_success' => true,
                    'message' => '',
                    'html' => $returnHTML,
                ];
            return response()->json($response);
        }
        elseif($request->type == "project" && module_is_active('Taskly'))
        {
            $projects = \Workdo\Taskly\Entities\Project::where('workspace', getActiveWorkSpace())->projectonly();
            if($request->project_id != 0)
            {
                $projects = $projects->where('id',$request->project_id);
            }
            $projects = $projects->first();
            $tasks=[];
            if(!empty($projects)){

                $tasks = \Workdo\Taskly\Entities\Task::where('project_id', $projects->id)->get()->pluck('title', 'id');
                if($acction != 'edit')
                {
                    $tasks->prepend('--', '');
                }
            }
            $returnHTML = view('reservation.section',compact('tasks','type' ,'acction','reservation'))->render();
                $response = [
                    'is_success' => true,
                    'message' => '',
                    'html' => $returnHTML,
                ];
            return response()->json($response);
        }
        elseif($request->type == "parts" && module_is_active('CMMS'))
        {
            $product_services = \Workdo\ProductService\Entities\ProductService::where('workspace_id', getActiveWorkSpace())->where('type','parts')->get()->pluck('name', 'id');
            $product_services_count =$product_services->count();
            if($acction != 'edit')
            {
                $product_services->prepend('--', '');
            }

            if (module_is_active('CMMS')) {
                $product_type['parts'] = 'Parts';
            }
            $returnHTML = view('reservation.section',compact('product_services','product_type','type' ,'acction','reservation','product_services_count'))->render();
                $response = [
                    'is_success' => true,
                    'message' => '',
                    'html' => $returnHTML,
                ];
            return response()->json($response);
        }
        else
        {
            return [];
        }
    }
    public function TaxDetailGet(Request $request)
    {
        $taxs_data = [];
        if(module_is_active('ProductService'))
        {
            $taxs_data = \Workdo\ProductService\Entities\Tax::whereIn('id',$request->Taxid)->where('workspace_id', getActiveWorkSpace())->get();
        }
        return $taxs_data;
    }
    public function getTax(Request $request){

        if(module_is_active('ProductService'))
        {
            $taxs_data = \Workdo\ProductService\Entities\Tax::whereIn('id',$request->tax_id)->where('workspace_id', getActiveWorkSpace())->get();
            return json_encode($taxs_data);
        }else{
            $taxs_data = [];
            return json_encode($taxs_data);
        }

    }
    public function productDestroy(Request $request)
    {
        if(Auth::user()->isAbleTo('reservation product delete'))
        {
            reservationProduct::where('id', '=', $request->id)->delete();

            return response()->json(['success' => __('The reservation product has been deleted')]);
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')]);
        }
    }
    public function reservationAttechment(Request $request,$id)
    {
        $reservation = reservation::find($id);
        $file_name = time() . "_" . $request->file->getClientOriginalName();

        $upload = upload_file($request,'file',$file_name,'reservation_attachment',[]);

        $fileSizeInBytes = \File::size($upload['url']);
        $fileSizeInKB = round($fileSizeInBytes / 1024, 2);

        if ($fileSizeInKB < 1024) {
            $fileSizeFormatted = $fileSizeInKB . " KB";
        } else {
            $fileSizeInMB = round($fileSizeInKB / 1024, 2);
            $fileSizeFormatted = $fileSizeInMB . " MB";
        }

        if($upload['flag'] == 1){
            $file                 = reservationAttechment::create(
                [
                    'reservation_id' => $reservation->id,
                    'file_name'  => $file_name,
                    'file_path' => $upload['url'],
                    'file_size' => $fileSizeFormatted,
                ]
            );
            $return               = [];
            $return['is_success'] = true;


            return response()->json($return);
        }else{

            return response()->json(
                [
                    'is_success' => false,
                    'error' => $upload['msg'],
                ], 401
            );
        }
    }

    public function reservationAttechmentDestroy($id)
    {
        $file = reservationAttechment::find($id);

        if (!empty($file->file_path)) {
            delete_file($file->file_path);
        }
        $file->delete();
        return redirect()->back()->with('success', __('The file has been deleted'));
    }

    public function reservationQuickStats()
    {
        $total_reservations = reservation::where('workspace',getActiveWorkSpace())->count();
        $statues = reservation::$statues;
        return view('reservation.statsreport',compact('statues','total_reservations'));
    }

}
