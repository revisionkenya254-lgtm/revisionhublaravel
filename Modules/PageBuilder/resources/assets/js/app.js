import grapesjs from "grapesjs";
import presetWebpage from "grapesjs-preset-webpage";
import basicBlocks from "grapesjs-blocks-basic";
import codeEditor from "grapesjs-component-code-editor";
import { builderAssets } from "./builderAssets";
import { showToast } from './toastr.js';
import customTraits from "./customTraits";

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const pageSaveUrl = document.querySelector('meta[name="url"]').getAttribute('content');

/*** Editor initialize ***/
const editor = grapesjs.init({
    container: "#gjs",
    height: "100vh",
    width: 'auto',
    fromElement: true,
    storageManager: false,

    plugins: [presetWebpage, basicBlocks, codeEditor],
    pluginsOpts: {
        [presetWebpage]: {
            blocks: [],
        },
        [basicBlocks]: {
            blocks: [
                'link',
                'image',
                'video',
                'map'
            ]
        },
        [codeEditor]: {},
    },

    assetManager: {
        upload: 'upload',
        uploadFile: (e) => {
            const file = e.target.files[0];
            if (!file) return;

            handleFileUpload(file, editor).then(fileName => {
                if (fileName) {
                    const fileUrl = fileName;
                    editor.AssetManager.add({
                        src: fileUrl,
                        name: fileName,
                        type: 'image',
                    });
                    editor.AssetManager.render();
                }
            });
        },
    },
});
// Register custom traits
customTraits(editor, csrfToken, window.baseUrl);

/*** File upload handler ***/
async function handleFileUpload(file, editor) {
    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await fetch(`${window.baseUrl}/admin/page-builder/upload`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
            body: formData,
        });

        const data = await res.json();

        if (res.ok) {
            showToast(data.message || "Uploaded successfully.", 'success');
            return data.file_name;
        } else {
            const msg = data.message || window.basicErrorMessage;
            if (res.status === 422 && typeof msg === "object") {
                Object.values(msg).flat().forEach(err => showToast(err, 'error'));
            } else {
                showToast(msg, 'error');
            }
        }
    } catch (e) {
        showToast(e.message || "Unexpected upload error", 'error');
        throw e;
    }
}
const panels = editor.Panels;

// Auto-open "Settings" (Traits) tab when a component is selected
editor.on('component:selected', () => {
    panels.getButton('views', 'open-tm').set('active', true);
});
// remove default buttons
panels.removeButton('options', 'export-template')
panels.removeButton('options', 'gjs-open-import-webpage')
panels.removeButton('options', 'sw-visibility');
panels.removeButton('views', 'open-layers');

/*** Add Buttons ***/
panels.addButton("options", [
    {
        id: "save-page",
        className: "fa fa-floppy-o",
        command: "save-page",
        attributes: {
            title: "Save Page",
        },
    },
    {
        id: "open-page",
        className: "fas fa-external-link-alt",
        command: "open-page",
        attributes: {
            title: "Open Page in New Tab",
        },
    },
    {
        id: 'toggle-sidebar',
        className: 'fa fa-angle-double-left',
        command: 'toggle-sidebar',
        attributes: { title: 'Toggle Sidebar' },
    },
]);
/*** Save Command ***/
editor.Commands.add("save-page", {
    async run(editor, sender) {
        sender && sender.set("active", false);

        // Save original class to restore later
        const originalClass = sender.get("className");
        // Show loading spinner icon
        sender.set("className", "spinner-border spinner-border-sm");

        const html = editor.getHtml();
        const css = editor.getCss();
        const js = editor.getJs();
        const json = JSON.stringify(editor.getProjectData());

        const content = `@extends('frontend.layouts.master')
        @section('meta_title', $page->translation->seo_title ?? $page->translation->name)
        @section('meta_description', $page->translation->seo_description ?? $page->translation->name)
        @section('body_class', $page?->fixed_header ? 'wsus_fixed_header' : '')
        @section('contents')
        ${updateEditorParts(html)}
        @endsection`;
        try {
            const res = await fetch(pageSaveUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ json, content }),
            });

            const data = await res.json();
            if (res.ok) {
                showToast(data.message || "Page saved successfully!", 'success');
            } else {
                const msg = data.message || window.basicErrorMessage;
                if (res.status === 422 && typeof msg === "object") {
                    Object.values(msg).flat().forEach(err => showToast(err, 'error'));
                } else {
                    showToast(msg, 'error');

                }
            }
        } catch (e) {
            showToast(window.basicErrorMessage, 'error');
        } finally {
            // Restore original icon after saving
            sender.set("className", originalClass);
            sender.set("active", false);
        }
    },
});
// Open Page command
editor.Commands.add("open-page", {
    run() {
        const currentUrl = window.location.href;
        const pageSlug = currentUrl.split('/').pop();
        const previewUrl = `${window.baseUrl}/page/${pageSlug}`;
        window.open(previewUrl, "_blank");
    },
});
// toggle sidebar command
editor.Commands.add('toggle-sidebar', {
    run(editor) {
        const sidebar = editor.getContainer().querySelector('.gjs-pn-views-container');
        sidebar.classList.toggle('collapsed');
        editor.getContainer().querySelector('.gjs-cv-canvas').classList.toggle('collapsed');

        const toggleBtn = panels.getButton('options', 'toggle-sidebar');
        toggleBtn.set('className', sidebar.classList.contains('collapsed')
            ? 'fa fa-angle-double-right toggled'
            : 'fa fa-angle-double-left');
    }
});

/*** body content format method with params ***/
function updateEditorParts(html) {
    // Remove <body> and </body> tags
    html = html
        .replace(/<\s*body[^>]*>/gi, "")
        .replace(/<\s*\/\s*body\s*>/gi, "");

    // Remove all <script>...</script> tags including multiline and inline
    html = html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, "");

    // Remove all data-trait="..." attributes
    html = html.replace(/\s*data-trait="[^"]*"/gi, "");

    // Remove all ".editor-preview-helper" class
    html = html.replace(/\s*editor-preview-helper\b/g, '');

    // Replace dynamic parts with Blade @include directive
    html = html.replace(
        /<!--\s*DYNAMIC_PART_START:(\S+)([^>]*)-->([\s\S]*?)<!--\s*DYNAMIC_PART_END\s*-->/g,
        (_, path, paramStr) => {
            const params = {};
            (paramStr.match(/(\w+)=(".*?"|'.*?'|\S+)/g) || []).forEach(p => {
                const [key, raw] = p.split('=');
                let val = raw.replace(/^['"]|['"]$/g, '');
                if (!isNaN(val)) val = Number(val);
                else if (val === 'true') val = true;
                else if (val === 'false') val = false;
                params[key] = val;
            });

            const bladeParams = Object.entries(params)
                .map(([k, v]) => `'${k}' => ${typeof v === 'string' ? `'${v}'` : v}`)
                .join(', ');

            return bladeParams
                ? `@include('pagebuilder::components.${path.trim()}', [${bladeParams}])`
                : `@include('pagebuilder::components.${path.trim()}')`;
        }
    );

    return html;
}
// Dynamically load all components from ./components
const modules = import.meta.glob("./components/**/*.js");
const entries = Object.entries(modules);

async function loadSequentially() {
    for (const [path, load] of entries) {
        const mod = await load();
        if (typeof mod.default === 'function') {
            const parts = path.split('/');
            const fileName = parts.pop().replace('.js', '');
            const folderName = parts.pop();

            await mod.default(editor, { folderName, fileName }, window.baseUrl);
        }
    }
}

loadSequentially().then(() => {
    // load the saved project JSON
    if (window.pageJson) {
        editor.loadProjectData(window.pageJson);
    }
    // Wait for iframe to fully load
    injectBuilderAssets(editor, builderAssets);
});
function injectBuilderAssets(editor, builderAssets) {
    const iframe = editor.Canvas.getFrameEl();

    const waitUntilReady = setInterval(() => {
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

        if (
            iframeDoc &&
            iframeDoc.readyState === "complete" &&
            iframeDoc.head &&
            iframeDoc.body
        ) {
            /*** force collapse all categories ***/
            editor.BlockManager.getCategories().forEach(category => {
                category.set('open', false);
            });

            clearInterval(waitUntilReady);

            // ✅ 1. Inject Styles
            builderAssets.styles.forEach((href) => {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.href = `${window.baseUrl}/${href}`;
                link.type = "text/css";
                iframeDoc.head.appendChild(link);
            });

            // ✅ 1b. Inject RTL Stylesheet if needed
            if (window.textDirection === 'rtl') {
                const rtlLink = document.createElement("link");
                rtlLink.rel = "stylesheet";
                rtlLink.href = `${window.baseUrl}/frontend/css/rtl.css`;
                rtlLink.type = "text/css";
                iframeDoc.head.appendChild(rtlLink);
            }

            // ✅ 2. Theme Root Style
            if (window.themeRootStyle) {
                iframeDoc.head.insertAdjacentHTML("beforeend", window.themeRootStyle);
            }

            // ✅ 3. Inject Scripts
            builderAssets.scripts.forEach((src) => {
                const script = document.createElement("script");
                script.src = `${window.baseUrl}/${src}`;
                script.type = "text/javascript";
                script.defer = true;
                iframeDoc.body.appendChild(script);
            });
        }
    }, 100); // check every 100ms until iframe is ready
}