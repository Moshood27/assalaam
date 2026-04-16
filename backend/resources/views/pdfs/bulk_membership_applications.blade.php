<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Membership Enrolment Forms</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.3; margin: 0; padding: 0; }
        .page { position: relative; width: 100%; page-break-after: always; padding: 20px; box-sizing: border-box; min-height: 1000px; }
        .header { text-align: center; margin-bottom: 100px; border-bottom: 2px solid #047857; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #047857; text-transform: uppercase; font-size: 18px; }
        .header p { margin: 3px 0; font-weight: bold; }
        .photo-box { position: absolute; top: 20px; right: 20px; width: 100px; height: 120px; border: 1px solid #ccc; text-align: center; line-height: 120px; font-size: 9px; color: #999; overflow: hidden; z-index: 100; }
        .section { margin-bottom: 15px; position: relative; }
        .section-title { background: #f3f4f6; padding: 4px 8px; font-weight: bold; color: #047857; text-transform: uppercase; margin-bottom: 8px; border-left: 3px solid #047857; }
        .label { font-weight: bold; width: 150px; }
        .value { border-bottom: 1px dotted #ccc; flex: 1; min-height: 1.2em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table td { padding: 4px; vertical-align: top; }
        .signature-box { margin-top: 8px; text-align: center; }
        .signature-image { max-width: 120px; max-height: 50px; border-bottom: 1px solid #000; display: block; margin: 0 auto 3px; }
        .footer { margin-top: 20px; font-size: 9px; text-align: center; color: #666; border-top: 1px solid #eee; padding-top: 10px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @php
        $getPath = function($path) {
            if (!$path) return null;

            $absolutePath = null;

            // 1. If it's already a full path and exists
            if (file_exists($path)) {
                $absolutePath = $path;
            }

            // 2. Check public/
            if (!$absolutePath) {
                $publicPath = public_path($path);
                if (file_exists($publicPath)) {
                    $absolutePath = $publicPath;
                }
            }

            // 3. Check storage/app/public/
            if (!$absolutePath) {
                $storagePath = storage_path('app/public/' . $path);
                if (file_exists($storagePath)) {
                    $absolutePath = $storagePath;
                }
            }

            // 4. Try to resolve if it's a "storage/..." URL path (common for Filament)
            if (!$absolutePath && str_starts_with($path, 'storage/')) {
                $trimmedPath = substr($path, 8);
                $storagePath2 = storage_path('app/public/' . $trimmedPath);
                if (file_exists($storagePath2)) {
                    $absolutePath = $storagePath2;
                }
            }

            if ($absolutePath) {
                try {
                    $imageData = base64_encode(file_get_contents($absolutePath));
                    $mimeType = 'image/jpeg';
                    if (function_exists('mime_content_type')) {
                        $mimeType = @mime_content_type($absolutePath) ?: 'image/jpeg';
                    } else {
                        $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
                        $mimeType = match(strtolower($ext)) {
                            'png' => 'image/png',
                            'webp' => 'image/webp',
                            'gif' => 'image/gif',
                            default => 'image/jpeg',
                        };
                    }
                    return 'data:' . $mimeType . ';base64,' . $imageData;
                } catch (\Exception $e) {
                    return null;
                }
            }

            // 5. If it's a URL, try to fetch it if isRemoteEnabled is on (optional, but robust)
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                try {
                    $imageData = base64_encode(file_get_contents($path));
                    // Simple mime detection by extension for URLs
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $mimeType = match(strtolower($ext)) {
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                        'gif' => 'image/gif',
                        default => 'image/jpeg',
                    };
                    return 'data:' . $mimeType . ';base64,' . $imageData;
                } catch (\Exception $e) {
                    return null;
                }
            }

            return null;
        };
    @endphp

    @foreach($users as $application)
        <div class="page">
            <div class="header">
                <h1>{{ config('app.name') }} COOPERATIVE SOCIETY</h1>
                <p>MEMBERSHIP ENROLMENT FORM</p>
                @if($application->membership_number)
                    <p style="color: #047857;">MEMBER NO: {{ $application->membership_number }}</p>
                @endif
            </div>

            @if($passport = $getPath($application->passport_path))
                <div class="photo-box">
                    <img src="{{ $passport }}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @else
                <div class="photo-box">PASSPORT PHOTO</div>
            @endif

            <div class="section">
                <div class="section-title">1. Basic Personal Information</div>
                <table>
                    <tr>
                        <td class="label">Surname (Last Name):</td>
                        <td class="value">{{ $application->surname }}</td>
                        <td class="label">Other Names:</td>
                        <td class="value">{{ $application->other_names }}</td>
                    </tr>
                    <tr>
                        <td class="label">Sex (Gender):</td>
                        <td class="value">{{ ucfirst((string) $application->gender) }}</td>
                        <td class="label">Date of Birth:</td>
                        <td class="value">{{ $application->dob ? (\Carbon\Carbon::parse($application->dob))->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Native (State/Town):</td>
                        <td class="value">{{ $application->native_place }}</td>
                        <td class="label">Marital Status:</td>
                        <td class="value">{{ ucfirst((string) $application->marital_status) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Occupation:</td>
                        <td class="value" colspan="3">{{ $application->occupation }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">2. Contact Information</div>
                <table>
                    <tr>
                        <td class="label">Phone No (Primary):</td>
                        <td class="value">{{ $application->phone }}</td>
                        <td class="label">Phone No (Secondary):</td>
                        <td class="value">{{ $application->secondary_phone }}</td>
                    </tr>
                    <tr>
                        <td class="label">E-mail Address:</td>
                        <td class="value" colspan="3">{{ $application->email }}</td>
                    </tr>
                    <tr>
                        <td class="label">Residential Address:</td>
                        <td class="value" colspan="3">{{ $application->residential_address }}</td>
                    </tr>
                    <tr>
                        <td class="label">Permanent Home Address:</td>
                        <td class="value" colspan="3">{{ $application->permanent_address }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">3. Business & Professional Information</div>
                <table>
                    <tr>
                        <td class="label">Nature of Business:</td>
                        <td class="value" colspan="3">{{ $application->nature_of_business }}</td>
                    </tr>
                    <tr>
                        <td class="label">Business Address:</td>
                        <td class="value" colspan="3">{{ $application->business_address }}</td>
                    </tr>
                    <tr>
                        <td class="label">Other Cooperatives:</td>
                        <td class="value">{{ $application->has_other_cooperatives ? 'Yes' : 'No' }}</td>
                        <td class="label">Details:</td>
                        <td class="value">{{ $application->other_cooperative_details }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">4. Next of Kin (Legacy Information)</div>
                <table>
                    <tr>
                        <td class="label">Next of Kin Name:</td>
                        <td class="value">{{ $application->nok_name }}</td>
                        <td class="label">Relationship:</td>
                        <td class="value">{{ $application->nok_relationship }}</td>
                    </tr>
                    <tr>
                        <td class="label">Phone Number:</td>
                        <td class="value">{{ $application->nok_phone }}</td>
                        <td class="label">Address:</td>
                        <td class="value">{{ $application->nok_address }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">5. Guarantor Details</div>
                <table>
                    <tr>
                        <td class="label">Guarantor Name:</td>
                        <td class="value">{{ $application->guarantor_name }}</td>
                        <td class="label">Occupation:</td>
                        <td class="value">{{ $application->guarantor_occupation }}</td>
                    </tr>
                    <tr>
                        <td class="label">Phone Number:</td>
                        <td class="value">{{ $application->guarantor_phone }}</td>
                        <td class="label">Address:</td>
                        <td class="value">{{ $application->guarantor_address }}</td>
                    </tr>
                </table>
                @if($sig = $getPath($application->guarantor_signature_path))
                    <div class="signature-box" style="float: right; width: 180px;">
                        <img src="{{ $sig }}" class="signature-image">
                        <p>Guarantor's Signature</p>
                    </div>
                @endif
                <div style="clear: both;"></div>
            </div>

            <div class="section">
                <div class="section-title">6. Religious Information & Imam's Attestation</div>
                <table>
                    <tr>
                        <td class="label">Religious Society:</td>
                        <td class="value">{{ $application->religious_society_name }}</td>
                        <td class="label">Imam/Amir Name:</td>
                        <td class="value">{{ $application->imam_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Mosque Address:</td>
                        <td class="value" colspan="3">{{ $application->mosque_address }}</td>
                    </tr>
                    <tr>
                        <td class="label">Imam Phone No:</td>
                        <td class="value">{{ $application->imam_phone }}</td>
                        <td class="label">Duration:</td>
                        <td class="value">{{ $application->duration_of_jamma_membership }}</td>
                    </tr>
                </table>
                @if($application->imam_approval_status)
                    <div class="signature-box" style="float: right; width: 180px;">
                        @if($sig = $getPath($application->imam_signature_path))
                            <img src="{{ $sig }}" class="signature-image">
                        @endif
                        <p>Imam's Signature/Stamp</p>
                    </div>
                @endif
                <div style="clear: both;"></div>
            </div>

            @if($application->gender === 'female' || $application->spouse_father_name)
                <div class="section">
                    <div class="section-title">7. Wali/Spouse Details (Female Members)</div>
                    <table>
                        <tr>
                            <td class="label">Father/Spouse Name:</td>
                            <td class="value">{{ $application->spouse_father_name }}</td>
                            <td class="label">Phone Number:</td>
                            <td class="value">{{ $application->spouse_father_phone }}</td>
                        </tr>
                        <tr>
                            <td class="label">Residential Address:</td>
                            <td class="value" colspan="3">{{ $application->spouse_father_address }}</td>
                        </tr>
                        <tr>
                            <td class="label">Business Address:</td>
                            <td class="value" colspan="3">{{ $application->spouse_father_business_address }}</td>
                        </tr>
                    </table>
                    @if($sig = $getPath($application->spouse_father_consent_signature_path))
                        <div class="signature-box" style="float: right; width: 180px;">
                            <img src="{{ $sig }}" class="signature-image">
                            <p>Consent Signature</p>
                        </div>
                    @endif
                    <div style="clear: both;"></div>
                </div>
            @endif

            <div class="section">
                <div class="section-title">8. Official Use Only</div>
                <table>
                    <tr>
                        <td class="label">Admission Number:</td>
                        <td class="value">{{ $application->admission_form_number ?: $application->membership_number }}</td>
                        <td class="label">Admission Date:</td>
                        <td class="value">{{ $application->admission_date ? (\Carbon\Carbon::parse($application->admission_date))->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Admission Officer:</td>
                        <td class="value" colspan="3">{{ $application->admission_officer_name }}</td>
                    </tr>
                </table>
                <div style="margin-top: 10px;">
                    <div class="signature-box" style="width: 45%; float: left;">
                        @if($sig = $getPath($application->president_signature_path))
                            <img src="{{ $sig }}" class="signature-image">
                        @endif
                        <p>President's Signature</p>
                    </div>
                    <div class="signature-box" style="width: 45%; float: right;">
                        @if($sig = $getPath($application->secretary_general_signature_path))
                            <img src="{{ $sig }}" class="signature-image">
                        @endif
                        <p>Secretary General's Signature</p>
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>

            <div class="footer">
                Printed on {{ now()->format('d/m/Y H:i') }} - Member {{ $loop->iteration }} of {{ $users->count() }}
            </div>
        </div>
    @endforeach
</body>
</html>
