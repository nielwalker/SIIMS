import React from "react";
import Heading from "../common/Heading";
import Text from "../common/Text";
import FormField from "../common/FormField";
import LoginFields from "../fields/LoginFields";
import BasicInformationFields from "./fields/BasicInformationFields";
import { Select } from "@headlessui/react";
import LoginInfoFields from "./fields/LoginInfoFields";
import PersonalInfoFields from "./fields/PersonalInfoFields";
import AddressInfoFields from "./fields/AddressInfoFields";
import { loginInfo } from "../../formDefaults/loginInfo";
import { personalInfo } from "../../formDefaults/personalInfo";
import { addressInfo } from "../../formDefaults/addressInfo";

const CoordinatorForm = ({
  authorizeRole,
  method = "post",
  coordinatorInfo = {
    ...loginInfo,
    ...personalInfo,
    ...addressInfo,
    program_id: "",
  },
  handleCoordinatorInfoChange,
  requiredFields = {
    id: true,
    password: true,
    first_name: true,
    middle_name: false,
    last_name: false,
    email: true,
    gender: false,
    phone_number: false,
    street: false,
    barangay: false,
    city_municipality: false,
    province: false,
    postal_code: false,
    program_id: true,
  },
  programs = [],
  errors = {},
}) => {
  return (
    <>
      <div className="space-y-3">
        {method !== "put" && (
          <LoginInfoFields
            info={coordinatorInfo}
            handleInfoChange={handleCoordinatorInfoChange}
            requiredFields={requiredFields}
            errors={errors}
          />
        )}

        <PersonalInfoFields
          personalInfo={coordinatorInfo}
          handlePersonalInfoChange={handleCoordinatorInfoChange}
          requiredFields={requiredFields}
          errors={errors}
        />

        <AddressInfoFields
          addressInfo={coordinatorInfo}
          handleAddressInfoChange={handleCoordinatorInfoChange}
          errors={errors}
        />

        <div>
            <Heading
              level={5}
              color="black"
              text={"Coordinator Information"}
              className="border-l-2 rounded-lg border-blue-700 px-3 font-bold text-md"
            />
            <div className="flex flex-col">
              <div className="grid grid-cols-3 gap-2 mt-4">
                {/* Coordinator Program Assign */}
                <div>
                  <FormField
                    label={"Program Assign"}
                    name={"program_id"}
                    labelClassName="text-sm text-black font-semibold"
                    required={requiredFields["program_id"]}
                  >
                    <Select
                      name="program_id"
                      className="border data-[hover]:shadow data-[focus]:bg-blue-100 h-full outline-none p-2"
                      aria-label="Select program"
                      onChange={handleCoordinatorInfoChange}
                      value={coordinatorInfo.program_id}
                      required={requiredFields["program_id"]}
                    >
                      <option value="">-Select a Program-</option>
                      {programs.map((program) => {
                        return (
                          <option key={program.id} value={program.id}>
                            {program.name}
                          </option>
                        );
                      })}
                    </Select>
                  </FormField>
                  {errors.program_id && (
                    <Text className="text-red-500">{errors.program_id[0]}</Text>
                  )}
                </div>
              </div>
            </div>
        </div>
      </div>

      {/*  <div>
      <div className="flex flex-col space-y-3">
        <LoginFields
          id={id}
          password={password}
          setId={setId}
          setPassword={setPassword}
        />
        <BasicInformationFields
          firstName={firstName}
          middleName={middleName}
          lastName={lastName}
          email={email}
          phone_number={phone_number}
          setFirstName={setFirstName}
          setMiddleName={setMiddleName}
          setLastName={setLastName}
          setEmail={setEmail}
          setPhoneNumber={setPhoneNumber}
          requiredFields={requiredFields}
          errors={errors}
        />
        <div className="mt-6">
          <Heading
            level={5}
            color="black"
            text={"Program Assign"}
            className="border-l-2 rounded-lg border-blue-700 px-3 font-bold text-md"
          />

          <div className="mt-4">
            <FormField
              label={"Coordinator Assign"}
              name={"program_id"}
              labelClassName="text-sm text-black font-semibold"
              required={requiredFields["program_id"]}
            >
              <Select
                typeof="text"
                className="outline-none text-black rounded-sm p-2 text-sm"
                name="program_id"
                onChange={(e) => {
                  setProgramId(e.target.value);
                }}
                value={program_id}
                required={requiredFields["program_id"]}
              >
                <option value="">-Program Assign-</option>
                {programs.map((program) => {
                  return (
                    <option key={program.id} value={program.id}>
                      {program.name} | {program.program}
                    </option>
                  );
                })}
              </Select>
              {errors.program_id && (
                <Text className="text-red-500">{errors.program_id[0]}</Text>
              )}
            </FormField>
          </div>
        </div>
      </div>
    </div> */}
    </>
  );
};

export default CoordinatorForm;
