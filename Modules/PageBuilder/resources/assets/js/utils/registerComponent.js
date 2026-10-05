export function registerComponent({
    editor,
    name,
    category = "Sections",
    media,
    model = {},
}) {
    var componentType = `${category}-${name}`;

    editor.DomComponents.addType(componentType, {
        model: {
            ...model,
        }
    });
    editor.BlockManager.add(componentType, {
        label: name.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        category: category.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
        media,
        content: { type: componentType },
    });
}
