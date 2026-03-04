import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './src/**/*.{js,ts,jsx,tsx}',
    './src/components/**/*.{js,ts,jsx,tsx}'
  ],
  important: '.web-components-wrapper',
  theme: {
    extend: {}
  },
  plugins: []
};

export default config;
