import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: '#EF233C', // Punch Red — primary accent
                    dark:    '#D90429', // Flag Red — hover/darker
                    indigo:  '#2B2D42', // Space Indigo — sidebar/headings
                    grey:    '#8D99AE', // Lavender Grey — muted text
                    bg:      '#EDF2F4', // Platinum — page background
                    sidebar: '#252741', // slightly darker indigo for sidebar hover
                },
            },
        },
    },

    plugins: [forms],
};
