var state = {
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

var maxDisplayWidth = 820;
var maxDisplayHeight = 560;

var roomImageInput = document.getElementById('roomImageInput');
var rugImageInput = document.getElementById('rugImageInput');
var roomLengthInput = document.getElementById('roomLength');
var roomWidthInput = document.getElementById('roomWidth');
var rugLengthInput = document.getElementById('rugLength');
var rugWidthInput = document.getElementById('rugWidth');

var plannerWrapper = document.getElementById('plannerWrapper');
var planner = document.getElementById('planner');
var roomImageEl = document.getElementById('roomImage');
var rugImageEl = document.getElementById('rugImage');
var placeholder = document.getElementById('placeholder');

function readFileAsDataUrl(file, callback) {
    if (!file) {
        callback(null);
        return;
    }
    var reader = new FileReader();
    reader.onload = function () {
        callback(reader.result);
    };
    reader.onerror = function () {
        callback(null);
    };
    reader.readAsDataURL(file);
}

function parsePositiveNumber(value) {
    var numeric = parseFloat(value);
    if (!isFinite(numeric) || numeric <= 0) {
        return null;
    }
    return numeric;
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
    var scale = Math.min(maxDisplayWidth / naturalWidth, maxDisplayHeight / naturalHeight, 1);
    return {
        displayWidth: naturalWidth * scale,
        displayHeight: naturalHeight * scale
    };
}

function clamp(value, min, max) {
    if (value < min) {
        return min;
    }
    if (value > max) {
        return max;
    }
    return value;
}

function applyPosition(leftPx, topPx, availableWidth, availableHeight) {
    rugImageEl.style.left = leftPx + 'px';
    rugImageEl.style.top = topPx + 'px';
    state.rug.position = {
        x: availableWidth > 0 ? leftPx / availableWidth : 0,
        y: availableHeight > 0 ? topPx / availableHeight : 0
    };
}

function updatePlanner() {
    var roomLength = parsePositiveNumber(roomLengthInput.value);
    var roomWidth = parsePositiveNumber(roomWidthInput.value);
    var rugLength = parsePositiveNumber(rugLengthInput.value);
    var rugWidth = parsePositiveNumber(rugWidthInput.value);

    state.room.length = roomLength;
    state.room.width = roomWidth;
    state.rug.length = rugLength;
    state.rug.width = rugWidth;

    var hasRoomData = Boolean(state.room.fileDataUrl && roomLength && roomWidth && state.room.naturalWidth && state.room.naturalHeight);
    var hasRugData = Boolean(state.rug.fileDataUrl && rugLength && rugWidth);
    var isReady = hasRoomData && hasRugData;
    updatePlannerVisibility(isReady);
    if (!isReady) {
        return;
    }

    var roomDisplay = determineRoomDisplaySize(state.room.naturalWidth, state.room.naturalHeight);
    var displayWidth = roomDisplay.displayWidth;
    var displayHeight = roomDisplay.displayHeight;
    if (displayWidth <= 0 || displayHeight <= 0) {
        return;
    }
    planner.style.width = displayWidth + 'px';
    planner.style.height = displayHeight + 'px';
    roomImageEl.src = state.room.fileDataUrl;

    var meterToPixelScale = Math.min(displayWidth / roomLength, displayHeight / roomWidth);
    if (!isFinite(meterToPixelScale) || meterToPixelScale <= 0) {
        return;
    }
    var rugWidthPx = rugLength * meterToPixelScale;
    var rugHeightPx = rugWidth * meterToPixelScale;
    rugImageEl.src = state.rug.fileDataUrl;
    rugImageEl.style.width = rugWidthPx + 'px';
    rugImageEl.style.height = rugHeightPx + 'px';

    var availableWidth = Math.max(displayWidth - rugWidthPx, 0);
    var availableHeight = Math.max(displayHeight - rugHeightPx, 0);

    var ratioX = 0.5;
    var ratioY = 0.5;
    if (state.rug.position && typeof state.rug.position.x === 'number') {
        ratioX = clamp(state.rug.position.x, 0, 1);
    }
    if (state.rug.position && typeof state.rug.position.y === 'number') {
        ratioY = clamp(state.rug.position.y, 0, 1);
    }

    var leftPx = availableWidth * ratioX;
    var topPx = availableHeight * ratioY;
    applyPosition(leftPx, topPx, availableWidth, availableHeight);
}

function attachInputHandlers() {
    roomImageInput.addEventListener('change', function () {
        var file = null;
        if (roomImageInput.files && roomImageInput.files[0]) {
            file = roomImageInput.files[0];
        }
        readFileAsDataUrl(file, function (dataUrl) {
            state.room.fileDataUrl = dataUrl;
            state.room.naturalWidth = null;
            state.room.naturalHeight = null;

            if (!dataUrl) {
                updatePlanner();
                return;
            }

            var img = new Image();
            img.onload = function () {
                state.room.naturalWidth = img.naturalWidth;
                state.room.naturalHeight = img.naturalHeight;
                updatePlanner();
            };
            img.onerror = function () {
                updatePlanner();
            };
            img.src = dataUrl;
        });
    });

    rugImageInput.addEventListener('change', function () {
        var file = null;
        if (rugImageInput.files && rugImageInput.files[0]) {
            file = rugImageInput.files[0];
        }
        readFileAsDataUrl(file, function (dataUrl) {
            state.rug.fileDataUrl = dataUrl;
            updatePlanner();
        });
    });

    var numericInputs = [roomLengthInput, roomWidthInput, rugLengthInput, rugWidthInput];
    for (var i = 0; i < numericInputs.length; i += 1) {
        numericInputs[i].addEventListener('input', function () {
            if (!state.rug.position) {
                state.rug.position = { x: 0.5, y: 0.5 };
            }
            updatePlanner();
        });
    }
}

function setupDragging() {
    var dragging = false;
    var offsetX = 0;
    var offsetY = 0;
    var pointerId = null;

    rugImageEl.addEventListener('pointerdown', function (event) {
        if (!planner.classList.contains('visible')) {
            return;
        }
        dragging = true;
        pointerId = event.pointerId;
        rugImageEl.setPointerCapture(pointerId);
        rugImageEl.classList.add('dragging');
        var rugRect = rugImageEl.getBoundingClientRect();
        offsetX = event.clientX - rugRect.left;
        offsetY = event.clientY - rugRect.top;
    });

    var stopDragging = function () {
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

    rugImageEl.addEventListener('pointermove', function (event) {
        if (!dragging || !planner.classList.contains('visible')) {
            return;
        }
        var plannerRect = planner.getBoundingClientRect();
        var rugRect = rugImageEl.getBoundingClientRect();
        var availableWidth = plannerRect.width - rugRect.width;
        var availableHeight = plannerRect.height - rugRect.height;
        var left = event.clientX - plannerRect.left - offsetX;
        var top = event.clientY - plannerRect.top - offsetY;
        left = clamp(left, 0, Math.max(availableWidth, 0));
        top = clamp(top, 0, Math.max(availableHeight, 0));
        applyPosition(left, top, Math.max(availableWidth, 0), Math.max(availableHeight, 0));
    });
}

attachInputHandlers();
setupDragging();
