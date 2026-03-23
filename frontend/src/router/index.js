import { createRouter, createWebHistory } from 'vue-router'

// Views (lazy-loaded)
const Login = () => import('../views/Login.vue')
const Dashboard = () => import('../views/Dashboard.vue')
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
const Store = () => import('../views/Store.vue')
const Privacy = () => import('../views/Privacy.vue')
const Policy = () => import('../views/Policy.vue')
const Support = () => import('../views/Support.vue')

const AdminLogin = () => import('../views/admin/AdminLogin.vue')
const AdminRegister = () => import('../views/admin/AdminRegister.vue')
const AdminForgot = () => import('../views/admin/AdminForgotPassword.vue')
const AdminImports = () => import('../views/admin/AdminImports.vue')
const AdminVTU = () => import('../views/admin/AdminVTU.vue')

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'login', component: Login, meta: { guest: true } },
  { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/wallet', name: 'wallet', component: Wallet, meta: { requiresAuth: true } },
  { path: '/pay', name: 'pay', component: MakePayment, meta: { requiresAuth: true } },
  { path: '/passbook', name: 'passbook', component: Passbook, meta: { requiresAuth: true } },
  { path: '/reports', name: 'reports', component: Reports, meta: { requiresAuth: true } },
  { path: '/settings', name: 'settings', component: Settings, meta: { requiresAuth: true } },
  { path: '/profile', name: 'profile', component: Profile, meta: { requiresAuth: true } },
  { path: '/store', name: 'store', component: Store, meta: { requiresAuth: true } },
  { path: '/goals', name: 'goals', component: () => import('../views/Goals.vue'), meta: { requiresAuth: true } },
  // VTU
  { path: '/vtu', name: 'vtu', component: VTU, meta: { requiresAuth: true } },
  { path: '/vtu/history', name: 'vtu.history', component: VTUHistory, meta: { requiresAuth: true } },
  // AGM Voting
  { path: '/agm', name: 'agm', component: Agm, meta: { requiresAuth: true } },
  { path: '/agm/sessions/:id', name: 'agm.session', component: AgmSession, meta: { requiresAuth: true } },
  // Placeholder: use existing Qard Hasan prototype under /loans for now
  { path: '/loans', name: 'loans', component: Loans, meta: { requiresAuth: true } },
  { path: '/qard', name: 'qard', component: QardHasan },

  // Public info pages
  { path: '/privacy', name: 'privacy', component: Privacy },
  { path: '/policy', name: 'policy', component: Policy },
  { path: '/support', name: 'support', component: Support },

  // Paystack callbacks
  { path: '/wallet-callback', name: 'wallet.callback', component: WalletCallback },
  { path: '/payment-callback', name: 'payment.callback', component: PaymentCallback },

  // Admin auth (Vue-based)
  { path: '/admin/login', name: 'admin.login', component: AdminLogin, meta: { guest: true } },
  { path: '/admin/register', name: 'admin.register', component: AdminRegister, meta: { guest: true } },
  { path: '/admin/forgot', name: 'admin.forgot', component: AdminForgot, meta: { guest: true } },
  { path: '/admin/imports', name: 'admin.imports', component: AdminImports, meta: { requiresAdmin: true } },
  { path: '/admin/vtu', name: 'admin.vtu', component: AdminVTU, meta: { requiresAdmin: true } },
]

const router = createRouter({
  history: createWebHistory('/app/'),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const adminToken = localStorage.getItem('admin_token')
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

export default router
