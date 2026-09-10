<script setup>
defineProps({ name: String });
const paths = {
    home: 'M3 10l9-7 9 7v10H3z M9 20v-7h6v7',
    chat: 'M21 11a8 8 0 0 1-8 8H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z M7 8h10 M7 12h6',
    video: 'M3 5h12v14H3z M15 10l6-4v12l-6-4',
    calendar: 'M3 5h18v16H3z M7 2v6 M17 2v6 M3 11h18 M8 15h3',
    pill: 'M8 4a6 6 0 0 1 8 8l-4 4a6 6 0 0 1-8-8z M7 5l8 8',
    heart: 'M20 4c-3-2-6 0-8 2-2-2-5-4-8-2-4 4 0 9 8 16 8-7 12-12 8-16z',
    user: 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0 M4 21v-3a8 6 0 0 1 16 0v3',
    list: 'M6 3h12v18H6z M9 8h6 M9 12h6 M9 16h4',
    shield: 'M12 2l9 4v6c0 5-9 10-9 10S3 17 3 12V6z M8 12l3 3 5-6',
    palette:
        'M12 3a9 9 0 1 0 0 18h2a2 2 0 0 0 0-4h-1a2 2 0 0 1 0-4h4a4 4 0 0 0 4-4c0-3-5-6-9-6 M7 9h.01 M11 6h.01 M16 7h.01',
    link: 'M10 14l4-4 M8 16l-2 2a4 4 0 0 1-5-5l5-5a4 4 0 0 1 5 0 M16 8l2-2a4 4 0 0 1 5 5l-5 5a4 4 0 0 1-5 0',
};
</script>
<template>
    <svg
        width="21"
        height="21"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="paths[name] || paths.heart" />
    </svg>
</template>
