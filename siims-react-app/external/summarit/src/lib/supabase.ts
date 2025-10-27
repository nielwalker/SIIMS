import { createClient } from '@supabase/supabase-js'

const supabaseUrl = process.env.SUPABASE_URL || process.env.NEXT_PUBLIC_SUPABASE_URL!
const supabaseKey = process.env.SUPABASE_KEY || process.env.SUPABASE_SERVICE_ROLE_KEY!

export const supabase = createClient(supabaseUrl, supabaseKey)

// Helper functions for database operations
export async function getStudents(section: string) {
  const { data, error } = await supabase
    .from('StudentEnrollment')
    .select('*')
    .eq('section', section)
    .order('studentId', { ascending: true })

  if (error) throw error
  return data || []
}

export async function createStudent(studentData: any) {
  const { data, error } = await supabase
    .from('StudentEnrollment')
    .insert([{
      ...studentData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }])
    .select()
    .single()

  if (error) throw error
  return data
}

export async function getReports(section?: string, studentId?: string) {
  let query = supabase.from('WeeklyReport').select('*')
  
  if (section) query = query.eq('section', section)
  if (studentId) query = query.eq('studentId', studentId)
  
  const { data, error } = await query.order('weekNumber', { ascending: true })
  
  if (error) throw error
  return data || []
}

export async function createReport(reportData: any) {
  const { data, error } = await supabase
    .from('WeeklyReport')
    .insert([{
      ...reportData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }])
    .select()
    .single()

  if (error) throw error
  return data
}

export async function updateReport(reportId: number, reportData: any) {
  const { data, error } = await supabase
    .from('WeeklyReport')
    .update({
      ...reportData,
      updatedAt: new Date().toISOString()
    })
    .eq('id', reportId)
    .select()
    .single()

  if (error) throw error
  return data
}

export async function deleteReport(id: number) {
  const { error } = await supabase
    .from('WeeklyReport')
    .delete()
    .eq('id', id)

  if (error) throw error
  return true
}

export async function createCompany(companyData: any) {
  const { data, error } = await supabase
    .from('Company')
    .upsert([{
      ...companyData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }], { onConflict: 'name' })
    .select()
    .single()

  if (error) throw error
  return data
}

export async function createCoordinator(coordinatorData: any) {
  const { data, error } = await supabase
    .from('Coordinator')
    .upsert([{
      ...coordinatorData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }], { onConflict: 'userName' })
    .select()
    .single()

  if (error) throw error
  return data
}
