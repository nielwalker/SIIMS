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
        let resp = await axiosClient.get('/api/v1/student/weekly-entry-requests');
        if (!resp || !Array.isArray(resp?.data?.data)) {
          // fallback to original path if alias not registered
          resp = await axiosClient.get('/api/v1/weekly-entry-requests/student');
        }
        const list = Array.isArray(resp?.data?.data) ? resp.data.data : [];
        setRequests(list);
      } catch (e) {
        try {
          const resp2 = await axiosClient.get('/api/v1/weekly-entry-requests/student');
          const list2 = Array.isArray(resp2?.data?.data) ? resp2.data.data : [];
          setRequests(list2);
        } catch {
          setRequests([]);
        }
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
    const appId = latestApplication?.id || profile?.latest_application?.id;
    if (!appId) { alert('No active application found.'); return; }
    const to = `${location.pathname}/${appId}/daily-time-records`;
    navigate(to);
  };

  // Navigate to Weekly Accomplishment Reports
  const navigateToWeekly = () => {
    const appId = latestApplication?.id || profile?.latest_application?.id;
    if (!appId) { alert('No active application found.'); return; }
    const to = `${location.pathname}/${appId}/weekly-accomplishment-reports`;
    navigate(to);
  };

  const openRequestWeek = async (week) => {
    // Navigate to the student weekly-accomplishments page which does not require application id
    const to = `/auth/my/weekly-accomplishments?request_week=${week}`;
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

          {/* Right column content stacked vertically */}
          <div className="flex-1 flex flex-col gap-4">
            {/* All Jobs / Job feed first */}
            {displayJobs({
              studentStatusID: studentStatusID,
              coordinatorID: profile.coordinator_id,
            })}

            {/* Requests from coordinator below jobs */}
            {requests.length > 0 && (
              <div className="bg-white border rounded px-4 py-3 shadow-sm">
                <h4 className="text-md font-semibold text-amber-900 mb-2">Requests from your coordinator</h4>
                <ul className="list-disc list-inside text-amber-900">
                  {requests.map((r) => (
                    <li key={r.id} className="mb-1">
                      Please submit <span className="font-semibold">Week {r.week_number}</span>
                      <button
                        onClick={() => openRequestWeek(r.week_number)}
                        className="ml-3 px-2 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700"
                      >
                        Open
                      </button>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {/* If the student has no coordinator yet. Display Empty State */}
            {!profile.coordinator_id && (
              <EmptyState
                title="No jobs available at the moment"
                message="Once you have a coordinator, jobs will appear here."
              />
            )}

            {studentStatusID &&
              (studentStatusID === 2 ||
                studentStatusID === 3 ||
                studentStatusID === 4) && (
                <CurrentApplication studentStatusID={studentStatusID} />
              )}

            {/* Reports Section */}
            {latestApplication && (studentStatusID === 5 || studentStatusID === 6) && (
              <ReportsSection
                navigateToDtr={navigateToDtr}
                navigateToWeekly={navigateToWeekly}
                navigateToInsights={navigateToInsights}
              />
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TestingHomePage;
