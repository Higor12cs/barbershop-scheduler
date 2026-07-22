export function whatsappDigits(phone) {
    const digits = String(phone ?? '').replace(/\D/g, '');

    if (digits === '') {
        return null;
    }

    return digits.length <= 11 ? `55${digits}` : digits;
}

export function whatsappUrl(phone, message = '') {
    const digits = whatsappDigits(phone);

    if (digits === null) {
        return null;
    }

    const base = `https://wa.me/${digits}`;

    return message ? `${base}?text=${encodeURIComponent(message)}` : base;
}
