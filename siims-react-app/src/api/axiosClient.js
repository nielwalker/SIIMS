import Axios from "axios";
import { showFailedAlert, showSuccessAlert } from "../utils/toastify";

// Axios Client Service
const axiosClient = Axios.create({
  baseURL: `${import.meta.env.VITE_API_BASE_URL}`,
  timeout: 60000,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
  headers: {
    Accept: "application/json",
  },
});

// Request Interceptors
axiosClient.interceptors.request.use((config) => {
  config.headers = {
    ...config.headers,
    Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
  };

  // Handle Content-Type based on payload type
  if (config.data instanceof FormData) {
    // If sending FormData, don't set Content-Type manually (let Axios handle it)
    delete config.headers['Content-Type'];

    console.log(config.headers);
  } else if (config.data) {
    // If sending JSON, set Content-Type to application/json
    config.headers['Content-Type'] = 'application/json';

    // console.log(config.headers);
  }


  // console.log(config);
  return config;
});

// Response Interceptors
axiosClient.interceptors.response.use(
  (response) => {
    // Successful Responses
    // Status 200

    // Status 201
    if (response && response.status === 201) {
      showSuccessAlert(response.data.message);
    }

    // Return response
    return response;
  },
  (error) => {
    const { response } = error;

    // General error handling based on HTTP status codes
    if (response) {
      switch (response.status) {
        case 401:
          handleUnauthorizedError(response, error.config);
          break;
        case 404:
          showFailedAlert(
            response.data.message || "The requested resource was not found."
          );
          break;
        case 403:
          showFailedAlert(
            response.data.message ||
              "You do not have permission to access this resource."
          );
          break;
        case 409:
          showFailedAlert(
            response.data.message || "There was a conflict with your request."
          );
          break;
        default:
          showFailedAlert(
            response.data.message || "An unexpected error occurred."
          );
      }
    } else {
      // If no response from server (e.g., network error)
      showFailedAlert("Network error. Please check your internet connection.");
    }

    return Promise.reject(error);
  }
);

// Handle 401 errors (Unauthorized)
function handleUnauthorizedError(response, config) {
  const errorMessage =
    response.data.message || "Unauthorized access. Please log in again.";

  // When backend truly says unauthenticated, log out; otherwise just notify
  const msg = String(errorMessage || "").toLowerCase();
  const isUnauthenticated = msg.includes("unauthenticated");
  const isAuthEndpoint = typeof config?.url === 'string' && config.url.includes('/api/v1/auth');

  if (isUnauthenticated || isAuthEndpoint) {
    // Show alert with error message
    showFailedAlert(errorMessage);

    // Remove sensitive data from localStorage
    localStorage.removeItem("ACCESS_TOKEN");
    localStorage.removeItem("user");
    localStorage.removeItem("roles");

    // Optionally clear session storage or other sensitive data
    sessionStorage.clear();

    // Redirect user to login page
    window.location.href = "/login";
  } else {
    // Do not auto-logout on feature endpoints that might return 401 for business rules
    showFailedAlert(errorMessage || "You are not authorized to perform this action.");
  }
}

// Export axiosClient service
export default axiosClient;
