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
        initialIcon: String,
        initialIconWidth: Number,
        initialIconHeight: Number,
    };

    connect() {
        const hiddenInput = this.element.querySelector('input[type="hidden"], input');
        const selectEl = this.selectTarget;

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

        const initialData = hiddenInput.value && this.initialTextValue ? {
            id: hiddenInput.value,
            text: this.initialTextValue,
            icon: this.initialIconValue || null,
            width: this.initialIconWidthValue || 20,
            height: this.initialIconHeightValue || 20,
        } : null;

        this.tom = new TomSelect(selectEl, {
            create: false,
            allowEmptyOption: true,
            placeholder: this.placeholderValue || "Rechercher…",
            maxOptions: this.limitValue || 20,
            valueField: "id",
            labelField: "text",
            searchField: ["text"],
            preload: false,

            plugins: {
                clear_button: {
                    title: "Supprimer"
                }
            },

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

            render: {
                option: (item) => {
                    let optionImg = item.icon ? `<img 
                        src="${item.icon}" 
                        alt="${item.text}" 
                        class="autocomplete-option-img" 
                        style="max-width: ${item.width ?? 20}px; max-height: ${item.height ?? 20}px; margin-right: 5px;"
                    />` : "";
                    return `<div>${optionImg} ${item.text}</div>`;
                },
                item: (item) => {
                    let itemImg = item.icon ? `<img 
                        src="${item.icon}" 
                        alt="${item.text}" 
                        class="autocomplete-option-img" 
                        style="max-width: ${item.width ?? 20}px; max-height: ${item.height ?? 20}px; margin-right: 5px;"
                    />` : "";
                    return `<div>${itemImg} ${item.text}</div>`;
                },
            },

            onChange: (value) => {
                hiddenInput.value = value || "";
            },
        });

        if (initialData) {
            this.tom.addOption(initialData);
            this.tom.addItem(initialData.id, true);
            this.tom.refreshItems();
        }
    }

    disconnect() {
        if (this.tom) {
            this.tom.destroy();
            this.tom = null;
        }
    }
}
