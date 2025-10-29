import React, { useEffect, useMemo, useState } from "react";
import Loader from "../components/common/Loader";
import ManageHeader from "../components/common/ManageHeader";
import DynamicDataGrid from "../components/tables/DynamicDataGrid";
import { Button } from "@headlessui/react";
import useForm from "../hooks/useForm";
import FormModal from "../components/modals/FormModal";
import CoordinatorForm from "../components/forms/CoordinatorForm";
import { getRequest, postFormDataRequest } from "../api/apiHelpers";
import useRequest from "../hooks/useRequest";
import DeleteConfirmModal from "../components/modals/DeleteConfirmModal";
import ImportCoordinatorForm from "../components/forms/ImportCoordinatorForm";
import Page from "../components/common/Page";
import Section from "../components/common/Section";
import Heading from "../components/common/Heading";
import Text from "../components/common/Text";
import { useLocation } from "react-router-dom";
import {
  DELETE_API_ROUTE_PATH,
  GET_API_ROUTE_PATH,
  POST_API_ROUTE_PATH,
  PUT_API_ROUTE_PATH,
} from "../api/resources";
import StatusDropdown from "../components/dropdowns/StatusDropdown";
import { loginInfo } from "../formDefaults/loginInfo";
import { personalInfo } from "../formDefaults/personalInfo";
import { addressInfo } from "../formDefaults/addressInfo";
import {
  getCoordinatorActionColumns,
  getCoordinatorStaticColumns,
} from "../utils/columns/coordinatorColumns";

const ViewCoordinatorsPage = ({ authorizeRole }) => {
  // Open location
  const location = useLocation();

  // Loading State
  const [loading, setLoading] = useState(false);

  // Container State for Lists
  const [listOfPrograms, setListOfPrograms] = useState([]);

  /**
   * File State
   */
  const [file, setFile] = useState(null);
  const [status, setStatus] = useState(""); // 'success' or 'error

  // Row State
  const [rows, setRows] = useState([]);
  // Analytics state
  const [analyticsLoading, setAnalyticsLoading] = useState(false);
  const [coordinatorAnalytics, setCoordinatorAnalytics] = useState([]);
  // AI Insight feature removed
  // const [aiInsight, setAiInsight] = useState("");
  // const [aiLoading, setAiLoading] = useState(false);
  // const [perCoordinatorInsights, setPerCoordinatorInsights] = useState({});
  const analyticsRef = React.useRef(null);

  // Modal State
  const [isOpen, setIsOpen] = useState(false);
  const [isEditOpen, setEditIsOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [isOpenImport, setIsOpenImport] = useState(false);

  /**
   *
   *
   *
   * URL State
   *
   *
   */
  const [dataGridUrl, setDataGridUrl] = useState(
    GET_API_ROUTE_PATH.coordinators
  );

  /**
   *
   *
   * Select State
   *
   *
   *
   */
  const [selectedStatus, setSelectedStatus] = useState("all");
  const [selectedCoordinator, setSelectedCoordinator] = useState({});
  const [selectedProgramId, setSelectedProgramId] = useState(null);

  useEffect(() => {
    setDataGridUrl(
      selectedStatus === "archived"
        ? `${GET_API_ROUTE_PATH.coordinators}?status=archived`
        : GET_API_ROUTE_PATH.coordinators
    );
  }, [selectedStatus]);

  // Use the useForm hook to manage form data
  const { formData, handleInputChange, resetForm, setFormValues } = useForm({
    ...loginInfo,
    ...personalInfo,
    ...addressInfo,
    program_id: "",
  });

  /**
   * Use Request
   */
  const {
    errors: validationErrors,
    postData,
    putData,
    deleteData,
  } = useRequest({
    setData: setRows,
    setIsOpen: setIsOpen,
    setLoading: setLoading,
  });

  /**
   * Function that adds a new coordinator
   */
  const addCoordinator = () => {
    // console.log(formData);

    // console.log(formData);

    // POST METHOD
    postData({
      url: POST_API_ROUTE_PATH.coordinators,
      payload: formData,
      resetForm: resetForm,
      params: {
        requestedBy: authorizeRole,
      },
    });
  };

  /**
   * Function that restore a deleted coordinator type
   */
  const restoreCoordinator = (id) => {
    // console.log(id);

    // UPDATE METHOD
    putData({
      url: `${PUT_API_ROUTE_PATH.coordinators}/${id}/restore`,
      id: id,
    });
  };

  /**
   * Function that updates a coordinator
   */
  const updateCoordinator = () => {
    // PUT METHOD
    putData({
      url: `${PUT_API_ROUTE_PATH.coordinators}/${selectedCoordinator["id"]}`,
      payload: formData,
      selectedData: selectedCoordinator,
      setIsOpen: setEditIsOpen,
      resetForm: resetForm,
      params: {
        requestedBy: authorizeRole,
      },
    });
  };

  /**
   * Function that opens a modal for edit
   */
  const handleEditModal = (row) => {
    // Set Select State
    setSelectedCoordinator(row);

    // console.log(row);

    // Set Form Values
    setFormValues({
      ...row,
      gender: row.gender.toLowerCase(),
    });
    // Open Edit Modal
    setEditIsOpen(true);
  };

  /**
   * Function that deletes a coordinator
   */
  const deleteCoordinator = () => {
    // DELETE METHOD
    deleteData({
      url: `${DELETE_API_ROUTE_PATH.coordinators}/${selectedCoordinator["id"]}`,
      id: selectedCoordinator["id"],
      setIsDeleteOpen: setIsDeleteOpen,
    });
  };

  /**
   * Function that opens a modal for delete
   */
  const handleDeleteModal = (row) => {
    // Set Select State
    setSelectedCoordinator(row);

    // Open Delete Modal
    setIsDeleteOpen(true);
  };

  // Static Columns
  const staticColumns = useMemo(
    () =>
      getCoordinatorStaticColumns({
        pathname: location.pathname,
        selectedStatus: selectedStatus,
      }),
    [selectedStatus]
  );

  // Action Column
  const actionColumn = useMemo(
    () =>
      getCoordinatorActionColumns({
        handleEditModal: handleEditModal,
        handleDeleteModal: handleDeleteModal,
        handleRestore: restoreCoordinator,
        authorizeRole: authorizeRole,
        selectedStatus: selectedStatus,
      }),
    [selectedStatus]
  );

  const columns = useMemo(
    () => [...staticColumns, actionColumn],
    [staticColumns, actionColumn]
  );

  /**
   *
   * Use Effect Area
   *
   */
  // Loads the lists using UseEffect
  useEffect(() => {
    // Fetch Needed Data for Lists in Select
    const fetchListOfPrograms = async () => {
      // Set Loading
      setLoading(true);

      try {
        const listOfProgramsResponse = await getRequest({
          url: "/api/v1/programs/lists",
        });

        // Set State
        setListOfPrograms(listOfProgramsResponse);
      } catch (error) {
        console.log(error);
      } finally {
        setLoading(false);
      }
    };

    // ! Fetch the program ID of a Chairperson Only
    const fetchCurrentProgramId = async () => {
      // Set Loading
      setLoading(true);

      try {
        const currentProgramResponse = await getRequest({
          url: "/api/v1/users/chairpersons/current-program-id",
        });

        /* console.log({
          ...formData,
          programId: currentProgramResponse,
        }); */

        if (currentProgramResponse) {
          setSelectedProgramId(currentProgramResponse);

          setFormValues({
            ...formData,
            program_id: currentProgramResponse,
          });
        }
      } catch (error) {
        console.log(error);
      } finally {
        setLoading(false);
      }
    };

    // Always fetch list of programs so dropdown is populated
    fetchListOfPrograms();
    // For chairperson, also fetch their current program id for default selection
    if (authorizeRole !== "admin" && authorizeRole !== "dean") {
      fetchCurrentProgramId();
    }
  }, []);

  /**
   * A function that handles the File Change
   */
  const handleFileChange = (event) => {
    const selectedFile = event.target.files[0];
    setFile(selectedFile);
    setStatus(""); // Reset status on file selection
  };

  // Submit File
  const submitFile = async (event) => {
    event.preventDefault();
    if (!file) {
      setStatus("error");
      return;
    }

    // Create a FormData object
    const formData = new FormData();
    formData.append("file", file);

    try {
      // Set Loading
      setLoading(true);

      // Assuming your backend has an endpoint for file upload
      const response = await postFormDataRequest({
        url: `/api/v1/users/coordinators/${selectedProgramId}/upload-coordinators`,
        data: formData,
      });

      setIsOpenImport(false);
      setStatus("success");

      if (response) {
        window.location.reload(); // Reload window
      }
    } catch (error) {
      console.error("Error uploading file:", error);
      setStatus("error");
    } finally {
      setLoading(false);
    }
  };

  // Load analytics for coordinators displayed in the grid
  const loadAnalytics = async () => {
    try {
      setAnalyticsLoading(true);
      const apiBase = import.meta.env.VITE_API_BASE_URL;
      const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${JSON.parse(localStorage.getItem("ACCESS_TOKEN"))}`,
      };

      // 0) Always build the coordinator list from backend (fetch ALL, not just grid page)
      let coordItems = [];
      try {
        const attempts = [
          // Prefer V2 with explicit role + large perPage
          `${apiBase}/api/v1/users/v2/coordinators?page=1&perPage=1000&requestedBy=chairperson`,
          // Non-paginated lists endpoint (all available to chairperson)
          `${apiBase}/api/v1/users/coordinators/lists`,
          // V1 with large perPage + role
          `${apiBase}/api/v1/users/coordinators?perPage=1000&requestedBy=chairperson`,
          // Legacy plain endpoint
          `${apiBase}/api/v1/coordinators`,
        ];
        for (const url of attempts) {
          try {
            const r = await fetch(url, { headers, credentials: 'include' });
            const p = await r.json().catch(() => ({}));
            const list = Array.isArray(p?.data?.data)
              ? p.data.data
              : (Array.isArray(p?.data) ? p.data : (Array.isArray(p) ? p : []));
            if (Array.isArray(list) && list.length) {
              coordItems = list.map((c) => ({
                id: String(c.id ?? c.user_id ?? c.coordinator_id ?? ''),
                first: c.first_name || c.firstName || '',
                last: c.last_name || c.lastName || '',
                name: c.name || c.fullName || 'Coordinator',
              }));
              break;
            }
          } catch(_) { /* try next endpoint */ }
        }
      } catch(_) {}

      // 1) Fetch students once to compute studentsCount per coordinator
      let students = [];
      try {
        const r = await fetch(`${apiBase}/api/v1/chairperson/students`, { headers, credentials: "include" });
        const p = await r.json().catch(() => ([]));
        students = Array.isArray(p?.data) ? p.data : (Array.isArray(p) ? p : []);
      } catch (_) {}

      const out = [];
      // Minimal helpers for fallback computation from real weekly-entries (no mock data)
      const keywordSets = [
        ["math", "mathematics", "science", "algorithm", "compute", "analysis"],
        ["best practice", "standard", "policy", "method", "procedure", "protocol"],
        ["analyze", "analysis", "problem", "root cause", "diagnose", "troubleshoot"],
        ["user need", "requirement", "stakeholder", "ux", "usability"],
        ["design", "implement", "evaluate", "build", "develop", "test", "setup", "configure", "configuration", "install"],
        ["safety", "health", "environment", "security", "ethical"],
        ["tool", "framework", "library", "technology", "platform"],
        ["team", "collaborat", "leader", "group"],
        ["plan", "schedule", "timeline", "project plan"],
        ["communicat", "present", "documentation", "write", "report"],
        ["impact", "society", "organization", "community"],
        ["ethical", "privacy", "legal", "compliance"],
        ["learn", "self-study", "latest", "new skill"],
        ["research", "experiment", "study", "investigation"],
        ["filipino", "heritage", "culture", "tradition"],
      ];
      const stripHtml = (t) => String(t || "")
        .replace(/<\s*\/? .*?>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>')
        .replace(/\s+/g, ' ')
        .trim();
      const computeFromEntries = (list = []) => {
        const text = list.map((r)=> `${stripHtml(r.tasks||r.task||r.activities||"")} ${stripHtml(r.learnings||r.learning||"")}`).join(' ').toLowerCase();
        const counts = keywordSets.map((set)=> set.some((kw)=> text.includes(kw)) ? 1 : 0);
        const nonZero = counts.reduce((a,b)=> a+b, 0);
        return Math.round((nonZero/15)*100);
      };
      const computeCountsFromEntries = (list = []) => {
        const text = list.map((r)=> `${stripHtml(r.tasks||r.task||r.activities||"")} ${stripHtml(r.learnings||r.learning||"")}`).join(' ').toLowerCase();
        return keywordSets.map((set)=> {
          let c = 0; for (const kw of set) { if (text.includes(kw)) c++; }
          return c;
        });
      };
      for (const c of coordItems) {
        const id = String(c.id || "");
        const label = `${id} - ${[c.first, c.last].filter(Boolean).join(' ') || c.name || 'Coordinator'}`;
        // Fetch accurate keywordScores from backend using coordinatorId (overall)
        let keywordScores = [];
        // we removed trends and any client-side fallbacks
        try {
          const qp = new URLSearchParams({ coordinatorId: String(id), useGPT: '0', analysisType: 'chairman', isOverall: '1' });
          const resp = await fetch(`${apiBase}/api/v1/summary/chair?${qp.toString()}`, {
            method: 'GET', headers: { ...headers, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'include'
          });
          if (resp.ok) {
            const j = await resp.json();
            if (Array.isArray(j?.combinedScores)) keywordScores = j.combinedScores; else if (Array.isArray(j?.keywordScores)) keywordScores = j.keywordScores;
          }
        } catch (_) {}
        let nonZero = (keywordScores || []).filter((v) => Number(v) > 0).length;
        let poCoveragePercent = Math.round((nonZero / 15) * 100);

        // Student count for this coordinator
        const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"]; 
        const studentsCount = students.filter((s) => {
          for (const key of coordinatorKeyNames) {
            if (s && Object.prototype.hasOwnProperty.call(s, key)) {
              if (String(s[key] ?? "") === String(id)) return true;
            }
          }
          const cc = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
          const cid2 = cc ? (cc.id ?? cc.coordinator_id) : undefined;
          return String(cid2 ?? "") === String(id);
        }).length;
        // Strengths and gaps primarily from backend scores; fallback to entries when empty
        let strengths = [];
        let gaps = [];
        const pairsFromApi = (keywordScores || []).map((v, i) => ({ po: `PO${i+1}`, v: Number(v)||0 }));
        if (studentsCount === 0) {
          poCoveragePercent = 0;
          strengths = [];
          gaps = [];
        } else if (pairsFromApi.length > 0) {
          strengths = pairsFromApi.filter(x => x.v > 0).sort((a,b)=> b.v - a.v).map(x => x.po);
          gaps = pairsFromApi.filter(x => x.v === 0).map(x => x.po);
        } else {
          // Fallback to entries for strengths/gaps and coverage
          try {
            const coordinatorKeyNames = ["coordinator_id", "coordinatorId", "coordinatorID", "coordinator_id_fk"]; 
            const assigned = students.filter((s) => {
              for (const key of coordinatorKeyNames) {
                if (s && Object.prototype.hasOwnProperty.call(s, key)) {
                  if (String(s[key] ?? "") === String(id)) return true;
                }
              }
              const cc = s.coordinator || s.ojt_coordinator || s.assignedCoordinator;
              const cid2 = cc ? (cc.id ?? cc.coordinator_id) : undefined;
              return String(cid2 ?? "") === String(id);
            });
            const reqs = assigned.map((s) => {
              const sid = s.id ?? s.student_id ?? s.user_id ?? s.application_id;
              return fetch(`${apiBase}/api/v1/weekly-entries/student/${sid}`, { headers, credentials: 'include' })
                .then((r)=> r.json()).catch(()=> []);
            });
            const payloads = await Promise.all(reqs);
            const normalize = (p)=> Array.isArray(p?.data) ? p.data : (Array.isArray(p?.weekly_entries) ? p.weekly_entries : (Array.isArray(p) ? p : []));
            const entries = payloads.flatMap((p)=> normalize(p));
            if (entries.length) {
              poCoveragePercent = computeFromEntries(entries);
              const counts = computeCountsFromEntries(entries);
              const pairs = counts.map((v, i) => ({ po: `PO${i+1}`, v: Number(v)||0 }));
              strengths = pairs.filter(x => x.v > 0).sort((a,b)=> b.v - a.v).map(x => x.po);
              gaps = pairs.filter(x => x.v === 0).map(x => x.po);
            }
          } catch(_) {}
        }
        out.push({ id, label, poCoveragePercent, studentsCount, strengths, gaps });
      }
      out.sort((a,b)=> b.poCoveragePercent - a.poCoveragePercent);
      setCoordinatorAnalytics(out);
    } catch (_) {
      setCoordinatorAnalytics([]);
    } finally {
      setAnalyticsLoading(false);
    }
  };

  // AI Insight feature removed

  // Export analytics section to PDF
  const handleDownloadAnalyticsPDF = () => {
    try {
      if (!analyticsRef.current) return;
      const html = `<!doctype html><html><head><meta charset="utf-8"/><title>Coordinator Analytics</title>
      <style>@page{size:A4;margin:16mm}body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;color:#111827}
      h2{margin:0 0 8px}.header{margin-bottom:12px;border-bottom:1px solid #e5e7eb;padding-bottom:6px}
      table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #e5e7eb;padding:8px;text-align:left}
      .bar{height:10px;background:#e5e7eb;position:relative}.fill{height:10px;background:#16a34a}
      </style><script>window.addEventListener('load',()=>setTimeout(()=>window.print(),150));window.onafterprint=()=>window.close();</script></head>
      <body><div class="header"><h2>Coordinator Analytics</h2></div>${analyticsRef.current.innerHTML}</body></html>`;
      const blob = new Blob([html], { type: 'text/html' });
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank', 'noopener');
      setTimeout(()=>{ try { URL.revokeObjectURL(url); } catch(e){} }, 10000);
    } catch(_) {}
  };

  // ! Only Display this if the User is Admin
  if (authorizeRole === "admin") {
    return (
      <>
        <Loader loading={loading} />

        <div className="mt-3">
          <div className="flex items-center justify-between">
            <StatusDropdown
              selectedStatus={selectedStatus}
              setSelectedStatus={setSelectedStatus}
            />
            <ManageHeader
              isOpen={isOpen}
              setIsOpen={setIsOpen}
              addPlaceholder="Add New Coordinator"
              showExportButton={false}
              showImportButton={true}
              isImportOpen={isOpenImport}
              setIsImportOpen={setIsOpenImport}
            />
          </div>

          <DynamicDataGrid
            searchPlaceholder={"Search Coordinator"}
            rows={rows}
            setRows={setRows}
            columns={columns}
            url={dataGridUrl}
            requestedBy={authorizeRole}
          />

          {/* Modals */}
          {/* Add Form Modal */}
          <FormModal
            isOpen={isOpen}
            setIsOpen={setIsOpen}
            modalTitle="Add Coordinator"
            onSubmit={addCoordinator}
          >
            <CoordinatorForm
              coordinatorInfo={formData}
              handleCoordinatorInfoChange={handleInputChange}
              programs={listOfPrograms}
              errors={validationErrors}
            />
          </FormModal>

          {/* Edit Form Modal */}
          <FormModal
            isOpen={isEditOpen}
            setIsOpen={setEditIsOpen}
            modalTitle="Edit Coordinator"
            onSubmit={updateCoordinator}
          >
            <CoordinatorForm
              method="put"
              coordinatorInfo={formData}
              handleCoordinatorInfoChange={handleInputChange}
              programs={listOfPrograms}
              errors={validationErrors}
            />
          </FormModal>

          {/* Delete Form Modal */}
          <DeleteConfirmModal
            open={isDeleteOpen}
            setOpen={setIsDeleteOpen}
            title="Delete coordinator"
            message="Are you sure you want to delete this coordinator?"
            handleDelete={deleteCoordinator}
          />

          {/* Import Form Modal */}
          <FormModal
            isOpen={isOpenImport}
            setIsOpen={setIsOpenImport}
            modalTitle="Import Coordinators"
            onSubmit={submitFile}
          >
            <ImportCoordinatorForm
              file={file}
              set={setFile}
              status={status}
              setStatus={setStatus}
              handleFileChange={handleFileChange}
              programs={listOfPrograms}
              programId={selectedProgramId}
              setProgramId={setSelectedProgramId}
              withSelection={true}
            />
          </FormModal>
        </div>
      </>
    );
  }

  // ! Other User Role can view this
  else {
    return (
      <Page>
        <Loader loading={loading} />

        <Section>
          <Heading level={3} text="Manage Coordinators" />
          <Text className="text-md text-blue-950">
            This is where you manage the coordinators.
          </Text>
          <hr className="my-3" />
        </Section>

        <div className="mt-3">
          <ManageHeader
            isOpen={isOpen}
            setIsOpen={setIsOpen}
            addPlaceholder="Add New Coordinator"
            showExportButton={false}
            showImportButton={true}
            isImportOpen={isOpenImport}
            setIsImportOpen={setIsOpenImport}
          />

          <DynamicDataGrid
            searchPlaceholder={"Search Coordinator"}
            rows={rows}
            setRows={setRows}
            columns={columns}
            url={GET_API_ROUTE_PATH.coordinators}
            requestedBy={authorizeRole}
          />

          {/* Analytics below grid */}
          <div className="mt-6 bg-white border rounded-lg p-4" ref={analyticsRef}>
            <div className="flex items-center justify-between mb-3">
              <h4 className="text-lg font-semibold text-gray-800">Program Outcomes Coverage by Coordinator</h4>
              <div className="flex gap-2">
                <button onClick={loadAnalytics} className="px-3 py-2 bg-slate-700 text-white rounded hover:bg-slate-800">{analyticsLoading ? 'Loading…' : 'Load Analytics'}</button>
                {/* AI Insight feature removed */}
                <button onClick={handleDownloadAnalyticsPDF} disabled={coordinatorAnalytics.length === 0} className="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50">Download PDF</button>
              </div>
            </div>
            {coordinatorAnalytics.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-gray-50 text-gray-700">
                      <th className="text-left px-3 py-2">Coordinator</th>
                      <th className="text-left px-3 py-2">PO Coverage</th>
                      <th className="text-left px-3 py-2">Students</th>
                      <th className="text-left px-3 py-2">Gaps (all POs with zero)</th>
                    </tr>
                  </thead>
                  <tbody>
                    {coordinatorAnalytics.map((r) => (
                      <tr key={r.id} className="border-t">
                        <td className="px-3 py-2 whitespace-nowrap">{r.label}</td>
                        <td className="px-3 py-2">
                          <div className="h-3 bg-gray-200 rounded relative" style={{ minWidth: 200 }}>
                            <div className="h-3 bg-green-600 rounded" style={{ width: `${r.poCoveragePercent}%` }}></div>
                            <div className="absolute inset-0 flex items-center justify-center text-[11px] text-white">{r.poCoveragePercent}%</div>
                          </div>
                        </td>
                        <td className="px-3 py-2">{r.studentsCount}</td>
                        <td className="px-3 py-2">{(r.gaps || []).join(', ') || '—'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="text-gray-500 text-sm">Click "Load Analytics" to compute coverage scores for the coordinators in this list.</div>
            )}
            {/* AI Insight feature removed; trends removed previously */}
          </div>

          {/* Modals */}
          {/* Add Form Modal */}
          <FormModal
            isOpen={isOpen}
            setIsOpen={setIsOpen}
            modalTitle="Add Coordinator"
            onSubmit={addCoordinator}
          >
            <CoordinatorForm
              coordinatorInfo={formData}
              handleCoordinatorInfoChange={handleInputChange}
              programs={listOfPrograms}
              errors={validationErrors}
              authorizeRole={authorizeRole}
            />
          </FormModal>

          {/* Edit Form Modal */}
          <FormModal
            isOpen={isEditOpen}
            setIsOpen={setEditIsOpen}
            modalTitle="Edit Coordinator"
            onSubmit={updateCoordinator}
          >
            <CoordinatorForm
              method="put"
              coordinatorInfo={formData}
              handleCoordinatorInfoChange={handleInputChange}
              programs={listOfPrograms}
              errors={validationErrors}
            />
          </FormModal>

          {/* Delete Form Modal */}
          <DeleteConfirmModal
            open={isDeleteOpen}
            setOpen={setIsDeleteOpen}
            title="Delete coordinator"
            message="Are you sure you want to delete this coordinator?"
            handleDelete={deleteCoordinator}
          />

          {/* Import Form Modal */}
          <FormModal
            isOpen={isOpenImport}
            setIsOpen={setIsOpenImport}
            modalTitle="Import Coordinators"
            onSubmit={submitFile}
          >
            <ImportCoordinatorForm
              file={file}
              set={setFile}
              status={status}
              setStatus={setStatus}
              handleFileChange={handleFileChange}
              programs={
                // ! For Dean Only
                authorizeRole === "dean" && listOfPrograms
              }
              programId={selectedProgramId}
              setProgramId={setSelectedProgramId}
              // Display Selection if role is dean
              withSelection={authorizeRole === "dean"}
            />
          </FormModal>
        </div>
      </Page>
    );
  }
};

export default ViewCoordinatorsPage;
