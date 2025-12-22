import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.default.css";

export default class extends Controller {
    static targets = ["select"];
    static values = {
        url: String,
        placeholder: String,
        minLength: Number,
        limit: Number,
        initialText: String,
    };

    connect() {
        const hiddenInput = this.element.querySelector('input[type="hidden"], input');
        const selectEl = this.selectTarget;
        console.log(this);

        if (!hiddenInput) {
            console.warn("[autocomplete] hidden input not found");
            return;
        }

        // if (selectEl.options.length === 0) {
        //     const opt = document.createElement("option");
        //     opt.value = "";
        //     opt.textContent = "";
        //     selectEl.appendChild(opt);
        // }

        if (hiddenInput.value && this.initialTextValue) {
            const opt = new Option(this.initialTextValue, hiddenInput.value, true, true);
            selectEl.appendChild(opt);
        }

        this.tom = new TomSelect(selectEl, {
            create: false,
            allowEmptyOption: true,
            placeholder: this.placeholderValue || "Rechercher…",
            maxOptions: this.limitValue || 20,
            valueField: "id",
            labelField: "text",
            searchField: ["text"],
            preload: false,

            load: async (query, callback) => {
                const q = (query ?? "").trim();
                const min = this.minLengthValue ?? 2;

                if (q.length < min) {
                    callback([]);
                    return;
                }

                try {
                    const url = new URL(this.urlValue, window.location.origin);
                    url.searchParams.set("q", q);
                    url.searchParams.set("limit", String(this.limitValue ?? 20));

                    const res = await fetch(url.toString(), {
                        headers: { "Accept": "application/json" },
                    });

                    if (!res.ok) {
                        callback([]);
                        return;
                    }

                    const data = await res.json();

                    callback(data.results ?? []);
                } catch (e) {
                    console.error("[autocomplete] load failed", e);
                    callback([]);
                }
            },

            onChange: (value) => {
                hiddenInput.value = value || "";
            },
        });

        if (hiddenInput.value) {
            this.tom.setValue(hiddenInput.value, true);
        }
    }

    disconnect() {
        if (this.tom) {
            this.tom.destroy();
            this.tom = null;
        }
    }
}
