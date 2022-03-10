import { defineConfig } from 'vite';
import { createVuePlugin } from 'vite-plugin-vue2';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [createVuePlugin()],
  base: './',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: (content, loaderContext) => {
          const relativePath = path.relative(__dirname, loaderContext);

          if (relativePath === 'src/assets/scss/_variables.scss') {
            return content;
          }

          return `@import "@/assets/scss/variables.scss";\n${content}`;
        },
      },
    },
  },
  server: {
    open: '/install.html',
  },
  build: {
    assetsDir: 'install/assets',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'install.html'),
      },
      output: {
        sourcemap: false,
      },
      makeAbsoluteExternalsRelative: true,
    },
  },
});
