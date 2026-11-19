import { useModalHolder } from "@pimcore/studio-ui-bundle/modules/app";
import { QuickActionsModal } from "../components/QuickActionsModal";

const MODAL_ID = "quick-actions-modal";

export interface QuickActionsModalControls {
    open: () => void;
    close: () => void;
}

/**
 * Reads context only (no state, memo or effect hooks), so it is safe inside a main nav item's useCustomMainNavItem,
 * which Studio calls while looping over a changing list of nav items.
 */
export function useQuickActionsModal(): QuickActionsModalControls {
    const { addModal } = useModalHolder();

    // Closing re-adds the modal as hidden rather than removing it: unmounting would abort running actions.
    const render = (isOpen: boolean) => {
        const modal = (
          <QuickActionsModal
            isOpen={ isOpen }
            onClose={ () => render(false) }
          />
        );

        addModal(MODAL_ID, modal);
    };

    return { open: () => render(true), close: () => render(false) };
}
