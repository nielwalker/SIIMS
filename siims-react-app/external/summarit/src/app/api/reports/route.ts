import { NextRequest, NextResponse } from 'next/server'
import { getReports, createReport, updateReport, deleteReport } from '../../../lib/supabase'

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
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
    
    if (action === 'getStudentTotalHours' && studentId) {
      // Get total hours for a specific student
      const reports = await getReports(undefined, studentId)
      console.log('Reports for student', studentId, ':', reports.length, 'reports')
      console.log('Report hours:', reports.map(r => ({ id: r.id, week: r.weekNumber, hours: r.hours })))
      const totalHours = reports.reduce((sum, report) => sum + (report.hours || 0), 0)
      console.log('Calculated total hours:', totalHours)
      return NextResponse.json({ totalHours }, { headers: corsHeaders as Record<string, string> })
    }
    
    // Use Supabase database only - no mock data fallbacks
    const reports = await getReports(section || undefined, studentId || undefined)
    
    return NextResponse.json(reports, { headers: corsHeaders as Record<string, string> })
  } catch (error: any) {
    console.error('Error fetching reports:', error)
    return NextResponse.json({ error: 'Failed to fetch reports: ' + error.message }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}

export async function POST(req: NextRequest) {
  try {
    const data = await req.json()
    console.log('Report submitted:', data)
    
    // Use Supabase database only - no mock data fallbacks
    const report = await createReport({
      userName: data.userName || 'Unknown',
      role: data.role || 'student',
      section: data.section || 'Unknown',
      studentId: data.studentId || null,
      weekNumber: data.weekNumber || 1,
      date: data.date || new Date().toISOString().split('T')[0],
      hours: data.hours || 0,
      activities: data.activities || '',
      learnings: data.learnings || ''
    })
    
    return NextResponse.json({ 
      success: true, 
      message: 'Report saved successfully',
      id: report.id
    }, { headers: corsHeaders as Record<string, string> })
    
  } catch (error: any) {
    console.error('Report error:', error)
    return NextResponse.json({ error: 'Failed to save report: ' + error.message }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}

export async function PUT(req: NextRequest) {
  try {
    const data = await req.json()
    console.log('Updating report:', data)
    
    // Import the Supabase client
    const { createClient } = await import('@supabase/supabase-js')
    const supabaseUrl = process.env.SUPABASE_URL || process.env.NEXT_PUBLIC_SUPABASE_URL!
    const supabaseKey = process.env.SUPABASE_KEY || process.env.SUPABASE_SERVICE_ROLE_KEY!
    const supabase = createClient(supabaseUrl, supabaseKey)
    
    if (data.reportId) {
      // Update existing report
      const updateData: any = {}
      if (data.excuse !== undefined) updateData.excuse = data.excuse
      if (data.date !== undefined) updateData.date = data.date
      if (data.hours !== undefined) updateData.hours = data.hours
      if (data.activities !== undefined) updateData.activities = data.activities
      if (data.learnings !== undefined) updateData.learnings = data.learnings
      
      const { data: updatedReport, error } = await supabase
        .from('WeeklyReport')
        .update(updateData)
        .eq('id', data.reportId)
        .select()
        .single()
      
      if (error) {
        console.error('Error updating report:', error)
        return NextResponse.json({ error: 'Failed to update report: ' + error.message }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
      
      return NextResponse.json({ 
        success: true, 
        message: 'Report updated successfully',
        id: updatedReport.id
      }, { headers: corsHeaders as Record<string, string> })
    } else {
      // Create a new report entry for excuse only (no activities/learnings for summarization)
      const { data: newReport, error } = await supabase
        .from('WeeklyReport')
        .insert({
          userName: 'Coordinator Entry',
          role: 'coordinator',
          section: 'N/A',
          studentId: data.studentId,
          weekNumber: data.weekNumber,
          date: data.date || new Date().toISOString().split('T')[0],
          hours: 0, // No hours for excuse-only entries
          activities: '',
          learnings: '',
          excuse: data.excuse
        })
        .select()
        .single()
      
      if (error) {
        console.error('Error creating excuse entry:', error)
        return NextResponse.json({ error: 'Failed to create excuse entry: ' + error.message }, { status: 500, headers: corsHeaders as Record<string, string> })
      }
      
      return NextResponse.json({ 
        success: true, 
        message: 'Excuse entry created successfully',
        id: newReport.id
      }, { headers: corsHeaders as Record<string, string> })
    }
  } catch (error: any) {
    console.error('Error updating report:', error)
    return NextResponse.json({ error: 'Failed to update report: ' + error.message }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}

export async function DELETE(req: NextRequest) {
  try {
    const url = new URL(req.url)
    const id = url.searchParams.get('id')
    
    if (!id) {
      return NextResponse.json({ error: 'Report ID required' }, { status: 400, headers: corsHeaders as Record<string, string> })
    }
    
    console.log('Delete report:', id)
    
    // Use Supabase database only - no mock data fallbacks
    await deleteReport(parseInt(id))
    
    return NextResponse.json({ 
      success: true, 
      message: 'Report deleted successfully' 
    }, { headers: corsHeaders as Record<string, string> })
    
  } catch (error: any) {
    console.error('Delete error:', error)
    return NextResponse.json({ error: 'Failed to delete report: ' + error.message }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}
