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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // The app's three-color palette (plus neutral gray/white/black).
                // brand = deep green (primary actions, navigation, positive figures)
                // accent = warm amber (secondary actions, highlights, alerts)
                brand: {
                    50: '#eefaf2',
                    100: '#d6f0dd',
                    200: '#aee0bd',
                    300: '#7cc999',
                    400: '#4aab74',
                    500: '#2f8c5a',
                    600: '#217048',
                    700: '#1c5a3c',
                    800: '#194832',
                    900: '#153c2b',
                },
                accent: {
                    50: '#fdf6e9',
                    100: '#faebc7',
                    200: '#f4d48a',
                    300: '#edb94f',
                    400: '#e5a12a',
                    500: '#c9821a',
                    600: '#a56414',
                    700: '#824c14',
                    800: '#6b3d16',
                    900: '#5a3316',
                },
            },
        },
    },

    plugins: [forms],
};
