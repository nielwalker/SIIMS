import React, { useEffect, useState } from "react";
import CompanyHomePageTesting from "../company/CompanyHomePageTesting";
import Loader from "../../components/common/Loader";
import { getRequest } from "../../api/apiHelpers";
import Page from "../../components/common/Page";
import Section from "../../components/common/Section";
import Heading from "../../components/common/Heading";
import Text from "../../components/common/Text";
import Calendar from "react-calendar/dist/cjs/Calendar.js";
import Container from "../../containers/Dashboards/components/Container";
import {
  Building2,
  BuildingIcon,
  Layers,
  Notebook,
  SquareUserRound,
  UserPen,
  UserRoundCheck,
  Users,
} from "lucide-react";
// Recharts

import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts";
import { capitalizeFirstLetter } from "../../utils/capitalizeFirstLetter";
import axiosClient from "../../api/axiosClient";

const HomeRemotePage = ({ authorizeRole }) => {
  // Loading State
  const [loading, setLoading] = useState(false);

  // Container State
  const [details, setDetails] = useState({});
  const [barChartData, setBarChartData] = useState([
    {
      name: "",
      value: 0,
    },
  ]);

  // Fetch Request
  const fetchDetails = async () => {
    // Set Loading State
    setLoading(true);

    try {
      const response = await getRequest({
        url: "/api/v1/dashboards",
        params: {
          requestedBy: authorizeRole,
        },
      });

      if (response) {
        // console.log(response);
        setBarChartData(response.bar_chart);
        setDetails(response);
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Fetch Details
    fetchDetails();
  }, []);

  /** Coordinator summary by week - removed from dashboard per request */
  const [coordStudents, setCoordStudents] = useState([]);
  const [selectedStudentId, setSelectedStudentId] = useState("");
  const [selectedWeek, setSelectedWeek] = useState(1);
  const [summaryLoading, setSummaryLoading] = useState(false);
  const [studentSummary, setStudentSummary] = useState("");
  const [totalHours, setTotalHours] = useState(0);
  const ALL_WEEKS = Array.from({ length: 13 }, (_, i) => i + 1);

  const fetchCoordinatorStudents = async () => {
    try {
      const resp = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/v1/coordinator/students`,
        {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
          },
          credentials: "include",
        }
      );
      const data = await resp.json().catch(() => []);
      const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
      const opts = list.map((s) => {
        const id = String(s.id ?? s.student_id ?? s.user_id ?? "");
        const first = s.first_name || s.firstName || "";
        const last = s.last_name || s.lastName || "";
        const name = [first, last].filter(Boolean).join(" ") || s.name || s.fullName || id;
        return { id, name };
      });
      setCoordStudents(opts);
      if (opts.length && !selectedStudentId) setSelectedStudentId(opts[0].id);
    } catch (e) {
      setCoordStudents([]);
    }
  };

  const fetchSummary = async () => {
    if (!selectedStudentId) return;
    try {
      setSummaryLoading(true);
      const resp = await axiosClient.post("/api/v1/summary", {
        studentId: selectedStudentId,
        week: selectedWeek,
        useGPT: true,
        analysisType: "coordinator",
        isOverall: false,
      });
      const data = resp?.data || {};
      setStudentSummary(data?.summary || "No data available.");
    } catch (e) {
      setStudentSummary("No data available.");
    } finally {
      setSummaryLoading(false);
    }
  };

  const fetchTotalHours = async () => {
    if (!selectedStudentId) { setTotalHours(0); return; }
    try {
      const resp = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/v1/weekly-entries/student/${selectedStudentId}`,
        {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
          },
          credentials: "include",
        }
      );
      const payload = await resp.json().catch(() => []);
      const list = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : [];
      const sum = list.reduce((acc, e) => acc + Number(e.no_of_hours ?? e.hours ?? 0), 0);
      setTotalHours(sum);
    } catch (e) {
      setTotalHours(0);
    }
  };

  useEffect(() => {
    // keep list available for reuse if needed elsewhere, but not shown here
    if (authorizeRole === "coordinator") fetchCoordinatorStudents();
  }, [authorizeRole]);

  // Set Loading
  if (loading) {
    return <Loader loading={loading} />;
  }

  /**
   * Remote for User Dashboards/Homepage
   */
  if (authorizeRole === "company") {
    return <CompanyHomePageTesting details={details} />;
  }

  return (
    <Page>
      {/* Dashboard Heading Section */}
      <Section className="mb-8 mt-5">
        <Heading level={3} text="Dashboard" className="text-blue-800" />
        <Text className="text-sm text-gray-500">
          Overview of the system data. Stay on top of everything!
        </Text>
        <hr className="my-4 border-gray-300" />
      </Section>

      {/* Welcome Section */}
      <Section className="mb-12">
        <div className="flex flex-1 justify-between items-center bg-white shadow-xl rounded-md p-8">
          <div>
            <Heading
              level={2}
              text={`Welcome back, ${capitalizeFirstLetter(authorizeRole)}!`}
              className="text-gray-900 font-bold text-2xl"
            />
            <Text className="text-gray-600 mt-1">
              Take a look at the updated SIIMS overview and statistics.
            </Text>
          </div>
        </div>
      </Section>

      {/* Statistics Section */}
      <Section>
        {/* Main Dashboard Grid Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Panel: Key Statistics */}
          <div className="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Total Students (for logged-in coordinator) */}
            <Container
              borderTopColor="border-t-indigo-600"
              title="Total Students"
              totalData={details.total_students}
            >
              <Users size={32} className="text-indigo-600" />
            </Container>

            {/* Total Companies */}
            <Container
              borderTopColor="border-t-red-600"
              title="Total Companies"
              totalData={details.total_companies}
            >
              <Building2 size={32} className="text-red-600" />
            </Container>

            {/* Total Jobs */}
            <Container
              borderTopColor="border-t-yellow-600"
              title="Total Jobs"
              totalData={details.total_work_posts}
            >
              <Notebook size={32} className="text-yellow-600" />
            </Container>

            {/* Total Programs */}
            <Container
              borderTopColor="border-t-green-600"
              title="Total Programs"
              totalData={details.total_programs}
            >
              <BuildingIcon size={32} className="text-green-600" />
            </Container>
          </div>

          {/* Right Panel: Calendar */}
          <div className="bg-white p-6 rounded-lg shadow-lg">
            <Heading
              level={4}
              text="Calendar"
              className="font-semibold text-gray-800 mb-4"
            />
            <div className="flex justify-center">
              <Calendar
                locale="en-US" // Sets the week to start on Sunday
                className="shadow-lg rounded-lg border"
              />
            </div>
          </div>
        </div>
      </Section>

      {/* Coordinator Student Summary moved to My Student's Reports page */}

      {/* System Overview (Bar Chart) - removed for coordinator dashboard */}
    </Page>
  );
};

export default HomeRemotePage;
