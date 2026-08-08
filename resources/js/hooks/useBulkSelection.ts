import { useEffect, useState } from 'react';

/**
 * Row-selection state for a "select rows on this page, then bulk-delete
 * selected" pattern. Scoped to the current page/filter view on purpose —
 * selection resets whenever the visible id set changes (new page, new
 * search, new filter), rather than silently persisting a hidden selection
 * across navigations.
 */
export default function useBulkSelection(pageIds: number[]) {
    const [selected, setSelected] = useState<Set<number>>(new Set());
    const pageIdsKey = pageIds.join(',');

    useEffect(() => {
        setSelected(new Set());
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pageIdsKey]);

    const allOnPageSelected = pageIds.length > 0 && pageIds.every((id) => selected.has(id));

    function toggle(id: number) {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }

    function toggleAllOnPage() {
        setSelected((prev) => {
            const next = new Set(prev);
            if (allOnPageSelected) {
                pageIds.forEach((id) => next.delete(id));
            } else {
                pageIds.forEach((id) => next.add(id));
            }
            return next;
        });
    }

    function clear() {
        setSelected(new Set());
    }

    return {
        selectedIds: Array.from(selected),
        selectedCount: selected.size,
        isSelected: (id: number) => selected.has(id),
        allOnPageSelected,
        toggle,
        toggleAllOnPage,
        clear,
    };
}
