import { useState, useEffect } from "react";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { postRequest } from "../../api/apiHelpers";
import axiosClient from "../../api/axiosClient";
import { useLoaderData, useSearchParams } from "react-router-dom";
import Loader from "../../components/common/Loader";

const StudentWeeklyAccomplishmentPage = () => {
  // Fetch Params
  const { applicationId, initial_weekly_reports } = useLoaderData();

  console.log(initial_weekly_reports);
  // console.log(applicationId);
  // console.log(initial_weekly_reports);

  const [weeklyReports, setWeeklyReports] = useState(initial_weekly_reports);
  const [currentWeek, setCurrentWeek] = useState({
    week_number: "",
    start_date: "",
    end_date: "",
    hours: "",
    tasks: "",
    learnings: "",
  });
  const [lockedWeek, setLockedWeek] = useState(null);
  const [pendingRequests, setPendingRequests] = useState([]); // Track pending requests
  const [searchParams] = useSearchParams();
  const [no_of_hours, setNo_of_hours] = useState(0);
  const [selectedWeek, setSelectedWeek] = useState(""); // For selecting week to export
  const [filterWeek, setFilterWeek] = useState(""); // For filtering displayed reports
  const [editingReport, setEditingReport] = useState(null); // Track which report is being edited

  // Loading State
  const [loading, setLoading] = useState(false);

  // Group reports by week number
  const groupedReports = weeklyReports.reduce((acc, report) => {
    if (!acc[report.week_number]) {
      acc[report.week_number] = [];
    }
    acc[report.week_number].push(report);
    return acc;
  }, {});

  // Filter reports based on selected week
  const filteredReports = filterWeek 
    ? weeklyReports.filter(report => String(report.week_number) === String(filterWeek))
    : weeklyReports;

  // Generate available weeks (1-13 for internship, or based on existing reports)
  const existingWeeks = Object.keys(groupedReports).map(w => Number(w)).filter(w => !isNaN(w));
  const maxWeek = existingWeeks.length > 0 ? Math.max(...existingWeeks, 13) : 13;
  const availableWeeks = Array.from({ length: maxWeek }, (_, i) => i + 1);

  const handleChange = (e) => {
    console.log(e);

    const { name, value } = e.target;
    setCurrentWeek({ ...currentWeek, [name]: value });
    
    // Check if the week number has a pending request (not completed)
    if (name === 'week_number') {
      const weekNum = value ? Number(value) : null;
      if (weekNum && pendingRequests && pendingRequests.length > 0) {
        // Strict check: week_number must match exactly and request must not be completed
        const hasPendingRequest = pendingRequests.some(req => {
          if (!req || req.completed === true || req.completed === 1) {
            return false;
          }
          const reqWeekNum = Number(req.week_number);
          return !isNaN(reqWeekNum) && reqWeekNum === weekNum;
        });
        if (hasPendingRequest) {
          setLockedWeek(weekNum);
        } else {
          setLockedWeek(null);
        }
      } else {
        // No week number entered or no pending requests, clear locked week
        setLockedWeek(null);
      }
    }
  };

  const addReport = async () => {
    // console.log(currentWeek);
    setLoading(true);
    try {
      if (
        currentWeek.start_date &&
        currentWeek.end_date &&
        currentWeek.hours &&
        currentWeek.tasks &&
        currentWeek.learnings
      ) {
        const parsedHours = parseInt(currentWeek.hours, 10);
        if (parsedHours <= 0) {
          alert("Hours must be greater than 0.");
          setLoading(false);
          return;
        }

        const payload = {
          week_number: Number(currentWeek.week_number),
          start_date: currentWeek.start_date,
          end_date: currentWeek.end_date,
          tasks: currentWeek.tasks,
          learnings: currentWeek.learnings,
          no_of_hours: no_of_hours,
        };

        console.log(payload);

        const response = await postRequest({
          url: `/api/v1/weekly-accomplishment-reports/${applicationId}`,
          data: payload,
        });

        if (response) {
          // Store the locked week before clearing
          const completedWeek = lockedWeek;
          
          // Clear form completely after successful submission - do this FIRST
          setCurrentWeek({
            week_number: "",
            start_date: "",
            end_date: "",
            hours: "",
            tasks: "",
            learnings: "",
          });
          
          // Clear lockedWeek immediately after form clear
          setLockedWeek(null);
          
          // Reset editing state
          setEditingReport(null);
          
          // Refresh weekly reports from server to get latest data
          try {
            const reportsResponse = await axiosClient.get(`/api/v1/weekly-accomplishment-reports/${applicationId}`);
            if (reportsResponse?.data?.data) {
              const reports = Array.isArray(reportsResponse.data.data) ? reportsResponse.data.data : [];
              setWeeklyReports(reports);
              // Recalculate total hours
              const totalHours = reports.reduce((sum, report) => sum + parseInt(report.hours || 0, 10), 0);
              setNo_of_hours(totalHours);
            }
          } catch (err) {
            console.error('Error refreshing weekly reports:', err);
            // Fallback: add to local state if refresh fails
            setWeeklyReports([...weeklyReports, currentWeek]);
            setNo_of_hours((prevNoOfHours) => prevNoOfHours + parsedHours);
          }
          
          // If we came from a coordinator request, mark it completed
          if (completedWeek) {
            try {
              await axiosClient.get('/sanctum/csrf-cookie', { withCredentials: true });
              try {
                await axiosClient.put('/api/v1/student/weekly-entry-requests/complete', { week_number: Number(completedWeek) });
              } catch (err) {
                // Fallback to original path if alias not present
                await axiosClient.put('/api/v1/weekly-entry-requests/complete', { week_number: Number(completedWeek) });
              }
            } catch (err) {
              console.error('Error completing weekly entry request:', err);
            }
          }
          
          // Always refresh pending requests after adding a report to ensure accuracy
          await fetchPendingRequests();
        }
      } else {
        alert("Please complete all fields.");
      }
    } catch (error) {
      console.log(error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch pending requests on component mount
  const fetchPendingRequests = async () => {
    try {
      await axiosClient.get('/sanctum/csrf-cookie', { withCredentials: true });
      let resp;
      try {
        resp = await axiosClient.get('/api/v1/student/weekly-entry-requests');
      } catch (err) {
        // Fallback to original path if alias not present
        resp = await axiosClient.get('/api/v1/weekly-entry-requests/student');
      }
      if (resp?.data?.data) {
        const requests = Array.isArray(resp.data.data) ? resp.data.data : [];
        // Filter out completed requests - use very strict check
        const pendingOnly = requests.filter(req => {
          if (!req) return false;
          // Only include if completed is explicitly false, 0, or null/undefined
          // Exclude if completed is true or 1
          return req.completed === false || req.completed === 0 || req.completed === null || req.completed === undefined;
        });
        console.log('Pending requests after filter:', pendingOnly);
        setPendingRequests(pendingOnly);
        
        // Check if there's a request_week query param and if it's in pending requests
        const rq = searchParams.get('request_week');
        if (rq) {
          const weekNum = Number(rq);
          const hasPendingRequest = pendingOnly.some(req => {
            const reqWeekNum = Number(req.week_number);
            return reqWeekNum === weekNum && (req.completed === false || req.completed === 0 || req.completed === null);
          });
          if (hasPendingRequest) {
            setLockedWeek(weekNum);
            setCurrentWeek((prev) => ({ ...prev, week_number: String(rq) }));
          } else {
            // If query param exists but no pending request, clear locked week
            setLockedWeek(null);
          }
        } else {
          // No query param, clear locked week if it was set
          if (lockedWeek && !pendingOnly.some(req => Number(req.week_number) === lockedWeek)) {
            setLockedWeek(null);
          }
        }
      } else {
        // No data returned, clear pending requests and locked week
        setPendingRequests([]);
        setLockedWeek(null);
      }
    } catch (error) {
      console.error('Error fetching pending requests:', error);
      setPendingRequests([]);
      setLockedWeek(null);
    }
  };

  // Pre-fill requested week if provided in query param and it's actually pending
  useEffect(() => {
    fetchPendingRequests();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Refresh pending requests when form is cleared or when adding a new report
  useEffect(() => {
    // Refresh pending requests periodically or when needed
    const interval = setInterval(() => {
      fetchPendingRequests();
    }, 30000); // Refresh every 30 seconds

    return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const editReport = (index) => {
    const reportToEdit = weeklyReports[index];
    setEditingReport(index);
    // Clear lockedWeek when editing (editing doesn't count as a new request)
    setLockedWeek(null);
    setCurrentWeek(reportToEdit);
  };

  const saveEditedReport = async () => {
    setLoading(true);
    try {
      const reportToUpdate = weeklyReports[editingReport];
      if (!reportToUpdate?.id) {
        alert("Cannot update report: Missing report ID");
        setLoading(false);
        return;
      }

      const payload = {
        week_number: Number(currentWeek.week_number),
        start_date: currentWeek.start_date,
        end_date: currentWeek.end_date,
        tasks: currentWeek.tasks,
        learnings: currentWeek.learnings,
        no_of_hours: parseInt(currentWeek.hours, 10),
      };

      const response = await axiosClient.put(
        `/api/v1/weekly-accomplishment-reports/${reportToUpdate.id}`,
        payload
      );

      if (response?.data) {
        // Refresh weekly reports from server
        const reportsResponse = await axiosClient.get(`/api/v1/weekly-accomplishment-reports/${applicationId}`);
        if (reportsResponse?.data?.data) {
          const reports = Array.isArray(reportsResponse.data.data) ? reportsResponse.data.data : [];
          setWeeklyReports(reports);
          const totalHours = reports.reduce((sum, report) => sum + parseInt(report.hours || 0, 10), 0);
          setNo_of_hours(totalHours);
        }
      }

      setEditingReport(null);
      // Clear form completely after saving edit
      setCurrentWeek({
        week_number: "",
        start_date: "",
        end_date: "",
        hours: "",
        tasks: "",
        learnings: "",
      });
      // Clear lockedWeek after editing
      setLockedWeek(null);
    } catch (error) {
      console.error("Error updating report:", error);
      alert("Failed to update report. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const deleteReport = (index) => {
    const updatedReports = [...weeklyReports];
    const deletedReport = updatedReports.splice(index, 1); // Remove the report at the specified index
    setWeeklyReports(updatedReports);

    // Recalculate no_of_hours
    const newNoOfHours = updatedReports.reduce(
      (sum, report) => sum + parseInt(report.hours, 10),
      0
    );
    setNo_of_hours(newNoOfHours);

    alert(`Report for Week ${deletedReport[0].week_number} deleted`);
  };

  const handleWeekSelection = (e) => {
    const selectedWeek = e.target.value;
    setSelectedWeek(selectedWeek);

    // Fetch start and end dates for the selected week dynamically
    if (
      groupedReports[selectedWeek] &&
      groupedReports[selectedWeek].length > 0
    ) {
      const { start_date, end_date } = groupedReports[selectedWeek][0]; // Assume all reports in a week share the same start and end dates
      setCurrentWeek((prev) => ({ ...prev, start_date, end_date }));
    }
  };

  const handleFilterWeekChange = (e) => {
    const week = e.target.value;
    setFilterWeek(week);
  };

  const exportToPDF = () => {
    const doc = new jsPDF({ format: "a4" });

    const margin = 10;
    const pageWidth = doc.internal.pageSize.width;

    // Use selectedWeek for export, or filterWeek if selectedWeek is empty
    const weekToExport = selectedWeek || filterWeek;
    if (!weekToExport) {
      alert("Please select a week to export.");
      return;
    }

    const reportsForWeek = groupedReports[weekToExport];
    if (!reportsForWeek || reportsForWeek.length === 0) {
      alert("No reports available for the selected week.");
      return;
    }

    // Report Period Text
    const periodText = `For the Period: ${
      reportsForWeek[0]?.start_date || "N/A"
    } to ${reportsForWeek[reportsForWeek.length - 1]?.end_date || "N/A"}`;

    // Add logos and header
    const leftLogo = "/src/assets/images/logo/USTP-Logo-against-Light.png";
    const rightLogo = "/src/assets/images/logo/CITC_LOGO.png";
    doc.addImage(leftLogo, "JPEG", margin, margin, 25, 25);
    doc.addImage(rightLogo, "JPEG", pageWidth - margin - 35, margin, 35, 25);

    const centerText = `
      UNIVERSITY OF SCIENCE AND TECHNOLOGY
      OF SOUTHERN PHILIPPINES
      Alubijid | Cagayan de Oro | Claveria | Jasaan | Oroquieta | Panaon
    `;
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.text(centerText, pageWidth / 2, margin + 15, { align: "center" });

    // Fetch identity for header lines
    const studentName = (initial_weekly_reports?.student?.name) || (initial_weekly_reports?.student_name) || "Student";
    const companyName = (initial_weekly_reports?.company?.name) || (initial_weekly_reports?.company_name) || "Company";
    const coordinatorName = (initial_weekly_reports?.coordinator?.name) || (initial_weekly_reports?.coordinator_name) || "Coordinator";
    const chairName = (initial_weekly_reports?.chairperson?.name) || (initial_weekly_reports?.chairperson_name) || "Chairperson";

    // Metadata and Title
    doc.setFont("helvetica", "normal");
    doc.setFontSize(12);
    doc.text("Weekly Accomplishment Report", margin, margin + 40);
    doc.setFontSize(10);
    doc.text(periodText, margin, margin + 45);
    doc.text(`Student: ${studentName}`, margin, margin + 50);
    doc.text(`Company: ${companyName}`, margin, margin + 55);
    doc.text(`Coordinator: ${coordinatorName}`, margin, margin + 60);
    doc.text(`Chairperson: ${chairName}`, margin, margin + 65);

    // Define header and table data
    const header = [
      "Week",
      "Start Date",
      "End Date",
      "Tasks",
      "Learnings",
      "Hours",
    ];
    const tableData = reportsForWeek.map((report) => [
      report.week_number,
      report.stat_date,
      report.end_date,
      report.tasks,
      report.learnings,
      report.hours,
      report.hours,
    ]);

    // Generate table
    autoTable(doc, {
      head: [header],
      body: tableData,
      startY: margin + 75,
      margin: { top: margin },
      styles: {
        fontSize: 8,
        valign: "middle",
        halign: "center",
        overflow: "linebreak",
      },
      columnStyles: {
        0: { cellWidth: 15 },
        1: { cellWidth: 30 },
        2: { cellWidth: 30 },
        3: { cellWidth: 45 },
        4: { cellWidth: 45 },
        5: { cellWidth: 15 },
      },
    });

    // Save PDF
    doc.save("weekly_accomplishment_report.pdf");
  };

  return (
    <>
      <div>
        <Loader loading={loading} />
        <div className="p-6 bg-gray-100 min-h-screen">
          {/* Header */}
          <div className="bg-white shadow-md rounded-lg p-6 mb-4">
            <h1 className="text-2xl font-bold text-gray-800 mb-2">
              Weekly Accomplishment Report
            </h1>
            <div className="flex justify-between mt-4">
              <div>
                <p className="text-lg font-semibold text-gray-700">
                  Name: Jane Smith
                </p>
                <p className="text-lg font-semibold text-gray-700">
                  Company: Mindanao Tech Solutions
                </p>
                <p className="text-lg font-semibold text-gray-700">
                  Unit/Office/Department: IT Department
                </p>
              </div>
              <div className="flex gap-4 items-center">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Filter by Week
                  </label>
                  <select
                    value={filterWeek}
                    onChange={handleFilterWeekChange}
                    className="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 px-4 py-2"
                  >
                    <option value="">All Weeks</option>
                    {Object.keys(groupedReports).sort((a, b) => Number(a) - Number(b)).map((week) => (
                      <option key={week} value={week}>
                        Week {week}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Export Week
                  </label>
                  <select
                    value={selectedWeek}
                    onChange={handleWeekSelection}
                    className="border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 px-4 py-2"
                  >
                    <option value="">Select Week</option>
                    {Object.keys(groupedReports).sort((a, b) => Number(a) - Number(b)).map((week) => (
                      <option key={week} value={week}>
                        Week {week}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="self-end">
                  <button
                    onClick={exportToPDF}
                    className="bg-blue-500 text-white px-4 py-2 rounded shadow hover:bg-blue-600"
                  >
                    Export to PDF
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Form to Add/Edit Report */}
          <div className="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 className="text-xl font-semibold text-gray-800 mb-4">
              {editingReport !== null
                ? "Edit Weekly Report"
                : "Add Weekly Report"}
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Week Number {
                    (() => {
                      if (!currentWeek.week_number || !pendingRequests || pendingRequests.length === 0) {
                        return null;
                      }
                      const weekNum = Number(currentWeek.week_number);
                      if (isNaN(weekNum)) {
                        return null;
                      }
                      // Very strict check: must have exact match and not completed
                      const hasPendingRequest = pendingRequests.some(req => {
                        if (!req) return false;
                        // Skip if completed is explicitly true or 1
                        if (req.completed === true || req.completed === 1) {
                          return false;
                        }
                        const reqWeekNum = Number(req.week_number);
                        return !isNaN(reqWeekNum) && reqWeekNum === weekNum;
                      });
                      return hasPendingRequest ? (
                        <span className="ml-2 px-2 py-0.5 text-xs rounded bg-amber-200 text-amber-900">Requested</span>
                      ) : null;
                    })()
                  }
                </label>
                <select
                  name="week_number"
                  value={currentWeek.week_number}
                  onChange={handleChange}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                  disabled={!!lockedWeek && Number(currentWeek.week_number) === lockedWeek}
                >
                  <option value="">Select Week</option>
                  {availableWeeks.map((week) => (
                    <option key={week} value={week}>
                      Week {week}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Start Date
                </label>
                <input
                  type="date"
                  name="start_date"
                  value={currentWeek.start_date}
                  onChange={handleChange}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  End Date
                </label>
                <input
                  type="date"
                  name="end_date"
                  value={currentWeek.end_date}
                  onChange={handleChange}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Hours
                </label>
                <input
                  type="number"
                  name="hours"
                  value={currentWeek.hours}
                  onChange={handleChange}
                  placeholder="e.g., 40"
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Tasks
                </label>
                <textarea
                  name="tasks"
                  value={currentWeek.tasks}
                  onChange={handleChange}
                  placeholder="Tasks completed during the week"
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Learnings
                </label>
                <textarea
                  name="learnings"
                  value={currentWeek.learnings}
                  onChange={handleChange}
                  placeholder="What you learned this week"
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                />
              </div>
            </div>
            <div className="mt-4">
              <button
                onClick={editingReport !== null ? saveEditedReport : addReport}
                className="bg-blue-500 text-white px-4 py-2 rounded shadow hover:bg-blue-600"
              >
                {editingReport !== null ? "Save Changes" : "Add Report"}
              </button>
            </div>
          </div>

          {/* Weekly Reports Table */}
          <div className="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 className="text-xl font-semibold text-gray-800 mb-4">
              {filterWeek ? `Week ${filterWeek} Reports` : "All Reports"}
            </h2>
            <div className="overflow-x-auto">
              <table className="min-w-full bg-white border border-gray-300">
                <thead>
                  <tr>
                    <th className="px-4 py-2 text-left border-b">Week</th>
                    <th className="px-4 py-2 text-left border-b">Start Date</th>
                    <th className="px-4 py-2 text-left border-b">End Date</th>
                    <th className="px-4 py-2 text-left border-b">Tasks</th>
                    <th className="px-4 py-2 text-left border-b">Learnings</th>
                    <th className="px-4 py-2 text-left border-b">Hours</th>
                    <th className="px-4 py-2 text-left border-b">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredReports.length === 0 ? (
                    <tr>
                      <td colSpan="7" className="px-4 py-4 text-center text-gray-500">
                        {filterWeek ? `No reports found for Week ${filterWeek}` : "No reports available"}
                      </td>
                    </tr>
                  ) : (
                    filteredReports.map((report, index) => {
                      // Find the original index in weeklyReports for edit/delete operations
                      const originalIndex = weeklyReports.findIndex(r => 
                        r.week_number === report.week_number && 
                        r.start_date === report.start_date &&
                        r.end_date === report.end_date
                      );
                      return (
                        <tr key={index}>
                          <td className="px-4 py-2">{report.week_number}</td>
                          <td className="px-4 py-2">{report.start_date}</td>
                          <td className="px-4 py-2">{report.end_date}</td>
                          <td className="px-4 py-2">{report.tasks}</td>
                          <td className="px-4 py-2">{report.learnings}</td>
                          <td className="px-4 py-2">{report.hours}</td>
                          <td className="px-4 py-2">
                            <button
                              onClick={() => editReport(originalIndex >= 0 ? originalIndex : index)}
                              className="bg-blue-500 text-white px-3 py-1 rounded shadow hover:bg-blue-600"
                            >
                              Edit
                            </button>
                            <button
                              onClick={() => deleteReport(originalIndex >= 0 ? originalIndex : index)}
                              className="bg-red-500 text-white px-3 py-1 rounded shadow hover:bg-red-600 ml-2"
                            >
                              Delete
                            </button>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
            {/*             <div className="mt-4">
              <p>Total Hours: {total_hours}</p>
            </div> */}
          </div>
        </div>
      </div>
    </>
  );
};

export default StudentWeeklyAccomplishmentPage;
