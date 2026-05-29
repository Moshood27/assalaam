// Detect native (Capacitor) environment for mobile-safe downloads
export const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))

/**
 * Robust blob opener that works in web and most mobile webviews
 * @param {Blob} blob
 * @param {string} filename
 */
export const openBlob = (blob, filename) => {
  try {
    const href = window.URL.createObjectURL(blob)

    // 1) Standard approach (works on modern browsers)
    const link = document.createElement('a')
    link.href = href
    link.download = filename
    link.rel = 'noopener'
    link.target = isNative ? '_blank' : '_self'
    document.body.appendChild(link)
    link.click()
    link.remove()

    // 2) Mobile webview fallbacks
    if (isNative) {
      // a) Try opening the blob URL directly in a new context
      setTimeout(() => {
        try { window.open(href, '_blank') } catch (_) {}
      }, 150)

      // b) As a stronger fallback, convert to a data URL and navigate/open
      setTimeout(() => {
        try {
          const reader = new FileReader()
          reader.onload = () => {
            const dataUrl = reader.result // data:application/pdf;base64,...
            try { window.open(dataUrl, '_blank') } catch (_) {}
            try { if (!document.hidden) window.location.href = dataUrl } catch (_) {}

            // Hidden iframe fallback
            try {
              const iframe = document.createElement('iframe')
              iframe.style.display = 'none'
              iframe.src = dataUrl
              document.body.appendChild(iframe)
              setTimeout(() => { try { document.body.removeChild(iframe) } catch (_) {} }, 15000)
            } catch (_) {}
          }
          reader.readAsDataURL(blob)
        } catch (_) {}
      }, 350)

      // Revoke after a delay to allow the viewer to read the blob
      setTimeout(() => { try { window.URL.revokeObjectURL(href) } catch (_) {} }, 15000)
    } else {
      window.URL.revokeObjectURL(href)
    }
  } catch (err) {
    console.error('openBlob failed', err)
    alert('Unable to open the file on this device. Please try again or update the app.')
  }
}


