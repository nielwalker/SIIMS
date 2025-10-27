import { GraduationCap, Phone, UserCircle2 } from "lucide-react";
import React from "react";
import Text from "../../../../components/common/Text";
import { getFullAddress } from "../../../../utils/formatAddress";
import { getFullName } from "../../../../utils/formatName";

const safe = (v, d = "") => (v === undefined || v === null ? d : v);

const StudentHeadProfileInfo = ({ profile = {} }) => {
  const student = safe(profile.student, {});
  const program = safe(student.program, {});
  const college = safe(program.college, {});
  const coordinator = safe(student.coordinator, {});
  const coordUser = safe(coordinator.user, {});
  return (
    <div className="col-span-3 no-page-break">
      <div>
        <div className="grid grid-cols-9 gap-5">
          <div className="col-span-3">
            <div className="flex items-center gap-3">
              <GraduationCap size={25} />
              <Text className="text-lg font-semibold">Current Education</Text>
            </div>

            {/* Current Educations */}
            <div className="flex flex-col gap-2 mt-2">
              <div className="flex flex-col">
                <Text className="text-sm font-bold">College</Text>
                <Text className="text-sm">{college.name || "College of Science"}</Text>
              </div>

              {/* Program */}
              <div className="flex flex-col">
                <Text className="text-sm font-bold">Program</Text>

                <Text className="text-sm">{program.name || "No Program"}</Text>
              </div>
            </div>
          </div>

          <div className="col-span-3">
            <div className="flex items-center gap-3">
              <Phone size={25} />
              <Text className="text-lg font-semibold">Contact</Text>
            </div>

            {/* Current Email */}
            <div className="flex flex-col gap-2 mt-2">
              <div className="flex flex-col">
                <Text className="text-sm font-bold">Email</Text>
                <Text className="text-sm">
                  <a
                    href={`mailto:${safe(profile.email, "")}`}
                    className="text-blue-500 hover:underline"
                  >
                    {safe(profile.email, "No email")}
                  </a>
                </Text>
              </div>

              {/* Phone Number */}
              <div className="flex flex-col">
                <Text className="text-sm font-bold">Contact no.</Text>

                <Text className="text-sm">{safe(profile.phone_number, "—")}</Text>
              </div>

              {/* Address */}
              <div className="flex flex-col">
                <Text className="text-sm font-bold">Address</Text>

                <Text className="text-sm">
                  {getFullAddress({
                    street: safe(profile.street, ""),
                    barangay: safe(profile.barangay, ""),
                    city: safe(profile.city_municipality, ""),
                    province: safe(profile.province, ""),
                    postalCode: safe(profile.postal_code, ""),
                  })}
                </Text>
              </div>
            </div>
          </div>

          <div className="col-span-3">
            <div className="flex items-center gap-3">
              <UserCircle2 size={25} />
              <Text className="text-lg font-semibold">Coordinator</Text>
            </div>

            {/* Coordinator Name */}
            <div className="flex flex-col">
              <Text className="text-sm font-bold">Name</Text>

              <Text className="text-sm">
                {getFullName(
                  coordUser.first_name,
                  coordUser.middle_name,
                  coordUser.last_name
                ) || "No Coordinator"}
              </Text>
            </div>

            {/* Coordinator Email */}
            <div className="flex flex-col">
              <Text className="text-sm font-bold">Email</Text>

              <Text className="text-sm">
                <a
                  href={`mailto:${safe(coordUser.email, "")}`}
                  className="text-blue-500 hover:underline"
                >
                  {safe(coordUser.email, "—")}
                </a>
              </Text>
            </div>

            {/* Coordinator Phone Number */}
            {coordUser.phone_number && (
              <div className="flex flex-col">
                <Text className="text-sm font-bold">Contact No.</Text>

                <Text className="text-sm">{coordUser.phone_number}</Text>
              </div>
            )}
          </div>
        </div>

        {/*  Border Line */}
        <div className="border-b-2 border-b-gray-900 w-full h-1 my-3"></div>
      </div>
    </div>
  );
};

export default StudentHeadProfileInfo;
