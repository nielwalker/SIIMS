<?php

namespace Database\Seeders;

use App\Models\TimeRecordStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimeRecordStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Generate Time Record Statuses
         */
        TimeRecordStatus::insert(
            [
                [
                    "name" => 'In',
                    "description" => "This status indicates the employee has started working or has arrived at work for the day. It's typically used to mark the time the employee starts their shift.",
                ],
                [
                    "name" => "Out",
                    "description" => "This status marks the time when the employee leaves work or takes a break. It could refer to clocking out at the end of the workday or for a scheduled break.",
                ],
                [
                    "name" => "On Break",
                    "description" => "Used to indicate that the employee is on a scheduled break (e.g., lunch break). It shows the time when they started the break.",
                ],
                [
                    "name" => "Off",
                    "description" => "This status may indicate that the employee is off work for the day or not working, such as for a non-working day, holiday, or leave."
                ],
                [
                    "name" => "Overtime",
                    "description" => "This status is used when an employee works beyond their regular working hours. Overtime hours are often marked separately from regular work hours for payroll purposes."
                ],
                [
                    "name" => "Leave",
                    "description" => "Indicates the employee is on a leave of absence, such as sick leave, vacation, or personal leave. This status may be further broken down depending on the type of leave taken."
                ],
                [
                    "name" => "Absent",
                    "description" => "Used when the employee is scheduled to work but does not show up without prior notice or approval."
                ],
                [
                    "name" => "Holiday",
                    "description" => "Used to indicate that the employee is on a holiday, either a public holiday or a company-designated holiday, and therefore not required to work."
                ],
                [
                    "name" => "Late",
                    "description" => "This status is used if the employee arrives late to work, often noted when the time exceeds the usual starting hour."
                ],
                [
                    "name" => "Early Out",
                    "description" => "Used when an employee leaves before their scheduled shift end time, often for personal reasons or work requirements."
                ],
            ]
        );
    }
}
