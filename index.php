<?php
// Страница примерки ковра в комнате
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Примерка ковра в комнате</title>
    <style>
        :root {
            color-scheme: light dark;
            --accent: #3b82f6;
            --border-color: rgba(148, 163, 184, 0.4);
            --bg-muted: rgba(148, 163, 184, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            padding: 24px 32px 8px;
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        h1 {
            margin: 0 0 4px;
            font-size: 2rem;
        }

        main {
            flex: 1;
            padding: 24px 32px 48px;
            display: grid;
            gap: 24px;
        }

        .forms {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }

        .form-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: 0 12px 20px rgba(15, 23, 42, 0.06);
        }

        .form-card h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.95rem;
        }

        input[type="file"],
        input[type="number"] {
            padding: 8px 10px;
            font: inherit;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.85);
        }

        input[type="number"] {
            appearance: textfield;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .dimensions {
            display: grid;
            grid-template-columns: repeat(2, minmax(120px, 1fr));
            gap: 10px;
        }

        .hint {
            font-size: 0.85rem;
            color: #475569;
        }

        #workspace {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        #plannerWrapper {
            position: relative;
            min-height: 320px;
            background: white;
            border: 1px dashed var(--border-color);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #planner {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #0f172a;
            display: none;
            touch-action: none;
        }

        #planner.visible {
            display: block;
        }

        #roomImage {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        #rugImage {
            position: absolute;
            cursor: grab;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            pointer-events: auto;
        }

        #rugImage.dragging {
            cursor: grabbing;
        }

        #placeholder {
            text-align: center;
            color: #475569;
            max-width: 420px;
            line-height: 1.4;
        }

        .legend {
            font-size: 0.9rem;
            color: #1e293b;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .legend i {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            display: inline-block;
        }

        .legend .room-color {
            background: rgba(30, 64, 175, 0.65);
        }

        .legend .rug-color {
            background: rgba(220, 38, 38, 0.65);
        }

        @media (max-width: 720px) {
            header,
            main {
                padding-left: 20px;
                padding-right: 20px;
            }

            #plannerWrapper {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
<header>
    <h1>Виртуальная примерка ковра</h1>
    <p class="hint">Загрузите фотографии комнаты и ковра, укажите реальные размеры и перемещайте ковёр поверх комнаты.</p>
</header>
<main>
    <section class="forms">
        <form class="form-card" id="roomForm">
            <h2>Комната</h2>
            <label>Фотография комнаты
                <input type="file" id="roomImageInput" accept="image/*">
            </label>
            <div class="dimensions">
                <label>Длина (м)
                    <input type="number" id="roomLength" min="0.1" step="0.1" placeholder="Напр. 6">
                </label>
                <label>Ширина (м)
                    <input type="number" id="roomWidth" min="0.1" step="0.1" placeholder="Напр. 4">
                </label>
            </div>
            <p class="hint">Длина соответствует горизонтальному размеру, ширина — вертикальному.</p>
        </form>
        <form class="form-card" id="rugForm">
            <h2>Ковёр</h2>
            <label>Фотография ковра
                <input type="file" id="rugImageInput" accept="image/*">
            </label>
            <div class="dimensions">
                <label>Длина (м)
                    <input type="number" id="rugLength" min="0.1" step="0.1" placeholder="Напр. 2.5">
                </label>
                <label>Ширина (м)
                    <input type="number" id="rugWidth" min="0.1" step="0.1" placeholder="Напр. 1.6">
                </label>
            </div>
            <p class="hint">Эти значения используются для масштабирования ковра внутри комнаты.</p>
        </form>
    </section>

    <section id="workspace">
        <h2>Интерфейс примерки</h2>
        <div class="legend">
            <span><i class="room-color"></i>Комната отображается пропорционально указанным размерам.</span>
            <span><i class="rug-color"></i>Ковёр можно перемещать внутри границ комнаты.</span>
        </div>
        <div id="plannerWrapper">
            <div id="planner">
                <img id="roomImage" alt="Комната">
                <img id="rugImage" alt="Ковёр">
            </div>
            <div id="placeholder">
                Загрузите изображения и заполните размеры, чтобы увидеть ковёр поверх комнаты. После этого перетаскивайте ковёр, чтобы найти оптимальное расположение.
            </div>
        </div>
    </section>
</main>

<script>
    const state = {
        room: {
            fileDataUrl: null,
            length: null,
            width: null
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

    function calculateScale(roomLength, roomWidth) {
        if (!roomLength || !roomWidth) {
            return { scale: 1, displayWidth: 0, displayHeight: 0 };
        }
        const scale = Math.min(maxDisplayWidth / roomLength, maxDisplayHeight / roomWidth);
        return {
            scale,
            displayWidth: roomLength * scale,
            displayHeight: roomWidth * scale
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

        const hasRoomData = Boolean(state.room.fileDataUrl && roomLength && roomWidth);
        const hasRugData = Boolean(state.rug.fileDataUrl && rugLength && rugWidth);
        const isReady = hasRoomData && hasRugData;
        updatePlannerVisibility(isReady);
        if (!isReady) {
            return;
        }

        const { scale, displayWidth, displayHeight } = calculateScale(roomLength, roomWidth);
        planner.style.width = `${displayWidth}px`;
        planner.style.height = `${displayHeight}px`;
        roomImageEl.src = state.room.fileDataUrl;

        const rugWidthPx = rugLength * scale;
        const rugHeightPx = rugWidth * scale;
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
            readFileAsDataUrl(roomImageInput.files?.[0], (dataUrl) => {
                state.room.fileDataUrl = dataUrl;
                updatePlanner();
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
</script>
</body>
</html>
