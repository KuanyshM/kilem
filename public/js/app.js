const { bootstrap } = window;

const state = {
  token: null,
  admin: null,
  room: null,
  calibration: {
    points: [],
  },
  catalog: [],
  selectedRug: null,
  rugImage: null,
  tryOn: null,
  showGrid: false,
};

const elements = {
  uploadForm: document.getElementById('roomUploadForm'),
  uploadStatus: document.getElementById('uploadStatus'),
  calibrationSection: document.getElementById('calibration-section'),
  calibrationCanvas: document.getElementById('calibrationCanvas'),
  calibrationHint: document.getElementById('calibrationHint'),
  resetCalibration: document.getElementById('resetCalibration'),
  confirmCalibration: document.getElementById('confirmCalibration'),
  catalogSection: document.getElementById('catalog-section'),
  catalogStats: document.getElementById('catalogStats'),
  catalogGrid: document.getElementById('rugCatalog'),
  emptyCatalog: document.getElementById('emptyCatalog'),
  tryOnSection: document.getElementById('tryon-section'),
  tryOnCanvas: document.getElementById('tryOnCanvas'),
  gridOverlay: document.getElementById('gridOverlay'),
  currentRugSize: document.getElementById('currentRugSize'),
  scaleRange: document.getElementById('scaleRange'),
  scaleValue: document.getElementById('scaleValue'),
  scaleYGroup: document.getElementById('scaleYGroup'),
  scaleYRange: document.getElementById('scaleYRange'),
  scaleYValue: document.getElementById('scaleYValue'),
  rotationRange: document.getElementById('rotationRange'),
  rotationValue: document.getElementById('rotationValue'),
  keepAspectToggle: document.getElementById('keepAspectToggle'),
  fitWidthBtn: document.getElementById('fitWidthBtn'),
  toggleGridBtn: document.getElementById('toggleGridBtn'),
  saveTryOnBtn: document.getElementById('saveTryOnBtn'),
  screenshotBtn: document.getElementById('screenshotBtn'),
  saveStatus: document.getElementById('saveStatus'),
  aspectWarning: document.getElementById('aspectWarning'),
  adminLoginForm: document.getElementById('adminLoginForm'),
  adminLoginStatus: document.getElementById('adminLoginStatus'),
  adminPanel: document.getElementById('adminPanel'),
  adminLogoutBtn: document.getElementById('adminLogoutBtn'),
  addRugForm: document.getElementById('addRugForm'),
  addRugStatus: document.getElementById('addRugStatus'),
  adminRugTable: document.getElementById('adminRugTable').querySelector('tbody'),
  toastContainer: document.getElementById('toastContainer'),
};

const API_HEADERS = {
  'Accept': 'application/json',
};

function authHeaders() {
  return state.token ? { Authorization: `Bearer ${state.token}` } : {};
}

async function apiFetch(url, options = {}) {
  const opts = {
    ...options,
    headers: {
      ...API_HEADERS,
      ...authHeaders(),
      ...(options.headers || {}),
    },
  };

  const response = await fetch(url, opts);
  const contentType = response.headers.get('Content-Type') || '';
  let payload;
  if (contentType.includes('application/json')) {
    payload = await response.json();
  } else {
    payload = await response.text();
  }

  if (!response.ok) {
    const message = payload?.error?.message || response.statusText || 'Ошибка запроса';
    throw new Error(message);
  }

  return payload;
}

function showSection(section) {
  section.classList.remove('d-none');
}

function hideSection(section) {
  section.classList.add('d-none');
}

function resetCalibrationCanvas() {
  const ctx = elements.calibrationCanvas.getContext('2d');
  ctx.clearRect(0, 0, elements.calibrationCanvas.width, elements.calibrationCanvas.height);
  if (state.room?.image) {
    drawCalibrationCanvas();
  }
  state.calibration.points = [];
  updateCalibrationHint('Выберите две точки на изображении.');
}

function updateCalibrationHint(text, variant = 'muted') {
  elements.calibrationHint.textContent = text;
  elements.calibrationHint.className = `small text-${variant}`;
}

function drawCalibrationCanvas() {
  const canvas = elements.calibrationCanvas;
  const ctx = canvas.getContext('2d');
  const room = state.room;
  if (!room?.image) {
    return;
  }
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(room.image, 0, 0, canvas.width, canvas.height);

  ctx.strokeStyle = '#ff6f61';
  ctx.fillStyle = '#ff6f61';
  ctx.lineWidth = 2;

  if (state.calibration.points.length === 2) {
    const [a, b] = state.calibration.points;
    ctx.beginPath();
    ctx.moveTo(a.displayX, a.displayY);
    ctx.lineTo(b.displayX, b.displayY);
    ctx.stroke();
  }

  for (const point of state.calibration.points) {
    ctx.beginPath();
    ctx.arc(point.displayX, point.displayY, 6, 0, Math.PI * 2);
    ctx.fill();
    ctx.stroke();
  }
}

function prepareRoomCanvas(roomData) {
  const img = new Image();
  img.src = roomData.image_url || `/${roomData.image_path}`;
  img.onload = () => {
    const maxWidth = 900;
    const displayWidth = Math.min(img.naturalWidth, maxWidth);
    const ratio = img.naturalHeight / img.naturalWidth;
    const displayHeight = Math.round(displayWidth * ratio);
    const scale = img.naturalWidth / displayWidth;

    state.room = {
      ...roomData,
      id: Number(roomData.id),
      real_room_width_cm: Number(roomData.real_room_width_cm),
      image: img,
      displayWidth,
      displayHeight,
      pixelScale: scale,
      pxPerCm: null,
      calibrationDistancePx: null,
    };

    state.selectedRug = null;
    state.rugImage = null;
    state.tryOn = null;
    hideSection(elements.tryOnSection);
    elements.currentRugSize.textContent = '—';

    elements.calibrationCanvas.width = displayWidth;
    elements.calibrationCanvas.height = displayHeight;
    elements.calibrationCanvas.style.width = '100%';
    elements.calibrationCanvas.style.height = 'auto';

    elements.tryOnCanvas.width = displayWidth;
    elements.tryOnCanvas.height = displayHeight;
    elements.tryOnCanvas.style.width = '100%';
    elements.tryOnCanvas.style.height = 'auto';

    state.calibration.points = [];
    drawCalibrationCanvas();
    showSection(elements.calibrationSection);
    updateCalibrationHint('Кликните по первой точке.');
  };
  img.onerror = () => {
    showToast('Не удалось загрузить изображение комнаты', 'danger');
  };
}

function handleCalibrationClick(event) {
  if (!state.room?.image) {
    return;
  }
  const canvas = elements.calibrationCanvas;
  const rect = canvas.getBoundingClientRect();
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;
  const xCanvas = (event.clientX - rect.left) * scaleX;
  const yCanvas = (event.clientY - rect.top) * scaleY;

  const point = {
    displayX: xCanvas,
    displayY: yCanvas,
    x: Math.round(xCanvas * state.room.pixelScale),
    y: Math.round(yCanvas * state.room.pixelScale),
  };

  if (state.calibration.points.length < 2) {
    state.calibration.points.push(point);
  } else {
    state.calibration.points = [state.calibration.points[1], point];
  }

  drawCalibrationCanvas();

  if (state.calibration.points.length === 1) {
    updateCalibrationHint('Кликните по второй точке.');
  } else {
    updateCalibrationHint('Нажмите «Сохранить калибровку».', 'success');
  }
}

async function submitCalibration() {
  if (!state.room) {
    return;
  }
  if (state.calibration.points.length !== 2) {
    showToast('Нужно выбрать две точки для калибровки', 'warning');
    return;
  }

  const [a, b] = state.calibration.points;
  try {
    const payload = {
      calib_x1: a.x,
      calib_y1: a.y,
      calib_x2: b.x,
      calib_y2: b.y,
    };
    const result = await apiFetch(`/api/rooms/${state.room.id}/calibrate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    state.room.pxPerCm = parseFloat(result.px_per_cm);
    state.room.calibrationDistancePx = parseFloat(result.calibration_distance_px);
    showToast('Калибровка сохранена', 'success');
    updateCalibrationHint(`Масштаб: ${state.room.pxPerCm.toFixed(3)} px/см`, 'success');
    showSection(elements.catalogSection);
    await loadCatalog();
  } catch (error) {
    console.error(error);
    showToast(error.message || 'Ошибка калибровки', 'danger');
  }
}

async function loadCatalog() {
  try {
    const response = await apiFetch('/api/rugs?per_page=60&is_active=1');
    state.catalog = response.items || [];
    renderCatalog();
    await loadAdminRugs();
  } catch (error) {
    console.error(error);
    showToast('Не удалось загрузить каталог ковров', 'danger');
  }
}

function renderCatalog() {
  elements.catalogGrid.innerHTML = '';
  if (!state.catalog.length) {
    elements.emptyCatalog.classList.remove('d-none');
    elements.catalogStats.textContent = '';
    return;
  }
  elements.emptyCatalog.classList.add('d-none');
  elements.catalogStats.textContent = `Найдено ${state.catalog.length} ковров`;

  for (const rug of state.catalog) {
    const col = document.createElement('div');
    col.className = 'col-md-4 col-lg-3';

    const card = document.createElement('div');
    card.className = 'card h-100 shadow-sm rug-card';
    card.dataset.rugId = rug.id;

    const img = document.createElement('img');
    img.src = rug.image_url || `/${rug.image_path}`;
    img.className = 'card-img-top';
    img.alt = rug.title;

    const body = document.createElement('div');
    body.className = 'card-body d-flex flex-column';

    const title = document.createElement('h3');
    title.className = 'h6 card-title';
    title.textContent = rug.title;

    const size = document.createElement('p');
    size.className = 'card-text small text-muted mb-3';
    size.textContent = `${rug.width_cm} × ${rug.length_cm} см`;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary mt-auto';
    button.textContent = 'Выбрать';
    button.addEventListener('click', () => selectRug(rug));

    body.appendChild(title);
    body.appendChild(size);
    body.appendChild(button);
    card.appendChild(img);
    card.appendChild(body);
    col.appendChild(card);
    elements.catalogGrid.appendChild(col);
  }
}

function selectRug(rug) {
  if (!state.room?.pxPerCm) {
    showToast('Сначала откалибруйте комнату', 'warning');
    return;
  }

  const image = new Image();
  image.src = rug.image_url || `/${rug.image_path}`;
  image.onload = () => {
    state.selectedRug = rug;
    state.rugImage = image;
    initializeTryOn();
    highlightSelectedRug(rug.id);
  };
  image.onerror = () => showToast('Не удалось загрузить изображение ковра', 'danger');
}

function highlightSelectedRug(id) {
  document.querySelectorAll('.rug-card').forEach((card) => {
    if (parseInt(card.dataset.rugId, 10) === id) {
      card.classList.add('border-primary', 'shadow');
    } else {
      card.classList.remove('border-primary', 'shadow');
    }
  });
}

function initializeTryOn() {
  if (!state.selectedRug || !state.room || !state.rugImage) {
    return;
  }

  const rug = state.selectedRug;
  const room = state.room;

  const baseWidthPx = rug.width_cm * room.pxPerCm;
  const baseLengthPx = rug.length_cm * room.pxPerCm;

  state.tryOn = {
    centerX: room.image.naturalWidth / 2,
    centerY: room.image.naturalHeight / 2,
    scaleX: 1,
    scaleY: 1,
    rotation: 0,
    keepAspect: true,
    baseWidthPx,
    baseLengthPx,
  };

  elements.scaleRange.value = 100;
  elements.scaleYRange.value = 100;
  elements.rotationRange.value = 0;
  elements.keepAspectToggle.checked = true;
  elements.scaleYGroup.classList.add('d-none');
  elements.aspectWarning.hidden = true;
  state.showGrid = false;
  elements.toggleGridBtn.classList.add('btn-outline-secondary');
  elements.toggleGridBtn.classList.remove('btn-secondary');
  elements.toggleGridBtn.textContent = 'Сетка 10 см';
  updateGridOverlay();

  showSection(elements.tryOnSection);
  updateScaleLabels();
  updateRotationLabel();
  updateRugSize();
  drawTryOn();
}

function drawTryOn() {
  const canvas = elements.tryOnCanvas;
  const ctx = canvas.getContext('2d');
  const room = state.room;
  const tryOn = state.tryOn;
  const rugImage = state.rugImage;

  if (!room?.image) {
    return;
  }

  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(room.image, 0, 0, canvas.width, canvas.height);

  if (!tryOn || !rugImage) {
    return;
  }

  const scale = room.pixelScale;
  const widthDisplay = (tryOn.baseWidthPx * tryOn.scaleX) / scale;
  const heightDisplay = (tryOn.baseLengthPx * tryOn.scaleY) / scale;
  const centerDisplayX = tryOn.centerX / scale;
  const centerDisplayY = tryOn.centerY / scale;

  ctx.save();
  ctx.translate(centerDisplayX, centerDisplayY);
  ctx.rotate((tryOn.rotation * Math.PI) / 180);
  ctx.drawImage(rugImage, -widthDisplay / 2, -heightDisplay / 2, widthDisplay, heightDisplay);
  ctx.restore();

  updateGridOverlay();
}

function updateGridOverlay() {
  const overlay = elements.gridOverlay;
  if (!state.showGrid || !state.room?.pxPerCm) {
    overlay.classList.add('d-none');
    overlay.style.backgroundSize = '40px 40px';
    return;
  }

  overlay.classList.remove('d-none');
  const canvas = elements.tryOnCanvas;
  const rect = canvas.getBoundingClientRect();
  const pixelToCss = canvas.width / rect.width;
  const stepDisplay = (10 * state.room.pxPerCm) / state.room.pixelScale;
  const size = stepDisplay / pixelToCss;
  overlay.style.backgroundSize = `${size}px ${size}px`;
}

function updateScaleLabels() {
  elements.scaleValue.textContent = `${state.tryOn.scaleX.toFixed(2)}×`;
  elements.scaleYValue.textContent = `${state.tryOn.scaleY.toFixed(2)}×`;
}

function updateRotationLabel() {
  elements.rotationValue.textContent = `${state.tryOn.rotation.toFixed(0)}°`;
}

function updateRugSize() {
  if (!state.tryOn || !state.selectedRug || !state.room?.pxPerCm) {
    elements.currentRugSize.textContent = '—';
    return;
  }
  const widthCm = state.selectedRug.width_cm * state.tryOn.scaleX;
  const lengthCm = state.selectedRug.length_cm * state.tryOn.scaleY;
  elements.currentRugSize.textContent = `${widthCm.toFixed(1)} × ${lengthCm.toFixed(1)} см`;
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function handleScaleChange(value, axis = 'x') {
  if (!state.tryOn) {
    return;
  }
  const scale = clamp(value / 100, 0.25, 3);
  if (axis === 'x') {
    state.tryOn.scaleX = scale;
    if (state.tryOn.keepAspect) {
      state.tryOn.scaleY = scale;
      elements.scaleYRange.value = value;
    }
  } else {
    state.tryOn.scaleY = scale;
  }
  updateScaleLabels();
  updateRugSize();
  drawTryOn();
}

function handleRotationChange(value) {
  if (!state.tryOn) {
    return;
  }
  state.tryOn.rotation = clamp(value, -45, 45);
  updateRotationLabel();
  drawTryOn();
}

function handleAspectToggle(checked) {
  if (!state.tryOn) {
    return;
  }
  state.tryOn.keepAspect = checked;
  elements.scaleYGroup.classList.toggle('d-none', checked);
  elements.aspectWarning.hidden = checked;
  if (checked) {
    state.tryOn.scaleY = state.tryOn.scaleX;
    elements.scaleYRange.value = elements.scaleRange.value;
    updateScaleLabels();
  } else {
    showToast('Пропорции ковра можно менять, но подсказка размеров будет неточной.', 'warning');
  }
  drawTryOn();
}

function fitRugToRoomWidth() {
  if (!state.tryOn || !state.room?.pxPerCm || !state.selectedRug) {
    return;
  }
  const targetWidthPx = state.room.real_room_width_cm * state.room.pxPerCm;
  const scaleX = clamp(targetWidthPx / state.tryOn.baseWidthPx, 0.25, 3);
  state.tryOn.scaleX = scaleX;
  if (state.tryOn.keepAspect) {
    state.tryOn.scaleY = scaleX;
    elements.scaleYRange.value = Math.round(scaleX * 100);
  }
  elements.scaleRange.value = Math.round(scaleX * 100);
  updateScaleLabels();
  updateRugSize();
  drawTryOn();
}

function toggleGrid() {
  state.showGrid = !state.showGrid;
  elements.toggleGridBtn.classList.toggle('btn-outline-secondary', !state.showGrid);
  elements.toggleGridBtn.classList.toggle('btn-secondary', state.showGrid);
  elements.toggleGridBtn.textContent = state.showGrid ? 'Скрыть сетку' : 'Сетка 10 см';
  updateGridOverlay();
}

function downloadScreenshot() {
  if (!state.room) {
    return;
  }
  const link = document.createElement('a');
  link.href = elements.tryOnCanvas.toDataURL('image/png');
  link.download = 'rug-tryon.png';
  link.click();
}

async function saveTryOnSession() {
  if (!state.tryOn || !state.selectedRug || !state.room) {
    return;
  }
  const payload = {
    room_photo_id: state.room.id,
    rug_id: state.selectedRug.id,
    pos_x: Math.round(state.tryOn.centerX),
    pos_y: Math.round(state.tryOn.centerY),
    rotate_deg: state.tryOn.rotation,
    scale_x: state.tryOn.scaleX,
    scale_y: state.tryOn.scaleY,
    keep_aspect: state.tryOn.keepAspect,
    current_rug_width_cm: state.selectedRug.width_cm * state.tryOn.scaleX,
    current_rug_length_cm: state.selectedRug.length_cm * state.tryOn.scaleY,
  };

  try {
    const response = await apiFetch('/api/tryon', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    elements.saveStatus.textContent = `Сессия сохранена (ID ${response.id}).`;
    showToast('Сессия примерки сохранена', 'success');
  } catch (error) {
    elements.saveStatus.textContent = 'Не удалось сохранить сессию.';
    showToast('Не удалось сохранить сессию', 'danger');
  }
}

function showToast(message, variant = 'info') {
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `
    <div class="toast align-items-center text-bg-${variant} border-0" role="status" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>
      </div>
    </div>`;
  const toastEl = wrapper.firstElementChild;
  elements.toastContainer.appendChild(toastEl);
  const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
  toast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

async function handleUploadForm(event) {
  event.preventDefault();
  const form = event.currentTarget;
  if (!form.image.files.length) {
    showToast('Выберите изображение комнаты', 'warning');
    return;
  }

  const formData = new FormData(form);
  try {
    elements.uploadStatus.textContent = 'Загрузка...';
    const response = await apiFetch('/api/rooms', {
      method: 'POST',
      body: formData,
    });
    elements.uploadStatus.textContent = 'Фото загружено. Перейдите к калибровке.';
    showToast('Фото комнаты загружено', 'success');
    prepareRoomCanvas(response);
  } catch (error) {
    elements.uploadStatus.textContent = error.message || 'Ошибка загрузки';
    showToast(error.message || 'Ошибка загрузки', 'danger');
  }
}

function setupDragHandlers() {
  const canvas = elements.tryOnCanvas;
  let isDragging = false;
  let startPoint = null;
  let startCenter = null;

  function pointerDown(event) {
    if (!state.tryOn) {
      return;
    }
    isDragging = true;
    const { x, y } = canvasCoords(event);
    startPoint = { x, y };
    startCenter = { x: state.tryOn.centerX, y: state.tryOn.centerY };
    canvas.setPointerCapture(event.pointerId);
  }

  function pointerMove(event) {
    if (!isDragging || !state.tryOn) {
      return;
    }
    const { x, y } = canvasCoords(event);
    const dx = (x - startPoint.x) * state.room.pixelScale;
    const dy = (y - startPoint.y) * state.room.pixelScale;
    state.tryOn.centerX = startCenter.x + dx;
    state.tryOn.centerY = startCenter.y + dy;
    drawTryOn();
  }

  function pointerUp(event) {
    if (!isDragging) {
      return;
    }
    isDragging = false;
    canvas.releasePointerCapture(event.pointerId);
  }

  function canvasCoords(event) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    return {
      x: (event.clientX - rect.left) * scaleX,
      y: (event.clientY - rect.top) * scaleY,
    };
  }

  canvas.addEventListener('pointerdown', pointerDown);
  canvas.addEventListener('pointermove', pointerMove);
  canvas.addEventListener('pointerup', pointerUp);
  canvas.addEventListener('pointerleave', pointerUp);
}

function setupWheelHandler() {
  const canvas = elements.tryOnCanvas;
  canvas.addEventListener('wheel', (event) => {
    if (!state.tryOn) {
      return;
    }
    event.preventDefault();
    const direction = event.deltaY < 0 ? 1 : -1;
    const nextValue = parseInt(elements.scaleRange.value, 10) + direction * 5;
    const clamped = clamp(nextValue, 25, 300);
    elements.scaleRange.value = clamped;
    handleScaleChange(clamped);
  });
}

async function handleAdminLogin(event) {
  event.preventDefault();
  const email = document.getElementById('adminEmail').value.trim();
  const password = document.getElementById('adminPassword').value;
  if (!email || !password) {
    showToast('Укажите email и пароль', 'warning');
    return;
  }

  try {
    elements.adminLoginStatus.textContent = 'Авторизация...';
    const response = await apiFetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    state.token = response.token;
    state.admin = response.user;
    elements.adminLoginStatus.textContent = `Здравствуйте, ${response.user.email}`;
    elements.adminPanel.classList.remove('d-none');
    showToast('Вы вошли как администратор', 'success');
    await loadAdminRugs();
  } catch (error) {
    elements.adminLoginStatus.textContent = error.message || 'Ошибка входа';
    showToast(error.message || 'Ошибка входа', 'danger');
  }
}

async function handleAdminLogout() {
  if (!state.token) {
    return;
  }
  try {
    await apiFetch('/api/auth/logout', { method: 'POST' });
  } catch (error) {
    console.warn('Ошибка выхода', error);
  }
  state.token = null;
  state.admin = null;
  elements.adminPanel.classList.add('d-none');
  elements.adminLoginStatus.textContent = 'Вы вышли из аккаунта.';
  showToast('Вы вышли из админ-панели', 'info');
}

async function handleAddRug(event) {
  event.preventDefault();
  if (!state.token) {
    showToast('Войдите в админ-панель', 'warning');
    return;
  }

  const formData = new FormData(elements.addRugForm);
  if (!elements.addRugForm.image.files.length) {
    showToast('Добавьте изображение ковра', 'warning');
    return;
  }

  const activeToggle = elements.addRugForm.querySelector('#rugActive');
  formData.set('is_active', activeToggle.checked ? '1' : '0');
  try {
    elements.addRugStatus.textContent = 'Сохраняем...';
    await apiFetch('/api/rugs', {
      method: 'POST',
      body: formData,
    });
    elements.addRugStatus.textContent = 'Ковер добавлен.';
    elements.addRugForm.reset();
    await loadCatalog();
    showToast('Ковер добавлен', 'success');
  } catch (error) {
    elements.addRugStatus.textContent = error.message || 'Ошибка сохранения';
    showToast(error.message || 'Ошибка сохранения', 'danger');
  }
}

async function loadAdminRugs() {
  try {
    const response = await apiFetch('/api/rugs?per_page=200');
    renderAdminTable(response.items || []);
  } catch (error) {
    console.warn('Не удалось обновить список ковров', error);
  }
}

function renderAdminTable(items) {
  elements.adminRugTable.innerHTML = '';
  for (const rug of items) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${rug.id}</td>
      <td>${rug.title}</td>
      <td>${rug.width_cm} × ${rug.length_cm}</td>
      <td>${rug.is_active ? 'Активен' : 'Скрыт'}</td>
      <td>${rug.created_at ? new Date(rug.created_at).toLocaleString() : '—'}</td>`;
    elements.adminRugTable.appendChild(tr);
  }
}

function setupEvents() {
  elements.uploadForm.addEventListener('submit', handleUploadForm);
  elements.calibrationCanvas.addEventListener('click', handleCalibrationClick);
  elements.resetCalibration.addEventListener('click', resetCalibrationCanvas);
  elements.confirmCalibration.addEventListener('click', submitCalibration);
  elements.scaleRange.addEventListener('input', (event) => handleScaleChange(parseInt(event.target.value, 10), 'x'));
  elements.scaleYRange.addEventListener('input', (event) => handleScaleChange(parseInt(event.target.value, 10), 'y'));
  elements.rotationRange.addEventListener('input', (event) => handleRotationChange(parseInt(event.target.value, 10)));
  elements.keepAspectToggle.addEventListener('change', (event) => handleAspectToggle(event.target.checked));
  elements.fitWidthBtn.addEventListener('click', fitRugToRoomWidth);
  elements.toggleGridBtn.addEventListener('click', toggleGrid);
  elements.screenshotBtn.addEventListener('click', downloadScreenshot);
  elements.saveTryOnBtn.addEventListener('click', saveTryOnSession);
  elements.adminLoginForm.addEventListener('submit', handleAdminLogin);
  elements.adminLogoutBtn.addEventListener('click', handleAdminLogout);
  elements.addRugForm.addEventListener('submit', handleAddRug);
  setupDragHandlers();
  setupWheelHandler();
}

setupEvents();
