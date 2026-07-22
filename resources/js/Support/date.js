const longFormatter = new Intl.DateTimeFormat('pt-BR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const weekdayShortFormatter = new Intl.DateTimeFormat('pt-BR', { weekday: 'short' });
const dayMonthFormatter = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit' });

function pad(value) {
    return String(value).padStart(2, '0');
}

export function toISODate(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function parseISODate(value) {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function addDays(value, amount) {
    const date = typeof value === 'string' ? parseISODate(value) : new Date(value);
    date.setDate(date.getDate() + amount);

    return date;
}

export function startOfWeek(value) {
    const date = typeof value === 'string' ? parseISODate(value) : new Date(value);
    const day = date.getDay();
    const diff = day === 0 ? -6 : 1 - day;
    date.setDate(date.getDate() + diff);

    return date;
}

export function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

export function formatLongDate(value) {
    return capitalize(longFormatter.format(parseISODate(value)));
}

export function formatWeekdayShort(value) {
    return capitalize(weekdayShortFormatter.format(parseISODate(value)).replace('.', ''));
}

export function formatDayMonth(value) {
    return dayMonthFormatter.format(parseISODate(value));
}

export function weekDays(startISO) {
    const todayISO = toISODate(new Date());

    return Array.from({ length: 7 }, (_, offset) => {
        const date = addDays(startISO, offset);
        const iso = toISODate(date);

        return {
            date: iso,
            weekday: formatWeekdayShort(iso),
            day: String(date.getDate()),
            dayMonth: formatDayMonth(iso),
            isToday: iso === todayISO,
        };
    });
}

export function minutesToTime(minutes) {
    return `${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}`;
}

export function timeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

export function todayISO() {
    return toISODate(new Date());
}

export function nowMinutes() {
    const now = new Date();

    return now.getHours() * 60 + now.getMinutes();
}

export function birthdayProximity(birthDate) {
    if (!birthDate) {
        return null;
    }

    const [, month, day] = String(birthDate).split('-').map(Number);

    if (!month || !day) {
        return null;
    }

    const now = new Date();

    if (month === now.getMonth() + 1 && day === now.getDate()) {
        return 'today';
    }

    const birthday = toISODate(new Date(now.getFullYear(), month - 1, day));
    const weekStart = toISODate(startOfWeek(now));
    const weekEnd = toISODate(addDays(startOfWeek(now), 6));

    if (birthday >= weekStart && birthday <= weekEnd) {
        return 'week';
    }

    if (month === now.getMonth() + 1) {
        return 'month';
    }

    return null;
}
