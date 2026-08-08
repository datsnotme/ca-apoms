import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Agriculture green (primary) / dark green (secondary) / warm gold (accent)
                brand: {
                    50: '#f0f9f1',
                    100: '#dcf1de',
                    200: '#bce3c1',
                    300: '#8ecd97',
                    400: '#5cb069',
                    500: '#39944a',
                    600: '#2a7638',
                    700: '#235e2f',
                    800: '#1f4b29',
                    900: '#123018',
                },
                gold: {
                    50: '#fdf8ed',
                    100: '#faedc8',
                    200: '#f4d98d',
                    300: '#eebf52',
                    400: '#e8a92b',
                    500: '#d18c17',
                    600: '#b06d13',
                    700: '#8c5013',
                    800: '#734215',
                    900: '#603715',
                },
            },
        },
    },

    plugins: [forms],
};
