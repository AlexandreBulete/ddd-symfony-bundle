// A WYSIWYG editor over any <textarea data-ddd-markdown>: Milkdown Crepe,
// writing Markdown back into the textarea, which the form posts as usual.
//
// Self-starting, no Stimulus needed: the textarea stays visible until the
// editor is up, so a script that fails to load never hides the content.
// Built once into assets/dist/ (npm run build:milkdown, in a Node
// container), served by AssetMapper: applications need no Node build.
import { CrepeBuilder } from "@milkdown/crepe/builder";
import { blockEdit } from "@milkdown/crepe/feature/block-edit";
import { cursor } from "@milkdown/crepe/feature/cursor";
import { linkTooltip } from "@milkdown/crepe/feature/link-tooltip";
import { listItem } from "@milkdown/crepe/feature/list-item";
import { placeholder } from "@milkdown/crepe/feature/placeholder";
import { table } from "@milkdown/crepe/feature/table";
import { toolbar } from "@milkdown/crepe/feature/toolbar";
import { uploadConfig } from "@milkdown/kit/plugin/upload";
import "@milkdown/crepe/theme/common/reset.css";
import "@milkdown/crepe/theme/common/prosemirror.css";
import "@milkdown/crepe/theme/common/block-edit.css";
import "@milkdown/crepe/theme/common/cursor.css";
import "@milkdown/crepe/theme/common/link-tooltip.css";
import "@milkdown/crepe/theme/common/list-item.css";
import "@milkdown/crepe/theme/common/placeholder.css";
import "@milkdown/crepe/theme/common/table.css";
import "@milkdown/crepe/theme/common/toolbar.css";
import "@milkdown/crepe/theme/frame.css";
import "./milkdown-editor.css";

const LABELS = {
    fr: {
        placeholder: "Écrivez, ou tapez / pour insérer un bloc…",
        textGroup: {
            label: "Texte", text: { label: "Paragraphe" }, h1: { label: "Titre 1" }, h2: { label: "Intertitre" },
            h3: { label: "Sous-intertitre" }, h4: { label: "Titre 4" }, h5: { label: "Titre 5" }, h6: { label: "Titre 6" },
            quote: { label: "Citation" }, divider: { label: "Séparateur" },
        },
        listGroup: { label: "Listes", bulletList: { label: "Liste à puces" }, orderedList: { label: "Liste numérotée" }, taskList: { label: "Cases à cocher" } },
        advancedGroup: { label: "Avancé", table: { label: "Tableau" } },
        toolbar: { boldLabel: "Gras", italicLabel: "Italique", strikethroughLabel: "Barré", linkLabel: "Lien", codeLabel: "Code" },
    },
    en: {
        placeholder: "Write, or type / to insert a block…",
        textGroup: {
            label: "Text", text: { label: "Paragraph" }, h1: { label: "Heading 1" }, h2: { label: "Heading 2" },
            h3: { label: "Heading 3" }, h4: { label: "Heading 4" }, h5: { label: "Heading 5" }, h6: { label: "Heading 6" },
            quote: { label: "Quote" }, divider: { label: "Divider" },
        },
        listGroup: { label: "Lists", bulletList: { label: "Bullet list" }, orderedList: { label: "Numbered list" }, taskList: { label: "Task list" } },
        advancedGroup: { label: "Advanced", table: { label: "Table" } },
        toolbar: { boldLabel: "Bold", italicLabel: "Italic", strikethroughLabel: "Strikethrough", linkLabel: "Link", codeLabel: "Code" },
    },
};

function labelsFor(locale) {
    return LABELS[(locale || document.documentElement.lang || "en").slice(0, 2)] ?? LABELS.en;
}

/**
 * The building block, for whoever wires the editor by hand (a Stimulus
 * controller, a live component).
 */
export async function createCrepe({ rootSelector, root, defaultValue, locale }) {
    const labels = labelsFor(locale);
    // Only what a text needs: no pictures (kept as blob: or base64, they
    // belong to a media library), no formulas, no code editor.
    const crepe = new CrepeBuilder({ root: root ?? rootSelector, defaultValue: defaultValue ?? "" })
        .addFeature(cursor)
        .addFeature(listItem)
        .addFeature(linkTooltip)
        .addFeature(table)
        .addFeature(placeholder, { text: labels.placeholder, mode: "doc" })
        .addFeature(blockEdit, { textGroup: labels.textGroup, listGroup: labels.listGroup, advancedGroup: labels.advancedGroup })
        .addFeature(toolbar, labels.toolbar);
    // A pasted or dropped file is ignored rather than inlined into the text.
    crepe.editor.config((ctx) => {
        ctx.update(uploadConfig.key, (previous) => ({ ...previous, uploader: async () => [] }));
    });
    await crepe.create();

    return crepe;
}

export async function enhance(textarea) {
    if (textarea.dataset.dddMarkdownState) {
        return;
    }
    textarea.dataset.dddMarkdownState = "loading";

    const root = document.createElement("div");
    root.className = "ddd-markdown-editor";
    textarea.before(root);

    try {
        const crepe = await createCrepe({ root, defaultValue: textarea.value, locale: textarea.dataset.dddMarkdownLocale });
        crepe.on((listener) => {
            listener.markdownUpdated((ctx, markdown) => {
                textarea.value = markdown ?? "";
                textarea.dispatchEvent(new Event("input", { bubbles: true }));
                textarea.dispatchEvent(new Event("change", { bubbles: true }));
            });
        });
        // A hidden required field blocks the submit without a word: the
        // server-side validation still applies.
        textarea.required = false;
        textarea.hidden = true;
        textarea.dataset.dddMarkdownState = "ready";
    } catch (error) {
        root.remove();
        textarea.dataset.dddMarkdownState = "failed";
        console.error("[ddd-markdown] editor unavailable, plain textarea kept", error);
    }
}

function enhanceAll(scope = document) {
    scope.querySelectorAll("textarea[data-ddd-markdown]").forEach((textarea) => enhance(textarea));
}

if (typeof document !== "undefined") {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => enhanceAll());
    } else {
        enhanceAll();
    }
    // Forms re-rendered in place (Turbo, live components, collections).
    new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    if (node.matches("textarea[data-ddd-markdown]")) enhance(node);
                    else enhanceAll(node);
                }
            });
        }
    }).observe(document.documentElement, { childList: true, subtree: true });
}
