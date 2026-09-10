export function createBuilderId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `id-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export function cloneDocument(value) {
    return JSON.parse(JSON.stringify(value));
}

function layoutByCode(layouts, code) {
    return layouts.find((item) => item.code === code) ?? layouts[0];
}

function makeColumn(width, widgets = []) {
    return {
        id: createBuilderId(),
        width,
        widgets,
    };
}

export function makeSection(layouts, layoutCode = '100') {
    const layout = layoutByCode(layouts, layoutCode);

    return {
        id: createBuilderId(),
        settings: {
            layout: layout.code,
            background: '#ffffff',
            background_image: '',
            padding: 'md',
            full_width: false,
        },
        columns: layout.widths.map((width) => makeColumn(width)),
    };
}

export function makeWidget(type, defaults = {}) {
    return {
        id: createBuilderId(),
        type,
        settings: cloneDocument(defaults),
    };
}

export function findWidgetLocation(document, widgetId) {
    for (const section of document.sections) {
        for (const column of section.columns) {
            const index = column.widgets.findIndex((widget) => widget.id === widgetId);
            if (index !== -1) {
                return { section, column, index, widget: column.widgets[index] };
            }
        }
    }

    return null;
}

export function applySectionLayout(section, layouts, layoutCode) {
    const layout = layoutByCode(layouts, layoutCode);
    const allWidgets = section.columns.flatMap((column) => column.widgets ?? []);

    section.settings.layout = layout.code;
    section.columns = layout.widths.map((width, index) => ({
        id: section.columns[index]?.id ?? createBuilderId(),
        width,
        widgets: index === 0 ? allWidgets : [],
    }));

    return section;
}

export function readDragPayload(event) {
    try {
        const raw = event.dataTransfer?.getData('text/plain');
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function writeDragPayload(event, payload) {
    event.dataTransfer.effectAllowed = payload.kind?.startsWith('palette') ? 'copy' : 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify(payload));
}
