import { reactive } from 'vue'

// Simple global modal store and helpers
const state = reactive({
  open: false,
  title: '',
  message: '',
  size: 'md',
  // actions: [{ label: 'OK', primary: true, handler: () => {} }]
  actions: [],
  type: 'alert', // 'alert', 'confirm', 'prompt', 'promptText'
  inputValue: '',
  inputPlaceholder: '',
})

let resolver = null

function show(opts = {}) {
  state.title = opts.title || ''
  state.message = opts.message || ''
  state.size = opts.size || 'md'
  state.actions = Array.isArray(opts.actions) ? opts.actions : []
  state.type = opts.type || 'alert'
  state.inputValue = opts.inputValue || ''
  state.inputPlaceholder = opts.inputPlaceholder || ''
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
      type: 'alert',
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
      type: 'confirm',
      actions: [
        { label: cancelText, handler: () => close(false) },
        { label: confirmText, primary: true, handler: () => close(true) },
      ],
    })
  })
}

function prompt(title, message, options = []) {
  return new Promise(resolve => {
    resolver = resolve
    show({
      title,
      message,
      type: 'prompt',
      actions: options.map(opt => ({
        ...opt,
        handler: () => close(opt.value)
      }))
    })
  })
}

function promptText(title, message, { placeholder = '', initialValue = '' } = {}) {
  return new Promise(resolve => {
    resolver = resolve
    show({
      title,
      message,
      type: 'promptText',
      inputValue: initialValue,
      inputPlaceholder: placeholder,
      actions: [
        { label: 'Cancel', handler: () => close(null) },
        { label: 'Submit', primary: true, handler: () => close(state.inputValue) },
      ]
    })
  })
}

export function useModal() {
  return { state, show, close, alert, confirm, prompt, promptText }
}
