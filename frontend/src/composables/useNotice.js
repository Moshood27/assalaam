import { ref } from 'vue'

// Shared notice state and helpers for VTU-style custom notice modal
const notice = ref({ visible: false, type: 'info', title: '', message: '' })

function showNotice(title, message, type = 'info') {
  notice.value = { visible: true, type, title, message }
}

function closeNotice() {
  notice.value.visible = false
}

export function useNotice() {
  return { notice, showNotice, closeNotice }
}


