import React, { useEffect, useState } from "react";
import Section from "../../components/common/Section";
import {
  Link,
  useLoaderData,
  useLocation,
  useNavigate,
} from "react-router-dom";
import Page from "../../components/common/Page";
import { ChevronLeft } from "lucide-react";
import Heading from "../../components/common/Heading";
import Text from "../../components/common/Text";
import WorkPostForm from "../../components/forms/WorkPostForm";
import { stripLocation } from "../../utils/strip";
import ContentLoader from "../../components/atoms/ContentLoader";
import { getRequest, postRequest } from "../../api/apiHelpers";
import useForm from "../../hooks/useForm";
import useHandleSubmit from "../../hooks/useHandleSubmit";

const SupervisorAddJobPage = () => {
  // Open Location
  const location = useLocation();
  const strippedPath = stripLocation(location.pathname, "/add");
  const navigate = useNavigate();

  // Retrieve the programs data from the loader
  const workTypes = useLoaderData();

  // Input State
  const [workTypeId, setWorkTypeId] = useState(null);
  const [title, setTitle] = useState("");
  const [responsibilities, setResponsibilities] = useState("");
  const [qualifications, setQualifications] = useState("");
  const [startDate, setStartDate] = useState("");
  const [maxApplicants, setMaxApplicants] = useState(1);
  const [endDate, setEndDate] = useState("");
  const [workDuration, setWorkDuration] = useState("");
  const [errors, setErrors] = useState({});

  // Add work post
  const addWorkPost = async (e) => {
    e.preventDefault(); // Prevent page refresh

    try {
      const payload = {
        work_type_id: workTypeId,
        title: title,
        responsibilities: responsibilities,
        qualifications: qualifications,
        start_date: startDate,
        end_date: endDate,
        work_duration: workDuration,
        max_applicants: maxApplicants,
      };

      // console.log(payload);

      // Make POST Request
      const response = await postRequest({
        url: "/api/v1/supervisor/work-posts",
        data: payload,
      });

      // Redirect after success
      if (response) {
        navigate(strippedPath); // Redirect to strippedPath
      }
    } catch (error) {
      // Handle and set errors
      if (error.response && error.response.data && error.response.data.errors) {
        console.log(error.response.data.errors);
        setErrors(error.response.data.errors); // Assuming validation errors are in `errors`
      } else {
        console.error("An unexpected error occurred:", error);
        setErrors({
          general: "An unexpected error occurred. Please try again.",
        });
      }
    }
  };

  return (
    <>
      <Page>
        <Section>
          <Link
            to={strippedPath}
            className="flex items-center text-sm font-bold text-blue-500 hover:underline"
          >
            <ChevronLeft size={20} />
            Go Back
          </Link>
        </Section>

        <Section>
          <Heading level={3} text={"Add Job"} />
          <Text className="text-sm text-blue-950">
            This is where you add a job opportunity.
          </Text>
          <hr className="my-3" />
        </Section>

        <Section>
          <WorkPostForm
            workTypeId={workTypeId}
            title={title}
            responsibilities={responsibilities}
            qualifications={qualifications}
            startDate={startDate}
            endDate={endDate}
            maxApplicants={maxApplicants}
            workDuration={workDuration}
            setWorkTypeId={setWorkTypeId}
            setTitle={setTitle}
            setResponsibilities={setResponsibilities}
            setQualifications={setQualifications}
            setStartDate={setStartDate}
            setEndDate={setEndDate}
            setMaxApplicants={setMaxApplicants}
            setWorkDuration={setWorkDuration}
            isFormModal={false}
            workTypes={workTypes}
            handleSubmit={addWorkPost}
          />
        </Section>
      </Page>
    </>
  );
};

export default SupervisorAddJobPage;
