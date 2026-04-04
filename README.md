
Based on the video you provided, you are building a Cooperative Management System (Fintech). The key features shown are a Branch-based Login, a Dashboard with balance cards, a Multi-scheme Contribution system (Shares, Savings, Building, etc.), and a Financial Passbook.
Here is how to structure your Laravel + Vue project to match that specific app.
1. Database Schema (The "Cooperative" Logic)
   To achieve the multi-scheme structure shown in the video, your database needs to be flexible.
   code
   PHP
   // Run: ./vendor/bin/sail artisan make:migration create_coop_tables
   public function up()
   {
   // Branches
   Schema::create('branches', function (Blueprint $table) {
   $table->id();
   $table->string('name'); // e.g., Ayetoro Osogbo Branch
   });

   // Schemes (Savings types)
   Schema::create('schemes', function (Blueprint $table) {
   $table->id();
   $table->string('name'); // e.g., Shares, Savings, Children Savings, Building Project
   $table->decimal('min_amount', 15, 2)->default(0);
   });

   // Users (Members)
   Schema::create('users', function (Blueprint $table) {
   $table->id();
   $table->foreignId('branch_id')->constrained();
   $table->string('membership_number')->unique(); // 052286
   $table->string('password');
   $table->timestamps();
   });

   // Contributions (Transactions)
   Schema::create('contributions', function (Blueprint $table) {
   $table->id();
   $table->foreignId('user_id')->constrained();
   $table->foreignId('scheme_id')->constrained();
   $table->decimal('amount', 15, 2);
   $table->string('reference');
   $table->enum('status', ['pending', 'success', 'failed']);
   $table->timestamps();
   });
   }
2. Backend: The "Financial Sheet" (Passbook) Logic
   In the video, there is a complex table showing contributions per month. You can generate this data in Laravel:
   code
   PHP
   // app/Http/Controllers/DashboardController.php
   public function getFinancialSheet(Request $request) {
   $user = $request->user();
   $year = $request->get('year', date('Y'));

   $data = Scheme::with(['contributions' => function($query) use ($user, $year) {
   $query->where('user_id', $user->id)
   ->whereYear('created_at', $year)
   ->where('status', 'success');
   }])->get();

   // Map the data into a Month-by-Month grid for the Vue table
   return response()->json($data);
   }
3. Frontend: The Member Login (Vue)
   To match the video's login screen exactly, update your Login.vue:
   code
   Vue
   <template>
  <div class="p-6 bg-white min-h-screen flex flex-col items-center">
    <img src="/logo.png" class="w-24 mb-4" />
    <h1 class="text-2xl font-bold mb-1">Member Login</h1>
    <p class="text-gray-500 text-sm mb-8 text-center">Please select your branch and enter your credentials</p>

    <div class="w-full space-y-4">
      <!-- Branch Selection -->
      <div>
        <label class="font-bold text-blue-900">Select Branch</label>
        <select v-model="form.branch_id" class="w-full p-4 bg-gray-100 rounded-xl border-none mt-2">
          <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>

      <!-- Membership Number -->
      <div>
        <label class="font-bold text-blue-900">Membership Number</label>
        <input v-model="form.membership_number" type="text" placeholder="052286" class="w-full p-4 bg-gray-100 rounded-xl border-none mt-2" />
      </div>

      <!-- Password -->
      <div>
        <label class="font-bold text-blue-900">Password</label>
        <input v-model="form.password" type="password" class="w-full p-4 bg-gray-100 rounded-xl border-none mt-2" />
      </div>

      <button @click="handleLogin" class="w-full bg-teal-600 text-white py-4 rounded-xl font-bold text-lg">
        Login
      </button>
    </div>
  </div>
</template>
4. Frontend: The Contribution Modal
The video shows a "Make Payment" screen where you add multiple schemes before paying.
code
JavaScript
// Inside your MakePayment.vue
const cart = ref([]); // Store multiple scheme payments here
const selectedScheme = ref(null);
const amount = ref(0);

const addToPaymentList = () => {
cart.value.push({
scheme_id: selectedScheme.value.id,
name: selectedScheme.value.name,
amount: amount.value
});
};

const totalToPay = computed(() => cart.value.reduce((sum, item) => sum + item.amount, 0));

const processPayment = async () => {
const response = await axios.post('/api/initiate-payment', { items: cart.value });
// Open Paystack Checkout
window.location.href = response.data.url;
};
5. Finalizing for Mobile (Capacitor)
   To get the bottom navigation bar and mobile feel seen in the video:
   Use Vue Router for the bottom tabs:
   Home (/dashboard)
   Passbook (/passbook)
   Transactions (/history)
   Settings (/profile)
   Add Native Plugins:
   To handle the "Copy to Clipboard" feature seen in the video (for the account number), install the Capacitor Clipboard plugin:
   code
   Bash
   npm install @capacitor/clipboard
   Deploy to Android:
   code
   Bash
   npm run build
   npx cap copy
   npx cap open android
   Your Next Step:
   To make this work like the video, you need a Payment Webhook.
   Member pays via the App.
   Paystack sends a signal to your-api.com/api/paystack/webhook.
   Laravel receives the signal, finds the user, and adds the money to the correct Scheme (Savings, Building, etc.).
   Do you want the code for the Laravel Webhook to handle the successful payments?
   Model 5:36 PM
   ThinkingThoughts
   Expand to view model thoughts

chevron_right
Model 12:58 PM
ThinkingThoughts
Expand to view model thoughts

chevron_right
To implement the Branch-Based Login exactly as seen in the video, we need to handle three things: the database structure, the Laravel API logic, and the Vue.js UI with the specific mobile-friendly fields.
1. Backend: Database Setup
   You need a branches table and a users table that links to it.
   Migration for Branches:
   code
   PHP
   Schema::create('branches', function (Blueprint $table) {
   $table->id();
   $table->string('name'); // e.g., "Ayetoro Osogbo Branch"
   $table->timestamps();
   });
   Migration for Users (Update):
   code
   PHP
   Schema::table('users', function (Blueprint $table) {
   $table->foreignId('branch_id')->constrained();
   $table->string('membership_number')->unique(); // e.g., "052286"
   });
2. Backend: Login Controller
   Create app/Http/Controllers/Api/AuthController.php. This logic ensures the user belongs to the selected branch.
   code
   PHP
   namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends \App\Http\Controllers\Controller
{
// 1. Get list of branches for the dropdown
public function branches() {
return response()->json(Branch::all());
}

    // 2. Handle Login
    public function login(Request $request) {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'membership_number' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('membership_number', $request->membership_number)
                    ->where('branch_id', $request->branch_id)
                    ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'membership_number' => ['The credentials do not match our records for this branch.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('mobile_token')->plainTextToken,
            'user' => $user
        ]);
    }
}
3. Frontend: Vue.js Login Page
   This UI matches the look in the video using Tailwind CSS.
   src/views/Login.vue
   code
   Vue
   <template>
  <div class="min-h-screen bg-white p-6 flex flex-col items-center">
    <!-- Header/Logo Area -->
    <div class="mt-10 mb-8 flex flex-col items-center">
      <div class="w-20 h-20 bg-green-100 rounded-full mb-4 flex items-center justify-center">
        <img src="/logo.png" alt="Logo" class="w-16 h-16 object-contain" />
      </div>
      <h1 class="text-3xl font-extrabold text-slate-900">Member Login</h1>
      <p class="text-gray-500 text-sm text-center mt-2 px-6">
        Please select your branch and enter your credentials
      </p>
    </div>

    <!-- Login Form -->
    <div class="w-full max-w-sm space-y-5">
      
      <!-- Branch Selection -->
      <div>
        <label class="block text-blue-900 font-bold mb-1">Select Branch</label>
        <select v-model="form.branch_id" 
          class="w-full bg-slate-100 border-2 border-transparent focus:border-green-400 p-4 rounded-2xl outline-none transition-all">
          <option value="" disabled>Choose your branch</option>
          <option v-for="branch in branches" :key="branch.id" :value="branch.id">
            {{ branch.name }}
          </div>
        </select>
      </div>

      <!-- Membership Number -->
      <div>
        <label class="block text-blue-900 font-bold mb-1">Membership Number</label>
        <input v-model="form.membership_number" type="text" placeholder="e.g. 052286"
          class="w-full bg-slate-100 border-2 border-transparent focus:border-green-400 p-4 rounded-2xl outline-none" />
      </div>

      <!-- Password with Eye Toggle -->
      <div class="relative">
        <label class="block text-blue-900 font-bold mb-1">Password</label>
        <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Enter your password"
          class="w-full bg-slate-100 border-2 border-transparent focus:border-green-400 p-4 rounded-2xl outline-none" />
        <button @click="showPassword = !showPassword" class="absolute right-4 top-11 text-gray-400">
          <span v-if="showPassword">🙈</span>
          <span v-else>👁️</span>
        </button>
      </div>

      <!-- Login Button -->
      <button @click="handleLogin" :disabled="loading"
        class="w-full bg-green-700 hover:bg-green-800 text-white py-4 rounded-2xl font-bold text-lg shadow-lg flex items-center justify-center gap-2 transition-all">
        <span v-if="loading" class="animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
        {{ loading ? 'Processing...' : 'Login' }}
      </button>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const branches = ref([]);
const loading = ref(false);
const showPassword = ref(false);

const form = ref({
  branch_id: '',
  membership_number: '',
  password: ''
});

// Fetch branches from API on load
onMounted(async () => {
  const response = await axios.get('http://localhost:8000/api/branches');
  branches.value = response.data;
});

const handleLogin = async () => {
  loading.value = true;
  try {
    const response = await axios.post('http://localhost:8000/api/login', form.value);
    localStorage.setItem('token', response.data.token);
    router.push('/dashboard');
  } catch (error) {
    alert(error.response.data.message || 'Login Failed');
  } finally {
    loading.value = false;
  }
};
</script>
4. Key Logic Points for the Prototype:
   Password Toggle: I added a showPassword boolean. When clicked, it changes the input type from password to text.
   Validation: The Laravel controller specifically queries where('branch_id', ...) so that even if a membership number exists in Branch A, they cannot use it to log into Branch B.
   Mobile Polish:
   Used rounded-2xl for the extra-rounded inputs seen in the video.
   Used bg-slate-100 for that light grey input background.
   The "Login" button includes a CSS spinner for that "C" animation effect.
   Next Step:
   After logging in, you will need the Dashboard (Wallet Card). Should we proceed to building the dashboard with the balance-hiding "Eye" icon?
   Model 1:00 PM
   ThinkingThoughts
   Expand to view model thoughts

chevron_right
The Dashboard is the heart of the cooperative app. Based on the video, it features a prominent balance card, a privacy toggle (Eye icon), and quick action buttons.
Here is how to build it.
1. Backend: Update User & Transactions
   First, we need to ensure the database can store the user's balance and track transactions.
   Add Balance to Users Table:
   Run: ./vendor/bin/sail artisan make:migration add_balance_to_users
   code
   PHP
   public function up() {
   Schema::table('users', function (Blueprint $table) {
   $table->decimal('balance', 15, 2)->default(0.00);
   });
   }
   Create Dashboard API Controller:
   code
   PHP
   // app/Http/Controllers/Api/DashboardController.php
   public function index(Request $request) {
   $user = $request->user();

   // Get last 5 successful or pending transactions
   $transactions = $user->contributions()
   ->with('scheme')
   ->latest()
   ->take(5)
   ->get();

   return response()->json([
   'full_name' => $user->name,
   'membership_id' => $user->membership_number,
   'balance' => $user->balance,
   'transactions' => $transactions
   ]);
   }
2. Frontend: Dashboard Component
   This Vue component replicates the "card" feel, the "eye" toggle logic, and the bottom navigation menu.
   src/views/Dashboard.vue
   code
   Vue
   <template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <!-- Top Header -->
    <header class="p-4 flex justify-between items-center bg-white border-b">
      <div class="flex items-center gap-2">
        <div class="w-10 h-10 bg-green-700 rounded-full flex items-center justify-center text-white font-bold text-xl">
          {{ dashboardData.full_name?.charAt(0) }}
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium">Welcome back,</p>
          <h2 class="text-sm font-bold text-slate-800 uppercase">{{ dashboardData.full_name }}</h2>
        </div>
      </div>
      <button class="bg-slate-100 p-2 rounded-full text-xl">🔔</button>
    </header>

    <!-- Balance Card -->
    <div class="p-4">
      <div class="bg-gradient-to-br from-green-700 to-green-900 rounded-[2rem] p-7 text-white shadow-xl shadow-green-200 relative overflow-hidden">
        <!-- Decoration circles -->
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full"></div>
        
        <div class="flex items-center gap-2 mb-2 relative z-10">
          <p class="text-green-100 text-sm font-medium">Available Balance</p>
          <button @click="hideBalance = !hideBalance" class="text-lg opacity-80">
            <span v-if="hideBalance">👁️</span>
            <span v-else>🙈</span>
          </button>
        </div>

        <h1 class="text-4xl font-bold relative z-10 tracking-tight">
          ₦ {{ hideBalance ? '***,***.**' : formatMoney(dashboardData.balance) }}
        </h1>

        <div class="mt-8 flex justify-between items-center relative z-10">
          <p class="text-xs text-green-100 font-mono tracking-widest">ID: {{ dashboardData.membership_id }}</p>
          <button class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-xl text-xs font-bold backdrop-blur-md transition-all">
            + Add Money
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Action Grid -->
    <div class="px-4 grid grid-cols-2 gap-4 mt-2">
      <button @click="$router.push('/pay')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-2xl">💳</div>
        <span class="text-sm font-bold text-slate-700">Make Payment</span>
      </button>
      <button @click="$router.push('/loans')" class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex flex-col items-center gap-2 active:bg-slate-50 transition-all">
        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-2xl">📊</div>
        <span class="text-sm font-bold text-slate-700">Loan Records</span>
      </button>
    </div>

    <!-- Recent Transactions -->
    <div class="px-4 mt-8">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-slate-800 text-lg">Recent Transactions</h3>
        <button class="text-green-700 text-sm font-bold">View All</button>
      </div>

      <div v-if="dashboardData.transactions?.length" class="space-y-3">
        <div v-for="tx in dashboardData.transactions" :key="tx.id" 
          class="bg-white p-4 rounded-2xl flex items-center justify-between border border-slate-100 shadow-sm">
          <div class="flex items-center gap-3">
            <div :class="tx.status === 'success' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'" 
              class="w-10 h-10 rounded-full flex items-center justify-center text-lg">
              {{ tx.status === 'success' ? '✓' : '⌛' }}
            </div>
            <div>
              <p class="font-bold text-slate-800 text-sm">{{ tx.scheme.name }}</p>
              <p class="text-[10px] text-gray-500 uppercase font-medium">{{ formatDate(tx.created_at) }}</p>
            </div>
          </div>
          <p class="font-bold text-slate-800">₦ {{ formatMoney(tx.amount) }}</p>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-10 text-gray-400">
        <p>No transactions yet.</p>
      </div>
    </div>

    <!-- Mobile Bottom Navigation (Prototype) -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-green-700 flex flex-col items-center gap-1">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1">
        <span class="text-xl">⚙️</span>
        <span class="text-[10px] font-bold">Settings</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const dashboardData = ref({});
const hideBalance = ref(false);

const formatMoney = (val) => {
  return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

onMounted(async () => {
  const token = localStorage.getItem('token');
  const response = await axios.get('http://localhost:8000/api/dashboard', {
    headers: { Authorization: `Bearer ${token}` }
  });
  dashboardData.value = response.data;
});
</script>
3. Key Design Features Explained:
   Eye Toggle Logic: The hideBalance ref controls whether the amount is visible. It is saved only in the component's memory for privacy while using the app.
   Mobile Formatting: I used rounded-[2rem] and rounded-3xl to match the extremely rounded corners seen in modern Fintech mobile apps (and your video).
   Gradient Card: The bg-gradient-to-br from-green-700 to-green-900 mimics the premium bank-card feel.
   Transaction Badges: It automatically checks the tx.status from Laravel and changes the icon from a checkmark (green) to an hourglass (yellow).
   Next Step:
   The video showed a very specific "Make Payment" screen where you can add multiple amounts to different schemes (Savings, Shares, etc.). Should we build that Multi-Scheme Payment Form next?
   Model 1:31 PM
   ThinkingThoughts
   Expand to view model thoughts

chevron_right
This feature is what makes the app a "Cooperative" system. In the video, we saw a member selecting a scheme (like Savings), entering an amount, and clicking a "+" button to add it to a list before making one final total payment.
Here is how to implement this Multi-Scheme Payment Form.
1. Backend: Schemes and Payment Logic
   We need to provide the list of available schemes to the app and a way to handle the bulk transaction.
   The API Routes (routes/api.php):
   code
   PHP
   Route::get('/schemes', [PaymentController::class, 'getSchemes']);
   Route::post('/initiate-payment', [PaymentController::class, 'initiate']);
   The Controller Logic (app/Http/Controllers/Api/PaymentController.php):
   code
   PHP
   public function initiate(Request $request) {
   $request->validate([
   'items' => 'required|array',
   'items.*.scheme_id' => 'required|exists:schemes,id',
   'items.*.amount' => 'required|numeric|min:1',
   ]);

   $totalAmount = collect($request->items)->sum('amount');
   $reference = 'COOP_' . time() . '_' . auth()->id();

   // 1. Record each pending contribution in the database
   foreach ($request->items as $item) {
   auth()->user()->contributions()->create([
   'scheme_id' => $item['scheme_id'],
   'amount' => $item['amount'],
   'reference' => $reference, // Grouped by same reference
   'status' => 'pending'
   ]);
   }

   // 2. Return the Paystack/Stripe checkout URL (Mocking here for now)
   return response()->json([
   'checkout_url' => "https://checkout.paystack.com/" . $reference,
   'total' => $totalAmount,
   'reference' => $reference
   ]);
   }
2. Frontend: Multi-Scheme Vue Component
   This UI mimics the video: select scheme, enter amount, add to list, then pay.
   src/views/MakePayment.vue
   code
   Vue
   <template>
  <div class="min-h-screen bg-slate-50 pb-20">
    <!-- Header -->
    <header class="p-4 bg-white border-b flex items-center gap-4">
      <button @click="$router.back()" class="text-2xl">⬅️</button>
      <h1 class="text-xl font-bold">Make Payment</h1>
    </header>

    <div class="p-4">
      <!-- Input Section Card -->
      <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 mb-6">
        <p class="text-xs text-red-500 font-bold mb-4 uppercase">
          ⚠️ Click the "+" button to share your money across multiple schemes
        </p>

        <div class="flex gap-2">
          <!-- Scheme Selector -->
          <div class="flex-grow">
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Scheme</label>
            <select v-model="selectedSchemeId" class="w-full bg-slate-50 p-3 rounded-xl border-none text-sm outline-none">
              <option value="">Select Scheme</option>
              <option v-for="scheme in schemes" :key="scheme.id" :value="scheme.id">
                {{ scheme.name }}
              </option>
            </select>
          </div>

          <!-- Amount Input -->
          <div class="w-1/3">
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount</label>
            <input v-model="inputAmount" type="number" placeholder="0.00" 
              class="w-full bg-slate-50 p-3 rounded-xl border-none text-sm outline-none" />
          </div>

          <!-- Add Button (+) -->
          <button @click="addToList" class="mt-5 bg-green-700 text-white w-12 h-12 rounded-xl flex items-center justify-center text-2xl font-bold">
            +
          </button>
        </div>
      </div>

      <!-- Payment Summary List -->
      <h3 class="font-bold text-slate-800 mb-3 px-2">Payment Summary</h3>
      <div v-if="paymentList.length > 0" class="space-y-3">
        <div v-for="(item, index) in paymentList" :key="index" 
          class="bg-white p-4 rounded-2xl flex items-center justify-between border-l-4 border-green-700 shadow-sm">
          <div>
            <p class="font-bold text-slate-800 text-sm">{{ item.scheme_name }}</p>
            <p class="text-xs text-gray-500">Scheduled Payment</p>
          </div>
          <div class="flex items-center gap-4">
            <p class="font-bold text-slate-800">₦ {{ item.amount.toLocaleString() }}</p>
            <button @click="removeFromList(index)" class="text-red-400 text-sm">✕</button>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-10 text-gray-400 italic text-sm">
        No schemes added yet.
      </div>
    </div>

    <!-- Sticky Bottom Payment Button -->
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t">
      <div class="flex justify-between items-center mb-4 px-2">
        <span class="text-gray-500 font-bold uppercase text-xs tracking-widest">Total to Transfer</span>
        <span class="text-2xl font-black text-slate-900">₦ {{ totalAmount.toLocaleString() }}</span>
      </div>
      <button @click="initiatePayment" :disabled="paymentList.length === 0"
        class="w-full bg-green-700 disabled:bg-gray-300 text-white py-4 rounded-2xl font-bold text-lg shadow-lg active:scale-95 transition-all">
        Make Payment
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const schemes = ref([]);
const paymentList = ref([]);
const selectedSchemeId = ref('');
const inputAmount = ref('');

const totalAmount = computed(() => {
  return paymentList.value.reduce((sum, item) => sum + Number(item.amount), 0);
});

const addToList = () => {
  if (!selectedSchemeId.value || !inputAmount.value) return;
  
  const scheme = schemes.value.find(s => s.id === selectedSchemeId.value);
  paymentList.value.push({
    scheme_id: scheme.id,
    scheme_name: scheme.name,
    amount: parseFloat(inputAmount.value)
  });

  // Reset inputs
  selectedSchemeId.value = '';
  inputAmount.value = '';
};

const removeFromList = (index) => {
  paymentList.value.splice(index, 1);
};

const initiatePayment = async () => {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.post('http://localhost:8000/api/initiate-payment', 
      { items: paymentList.value },
      { headers: { Authorization: `Bearer ${token}` }}
    );
    
    // In a real app, you'd use Capacitor InAppBrowser to open this:
    window.location.href = response.data.checkout_url;
  } catch (error) {
    alert("Payment initialization failed");
  }
};

onMounted(async () => {
  const response = await axios.get('http://localhost:8000/api/schemes');
  schemes.value = response.data;
});
</script>
3. Features Integrated (from the video):
   Bulk Logic: The user can pay for "Monthly Savings" and "Building Shares" at the same time. The database creates two records, but the payment gateway only processes one total.
   Validation: The "Make Payment" button remains disabled (greyed out) until at least one item is added to the list.
   UI Cues: The red warning text and the "+" button design match the exact flow seen in the second video.
   Grouped Reference: All items in one session share the same reference code. This makes it easy to approve the whole batch when the payment is successful.
   Next Step:
   We have the dashboard and the payment system. The final big feature from the video is the Financial Passbook (The Grid) that shows payments across months (Jan, Feb, Mar, etc.).
   Should we build the Passbook Matrix next?
   Model 1:33 PM
   ThinkingThoughts
   Expand to view model thoughts

chevron_right
The Financial Passbook (or Financial Sheet) is the most complex part of the cooperative app. It provides a "matrix" view: Rows are your schemes (Savings, Shares, etc.), and Columns are the months of the year (Jan–Dec).
Here is how to build this grid logic.
1. Backend: The Matrix Logic
   We need to group all successful contributions by Scheme and Month for a specific year.
   The API Route (routes/api.php):
   code
   PHP
   Route::get('/passbook/{year}', [PassbookController::class, 'getMatrix']);
   The Controller (app/Http/Controllers/Api/PassbookController.php):
   code
   PHP
   public function getMatrix(Request $request, $year) {
   $user = $request->user();

   // Fetch all successful contributions for the chosen year
   $contributions = $user->contributions()
   ->whereYear('created_at', $year)
   ->where('status', 'success')
   ->get();

   // Fetch all schemes to ensure every row exists
   $schemes = \App\Models\Scheme::all();

   $matrix = $schemes->map(function ($scheme) use ($contributions) {
   $row = [
   'scheme_name' => $scheme->name,
   'months' => array_fill(1, 12, 0), // Initialize months 1-12 with 0
   'total' => 0
   ];

        foreach ($contributions as $con) {
            if ($con->scheme_id === $scheme->id) {
                $month = $con->created_at->month;
                $row['months'][$month] += (float)$con->amount;
                $row['total'] += (float)$con->amount;
            }
        }
        return $row;
   });

   return response()->json([
   'year' => $year,
   'matrix' => $matrix,
   'grand_total' => $matrix->sum('total')
   ]);
   }
2. Frontend: The Passbook Grid UI
   Because a 13-column table (Scheme + 12 Months) is too wide for a phone, we must use a horizontal scroll container, just like in the video.
   src/views/Passbook.vue
   code
   Vue
   <template>
  <div class="min-h-screen bg-slate-50 pb-20">
    <!-- Header & Year Selector -->
    <header class="p-4 bg-white border-b sticky top-0 z-20">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold text-slate-800">Financial Sheet</h1>
        <select v-model="selectedYear" @change="fetchPassbook" class="bg-slate-100 p-2 rounded-lg text-sm font-bold border-none outline-none">
          <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>

      <!-- Summary Mini-Card -->
      <div class="bg-blue-50 p-3 rounded-xl flex justify-between items-center">
        <span class="text-xs font-bold text-blue-800 uppercase">Yearly Cumulative</span>
        <span class="text-lg font-black text-blue-900">₦ {{ grandTotal.toLocaleString() }}</span>
      </div>
    </header>

    <div class="p-4">
      <!-- Horizontal Scroll Container -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-800 text-white text-[10px] uppercase tracking-tighter">
                <th class="p-3 sticky left-0 bg-slate-800 z-10 border-r border-slate-700">Scheme</th>
                <th v-for="m in months" :key="m" class="p-3 text-center min-w-[60px] border-r border-slate-700">{{ m }}</th>
                <th class="p-3 text-center bg-green-700">Total</th>
              </tr>
            </thead>
            <tbody class="text-[11px]">
              <tr v-for="(row, idx) in matrix" :key="idx" class="border-b border-slate-100 hover:bg-slate-50">
                <!-- Sticky Scheme Name Column -->
                <td class="p-3 font-bold text-slate-700 sticky left-0 bg-white border-r border-slate-100 shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                  {{ row.scheme_name }}
                </td>
                
                <!-- Monthly Cells -->
                <td v-for="mIdx in 12" :key="mIdx" class="p-3 text-center border-r border-slate-50 text-slate-500">
                  {{ row.months[mIdx] > 0 ? row.months[mIdx].toLocaleString() : '-' }}
                </td>

                <!-- Row Total -->
                <td class="p-3 text-center font-black text-slate-900 bg-slate-50">
                  {{ row.total.toLocaleString() }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <p class="text-[10px] text-gray-400 mt-4 px-2 italic text-center">
        Swipe left/right to view all months
      </p>
    </div>

    <!-- Re-using Bottom Nav -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button @click="$router.push('/dashboard')" class="text-slate-400 flex flex-col items-center gap-1">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-green-700 flex flex-col items-center gap-1">
        <span class="text-xl">📅</span>
        <span class="text-[10px] font-bold">Passbook</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1">
        <span class="text-xl">⚙️</span>
        <span class="text-[10px] font-bold">Settings</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const selectedYear = ref(2026);
const matrix = ref([]);
const grandTotal = ref(0);

const fetchPassbook = async () => {
  const token = localStorage.getItem('token');
  try {
    const response = await axios.get(`http://localhost:8000/api/passbook/${selectedYear.value}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    matrix.value = response.data.matrix;
    grandTotal.value = response.data.grand_total;
  } catch (e) {
    console.error("Failed to load passbook");
  }
};

onMounted(fetchPassbook);
</script>

<style scoped>
/* Force horizontal scroll for the table */
.overflow-x-auto {
  -webkit-overflow-scrolling: touch;
}
</style>
3. Critical Features Implemented:
   Sticky First Column: In the video, the user needs to know which scheme they are looking at even when they scroll to "December". The sticky left-0 class ensures the Scheme name stays visible while the months scroll behind it.
   Empty Data Handling: We use a dash (-) for months with zero contributions to keep the table clean, making paid amounts stand out (just like the video's green/blue highlights).
   Real-Time Year Filtering: Changing the year in the dropdown triggers a new API call to refresh the entire grid.
   Mobile Styling: Smaller font sizes (text-[11px]) and tracking are used so the table fits as many months as possible on the screen at once.
   Summary of the Prototype Accomplished:
   ✅ Branch-based Login with membership IDs.
   ✅ Modern Dashboard with Balance card and Privacy toggle.
   ✅ Multi-Scheme Payment Form with a "+" list builder.
   ✅ Financial Passbook Grid with horizontal scrolling.
   Final Step for You:
   To make this "Native" for a phone, you would wrap this in Capacitor.
   Run npm run build.
   Run npx cap sync.
   Open the project in Android Studio or Xcode to generate the .apk or .app file.
   Would you like instructions on how to set up the Paystack Webhook to automate the balance updates when someone makes a payment?
   Model 1:56 PM
   ThinkingThoughts
   Expand to view model thoughts

chevron_right
The Webhook is the most important part of the backend. It ensures that even if the user closes their app or loses internet connection after paying, your system still updates their balance automatically when Paystack confirms the money has been received.
Since we are using a grouped reference (one payment for multiple schemes), the webhook must update all related records.
1. Create the Webhook Route
   You must exclude this route from CSRF protection because Paystack (an external server) is sending the request.
   In routes/api.php:
   code
   PHP
   use App\Http\Controllers\Api\WebhookController;

Route::post('/webhooks/paystack', [WebhookController::class, 'handlePaystack']);
In bootstrap/app.php (Laravel 11 version):
code
PHP
->withMiddleware(function (Middleware $middleware) {
$middleware->validateCsrfTokens(except: [
'api/webhooks/paystack' // Exclude webhook from CSRF
]);
})
2. Create the Webhook Controller
   This controller validates that the request actually came from Paystack and then updates the user's finances.
   app/Http/Controllers/Api/WebhookController.php
   code
   PHP
   namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Contribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends \App\Http\Controllers\Controller
{
public function handlePaystack(Request $request)
{
// 1. Verify the signature (Security)
$paystackSignature = $request->header('x-paystack-signature');
$secretKey = config('services.paystack.secret_key'); // Set this in .env

        if (!$paystackSignature || ($paystackSignature !== hash_hmac('sha512', $request->getContent(), $secretKey))) {
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        // 2. Parse the event
        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'charge.success') {
            $reference = $data['reference'];
            $paidAmount = $data['amount'] / 100; // Paystack sends in kobo

            // 3. Find all pending contributions sharing this reference
            $contributions = Contribution::where('reference', $reference)
                                        ->where('status', 'pending')
                                        ->get();

            if ($contributions->count() > 0) {
                $user = User::find($contributions->first()->user_id);

                // 4. Update each contribution and user balance
                foreach ($contributions as $contribution) {
                    $contribution->update(['status' => 'success']);
                    
                    // Increment the user's wallet balance
                    $user->increment('balance', $contribution->amount);
                }

                Log::info("Payment Successful: Ref $reference for User ID: $user->id");
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
3. Update your .env file
   You need to add your Paystack keys here so the controller can use them.
   code
   Env
   PAYSTACK_PUBLIC_KEY=pk_test_xxxxxx
   PAYSTACK_SECRET_KEY=sk_test_xxxxxx
4. How the Flow Works (Summary)
   Vue App: User selects 3 schemes (Savings, Shares, Takaful) totaling ₦15,000.
   Laravel API: Creates 3 rows in the contributions table, all with status = pending and reference = COOP_123.
   Paystack Checkout: The user pays ₦15,000 via card or bank transfer.
   Paystack Webhook: Paystack's server sends a background message to your Laravel server saying "Payment COOP_123 was successful."
   Laravel Logic:
   Finds all 3 rows with reference COOP_123.
   Changes their status to success.
   Adds ₦15,000 to the user's total balance.
   Vue App: Next time the user refreshes the Dashboard or Passbook, their balance is updated and the "Checkmark" appears in their history.
5. Testing the Webhook
   Since your computer is likely running on localhost, Paystack cannot "see" your server to send the webhook. To test this during development, you should use a tool called Ngrok.
   Install Ngrok: npm install -g ngrok
   Run Ngrok: ngrok http 8000
   Get the URL: It will give you a link like https://xyz.ngrok.io.
   Set in Paystack Dashboard: Go to your Paystack Settings -> Webhooks and paste https://xyz.ngrok.io/api/webhooks/paystack.
   Final Prototype Status
   You now have a fully functional fintech architecture:
   Branch/Member Auth (Security)
   Dynamic Dashboard (Privacy)
   Multi-Scheme Cart (Business Logic)
   Month-by-Month Passbook (Transparency)
   Automated Webhooks (Reliability)
   Is there any other specific feature from the videos (like Loans or PDF exports) that you would like to tackle?
   Model 1:58 PM
   ThinkingThoughts
   Expand to view model thoughts

chevron_right
To complete the prototype based on the videos, we will now implement the Loan Records system and the PDF Statement Export feature.
In the video, the Loan Card is highly detailed, showing "Loan Amount," "Total Installments," "Per Installment," "Interval," "Paid Amount," and "Balance."
1. Backend: Loan System Setup
   You need a table to track loans separately from regular savings.
   Migration for Loans:
   code
   PHP
   Schema::create('loans', function (Blueprint $table) {
   $table->id();
   $table->foreignId('user_id')->constrained();
   $table->string('loan_id_string')->unique(); // e.g., "S90TVB0LFM"
   $table->decimal('amount', 15, 2);
   $table->integer('total_installments');
   $table->decimal('per_installment', 15, 2);
   $table->string('interval')->default('Monthly');
   $table->decimal('paid_amount', 15, 2)->default(0);
   $table->enum('status', ['active', 'paid', 'inactive'])->default('active');
   $table->timestamps();
   });
   Loan Controller (app/Http/Controllers/Api/LoanController.php):
   code
   PHP
   public function index(Request $request) {
   return response()->json($request->user()->loans()->get());
   }
2. Frontend: The Loan Card (Video 1 Style)
   This Vue component replicates the exact layout seen in the video, with the green header and double-column data points.
   src/components/LoanCard.vue
   code
   Vue
   <template>
  <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <!-- Card Header -->
    <div class="p-4 bg-slate-50 flex justify-between items-center border-b border-slate-100">
      <div>
        <span class="text-green-700 font-black text-lg">#{{ index + 1 }}</span>
        <h3 class="inline ml-2 font-bold text-slate-800 text-lg">Loan Repayment</h3>
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">ID: {{ loan.loan_id_string }}</p>
      </div>
      <span :class="loan.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-500'" 
        class="px-3 py-1 rounded-full text-[10px] font-black uppercase">
        {{ loan.status }}
      </span>
    </div>

    <!-- Card Body (Grid) -->
    <div class="p-5 grid grid-cols-2 gap-y-4 gap-x-2">
      <div>
        <p class="text-[10px] text-slate-400 font-bold uppercase">Loan Amount</p>
        <p class="font-black text-slate-800 tracking-tighter">₦ {{ loan.amount.toLocaleString() }}</p>
      </div>
      <div>
        <p class="text-[10px] text-slate-400 font-bold uppercase">Total Installments</p>
        <p class="font-black text-slate-800">{{ loan.total_installments }}</p>
      </div>
      <div>
        <p class="text-[10px] text-slate-400 font-bold uppercase">Interval</p>
        <p class="font-black text-slate-800">{{ loan.interval }}</p>
      </div>
      <div>
        <p class="text-[10px] text-slate-400 font-bold uppercase">Per Installment</p>
        <p class="font-black text-slate-800">₦ {{ loan.per_installment.toLocaleString() }}</p>
      </div>
      <div>
        <p class="text-[10px] text-slate-400 font-bold uppercase">Paid Amount</p>
        <p class="font-black text-green-600 text-lg">₦ {{ loan.paid_amount.toLocaleString() }}</p>
      </div>
      <div>
        <p class="text-[10px] text-slate-400 font-bold uppercase text-right">Balance</p>
        <p class="font-black text-red-600 text-lg text-right">₦ {{ (loan.amount - loan.paid_amount).toLocaleString() }}</p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="p-4 pt-0 flex gap-3">
      <button class="flex-grow bg-slate-100 text-slate-700 py-3 rounded-2xl font-bold text-xs uppercase">
        View More Information
      </button>
      <button @click="$router.push('/pay')" class="flex-grow bg-green-700 text-white py-3 rounded-2xl font-bold text-xs uppercase shadow-lg shadow-green-100">
        Make Payment
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps(['loan', 'index']);
</script>
3. Backend: PDF Generation (Financial Passbook)
   Members often need a formal PDF for their records. We use laravel-dompdf.
   The Export Controller:
   code
   PHP
   use Barryvdh\DomPDF\Facade\Pdf;

public function downloadPassbook(Request $request) {
$user = $request->user();
$data = [
'user' => $user,
'branch' => $user->branch->name,
'year' => date('Y'),
'contributions' => $user->contributions()->where('status', 'success')->get()
];

    $pdf = Pdf::loadView('pdfs.passbook', $data);
    return $pdf->download('Coop_Statement_'.$user->membership_number.'.pdf');
}
The PDF Blade View (resources/views/pdfs/passbook.blade.php):
In a real app, this would look like a formal letterhead.
code
Html
<style>
    body { font-family: sans-serif; font-size: 12px; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { bg-color: #f2f2f2; }
</style>

<div class="header">
    <h1>Al-Hayat Relief Foundation</h1>
    <p>Member Passbook Statement - {{ $year }}</p>
</div>

<p><strong>Member Name:</strong> {{ $user->name }}</p>
<p><strong>Membership ID:</strong> {{ $user->membership_number }}</p>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Scheme</th>
            <th>Reference</th>
            <th>Amount (₦)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contributions as $con)
        <tr>
            <td>{{ $con->created_at->format('d/m/Y') }}</td>
            <td>{{ $con->scheme->name }}</td>
            <td>{{ $con->reference }}</td>
            <td>{{ number_format($con->amount, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
4. Frontend: Triggering the Download
In your Vue app (usually in Settings or Passbook), add the download button:
code
JavaScript
const downloadStatement = () => {
  const token = localStorage.getItem('token');
  window.open(`http://localhost:8000/api/download-passbook?token=${token}`, '_blank');
};
Final Prototype Accomplishments:
Login: Branch selection + Membership ID verification.
Dashboard: High-quality Fintech UI with Balance hiding.
Payments: Multi-scheme list building with one total checkout.
Webhooks: Automated background processing for money security.
Passbook: Monthly matrix grid with horizontal scrolling.
Loans: Full breakdown of repayment progress with color-coded balances.
Documents: Professional PDF export for transparency.
Summary
You have built a complete Cooperative Fintech App. To turn this into a production-ready system:
Production Deployment: Deploy the Laravel API to a server (Forge/DigitalOcean).
App Stores: Use Capacitor to wrap the Vue code. Install the Android Studio SDK, build the .aab file, and upload it to the Google Play Store.
You are now ready to launch the prototype. Do you have any final technical questions on deployment?
Model 2:21 PM
ThinkingThoughts
Expand to view model thoughts

chevron_right
To build a professional cooperative app like the one in the video, you need to transition from a basic prototype to a secure, scalable fintech product.
Here is the master summary of Key Features and the Professional Roadmap.
Part 1: Summary of Key Features (The "Video Look")
1. Advanced Authentication
   Branch-Gated Login: A dropdown to select the branch, ensuring membership IDs are unique to their specific location.
   Membership ID System: Using custom strings (e.g., 052286) instead of emails for the primary login.
   Security UI: Eye-toggle for passwords and biometric-ready (Fingerprint/FaceID via Capacitor).
2. Fintech Dashboard
   Wallet Management: A balance card with a Privacy Toggle (eye icon) to hide the amount.
   Instant Funding: Modal showing a Dedicated Virtual Account Number for bank transfers.
   Quick Action Grid: Large, rounded buttons for "Make Payment" and "Loan Records."
3. Multi-Scheme Contribution (Cart Logic)
   Scheme Selection: Ability to choose from 10+ savings types (Monthly, Building, Shares, etc.).
   The "+" List Builder: Adding multiple items to a "payment cart" to pay once for everything.
   Payment Gateway: Integration with Paystack or Flutterwave using background Webhooks for 100% payment reliability.
4. The "Financial Sheet" (Passbook)
   Horizontal Matrix Grid: A complex table showing Schemes (Rows) vs. Months (Columns).
   Sticky Columns: Keeping the Scheme name visible while swiping through the months (Jan–Dec).
   Yearly Filter: Capability to switch between different financial years.
5. Loan Tracking
   Real-time Amortization: Showing exactly what has been paid vs. the remaining balance.
   Installment Indicators: Tracking intervals (Monthly/Weekly) and per-installment costs.
   Part 2: Roadmap to a Professional App (Step-by-Step)
   Step 1: Secure Infrastructure (Docker)
   Don't just run code locally; use a production-ready Docker setup (Laravel Sail).

   ## Run with Docker (Laravel Sail) + Frontend

   Prerequisites: Docker Desktop.

   1. Copy backend/.env if needed and set DB_PASSWORD, etc.
   2. From the backend directory, start Sail services (PHP, MySQL, Redis, Mailpit):
      - Linux/macOS: ./vendor/bin/sail up -d
      - Windows (WSL): bash backend/vendor/bin/sail up -d
   3. Start the frontend service via the same compose file:
      - Linux/macOS: ./vendor/bin/sail compose up -d frontend
      - Windows (WSL): bash backend/vendor/bin/sail compose up -d frontend

   URLs:
   - Backend (Laravel): http://localhost:8080
   - Frontend (Vue Vite): http://localhost:5174
   - Mailpit: http://localhost:8025

   The frontend Vite dev server proxies API calls from /api to the backend inside Sail.

   ## Mobile (Capacitor)

   - Set the API URL for mobile builds (since there is no proxy):
     - Create frontend/.env with:
       VITE_API_URL=http://localhost:8080
     - On Android emulator, use 10.0.2.2 instead of localhost.

   - For live reload on device/emulator, also set:
     VITE_MOBILE_SERVER_URL=http://YOUR_LAN_IP:5174

   Then:
   - cd frontend
   - npm install
   - npm run build (or npm run dev for live reload with Vite)
   - npx cap add android (or ios)
   - npx cap sync
   - npx cap open android (or ios)

   Ensure the device can reach the backend URL you set in VITE_API_URL.
   Action: Ensure separate containers for php-fpm, mysql, redis (for speed), and nginx.
   Tool: Use Laravel Sanctum for secure, token-based mobile communication.
   Step 2: High-Fidelity UI (Vue 3 + Tailwind v4)
   To get the "App Store" look:
   Action: Use a Mobile-First CSS approach. Focus on rounded-[2rem] corners and safe-area padding for iPhones with notches.
   Tool: Pinia for state management (keeping the user's balance updated across all screens without reloading).
   Step 3: Admin Control Panel (The Hidden Side)
   Every cooperative app needs an admin side to approve members and view total collections.
   Action: Use Laravel Filament. It allows you to build a powerful admin dashboard in minutes to manage Branches, Schemes, and Loans.
   Step 4: Native Mobile Integration (Capacitor)
   Turn your Vue web app into a real .apk (Android) and .ipa (iOS) file.
   Action: Install CapacitorJS. Use native plugins for Clipboard (copying account numbers) and Browser (secure payment windows).
   Step 5: Professional Logic & Reliability
   Action: Implement Background Jobs (Queues). When a payment is successful, Laravel should process the balance update in the background so the app stays fast.
   Action: Implement Logging. Track every single balance change in an audit_logs table for financial accountability.
   Step 6: Testing & Deployment
   Action: Use Ngrok to test your Paystack webhooks while in development.
   Action: Deploy the backend to a VPS (like DigitalOcean or AWS) using SSL (HTTPS). You cannot process payments without HTTPS.
   Final Checklist for Your Prototype
   Step	Status	Focus
   Backend	Composer	Laravel 11, Sanctum, DomPDF, Paystack SDK
   Frontend	NPM	Vue 3, Tailwind v4, Pinia, Axios
   Mobile	Capacitor	Clipboard, App Launcher, SplashScreen
   Logic	Database	Branch-User-Scheme-Contribution relationships
   Fintech	Webhooks	Background automated balance updates


# Shariah-Compliant Addendum: Complete, Interest‑Free Cooperative App (Web + Mobile)

This addendum adapts the existing cooperative fintech blueprint to be fully Shariah‑compliant for a Muslim community. It preserves all the good UX and reliability, while guaranteeing:
- No riba (no interest or usury) on any loan or balance.
- Transparent, ethical finance: Qard Hasan (benevolent, interest‑free loans), optional permissible administrative fees at cost, late penalties routed to charity, and profit‑sharing products where relevant.
- Strong governance: Shariah Board review, immutable audit logs, and charity ledger separation.

Overview of What Changes vs. the Generic Prototype
- Loans become Qard Hasan: strictly 0% interest; only optional, capped administrative fees (cost recovery) are allowed and must be disclosed.
- Late penalties (if used) are not income; they go to a Charity Ledger and are earmarked for sadaqah only.
- Savings can remain standard contributions, and profit distribution (if any) should be modeled as Mudarabah/Musharakah shares, not interest accrual.
- Contracts and disclosure screens are added on the frontend for informed consent.

1) Data Model Extensions (Laravel Migrations)
Add the following migrations on top of the prototype’s schema to model Shariah‑compliant logic clearly.

- Qard Hasan (interest‑free loans)
  PHP
  Schema::create('qard_hasans', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained();
      $table->string('qard_id_string')->unique(); // e.g., QH-2026-000123
      $table->decimal('principal_amount', 15, 2);
      $table->integer('total_installments'); // e.g., 12
      $table->decimal('per_installment', 15, 2); // derived: principal / installments
      $table->string('interval')->default('Monthly'); // Monthly/Weekly
      $table->decimal('admin_fee_flat', 15, 2)->default(0); // permissible cost recovery
      $table->decimal('admin_fee_pct', 5, 2)->default(0);  // cap at board policy, e.g., <= 2%
      $table->decimal('paid_amount', 15, 2)->default(0);
      $table->enum('status', ['active', 'paid', 'inactive'])->default('active');
      $table->timestamps();
  });

- Qard Hasan Repayments
  PHP
  Schema::create('qard_hasans_repayments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('qard_hasan_id')->constrained();
      $table->decimal('amount', 15, 2);
      $table->string('reference'); // from gateway
      $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();
  });

- Charity Ledger (for late penalties routed to sadaqah only)
  PHP
  Schema::create('charity_ledger', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained();
      $table->string('source'); // e.g., 'late_penalty_qard:QH-2026-000123'
      $table->decimal('amount', 15, 2);
      $table->text('note')->nullable();
      $table->timestamps();
  });

- Shariah Audit Logs (immutable intent; application-level append‑only)
  PHP
  Schema::create('shariah_audit_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained();
      $table->string('action'); // e.g., 'create_qard_hasan', 'repay_qard_hasan', 'charity_posting'
      $table->json('payload');
      $table->timestamps();
  });

Policy Constraints to Enforce in Code
- admin_fee_pct must be <= a cooperative policy cap (e.g., 2%).
- per_installment must equal round(principal_amount / total_installments, 2).
- No interest columns or calculations are permitted anywhere.
- Late penalty, if configured, posts a charity_ledger row and must NOT affect coop income.

2) Backend API (Laravel) — Qard Hasan Controller

- Routes (api.php)
  PHP
  Route::middleware('auth:sanctum')->group(function () {
      Route::get('/qard-hasan', [QardHasanController::class, 'index']);
      Route::post('/qard-hasan', [QardHasanController::class, 'store']);
      Route::post('/qard-hasan/{id}/repay', [QardHasanController::class, 'repay']);
      Route::post('/webhooks/paystack', [WebhookController::class, 'paystack']);
  });

- QardHasanController (illustrative)
  PHP
  class QardHasanController extends Controller
  {
      public function index(Request $request)
      {
          return response()->json($request->user()->qardHasans()->with('repayments')->get());
      }

      public function store(Request $request)
      {
          $data = $request->validate([
              'principal_amount' => ['required','numeric','min:1'],
              'total_installments' => ['required','integer','min:1','max:120'],
              'interval' => ['required','in:Monthly,Weekly'],
              'admin_fee_flat' => ['nullable','numeric','min:0'],
              'admin_fee_pct' => ['nullable','numeric','min:0','max:2'], // example cap 2%
          ]);

          $per = round($data['principal_amount'] / $data['total_installments'], 2);

          $q = QardHasan::create([
              'user_id' => $request->user()->id,
              'qard_id_string' => 'QH-'.now()->format('Y').'-'.str()->upper(str()->random(6)),
              'principal_amount' => $data['principal_amount'],
              'total_installments' => $data['total_installments'],
              'per_installment' => $per,
              'interval' => $data['interval'],
              'admin_fee_flat' => $data['admin_fee_flat'] ?? 0,
              'admin_fee_pct' => $data['admin_fee_pct'] ?? 0,
          ]);

          ShariahAudit::log($request->user(), 'create_qard_hasan', $q->toArray());
          return response()->json($q, 201);
      }

      public function repay(Request $request, int $id)
      {
          $q = QardHasan::where('user_id', $request->user()->id)->findOrFail($id);
          $data = $request->validate(['amount' => ['required','numeric','min:1']]);

          // Gateways (Paystack/Flutterwave) collect the payment; we mark pending here.
          $rep = $q->repayments()->create([
              'amount' => $data['amount'],
              'reference' => 'QH_'.str()->upper(str()->random(10)),
              'status' => 'pending',
          ]);

          ShariahAudit::log($request->user(), 'initiate_qard_repayment', $rep->toArray());
          return response()->json($rep, 201);
      }
  }

- WebhookController (repayment posting + optional late penalty to charity)
  PHP
  public function paystack(Request $request)
  {
      // Verify signature, then locate repayment by reference
      $reference = data_get($request->input('data'), 'reference');
      $amount = (float) data_get($request->input('data'), 'amount') / 100.0; // kobo->naira

      $rep = QardHasanRepayment::where('reference', $reference)->first();
      if (!$rep) return response()->json(['ok' => true]);

      DB::transaction(function() use ($rep, $amount) {
          $rep->update(['status' => 'success', 'paid_at' => now()]);
          $q = $rep->qardHasan()->lockForUpdate()->first();
          $q->paid_amount = round($q->paid_amount + $amount, 2);
          if ($q->paid_amount >= ($q->principal_amount + $q->admin_fee_flat + round($q->principal_amount * ($q->admin_fee_pct/100), 2))) {
              $q->status = 'paid';
          }
          $q->save();
          ShariahAudit::log(optional($q->user), 'repay_qard_hasan', ['qard' => $q->qard_id_string, 'amount' => $amount]);
      });

      return response()->json(['ok' => true]);
  }

Note: If you implement late penalties for missed schedules, post them to charity_ledger and never to coop income.

3) Frontend (Vue 3 + Tailwind) — Qard Hasan UI and Disclosures

- Replace generic “Loan” wording with “Qard Hasan (Interest‑Free)”.
- Display a Disclosure box on creation and payment screens:
  Text example: “This is a Qard Hasan (benevolent loan) with 0% interest (no riba). Any optional administrative fee is only to cover costs and is capped per policy. If a late penalty applies, it will be donated to charity (sadaqah) and not treated as cooperative income.”

- LoanCard adaptation
  Vue
  <template>
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-6">
      <div class="p-4 bg-slate-50 flex justify-between items-center border-b border-slate-100">
        <div>
          <span class="text-green-700 font-black text-lg">#{{ index + 1 }}</span>
          <h3 class="inline ml-2 font-bold text-slate-800 text-lg">Qard Hasan</h3>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">ID: {{ q.qard_id_string }}</p>
        </div>
        <span :class="q.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-500'" class="px-3 py-1 rounded-full text-[10px] font-black uppercase">{{ q.status }}</span>
      </div>
      <div class="p-5 grid grid-cols-2 gap-y-4 gap-x-2">
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase">Principal</p>
          <p class="font-black text-slate-800 tracking-tighter">₦ {{ q.principal_amount.toLocaleString() }}</p>
        </div>
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase">Installments</p>
          <p class="font-black text-slate-800">{{ q.total_installments }}</p>
        </div>
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase">Per Installment</p>
          <p class="font-black text-slate-800">₦ {{ q.per_installment.toLocaleString() }}</p>
        </div>
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase">Admin Fee (Total)</p>
          <p class="font-black text-slate-800">₦ {{ (q.admin_fee_flat + (q.principal_amount * (q.admin_fee_pct/100))).toFixed(2) }}</p>
        </div>
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase">Paid</p>
          <p class="font-black text-green-600 text-lg">₦ {{ q.paid_amount.toLocaleString() }}</p>
        </div>
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase text-right">Balance</p>
          <p class="font-black text-red-600 text-lg text-right">₦ {{ (q.principal_amount + q.admin_fee_flat + (q.principal_amount * (q.admin_fee_pct/100)) - q.paid_amount).toFixed(2) }}</p>
        </div>
      </div>
      <div class="px-5 pb-2 text-xs text-slate-500">
        <p>Qard Hasan is interest‑free. Any late penalty is donated to charity (not coop income).</p>
      </div>
      <div class="p-4 pt-0 flex gap-3">
        <button class="flex-grow bg-slate-100 text-slate-700 py-3 rounded-2xl font-bold text-xs uppercase">Details</button>
        <button @click="$router.push('/pay')" class="flex-grow bg-green-700 text-white py-3 rounded-2xl font-bold text-xs uppercase shadow-lg shadow-green-100">Make Payment</button>
      </div>
    </div>
  </template>

- Payment Page Guardrails
  JavaScript
  const totalDue = computed(() => (q.principal_amount + q.admin_fee_flat + (q.principal_amount * (q.admin_fee_pct/100))) - q.paid_amount);
  // UI must not show or compute any interest. Validate client+server side.

- Optional Modules for Community Giving
  - Toggle to add Sadaqah during checkout.
  - Zakat calculator (separate feature) — not tied to loan balances.

4) Reliability and Compliance Operations
- Shariah Board: Maintain a simple table of board members and policy caps for admin_fee_pct and late penalties.
- Append‑only Audit: shariah_audit_logs must be append‑only at the application layer. Do not expose update/delete in admin UI.
- Charity Disbursement: Admin UI should enable exporting charity_ledger rows as CSV/PDF and marking disbursement details; funds never mingle with operating income.

5) Mobile and Web Builds (Capacitor + Vite + Laravel)

Web (Local Dev)
- Backend: cd backend; cp .env.example .env; set DB creds; composer install; php artisan key:generate; php artisan migrate; php artisan serve
- Frontend: cd frontend; npm i; npm run dev

Web (Production)
- Backend: Deploy Laravel 11 with PHP‑FPM + Nginx, MySQL/Postgres, Redis; enable HTTPS; run migrations; set APP_KEY; configure Paystack/Flutterwave webhooks.
- Frontend: npm run build; serve dist/ via Nginx or behind the Laravel app’s public/ (as a separate static site or single‑page app).

Mobile (Android/iOS)
- Prereq: Capacitor deps already present.
- Steps:
  1) cd frontend
  2) npm i
  3) npx cap init coop-app com.example.coopapp
  4) npm run build
  5) npx cap add android
  6) npx cap add ios  // on macOS
  7) npx cap sync
  8) npx cap open android  // build .aab in Android Studio
  9) npx cap open ios      // build .ipa in Xcode
- Configure environment: point API base URL to production HTTPS for release builds.

6) Security & Governance Checklist
- Auth: Laravel Sanctum tokens for mobile; short‑lived tokens; device binding if needed.
- HTTPS everywhere: Webhooks require HTTPS in production.
- Input validation: Enforce zero interest and admin fee caps server‑side.
- Logging: Audit all balance‑affecting events (create/repay qard, charity postings).
- Backups: Daily DB snapshots; secure access to charity and audit logs.

7) Roadmap of Shariah‑Compliant Features
- Phase 1: Qard Hasan + Contributions + Passbook + Webhooks (current document covers this)
- Phase 2: Profit‑sharing savings (Mudarabah/Musharakah) — dividends via “profit_distributions” tables and statements, not interest.
- Phase 3: Community marketplace classifieds with escrow (Shariah‑reviewed) — optional.

By following this addendum, the cooperative remains fully interest‑free while delivering a modern, high‑quality web and mobile experience aligned with Islamic finance principles.



## Docker and Mobile Deployment
For instructions on building and pushing Docker images and deploying the mobile app (Android/iOS), see DEPLOYMENT.md.

\n## Payments with Paystack\n\nBackend implements Paystack payments and webhooks.\n\nEnvironment variables (backend/.env):\n- PAYSTACK_PUBLIC_KEY=pk_test_xxx\n- PAYSTACK_SECRET_KEY=sk_test_xxx\n\nInitiate a payment (authenticated):\n- POST /api/initiate-payment\n- Body: {\n  "items": [\n    {"scheme_id": 1, "amount": 5000},\n    {"scheme_id": 2, "amount": 2000}\n  ],\n  "callback_url": "https://your-frontend.app/pay/complete" // optional\n}\n- Response: { authorization_url, checkout_url, access_code, reference, total }\n\nWebhook (configure in Paystack Dashboard):\n- URL: https://your-backend.app/api/webhooks/paystack\n- Method: POST\n- Secret: uses PAYSTACK_SECRET_KEY for signature verification (X-Paystack-Signature)\n\nFlow:\n- The initiate endpoint pre-creates pending Contribution rows per selected scheme and calls Paystack initialize.\n- When Paystack sends charge.success, the webhook verifies signature and calls /transaction/verify/{reference}.\n- On success, it marks those Contribution rows as success and credits the member's wallet balance (users.balance) with the total amount.\n- Idempotent: repeated webhooks will not double-credit because pending rows are switched to success on first processing.\n\nFrontend:\n- Redirect the user to authorization_url (or checkout_url alias) to complete payment.\n- After payment, use the callback to show result; server-side allocation already handled by the webhook.\n

## Wallet Funding via Paystack Dedicated Virtual Accounts (DVA)

Backend now supports:
- Assigning a Paystack Dedicated Virtual Account to each member.
- Automatic wallet top-ups on successful bank transfers to the DVA (via Paystack webhook).
- Allocating wallet funds across schemes with automatic passbook entries and wallet debits.

Requirements:
- Set PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY in backend/.env.
- Set your Paystack webhook URL in the Paystack Dashboard to: {APP_URL}/api/webhooks/paystack

Migrations:
- Run: ./vendor/bin/sail artisan migrate (or php artisan migrate) to create tables/columns.

API Endpoints (auth required unless stated otherwise):
- POST /api/virtual-account/assign → Creates Paystack customer if needed and assigns DVA. Optional body: { preferred_bank: "wema-bank" | "titan-paystack", phone: "..." }
- GET  /api/virtual-account       → Returns assigned virtual account details.
- GET  /api/wallet                → Returns balance, virtual account info, and 10 recent transactions.
- GET  /api/wallet/transactions   → Paginated wallet transactions. Query: ?page=1&per_page=15&type=credit|debit
- POST /api/wallet/allocate       → Allocates wallet to schemes. Body: { items: [{ scheme_id, amount }, ...] }
- POST /api/webhooks/paystack     → Public webhook, signature-verified.

Behavior notes:
- Paying by card via /initiate-payment creates pending scheme contributions and, on success, marks them as success (passbook). It does NOT credit the wallet to avoid double counting.
- DVA bank transfers (Paystack charge.success with channel=bank_transfer and no pending contributions for the reference) credit the user wallet and create a WalletTransaction credit.
- All wallet allocations generate Contribution records with status=success and a matching WalletTransaction debit for auditability.



## Local Paystack Webhooks with ngrok (Local Development)

Use ngrok to receive Paystack webhooks on your local machine over HTTPS.

Prerequisites
- Docker Desktop installed and running
- backend/.env configured with your Paystack keys (PAYSTACK_PUBLIC_KEY, PAYSTACK_SECRET_KEY)
- A free ngrok account (to obtain NGROK_AUTHTOKEN)

Steps
1) Set your ngrok auth token
   - Edit backend/.env and set: NGROK_AUTHTOKEN=your-ngrok-token
   - If backend/.env doesn’t exist yet, copy from backend/.env.example first.

2) Start Laravel and ngrok (from the backend directory)
   - docker compose up -d laravel.test
   - docker compose up -d ngrok

3) Get your public ngrok URL
   - Open http://localhost:4040/api/tunnels
   - Copy the https public_url (e.g., https://abcd-1234.ngrok-free.app)

4) Configure Paystack Webhook
   - In your Paystack Dashboard → Settings → API → Webhooks
   - Set URL to: https://YOUR_NGROK_SUBDOMAIN.ngrok-free.app/api/webhooks/paystack
   - Save

5) Test
   - Make a test payment (via the app’s Make Payment or Wallet Top-up)
   - Observe deliveries in:
     - Ngrok inspector: http://localhost:4040
     - Laravel logs: docker compose logs -f laravel.test

Notes
- The webhook endpoint is already implemented at POST /api/webhooks/paystack and verifies X-Paystack-Signature using PAYSTACK_SECRET_KEY.
- If you see “Invalid Signature”, ensure you’re using the correct Paystack secret (Test vs Live) for the mode you’re testing in.
- Ngrok URLs change when the container restarts; update the webhook URL in Paystack accordingly. For a stable URL, use a reserved ngrok domain (paid feature) and adjust the ngrok command accordingly.


## Mobile Biometric Login (Capacitor)

This app supports optional biometric quick login (Fingerprint/Face ID) on mobile builds. Members can enable it after a successful password login, and then use "Quick Login with Biometrics" on subsequent logins.

How it works
- After a normal login, if the device supports biometrics, the user can tick "Remember me with biometrics" to securely store their membership + branch and password in the device's biometric vault.
- On the login screen, if stored credentials exist, a "Quick Login with Biometrics" button will appear. Tapping it will prompt biometrics and sign in automatically.

Setup (one-time per platform)
1) Install deps (already in package.json). If you pulled fresh or changed deps:
   - cd frontend
   - npm install
2) Sync native projects with Capacitor so the biometric plugin is installed natively:
   - npx cap sync
3) Open and build/run the native project:
   - npx cap open android
   - npx cap open ios

Notes
- Web builds gracefully hide biometric options.
- On Android emulators without biometrics enrolled, enable biometrics/enroll in emulator settings.
- The app does not delete biometric credentials on logout so users can use Quick Login next time. If you need to clear, remove the device credentials from system settings or implement a “Forget biometrics on this device” in Settings.
# coop_app



## KYC 2.0: Automated BVN + Face Verification
This release adds automated BVN and face verification during member signup to eliminate ghost members and reduce loan fraud.

- Flow (backend endpoints):
  - POST /api/register/start → returns token
  - POST /api/register/upload → upload passport (selfie), ID card, proof of address
  - POST /api/register/send-otps → email/SMS OTPs
  - POST /api/register/verify-email → verify email code
  - POST /api/register/verify-sms → verify SMS code
  - POST /api/register/finalize → now requires: token, bvn (11 digits). Performs BVN + face match before creating user.

- Providers:
  - mock (default): accepts even-ending BVNs, for local/dev.
  - dojah: real BVN/face verification via Dojah.

- Configuration (backend/.env):
  - KYC_PROVIDER=mock | dojah
  - KYC_FACE_MATCH_MIN=0.82
  - DOJAH_APP_ID= (if provider=dojah)
  - DOJAH_SECRET= (if provider=dojah)
  - DOJAH_BASE_URL=https://api.dojah.io (default)

- Storage:
  - On success, User is saved with bvn, bvn_verified_at, and dva_verification_meta (JSON with provider, status, score, meta).

- Notes:
  - Selfie/Passport image path from upload is used as the live face. Reference image is taken from provider BVN record if available, else the uploaded ID image is used for comparison.

