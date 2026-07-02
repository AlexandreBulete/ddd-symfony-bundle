import { Controller } from "@hotwired/stimulus";

// TODO: finalize implementation, tests, and documentation
export default class extends Controller {
    static targets = ["canvas", "source"];

    show() {
        const reader = new FileReader();

        reader.onload = function () {
            this.canvasTarget.removeAttribute("hidden");

            this.canvasTarget.src = reader.result;
        }.bind(this)

        reader.readAsDataURL(this.sourceTarget.files[0]);
    }
}