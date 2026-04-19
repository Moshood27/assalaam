<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Membership Enrolment Form</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 120px; border-bottom: 2px solid #047857; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #047857; text-transform: uppercase; font-size: 20px; }
        .header p { margin: 5px 0; font-weight: bold; }
        .photo-box { position: absolute; top: 10px; right: 10px; width: 120px; height: 140px; border: 1px solid #ccc; text-align: center; line-height: 140px; font-size: 10px; color: #999; z-index: 100; }
        .section { margin-bottom: 20px; position: relative; }
        .section-title { background: #f3f4f6; padding: 5px 10px; font-weight: bold; color: #047857; text-transform: uppercase; margin-bottom: 10px; border-left: 4px solid #047857; }
        .label { font-weight: bold; width: 180px; }
        .value { border-bottom: 1px dotted #ccc; min-height: 1.2em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table td { padding: 5px; vertical-align: top; }
        .signature-box { margin-top: 10px; text-align: center; }
        .signature-image { max-width: 150px; max-height: 60px; border-bottom: 1px solid #000; display: block; margin: 0 auto 5px; }
        .footer { margin-top: 30px; font-size: 10px; text-align: center; color: #666; border-top: 1px solid #eee; padding-top: 10px; }
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
    <div class="header">
        <h1>{{ config('app.name') }} COOPERATIVE SOCIETY</h1>
        <p>MEMBERSHIP ENROLMENT FORM</p>
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
                <td class="label">Full Name:</td>
                <td class="value" colspan="3">{{ $application->full_name }}</td>
            </tr>
            <tr>
                <td class="label">Sex (Gender):</td>
                <td class="value">{{ ucfirst((string) $application->gender) }}</td>
                <td class="label">Date of Birth:</td>
                <td class="value">{{ $application->dob ? $application->dob->format('d/m/Y') : 'N/A' }}</td>
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
            <div class="signature-box" style="float: right; width: 200px;">
                <img src="{{ $sig }}" class="signature-image">
                <p>Guarantor's Signature</p>
            </div>
        @endif
        <div style="clear: both;"></div>
    </div>

    <div class="page-break"></div>

    <div class="header">
        <h1>ATTESTATION OF IMAM/AMIR</h1>
    </div>

    <div class="section">
        <div class="section-title">6. Religious Information & Imam's Attestation</div>
        <table>
            <tr>
                <td class="label">Religious Society Name:</td>
                <td class="value" colspan="3">{{ $application->religious_society_name }}</td>
            </tr>
            <tr>
                <td class="label">Imam/Amir Name:</td>
                <td class="value">{{ $application->imam_name }}</td>
                <td class="label">Phone Number:</td>
                <td class="value">{{ $application->imam_phone }}</td>
            </tr>
            <tr>
                <td class="label">Mosque Address:</td>
                <td class="value" colspan="3">{{ $application->mosque_address }}</td>
            </tr>
            <tr>
                <td class="label">Duration of Membership:</td>
                <td class="value" colspan="3">{{ $application->duration_of_jamma_membership }}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">
            I, the undersigned Imam/Amir, hereby attest that the applicant is a known member of our Jamma and of good character.
        </p>

        @if($application->imam_approval_status)
            <div class="signature-box" style="float: right; width: 200px; margin-top: 20px;">
                <p style="font-size: 14px; font-weight: bold; color: green;">APPROVED</p>
                @if($sig = $getPath($application->imam_signature_path))
                    <img src="{{ $sig }}" class="signature-image">
                @endif
                <p>Date: {{ $application->imam_approved_at ? $application->imam_approved_at->format('d/m/Y') : '' }}</p>
                <p>Imam's Signature/Stamp</p>
            </div>
        @endif
        <div style="clear: both;"></div>
    </div>

    @if($application->gender === 'female' || $application->spouse_father_name)
    <div class="section" style="margin-top: 40px;">
        <div class="section-title">7. Wali/Spouse Details (For Female Members)</div>
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
            <div class="signature-box" style="float: right; width: 200px; margin-top: 10px;">
                <img src="{{ $sig }}" class="signature-image">
                <p>Father/Spouse Consent Signature</p>
            </div>
        @endif
        <div style="clear: both;"></div>
    </div>
    @endif

    <div class="section" style="margin-top: 50px; border-top: 2px solid #333; padding-top: 10px;">
        <div class="section-title">8. Official Use Only (Admin Workflow)</div>
        <table>
            <tr>
                <td class="label">Admission Form No:</td>
                <td class="value">{{ $application->admission_form_number }}</td>
                <td class="label">Admission Date:</td>
                <td class="value">{{ $application->admission_date ? $application->admission_date->format('d/m/Y') : '' }}</td>
            </tr>
            <tr>
                <td class="label">Admission Officer:</td>
                <td class="value" colspan="3">{{ $application->admission_officer_name }}</td>
            </tr>
            <tr>
                <td class="label">Recommendation/Comment:</td>
                <td class="value" colspan="3">{{ $application->officer_recommendation }}</td>
            </tr>
            <tr>
                <td class="label">Final Approval Status:</td>
                <td class="value" colspan="3" style="font-weight: bold; text-transform: uppercase;">{{ $application->approval_status }}</td>
            </tr>
        </table>

        <div style="margin-top: 30px;">
            <table style="border: none; margin-top: 30px;">
                <tr>
                    <td style="width: 45%; border: none;">
                        <div class="signature-box">
                            @if($sig = $getPath($application->president_signature_path))
                                <img src="{{ $sig }}" class="signature-image">
                            @else
                                <div style="height: 60px; border-bottom: 1px solid #000;"></div>
                            @endif
                            <p>President's Signature/Date</p>
                        </div>
                    </td>
                    <td style="width: 10%; border: none;"></td>
                    <td style="width: 45%; border: none;">
                        <div class="signature-box">
                            @if($sig = $getPath($application->secretary_general_signature_path))
                                <img src="{{ $sig }}" class="signature-image">
                            @else
                                <div style="height: 60px; border-bottom: 1px solid #000;"></div>
                            @endif
                            <p>Secretary General's Signature/Date</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }} Cooperative Society. Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
