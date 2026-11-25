import { Controller } from "@hotwired/stimulus";
import { enhance } from "./markdown-editor.js";

/**
 * Kept for applications that registered it: the editor now starts by itself
 * on any textarea[data-ddd-markdown] (see markdown-editor.js), Stimulus or
 * not. This controller only enhances its textarea target, once.
 */
export default class extends Controller {
    static targets = ["textarea"];

    connect() {
        if (this.hasTextareaTarget) {
            enhance(this.textareaTarget);
        }
    }
}
