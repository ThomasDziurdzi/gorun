/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.html.twig",
    "./assets/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
             DEFAULT:'#e75d2f',
             light: '#ff9b7a'
        },
        navy: 'rgba(1, 24, 87, 1)'
      },
      fontFamily: {
         sans: ['Outfit', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}