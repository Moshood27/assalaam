<!DOCTYPE html>
<html>
<head>
    <title>Loan Agreement Verified</title>
</head>
<body>
    <h3>Great news, {{ $member->name ?? 'Member' }}!</h3>
    <p>Your signed loan agreement for <strong>{{ $loan->qard_id_string }}</strong> has been verified by our team.</p>

    <p>Your loan is now being processed for final disbursement. You will be notified once the funds are credited to your wallet.</p>

    <p><strong>Next Steps:</strong></p>
    <ul>
        <li>Keep an eye on your wallet balance.</li>
        <li>Review your repayment schedule in the app.</li>
    </ul>

    <p>Thank you for your cooperation!</p>
</body>
</html>
