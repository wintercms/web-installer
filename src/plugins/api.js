export default {
  install(Vue) {
    Vue.prototype.$api = (method, endpoint, data) => {
      // Validate arguments
      if (typeof method !== 'string' || ['GET', 'POST'].indexOf(method) === -1) {
        throw new Error('Invalid method for API call, must be one of: GET, POST');
      }
      if (typeof endpoint !== 'string') {
        throw new Error('Endpoint must be provided as a string');
      }

      // Allow for API URL override
      let fullUrl = 'api.php';

      if (import.meta.env.VITE_APP_INSTALL_URL) {
        const baseUrl = import.meta.env.VITE_APP_INSTALL_URL;

        if (fullUrl.substr(-1, 1) !== '/') {
          fullUrl = `${baseUrl}/${fullUrl}`;
        } else {
          fullUrl = `${baseUrl}${fullUrl}`;
        }
      }

      // Format provided data for either GET or POST
      let postBody = null;

      if (method === 'GET') {
        fullUrl = `${fullUrl}?endpoint=${endpoint}`;

        if (data && !Array.isArray(data)) {
          throw new Error('Data must be provided as an array');
        }

        if (data && data.length) {
          const dataUrl = data.join('&');
          fullUrl = `${fullUrl}&${dataUrl}`;
        }
      } else {
        if (data && typeof data !== 'object') {
          throw new Error('Data must be provided as an object');
        }

        data.endpoint = endpoint;
        postBody = JSON.stringify(data);
      }

      return new Promise(
        (resolve, reject) => {
          fetch(fullUrl, {
            method,
            body: postBody,
          }).then(
            (response) => {
              if (!response.ok) {
                response.json().then(
                  (jsonData) => {
                    resolve({
                      success: false,
                      error: jsonData.error,
                      data: jsonData.data || null,
                    });
                  },
                  () => {
                    reject(new Error('Unable to parse JSON response'));
                  },
                );
              } else {
                response.json().then(
                  (jsonData) => {
                    if (jsonData.success) {
                      resolve({
                        success: true,
                        data: jsonData.data || null,
                      });
                    } else {
                      resolve({
                        success: false,
                        error: jsonData.error,
                        data: jsonData.data || null,
                      });
                    }
                  },
                  () => {
                    reject(new Error('Unable to parse JSON response'));
                  },
                );
              }
            },
            () => {
              reject(new Error('An unknown AJAX error occured.'));
            },
          );
        },
      );
    };
  },
};
