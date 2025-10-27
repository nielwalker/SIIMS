import { NextRequest, NextResponse } from 'next/server'
import { getStudents, createStudent, createCompany, createCoordinator, supabase } from '../../../lib/supabase'

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization',
  'Cache-Control': 'no-cache, no-store, must-revalidate',
  'Pragma': 'no-cache',
  'Expires': '0'
}

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: corsHeaders as Record<string, string> })
}

export async function GET(req: NextRequest) {
  try {
    const url = new URL(req.url)
    const action = url.searchParams.get('action')
    const section = url.searchParams.get('section')
    const studentId = url.searchParams.get('studentId')

    if (action === 'listStudents' && section) {
      try {
        console.log("Fetching students for section:", section)
        const students = await getStudents(section)
        console.log("Students fetched:", students)
        
        return NextResponse.json(students, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Error fetching students:', error.message, error.stack)
        return NextResponse.json(
          { error: 'Failed to fetch students', details: error.message },
          { status: 500, headers: corsHeaders as Record<string, string> }
        )
      }
    }

    if (action === 'getCompanies') {
      try {
        const { data: companies, error } = await supabase
          .from('Company')
          .select('id, name, address, supervisor, contactNumber')
          .order('name')
        
        if (error) throw error
        return NextResponse.json(companies || [], { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Error fetching companies:', error)
        return NextResponse.json({ error: 'Failed to fetch companies' }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (action === 'getCoordinatorSections') {
      try {
        const coordinatorId = url.searchParams.get('coordinatorId')
        let query = supabase
          .from('Coordinator')
          .select('sections')
          .eq('approved', true)
        if (coordinatorId) {
          query = query.eq('coordinatorId', Number(coordinatorId))
        }
        const { data: coordinators, error } = await query
        
        if (error) throw error
        
        // Extract all unique sections from coordinators
        const allSections = new Set<string>()
        coordinators?.forEach(coord => {
          if (coord.sections && Array.isArray(coord.sections)) {
            coord.sections.forEach(section => allSections.add(section))
          }
        })
        
        return NextResponse.json(Array.from(allSections).sort(), { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Error fetching coordinator sections:', error)
        return NextResponse.json({ error: 'Failed to fetch coordinator sections' }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (action === 'listAllStudents') {
      try {
        const { data: students, error } = await supabase
          .from('StudentEnrollment')
          .select('studentId, userName, section, companyName')
          .order('studentId')
        
        if (error) throw error
        return NextResponse.json(students || [], { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Error fetching all students:', error)
        return NextResponse.json({ error: 'Failed to fetch students' }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (action === 'listAllCoordinators') {
      try {
        // Try selecting with coordinatorId; if column doesn't exist, fallback without it
        let { data: coordinators, error } = await supabase
          .from('Coordinator')
          .select('id, coordinatorId, userName, sections, approved')
          .order('userName')
        if (error) {
          console.warn('listAllCoordinators with coordinatorId failed, falling back:', error?.message)
          const fallback = await supabase
            .from('Coordinator')
            .select('id, userName, sections, approved')
            .order('userName')
          coordinators = fallback.data as any
          error = fallback.error as any
        }
        if (error) throw error
        return NextResponse.json(coordinators || [], { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Error fetching all coordinators:', error)
        return NextResponse.json({ error: 'Failed to fetch coordinators' }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (action === 'getStudentDetails' && studentId) {
      try {
        const { data: student, error: studentError } = await supabase
          .from('StudentEnrollment')
          .select('*')
          .eq('studentId', studentId)
          .single()
        
        if (studentError || !student) {
          return NextResponse.json({ error: 'Student not found' }, { status: 404, headers: corsHeaders as Record<string, string> })
        }

        // Get coordinator for the student's section
        const { data: coordinator } = await supabase
          .from('Coordinator')
          .select('userName, sections')
          .contains('sections', [student.section])
          .single()

        // Get company details if company name exists
        let companyDetails = null
        if (student.companyName) {
          const { data: company } = await supabase
            .from('Company')
            .select('*')
            .eq('name', student.companyName)
            .single()
          companyDetails = company
        }

        return NextResponse.json({
          student: {
            studentId: student.studentId,
            userName: student.userName,
            section: student.section,
            companyName: student.companyName
          },
          coordinator: coordinator ? {
            userName: coordinator.userName,
            sections: coordinator.sections
          } : null,
          company: companyDetails
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Error fetching student details:', error)
        return NextResponse.json({ error: 'Failed to fetch student details' }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    return NextResponse.json({ error: 'Invalid action' }, { status: 400, headers: corsHeaders as Record<string, string> })
    
  } catch (error: any) {
    console.error('Admin API error:', error)
    return NextResponse.json({ error: 'Admin API failed' }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json().catch(() => null) as any
    if (!body) return NextResponse.json({ error: 'Invalid JSON' }, { status: 400, headers: corsHeaders as Record<string, string> })

    if (body.action === 'registerStudent') {
      const { studentId, userName, section, companyId } = body
      if (!studentId || !section || !companyId) return NextResponse.json({ error: 'studentId, section, and companyId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      const name = userName || studentId

      try {
        // Get company name from companyId
        const { data: company, error: companyError } = await supabase
          .from('Company')
          .select('name')
          .eq('id', companyId)
          .single()
        
        if (companyError || !company) {
          return NextResponse.json({ error: 'Company not found' }, { status: 404, headers: corsHeaders as Record<string, string> })
        }

        // Register student in database
        const student = await createStudent({
          studentId,
          section,
          userName: name,
          companyName: company.name
        })

        return NextResponse.json({ 
          success: true, 
          message: 'Student registered successfully',
          student: { 
            studentId: student.studentId, 
            userName: student.userName, 
            section: student.section, 
            companyName: student.companyName 
          }
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Registration error:', error)
        return NextResponse.json({ 
          error: 'Registration failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (body.action === 'registerCompany') {
      const { name, address, supervisor, contactNumber } = body
      if (!name) {
        return NextResponse.json({ error: 'Company name is required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      }
      
      try {
        // Build insert object dynamically to handle missing columns
        const insertData: any = {
          name,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        }
        
        // Only include fields that are provided and not null
        if (address !== undefined && address !== null) insertData.address = address
        if (supervisor !== undefined && supervisor !== null) insertData.supervisor = supervisor
        if (contactNumber !== undefined && contactNumber !== null) insertData.contactNumber = contactNumber

        const { data: company, error } = await supabase
          .from('Company')
          .insert([insertData])
          .select()
          .single()

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Company registered successfully',
          company: { 
            id: company.id,
            name: company.name,
            address: company.address || null,
            supervisor: company.supervisor || null,
            contactNumber: company.contactNumber || null
          }
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Company registration error:', error)
        return NextResponse.json({ 
          error: 'Company registration failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (body.action === 'registerCoordinator') {
      const { userName, sections, coordinatorId } = body
      if (!userName) return NextResponse.json({ error: 'userName required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      if (coordinatorId == null || isNaN(Number(coordinatorId))) return NextResponse.json({ error: 'coordinatorId (integer) required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        let coordinator: any
        try {
          coordinator = await createCoordinator({
            userName,
            coordinatorId: Number(coordinatorId),
            sections: Array.isArray(sections) ? sections : [sections].filter(Boolean),
            approved: true
          })
        } catch (e: any) {
          // Fallback when coordinatorId column doesn't exist yet
          console.warn('createCoordinator with coordinatorId failed, falling back:', e?.message)
          coordinator = await createCoordinator({
            userName,
            sections: Array.isArray(sections) ? sections : [sections].filter(Boolean),
            approved: true
          })
        }

        return NextResponse.json({ 
          success: true, 
          message: 'Coordinator registered successfully',
          coordinator: { 
            id: coordinator.id,
            coordinatorId: coordinator.coordinatorId,
            userName: coordinator.userName, 
            sections: coordinator.sections,
            approved: coordinator.approved
          }
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Coordinator registration error:', error)
        return NextResponse.json({ 
          error: 'Coordinator registration failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    // Delete operations
    if (body.action === 'deleteStudent') {
      const { studentId } = body
      if (!studentId) return NextResponse.json({ error: 'studentId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        const { error } = await supabase
          .from('StudentEnrollment')
          .delete()
          .eq('studentId', studentId)

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Student deleted successfully' 
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Delete student error:', error)
        return NextResponse.json({ 
          error: 'Delete student failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (body.action === 'deleteCoordinator') {
      const { coordinatorId } = body
      if (!coordinatorId) return NextResponse.json({ error: 'coordinatorId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        const { error } = await supabase
          .from('Coordinator')
          .delete()
          .eq('id', coordinatorId)

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Coordinator deleted successfully' 
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Delete coordinator error:', error)
        return NextResponse.json({ 
          error: 'Delete coordinator failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (body.action === 'deleteCompany') {
      const { companyId } = body
      if (!companyId) return NextResponse.json({ error: 'companyId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        const { error } = await supabase
          .from('Company')
          .delete()
          .eq('id', companyId)

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Company deleted successfully' 
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Delete company error:', error)
        return NextResponse.json({ 
          error: 'Delete company failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    // Update operations
    if (body.action === 'updateStudent') {
      const { studentId, userName, section, companyName, newStudentId } = body
      if (!studentId) return NextResponse.json({ error: 'studentId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        const updates: any = {
          userName,
          section,
          companyName,
          updatedAt: new Date().toISOString()
        }
        if (newStudentId) updates.studentId = newStudentId
        const { error } = await supabase
          .from('StudentEnrollment')
          .update(updates)
          .eq('studentId', studentId)

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Student updated successfully' 
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Update student error:', error)
        return NextResponse.json({ 
          error: 'Update student failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (body.action === 'updateCoordinator') {
      const { coordinatorId, userName, sections, coordinatorIdValue } = body
      if (!coordinatorId) return NextResponse.json({ error: 'coordinatorId (record id) required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        let updateData: any = {
          userName,
          sections,
          updatedAt: new Date().toISOString()
        }
        let { error } = await supabase
          .from('Coordinator')
          .update({
            ...updateData,
            ...(coordinatorIdValue !== undefined && coordinatorIdValue !== null ? { coordinatorId: Number(coordinatorIdValue) } : {})
          })
          .eq('id', coordinatorId)
        if (error && /column .*coordinatorId.* does not exist/i.test(error.message || '')) {
          console.warn('updateCoordinator coordinatorId update failed, retrying without column:', error.message)
          const retry = await supabase
            .from('Coordinator')
            .update(updateData)
            .eq('id', coordinatorId)
          error = retry.error as any
        }

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Coordinator updated successfully' 
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Update coordinator error:', error)
        return NextResponse.json({ 
          error: 'Update coordinator failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    if (body.action === 'updateCompany') {
      const { companyId, name, address, supervisor, contactNumber } = body
      if (!companyId) return NextResponse.json({ error: 'companyId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      
      try {
        // Build update object dynamically to handle missing columns
        const updateData: any = {
          name,
          updatedAt: new Date().toISOString()
        }
        
        // Only include fields that are provided and not null
        if (address !== undefined && address !== null) updateData.address = address
        if (supervisor !== undefined && supervisor !== null) updateData.supervisor = supervisor
        if (contactNumber !== undefined && contactNumber !== null) updateData.contactNumber = contactNumber

        const { error } = await supabase
          .from('Company')
          .update(updateData)
          .eq('id', companyId)

        if (error) throw error

        return NextResponse.json({ 
          success: true, 
          message: 'Company updated successfully' 
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Update company error:', error)
        return NextResponse.json({ 
          error: 'Update company failed: ' + (error.message || 'Unknown error') 
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    }

    return NextResponse.json({ error: 'Unknown action' }, { status: 400, headers: corsHeaders as Record<string, string> })
  } catch (error: any) {
    console.error('Admin POST error:', error)
    return NextResponse.json({ error: 'Admin POST failed' }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}
