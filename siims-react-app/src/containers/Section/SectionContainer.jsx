import React, { useCallback, useEffect, useMemo, useState } from "react";
import Loader from "../../components/common/Loader";
import SectionPresenter from "./SectionPresenter";
import {
  addSection,
  assignStudentToSection,
  fetchData,
  getSection,
} from "./Api";
import { getRequest } from "../../api/apiHelpers";
import {
  getStudentActionColumns,
  getStudentStaticColumns,
} from "./utilities/studentColumns";
import useDebouncedSearch from "../../hooks/useDebouncedSearch";
import useForm from "../../hooks/useForm";

const SectionContainer = ({ authorizeRole }) => {
  /**
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
   * USE HOOK
   *
   */
  const {
    formData: sectionFormData,
    handleInputChange: sectionHandleInputChange,
  } = useForm({
    name: "",
    limit: 0,
    class_list: null,
    coordinator_id: 0,
  });

  /**
   *
   * ROW STATE
   *
   *
   */
  const [rows, setRows] = useState([]);

  /**
   *
   *
   * MODAL STATE
   *
   *
   */
  const [isOpenSection, setIsOpenSection] = useState(false);
  const [isSectionOpen, setIsSectionOpen] = useState(false);
  const [isSectionAssignOpen, setIsSectionAssignOpen] = useState(false);
  const [isStudentModalOpen, setIsStudentModalOpen] = useState(false);

  /**
   *
   *
   * SELECT STATE
   *
   *
   */
  const [selectedSection, setSelectedSection] = useState({ id: null });
  const [selectedRows, setSelectedRows] = useState([]);
  const [selectedSectionID, setSelectedSectionID] = useState("");
  const [selectedStudent, setSelectedStudent] = useState({});
  const [allSectionsForAssign, setAllSectionsForAssign] = useState([]);

  /**
   *
   *
   * SELECTION FUNCTIONS
   *
   *
   */
  const handleSelectionChange = (selection) => {
    setSelectedRows(selection);
  };

  /**
   *
   *
   * LIST STATE
   *
   *
   */
  const [sections, setSections] = useState([]);
  const [coordinators, setCoordinators] = useState([]);

  /**
   *
   *
   * SEARCH STATE
   *
   *
   */
  const [searchSection, setSearchSection] = useState("");

  /**
   *
   *
   * DATAGRID STATE
   *
   *
   *
   */
  const [totalCount, setTotalCount] = useState(0);
  const [dataGridLoading, setDataGridLoading] = useState(false);
  const [searchInput, setSearchInput] = useState("");
  const [searchTerm, setSearchTerm] = useState("");
  const [paginationModel, setPaginationModel] = useState({
    page: 0, // Current page
    pageSize: 5, // Items per page
  });

  // Use debounced search term to avoid sending request on every keystroke
  const debouncedSearchTerm = useDebouncedSearch(searchInput, 500); // 500ms debounce delay

  /**
   *
   *
   * DATAGRID FUNCTION STATE
   *
   *
   */
  // Handle pagination model change
  const handlePaginationModelChange = (newPaginationModel) => {
    setPaginationModel(newPaginationModel); // Update pagination model (page and pageSize)
  };

  // Handle input field change
  const handleSearchInputChange = useCallback((event) => {
    const value = event.target.value;
    setSearchInput(value);

    if (value === "") {
      // Reload data if input is cleared
      setSearchTerm("");
      setPaginationModel({ ...paginationModel, page: 0 });
    }
  });

  // Trigger search only on Enter key press
  const handleSearchKeyDown = (event) => {
    // console.log(event.key);

    if (event.key === "Enter") {
      setSearchTerm(debouncedSearchTerm); // Use the input value for fetching data
      setPaginationModel({ ...paginationModel, page: 0 }); // Reset to first page
    }
  };

  /**
   *
   *
   * MODAL FUNCTIONS
   *
   *
   */
  const openSectionAssignModal = async () => {
    // Fetch all sections for the assign modal (no limit, no search filter, no coordinator filter)
    try {
      const response = await getRequest({
        url: "/api/v1/sections",
        params: {
          requestedBy: authorizeRole,
          getAll: 'true', // Flag to get all sections (pass as string for query param)
        },
      });
      
      console.log("Fetched sections for assign modal - raw response:", response);
      
      // Handle different response formats
      // Laravel Resource Collection when passed to jsonResponse returns the collection directly
      // getRequest returns res.data, so response will be the collection array directly
      let sectionsList = [];
      
      // Check if response is directly an array (most likely for Resource Collection)
      if (Array.isArray(response)) {
        sectionsList = response;
      } 
      // Check if response has a data property with array (if wrapped in another object)
      else if (response && response.data && Array.isArray(response.data)) {
        sectionsList = response.data;
      }
      // Check nested data.data structure (if double-wrapped)
      else if (response && response.data && response.data.data && Array.isArray(response.data.data)) {
        sectionsList = response.data.data;
      }
      // Try to find any array in the response object
      else if (response && typeof response === 'object') {
        const foundArray = Object.values(response).find(v => Array.isArray(v));
        if (foundArray) {
          sectionsList = foundArray;
        }
      }
      
      console.log("Extracted sections list:", sectionsList, "Count:", sectionsList.length);
      if (sectionsList.length > 0) {
        console.log("Sample sections:", sectionsList.slice(0, 3).map(s => ({ id: s.id, name: s.name })));
        // Check specifically for 4R3
        const has4R3 = sectionsList.some(s => s.name === '4R3');
        console.log("Does list include 4R3?", has4R3);
        if (!has4R3) {
          console.warn("WARNING: Section 4R3 is missing from the list!");
          console.log("All section names:", sectionsList.map(s => s.name).sort());
        }
      }
      
      if (sectionsList.length > 0) {
        setAllSectionsForAssign(sectionsList);
      } else {
        // Fallback to existing sections if API doesn't return data
        console.warn("No sections returned from API, using existing sections");
        setAllSectionsForAssign(sections.filter(s => s.id !== null)); // Filter out "All Sections" option
      }
    } catch (error) {
      console.error("Error fetching all sections for assign:", error);
      // Fallback to existing sections
      setAllSectionsForAssign(sections.filter(s => s.id !== null));
    }
    
    setIsSectionAssignOpen(true);
  };
  const openStudentInfoModal = (data) => {
    // console.log(data);

    setSelectedStudent(data);
    setIsStudentModalOpen(true);
  };

  /**
   *
   *
   * FETCHING
   *
   *
   */

  useEffect(() => {
    fetchSections();
    fetchCoordinators();
  }, []);

  useEffect(() => {
    fetchStudents();
  }, [paginationModel, searchTerm, selectedSection]);

  /**
   *
   *
   * API FUNCTIONS
   *
   *
   */

  const assignSection = async (e) => {
    // const selected
    e.preventDefault();

    const selectedData = rows.filter((row) => selectedRows.includes(row.id));

    // console.log(selectedData);

    // Format
    const payload = {
      student_ids: selectedData.map((data) => ({
        student_id: data.id, // Change `id` to `student_id`
      })),
      section_id: selectedSectionID,
    };

    // console.log(selectedSectionID);

    await assignStudentToSection({
      sectionID: selectedSectionID,
      setLoading: setLoading,
      payload: payload,
      setRows: setRows,
      setIsSectionAssignOpen: setIsSectionAssignOpen,
    });
  };

  const addNewSection = async (e) => {
    e.preventDefault();

    // console.log(sectionFormData);
    await addSection({
      setLoading: setLoading,
      setSections: setSections,
      authorizeRole: authorizeRole,
      payload: sectionFormData,
      setIsOpenSection: setIsOpenSection,
    });
  };

  const fetchStudents = async () => {
    await fetchData({
      requestedBy: authorizeRole,
      setLoading: setDataGridLoading,
      paginationModel: paginationModel,
      setRows: setRows,
      setTotalCount: setTotalCount,
      searchTerm: searchTerm,
      sectionID: selectedSection["id"],
    });
  };

  const fetchSections = async () => {
    await getSection({
      authorizeRole: authorizeRole,
      searchTerm: searchSection,
      setLoading: setLoading,
      setSelectedSection: setSelectedSection,
      setSections: setSections,
    });
  };

  const fetchCoordinators = async () => {
    try {
      const resp = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/v1/users/coordinators`, {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${JSON.parse(localStorage.getItem('ACCESS_TOKEN'))}`,
        },
        credentials: 'include',
      });
      const payload = await resp.json().catch(() => []);
      const list = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
      
      console.log('SectionContainer: Fetched coordinators:', list);
      
      const normalized = list.map((c) => {
        const id = String(c.id ?? c.user_id ?? c.coordinator_id ?? '');
        // Try to get name from API response - prioritize name field, then construct from parts
        const apiName = c.name ?? c.fullName ?? '';
        const first = c.first_name ?? c.firstName ?? c.user?.first_name ?? '';
        const middle = c.middle_name ?? c.middleName ?? c.user?.middle_name ?? '';
        const last = c.last_name ?? c.lastName ?? c.user?.last_name ?? '';
        
        // Build display name: prefer API name, then construct from parts
        let displayName = '';
        if (apiName && apiName.trim()) {
          displayName = apiName.trim();
        } else if (first || last) {
          displayName = `${first} ${middle} ${last}`.trim();
        }
        
        console.log(`SectionContainer: Coordinator ${id}: apiName="${apiName}", displayName="${displayName}"`);
        
        return {
          id: id,
          name: displayName, // Set name field (may be empty)
          first_name: first || undefined,
          middle_name: middle || undefined,
          last_name: last || undefined,
        };
      }).filter((c) => c.id != null);
      
      console.log('SectionContainer: Normalized coordinators:', normalized);
      setCoordinators(normalized);
    } catch (error) {
      console.error('SectionContainer: Error fetching coordinators:', error);
      setCoordinators([]);
    }
  };

  /**
   *
   * COLUMNS
   *
   */

  const staticColumns = useMemo(
    () =>
      getStudentStaticColumns({
        authorizeRole: authorizeRole,
        selectedSection: selectedSection,
        openStudentInfoModal: openStudentInfoModal,
      }),
    [authorizeRole, selectedSection]
  );

  const actionColumn = useMemo(
    () =>
      getStudentActionColumns({
        authorizeRole,
      }),
    [authorizeRole]
  );

  const columns = useMemo(
    () => [...staticColumns, actionColumn],
    [staticColumns, actionColumn]
  );

  return (
    <div>
      <Loader loading={loading} />
      <SectionPresenter
        authorizeRole={authorizeRole}
        sections={sections}
        selectedSection={selectedSection}
        setSelectedSection={setSelectedSection}
        isSectionOpen={isSectionOpen}
        setIsSectionOpen={setIsSectionOpen}
        searchSection={searchSection}
        setSearchSection={setSearchSection}
        fetchSections={fetchSections}
        isOpenSection={isOpenSection}
        setIsOpenSection={setIsOpenSection}
        rows={rows}
        setRows={setRows}
        columns={columns}
        /* Data Grid Props */
        paginationModel={paginationModel}
        totalCount={totalCount}
        searchInput={searchInput}
        handleSearchInputChange={handleSearchInputChange}
        handleSearchKeyDown={handleSearchKeyDown}
        dataGridLoading={dataGridLoading}
        handlePaginationModelChange={handlePaginationModelChange}
        onRowSelectionModelChange={handleSelectionChange}
        /* Section Form Data */
        sectionFormData={sectionFormData}
        sectionHandleInputChange={sectionHandleInputChange}
        addNewSection={addNewSection}
        /** Assign Section Props */
        isSectionAssignOpen={isSectionAssignOpen}
        openSectionAssignModal={openSectionAssignModal}
        setIsSectionAssignOpen={setIsSectionAssignOpen}
        selectedRows={selectedRows}
        selectedSectionID={selectedSectionID}
        setSelectedSectionID={setSelectedSectionID}
        assignSection={assignSection}
        allSectionsForAssign={allSectionsForAssign}
        /** Student Info Modal */
        isStudentModalOpen={isStudentModalOpen}
        setIsStudentModalOpen={setIsStudentModalOpen}
        selectedStudent={selectedStudent}
        /** Testing Props */
        // printSelectedRows={printSelectedRows}
      />
    </div>
  );
};

export default SectionContainer;
