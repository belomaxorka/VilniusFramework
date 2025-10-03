import { ref, computed } from 'vue';

/**
 * Composable for API requests
 * 
 * @param {string} baseUrl - Base URL for API
 * @returns {Object} API methods and state
 */
export function useApi(baseUrl = '/api') {
  const loading = ref(false);
  const error = ref(null);
  const data = ref(null);

  /**
   * Make a request
   * @param {string} endpoint 
   * @param {Object} options 
   * @returns {Promise}
   */
  const request = async (endpoint, options = {}) => {
    loading.value = true;
    error.value = null;

    const url = `${baseUrl}${endpoint}`;
    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    };

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
      defaultOptions.headers['X-CSRF-TOKEN'] = csrfToken;
    }

    try {
      const response = await fetch(url, {
        ...defaultOptions,
        ...options,
        headers: {
          ...defaultOptions.headers,
          ...options.headers,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      data.value = result;
      return result;
    } catch (e) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  /**
   * GET request
   */
  const get = (endpoint, options = {}) => {
    return request(endpoint, { ...options, method: 'GET' });
  };

  /**
   * POST request
   */
  const post = (endpoint, body, options = {}) => {
    return request(endpoint, {
      ...options,
      method: 'POST',
      body: JSON.stringify(body),
    });
  };

  /**
   * PUT request
   */
  const put = (endpoint, body, options = {}) => {
    return request(endpoint, {
      ...options,
      method: 'PUT',
      body: JSON.stringify(body),
    });
  };

  /**
   * DELETE request
   */
  const del = (endpoint, options = {}) => {
    return request(endpoint, { ...options, method: 'DELETE' });
  };

  /**
   * Clear error
   */
  const clearError = () => {
    error.value = null;
  };

  const isLoading = computed(() => loading.value);
  const hasError = computed(() => error.value !== null);

  return {
    // State
    loading: isLoading,
    error,
    data,
    hasError,

    // Methods
    request,
    get,
    post,
    put,
    delete: del,
    clearError,
  };
}

