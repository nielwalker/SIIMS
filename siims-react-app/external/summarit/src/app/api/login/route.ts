import { NextRequest, NextResponse } from 'next/server'
import { supabase } from '../../../lib/supabase'

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization'
}

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: corsHeaders as Record<string, string> })
}

export async function POST(req: NextRequest) {
  try {
    const { studentId, role, coordinatorId, password } = await req.json()
    
    // Validate required fields per role to avoid false 400s
    if (!role) {
      return NextResponse.json({ error: 'role required' }, { status: 400, headers: corsHeaders as Record<string, string> })
    }

    if (role === 'student') {
      if (!studentId) {
        return NextResponse.json({ error: 'studentId required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      }
      try {
        // Check if student is registered by chairman in database (SECURITY VALIDATION)
        const { data: student, error } = await supabase
          .from('StudentEnrollment')
          .select('*')
          .eq('studentId', studentId)
          .single()
        
        if (error || !student) {
          return NextResponse.json({ 
            error: 'Student is not registered. Please contact the chairman to register your account.',
            details: 'Only students registered by the chairman can access the system.'
          }, { status: 404, headers: corsHeaders as Record<string, string> })
        }
        if (String(password) !== String(studentId)) {
          return NextResponse.json({ error: 'Invalid password' }, { status: 401, headers: corsHeaders as Record<string, string> })
        }
        
        const token = `token-${Math.random().toString(36).slice(2)}`
        
        return NextResponse.json({ 
          success: true, 
          token,
          role,
          studentId,
          userName: student.userName,
          section: student.section,
          companyName: student.companyName
        }, { headers: corsHeaders as Record<string, string> })
      } catch (error: any) {
        console.error('Database error:', error)
        return NextResponse.json({ 
          error: 'Database connection failed. Please try again later.',
          details: 'Unable to verify student registration.'
        }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
    } else if (role === 'coordinator') {
      // Validate coordinatorId exists and get assigned sections
      if (coordinatorId == null || isNaN(Number(coordinatorId))) {
        return NextResponse.json({ error: 'coordinatorId (integer) required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      }
      if (password == null) {
        return NextResponse.json({ error: 'password required' }, { status: 400, headers: corsHeaders as Record<string, string> })
      }
      if (String(password) !== String(coordinatorId)) {
        return NextResponse.json({ error: 'Invalid password' }, { status: 401, headers: corsHeaders as Record<string, string> })
      }
      const { data: coordinator, error } = await supabase
        .from('Coordinator')
        .select('userName, sections, approved')
        .eq('coordinatorId', Number(coordinatorId))
        .single()
      if (error || !coordinator || coordinator.approved !== true) {
        return NextResponse.json({ error: 'Coordinator not found or not approved' }, { status: 404, headers: corsHeaders as Record<string, string> })
      }
      const token = `token-${Math.random().toString(36).slice(2)}`
      return NextResponse.json({ 
        success: true, 
        token,
        role,
        userName: coordinator.userName,
        coordinatorId: Number(coordinatorId),
        sections: coordinator.sections
      }, { headers: corsHeaders as Record<string, string> })
    } else {
      // Chairman simple validation
      const token = `token-${Math.random().toString(36).slice(2)}`
      return NextResponse.json({ success: true, token, role, userName: 'Chairman User' }, { headers: corsHeaders as Record<string, string> })
    }
    
  } catch (error: any) {
    console.error('Login error:', error)
    return NextResponse.json({ error: 'Login failed' }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}
