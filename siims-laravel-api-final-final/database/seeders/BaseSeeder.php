<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    /**
     * The User ID's instance.
     */
    // ! User ID & Password
    protected $ADMIN_ID = 2015301502;
    protected $ADMIN_PASSWORD = "password";

    protected $COMPANY_ID = 2023301502;
    protected $COMPANY_PASSWORD = "password";
    protected $STUDENT_ID = 2024301502;
    protected $STUDENT_PASSWORD = "password";
    protected $DEAN_ID = 2020301502;
    protected $DEAN_PASSWORD = "password123";
    protected $CHAIRPERSON_ID = 2019301502;
    // protected $CHAIRPERSON_ID_2 = 201834;
    protected $CHAIRPERSON_PASSWORD = "securePassword456";
   //  protected $CHAIRPERSON_PASSWORD_2 = "newPassword";
    protected $COORDINATOR_ID = 20193471861;
    protected $COORDINATOR_PASSWORD = "coorPassword";
    protected $SUPERVISOR_ID = 2018301502;
    protected $SUPERVISOR_PASSWORD = "supervisor123";
    protected $OSA_ID = 2024516023;
    protected $OSA_PASSWORD = "osaPassword123";

    /**
     * The User IDs to exclude.
     */
    public $excludeUserIds;

    /**
     * Constructor to initialize the excluded user IDs.
     */
    public function __construct()
    {
        $this->excludeUserIds = [
            $this->ADMIN_ID,
            $this->COMPANY_ID,
            $this->STUDENT_ID,
            $this->DEAN_ID,
            $this->CHAIRPERSON_ID,
            $this->COORDINATOR_ID,
            $this->SUPERVISOR_ID,
            $this->OSA_ID,
        ];
    }
}
