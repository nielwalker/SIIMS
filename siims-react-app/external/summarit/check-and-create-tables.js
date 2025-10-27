const { PrismaClient } = require('@prisma/client');

async function checkAndCreateTables() {
  const prisma = new PrismaClient({
    log: ['query', 'info', 'warn', 'error'],
  });

  try {
    console.log('🔍 Checking Supabase database tables...\n');

    // Test connection
    await prisma.$queryRaw`SELECT 1 as test`;
    console.log('✅ Database connection successful!');

    // Check if tables exist
    const tables = await prisma.$queryRaw`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      AND table_name IN ('WeeklyReport', 'StudentEnrollment', 'Coordinator', 'Company')
      ORDER BY table_name
    `;
    
    console.log(`📋 Found tables: ${tables.map(row => row.table_name).join(', ') || 'None'}`);

    if (tables.length === 0) {
      console.log('\n🔧 No tables found. Creating tables manually...');
      
      // Create tables manually using raw SQL
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
      console.log('✅ Created StudentEnrollment table');

      await prisma.$executeRaw`
        CREATE TABLE IF NOT EXISTS "Company" (
          "id" SERIAL PRIMARY KEY,
          "name" TEXT NOT NULL UNIQUE,
          "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
          "updatedAt" TIMESTAMP(3) NOT NULL
        )
      `;
      console.log('✅ Created Company table');

      await prisma.$executeRaw`
        CREATE TABLE IF NOT EXISTS "Coordinator" (
          "id" SERIAL PRIMARY KEY,
          "coordinatorId" INTEGER UNIQUE,
          "userName" TEXT NOT NULL UNIQUE,
          "sections" TEXT[] NOT NULL,
          "approved" BOOLEAN NOT NULL DEFAULT false,
          "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
          "updatedAt" TIMESTAMP(3) NOT NULL
        )
      `;
      console.log('✅ Created Coordinator table');

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
          "learnings" TEXT NOT NULL,
          "excuse" TEXT
        )
      `;
      console.log('✅ Created WeeklyReport table');

      console.log('\n🎉 All tables created successfully!');
    } else {
      console.log('\n✅ All required tables already exist!');
      
      // Check if excuse column exists in WeeklyReport table
      const excuseColumn = await prisma.$queryRaw`
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'WeeklyReport' 
        AND column_name = 'excuse'
      `;
      
      if (excuseColumn.length === 0) {
        console.log('🔧 Adding excuse column to WeeklyReport table...');
        await prisma.$executeRaw`ALTER TABLE "WeeklyReport" ADD COLUMN "excuse" TEXT`;
        console.log('✅ Added excuse column to WeeklyReport table');
      } else {
        console.log('✅ Excuse column already exists in WeeklyReport table');
      }
    }

    // Test fetching data
    console.log('\n📊 Testing data fetch...');
    const students = await prisma.studentEnrollment.findMany();
    const coordinators = await prisma.coordinator.findMany();
    const companies = await prisma.company.findMany();
    const reports = await prisma.weeklyReport.findMany();

    console.log(`📚 Students: ${students.length}`);
    console.log(`👨‍🏫 Coordinators: ${coordinators.length}`);
    console.log(`🏢 Companies: ${companies.length}`);
    console.log(`📊 Weekly Reports: ${reports.length}`);

    console.log('\n✅ Database setup complete! Your Supabase database is ready.');

  } catch (error) {
    console.error('❌ Error:', error.message);
    console.error('Full error:', error);
  } finally {
    await prisma.$disconnect();
  }
}

checkAndCreateTables();
