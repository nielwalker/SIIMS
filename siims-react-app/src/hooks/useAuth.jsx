// Libraries
import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
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

const AUTO_LOGOUT_MINUTES = Number(import.meta.env.VITE_AUTO_LOGOUT_MINUTES ?? 15);
const AUTO_LOGOUT_MS = AUTO_LOGOUT_MINUTES * 60 * 1000;
const AUTO_LOGOUT_MESSAGE =
  "For security purposes, your session timed out due to inactivity. Please sign in again.";

export const AuthProvider = ({ children }) => {
  // Loading State
  const [loading, setLoading] = useState(false);

  // User State
  const [user, setUser] = useLocalStorage("user", null);
  const [roles, setRoles] = useLocalStorage("roles", null);
  const [token, setToken] = useLocalStorage("ACCESS_TOKEN", null);

  const logoutTimerRef = useRef(null);

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
          const message =
            data?.message || "Credentials Doesnt match on the Records";
          return { _general: message };
        }
        return { _error: data?.message || "Login failed." };
      }
      return { _error: "Network error. Please try again." };
    } finally {
      setLoading(false);
    }
  };

  // Function to log out the authenticated user
  const logout = useCallback(
    async (arg) => {
      let options = {};
      if (arg && typeof arg === "object" && "preventDefault" in arg) {
        arg.preventDefault();
      } else if (arg) {
        options = arg;
      }

      const { reason } = options;
      if (reason) {
        localStorage.setItem("loginError", reason);
      } else {
        localStorage.removeItem("loginError");
      }

      setLoading(true);
      try {
        await axiosClient.post("/api/v1/auth/logout");
      } catch (error) {
        console.error("Logout failed:", error);
      } finally {
        if (logoutTimerRef.current) {
          clearTimeout(logoutTimerRef.current);
          logoutTimerRef.current = null;
        }

        localStorage.removeItem("ACCESS_TOKEN");
        localStorage.removeItem("user");
        localStorage.removeItem("roles");
        setUser(null);
        setToken(null);
        setRoles(null);
        setLoading(false);
        window.location.href = "/login";
      }
    },
    [setLoading, setRoles, setToken, setUser]
  );

  // Auto logout when inactive
  useEffect(() => {
    const activityEvents = ["mousemove", "keydown", "click", "scroll", "touchstart"];

    const resetTimer = () => {
      if (!token) {
        return;
      }

      if (logoutTimerRef.current) {
        clearTimeout(logoutTimerRef.current);
      }

      logoutTimerRef.current = setTimeout(() => {
        logout({ reason: AUTO_LOGOUT_MESSAGE });
      }, AUTO_LOGOUT_MS);
    };

    if (token) {
      activityEvents.forEach((event) => window.addEventListener(event, resetTimer));
      resetTimer();
    }

    return () => {
      activityEvents.forEach((event) => window.removeEventListener(event, resetTimer));
      if (logoutTimerRef.current) {
        clearTimeout(logoutTimerRef.current);
        logoutTimerRef.current = null;
      }
    };
  }, [token, logout]);

  // Use Memo
  const value = useMemo(
    () => ({
      user,
      token,
      roles,
      login,
      logout,
    }),
    [login, logout, roles, token, user]
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
