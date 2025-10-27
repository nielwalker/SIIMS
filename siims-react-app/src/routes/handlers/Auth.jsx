// Libraries
import React from "react";
import { Navigate, Outlet, useLoaderData, useNavigate } from "react-router-dom";

// Custom Hooks
import { useAuth } from "../../hooks/useAuth";
import RoleSelectionPage from "./RoleSelectionPage";

/**
 * Auth Handler Component
 *
 * Purpose: Checks if the user has token or not. If the user has not token it will redirect back to login.
 */
const Auth = () => {
  // Fetch Data
  const userRoles = useLoaderData() || [];

  // Open useAuth
  const { user, token } = useAuth();

  // Check if and token exist
  // If loader couldn't fetch roles (e.g., unauthorized), send user back to login
  if (!Array.isArray(userRoles) || userRoles.length === 0) {
    return <Navigate to={"/login"} replace={true} />;
  }

  if (userRoles.length > 1) {
    return <RoleSelectionPage roles={userRoles} />; // Show selection for multiple roles
  }

  const role = userRoles[0]; // Single role

  // console.log(userRoles);

  // Check Roles
  switch (role) {
    case "admin":
      return <Navigate to={'/auth/admin'} replace />;
    case "student":
      return <Navigate to={'/auth/my'} replace />;
    case "dean":
      return <Navigate to={'/auth/dean'} replace />;
    case "chairperson":
      return <Navigate to={'/auth/chairperson'} replace />;
    case "company":
      return <Navigate to={'/auth/company'} replace />;
    case "coordinator":
      return <Navigate to={'/auth/coordinator'} replace />;
    case "osa":
      return <Navigate to={'/auth/osa'} replace />;
    case "supervisor":
      return <Navigate to={'/auth/supervisor'} replace />;
    default:
      return <Navigate to={"unauthorized"} />;
  }
};

export default Auth;
