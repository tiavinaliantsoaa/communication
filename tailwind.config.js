import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        'bg-yellow-100', 'text-yellow-800', 'bg-yellow-400', 'text-yellow-900',
        'bg-blue-100', 'text-blue-800', 'bg-blue-500',
        'bg-red-100', 'text-red-800', 'bg-red-500',
        'bg-emerald-100', 'text-emerald-800', 'bg-emerald-500',
        'bg-cyan-100', 'text-cyan-800', 'bg-cyan-400', 'text-cyan-900',
        'bg-purple-100', 'text-purple-800', 'bg-purple-500',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                escm: {
                    navy: '#0f172a',
                    sidebar: '#1e293b',
                    primary: '#2563eb',
                    'primary-dark': '#1d4ed8',
                },
            },
        },
    },

    plugins: [forms],
};
