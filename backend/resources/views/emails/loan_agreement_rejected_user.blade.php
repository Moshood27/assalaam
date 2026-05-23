<!DOCTYPE html>
<html>
<head>
    <title>Loan Agreement Rejected</title>
</head>
<body>
    <h3>Assalāmu ‘alaykum, {{ $member->name ?? 'Member' }}</h3>
    <p>We pray this message finds you in good health and īmān.</p>
    <p>The signed loan agreement you uploaded for <strong>{{ $loan->qard_id_string }}</strong> was not accepted for the following reason:</p>

    <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 10px; margin: 15px 0; color: #991b1b;">
        <strong>Reason:</strong> {{ $reason }}
    </div>

    <p>Please log in to your dashboard to re-upload a clear, correctly signed copy of the agreement.</p>

    <p><strong>Steps to resolve:</strong></p>
    <ol>
        <li>Go to the <strong>Loans</strong> section.</li>
        <li>Download the agreement template again (if needed).</li>
        <li>Ensure all pages are signed and clearly visible.</li>
        <li>Upload the new file.</li>
    </ol>

    <p>Once you re-upload, our admin will review it again for final verification and disbursement.</p>

    <p>Thank you.</p>
</body>
</html>
