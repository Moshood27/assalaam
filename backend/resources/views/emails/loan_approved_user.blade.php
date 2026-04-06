<!DOCTYPE html>
<html>
<head>
    <title>Loan Approved</title>
</head>
<body>
    <h3>Hello, {{ $member->name ?? 'Member' }}!</h3>
    <p>We are pleased to inform you that your loan request <strong>{{ $loan->qard_id_string }}</strong> has been approved.</p>

    <p>Before we can disburse the funds (₦{{ number_format($loan->principal_amount, 2) }}), we require you to sign the loan agreement.</p>

    <p><strong>Instructions:</strong></p>
    <ol>
        <li>Log in to your dashboard.</li>
        <li>Go to the <strong>Loans</strong> section.</li>
        <li>Download the <strong>Agreement Template</strong>.</li>
        <li>Print, sign, and scan (or take a clear photo) of the signed document.</li>
        <li>Upload the signed copy back on the dashboard.</li>
    </ol>

    <p>Once you upload the signed agreement, our admin will verify it and disburse the funds to your wallet.</p>

    <p>Thank you for using our cooperative service!</p>
</body>
</html>
