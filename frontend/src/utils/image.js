// Small helper to build absolute image URLs that work on mobile (Capacitor) and web
// - Accepts values like:
//   - 'upload/file.jpg'            -> https://domain.tld/storage/upload/file.jpg
//   - '/upload/file.jpg'           -> https://domain.tld/upload/file.jpg
//   - 'storage/upload/file.jpg'    -> https://domain.tld/storage/upload/file.jpg
//   - '/storage/upload/file.jpg'   -> https://domain.tld/storage/upload/file.jpg
//   - Absolute URLs (http/https/data/blob) are returned unchanged
// - Uses VITE_API_URL as the base when available.
export const getImageUrl = (path) => {
  if (!path) return ''
  const raw = String(path)
  // Return as-is for absolute URLs or data/blob URIs
  if (/^(https?:|data:|blob:)/i.test(raw)) return raw

  const base = (import.meta?.env?.VITE_API_URL || '').replace(/\/$/, '')

  // If it's already a root-relative path (starts with /), just prepend base if needed
  if (raw.startsWith('/')) {
    return base ? `${base}${raw}` : raw
  }

  // Normalize input path: remove leading slashes
  let clean = raw.replace(/^\/+/, '')

  // Avoid creating /storage/storage/... if caller already provided 'storage/...'
  if (!/^storage\//i.test(clean)) {
    clean = `storage/${clean}`
  }

  // Collapse any accidental duplicate 'storage/storage/'
  clean = clean.replace(/^storage\/storage\//i, 'storage/')

  // If we have a base origin (mobile/prod), prepend it; otherwise return root-relative (dev proxy)
  return base ? `${base}/${clean}` : `/${clean}`
}

export default getImageUrl


