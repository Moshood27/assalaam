// Simple brand helper for dynamic logos and titles
const slug = (import.meta?.env?.VITE_BRAND_SLUG || 'assalam').toLowerCase()
const defaultName = slug === 'attaqwa' ? 'ATTAQWA CO-OPERATIVE' : 'ASSALAM CO-OPERATIVE'
const name = String(import.meta?.env?.VITE_APP_NAME || defaultName)

const logo = `/images/${slug}-logo.svg`
const darkLogo = `/images/${slug}-logo-dark.svg`
const favicon = `/images/${slug}-favicon.svg`

export const brand = {
  slug,
  name,
  logo,
  darkLogo,
  favicon,
}

export default brand
