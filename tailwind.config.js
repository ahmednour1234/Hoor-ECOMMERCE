import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/**
 * HOOR design system.
 *
 * Colour ramps are derived from the brand kit in ui/hoor-design-tokens.json.
 * The 500/600 steps are the canonical brand values; lighter and darker steps
 * exist so components can build hierarchy without inventing off-brand colours.
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

    /*
     * Blade components compose these names at runtime (e.g. 'btn-'.$variant),
     * so the content scanner never sees them as literals. Without safelisting,
     * <x-ui.button variant="primary"> would render unstyled.
     */
    safelist: [
        'btn-primary', 'btn-secondary', 'btn-outline', 'btn-ghost', 'btn-gold', 'btn-danger',
        'btn-sm', 'btn-lg',
        'badge-navy', 'badge-denim', 'badge-gold', 'badge-neutral',
        'badge-success', 'badge-warning', 'badge-danger',
        'card', 'card-hover',
    ],

    theme: {
        extend: {
            colors: {
                'hoor-navy': {
                    50:  '#EEF3F8',
                    100: '#D6E1EC',
                    200: '#AEC2D8',
                    300: '#7B99B8',
                    400: '#4A6D93',
                    500: '#0C2F53', // brand navy800 — primary
                    600: '#0A2846',
                    700: '#082540', // brand navy900
                    800: '#061C31',
                    900: '#041322',
                    DEFAULT: '#0C2F53',
                },
                'hoor-denim': {
                    50:  '#F2F6FA',
                    100: '#E1EAF3',
                    200: '#C4D5E6',
                    300: '#A3BDD5',
                    400: '#7E9BB7', // brand denim400
                    500: '#416A8F', // brand denim700 — secondary
                    600: '#375B7A',
                    700: '#2D4A64',
                    800: '#23394E',
                    900: '#1A2B3B',
                    DEFAULT: '#416A8F',
                },
                'hoor-cream': {
                    50:  '#FFFDF9', // brand white
                    100: '#F7F3ED', // brand cream100
                    200: '#F0EAE0',
                    300: '#E6DED4', // border token
                    400: '#D9CEC0',
                    500: '#C9BCAB',
                    DEFAULT: '#F7F3ED',
                },
                'hoor-beige': {
                    50:  '#FAF6F1',
                    100: '#F2E9DE',
                    200: '#E7D9C9',
                    300: '#D8C6B3', // brand sand300
                    400: '#C7B09A',
                    500: '#B39A82',
                    600: '#96806B',
                    DEFAULT: '#D8C6B3',
                },
                'hoor-gold': {
                    50:  '#FBF7F0',
                    100: '#F4EADA',
                    200: '#E8D5B5',
                    300: '#D8BC8C',
                    400: '#C8A570',
                    500: '#B99355', // brand gold500 — accent
                    600: '#9E7B44',
                    700: '#7E6236',
                    DEFAULT: '#B99355',
                },
                'hoor-charcoal': '#1F2933',
                'hoor-muted': '#637083',
            },

            fontFamily: {
                // Latin UI + display
                sans:    ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['"Playfair Display"', 'Georgia', 'serif'],
                // Arabic UI + display, applied via the [lang="ar"] base rule
                arabic:  ['"Noto Sans Arabic"', ...defaultTheme.fontFamily.sans],
                'arabic-display': ['"Noto Naskh Arabic"', 'serif'],
            },

            borderRadius: {
                sm: '8px',
                md: '14px',
                lg: '22px',
            },

            boxShadow: {
                soft: '0 10px 28px rgba(8, 37, 64, 0.10)',
                card: '0 8px 24px rgba(8, 37, 64, 0.08)',
                'card-hover': '0 16px 40px rgba(8, 37, 64, 0.14)',
            },

            spacing: {
                18: '4.5rem',
                22: '5.5rem',
            },

            maxWidth: {
                'screen-2xl': '1440px',
            },

            letterSpacing: {
                editorial: '0.18em',
            },

            transitionTimingFunction: {
                'hoor': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },

            keyframes: {
                'fade-up': {
                    '0%':   { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },

            animation: {
                'fade-up': 'fade-up 0.5s cubic-bezier(0.4, 0, 0.2, 1) both',
            },
        },
    },

    plugins: [forms, typography],
};
