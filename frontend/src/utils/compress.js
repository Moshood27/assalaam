export async function compressImage(file, { maxKB = 2000, maxWidth = 1920, maxHeight = 1920 } = {}) {
  const dataUrl = await readAsDataURL(file)
  const img = await loadImage(dataUrl)
  const { canvas, ctx, targetW, targetH } = createCanvasToFit(img, maxWidth, maxHeight)
  ctx.drawImage(img, 0, 0, targetW, targetH)

  // Try decreasing quality until under size or quality floor reached
  let quality = 0.9
  let blob = await canvasToBlob(canvas, 'image/jpeg', quality)
  while (blob.size > maxKB * 1024 && quality > 0.5) {
    quality -= 0.1
    blob = await canvasToBlob(canvas, 'image/jpeg', quality)
  }
  return blob
}

function readAsDataURL(file) {
  return new Promise((resolve, reject) => {
    const fr = new FileReader()
    fr.onload = () => resolve(fr.result)
    fr.onerror = reject
    fr.readAsDataURL(file)
  })
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = reject
    img.src = src
  })
}

function createCanvasToFit(img, maxW, maxH) {
  const ratio = Math.min(maxW / img.width, maxH / img.height, 1)
  const targetW = Math.round(img.width * ratio)
  const targetH = Math.round(img.height * ratio)
  const canvas = document.createElement('canvas')
  canvas.width = targetW
  canvas.height = targetH
  const ctx = canvas.getContext('2d')
  ctx.imageSmoothingEnabled = true
  ctx.imageSmoothingQuality = 'high'
  return { canvas, ctx, targetW, targetH }
}

function canvasToBlob(canvas, type = 'image/jpeg', quality = 0.9) {
  return new Promise((resolve) => canvas.toBlob((b) => resolve(b), type, quality))
}
