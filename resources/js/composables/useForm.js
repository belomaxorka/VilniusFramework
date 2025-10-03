import { reactive, ref, computed } from 'vue';

/**
 * Composable for form handling
 * 
 * @param {Object} initialData - Initial form data
 * @returns {Object} Form methods and state
 */
export function useForm(initialData = {}) {
  const form = reactive({ ...initialData });
  const errors = ref({});
  const processing = ref(false);
  const touched = ref({});

  /**
   * Reset form to initial state
   */
  const reset = () => {
    Object.keys(form).forEach((key) => {
      form[key] = initialData[key];
    });
    clearErrors();
    touched.value = {};
  };

  /**
   * Set a field value
   */
  const setField = (field, value) => {
    form[field] = value;
    touched.value[field] = true;
    if (errors.value[field]) {
      delete errors.value[field];
    }
  };

  /**
   * Set errors
   */
  const setErrors = (newErrors) => {
    errors.value = { ...newErrors };
  };

  /**
   * Clear all errors
   */
  const clearErrors = () => {
    errors.value = {};
  };

  /**
   * Clear error for specific field
   */
  const clearError = (field) => {
    if (errors.value[field]) {
      delete errors.value[field];
    }
  };

  /**
   * Check if field has error
   */
  const hasError = (field) => {
    return !!errors.value[field];
  };

  /**
   * Get error for field
   */
  const getError = (field) => {
    return errors.value[field];
  };

  /**
   * Mark field as touched
   */
  const touch = (field) => {
    touched.value[field] = true;
  };

  /**
   * Check if field is touched
   */
  const isTouched = (field) => {
    return !!touched.value[field];
  };

  /**
   * Submit form
   */
  const submit = async (callback) => {
    processing.value = true;
    clearErrors();

    try {
      const result = await callback(form);
      return result;
    } catch (error) {
      // Handle validation errors
      if (error.response && error.response.status === 422) {
        setErrors(error.response.data.errors || {});
      }
      throw error;
    } finally {
      processing.value = false;
    }
  };

  /**
   * Validate single field
   */
  const validate = (field, rules) => {
    const value = form[field];
    
    for (const rule of rules) {
      const error = rule(value, form);
      if (error) {
        errors.value[field] = error;
        return false;
      }
    }
    
    clearError(field);
    return true;
  };

  const hasErrors = computed(() => Object.keys(errors.value).length > 0);
  const isProcessing = computed(() => processing.value);

  return {
    // Form data
    form,
    errors,
    processing: isProcessing,
    hasErrors,

    // Methods
    reset,
    setField,
    setErrors,
    clearErrors,
    clearError,
    hasError,
    getError,
    touch,
    isTouched,
    submit,
    validate,
  };
}

// Common validation rules
export const rules = {
  required: (message = 'This field is required') => (value) => {
    if (!value || (typeof value === 'string' && !value.trim())) {
      return message;
    }
    return null;
  },

  email: (message = 'Invalid email address') => (value) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (value && !emailRegex.test(value)) {
      return message;
    }
    return null;
  },

  minLength: (min, message) => (value) => {
    if (value && value.length < min) {
      return message || `Minimum length is ${min} characters`;
    }
    return null;
  },

  maxLength: (max, message) => (value) => {
    if (value && value.length > max) {
      return message || `Maximum length is ${max} characters`;
    }
    return null;
  },

  min: (min, message) => (value) => {
    if (value && Number(value) < min) {
      return message || `Minimum value is ${min}`;
    }
    return null;
  },

  max: (max, message) => (value) => {
    if (value && Number(value) > max) {
      return message || `Maximum value is ${max}`;
    }
    return null;
  },

  confirmed: (field, message = 'Passwords do not match') => (value, form) => {
    if (value !== form[field]) {
      return message;
    }
    return null;
  },
};

