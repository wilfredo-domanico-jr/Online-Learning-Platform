/**
 * Laravel validation failures return 422 with { message, errors: { field: [msg, ...] } }.
 * Flattens that into { field: "first message" } for simple form display.
 */
export function fieldErrors(error) {
    const errors = error?.response?.data?.errors;

    if (!errors) return {};

    return Object.fromEntries(
        Object.entries(errors).map(([field, messages]) => [field, messages[0]])
    );
}

export function generalError(error) {
    return error?.response?.data?.message ?? 'Something went wrong. Please try again.';
}
