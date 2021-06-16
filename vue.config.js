const path = require('path');

module.exports = {
  css: {
    loaderOptions: {
      scss: {
        additionalData: (content, loaderContext) => {
          const { resourcePath, rootContext } = loaderContext;
          const relativePath = path.relative(rootContext, resourcePath);

          if (relativePath === 'src/assets/scss/_variables.scss') {
            return content;
          }

          return `@import "~@/assets/scss/variables.scss";\n${content}`;
        },
      },
    },
  },
  assetsDir: 'install/assets',
  indexPath: 'install.htm',
  lintOnSave: false,
  productionSourceMap: false,
  publicPath: './',
};
