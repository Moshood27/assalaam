// Simple brand helper for dynamic logos and titles
const slug = (import.meta?.env?.VITE_BRAND_SLUG || 'assalam').toLowerCase()
const defaultName = slug === 'attaqwa' ? 'ATTAQWA CO-OPERATIVE' : 'ASSALAM CO-OPERATIVE'
const name = String(import.meta?.env?.VITE_APP_NAME || defaultName)

// Respect Vite base for correct paths in Capacitor (base: './') and web (base: '/app/')
const base = import.meta?.env?.BASE_URL || '/'
const prefix = base && base.endsWith('/') ? base : `${base}/`

const logo = `${prefix}images/${slug}-logo.svg`
const darkLogo = `${prefix}images/${slug}-logo-dark.svg`
const favicon = `${prefix}images/${slug}-favicon.svg`

// Theme color for OS chrome (status bar, PWA address bar)
const themeColor = slug === 'attaqwa' ? '#3730a3' /* indigo-700 */ : '#065f46' /* emerald-800 */

const shortName = slug === 'attaqwa' ? 'Attaqwa' : 'Assalam'

export const brand = {
  slug,
  name,
  shortName,
  logo,
  darkLogo,
  favicon,
  themeColor,
}

export default brand
