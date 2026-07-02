import { Crepe } from "@milkdown/crepe";
import "@milkdown/crepe/theme/common/style.css";
import "@milkdown/crepe/theme/frame.css";

export async function createCrepe({ rootSelector, defaultValue }) {
    const crepe = new Crepe({
        root: rootSelector,
        defaultValue: defaultValue ?? "",
    });

    await crepe.create();

    return crepe;
}
