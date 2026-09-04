import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './modules/**/Resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    navy: '#2C4B60',
                    'navy-dark': '#243d4f',
                    orange: '#F27D42',
                    'orange-dark': '#e06a30',
                    teal: '#4BC6C8',
                    'teal-dark': '#3ab5b7',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 3px rgba(44, 75, 60, 0.06), 0 1px 2px rgba(44, 75, 60, 0.04)',
            },
        },
    },

    plugins: [forms],
};
