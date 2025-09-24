const state = {
    room: {
        fileDataUrl: null,
        length: null,
        width: null,
        naturalWidth: null,
        naturalHeight: null
    },
    rug: {
        fileDataUrl: null,
        length: null,
        width: null,
        position: { x: 0.5, y: 0.5 }
    }
};

const maxDisplayWidth = 820;
const maxDisplayHeight = 560;

const roomImageInput = document.getElementById('roomImageInput');
const rugImageInput = document.getElementById('rugImageInput');
const roomLengthInput = document.getElementById('roomLength');
const roomWidthInput = document.getElementById('roomWidth');
const rugLengthInput = document.getElementById('rugLength');
const rugWidthInput = document.getElementById('rugWidth');

const plannerWrapper = document.getElementById('plannerWrapper');
const planner = document.getElementById('planner');
const roomImageEl = document.getElementById('roomImage');
const rugImageEl = document.getElementById('rugImage');
const placeholder = document.getElementById('placeholder');

function readFileAsDataUrl(file, callback) {
    if (!file) {
        callback(null);
        return;
    }
    const reader = new FileReader();
    reader.onload = () => callback(reader.result);
    reader.onerror = () => callback(null);
    reader.readAsDataURL(file);
}

function parsePositiveNumber(value) {
    const numeric = parseFloat(value);
    return Number.isFinite(numeric) && numeric > 0 ? numeric : null;
}

function updatePlannerVisibility(isReady) {
    if (isReady) {
        planner.classList.add('visible');
        placeholder.style.display = 'none';
    } else {
        planner.classList.remove('visible');
        placeholder.style.display = 'block';
    }
}

function determineRoomDisplaySize(naturalWidth, naturalHeight) {
    if (!naturalWidth || !naturalHeight) {
        return { displayWidth: 0, displayHeight: 0 };
    }
    const scale = Math.min(maxDisplayWidth / naturalWidth, maxDisplayHeight / naturalHeight, 1);
    return {
        displayWidth: naturalWidth * scale,
        displayHeight: naturalHeight * scale
    };
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function applyPosition(leftPx, topPx, availableWidth, availableHeight) {
    rugImageEl.style.left = `${leftPx}px`;
    rugImageEl.style.top = `${topPx}px`;
    state.rug.position = {
        x: availableWidth > 0 ? leftPx / availableWidth : 0,
        y: availableHeight > 0 ? topPx / availableHeight : 0
    };
}

function updatePlanner() {
    const roomLength = parsePositiveNumber(roomLengthInput.value);
    const roomWidth = parsePositiveNumber(roomWidthInput.value);
    const rugLength = parsePositiveNumber(rugLengthInput.value);
    const rugWidth = parsePositiveNumber(rugWidthInput.value);

    state.room.length = roomLength;
    state.room.width = roomWidth;
    state.rug.length = rugLength;
    state.rug.width = rugWidth;

    const hasRoomData = Boolean(state.room.fileDataUrl && roomLength && roomWidth && state.room.naturalWidth && state.room.naturalHeight);
    const hasRugData = Boolean(state.rug.fileDataUrl && rugLength && rugWidth);
    const isReady = hasRoomData && hasRugData;
    updatePlannerVisibility(isReady);
    if (!isReady) {
        return;
    }

    const { displayWidth, displayHeight } = determineRoomDisplaySize(state.room.naturalWidth, state.room.naturalHeight);
    if (displayWidth <= 0 || displayHeight <= 0) {
        return;
    }
    planner.style.width = `${displayWidth}px`;
    planner.style.height = `${displayHeight}px`;
    roomImageEl.src = state.room.fileDataUrl;

    const meterToPixelScale = Math.min(displayWidth / roomLength, displayHeight / roomWidth);
    if (!Number.isFinite(meterToPixelScale) || meterToPixelScale <= 0) {
        return;
    }
    const rugWidthPx = rugLength * meterToPixelScale;
    const rugHeightPx = rugWidth * meterToPixelScale;
    rugImageEl.src = state.rug.fileDataUrl;
    rugImageEl.style.width = `${rugWidthPx}px`;
    rugImageEl.style.height = `${rugHeightPx}px`;

    const availableWidth = Math.max(displayWidth - rugWidthPx, 0);
    const availableHeight = Math.max(displayHeight - rugHeightPx, 0);

    const ratioX = clamp(state.rug.position?.x ?? 0.5, 0, 1);
    const ratioY = clamp(state.rug.position?.y ?? 0.5, 0, 1);

    const leftPx = availableWidth * ratioX;
    const topPx = availableHeight * ratioY;
    applyPosition(leftPx, topPx, availableWidth, availableHeight);
}

function attachInputHandlers() {
    roomImageInput.addEventListener('change', () => {
        const file = roomImageInput.files?.[0] ?? null;
        readFileAsDataUrl(file, (dataUrl) => {
            state.room.fileDataUrl = dataUrl;
            state.room.naturalWidth = null;
            state.room.naturalHeight = null;

            if (!dataUrl) {
                updatePlanner();
                return;
            }

            const img = new Image();
            img.onload = () => {
                state.room.naturalWidth = img.naturalWidth;
                state.room.naturalHeight = img.naturalHeight;
                updatePlanner();
            };
            img.onerror = () => {
                updatePlanner();
            };
            img.src = dataUrl;
        });
    });

    rugImageInput.addEventListener('change', () => {
        readFileAsDataUrl(rugImageInput.files?.[0], (dataUrl) => {
            state.rug.fileDataUrl = dataUrl;
            updatePlanner();
        });
    });

    const numericInputs = [roomLengthInput, roomWidthInput, rugLengthInput, rugWidthInput];
    numericInputs.forEach((input) => {
        input.addEventListener('input', () => {
            if (state.rug.position == null) {
                state.rug.position = { x: 0.5, y: 0.5 };
            }
            updatePlanner();
        });
    });
}

function setupDragging() {
    let dragging = false;
    let offsetX = 0;
    let offsetY = 0;
    let pointerId = null;

    rugImageEl.addEventListener('pointerdown', (event) => {
        if (!planner.classList.contains('visible')) {
            return;
        }
        dragging = true;
        pointerId = event.pointerId;
        rugImageEl.setPointerCapture(pointerId);
        rugImageEl.classList.add('dragging');
        const rugRect = rugImageEl.getBoundingClientRect();
        offsetX = event.clientX - rugRect.left;
        offsetY = event.clientY - rugRect.top;
    });

    const stopDragging = () => {
        if (!dragging) {
            return;
        }
        dragging = false;
        if (pointerId !== null) {
            rugImageEl.releasePointerCapture(pointerId);
            pointerId = null;
        }
        rugImageEl.classList.remove('dragging');
    };

    rugImageEl.addEventListener('pointerup', stopDragging);
    rugImageEl.addEventListener('pointercancel', stopDragging);
    window.addEventListener('pointerup', stopDragging);

    rugImageEl.addEventListener('pointermove', (event) => {
        if (!dragging || !planner.classList.contains('visible')) {
            return;
        }
        const plannerRect = planner.getBoundingClientRect();
        const rugRect = rugImageEl.getBoundingClientRect();
        const availableWidth = plannerRect.width - rugRect.width;
        const availableHeight = plannerRect.height - rugRect.height;
        let left = event.clientX - plannerRect.left - offsetX;
        let top = event.clientY - plannerRect.top - offsetY;
        left = clamp(left, 0, Math.max(availableWidth, 0));
        top = clamp(top, 0, Math.max(availableHeight, 0));
        applyPosition(left, top, Math.max(availableWidth, 0), Math.max(availableHeight, 0));
    });
}

attachInputHandlers();
setupDragging();
