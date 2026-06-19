// Shared card-face helpers used by every board view (Board, Table, Calendar, Timeline)
// so cards look and behave consistently no matter how they are rendered.
import dayjs from 'dayjs';

export const LABEL_CLASSES = {
    green:  'bg-emerald-300 text-emerald-900',
    yellow: 'bg-yellow-300 text-yellow-900',
    orange: 'bg-orange-300 text-orange-900',
    red:    'bg-red-300 text-red-900',
    purple: 'bg-purple-300 text-purple-900',
    blue:   'bg-sky-300 text-sky-900',
    pink:   'bg-pink-300 text-pink-900',
    slate:  'bg-slate-300 text-slate-900',
};

export function labelClass(color) {
    return LABEL_CLASSES[color] ?? 'bg-gray-300 text-gray-800';
}

export function labelDotClass(color) {
    const map = {
        green: 'bg-emerald-400', yellow: 'bg-yellow-400', orange: 'bg-orange-400',
        red: 'bg-red-400', purple: 'bg-purple-400', blue: 'bg-sky-400',
        pink: 'bg-pink-400', slate: 'bg-slate-400',
    };
    return map[color] ?? 'bg-gray-400';
}

export function shortDue(iso) {
    return iso ? dayjs(iso).format('D MMM') : '';
}

// Colour treatment for a card's due-date badge based on whether it's done/overdue/soon.
export function dueClass(card) {
    if (!card.due_date) return 'bg-gray-100 text-gray-500';
    if (card.due_done) return 'bg-emerald-100 text-emerald-700';
    const due = dayjs(card.due_date);
    if (due.isBefore(dayjs())) return 'bg-red-100 text-red-700';
    if (due.isBefore(dayjs().add(1, 'day'))) return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-500';
}

export function hasMeta(card) {
    return card.due_date || card.checklist_total > 0 || card.description
        || card.attachments.length || card.comments.length;
}

// Flatten a board's lists into a single array of cards, each tagged with its
// owning list (name + id) so flat views (table/calendar/timeline) keep the link.
export function flattenCards(lists) {
    return lists.flatMap(list =>
        list.cards.map(card => ({
            ...card,
            listName: list.name,
            listId:   list.id,
        }))
    );
}
