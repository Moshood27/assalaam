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
use App\Http\Controllers\Api\ProjectProposalController;
use App\Http\Controllers\Api\ShariaBoardController;
use App\Http\Controllers\Api\GuarantorController;
use App\Http\Controllers\Api\ZakatController;
use App\Http\Controllers\Api\SadaqahController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\AdminVendorController;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\MemberRegistrationController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\TakafulController;
use App\Http\Controllers\Api\TransparencyController;
use App\Http\Controllers\Api\MerchantPayController;
use App\Http\Controllers\Api\WasiyyahController;
use App\Http\Controllers\Api\JuniorCooperativeController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\GoldController;
use App\Http\Controllers\Api\SavingsGroupController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\MeetingApologyController;

Route::get('/health', function () {
    return response()
        ->json(['status' => 'ok'])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
});

// Public endpoints (rate limited)
Route::middleware('throttle:api')->group(function () {
    Route::get('/status', [AuthController::class, 'status']);
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
Route::middleware(['auth:sanctum', 'inactivity', 'admin'])->prefix('admin')->group(function () {
    // Admin profile (separated from member profile)
    Route::get('/profile', [AdminProfileController::class, 'show']);
    Route::post('/profile/email', [AdminProfileController::class, 'updateEmail']);
    Route::post('/profile/password', [AdminProfileController::class, 'updatePassword']);

    // Push tokens (admins may also register their device tokens)
    Route::post('/push/token', [ProfileController::class, 'savePushToken']);
    Route::post('/fcm-token', [ProfileController::class, 'savePushToken']);


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

// Protected endpoints (rate limited)
Route::middleware(['auth:sanctum', 'inactivity', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
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
    Route::post('/profile/gender', [ProfileController::class, 'updateGender']);
    Route::post('/profile/email', [ProfileController::class, 'updateEmail']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotificationPreferences']);
    Route::post('/profile/admin-charge-preference', [ProfileController::class, 'updateAdminChargePreference']);
    Route::post('/profile/apply-nursing-mother-grace', [ProfileController::class, 'applyForNursingMotherGrace']);
    Route::post('/profile/verify-migration', [ProfileController::class, 'verifyMigration']);
    Route::post('/profile/report-migration-error', [ProfileController::class, 'reportMigrationError']);
    // Banks directory (dynamic list from provider)
    Route::get('/banks', [ProfileController::class, 'banks']);
    // Bank details: resolve and save (2-step with confirm flag)
    Route::post('/profile/bank-details', [ProfileController::class, 'saveBankDetails']);

    // Security - Transaction PIN
    Route::post('/security/pin/set', [SecurityController::class, 'setPin'])->middleware('throttle:api');
    Route::post('/security/pin/verify', [SecurityController::class, 'verifyPin'])->middleware('throttle:api');
    Route::post('/security/pin/reset/request', [SecurityController::class, 'requestPinReset'])->middleware('throttle:api');
    Route::post('/security/pin/reset/confirm', [SecurityController::class, 'confirmPinReset'])->middleware('throttle:api');
    Route::post('/security/otp/request', [SecurityController::class, 'requestOtp'])->middleware('throttle:api');

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
    Route::post('/wallet/admin-charge/pay', [\App\Http\Controllers\Api\WalletController::class, 'payAdminCharge']);

    // Merchant Pay (QR)
    Route::get('/merchant/pay/qr', [MerchantPayController::class, 'generateQr']);
    Route::post('/merchant/pay/resolve', [MerchantPayController::class, 'resolve']);
    Route::post('/merchant/pay', [MerchantPayController::class, 'pay']);

    // Projects (Pooled Investments)
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::get('/projects/{id}/investments', [ProjectController::class, 'myInvestments']);
    Route::get('/projects/{id}/profits', [ProjectController::class, 'profits']);

    // Savings Groups (Ajo/Esusu/Pardna)
    Route::get('/savings-groups', [SavingsGroupController::class, 'index']);
    Route::get('/savings-groups/invitations', [SavingsGroupController::class, 'invitations']);
    Route::get('/savings-groups/projects', [SavingsGroupController::class, 'projects']);
    Route::get('/savings-groups/discover', [SavingsGroupController::class, 'discover']);
    Route::post('/savings-groups', [SavingsGroupController::class, 'store']);
    Route::get('/savings-groups/{id}', [SavingsGroupController::class, 'show']);
    Route::get('/savings-groups/{id}/contribution-data', [SavingsGroupController::class, 'getContributionData']);
    Route::post('/savings-groups/{id}/join', [SavingsGroupController::class, 'join']);
    Route::post('/savings-groups/{id}/leave', [SavingsGroupController::class, 'leave']);
    Route::post('/savings-groups/{id}/invite', [SavingsGroupController::class, 'invite']);
    Route::post('/savings-groups/{id}/accept-invitation', [SavingsGroupController::class, 'acceptInvitation']);
    Route::post('/savings-groups/{id}/dissolve', [SavingsGroupController::class, 'dissolve']);

    // Passbook
    Route::get('/passbook/{year}', [PassbookController::class, 'getMatrix']);

    // Virtual Account (Paystack DVA)
    Route::get('/virtual-account', [\App\Http\Controllers\Api\VirtualAccountController::class, 'show']);
    Route::post('/virtual-account/assign', [\App\Http\Controllers\Api\VirtualAccountController::class, 'assign']);

    // Attendance
    Route::get('/attendance/current', [AttendanceController::class, 'current']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    Route::post('/meetings/{meeting}/mark-attendance', [AttendanceController::class, 'markAttendance']);
    Route::post('/meetings/{meeting}/apology', [MeetingApologyController::class, 'store']);

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
    Route::get('/products/categories', [\App\Http\Controllers\Api\ProductController::class, 'categories']);

    // Vendor Portal (authenticated vendors)
    Route::get('/vendor/profile', [\App\Http\Controllers\Api\VendorController::class, 'profile']);
    Route::post('/vendor/profile', [\App\Http\Controllers\Api\VendorController::class, 'upsertProfile']);
    Route::get('/vendor/stats', [\App\Http\Controllers\Api\VendorController::class, 'stats']);
    Route::get('/vendor/orders', [\App\Http\Controllers\Api\VendorController::class, 'orders']);
    Route::post('/vendor/orders/{id}/status', [\App\Http\Controllers\Api\VendorController::class, 'updateOrderStatus']);
    Route::get('/vendor/settlements', [\App\Http\Controllers\Api\VendorController::class, 'settlements']);
    Route::post('/vendor/settlements', [\App\Http\Controllers\Api\VendorController::class, 'requestSettlement']);
    Route::get('/vendor/products', [\App\Http\Controllers\Api\VendorProductController::class, 'index']);
    Route::post('/vendor/products', [\App\Http\Controllers\Api\VendorProductController::class, 'store']);
    Route::match(['put','patch'], '/vendor/products/{id}', [\App\Http\Controllers\Api\VendorProductController::class, 'update']);
    Route::delete('/vendor/products/{id}', [\App\Http\Controllers\Api\VendorProductController::class, 'destroy']);

    Route::get('/store/eligibility', [\App\Http\Controllers\Api\StoreOrderController::class, 'eligibility']);
    Route::get('/store/orders', [\App\Http\Controllers\Api\StoreOrderController::class, 'index']);
    Route::get('/store/disputes', [\App\Http\Controllers\Api\StoreOrderController::class, 'myDisputes']);
    Route::get('/store/orders/{id}', [\App\Http\Controllers\Api\StoreOrderController::class, 'show']);
    Route::post('/store/orders', [\App\Http\Controllers\Api\StoreOrderController::class, 'store']);
    Route::post('/store/orders/{id}/installments/pay', [\App\Http\Controllers\Api\StoreOrderController::class, 'payInstallment']);
    Route::post('/store/orders/{id}/dispute', [\App\Http\Controllers\Api\StoreOrderController::class, 'dispute']);

    // Goal-based Savings (Hajj & Umrah)
    Route::get('/goals', [\App\Http\Controllers\Api\SavingsGoalController::class, 'index']);
    Route::post('/goals', [\App\Http\Controllers\Api\SavingsGoalController::class, 'store']);
    Route::get('/goals/{id}', [\App\Http\Controllers\Api\SavingsGoalController::class, 'show']);
    Route::post('/goals/{id}/deposit', [\App\Http\Controllers\Api\SavingsGoalController::class, 'deposit']);
    Route::post('/goals/{id}/book', [\App\Http\Controllers\Api\SavingsGoalController::class, 'book']);

    // Loans (authenticated)
    Route::get('/loans', [LoanController::class, 'index']);
    Route::get('/loans/eligibility', [LoanController::class, 'eligibility']);
    Route::get('/loans/analysis', [LoanController::class, 'analysis']);
    Route::get('/coop-score', [ScoreController::class, 'show']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::post('/loans/{id}/repay', [LoanController::class, 'repay']);
    Route::post('/loans/{id}/agreement', [LoanController::class, 'uploadAgreement']);

    // AGM Voting
    Route::get('/agm/sessions', [AgmController::class, 'sessions']);
    Route::get('/agm/sessions/{id}/candidates', [AgmController::class, 'candidates']);
    Route::post('/agm/sessions/{id}/vote', [AgmController::class, 'vote']);
    Route::get('/agm/sessions/{id}/results', [AgmController::class, 'results']);

    // Project Proposals
    Route::get('/sharia-board', [ShariaBoardController::class, 'index']);
    Route::get('/project-proposals', [ProjectProposalController::class, 'index']);
    Route::post('/project-proposals', [ProjectProposalController::class, 'store']);
    Route::get('/project-proposals/{id}', [ProjectProposalController::class, 'show']);
    Route::post('/project-proposals/{id}/vote', [ProjectProposalController::class, 'vote']);
    Route::post('/project-proposals/{id}/comments', [ProjectProposalController::class, 'storeComment']);

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
    Route::get('/download-passbook', [ExportController::class, 'downloadPassbook'])->name('download-passbook');
    Route::get('/download-passbook-csv', [ExportController::class, 'downloadPassbookCsv'])->name('download-passbook-csv');
    Route::get('/download-statement', [ExportController::class, 'downloadStatement'])->name('download-statement');
    Route::get('/download-loan-schedule/{id}', [ExportController::class, 'downloadLoanSchedule'])->name('download-loan-schedule');
    Route::get('/download-loan-agreement/{id}', [ExportController::class, 'downloadLoanAgreement'])->name('download-loan-agreement');
    Route::get('/download-murabahah-agreement/{id}', [ExportController::class, 'downloadMurabahahAgreement'])->name('download-murabahah-agreement');
    Route::get('/download-dividend/{year}', [ExportController::class, 'downloadDividend'])->name('download-dividend');
    Route::get('/download-appropriation/{year}', [ExportController::class, 'downloadAppropriation'])->name('download-appropriation');
    Route::get('/download-financials/{year}', [ExportController::class, 'downloadFinancials'])->name('download-financials');
    Route::get('/download-cash-flow/{year}', [ExportController::class, 'downloadCashFlow'])->name('download-cash-flow');
    Route::get('/download-charity-report/{year}', [ExportController::class, 'downloadCharityReport'])->name('download-charity-report');
    Route::get('/download-project-roi', [ExportController::class, 'downloadProjectRoiReport'])->name('download-project-roi');
    Route::get('/download-vendor-settlement', [ExportController::class, 'downloadVendorSettlementReport'])->name('download-vendor-settlement');
    Route::get('/download-attendance-report/{year}', [ExportController::class, 'downloadAttendanceReport'])->name('download-attendance-report');
    Route::get('/download-sharia-audit/{year}', [ExportController::class, 'downloadShariaAuditReport'])->name('download-sharia-audit');
    Route::get('/download-loan-aging', [ExportController::class, 'downloadLoanAgingReport'])->name('download-loan-aging');
    // Takaful Pool Report
    Route::get('/download-takaful-report', [ExportController::class, 'downloadTakafulReport'])->name('download-takaful-report');
    // Gold Savings Valuation Report
    Route::get('/download-gold-report', [ExportController::class, 'downloadGoldReport'])->name('download-gold-report');
    // Cooperative Zakat Report
    Route::get('/download-coop-zakat-report', [ExportController::class, 'downloadCoopZakatReport'])->name('download-coop-zakat-report');
    // Audit Trail
    Route::get('/download-audit-trail', [ExportController::class, 'downloadAuditTrail'])->name('download-audit-trail');
    Route::get('/download-order-receipt/{id}', [ExportController::class, 'downloadOrderReceipt'])->name('download-order-receipt');
    Route::get('/download-zakat-report', [ExportController::class, 'downloadZakatReport'])->name('download-zakat-report');
    Route::get('/download-membership-enrolment', [ExportController::class, 'downloadMembershipEnrolment'])->name('download-membership-enrolment');
    Route::get('/download-imam-attestation', [ExportController::class, 'downloadImamAttestation'])->name('download-imam-attestation');
    Route::get('/download-loan-analysis', [ExportController::class, 'downloadLoanAnalysis'])->name('download-loan-analysis');
    Route::get('/download-zakat-portfolio', [ExportController::class, 'downloadMemberZakatPortfolio'])->name('download-zakat-portfolio');
    Route::get('/download-project-distribution/{id}', [ExportController::class, 'downloadProjectDistribution'])->name('download-project-distribution');
    Route::get('/download-savings-ledger/{userId?}', [ExportController::class, 'downloadMemberSavingsLedger'])->name('download-savings-ledger');

    // Zakat
    Route::get('/zakat/estimate', [ZakatController::class, 'estimate']);
    Route::get('/zakat/history', [ZakatController::class, 'history']);
    Route::post('/zakat/pay', [ZakatController::class, 'pay']);
    Route::post('/zakat/pay-fitr', [ZakatController::class, 'payFitr']);

    // Sadaqah Jariyah Crowdfunding
    Route::get('/sadaqah/projects', [SadaqahController::class, 'index']);
    Route::get('/sadaqah/my-contributions', [SadaqahController::class, 'myContributions']);
    Route::get('/sadaqah/projects/{id}', [SadaqahController::class, 'show']);
    Route::post('/sadaqah/projects/{id}/contribute', [SadaqahController::class, 'contribute']);

    // Wasiyyah (Beneficiaries)
    Route::get('/wasiyyah', [WasiyyahController::class, 'index']);
    Route::post('/wasiyyah', [WasiyyahController::class, 'store']);
    Route::patch('/wasiyyah/{id}', [WasiyyahController::class, 'update']);
    Route::delete('/wasiyyah/{id}', [WasiyyahController::class, 'destroy']);

    // Junior Cooperative (Children's Savings)
    Route::get('/junior-cooperative', [JuniorCooperativeController::class, 'index']);
    Route::post('/junior-cooperative', [JuniorCooperativeController::class, 'store']);
    Route::patch('/junior-cooperative/{id}', [JuniorCooperativeController::class, 'update']);
    Route::post('/junior-cooperative/{id}/deposit', [JuniorCooperativeController::class, 'deposit']);
    Route::post('/junior-cooperative/{id}/withdraw', [JuniorCooperativeController::class, 'withdraw']);
    Route::get('/junior-cooperative/{id}/history', [JuniorCooperativeController::class, 'history']);

    // Gold-Backed Savings (Inflation Hedge)
    Route::get('/gold/price', [GoldController::class, 'getPrice']);
    Route::post('/gold/buy', [GoldController::class, 'buy']);
    Route::post('/gold/sell', [GoldController::class, 'sell']);
    Route::get('/gold/history', [GoldController::class, 'history']);
    Route::get('/gold/export', [GoldController::class, 'export']);

    // In-App Notifications (Inbox)
    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'readOne']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll']);


    // Enhanced Islamic Cooperative Chat System
    Route::prefix('chat')->group(function () {
        Route::get('/rooms', [\App\Http\Controllers\Api\ChatController::class, 'index']);
        Route::post('/rooms', [\App\Http\Controllers\Api\ChatController::class, 'storeRoom']);
        Route::get('/rooms/{room}', [\App\Http\Controllers\Api\ChatController::class, 'show']);
        Route::post('/rooms/{room}/join', [\App\Http\Controllers\Api\ChatController::class, 'joinRoom']);
        Route::post('/rooms/{room}/messages', [\App\Http\Controllers\Api\ChatController::class, 'store']);
        Route::patch('/messages/{message}', [\App\Http\Controllers\Api\ChatController::class, 'update']);
        Route::delete('/messages/{message}', [\App\Http\Controllers\Api\ChatController::class, 'destroy']);
        Route::post('/messages/{message}/respond', [\App\Http\Controllers\Api\ChatController::class, 'respond']);
        Route::post('/rooms/{room}/read', [\App\Http\Controllers\Api\ChatController::class, 'markRead']);
        Route::post('/rooms/{room}/typing', [\App\Http\Controllers\Api\ChatController::class, 'typing']);
        Route::get('/search', [\App\Http\Controllers\Api\ChatController::class, 'search']);
        Route::get('/greetings', [\App\Http\Controllers\Api\ChatController::class, 'greetings']);
        Route::get('/canned-responses', [\App\Http\Controllers\Api\ChatController::class, 'cannedResponses']);
        Route::get('/status', [\App\Http\Controllers\Api\ChatController::class, 'status']);
        Route::get('/support-room', [\App\Http\Controllers\Api\ChatController::class, 'getOrCreateSupportRoom']);
        Route::post('/private/{user}', [\App\Http\Controllers\Api\ChatController::class, 'createPrivateRoom']);
        Route::post('/rooms/{room}/assign', [\App\Http\Controllers\Api\ChatController::class, 'assignStaff']);
        Route::post('/broadcast', [\App\Http\Controllers\Api\ChatController::class, 'broadcast']);
        Route::get('/chat-analytics', [\App\Http\Controllers\Api\ChatController::class, 'analytics']);
        Route::post('/users/{user}/ban', [\App\Http\Controllers\Api\ChatController::class, 'ban']);
        Route::post('/users/{user}/unban', [\App\Http\Controllers\Api\ChatController::class, 'unban']);
    });
});

// Existing Qard Hasan prototype endpoints (kept)
Route::prefix('qard-hasan')->group(function () {
    Route::get('/', [QardHasanController::class, 'index']);
    Route::post('/', [QardHasanController::class, 'store']);
    Route::post('/{id}/repay', [QardHasanController::class, 'repay']);
});



// Admin reports endpoints
Route::middleware(['auth:sanctum', 'inactivity', 'admin'])->prefix('admin/reports')->group(function () {
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
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/import')->group(function () {
    Route::post('/members', [ImportController::class, 'importMembers']);
    Route::post('/schemes', [ImportController::class, 'importSchemes']);
    Route::post('/loans', [ImportController::class, 'importLoans']);
});


// Admin VTU endpoints
Route::middleware(['auth:sanctum', 'inactivity', 'admin'])->prefix('admin/vtu')->group(function () {
    Route::get('/transactions', [AdminUtilityController::class, 'transactions']);
});

// Admin products management (images & approval)
Route::middleware(['auth:sanctum', 'inactivity', 'admin'])->prefix('admin/vendors')->group(function () {
    Route::get('/', [AdminVendorController::class, 'index']);
    Route::post('/{id}/approve', [AdminVendorController::class, 'approve']);
    Route::post('/{id}/reject', [AdminVendorController::class, 'reject']);
    Route::post('/{id}/toggle-active', [AdminVendorController::class, 'toggleActive']);
    Route::get('/settlements', [AdminVendorController::class, 'settlements']);
    Route::post('/settlements/{id}/approve', [AdminVendorController::class, 'approveSettlement']);
    Route::post('/settlements/{id}/reject', [AdminVendorController::class, 'rejectSettlement']);
});

Route::middleware(['auth:sanctum', 'inactivity', 'admin'])->prefix('admin/products')->group(function () {
    Route::get('/', [AdminProductController::class, 'index']);
    Route::post('/{id}/image', [AdminProductController::class, 'uploadImage']);
    Route::delete('/{id}/image', [AdminProductController::class, 'deleteImage']);
    Route::post('/{id}/approve', [AdminProductController::class, 'approve']);
    Route::post('/{id}/reject', [AdminProductController::class, 'reject']);
});
