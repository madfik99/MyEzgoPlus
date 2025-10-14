{{-- @component('mail::message')
# Booking Details

Thank you for renting a vehicle from us!
If you have any enquiry, please call +6014-4005050 or WhatsApp: [wa.me/60144005050](https://wa.me/60144005050).

@component('mail::panel')
**Reservation Number:** {{ $data['agreement_no'] }}  
**Pickup:** {{ $data['pickup_date'] }} {{ $data['pickup_time'] }} @ {{ $data['pickup_location'] }}  
**Return:** {{ $data['return_date'] }} {{ $data['return_time'] }} @ {{ $data['return_location'] }}
@endcomponent

### Vehicle
- Make: {{ $data['vehicle_make'] }}
- Model: {{ $data['vehicle_model'] }}

### Driver
- Name: {{ $data['fullname'] }}
- Phone: {{ $data['phone_no'] }}
- Address: {{ $data['address'] }}, {{ $data['postcode'] }} {{ $data['city'] }}, {{ $data['country'] }}

### Cost
- Base Cost: RM{{ number_format($data['sub_total'], 2) }}
- Subtotal: RM{{ number_format($data['est_total'], 2) }}

@isset($data['create_online']) 
@if($data['create_online'] === true && !empty($data['temp_password']))
---
## Account Activation
- **Username:** Your NRIC/Passport
- **Temporary Password:** `{{ $data['temp_password'] }}`

You can login here:

@component('mail::button', ['url' => config('app.url').'/customer'])
Login
@endcomponent

> You’ll be prompted to reset your password on first login.
@endif
@endisset

@component('mail::button', ['url' => $data['agreement_url']])
View Agreement
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent --}}


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Details</title>
    <style>
        table { border-collapse: collapse; }
        table, th, td { border: 1px solid #576574; padding: 15px; }
        .no-border { border: 0 !important; }
        .nav-link { text-decoration: none; font-family: arial narrow, sans-serif; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    {{-- ========= Header (Logo + 4 links on one line) ========= --}}
    @php
        $companyBase   = $data['company_base_url']  ?? config('app.url') ?? 'https://www.myezgo.com';
        $bookAgainUrl  = $data['company_base_url']    ?? (config('app.url') ?? 'https://www.myezgo.com');
        $listingUrl    = $data['product_listing_url'] ?? ($companyBase.'/product_listing.php');
        $signinUrl     = $data['login_url']           ?? ($companyBase.'/login.php');
        $aboutUrl      = $data['about_url']           ?? ($companyBase.'/about_us.php');

        // Embed the logo and get a CID string. Fallback to absolute URL if needed.
        $logoCid = null;
        try {
            $logoPath = public_path('assets/img/company/logo.png'); // public/assets/img/company/logo.png
            if (file_exists($logoPath)) {
                // IMPORTANT: use embed(), which returns a string CID.
                $logoCid = $message->embed($logoPath);
            }
        } catch (\Throwable $e) {
            $logoCid = null;
        }
        $logoUrlFallback = secure_asset('assets/img/company/logo.png');
    @endphp

    <table width="100%" class="no-border" style="border:0;">
        <tr class="no-border">
            <td class="no-border" style="width:35%; vertical-align:middle;">
                <a href="{{ e($companyBase) }}">
                    @if($logoCid)
                        <img src="{{ $logoCid }}" alt="Company Logo" style="max-height:60px;">
                    @else
                        <img src="{{ $logoUrlFallback }}" alt="Company Logo" style="max-height:60px;">
                    @endif
                </a>
            </td>
            <td class="no-border" style="width:65%; text-align:right; vertical-align:middle;">
                <span class="nowrap">
                    <a class="nav-link" href="{{ e($bookAgainUrl) }}"><b>BOOK AGAIN</b></a>
                    &nbsp;|&nbsp;
                    <a class="nav-link" href="{{ e($listingUrl) }}"><b>LIST OF VEHICLE</b></a>
                    &nbsp;|&nbsp;
                    <a class="nav-link" href="{{ e($signinUrl) }}"><b>REGISTER / SIGN IN</b></a>
                    &nbsp;|&nbsp;
                    <a class="nav-link" href="{{ e($aboutUrl) }}"><b>CONTACT US</b></a>
                </span>
            </td>
        </tr>
    </table>

    <br>

    {{-- ========= BEGIN legacy content (enhanced) ========= --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:0;">
  <tr>
    <td align="center" style="padding:0 16px;">
      <!-- Card wrapper (max-width 600) -->
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; border:1px solid #e5e7eb;">
        <!-- Intro -->
        <tr>
          <td style="padding:24px; font-family:Arial,Helvetica,sans-serif; color:#111827;">
            <p style="margin:0 0 8px 0; font-size:18px; line-height:24px; font-weight:700;">
              Thank you for renting a vehicle from us!
            </p>
            <p style="margin:0; font-size:14px; line-height:20px;">
              If you have any enquiry, please call <strong>+6014-4005050</strong> or WhatsApp us:
              <a href="https://wa.me/60144005050" style="color:#2563eb; text-decoration:underline;">wa.me/60144005050</a>.
            </p>
          </td>
        </tr>

        @if(!empty($data['create_online']))
        <!-- Account Activation -->
        <tr>
          <td style="padding:0 24px 24px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;">
              <tr>
                <td colspan="2" style="background:#f3f4f6; padding:12px 16px; font:700 14px/20px Arial,Helvetica,sans-serif; color:#111827;">
                  Account Activation
                </td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">
                  Username
                </td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">
                  Your NRIC No./Passport ID
                </td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">
                  Password
                </td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">
                  {{ $data['temp_password'] ?? '' }}
                </td>
              </tr>
              <tr>
                <td colspan="2" style="padding:12px 16px; border-top:1px solid #e5e7eb;">
                  <p style="margin:0; font:13px/18px Arial,Helvetica,sans-serif; color:#111827;">
                    You can log in at
                    <a href="{{ e(($data['company_base_url'] ?? config('app.url') ?? 'https://www.myezgo.com')).'/customer' }}"
                       style="color:#2563eb; text-decoration:underline;">our website</a>.
                    On first login, you’ll be prompted to reset your password.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @endif

        <!-- Booking Details -->
        <tr>
          <td style="padding:0 24px 24px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;">
              <tr>
                <td colspan="2" style="background:#f3f4f6; padding:12px 16px; font:700 14px/20px Arial,Helvetica,sans-serif; color:#111827;">
                  Booking Details
                </td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Reservation Number</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['agreement_no']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Pickup Date</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['pickup_date']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Pickup Time</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['pickup_time']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Pickup Location</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['pickup_location']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Return Date</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['return_date']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Return Time</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['return_time']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Return Location</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['return_location']) }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Vehicle Details -->
        <tr>
          <td style="padding:0 24px 24px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;">
              <tr>
                <td colspan="2" style="background:#f3f4f6; padding:12px 16px; font:700 14px/20px Arial,Helvetica,sans-serif; color:#111827;">
                  Vehicle Details
                </td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Vehicle Make</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['make']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Vehicle Model</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['model']) }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Driver Details -->
        <tr>
          <td style="padding:0 24px 24px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;">
              <tr>
                <td colspan="2" style="background:#f3f4f6; padding:12px 16px; font:700 14px/20px Arial,Helvetica,sans-serif; color:#111827;">
                  Driver Details
                </td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Full Name</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['fullname']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Phone No.</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['phone_no']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Address</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['address']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Postcode</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['postcode']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">City</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['city']) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Country</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">{{ e($data['country']) }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Cost Details -->
        <tr>
          <td style="padding:0 24px 8px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;">
              <tr>
                <td colspan="2" style="background:#f3f4f6; padding:12px 16px; font:700 14px/20px Arial,Helvetica,sans-serif; color:#111827;">
                  Cost Details
                </td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Base Cost</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">RM{{ number_format((float)$data['sub_total'], 2) }}</td>
              </tr>
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Subtotal</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">RM{{ number_format((float)$data['est_total'], 2) }}</td>
              </tr>
              @isset($data['refund_dep'])
              <tr>
                <td style="width:40%; padding:12px 16px; font:700 13px/18px Arial,Helvetica,sans-serif; color:#374151; border-top:1px solid #e5e7eb;">Deposit</td>
                <td style="padding:12px 16px; font:13px/18px Arial,Helvetica,sans-serif; color:#111827; border-top:1px solid #e5e7eb;">RM{{ number_format((float)$data['refund_dep'], 2) }}</td>
              </tr>
              @endisset
            </table>
          </td>
        </tr>

        {{-- <!-- CTA -->
        <tr>
          <td style="padding:0 24px 24px 24px;">
            @php $agreementUrl = $data['agreement_url'] ?? '#'; @endphp
            <a href="{{ e($agreementUrl) }}"
               style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; font:700 13px/18px Arial,Helvetica,sans-serif; padding:10px 16px; border-radius:4px;">
              
            </a>
          </td>
        </tr> --}}

      </table>
    </td>
  </tr>
</table>
{{-- ========= END legacy content ========= --}}

    
    
</body>
</html>




