import { reactive } from 'vue'

// Simple global modal store and helpers
const state = reactive({
  open: false,
  title: '',
  message: '',
  size: 'md',
  // actions: [{ label: 'OK', primary: true, handler: () => {} }]
  actions: [],
})

let resolver = null

function show(opts = {}) {
  state.title = opts.title || ''
  state.message = opts.message || ''
  state.size = opts.size || 'md'
  state.actions = Array.isArray(opts.actions) ? opts.actions : []
  state.open = true
}

function close(value) {
  state.open = false
  const r = resolver
  resolver = null
  if (typeof r === 'function') r(value)
}

function alert(message, title = 'Notice') {
  return new Promise(resolve => {
    resolver = resolve
    show({
      title,
      message,
      actions: [
        {
          label: 'OK',
          primary: true,
          handler: () => close(true),
        },
      ],
    })
  })
}

function confirm(message, { title = 'Confirm', confirmText = 'OK', cancelText = 'Cancel' } = {}) {
  return new Promise(resolve => {
    resolver = resolve
    show({
      title,
      message,
      actions: [
        { label: cancelText, handler: () => close(false) },
        { label: confirmText, primary: true, handler: () => close(true) },
      ],
    })
  })
}

export function useModal() {
  return { state, show, close, alert, confirm }
}
