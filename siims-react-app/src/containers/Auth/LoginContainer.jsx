import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import useFormData from "../../_global/hooks/useFormData";
import { useAuth } from "../../hooks/useAuth";
import LoginPresenter from "./LoginPresenter";

const LoginContainer = () => {
  /**
   *
   *
   *
   * LOADING STATE
   *
   *
   */
  const [loading, setLoading] = useState(false);

  /**
   *
   *
   * NAVIGATE
   *
   *
   */
  const navigate = useNavigate();

  /**
   *
   *
   * USE HOOK
   *
   *
   */
  const { formData, handleChange } = useFormData({
    id: "",
    password: "",
  });
  const { login } = useAuth();

  /**
   *
   *
   *
   * ERROR HANDLER
   *
   *
   *
   */
  const [errors, setErrors] = useState({});

  useEffect(() => {
    const storedError = localStorage.getItem("loginError");
    if (storedError) {
      setErrors({ _general: storedError });
      localStorage.removeItem("loginError");
    }
  }, []);

  /**
   *
   *
   *
   * FUNCTIONS
   *
   *
   *
   */
  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});

    // Prepare the payload with the login information from the form
    const payload = formData;
    console.log('submit', formData)

    // Attempt login and handle validation errors
    const validationErrors = await login(payload, setLoading, navigate);

    // console.log(validationErrors);
    if (validationErrors) {
      setErrors(validationErrors); // Set errors in state
    }
  };

  return (
    <>
      <LoginPresenter
        loading={loading}
        formData={formData}
        handleChange={handleChange}
        handleSubmit={handleSubmit}
        errors={errors}
      />
    </>
  );
};

export default LoginContainer;
