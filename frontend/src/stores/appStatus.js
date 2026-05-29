import { defineStore } from 'pinia'

export const useAppStatusStore = defineStore('appStatus', {
  state: () => ({
    maintenanceMode: false,
    maintenanceMessage: '',
    maintenanceUntil: '',
    systemAnnouncement: '',
    isOutdated: false,
    isUpdateAvailable: false,
    currentVersion: '',
    playStoreUrl: '',
    paymentGateways: {
      paystack: true,
      flutterwave: true,
      monnify: true,
      opay: true,
      primary: 'paystack'
    },
    features: {}
  }),
  actions: {
    setFeatures(features) {
      this.features = features || {}
    },
    setStatus(status) {
      this.maintenanceMode = status.maintenanceMode
      this.maintenanceMessage = status.maintenanceMessage
      this.maintenanceUntil = status.maintenanceUntil
      this.systemAnnouncement = status.systemAnnouncement
      this.isOutdated = status.isOutdated
      this.isUpdateAvailable = status.isUpdateAvailable
      this.currentVersion = status.currentVersion
      this.playStoreUrl = status.playStoreUrl
      if (status.paymentGateways) {
        this.paymentGateways = status.paymentGateways
      }
    }
  }
})


