import React, { useEffect, useState } from "react";
import { getRequest } from "../../../api/apiHelpers";
import StudentSideProfileInfo from "../../../components/profiles/StudentSideProfileInfo";
import Loader from "../../../components/common/Loader";
import ProfileSidebar from "../../../components/dashboards/ProfileSidebar";
import JobListsSection from "../../../components/workPosts/JobListsSection";
import TestJobListsSection from "../../../components/workPosts/TestJobListsSection";
import CurrentApplication from "../../../components/applications/CurrentApplication";
import EmptyState from "../../../components/common/EmptyState";
import { useLocation, useNavigate } from "react-router-dom";
import ReportsSection from "../../../components/workPosts/ReportsSection";
import axiosClient from "../../../api/axiosClient";

const TestingHomePage = () => {
  const location = useLocation();
  const navigate = useNavigate();
  // Loading State
  const [loading, setLoading] = useState(false);

  // Container State
  const [profile, setProfile] = useState({});

  // State
  const [studentStatusID, setStudentStatusID] = useState(0);
  const [latestApplication, setLatestApplication] = useState({});
  const [requests, setRequests] = useState([]);

  // Fetch State
  useEffect(() => {
    // Fetch Profile
    const fetchStudentProfile = async () => {
      // Set Loading State
      setLoading(true);

      try {
        const response = await getRequest({
          url: "/api/v1/homes/student",
        });

        if (response) {
          // console.log(response);
          setProfile(response);
          setStudentStatusID(response.student_status_id);
          setLatestApplication(response.latest_application);
        }
      } catch (error) {
        console.error(error);
      } finally {
        setLoading(false);
      }
    };

    // Fetch Job Posts

    // * Call method
    fetchStudentProfile();
  }, []);

  // Fetch pending weekly entry requests
  useEffect(() => {
    const fetchRequests = async () => {
      try {
        const resp = await axiosClient.get('/api/v1/weekly-entry-requests/student');
        const list = Array.isArray(resp?.data?.data) ? resp.data.data : [];
        setRequests(list);
      } catch (e) {
        setRequests([]);
      }
    };
    fetchRequests();
  }, []);

  /**
   * Fetch Job Posts
   *
   * Displays only if the student status ID is 1 (Not Yet Applied)
   */
  const displayJobs = ({ studentStatusID, coordinatorID }) => {
    return studentStatusID && studentStatusID === 1 && coordinatorID ? (
      <TestJobListsSection />
    ) : null;
  };

  // Navigate to Daily Time Record
  const navigateToDtr = () => {
    const to = `${location.pathname}/${latestApplication.id}/daily-time-records`;

    navigate(to);
  };

  // Navigate to Weekly Accomplishment Reports
  const navigateToWeekly = () => {
    const to = `${location.pathname}/${latestApplication.id}/weekly-accomplishment-reports`;
    navigate(to);
  };

  const openRequestWeek = (week) => {
    const to = `${location.pathname}/${latestApplication.id}/weekly-accomplishment-reports?request_week=${week}`;
    navigate(to);
  };

  // Navigate to Personal Insights
  const navigateToInsights = () => {
    const to = `${location.pathname}/${latestApplication.id}/personal-insight`;

    navigate(to);
  };

  // Loading Condition
  if (loading) {
    return <Loader loading={loading} />;
  }

  return (
    <div>
      <div className="min-h-screen overflow-y-auto">
        <div className="flex flex-col lg:flex-row space-y-10 lg:space-y-0 lg:space-x-10">
          {/* Profile Sidebar */}
          <ProfileSidebar profile={profile} />

          {/* If the student has no coordinator yet. Display Empty State */}
          {!profile.coordinator_id && (
            <EmptyState
              title="No jobs available at the moment"
              message="Once you have a coordinator, jobs will appear here."
            />
          )}

          {/* If student is not yet applied . Display Job Posts */}
          {/* Job Listings and Filters (Scrollable Middle) */}
          {displayJobs({
            studentStatusID: studentStatusID,
            coordinatorID: profile.coordinator_id,
          })}

          {studentStatusID &&
            (studentStatusID === 2 ||
              studentStatusID === 3 ||
              studentStatusID === 4) && (
              <CurrentApplication studentStatusID={studentStatusID} />
            )}

          {/* Reports Section */}
          {latestApplication &&
            (studentStatusID === 5 || studentStatusID === 6) && (
              <>
                {requests.length > 0 && (
                  <div className="bg-amber-50 border border-amber-200 rounded p-4 mb-4">
                    <h4 className="text-md font-semibold text-amber-900 mb-2">Requests from your coordinator</h4>
                    <ul className="list-disc list-inside text-amber-900">
                      {requests.map((r) => (
                        <li key={r.id} className="mb-1">
                          Please submit <span className="font-semibold">Week {r.week_number}</span>
                          <button onClick={() => openRequestWeek(r.week_number)} className="ml-3 px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Open</button>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                <ReportsSection
                  navigateToDtr={navigateToDtr}
                  navigateToWeekly={navigateToWeekly}
                  navigateToInsights={navigateToInsights}
                />
              </>
            )}
        </div>
      </div>
    </div>
  );
};

export default TestingHomePage;
