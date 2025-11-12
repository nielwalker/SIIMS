import React, { useEffect, useMemo, useState } from "react";
import Loader from "../components/common/Loader";
import ManageHeader from "../components/common/ManageHeader";
import DynamicDataGrid from "../components/tables/DynamicDataGrid";
import { Button } from "@headlessui/react";
import useForm from "../hooks/useForm";
import FormModal from "../components/modals/FormModal";
import CoordinatorForm from "../components/forms/CoordinatorForm";
import { getRequest, postFormDataRequest, putFormDataRequest, putRequest, deleteRequest } from "../api/apiHelpers";
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
  const [allCoordinators, setAllCoordinators] = useState([]);

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

  // Section creation modal state
  const [isCreateSectionOpen, setIsCreateSectionOpen] = useState(false);
  const [sectionFormData, setSectionFormData] = useState({ name: "", coordinator_id: "", program_id: "" });
  // Section edit state
  const [editingSection, setEditingSection] = useState(null);
  const [editSectionFormData, setEditSectionFormData] = useState({ name: "", coordinator_id: "", program_id: "" });
  const [isDeleteSectionOpen, setIsDeleteSectionOpen] = useState(false);
  const [sectionToDelete, setSectionToDelete] = useState(null);
  // Bulk assign section modal state
  const [isBulkAssignSectionOpen, setIsBulkAssignSectionOpen] = useState(false);
  const [selectedCoordinatorIds, setSelectedCoordinatorIds] = useState([]);
  const [allSections, setAllSections] = useState([]);
  const [bulkAssignSectionId, setBulkAssignSectionId] = useState("");
  const [bulkAssignSectionName, setBulkAssignSectionName] = useState("");


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

  // Load all sections for bulk assignment
  useEffect(() => {
    fetchAllSections();
  }, []);

  // Fetch all coordinators for section modal
  const fetchAllCoordinators = async () => {
    try {
      const resp = await getRequest({ url: `/api/v1/users/coordinators/lists` });
      const coordList = Array.isArray(resp) ? resp : (Array.isArray(resp?.data) ? resp.data : []);
      // Normalize coordinators to have id, first_name, last_name, and name
      const normalized = coordList.map((c) => {
        const id = c.id ?? c.user_id ?? c.coordinator_id ?? "";
        // Check if API returns full name directly (from getAllListsOfCoordinators)
        const fullName = c.name ?? "";
        // Try to extract first/last names, or use full name
        const first = c.first_name ?? c.firstName ?? c.user?.first_name ?? "";
        const middle = c.middle_name ?? c.middleName ?? c.user?.middle_name ?? "";
        const last = c.last_name ?? c.lastName ?? c.user?.last_name ?? "";
        // If we have full name but no separate names, use full name
        const displayName = fullName || (first || last ? `${first} ${middle} ${last}`.trim() : String(id));
        return { 
          id: String(id), 
          first_name: first, 
          middle_name: middle, 
          last_name: last,
          name: displayName
        };
      });
      setAllCoordinators(normalized);
    } catch (_) {
      setAllCoordinators([]);
    }
  };

  // Refresh sections and coordinators when modal opens and clear edit state when closing
  useEffect(() => {
    if (isCreateSectionOpen) {
      fetchAllSections();
      fetchAllCoordinators();
    } else {
      // Clear edit state when modal closes
      setEditingSection(null);
      setEditSectionFormData({ name: "", coordinator_id: "", program_id: "" });
    }
  }, [isCreateSectionOpen]);

  const fetchAllSections = async () => {
    try {
      // Use getAll=true to fetch ALL sections without limit
      const resp = await getRequest({ 
        url: `/api/v1/sections`, 
        params: { 
          requestedBy: authorizeRole || 'chairperson',
          getAll: 'true' // Fetch all sections, not just 10
        } 
      });
      const payload = resp;
      // Handle different response formats
      const list = Array.isArray(payload?.data) 
        ? payload.data 
        : (Array.isArray(payload) 
          ? payload 
          : []);
      console.log("Fetched all sections for Manage Sections modal:", list.length, "sections");
      setAllSections(list);
    } catch (error) {
      console.error("Error fetching all sections:", error);
      setAllSections([]);
    }
  };

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

  // Create standalone section
  const createSection = async () => {
    if (!sectionFormData.name || !sectionFormData.coordinator_id || !sectionFormData.program_id) {
      alert('Please fill in Section Name, Coordinator, and Program.');
      return;
    }
    try {
      setLoading(true);
      const fd = new FormData();
      fd.append('name', String(sectionFormData.name || ''));
      fd.append('coordinator_id', String(sectionFormData.coordinator_id));
      fd.append('program_id', String(sectionFormData.program_id));
      fd.append('requestedBy', String(authorizeRole || 'chairperson'));
      await postFormDataRequest({ url: POST_API_ROUTE_PATH.sections, data: fd });
      setSectionFormData({ name: "", coordinator_id: "", program_id: "" });
      fetchAllSections();
      // Refresh coordinator list to show updated sections
      setTimeout(() => {
        window.location.reload();
      }, 300);
    } catch (error) {
      console.log(error);
      alert('Failed to create section. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  // Handle edit section click
  const handleEditSection = (section) => {
    setEditingSection(section);
    setEditSectionFormData({
      name: section.name || "",
      coordinator_id: String(section.coordinator_id || ""),
      program_id: String(section.program_id || ""),
    });
  };

  // Update section
  const updateSection = async () => {
    if (!editingSection || !editSectionFormData.name || !editSectionFormData.coordinator_id || !editSectionFormData.program_id) {
      alert('Please fill in all required fields.');
      return;
    }
    try {
      setLoading(true);
      
      // Send as JSON instead of FormData for PUT requests - Laravel parses JSON better
      const payload = {
        name: String(editSectionFormData.name || '').trim(),
        coordinator_id: String(editSectionFormData.coordinator_id),
        program_id: String(editSectionFormData.program_id),
        requestedBy: String(authorizeRole || 'chairperson')
      };
      
      // Log for debugging
      console.log('Updating section:', {
        id: editingSection.id,
        payload: payload,
        url: `${PUT_API_ROUTE_PATH.sections}/${editingSection.id}`
      });
      
      const response = await putRequest({ 
        url: `${PUT_API_ROUTE_PATH.sections}/${editingSection.id}`, 
        data: payload
      });
      
      setEditingSection(null);
      setEditSectionFormData({ name: "", coordinator_id: "", program_id: "" });
      fetchAllSections();
      // Refresh coordinator list to show updated sections
      setTimeout(() => {
        window.location.reload();
      }, 300);
    } catch (error) {
      console.error('Update section error:', error);
      console.error('Full error response:', error?.response);
      console.error('Error response data:', error?.response?.data);
      console.error('Error response status:', error?.response?.status);
      
      // Handle validation errors
      let errorMessage = 'Failed to update section. Please try again.';
      if (error?.response?.data) {
        const errorData = error.response.data;
        console.error('Parsed error data:', errorData);
        
        if (errorData.message) {
          errorMessage = errorData.message;
        } else if (errorData.errors) {
          // Laravel validation errors format - flatten all errors
          const errorMessages = Object.entries(errorData.errors)
            .map(([field, messages]) => {
              const msgArray = Array.isArray(messages) ? messages : [messages];
              return `${field}: ${msgArray.join(', ')}`;
            })
            .join('\n');
          errorMessage = `Validation errors:\n${errorMessages}`;
        } else if (typeof errorData === 'string') {
          errorMessage = errorData;
        }
      } else if (error?.message) {
        errorMessage = error.message;
      }
      
      console.error('Final error message to display:', errorMessage);
      alert(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  // Handle delete section click
  const handleDeleteSectionClick = (section) => {
    setSectionToDelete(section);
    setIsDeleteSectionOpen(true);
  };

  // Delete section
  const deleteSection = async () => {
    if (!sectionToDelete) return;
    try {
      setLoading(true);
      await deleteRequest({
        url: `${DELETE_API_ROUTE_PATH.sections}/${sectionToDelete.id}`,
      });
      setIsDeleteSectionOpen(false);
      setSectionToDelete(null);
      fetchAllSections();
      // Refresh coordinator list to show updated sections
      setTimeout(() => {
        window.location.reload();
      }, 300);
    } catch (error) {
      console.log(error);
      alert('Failed to delete section. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  // Bulk assign selected coordinators to sections (create a section for each)
  const handleBulkAssignSection = async () => {
    if (selectedCoordinatorIds.length === 0) {
      alert('Please select at least one coordinator.');
      return;
    }
    
    // Determine the section name pattern
    let namePattern = '';
    let programIdForSections = '';
    
    if (bulkAssignSectionId) {
      // Use template section
      const templateSection = allSections.find(s => String(s.id) === String(bulkAssignSectionId));
      if (templateSection) {
        namePattern = templateSection.name;
        programIdForSections = templateSection.program_id;
      } else {
        alert('Selected section template not found.');
        return;
      }
    } else if (bulkAssignSectionName) {
      // Use custom name pattern
      namePattern = bulkAssignSectionName;
    } else {
      alert('Please select a section template or enter a section name pattern.');
      return;
    }
    
    try {
      setLoading(true);
      
      // For each selected coordinator, create a new section with just the name pattern (no coordinator name appended)
      for (const coordId of selectedCoordinatorIds) {
        const coordinator = rows.find(r => String(r.id) === String(coordId));
        if (coordinator) {
          const fd = new FormData();
          // Use only the name pattern, do not append coordinator name
          fd.append('name', namePattern);
          fd.append('coordinator_id', String(coordId));
          fd.append('program_id', String(coordinator.program_id || programIdForSections || selectedProgramId || ''));
          fd.append('requestedBy', String(authorizeRole || 'chairperson'));
          await postFormDataRequest({ url: POST_API_ROUTE_PATH.sections, data: fd });
        }
      }
      setSelectedCoordinatorIds([]);
      setBulkAssignSectionId("");
      setBulkAssignSectionName("");
      setIsBulkAssignSectionOpen(false);
      fetchAllSections();
      // Refresh coordinator list to show updated sections
      setTimeout(() => {
        window.location.reload();
      }, 300);
    } catch (error) {
      console.log(error);
      alert('Failed to assign sections. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  // Handle row selection change from grid
  const handleRowSelectionChange = (selectionModel) => {
    // selectionModel is an array of row IDs
    setSelectedCoordinatorIds(selectionModel || []);
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


  // ! Only Display this if the User is Admin
  if (authorizeRole === "admin") {
    return (
      <>
        <Loader loading={loading} />

        <div className="mt-3">
          <div className="flex items-center justify-between mb-3">
            <StatusDropdown
              selectedStatus={selectedStatus}
              setSelectedStatus={setSelectedStatus}
            />
            <div className="flex items-center gap-2">
              <ManageHeader
                isOpen={isOpen}
                setIsOpen={setIsOpen}
                addPlaceholder="Add New Coordinator"
                showExportButton={false}
                showImportButton={true}
                isImportOpen={isOpenImport}
                setIsImportOpen={setIsOpenImport}
              />
              {selectedCoordinatorIds.length > 0 && (
                <Button
                  onClick={() => setIsBulkAssignSectionOpen(true)}
                  className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-semibold"
                >
                  Assign Section ({selectedCoordinatorIds.length} selected)
                </Button>
              )}
              <Button
                onClick={() => setIsCreateSectionOpen(true)}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-semibold"
              >
                + Create Section
              </Button>
            </div>
          </div>

          <DynamicDataGrid
            searchPlaceholder={"Search Coordinator"}
            rows={rows}
            setRows={setRows}
            columns={columns}
            url={dataGridUrl}
            requestedBy={authorizeRole}
            onSelectionModelChange={handleRowSelectionChange}
            checkboxSelection={true}
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

          {/* Create Section Modal */}
          <FormModal
            isOpen={isCreateSectionOpen}
            setIsOpen={setIsCreateSectionOpen}
            modalTitle="Manage Sections"
            onSubmit={(e) => { e.preventDefault(); createSection(); }}
          >
            <div className="space-y-6">
              {/* Create New Section Form */}
              <div className="border-b pb-4">
                <Heading level={5} text="Create New Section" className="mb-3 text-lg font-semibold" />
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                      Section Name <span className="text-red-600">*</span>
                    </label>
                    <input
                      type="text"
                      className="w-full border rounded px-3 py-2"
                      value={sectionFormData.name}
                      onChange={(e) => setSectionFormData({...sectionFormData, name: e.target.value})}
                      placeholder="e.g., BSIT 4A"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                      Coordinator <span className="text-red-600">*</span>
                    </label>
                    <select
                      className="w-full border rounded px-3 py-2"
                      value={sectionFormData.coordinator_id}
                      onChange={(e) => setSectionFormData({...sectionFormData, coordinator_id: e.target.value})}
                      required
                    >
                      <option value="">- Select Coordinator -</option>
                      {(allCoordinators.length > 0 ? allCoordinators : rows).map((coord) => {
                        // Use name if available, otherwise construct from first_name/last_name, fallback to ID
                        const displayName = coord.name 
                          || (coord.first_name || coord.last_name 
                            ? `${coord.first_name || ''} ${coord.middle_name || ''} ${coord.last_name || ''}`.trim()
                            : `Coordinator ${coord.id}`);
                        return (
                          <option key={String(coord.id)} value={String(coord.id)}>
                            {displayName} ({coord.id})
                          </option>
                        );
                      })}
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                      Program <span className="text-red-600">*</span>
                    </label>
                    <select
                      className="w-full border rounded px-3 py-2"
                      value={sectionFormData.program_id}
                      onChange={(e) => setSectionFormData({...sectionFormData, program_id: e.target.value})}
                      required
                    >
                      <option value="">- Select Program -</option>
                      {listOfPrograms.map((prog) => (
                        <option key={String(prog.id)} value={String(prog.id)}>
                          {prog.name}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>

              {/* All Available Sections List */}
              <div>
                <Heading level={5} text="All Available Sections" className="mb-3 text-lg font-semibold" />
                {allSections.length > 0 ? (
                  <div className="max-h-96 overflow-y-auto border rounded">
                    <table className="w-full text-sm">
                      <thead className="bg-gray-50 sticky top-0">
                        <tr>
                          <th className="text-left px-3 py-2 font-semibold">Section Name</th>
                          <th className="text-left px-3 py-2 font-semibold">Coordinator</th>
                          <th className="text-left px-3 py-2 font-semibold">Program</th>
                          <th className="text-center px-3 py-2 font-semibold">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {allSections.map((section) => (
                          <tr key={String(section.id)} className="border-t hover:bg-gray-50">
                            <td className="px-3 py-2">
                              {editingSection?.id === section.id ? (
                                <input
                                  type="text"
                                  className="w-full border rounded px-2 py-1 text-sm"
                                  value={editSectionFormData.name}
                                  onChange={(e) => setEditSectionFormData({...editSectionFormData, name: e.target.value})}
                                />
                              ) : (
                                <span>{section.name}</span>
                              )}
                            </td>
                            <td className="px-3 py-2">
                              {editingSection?.id === section.id ? (
                                <select
                                  className="w-full border rounded px-2 py-1 text-sm"
                                  value={editSectionFormData.coordinator_id}
                                  onChange={(e) => setEditSectionFormData({...editSectionFormData, coordinator_id: e.target.value})}
                                >
                                  <option value="">- Select -</option>
                                  {(allCoordinators.length > 0 ? allCoordinators : rows).map((coord) => {
                                    // Use name if available, otherwise construct from first_name/last_name, fallback to ID
                                    const displayName = coord.name 
                                      || (coord.first_name || coord.last_name 
                                        ? `${coord.first_name || ''} ${coord.middle_name || ''} ${coord.last_name || ''}`.trim()
                                        : `Coordinator ${coord.id}`);
                                    return (
                                      <option key={String(coord.id)} value={String(coord.id)}>
                                        {displayName}
                                      </option>
                                    );
                                  })}
                                </select>
                              ) : (
                                <span>
                                  {(() => {
                                    // Use coordinator_name from API if available (preferred)
                                    if (section.coordinator_name) {
                                      return section.coordinator_name;
                                    }
                                    // Fallback: Try to find coordinator from allCoordinators first (has all coordinators), fallback to rows (current page)
                                    const coord = allCoordinators.find(c => String(c.id) === String(section.coordinator_id)) 
                                      || rows.find(c => String(c.id) === String(section.coordinator_id));
                                    return coord ? `${coord.first_name} ${coord.last_name}` : (section.coordinator_id || 'No Coordinator');
                                  })()}
                                </span>
                              )}
                            </td>
                            <td className="px-3 py-2">
                              {editingSection?.id === section.id ? (
                                <select
                                  className="w-full border rounded px-2 py-1 text-sm"
                                  value={editSectionFormData.program_id}
                                  onChange={(e) => setEditSectionFormData({...editSectionFormData, program_id: e.target.value})}
                                >
                                  <option value="">- Select -</option>
                                  {listOfPrograms.map((prog) => (
                                    <option key={String(prog.id)} value={String(prog.id)}>
                                      {prog.name}
                                    </option>
                                  ))}
                                </select>
                              ) : (
                                <span>
                                  {listOfPrograms.find(p => String(p.id) === String(section.program_id))?.name || section.program_id}
                                </span>
                              )}
                            </td>
                            <td className="px-3 py-2">
                              <div className="flex items-center justify-center gap-2">
                                {editingSection?.id === section.id ? (
                                  <>
                                    <Button
                                      onClick={() => updateSection()}
                                      className="px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs"
                                    >
                                      Save
                                    </Button>
                                    <Button
                                      onClick={() => {
                                        setEditingSection(null);
                                        setEditSectionFormData({ name: "", coordinator_id: "", program_id: "" });
                                      }}
                                      className="px-2 py-1 bg-gray-500 hover:bg-gray-600 text-white rounded text-xs"
                                    >
                                      Cancel
                                    </Button>
                                  </>
                                ) : (
                                  <>
                                    <Button
                                      onClick={() => handleEditSection(section)}
                                      className="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs"
                                    >
                                      Edit
                                    </Button>
                                    <Button
                                      onClick={() => handleDeleteSectionClick(section)}
                                      className="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs"
                                    >
                                      Delete
                                    </Button>
                                  </>
                                )}
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <Text className="text-sm text-gray-500">No sections available. Create your first section above.</Text>
                )}
              </div>
            </div>
          </FormModal>

          {/* Delete Section Confirm Modal */}
          <DeleteConfirmModal
            open={isDeleteSectionOpen}
            setOpen={setIsDeleteSectionOpen}
            title="Delete Section"
            message={`Are you sure you want to delete section "${sectionToDelete?.name}"?`}
            handleDelete={deleteSection}
          />

          {/* Bulk Assign Section Modal */}
          <FormModal
            isOpen={isBulkAssignSectionOpen}
            setIsOpen={setIsBulkAssignSectionOpen}
            modalTitle={`Assign Section to ${selectedCoordinatorIds.length} Coordinator(s)`}
            onSubmit={(e) => { e.preventDefault(); handleBulkAssignSection(); }}
          >
            <div className="space-y-4">
              <div>
                <Text className="text-sm text-gray-600 mb-3">
                  Selected coordinators: {selectedCoordinatorIds.map(id => {
                    const coord = rows.find(r => String(r.id) === String(id));
                    return coord ? `${coord.first_name} ${coord.last_name}` : id;
                  }).join(', ')}
                </Text>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">
                  Select Section Template (or enter custom name)
                </label>
                <select
                  className="w-full border rounded px-3 py-2 mb-2"
                  value={bulkAssignSectionId}
                  onChange={(e) => setBulkAssignSectionId(e.target.value)}
                >
                  <option value="">- Select Section Template -</option>
                  {allSections.map((sec) => (
                    <option key={String(sec.id)} value={String(sec.id)}>
                      {sec.name} (Coordinator: {sec.coordinator_id})
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">
                  Or Enter Custom Section Name Pattern
                </label>
                <input
                  type="text"
                  className="w-full border rounded px-3 py-2"
                  value={bulkAssignSectionName}
                  onChange={(e) => setBulkAssignSectionName(e.target.value)}
                  placeholder="e.g., BSIT 4A"
                />
                <Text className="text-xs text-gray-500 mt-1">
                  A section with this exact name will be created for each selected coordinator.
                </Text>
              </div>
            </div>
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
          <div className="flex items-center justify-between mb-3">
            <ManageHeader
              isOpen={isOpen}
              setIsOpen={setIsOpen}
              addPlaceholder="Add New Coordinator"
              showExportButton={false}
              showImportButton={true}
              isImportOpen={isOpenImport}
              setIsImportOpen={setIsOpenImport}
            />
            <div className="flex gap-2">
              {selectedCoordinatorIds.length > 0 && (
                <Button
                  onClick={() => setIsBulkAssignSectionOpen(true)}
                  className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-semibold"
                >
                  Assign Section ({selectedCoordinatorIds.length} selected)
                </Button>
              )}
              <Button
                onClick={() => setIsCreateSectionOpen(true)}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-semibold"
              >
                + Create Section
              </Button>
            </div>
          </div>

          <DynamicDataGrid
            searchPlaceholder={"Search Coordinator"}
            rows={rows}
            setRows={setRows}
            columns={columns}
            url={GET_API_ROUTE_PATH.coordinators}
            requestedBy={authorizeRole}
            onSelectionModelChange={handleRowSelectionChange}
            checkboxSelection={true}
            scrollable={true}
            scrollableHeight={600}
          />

          {/* Analytics below grid */}
          <div className="mt-6 bg-white border rounded-lg p-4" ref={analyticsRef}>
            <div className="flex items-center justify-between mb-3">
              <h4 className="text-lg font-semibold text-gray-800">Program Outcomes Coverage by Coordinator</h4>
              <div className="flex gap-2">
                <button onClick={loadAnalytics} className="px-3 py-2 bg-slate-700 text-white rounded hover:bg-slate-800">{analyticsLoading ? 'Loading…' : 'Load Analytics'}</button>
                {/* AI Insight feature removed */}
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

          {/* Create Section Modal */}
          <FormModal
            isOpen={isCreateSectionOpen}
            setIsOpen={setIsCreateSectionOpen}
            modalTitle="Manage Sections"
            onSubmit={(e) => { e.preventDefault(); createSection(); }}
          >
            <div className="space-y-6">
              {/* Create New Section Form */}
              <div className="border-b pb-4">
                <Heading level={5} text="Create New Section" className="mb-3 text-lg font-semibold" />
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                      Section Name <span className="text-red-600">*</span>
                    </label>
                    <input
                      type="text"
                      className="w-full border rounded px-3 py-2"
                      value={sectionFormData.name}
                      onChange={(e) => setSectionFormData({...sectionFormData, name: e.target.value})}
                      placeholder="e.g., BSIT 4A"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                      Coordinator <span className="text-red-600">*</span>
                    </label>
                    <select
                      className="w-full border rounded px-3 py-2"
                      value={sectionFormData.coordinator_id}
                      onChange={(e) => setSectionFormData({...sectionFormData, coordinator_id: e.target.value})}
                      required
                    >
                      <option value="">- Select Coordinator -</option>
                      {(allCoordinators.length > 0 ? allCoordinators : rows).map((coord) => {
                        // Use name if available, otherwise construct from first_name/last_name, fallback to ID
                        const displayName = coord.name 
                          || (coord.first_name || coord.last_name 
                            ? `${coord.first_name || ''} ${coord.middle_name || ''} ${coord.last_name || ''}`.trim()
                            : `Coordinator ${coord.id}`);
                        return (
                          <option key={String(coord.id)} value={String(coord.id)}>
                            {displayName} ({coord.id})
                          </option>
                        );
                      })}
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-1">
                      Program <span className="text-red-600">*</span>
                    </label>
                    <select
                      className="w-full border rounded px-3 py-2"
                      value={sectionFormData.program_id}
                      onChange={(e) => setSectionFormData({...sectionFormData, program_id: e.target.value})}
                      required
                    >
                      <option value="">- Select Program -</option>
                      {listOfPrograms.map((prog) => (
                        <option key={String(prog.id)} value={String(prog.id)}>
                          {prog.name}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>

              {/* All Available Sections List */}
              <div>
                <Heading level={5} text="All Available Sections" className="mb-3 text-lg font-semibold" />
                {allSections.length > 0 ? (
                  <div className="max-h-96 overflow-y-auto border rounded">
                    <table className="w-full text-sm">
                      <thead className="bg-gray-50 sticky top-0">
                        <tr>
                          <th className="text-left px-3 py-2 font-semibold">Section Name</th>
                          <th className="text-left px-3 py-2 font-semibold">Coordinator</th>
                          <th className="text-left px-3 py-2 font-semibold">Program</th>
                          <th className="text-center px-3 py-2 font-semibold">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {allSections.map((section) => (
                          <tr key={String(section.id)} className="border-t hover:bg-gray-50">
                            <td className="px-3 py-2">
                              {editingSection?.id === section.id ? (
                                <input
                                  type="text"
                                  className="w-full border rounded px-2 py-1 text-sm"
                                  value={editSectionFormData.name}
                                  onChange={(e) => setEditSectionFormData({...editSectionFormData, name: e.target.value})}
                                />
                              ) : (
                                <span>{section.name}</span>
                              )}
                            </td>
                            <td className="px-3 py-2">
                              {editingSection?.id === section.id ? (
                                <select
                                  className="w-full border rounded px-2 py-1 text-sm"
                                  value={editSectionFormData.coordinator_id}
                                  onChange={(e) => setEditSectionFormData({...editSectionFormData, coordinator_id: e.target.value})}
                                >
                                  <option value="">- Select -</option>
                                  {(allCoordinators.length > 0 ? allCoordinators : rows).map((coord) => {
                                    // Use name if available, otherwise construct from first_name/last_name, fallback to ID
                                    const displayName = coord.name 
                                      || (coord.first_name || coord.last_name 
                                        ? `${coord.first_name || ''} ${coord.middle_name || ''} ${coord.last_name || ''}`.trim()
                                        : `Coordinator ${coord.id}`);
                                    return (
                                      <option key={String(coord.id)} value={String(coord.id)}>
                                        {displayName}
                                      </option>
                                    );
                                  })}
                                </select>
                              ) : (
                                <span>
                                  {(() => {
                                    // Use coordinator_name from API if available (preferred)
                                    if (section.coordinator_name) {
                                      return section.coordinator_name;
                                    }
                                    // Fallback: Try to find coordinator from allCoordinators first (has all coordinators), fallback to rows (current page)
                                    const coord = allCoordinators.find(c => String(c.id) === String(section.coordinator_id)) 
                                      || rows.find(c => String(c.id) === String(section.coordinator_id));
                                    return coord ? `${coord.first_name} ${coord.last_name}` : (section.coordinator_id || 'No Coordinator');
                                  })()}
                                </span>
                              )}
                            </td>
                            <td className="px-3 py-2">
                              {editingSection?.id === section.id ? (
                                <select
                                  className="w-full border rounded px-2 py-1 text-sm"
                                  value={editSectionFormData.program_id}
                                  onChange={(e) => setEditSectionFormData({...editSectionFormData, program_id: e.target.value})}
                                >
                                  <option value="">- Select -</option>
                                  {listOfPrograms.map((prog) => (
                                    <option key={String(prog.id)} value={String(prog.id)}>
                                      {prog.name}
                                    </option>
                                  ))}
                                </select>
                              ) : (
                                <span>
                                  {listOfPrograms.find(p => String(p.id) === String(section.program_id))?.name || section.program_id}
                                </span>
                              )}
                            </td>
                            <td className="px-3 py-2">
                              <div className="flex items-center justify-center gap-2">
                                {editingSection?.id === section.id ? (
                                  <>
                                    <Button
                                      onClick={() => updateSection()}
                                      className="px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs"
                                    >
                                      Save
                                    </Button>
                                    <Button
                                      onClick={() => {
                                        setEditingSection(null);
                                        setEditSectionFormData({ name: "", coordinator_id: "", program_id: "" });
                                      }}
                                      className="px-2 py-1 bg-gray-500 hover:bg-gray-600 text-white rounded text-xs"
                                    >
                                      Cancel
                                    </Button>
                                  </>
                                ) : (
                                  <>
                                    <Button
                                      onClick={() => handleEditSection(section)}
                                      className="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs"
                                    >
                                      Edit
                                    </Button>
                                    <Button
                                      onClick={() => handleDeleteSectionClick(section)}
                                      className="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs"
                                    >
                                      Delete
                                    </Button>
                                  </>
                                )}
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : (
                  <Text className="text-sm text-gray-500">No sections available. Create your first section above.</Text>
                )}
              </div>
            </div>
          </FormModal>

          {/* Delete Section Confirm Modal */}
          <DeleteConfirmModal
            open={isDeleteSectionOpen}
            setOpen={setIsDeleteSectionOpen}
            title="Delete Section"
            message={`Are you sure you want to delete section "${sectionToDelete?.name}"?`}
            handleDelete={deleteSection}
          />

          {/* Bulk Assign Section Modal */}
          <FormModal
            isOpen={isBulkAssignSectionOpen}
            setIsOpen={setIsBulkAssignSectionOpen}
            modalTitle={`Assign Section to ${selectedCoordinatorIds.length} Coordinator(s)`}
            onSubmit={(e) => { e.preventDefault(); handleBulkAssignSection(); }}
          >
            <div className="space-y-4">
              <div>
                <Text className="text-sm text-gray-600 mb-3">
                  Selected coordinators: {selectedCoordinatorIds.map(id => {
                    const coord = rows.find(r => String(r.id) === String(id));
                    return coord ? `${coord.first_name} ${coord.last_name}` : id;
                  }).join(', ')}
                </Text>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">
                  Select Section Template (or enter custom name)
                </label>
                <select
                  className="w-full border rounded px-3 py-2 mb-2"
                  value={bulkAssignSectionId}
                  onChange={(e) => setBulkAssignSectionId(e.target.value)}
                >
                  <option value="">- Select Section Template -</option>
                  {allSections.map((sec) => (
                    <option key={String(sec.id)} value={String(sec.id)}>
                      {sec.name} (Coordinator: {sec.coordinator_id})
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">
                  Or Enter Custom Section Name Pattern
                </label>
                <input
                  type="text"
                  className="w-full border rounded px-3 py-2"
                  value={bulkAssignSectionName}
                  onChange={(e) => setBulkAssignSectionName(e.target.value)}
                  placeholder="e.g., BSIT 4A"
                />
                <Text className="text-xs text-gray-500 mt-1">
                  A section with this exact name will be created for each selected coordinator.
                </Text>
              </div>
            </div>
          </FormModal>
        </div>
      </Page>
    );
  }
};

export default ViewCoordinatorsPage;
