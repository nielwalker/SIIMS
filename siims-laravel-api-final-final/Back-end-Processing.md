# Modules

## Status Types:

-   **Rejected:** (The task or implementation was dismissed or deemed unnecessary.)

-   **Abandoned:** (Work on the task was stopped and will not be continued.)

-   **Testing:** (The implementation is currently being tested for functionality, performance, and quality.)

-   **Ongoing:** (The task is actively being worked on and has not yet been completed.)

-   **Done:** (The task or implementation has been finished and closed.)

-   **In Progress:** (Code is actively being worked on, but not yet finished.)

-   **Ready for Review:** (Code has been implemented and is waiting for peer review or approval.)

-   **Blocked:** (Progress is halted due to an external dependency or issue that needs resolution.)

-   **On Hold:** (Development is paused temporarily, often due to prioritization or external factors.)

-   **Under Investigation:** (The issue or task requires further exploration or research to determine the next steps.)

-   **Waiting for Feedback:** (Code or implementation is complete, but feedback from stakeholders or team members is needed.)

-   **Fixed:** (A bug or issue has been resolved and the solution has been implemented.)

-   **Verified:** (The implementation or bug fix has been tested and validated.)

-   **Scheduled:** (The task or feature is planned and scheduled for a future sprint or release.)

-   **In Review:** (Code is undergoing a formal review process, such as code review or quality assurance (QA).)

-   **Reopened:** (A previously closed issue or task has been reopened for further work.)

-   **Reworked:** (The code or implementation has been significantly modified or rewritten based on feedback or new requirements.)

-   **Completed:** (The implementation or task is finished and has been deployed or finalized.)

-   **Deployed:** (Code has been successfully deployed to production or a staging environment.)

-   **Not Planned:** (The task or feature is currently not part of the roadmap or backlog.)

## Role Management (Admin)

-   Admin can add a new role (Done)
-   Admin can view a list of roles (Done)
-   Admin can view the user with roles (Done)

## College Management (Admin)

-   Admin adds a new record of college. (Done)
-   Admin updates new record of college by ID. (Done)
-   Admin can view the list of colleges. (Done)
-   The college can only have one dean only. (Done)

## Application Module

-   Student views the list of jobs
-   Student view a job
-   Student attempts to apply in the job.

## Program Management (Admin, Dean)

-   The system will not allow the users to create a new program without placing it in a college.
-   Admin adds a new record of program.
-   Admin updates a record of program by ID.
-   Admin can view the list of programs and their college belong to.

## Company Management Module (Admin, Dean, Chairperson)

### Company Management Module (Admin)

-   Admin adds a new record of company. (Done)
-   Admin adds a list of companies by CSV or Excel Files. (On Hold)
-   Admin updates a record of company. (Done)
-   Admin archive a record of company. (In Progress)
-   Admin can archive multiple records of company.
-   Admin deletes a record of company.
-   Admin views all the list of companies.

### Company Management Module (Dean)

-   Dean adds a new record of company.
-   Dean adds a list of companies by CSV or Excel Files.
-   Dean views all the list of companies.

### Company Management Module (Chairperson)

-   Chairperson adds a new record of company.
-   Chairperson adds a list of companies by CSV or Excel Files.
-   Chairperson views all the list of companies.

## Coordinator & Chairperson

-   Chairperson assigns a student to their coordinator by ID.
-   Chairperson can view the list of coordinators in their assigned program only.
-   Coordinator can view their list of student.

## Application Processing

-   Company creates a new record of office.
-   Company can update his/her office.
-   Company can view the list of offices.
-   Company assigns a supervisor in the office.
-   Company can create a new record of job post.
-   Company can select either immersion or internship upon creating a job post.
-   Supervisor can create a new record of job post.
-   Supervisor can select either immersion or internship upon creating a job post.
-   Chairperson can view the list of job post.
-   Coordinator can view the list of job post.
-   Student can view the list of job posts.
-   Student can view the job post.
-   Student can apply job post. By applying the job post the isA

### First

### Recommendations

-
-
-
-
-
-

### Weekly Accomplishment Report Module

### Daily Time Record Module

### Work Experience Module

-   Student adds new record of work_experience. (Testing)
-   Student updates a record of work_experience by id. (Testing)
-   Student deletes a record of work_experience by id. (Testing)
-   Student views a lists of work_experience by date. (Testing)

### Education Module

-   Student adds new record of education. (Testing)
-   Student updates a record of education by id. (Testing)
-   Student deletes a record of education by id. (Testing)
-   Student views a lists of education by date. (Testing)

### Skill Module (Student)

-   Student adds a new record of skill.
-   Student updates a record of skill by skill_id.
-   Student deletes a record of skill by skill_id.
-   Student views a list of skills including the name of the skill.
-   Student adds a new record of application by work_id as a reference if the student applies. During creating a new record of application, the status_type_id is defined to 1 which mean it is pending.
-   Student stores a new application record if he/she applies to a work. (Done)
-   Student's isPending is updated into true and last_applied_at. (Done)
-   Student stores a new endorsement request base on the job post he/she applies.
-   Student can also include other of his/her classmate in the endorsement request based on the program he/she enrolled.
-   Other student who is part of the endorsement request can be inluded in the isPending to true.
-   Chairperson can view all endorsement letter requests based on the program he/she assigned.
-   Chairperson updates the endorsement letter request
-   If the student has not been approved by the company by 3 days it will update the isPending to false and he/she can continue apply another job.

-   Company views all applicants.
-   Company views all applicants base on the work post.
-   Company stores new acceptance letter and the endorsement letter is mark as approved.
