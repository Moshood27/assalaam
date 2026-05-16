import axios from '../http.js'

export async function checkAppStatus() {
  try {
    const { data } = await axios.get('/api/status', { 
      // Ensure we don't use a cached response for health/status
      headers: { 'Cache-Control': 'no-cache' } 
    })
    
    const currentVersion = import.meta.env.VITE_APP_VERSION || '1.0.0'
    const minVersion = data.mobile_min_version || '1.0.0'
    const currentRecommendedVersion = data.mobile_current_version || minVersion
    const maintenanceMode = data.maintenance_mode === true
    
    // Forced Update only applies to native mobile apps
    const isNative = typeof window !== 'undefined' && !!(window?.Capacitor?.isNativePlatform?.() || (window?.Capacitor?.getPlatform && window.Capacitor.getPlatform() !== 'web'))
    
    // Simple semver compare (assuming x.y.z format)
    const isOutdated = isNative && compareVersions(currentVersion, minVersion) < 0
    const isUpdateAvailable = isNative && !isOutdated && compareVersions(currentVersion, currentRecommendedVersion) < 0

    return {
      maintenanceMode,
      maintenanceMessage: data.maintenance_message,
      maintenanceUntil: data.maintenance_until,
      systemAnnouncement: data.system_announcement,
      isOutdated,
      isUpdateAvailable,
      currentVersion: currentRecommendedVersion,
      playStoreUrl: data.play_store_url,
      paymentGateways: data.payment_gateways
    }
  } catch (error) {
    console.error('Failed to check app status:', error)
    // If we get a 503 Service Unavailable, it likely means Laravel's native maintenance mode is active.
    if (error?.response?.status === 503) {
      return { 
        maintenanceMode: true, 
        maintenanceMessage: 'The server is currently undergoing maintenance. Please try again later.',
        isOutdated: false 
      }
    }
    // On network failure, we assume everything is fine to avoid blocking the user
    // unless it's a critical safety feature that requires blocking on error.
    return { maintenanceMode: false, isOutdated: false }
  }
}

function compareVersions(v1, v2) {
  const parts1 = v1.split('.').map(Number)
  const parts2 = v2.split('.').map(Number)
  for (let i = 0; i < 3; i++) {
    const a = parts1[i] || 0
    const b = parts2[i] || 0
    if (a > b) return 1
    if (a < b) return -1
  }
  return 0
}
