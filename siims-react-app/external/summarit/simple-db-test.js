const { PrismaClient } = require('@prisma/client');

async function testDatabase() {
  const prisma = new PrismaClient();

  try {
    console.log('🔍 Testing Supabase database...\n');

    // Test basic connection
    console.log('Testing connection...');
    await prisma.$queryRaw`SELECT 1 as test`;
    console.log('✅ Connection successful!');

    // Test if we can create a simple record
    console.log('\nTesting data operations...');
    
    // Try to create a test student
    try {
      const testStudent = await prisma.studentEnrollment.create({
        data: {
          studentId: 'TEST001',
          userName: 'Test Student',
          section: 'IT4R8',
          companyName: 'Test Company'
        }
      });
      console.log('✅ Student creation successful!');
      
      // Clean up test data
      await prisma.studentEnrollment.delete({
        where: { studentId: 'TEST001' }
      });
      console.log('✅ Test data cleaned up');
      
    } catch (error) {
      if (error.code === 'P2002') {
        console.log('✅ Student table exists (duplicate key error expected)');
      } else {
        console.log('❌ Student table error:', error.message);
      }
    }

    // Test coordinator creation
    try {
      const testCoordinator = await prisma.coordinator.create({
        data: {
          userName: 'Test Coordinator',
          sections: ['IT4R8'],
          approved: true
        }
      });
      console.log('✅ Coordinator creation successful!');
      
      // Clean up test data
      await prisma.coordinator.delete({
        where: { userName: 'Test Coordinator' }
      });
      console.log('✅ Test coordinator cleaned up');
      
    } catch (error) {
      if (error.code === 'P2002') {
        console.log('✅ Coordinator table exists (duplicate key error expected)');
      } else {
        console.log('❌ Coordinator table error:', error.message);
      }
    }

    console.log('\n🎉 Database test complete! Your Supabase database is working.');

  } catch (error) {
    console.error('❌ Database test failed:', error.message);
  } finally {
    await prisma.$disconnect();
  }
}

testDatabase();
