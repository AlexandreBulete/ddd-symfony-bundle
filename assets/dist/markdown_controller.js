import { Controller } from "@hotwired/stimulus";

// TODO: finalize implementation, tests, and documentation
export default class extends Controller {
    static targets = ["editor", "textarea"];

    async connect() {
        if (!this.editorTarget.id) {
            this.editorTarget.id = `milkdown-${(crypto.randomUUID?.() ?? Math.random().toString(16).slice(2))}`;
        }

        const rootSelector = `#${this.editorTarget.id}`;
        const defaultValue = this.textareaTarget.value ?? "";

        try {
            const mod = await import("/build/milkdown-editor.js");
            this.editor = await mod.createCrepe({
                rootSelector,
                defaultValue,
            });

            await this.editor.create();

            this.editor.on((listener) => {
                listener.markdownUpdated((ctx, markdown) => {
                    this.textareaTarget.value = markdown ?? "";
                    this.textareaTarget.dispatchEvent(new Event("input", { bubbles: true }));
                    this.textareaTarget.dispatchEvent(new Event("change", { bubbles: true }));
                });
            });

        } catch (e) {
            console.error("[Milkdown] failed to load/create", e);
        }
    }

    disconnect() {
        this.crepe?.destroy?.();
        this.crepe = null;
    }
}
