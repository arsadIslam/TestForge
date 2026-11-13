import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/**
 * "Clean academic" design system: indigo brand + amber accent, slate neutrals,
 * Plus Jakarta Sans for headings and Inter for text. Changing it here restyles every page.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                gray: colors.slate, // cooler neutrals everywhere, no page edits needed
                brand: colors.indigo,
                accent: colors.amber,
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                lg: '0.75rem', // softer cards everywhere
            },
            boxShadow: {
                // a hairline ring + soft shadow: cards read as cards without heavy borders
                sm: '0 0 0 1px rgb(15 23 42 / 0.05), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                soft: '0 0 0 1px rgb(15 23 42 / 0.04), 0 8px 24px -6px rgb(15 23 42 / 0.12)',
            },
        },
    },

    plugins: [forms],
};
