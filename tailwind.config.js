import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    primary: '#2E6FD8',
                    secondary: '#2BB3A3',
                    accent: '#F5A524',
                    danger: '#D93B3B',
                    ink: '#1F2937',
                    soft: '#F3F7FC',
                },
            },
            fontFamily: {
                sans: [...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
