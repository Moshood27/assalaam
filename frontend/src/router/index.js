import { createRouter, createWebHistory, createWebHashHistory } from 'vue-router'

// Views (lazy-loaded)
const Login = () => import('../views/Login.vue')
import Dashboard from '../views/Dashboard.vue'
const MakePayment = () => import('../views/MakePayment.vue')
const Wallet = () => import('../views/Wallet.vue')
const Passbook = () => import('../views/Passbook.vue')
const Loans = () => import('../views/Loans.vue')
const Settings = () => import('../views/Settings.vue')
const Profile = () => import('../views/Profile.vue')
const Reports = () => import('../views/Reports.vue')
const QardHasan = () => import('../components/QardHasan.vue')
const WalletCallback = () => import('../views/WalletCallback.vue')
const PaymentCallback = () => import('../views/PaymentCallback.vue')
const VTU = () => import('../views/VTU.vue')
const VTUHistory = () => import('../views/VTUHistory.vue')
const Agm = () => import('../views/Agm.vue')
const AgmSession = () => import('../views/AgmSession.vue')
const ProjectProposals = () => import('../views/ProjectProposals.vue')
const ProjectProposalDetail = () => import('../views/ProjectProposalDetail.vue')
const ShariaBoard = () => import('../views/ShariaBoard.vue')
const TahkimHistory = () => import('../views/TahkimHistory.vue')
const Store = () => import('../views/Store.vue')
const OrderReceipt = () => import('../views/OrderReceipt.vue')
const Privacy = () => import('../views/Privacy.vue')
const Policy = () => import('../views/Policy.vue')
const Support = () => import('../views/Support.vue')
const Projects = () => import('../views/Projects.vue')
const Project = () => import('../views/Project.vue')
const Takaful = () => import('../views/Takaful.vue')
const Transparency = () => import('../views/Transparency.vue')
const Sadaqah = () => import('../views/Sadaqah.vue')
const SadaqahDetail = () => import('../views/SadaqahDetail.vue')
const SadaqahHistory = () => import('../views/SadaqahHistory.vue')
const MerchantReceive = () => import('../views/MerchantReceive.vue')
const MerchantPay = () => import('../views/MerchantPay.vue')
const VendorApply = () => import('../views/VendorApply.vue')
const VendorDashboard = () => import('../views/VendorDashboard.vue')
const VendorProducts = () => import('../views/VendorProducts.vue')
const VendorOrders = () => import('../views/VendorOrders.vue')
const VendorSettlements = () => import('../views/VendorSettlements.vue')
const Wasiyyah = () => import('../views/Wasiyyah.vue')
const JuniorCooperative = () => import('../views/JuniorCooperative.vue')
const GoldSavings = () => import('../views/GoldSavings.vue')

const AdminLogin = () => import('../views/admin/AdminLogin.vue')
const AdminRegister = () => import('../views/admin/AdminRegister.vue')
const AdminForgot = () => import('../views/admin/AdminForgotPassword.vue')
const AdminImports = () => import('../views/admin/AdminImports.vue')
const AdminVTU = () => import('../views/admin/AdminVTU.vue')
const AdminProducts = () => import('../views/admin/AdminProducts.vue')
const AdminVendors = () => import('../views/admin/AdminVendors.vue')
const AdminTakaful = () => import('../views/admin/AdminTakaful.vue')

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/onboarding', name: 'onboarding', component: () => import('../views/Onboarding.vue'), meta: { guest: true, skipOnboarding: true } },
  { path: '/login', name: 'login', component: Login, meta: { guest: true } },
  { path: '/forgot', name: 'forgot', component: () => import('../views/ForgotPassword.vue'), meta: { guest: true, skipOnboarding: true } },
  { path: '/register', name: 'register', component: () => import('../views/MemberRegister.vue'), meta: { guest: true, skipOnboarding: true } },
  { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/wallet', name: 'wallet', component: Wallet, meta: { requiresAuth: true } },
  { path: '/pay', name: 'pay', component: MakePayment, meta: { requiresAuth: true } },
  { path: '/passbook', name: 'passbook', component: Passbook, meta: { requiresAuth: true } },
  { path: '/reports', name: 'reports', component: Reports, meta: { requiresAuth: true } },
  { path: '/takaful', name: 'takaful', component: Takaful, meta: { requiresAuth: true } },
  { path: '/settings', name: 'settings', component: Settings, meta: { requiresAuth: true } },
  { path: '/profile', name: 'profile', component: Profile, meta: { requiresAuth: true } },
  { path: '/store', name: 'store', component: Store, meta: { requiresAuth: true } },
  // Merchant QR pay
  { path: '/merchant/receive', name: 'merchant.receive', component: MerchantReceive, meta: { requiresAuth: true } },
  { path: '/merchant/pay', name: 'merchant.pay', component: MerchantPay, meta: { requiresAuth: true } },
  { path: '/store/orders', name: 'store.orders', component: () => import('../views/StoreOrders.vue'), meta: { requiresAuth: true } },
  { path: '/store/orders/:id', name: 'store.order', component: OrderReceipt, meta: { requiresAuth: true } },

  // Vendor Portal
  { path: '/vendor/apply', name: 'vendor.apply', component: VendorApply, meta: { requiresAuth: true } },
  { path: '/vendor/dashboard', name: 'vendor.dashboard', component: VendorDashboard, meta: { requiresAuth: true } },
  { path: '/vendor/products', name: 'vendor.products', component: VendorProducts, meta: { requiresAuth: true } },
  { path: '/vendor/orders', name: 'vendor.orders', component: VendorOrders, meta: { requiresAuth: true } },
  { path: '/vendor/settlements', name: 'vendor.settlements', component: VendorSettlements, meta: { requiresAuth: true } },

  { path: '/wasiyyah', name: 'wasiyyah', component: Wasiyyah, meta: { requiresAuth: true } },
  { path: '/junior-cooperative', name: 'junior.cooperative', component: JuniorCooperative, meta: { requiresAuth: true } },
  { path: '/gold', name: 'gold', component: GoldSavings, meta: { requiresAuth: true } },

  { path: '/goals', name: 'goals', component: () => import('../views/Goals.vue'), meta: { requiresAuth: true } },
  { path: '/projects', name: 'projects', component: Projects, meta: { requiresAuth: true } },
  { path: '/projects/:id', name: 'project', component: Project, meta: { requiresAuth: true } },
  { path: '/sadaqah', name: 'sadaqah', component: Sadaqah, meta: { requiresAuth: true } },
  { path: '/sadaqah/history', name: 'sadaqah.history', component: SadaqahHistory, meta: { requiresAuth: true } },
  { path: '/sadaqah/:id', name: 'sadaqah.detail', component: SadaqahDetail, meta: { requiresAuth: true } },
  { path: '/transparency', name: 'transparency', component: Transparency, meta: { requiresAuth: true } },
  // VTU
  { path: '/vtu', name: 'vtu', component: VTU, meta: { requiresAuth: true } },
  { path: '/vtu/history', name: 'vtu.history', component: VTUHistory, meta: { requiresAuth: true } },
  // AGM Voting
  { path: '/agm', name: 'agm', component: Agm, meta: { requiresAuth: true } },
  { path: '/agm/sessions/:id', name: 'agm.session', component: AgmSession, meta: { requiresAuth: true } },
  { path: '/agm/proposals', name: 'agm.proposals', component: ProjectProposals, meta: { requiresAuth: true } },
  { path: '/agm/proposals/:id', name: 'agm.proposal_detail', component: ProjectProposalDetail, meta: { requiresAuth: true } },
  { path: '/sharia-board', name: 'sharia.board', component: ShariaBoard, meta: { requiresAuth: true } },
  { path: '/sharia-board/history', name: 'sharia.board.history', component: TahkimHistory, meta: { requiresAuth: true } },
  // Placeholder: use existing Qard Hasan prototype under /loans for now
  { path: '/loans', name: 'loans', component: Loans, meta: { requiresAuth: true } },
  { path: '/qard', name: 'qard', component: QardHasan },

  // Public info pages
  { path: '/privacy', name: 'privacy', component: Privacy, meta: { skipOnboarding: true } },
  { path: '/policy', name: 'policy', component: Policy, meta: { skipOnboarding: true } },
  { path: '/support', name: 'support', component: Support, meta: { skipOnboarding: true } },

  // Paystack callbacks
  { path: '/wallet-callback', name: 'wallet.callback', component: WalletCallback, meta: { skipOnboarding: true } },
  { path: '/payment-callback', name: 'payment.callback', component: PaymentCallback, meta: { skipOnboarding: true } },

  // Admin auth (Vue-based)
  { path: '/admin/login', name: 'admin.login', component: AdminLogin, meta: { guest: true } },
  { path: '/admin/register', name: 'admin.register', component: AdminRegister, meta: { guest: true } },
  { path: '/admin/forgot', name: 'admin.forgot', component: AdminForgot, meta: { guest: true } },
  { path: '/admin/imports', name: 'admin.imports', component: AdminImports, meta: { requiresAdmin: true } },
  { path: '/admin/vtu', name: 'admin.vtu', component: AdminVTU, meta: { requiresAdmin: true } },
  { path: '/admin/products', name: 'admin.products', component: AdminProducts, meta: { requiresAdmin: true } },
  { path: '/admin/vendors', name: 'admin.vendors', component: AdminVendors, meta: { requiresAdmin: true } },
  { path: '/admin/takaful', name: 'admin.takaful', component: AdminTakaful, meta: { requiresAdmin: true } },
]

const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))
const history = isNative ? createWebHashHistory() : createWebHistory(import.meta.env.BASE_URL)

const router = createRouter({
  history,
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const adminToken = localStorage.getItem('admin_token')

  // 0. Onboarding gate for first-time users (skip for admin and explicit skips)
  try {
    const hasSeen = localStorage.getItem('has_seen_onboarding') === 'true'
    const isAdminRoute = to.path?.startsWith('/admin')
    const isOnboarding = to.name === 'onboarding'
    const skip = !!to.meta?.skipOnboarding
    const isAuthed = !!localStorage.getItem('token')
    if (!hasSeen && !isAdminRoute && !isOnboarding && !skip && !isAuthed) {
      return { name: 'onboarding', query: { redirect: to.fullPath } }
    }
  } catch (_) {}

  if (to.meta.requiresAuth && !token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresAdmin && !adminToken) {
    return { name: 'admin.login', query: { redirect: to.fullPath } }
  }
  if (to.meta.guest && token) {
    return { name: 'dashboard' }
  }
  // allow navigation
  return true
})

router.onError((error, to) => {
  if (error.message.includes('Failed to fetch dynamically imported module') || 
      error.message.includes('Importing a module script failed')) {
    window.location.reload()
  }
})

export default router
