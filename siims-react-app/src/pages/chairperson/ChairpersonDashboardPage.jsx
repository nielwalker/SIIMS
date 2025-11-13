import React, { useEffect, useMemo, useState, useRef } from "react";
import { useLoaderData } from "react-router-dom"; // Hook to load route data
import axiosClient from "../../api/axiosClient";
import { FaUserGraduate, FaBuilding, FaUsers, FaBook } from "react-icons/fa"; // Import icons

// Import Components
import Heading from "../../components/common/Heading";
import Page from "../../components/common/Page";
import ChairpersonSummary from "../../components/chairperson/ChairpersonSummary";

export default function ChairpersonDashboardPage() {
  // Fetch Data (from loader as fallback)
  const loaderData = useLoaderData() || {}; // { userRoles, dashboard }

  // Local state so the page can fetch totals directly (avoids loader issues)
  const [dashboard, setDashboard] = useState(loaderData.dashboard || null);
  const [coordinators, setCoordinators] = useState([]);
  const [selectedCoordinatorId, setSelectedCoordinatorId] = useState("");
  const [coordinatorSections, setCoordinatorSections] = useState([]);
  const [selectedSectionId, setSelectedSectionId] = useState("");
  const [selectedWeek, setSelectedWeek] = useState(1);
  const [availableWeeks, setAvailableWeeks] = useState([]);
  const [refreshTrigger, setRefreshTrigger] = useState(0);
  const [exportSnapshot, setExportSnapshot] = useState(null);
  const summaryRef = useRef(null);


  useEffect(() => {
    let didCancel = false;
    (async () => {
      try {
        // Use native fetch to avoid global axios toasts when backend throws unrelated errors
        const resp = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/dashboards?requestedBy=chairperson`, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
          },
          credentials: "include",
        });
        if (!resp.ok) throw new Error("dashboards error");
        const data = await resp.json();
        if (!didCancel) setDashboard(data);
      } catch (e) {
        if (!didCancel) setDashboard(null); // UI will show zeros
      }
    })();
    return () => {
      didCancel = true;
    };
  }, []);

  // Load coordinators for dropdown
  useEffect(() => {
    let didCancel = false;
    (async () => {
      try {
        const normalize = (p) => {
          if (!p) return [];
          if (Array.isArray(p?.data?.data)) return p.data.data;
          if (Array.isArray(p?.data)) return p.data;
          if (Array.isArray(p)) return p;
          return [];
        };

        // Try multiple known endpoints until one returns data
        const attempts = [
          () => axiosClient.get("/api/v1/users/v2/coordinators", { params: { requestedBy: "chairperson", page: 1, perPage: 500 } }),
          () => axiosClient.get("/api/v1/users/coordinators"),
          () => axiosClient.get("/api/v1/users/coordinators/all"),
          () => axiosClient.get("/api/v1/coordinators"),
        ];

        let rows = [];
        for (const attempt of attempts) {
          try {
            const resp = await attempt();
            rows = normalize(resp);
            if (rows.length) break;
          } catch {
            // try next
          }
        }

        // Normalize/dedupe and build labels
        const normalized = [];
        const seen = new Set();
        for (const c of rows) {
          const rawId = c?.id ?? c?.user_id ?? c?.coordinator_id;
          const id = rawId != null ? String(rawId) : undefined;
          if (!id || seen.has(id)) continue;
          seen.add(id);
          const first = c.first_name || c.firstName || c.user_first_name || '';
          const last = c.last_name || c.lastName || c.user_last_name || '';
          const fallback = c.user_name || c.userName || c.name || c.fullName || 'Coordinator';
          const label = `${id} - ${[first, last].filter(Boolean).join(' ') || fallback}`;
          normalized.push({ id, label });
        }

          if (!didCancel) {
          setCoordinators(normalized);
          // Do not auto-select; require explicit choice
          setSelectedCoordinatorId("");
        }
      } catch (err) {
        if (!didCancel) {
          setCoordinators([]);
          setSelectedCoordinatorId("");
        }
      }
    })();
    return () => {
      didCancel = true;
    };
  }, []);

  // Dynamically compute weeks that have data for the selected coordinator (OPTIMIZED)
  useEffect(() => {
    let cancelled = false;
    async function loadWeeks() {
      try {
        if (!selectedCoordinatorId) {
          if (!cancelled) {
            setAvailableWeeks([]);
            setSelectedWeek("");
            setCoordinatorSections([]);
            setSelectedSectionId("");
          }
          return;
        }

        const apiBase = import.meta.env.VITE_API_BASE_URL;
        const headers = {
          Accept: "application/json",
          Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
        };

        // OPTIMIZED: Use single endpoint to get weeks and sections in one call
        try {
          const qp = new URLSearchParams({ coordinatorId: selectedCoordinatorId });
          if (selectedSectionId) {
            qp.set('sectionId', selectedSectionId);
          }
          const resp = await fetch(`${apiBase}/api/v1/chairperson/available-weeks?${qp.toString()}`, {
            headers,
            credentials: "include",
          });
          
          if (resp.ok) {
            const data = await resp.json();
            const weeks = Array.isArray(data?.weeks) ? data.weeks : [];
            const sections = Array.isArray(data?.sections) ? data.sections : [];
            
            // Remove coordinator name from section names (e.g., "4R11-Ethan" -> "4R11")
            const cleanedSections = sections.map((s) => {
              if (!s.name) return s;
              const parts = s.name.split('-');
              // If there's more than one part, remove the last part (coordinator name)
              // Otherwise, keep the original name
              const cleanedName = parts.length > 1 ? parts.slice(0, -1).join('-').trim() : s.name;
              return { ...s, name: cleanedName || s.name };
            });
            
            if (!cancelled) {
              setAvailableWeeks(weeks);
              setCoordinatorSections(cleanedSections);
              if (weeks.length > 0 && !weeks.includes(Number(selectedWeek))) {
                setSelectedWeek(weeks[0]);
              }
              // Auto-select section if only one, or auto-select first section if multiple
              if (sections.length === 1 && !selectedSectionId) {
                setSelectedSectionId(sections[0].id);
              } else if (sections.length > 1 && !selectedSectionId) {
                setSelectedSectionId(sections[0].id);
              }
            }
            return; // Success, exit early
          }
        } catch (err) {
          console.warn('Optimized weeks endpoint failed, falling back to legacy method', err);
        }

        // FALLBACK: Legacy method (slower, but works if new endpoint fails)
        // Load sections for selected coordinator
        try {
          const rs = await fetch(`${apiBase}/api/v1/sections?requestedBy=chairperson`, { headers, credentials: 'include' });
          const ps = await rs.json().catch(() => []);
          const allSecs = Array.isArray(ps?.data) ? ps.data : (Array.isArray(ps) ? ps : []);
          const secs = allSecs.filter((s) => String(s.coordinator_id ?? s.coordinatorId) === String(selectedCoordinatorId));
          if (!cancelled) {
            // Remove coordinator name from section names (e.g., "4R11-Ethan" -> "4R11")
            const sectionsList = secs.map((s) => {
              if (!s.name) return { id: s.id, name: s.name || '' };
              const parts = s.name.split('-');
              // If there's more than one part, remove the last part (coordinator name)
              // Otherwise, keep the original name
              const cleanedName = parts.length > 1 ? parts.slice(0, -1).join('-').trim() : s.name;
              return { id: s.id, name: cleanedName || s.name };
            });
            setCoordinatorSections(sectionsList);
            // Auto-select first section if available
            if (sectionsList.length > 0 && !selectedSectionId) {
              setSelectedSectionId(sectionsList[0].id);
            } else if (sectionsList.length === 0) {
              setSelectedSectionId("");
            }
          }
        } catch (_) {
          if (!cancelled) { setCoordinatorSections([]); setSelectedSectionId(""); }
        }

        // Use existing coordinator endpoint (single query instead of N+1)
        try {
          const coordResp = await fetch(`${apiBase}/api/v1/weekly-entries/coordinator/${selectedCoordinatorId}`, {
            headers,
            credentials: "include",
          });
          
          if (coordResp.ok) {
            const coordData = await coordResp.json();
            const entries = Array.isArray(coordData?.data) ? coordData.data : (Array.isArray(coordData) ? coordData : []);
            const weekNums = new Set();
            for (const row of entries) {
              const wn = Number(row?.week_number ?? row?.weekNumber ?? row?.week);
              if (!Number.isNaN(wn) && wn > 0) weekNums.add(wn);
            }
            const sorted = Array.from(weekNums).sort((a, b) => a - b);
            if (!cancelled) {
              setAvailableWeeks(sorted);
              if (!sorted.includes(Number(selectedWeek))) {
                setSelectedWeek(sorted.length ? sorted[0] : "");
              }
            }
            return; // Success with coordinator endpoint
          }
        } catch (_) {
          // Continue to final fallback
        }

        // FINAL FALLBACK: Original method (slowest, but most compatible)
        const r = await fetch(`${apiBase}/api/v1/chairperson/students`, { headers, credentials: "include" });
        const payload = await r.json().catch(() => ([]));
        const students = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
        const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"];
        let filtered = students.filter((s) => {
          for (const key of coordinatorKeyNames) {
            if (s && Object.prototype.hasOwnProperty.call(s, key)) {
              return String(s[key] ?? "") === String(selectedCoordinatorId ?? "");
            }
          }
          const c = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
          const cid = c ? (c.id ?? c.coordinator_id) : undefined;
          return String(cid ?? "") === String(selectedCoordinatorId ?? "");
        });

        if (filtered && filtered.length > 0) {
          const ids = filtered.map((s) => s.id ?? s.student_id ?? s.user_id ?? s.application_id).filter(Boolean);
          // Limit to first 20 students to avoid too many requests
          const limitedIds = ids.slice(0, 20);
          const reqs = limitedIds.map((id) => fetch(`${apiBase}/api/v1/weekly-entries/student/${id}`, { headers, credentials: "include" })
            .then((res) => res.json()).catch(() => ([])));
          const results = await Promise.all(reqs);

          const normalizeWeekly = (p) => {
            if (!p) return [];
            if (Array.isArray(p?.data)) return p.data;
            if (Array.isArray(p?.weekly_entries)) return p.weekly_entries;
            if (Array.isArray(p)) return p;
            return [];
          };
          const all = results.flatMap((p) => normalizeWeekly(p));
          const weekNums = new Set();
          for (const row of all) {
            const wn = Number(row?.week_number ?? row?.weekNumber ?? row?.week);
            if (!Number.isNaN(wn) && wn > 0) weekNums.add(wn);
          }
          const sorted = Array.from(weekNums).sort((a, b) => a - b);
          if (!cancelled) {
            setAvailableWeeks(sorted);
            if (!sorted.includes(Number(selectedWeek))) {
              setSelectedWeek(sorted.length ? sorted[0] : "");
            }
          }
        }
      } catch {
        if (!cancelled) {
          setAvailableWeeks([]);
          setSelectedWeek("");
        }
      }
    }
    loadWeeks();
    return () => { cancelled = true; };
  }, [selectedCoordinatorId, selectedSectionId]);

  // Coordinators list removed from dashboard

  // Destructure data safely (using default values)
  const dash = dashboard || loaderData?.dashboard || {};
  const totalStudents = (dash.totalStudents ?? dash.total_students) ?? 0;
  const totalCompanies = (dash.totalCompanies ?? dash.total_companies) ?? 0;
  const totalCoordinators = (dash.totalCoordinators ?? dash.total_coordinators) ?? 0;
  const totalPrograms = (dash.totalPrograms ?? dash.total_programs) ?? 0;

  const stats = [
    {
      label: "Total Interns",
      value: totalStudents,
      color: "blue",
      icon: <FaUserGraduate className="text-blue-500" size={32} />,
    },
    {
      label: "Total Companies",
      value: totalCompanies,
      color: "violet",
      icon: <FaBuilding className="text-orange-500" size={32} />,
    },
    {
      label: "Total Coordinators",
      value: totalCoordinators,
      color: "red",
      icon: <FaUsers className="text-red-500" size={32} />,
    },
    {
      label: "Total Programs",
      value: totalPrograms,
      color: "teal",
      icon: <FaBook className="" size={32} />,
    },
  ];


  return (
    <>
      <Page>
        <div className="bg-blue-600 w-100 rounded-md px-2 py-7">
          <Heading
            level={3}
            text={"Welcome, Chairperson! 👋"}
            textColor="text-white"
          />
        </div>

        <section>
          <div className="p-6 bg-gray-100">
            {/* Overview Section */}
            <div className="mb-4">
              <h3 className="text-xl font-semibold text-gray-800">Overview</h3>
            </div>

            {/* Cards Grid */}
            <div className="grid grid-cols-2 grid-rows-2 p-4 h-[400px] gap-4">
              {stats.map((stat, index) => (
                <div
                  key={index}
                  className={`flex flex-col items-center justify-center bg-white shadow-md rounded-lg border-t-4 border-${stat.color}-500 p-4`}
                >
                  {/* Icon */}
                  <div className="mb-2">{stat.icon}</div>
                  {/* Label */}
                  <span className="text-gray-600 text-sm font-medium">
                    {stat.label}
                  </span>
                  {/* Value */}
                  <span className="text-6xl font-bold text-gray-800">
                    {stat.value}
                  </span>
                </div>
              ))}
            </div>

            {/* Controls for Section and Week */}
            <div className="flex flex-wrap items-center gap-3 p-4 bg-gray-50 border rounded">
              <div className="flex items-center gap-2">
                <label className="text-sm font-semibold text-gray-700">Coordinator:</label>
                <select
                  value={selectedCoordinatorId}
                  onChange={(e) => {
                    setSelectedCoordinatorId(e.target.value);
                    setSelectedSectionId(""); // Reset section when coordinator changes
                  }}
                  className="px-3 py-2 border rounded text-gray-900 bg-white"
                >
                  <option value="">Select Coordinator</option>
                  {coordinators.map((c) => (
                    <option key={c.id} value={c.id}>{c.label}</option>
                  ))}
                  {coordinators.length === 0 && (
                    <option value="">-- No coordinators found --</option>
                  )}
                </select>
              </div>
              {/* Section dropdown - only show if coordinator has multiple sections */}
              {selectedCoordinatorId && coordinatorSections.length > 1 && (
                <div className="flex items-center gap-2">
                  <label className="text-sm font-semibold text-gray-700">Section:</label>
                  <select
                    value={selectedSectionId || coordinatorSections[0]?.id || ""}
                    onChange={(e) => {
                      setSelectedSectionId(e.target.value);
                      setRefreshTrigger(prev => prev + 1);
                    }}
                    className="px-3 py-2 border rounded text-gray-900 bg-white"
                  >
                    {coordinatorSections.map((sec) => (
                      <option key={sec.id} value={sec.id}>{sec.name}</option>
                    ))}
                  </select>
                </div>
              )}
              {/* Show section name if only one section exists */}
              {selectedCoordinatorId && coordinatorSections.length === 1 && (
                <div className="flex items-center gap-2">
                  <label className="text-sm font-semibold text-gray-700">Section:</label>
                  <span className="px-3 py-2 bg-gray-100 border rounded text-gray-700">
                    {coordinatorSections[0].name}
                  </span>
                </div>
              )}
              <div className="flex items-center gap-2">
                <label className="text-sm font-semibold text-gray-700">Week:</label>
                <select
                  value={selectedWeek}
                  onChange={(e) => {
                    const val = e.target.value === "" ? "" : (e.target.value === "overall" ? "overall" : Number(e.target.value));
                    setSelectedWeek(val);
                    setRefreshTrigger(prev => prev + 1);
                  }}
                  className="px-3 py-2 border rounded text-gray-900 bg-white"
                >
                  <option value="">Select Week</option>
                  <option value="overall">Overall (All Weeks)</option>
                  {availableWeeks.length === 0 && (
                    <option value="" disabled>No weeks available</option>
                  )}
                  {availableWeeks.map((w) => (
                    <option key={w} value={w}>Week {w}</option>
                  ))}
                </select>
                <button
                  onClick={() => setRefreshTrigger(prev => prev + 1)}
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  Refresh
                </button>
              </div>
            </div>

            {/* Summary Block - only when both coordinator and week are selected */}
            {!!selectedCoordinatorId && (selectedWeek === "overall" || !!selectedWeek) && (
              <div ref={summaryRef}>
                <ChairpersonSummary 
                  coordinatorId={selectedCoordinatorId}
                  sectionId={selectedSectionId || null}
                  week={selectedWeek} 
                  refreshTrigger={refreshTrigger}
                  onExportReady={setExportSnapshot}
                />
              </div>
            )}

          

            {/* Coordinators section removed per requirement */}
          </div>
        </section>
      </Page>
    </>
  );
}
