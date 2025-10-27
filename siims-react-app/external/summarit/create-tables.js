const { PrismaClient } = require('@prisma/client');

async function createTables() {
  const prisma = new PrismaClient();

  try {
    console.log('🔧 Creating database tables in Supabase...\n');

    // Create tables using raw SQL
    console.log('Creating StudentEnrollment table...');
    await prisma.$executeRaw`
      CREATE TABLE IF NOT EXISTS "StudentEnrollment" (
        "studentId" TEXT NOT NULL PRIMARY KEY,
        "userName" TEXT NOT NULL,
        "section" TEXT NOT NULL,
        "companyName" TEXT,
        "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        "updatedAt" TIMESTAMP(3) NOT NULL
      )
    `;
    console.log('✅ StudentEnrollment table created');

    console.log('Creating Company table...');
    await prisma.$executeRaw`
      CREATE TABLE IF NOT EXISTS "Company" (
        "id" SERIAL PRIMARY KEY,
        "name" TEXT NOT NULL UNIQUE,
        "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        "updatedAt" TIMESTAMP(3) NOT NULL
      )
    `;
    console.log('✅ Company table created');

    console.log('Creating Coordinator table...');
    await prisma.$executeRaw`
      CREATE TABLE IF NOT EXISTS "Coordinator" (
        "id" SERIAL PRIMARY KEY,
        "userName" TEXT NOT NULL UNIQUE,
        "sections" TEXT[] NOT NULL,
        "approved" BOOLEAN NOT NULL DEFAULT false,
        "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        "updatedAt" TIMESTAMP(3) NOT NULL
      )
    `;
    console.log('✅ Coordinator table created');

    console.log('Creating WeeklyReport table...');
    await prisma.$executeRaw`
      CREATE TABLE IF NOT EXISTS "WeeklyReport" (
        "id" SERIAL PRIMARY KEY,
        "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        "updatedAt" TIMESTAMP(3) NOT NULL,
        "userName" TEXT NOT NULL,
        "role" TEXT NOT NULL,
        "section" TEXT NOT NULL,
        "studentId" TEXT,
        "weekNumber" INTEGER NOT NULL,
        "date" TEXT NOT NULL,
        "hours" INTEGER NOT NULL,
        "activities" TEXT NOT NULL,
        "score" INTEGER NOT NULL,
        "learnings" TEXT NOT NULL
      )
    `;
    console.log('✅ WeeklyReport table created');

    // Test the tables
    console.log('\n🧪 Testing tables...');
    const students = await prisma.studentEnrollment.findMany();
    const coordinators = await prisma.coordinator.findMany();
    const companies = await prisma.company.findMany();
    const reports = await prisma.weeklyReport.findMany();

    console.log(`📊 Tables created successfully!`);
    console.log(`   - Students: ${students.length}`);
    console.log(`   - Coordinators: ${coordinators.length}`);
    console.log(`   - Companies: ${companies.length}`);
    console.log(`   - Reports: ${reports.length}`);

    console.log('\n🎉 Database setup complete! Your Supabase database is ready.');

  } catch (error) {
    console.error('❌ Error creating tables:', error.message);
  } finally {
    await prisma.$disconnect();
  }
}

createTables();
