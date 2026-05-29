import { driver } from 'driver.js'
import 'driver.js/dist/driver.css'

export const startDashboardTour = () => {
  try {
    if (localStorage.getItem('dashboard_tour_seen')) return

    const driverObj = driver({
      showProgress: true,
      animate: true,
      nextBtnText: 'Next —>',
      prevBtnText: '<— Back',
      doneBtnText: 'Got it!',
      steps: [
        {
          element: '#balance-card',
          popover: {
            title: 'Your Wallet',
            description: 'This is your total savings. You can top it up anytime.',
            side: 'bottom',
            align: 'start',
          },
        },
        {
          element: '#loan-btn',
          popover: {
            title: 'Need a Loan?',
            description: 'Apply for interest-free loans directly from this button.',
            side: 'top',
            align: 'center',
          },
        },
        {
          element: '#nav-profile',
          popover: {
            title: 'Your Settings',
            description: 'Enable Biometrics and update your profile here.',
            side: 'top',
            align: 'end',
          },
        },
      ],
      onDestroyed: () => {
        localStorage.setItem('dashboard_tour_seen', 'true')
      },
      onCloseClick: () => {
        localStorage.setItem('dashboard_tour_seen', 'true')
      },
    })

    driverObj.drive()
  } catch (e) {
    // Fail silently if anything goes wrong so it doesn't affect the app
    console.warn('Dashboard tour failed to start:', e)
  }
}


