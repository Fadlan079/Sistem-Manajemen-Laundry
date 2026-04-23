import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                // Disamakan dengan warna di app.css
                primary: "#E30613",
                secondary: "#FFE800",
                "primary-fixed": "#ff7763",
                "secondary-fixed": "#FFE800",
                surface: "#FFFFFF",
                "surface-container": "#F9FAFB",
                "surface-container-lowest": "#ffffff",
                "on-surface": "#1F2937",
                "on-surface-variant": "#6B7280",
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            }
        },
    },
    plugins: [forms],
}