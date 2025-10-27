// Libraries
import React, { createContext, useContext, useMemo, useState } from "react";
import { Navigate, useNavigate } from "react-router-dom";

// Custom Hooks
import useLocalStorage from "./useLocalStorage";

// Services
import axiosClient from "../api/axiosClient";
import Loader from "../components/common/Loader";

// Create Auth Context
const AuthContext = createContext({
  user: null,
  token: null,
  roles: null,
  setUser: () => {},
  setToken: () => {},
  setRoles: () => {},
  login: async () => {},
  logout: async () => {},
});

// Auth Provider Component
export const AuthProvider = ({ children }) => {
  // Loading State
  const [loading, setLoading] = useState(false);

  // User State
  const [user, setUser] = useLocalStorage("user", null);
  const [roles, setRoles] = useLocalStorage("roles", null);
  const [token, setToken] = useLocalStorage("ACCESS_TOKEN", null);

  // Function to authenticate the user
  const login = async (payload = {}, setLoading, navigate) => {
    setLoading(true);

    try {
      // Fetch CSRF token
      await axiosClient.get("/sanctum/csrf-cookie", { withCredentials: true });

      // Attempt login
      const response = await axiosClient.post("/api/v1/auth/login", payload);

      // Remove any previous login errors
      localStorage.removeItem("loginError");

      // Set user, token, and roles
      setUser(response.data.user);
      setToken(response.data.token);
      setRoles(response.data.roles);

      // Store data in localStorage
      localStorage.setItem("user", JSON.stringify(response.data.user));
      localStorage.setItem("roles", JSON.stringify(response.data.roles));

      // Set Loading
      setLoading(false);

      // Redirect user after successful login
      navigate && navigate("/auth");
    } catch (error) {
      // Normalize errors so the form can display them
      if (error?.response) {
        const { status, data } = error.response;
        if (status === 422) {
          return data.errors; // field validation
        }
        if (status === 401) {
          return { password: [data?.message || "Invalid credentials."] };
        }
        return { _error: data?.message || "Login failed." };
      }
      return { _error: "Network error. Please try again." };
    } finally {
      setLoading(false);
    }
  };

  // Function to log out the authenticated user
  const logout = async () => {
    // Set Loading State
    setLoading(true);
    try {
      await axiosClient
        .post("/api/v1/auth/logout")
        .then((response) => {
          // console.log("Successful Log Out");

          // Remove Local Storages
          localStorage.removeItem("ACCESS_TOKEN");
          localStorage.removeItem("user");
          localStorage.removeItem("roles");

          window.location.href = "/login";
        })
        .catch((error) => {
          console.error("Logout failed:", error);

          // Optionally handle errors, e.g., display a toast notification
          throw error;
        });
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  // Use Memo
  const value = useMemo(
    () => ({
      user,
      token,
      roles,
      login,
      logout,
    }),
    [token]
  );

  return (
    <>
      <Loader loading={loading} />
      <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
    </>
  );
};

// Exporting useAuth
export const useAuth = () => {
  return useContext(AuthContext);
};
