<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QardHasanController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PassbookController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\AdminReportsController;
use App\Http\Controllers\Api\AdminTakafulController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\AdminUtilityController;
use App\Http\Controllers\Api\AdminProfileController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AgmController;
use App\Http\Controllers\Api\GuarantorController;
use App\Http\Controllers\Api\ZakatController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\MemberRegistrationController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\TakafulController;
use App\Http\Controllers\Api\TransparencyController;
use App\Http\Controllers\Api\MerchantPayController;

Route::get('/health', function () {
    return response()
        ->json(['status' => 'ok'])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
});

// Public endpoints (rate limited)
Route::middleware('throttle:api')->group(function () {
    Route::get('/branches', [AuthController::class, 'branches']);

    // Member self-registration (multi-step) endpoints
    Route::post('/register/start', [MemberRegistrationController::class, 'start']);
    Route::post('/register/upload', [MemberRegistrationController::class, 'upload']);
    Route::post('/register/send-otps', [MemberRegistrationController::class, 'sendOtps']);
    Route::post('/register/verify-email', [MemberRegistrationController::class, 'verifyEmail']);
    Route::post('/register/verify-sms', [MemberRegistrationController::class, 'verifySms']);
    Route::get('/register/status', [MemberRegistrationController::class, 'status']);
    Route::post('/register/finalize', [MemberRegistrationController::class, 'finalize']);
});
// Login endpoints with stricter throttle
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
// Member password reset (email or SMS code)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');

// Admin auth endpoints (Vue-based)
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register'])->middleware('throttle:login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/forgot-password', [AdminAuthController::class, 'forgotPassword'])->middleware('throttle:login');
});

// Admin: profile & push token endpoints (protected)
Route::middleware(['auth:sanctum', 'inactivity'])->prefix('admin')->group(function () {
    // Admin profile (separated from member profile)
    Route::get('/profile', [AdminProfileController::class, 'show']);
    Route::post('/profile/email', [AdminProfileController::class, 'updateEmail']);
    Route::post('/profile/password', [AdminProfileController::class, 'updatePassword']);

    // Push tokens (admins may also register their device tokens)
    Route::post('/push/token', [ProfileController::class, 'savePushToken']);
    Route::post('/fcm-token', [ProfileController::class, 'savePushToken']);

    // In-App Support Chat (admin -> member)
    Route::post('/support/{user}/message', [\App\Http\Controllers\Api\SupportChatAdminController::class, 'sendToUser']);

    // Takaful (Mutual Protection Pool) admin endpoints
    Route::get('/takaful/summary', [AdminTakafulController::class, 'summary']);
    Route::get('/takaful/ledger', [AdminTakafulController::class, 'ledger']);
    // Exports
    Route::get('/takaful/export/ledger.csv', [AdminTakafulController::class, 'exportLedgerCsv']);
    Route::get('/takaful/export/ledger.pdf', [AdminTakafulController::class, 'exportLedgerPdf']);
    Route::get('/takaful/export/summary.csv', [AdminTakafulController::class, 'exportSummaryCsv']);
    Route::get('/takaful/export/summary.pdf', [AdminTakafulController::class, 'exportSummaryPdf']);
    // Manual batch charge and policy actions
    Route::post('/takaful/charge', [AdminTakafulController::class, 'charge']);
    Route::post('/takaful/mark-deceased', [AdminTakafulController::class, 'markDeceased']);
    Route::post('/takaful/mark-major-loss', [AdminTakafulController::class, 'markMajorLoss']);
});

// Webhook (public, signature-verified inside controller)
Route::post('/webhooks/paystack', [WebhookController::class, 'handlePaystack']);
Route::post('/webhooks/flutterwave', [WebhookController::class, 'handleFlutterwave']);

// VTpass webhook (public) - accept GET (VTpass URL verification) and POST (real callbacks)
Route::match(['get', 'post'], '/vtu/webhook', [\App\Http\Controllers\Api\UtilityController::class, 'handleWebhook']);
// Alias for ClubKonnect/Nellobytes callback URL
Route::match(['get', 'post'], '/vtu/callback', [\App\Http\Controllers\Api\UtilityController::class, 'handleWebhook']);

// Protected endpoints
Route::middleware(['auth:sanctum', 'inactivity'])->group(function () {
    // Takaful (member-facing)
    Route::get('/takaful/summary', [TakafulController::class, 'summary']);
    Route::get('/takaful/contributions', [TakafulController::class, 'contributions']);
    Route::post('/takaful/pay-now', [TakafulController::class, 'payNow']);

    // Transparency (Portfolio / Proof of Reserve)
    Route::get('/transparency', [TransparencyController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Member profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/passport', [ProfileController::class, 'uploadPassport']);
    Route::post('/profile/email', [ProfileController::class, 'updateEmail']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
    // Banks directory (dynamic list from provider)
    Route::get('/banks', [ProfileController::class, 'banks']);
    // Bank details: resolve and save (2-step with confirm flag)
    Route::post('/profile/bank-details', [ProfileController::class, 'saveBankDetails']);

    // Security - Transaction PIN
    Route::post('/security/pin/set', [SecurityController::class, 'setPin'])->middleware('throttle:api');
    Route::post('/security/pin/verify', [SecurityController::class, 'verifyPin'])->middleware('throttle:api');
    Route::post('/security/pin/reset/request', [SecurityController::class, 'requestPinReset'])->middleware('throttle:api');
    Route::post('/security/pin/reset/confirm', [SecurityController::class, 'confirmPinReset'])->middleware('throttle:api');

    // Push token registration
    Route::post('/push/token', [ProfileController::class, 'savePushToken']);
    // Alias for mobile apps saving FCM token
    Route::post('/user/fcm-token', [ProfileController::class, 'savePushToken']);

    // Payments
    Route::get('/schemes', [PaymentController::class, 'getSchemes']);
    Route::post('/initiate-payment', [PaymentController::class, 'initiate']);
    Route::post('/verify-payment', [PaymentController::class, 'verify']);

    // Wallet
    Route::get('/wallet', [\App\Http\Controllers\Api\WalletController::class, 'getWallet']);
    Route::get('/wallet/transactions', [\App\Http\Controllers\Api\WalletController::class, 'transactions']);
    Route::get('/wallet/transactions/{id}/receipt', [ExportController::class, 'downloadWalletReceipt']);
    Route::post('/wallet/topup/initiate', [\App\Http\Controllers\Api\WalletController::class, 'initiateTopup']);
    Route::post('/wallet/allocate', [\App\Http\Controllers\Api\WalletController::class, 'allocateToSchemes']);
    Route::get('/wallet/transfer/resolve', [\App\Http\Controllers\Api\WalletController::class, 'resolveRecipient']);
    Route::post('/wallet/transfer', [\App\Http\Controllers\Api\WalletController::class, 'transfer']);
    Route::post('/wallet/withdraw', [\App\Http\Controllers\Api\WalletController::class, 'withdraw'])->middleware('throttle:5,1');
    Route::get('/wallet/withdrawals', [\App\Http\Controllers\Api\WalletController::class, 'withdrawals']);
    Route::post('/wallet/withdrawals/{id}/cancel', [\App\Http\Controllers\Api\WalletController::class, 'cancelWithdrawal'])->middleware('throttle:5,1');

    // Merchant Pay (QR)
    Route::get('/merchant/pay/qr', [MerchantPayController::class, 'generateQr']);
    Route::post('/merchant/pay/resolve', [MerchantPayController::class, 'resolve']);
    Route::post('/merchant/pay', [MerchantPayController::class, 'pay']);

    // Projects (Pooled Investments)
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/projects/{id}/investments', [ProjectController::class, 'myInvestments']);
    Route::get('/projects/{id}/profits', [ProjectController::class, 'profits']);

    // Passbook
    Route::get('/passbook/{year}', [PassbookController::class, 'getMatrix']);

    // Virtual Account (Paystack DVA)
    Route::get('/virtual-account', [\App\Http\Controllers\Api\VirtualAccountController::class, 'show']);
    Route::post('/virtual-account/assign', [\App\Http\Controllers\Api\VirtualAccountController::class, 'assign']);

    // VTU (Airtime, Data, Electricity, Cable TV)
    Route::get('/vtu/transactions', [\App\Http\Controllers\Api\UtilityController::class, 'transactions']);
    Route::get('/vtu/data/bundles', [\App\Http\Controllers\Api\UtilityController::class, 'dataBundles']);
    Route::get('/vtu/tv/bundles', [\App\Http\Controllers\Api\UtilityController::class, 'tvBundles']);
    Route::post('/vtu/airtime', [\App\Http\Controllers\Api\UtilityController::class, 'purchaseAirtime']);
    Route::post('/vtu/data', [\App\Http\Controllers\Api\UtilityController::class, 'purchaseData']);
    Route::post('/vtu/electricity', [\App\Http\Controllers\Api\UtilityController::class, 'purchaseElectricity']);
    Route::post('/vtu/cable', [\App\Http\Controllers\Api\UtilityController::class, 'purchaseCable']);
    Route::post('/vtu/verify-merchant', [\App\Http\Controllers\Api\UtilityController::class, 'verifyMerchant']);
    // Manual status check by OrderID/RequestID (member-initiated requery)
    Route::get('/vtu/status/{orderId}', [\App\Http\Controllers\Api\UtilityController::class, 'checkStatus']);
    Route::post('/vtu/cancel/{orderId}', [\App\Http\Controllers\Api\UtilityController::class, 'cancelTransaction']);

    // Coop Store (member-facing)
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/store/orders', [\App\Http\Controllers\Api\StoreOrderController::class, 'index']);
    Route::get('/store/orders/{id}', [\App\Http\Controllers\Api\StoreOrderController::class, 'show']);
    Route::post('/store/orders', [\App\Http\Controllers\Api\StoreOrderController::class, 'store']);
    Route::post('/store/orders/{id}/installments/pay', [\App\Http\Controllers\Api\StoreOrderController::class, 'payInstallment']);

    // Goal-based Savings (Hajj & Umrah)
    Route::get('/goals', [\App\Http\Controllers\Api\SavingsGoalController::class, 'index']);
    Route::post('/goals', [\App\Http\Controllers\Api\SavingsGoalController::class, 'store']);
    Route::get('/goals/{id}', [\App\Http\Controllers\Api\SavingsGoalController::class, 'show']);
    Route::post('/goals/{id}/deposit', [\App\Http\Controllers\Api\SavingsGoalController::class, 'deposit']);
    Route::post('/goals/{id}/book', [\App\Http\Controllers\Api\SavingsGoalController::class, 'book']);

    // Loans (authenticated)
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/eligibility', [LoanController::class, 'eligibility']);
    Route::get('/coop-score', [\App\Http\Controllers\Api\ScoreController::class, 'show']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::post('/loans/{id}/repay', [LoanController::class, 'repay']);

    // AGM Voting
    Route::get('/agm/sessions', [AgmController::class, 'sessions']);
    Route::get('/agm/sessions/{id}/candidates', [AgmController::class, 'candidates']);
    Route::post('/agm/sessions/{id}/vote', [AgmController::class, 'vote']);
    Route::get('/agm/sessions/{id}/results', [AgmController::class, 'results']);

    // Guarantor digital approvals
    Route::get('/guarantor/requests', [GuarantorController::class, 'listRequests']);
    Route::post('/guarantor/requests/{id}/accept', [GuarantorController::class, 'accept']);
    Route::post('/guarantor/requests/{id}/decline', [GuarantorController::class, 'decline']);
    // Borrower actions
    Route::post('/guarantor/loans/{id}/nudge', [GuarantorController::class, 'nudge']);
    Route::post('/guarantor/loans/{id}/escalate', [GuarantorController::class, 'escalate']);

    // Member reports
    Route::get('/reports/contribution-mix', [ReportsController::class, 'contributionMix']);
    Route::get('/reports/loans/{id}/schedule', [ReportsController::class, 'loanSchedule']);
    Route::get('/reports/dividend/{year}', [ReportsController::class, 'dividend']);

    // PDF export
    Route::get('/download-passbook', [ExportController::class, 'downloadPassbook']);
    Route::get('/download-loan-schedule/{id}', [ExportController::class, 'downloadLoanSchedule']);
    Route::get('/download-dividend/{year}', [ExportController::class, 'downloadDividend']);
    Route::get('/download-appropriation/{year}', [ExportController::class, 'downloadAppropriation']);
    Route::get('/download-financials/{year}', [ExportController::class, 'downloadFinancials']);

    // Zakat
    Route::get('/zakat/estimate', [ZakatController::class, 'estimate']);
    Route::post('/zakat/pay', [ZakatController::class, 'pay']);

    // In-App Notifications (Inbox)
    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'readOne']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll']);

    // In-App Support Chat (member)
    Route::get('/support/messages', [\App\Http\Controllers\Api\SupportChatController::class, 'index']);
    Route::post('/support/messages', [\App\Http\Controllers\Api\SupportChatController::class, 'store']);
    Route::post('/support/read', [\App\Http\Controllers\Api\SupportChatController::class, 'markRead']);
});

// Existing Qard Hasan prototype endpoints (kept)
Route::prefix('qard-hasan')->group(function () {
    Route::get('/', [QardHasanController::class, 'index']);
    Route::post('/', [QardHasanController::class, 'store']);
    Route::post('/{id}/repay', [QardHasanController::class, 'repay']);
});



// Admin reports endpoints
Route::middleware(['auth:sanctum', 'inactivity'])->prefix('admin/reports')->group(function () {
    Route::get('/branch-performance', [AdminReportsController::class, 'branchPerformance']);
    Route::get('/scheme-popularity', [AdminReportsController::class, 'schemePopularity']);
    Route::get('/delinquency', [AdminReportsController::class, 'delinquency']);
    Route::get('/reconciliation', [AdminReportsController::class, 'reconciliation']);
    Route::get('/total-liquidity', [AdminReportsController::class, 'totalLiquidity']);
    Route::get('/audit-trail', [AdminReportsController::class, 'auditTrail']);
    Route::get('/user-growth', [AdminReportsController::class, 'userGrowth']);
    Route::get('/system-health', [AdminReportsController::class, 'systemHealth']);
});

// Admin import endpoints
Route::middleware('auth:sanctum')->prefix('admin/import')->group(function () {
    Route::post('/members', [ImportController::class, 'importMembers']);
    Route::post('/schemes', [ImportController::class, 'importSchemes']);
    Route::post('/loans', [ImportController::class, 'importLoans']);
});


// Admin VTU endpoints
Route::middleware(['auth:sanctum', 'inactivity'])->prefix('admin/vtu')->group(function () {
    Route::get('/transactions', [AdminUtilityController::class, 'transactions']);
});

// Admin products management (images)
Route::middleware(['auth:sanctum', 'inactivity'])->prefix('admin/products')->group(function () {
    Route::get('/', [AdminProductController::class, 'index']);
    Route::post('/{id}/image', [AdminProductController::class, 'uploadImage']);
    Route::delete('/{id}/image', [AdminProductController::class, 'deleteImage']);
});
