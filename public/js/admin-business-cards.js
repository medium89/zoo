(() => {
    const DPI = 300;
    const MM_TO_PX = DPI / 25.4;
    const PAGE_WIDTH = 210;
    const PAGE_HEIGHT = 297;
    const canvas = document.getElementById('cardsCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const fields = {
        file: document.getElementById('cardsFile'),
        fileLabel: document.getElementById('cardsFileLabel'),
        fileError: document.getElementById('cardsFileError'),
        resolution: document.getElementById('cardsResolution'),
        preset: document.getElementById('cardsPreset'),
        width: document.getElementById('cardsWidth'),
        height: document.getElementById('cardsHeight'),
        scale: document.getElementById('cardsScale'),
        scaleValue: document.getElementById('cardsScaleValue'),
        actualSize: document.getElementById('cardsActualSize'),
        margin: document.getElementById('cardsMargin'),
        gap: document.getElementById('cardsGap'),
        fit: document.getElementById('cardsFit'),
        count: document.getElementById('cardsCount'),
        layoutError: document.getElementById('cardsLayoutError'),
        download: document.getElementById('cardsDownload'),
        print: document.getElementById('cardsPrint'),
    };

    let image = null;
    let imageUrl = null;
    let renderPending = false;

    const px = mm => mm * MM_TO_PX;
    const formatMm = value => Number(value.toFixed(1)).toLocaleString('ru-RU');
    const showError = (element, message) => {
        element.textContent = message;
        element.hidden = !message;
    };

    function drawImageInCard(x, y, width, height, fit) {
        ctx.fillStyle = '#fff';
        ctx.fillRect(x, y, width, height);
        const imageRatio = image.naturalWidth / image.naturalHeight;
        const cardRatio = width / height;

        if (fit === 'cover') {
            let sourceWidth = image.naturalWidth;
            let sourceHeight = image.naturalHeight;
            if (imageRatio > cardRatio) sourceWidth = sourceHeight * cardRatio;
            else sourceHeight = sourceWidth / cardRatio;
            ctx.drawImage(
                image,
                (image.naturalWidth - sourceWidth) / 2,
                (image.naturalHeight - sourceHeight) / 2,
                sourceWidth,
                sourceHeight,
                x, y, width, height
            );
        } else {
            const ratio = Math.min(width / image.naturalWidth, height / image.naturalHeight);
            const drawnWidth = image.naturalWidth * ratio;
            const drawnHeight = image.naturalHeight * ratio;
            ctx.drawImage(image, x + (width - drawnWidth) / 2, y + (height - drawnHeight) / 2, drawnWidth, drawnHeight);
        }
    }

    function drawCutLines(x, y, width, height) {
        const mark = px(1.5);
        ctx.save();
        ctx.strokeStyle = '#777';
        ctx.lineWidth = Math.max(1, px(0.12));
        ctx.beginPath();
        ctx.rect(x, y, width, height);
        const corners = [
            [x, y, -1, -1], [x + width, y, 1, -1],
            [x, y + height, -1, 1], [x + width, y + height, 1, 1],
        ];
        for (const [cornerX, cornerY, dx, dy] of corners) {
            ctx.moveTo(cornerX + dx * mark, cornerY);
            ctx.lineTo(cornerX, cornerY);
            ctx.moveTo(cornerX, cornerY + dy * mark);
            ctx.lineTo(cornerX, cornerY);
        }
        ctx.stroke();
        ctx.restore();
    }

    function drawSharedCutLines(startX, startY, columns, rows, cardWidth, cardHeight) {
        const x = px(startX);
        const y = px(startY);
        const width = px(columns * cardWidth);
        const height = px(rows * cardHeight);
        ctx.save();
        ctx.strokeStyle = '#777';
        ctx.lineWidth = Math.max(1, px(0.12));
        ctx.beginPath();
        ctx.rect(x, y, width, height);
        for (let column = 1; column < columns; column++) {
            const lineX = px(startX + column * cardWidth);
            ctx.moveTo(lineX, y);
            ctx.lineTo(lineX, y + height);
        }
        for (let row = 1; row < rows; row++) {
            const lineY = px(startY + row * cardHeight);
            ctx.moveTo(x, lineY);
            ctx.lineTo(x + width, lineY);
        }
        ctx.stroke();
        ctx.restore();
    }

    function render() {
        renderPending = false;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const baseWidth = Number(fields.width.value);
        const baseHeight = Number(fields.height.value);
        const scale = Number(fields.scale.value);
        const margin = fields.margin.value === '' ? NaN : Number(fields.margin.value);
        const gap = fields.gap.value === '' ? NaN : Number(fields.gap.value);
        const cardWidth = baseWidth * scale / 100;
        const cardHeight = baseHeight * scale / 100;

        fields.scaleValue.textContent = scale + '%';
        fields.actualSize.textContent = Number.isFinite(cardWidth) && Number.isFinite(cardHeight)
            ? formatMm(cardWidth) + ' × ' + formatMm(cardHeight) + ' мм'
            : '—';

        let error = '';
        if (![baseWidth, baseHeight, scale, margin, gap].every(Number.isFinite)
            || baseWidth < 35 || baseHeight < 25 || margin < 0 || gap < 0
            || baseWidth > 190 || baseHeight > 277 || margin > 30 || gap > 20) {
            error = 'Проверьте размеры, поля и расстояние между визитками.';
        }

        const columns = error ? 0 : Math.floor((PAGE_WIDTH - 2 * margin + gap) / (cardWidth + gap));
        const rows = error ? 0 : Math.floor((PAGE_HEIGHT - 2 * margin + gap) / (cardHeight + gap));
        if (!error && (columns < 1 || rows < 1)) error = 'Визитка не помещается на A4 с заданными полями и масштабом.';
        showError(fields.layoutError, error);

        const count = error ? 0 : columns * rows;
        fields.count.textContent = image && count ? count + ' шт. · ' + columns + ' × ' + rows : (image ? 'Настройте размер' : 'Загрузите изображение');
        fields.download.disabled = !image || !!error;
        fields.print.disabled = !image || !!error;

        if (!image || error) {
            fields.resolution.textContent = '';
            return;
        }

        const neededWidth = Math.ceil(px(cardWidth));
        const neededHeight = Math.ceil(px(cardHeight));
        const lowResolution = image.naturalWidth < neededWidth || image.naturalHeight < neededHeight;
        fields.resolution.textContent = lowResolution
            ? 'Изображение ' + image.naturalWidth + ' × ' + image.naturalHeight + ' px. Для 300 dpi желательно не менее ' + neededWidth + ' × ' + neededHeight + ' px.'
            : 'Разрешение подходит для печати 300 dpi: ' + image.naturalWidth + ' × ' + image.naturalHeight + ' px.';

        const gridWidth = columns * cardWidth + (columns - 1) * gap;
        const gridHeight = rows * cardHeight + (rows - 1) * gap;
        const startX = margin === 0 ? 0 : (PAGE_WIDTH - gridWidth) / 2;
        const startY = margin === 0 ? 0 : (PAGE_HEIGHT - gridHeight) / 2;
        for (let row = 0; row < rows; row++) {
            for (let column = 0; column < columns; column++) {
                const x = px(startX + column * (cardWidth + gap));
                const y = px(startY + row * (cardHeight + gap));
                const width = px(cardWidth);
                const height = px(cardHeight);
                drawImageInCard(x, y, width, height, fields.fit.value);
                if (gap > 0) drawCutLines(x, y, width, height);
            }
        }
        if (gap === 0) drawSharedCutLines(startX, startY, columns, rows, cardWidth, cardHeight);
    }

    function queueRender() {
        if (renderPending) return;
        renderPending = true;
        requestAnimationFrame(render);
    }

    fields.file.addEventListener('change', () => {
        const file = fields.file.files?.[0];
        if (!file) return;
        if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type) || file.size > 20 * 1024 * 1024) {
            showError(fields.fileError, 'Выберите PNG, JPG или WEBP размером до 20 МБ.');
            fields.file.value = '';
            return;
        }
        showError(fields.fileError, '');
        const nextUrl = URL.createObjectURL(file);
        const nextImage = new Image();
        nextImage.onload = () => {
            if (imageUrl) URL.revokeObjectURL(imageUrl);
            imageUrl = nextUrl;
            image = nextImage;
            fields.fileLabel.textContent = file.name;
            queueRender();
        };
        nextImage.onerror = () => {
            URL.revokeObjectURL(nextUrl);
            showError(fields.fileError, 'Не удалось открыть изображение. Попробуйте другой файл.');
        };
        nextImage.src = nextUrl;
    });

    fields.preset.addEventListener('change', () => {
        if (fields.preset.value !== 'custom') {
            const [width, height] = fields.preset.value.split('x');
            fields.width.value = width;
            fields.height.value = height;
        }
        queueRender();
    });

    for (const field of [fields.width, fields.height]) {
        field.addEventListener('input', () => {
            fields.preset.value = 'custom';
            queueRender();
        });
    }
    for (const field of [fields.scale, fields.margin, fields.gap, fields.fit]) {
        field.addEventListener('input', queueRender);
    }

    async function withPrintResolution(blob) {
        const source = new Uint8Array(await blob.arrayBuffer());
        const view = new DataView(source.buffer);
        const pixelsPerMeter = Math.round(DPI * 1000 / 25.4);
        const physical = new Uint8Array(21);
        const physicalView = new DataView(physical.buffer);
        physicalView.setUint32(0, 9);
        physical.set([112, 72, 89, 115], 4); // pHYs
        physicalView.setUint32(8, pixelsPerMeter);
        physicalView.setUint32(12, pixelsPerMeter);
        physical[16] = 1; // metres

        let crc = 0xffffffff;
        for (let index = 4; index < 17; index++) {
            crc ^= physical[index];
            for (let bit = 0; bit < 8; bit++) {
                crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
            }
        }
        physicalView.setUint32(17, (crc ^ 0xffffffff) >>> 0);

        const parts = [source.subarray(0, 8)];
        let position = 8;
        while (position + 12 <= source.length) {
            const length = view.getUint32(position);
            const end = position + length + 12;
            if (end > source.length) throw new Error('Invalid PNG');
            const type = String.fromCharCode(...source.subarray(position + 4, position + 8));
            if (type !== 'pHYs') parts.push(source.subarray(position, end));
            if (type === 'IHDR') parts.push(physical);
            position = end;
        }
        const bytes = new Uint8Array(parts.reduce((sum, part) => sum + part.length, 0));
        let offset = 0;
        for (const part of parts) {
            bytes.set(part, offset);
            offset += part.length;
        }
        return new Blob([bytes], { type: 'image/png' });
    }

    fields.download.addEventListener('click', () => {
        if (fields.download.disabled) return;
        canvas.toBlob(async blob => {
            if (!blob) {
                showError(fields.layoutError, 'Не удалось создать PNG. Попробуйте другой файл.');
                return;
            }
            try {
                const output = await withPrintResolution(blob);
                const url = URL.createObjectURL(output);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'vizitki-a4-300dpi.png';
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(url), 60000);
            } catch {
                showError(fields.layoutError, 'Не удалось подготовить PNG для печати.');
            }
        }, 'image/png');
    });

    fields.print.addEventListener('click', () => {
        if (!fields.print.disabled) window.print();
    });

    render();
})();
