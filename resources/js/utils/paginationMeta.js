/**
 * Normalize Laravel / Inertia paginator props.
 */
export const paginationMeta = (paginator) => {
    if (!paginator) {
        return {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
            per_page: 10,
        };
    }

    if (paginator.meta) {
        return paginator.meta;
    }

    return {
        current_page: paginator.current_page ?? 1,
        from: paginator.from ?? 0,
        to: paginator.to ?? 0,
        total: paginator.total ?? 0,
        last_page: paginator.last_page ?? 1,
        per_page: paginator.per_page ?? 10,
    };
};
