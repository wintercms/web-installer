const asset = {
  inserted(el, bind) {
    const srcTags = ['js', 'img'];
    const hrefTags = ['css', 'a'];

    if (srcTags.indexOf(el.tagName.toLowerCase()) !== -1) {
      el.setAttribute('src', process.env.BASE_URL + bind.value);
      return;
    }

    if (hrefTags.indexOf(el.tagName.toLowerCase()) !== -1) {
      el.setAttribute('href', process.env.BASE_URL + bind.value);
    }
  },
};

export default asset;
