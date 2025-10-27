const { execSync } = require('child_process');

async function setupSupabase() {
  console.log('🔧 Setting up new Supabase database...\n');

  try {
    // Set environment variable
    process.env.DATABASE_URL = 'postgresql://postgres.tkcsrdknnivqtaicpjho:databasepassword41201007@aws-1-ap-southeast-1.pooler.supabase.com:6543/postgres';
    
    console.log('🔄 Pushing database schema...');
    execSync('npx prisma db push --accept-data-loss', { 
      stdio: 'inherit',
      env: { ...process.env, DATABASE_URL: process.env.DATABASE_URL }
    });
    console.log('✅ Database schema pushed successfully!');

    console.log('🔄 Testing connection...');
    const { PrismaClient } = require('@prisma/client');
    const prisma = new PrismaClient();
    
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
    
    console.log(`✅ Found tables: ${tables.map(row => row.table_name).join(', ')}`);
    
    await prisma.$disconnect();
    console.log('\n🎉 Supabase database setup complete!');
    console.log('📊 Your database is ready with all tables created.');
    
  } catch (error) {
    console.error('❌ Setup failed:', error.message);
    console.error('Full error:', error);
  }
}

setupSupabase();
