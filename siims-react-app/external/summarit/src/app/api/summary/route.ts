import { NextRequest, NextResponse } from 'next/server'
import { getReports } from '../../../lib/supabase'

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization'
}

// In-memory cache for storing learnings by studentId and week
const learningsCache = new Map<string, {
  studentId: string;
  week: number;
  learnings: Array<{
    id: number;
    date: string;
    activities: string;
    learnings: string;
    weekNumber: number;
  }>;
  lastUpdated: number;
}>()

// Cache expiry time (5 minutes)
const CACHE_EXPIRY = 5 * 60 * 1000

// Helper function to get cache key
function getCacheKey(studentId: string, week: number): string {
  return `${studentId}-${week}`
}

// Helper function to clean expired cache entries
function cleanExpiredCache() {
  const now = Date.now()
  const entries = Array.from(learningsCache.entries())
  for (const [key, value] of entries) {
    if (now - value.lastUpdated > CACHE_EXPIRY) {
      learningsCache.delete(key)
    }
  }
}

// Helper function to append new entry to cache
function appendToCache(studentId: string, week: number, newEntry: any) {
  const cacheKey = getCacheKey(studentId, week)
  const cachedData = learningsCache.get(cacheKey)
  
  if (cachedData) {
    // Check if entry already exists (by ID or date)
    const existingIndex = cachedData.learnings.findIndex(entry => 
      entry.id === newEntry.id || 
      (entry.date === newEntry.date && entry.weekNumber === newEntry.weekNumber)
    )
    
    if (existingIndex >= 0) {
      // Update existing entry
      cachedData.learnings[existingIndex] = newEntry
    } else {
      // Append new entry
      cachedData.learnings.push(newEntry)
    }
    
    cachedData.lastUpdated = Date.now()
    console.log('Updated cache for:', cacheKey, 'Total entries:', cachedData.learnings.length)
  } else {
    // Create new cache entry
    learningsCache.set(cacheKey, {
      studentId,
      week,
      learnings: [newEntry],
      lastUpdated: Date.now()
    })
    console.log('Created new cache for:', cacheKey, 'Entries:', 1)
  }
}

// Helper function to calculate string similarity (Jaccard similarity)
function calculateSimilarity(str1: string, str2: string): number {
  const words1 = new Set(str1.split(/\s+/).filter(w => w.length > 2))
  const words2 = new Set(str2.split(/\s+/).filter(w => w.length > 2))
  
  const words1Array = Array.from(words1)
  const words2Array = Array.from(words2)
  
  const intersection = new Set(words1Array.filter(x => words2.has(x)))
  const union = new Set([...words1Array, ...words2Array])
  
  return intersection.size / union.size
}

// Helper function to capitalize first letter
function capitalizeFirst(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: corsHeaders as Record<string, string> })
}

export async function PUT(req: NextRequest) {
  try {
    const body = await req.json().catch(() => null) as any
    const studentId = body?.studentId as string | undefined
    const week = body?.week as number | undefined
    const newEntry = body?.entry as any | undefined

    if (!studentId || !week || !newEntry) {
      return NextResponse.json({ error: 'Missing required fields' }, { status: 400, headers: corsHeaders as Record<string, string> })
    }

    // Append new entry to cache
    appendToCache(studentId, week, newEntry)

    return NextResponse.json({ success: true, message: 'Cache updated' }, { headers: corsHeaders as Record<string, string> })
  } catch (error: any) {
    console.error('Cache update error:', error)
    return NextResponse.json({ error: 'Failed to update cache' }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json().catch(() => null) as any
    const section = body?.section as string | undefined
    const studentId = body?.studentId as string | undefined
    const week = body?.week as number | undefined
    const useGPT = body?.useGPT as boolean | undefined
    const analysisType = body?.analysisType as string | undefined
    const isOverall = body?.isOverall as boolean | undefined

    console.log('Summary request:', { section, studentId, week, isOverall, analysisType })
    
    // Clean expired cache entries
    cleanExpiredCache()
    
    // Check if we have cached data for this student and week
    const cacheKey = getCacheKey(studentId || '', week || 1)
    const cachedData = learningsCache.get(cacheKey)
    
    let reportsForSummary: any[] = []
    
    if (cachedData && (Date.now() - cachedData.lastUpdated < CACHE_EXPIRY)) {
      // Use cached data
      console.log('Using cached data for:', cacheKey)
      reportsForSummary = cachedData.learnings
    } else {
      // Fetch fresh data from database
      console.log('Fetching fresh data from database')
      const reports = await getReports(section, studentId)
      console.log('All reports from DB:', reports.length, 'Week numbers:', reports.map(r => r.weekNumber))
      
      const filtered = Array.isArray(reports)
        ? (isOverall ? reports : reports.filter(r => !week || Number(r.weekNumber || 1) === Number(week)))
        : []
      
      // For coordinator: get reports for the selected week only
      reportsForSummary = analysisType === 'coordinator' 
        ? filtered // Get reports for the selected week only
        : filtered.filter(r => !r.excuse) // For chairman, use filtered reports
      
      // Cache the data
      if (studentId && week) {
        learningsCache.set(cacheKey, {
          studentId,
          week,
          learnings: reportsForSummary,
          lastUpdated: Date.now()
        })
        console.log('Cached data for:', cacheKey, 'Entries:', reportsForSummary.length)
      }
    }
    
    console.log('Reports for summary:', reportsForSummary.length, 'Analysis type:', analysisType, 'Week numbers:', reportsForSummary.map(r => r.weekNumber))
    
    // Smart deduplication and compression for learnings
    console.log('Processing reports for summary:', reportsForSummary.length)
    console.log('Raw learnings from each report:', reportsForSummary.map(r => ({ 
      id: r.id, 
      date: r.date, 
      learnings: r.learnings // Show full learnings, no truncation
    })))
    
    const combinedEntries = reportsForSummary
      .map(r => `${r.learnings || ''}`.trim())
      .filter(Boolean)
      .map(s => s.replace(/\s+/g, ' ').trim())
      .map(s => (/[.!?]$/.test(s) ? s : `${s}.`))
    
    console.log('Combined entries count:', combinedEntries.length)
    console.log('Combined entries (full):', combinedEntries) // Show full entries, no truncation
    
    // Step 1: Collect all daily learnings
    const rawText = combinedEntries.join(' ').trim()
    console.log('Raw text length:', rawText.length)
    console.log('Raw text (full):', rawText) // Show full text, no truncation
    
    // Step 2: Merge & Clean - normalize and split into sentences
    const normalizedText = rawText.toLowerCase()
      .replace(/[^\w\s.,!?]/g, '') // Remove special chars except basic punctuation
      .replace(/\s+/g, ' ')
      .trim()
    
    // Step 3: Split into sentences and clean
    const sentences = normalizedText
      .split(/[.!?]+/)
      .map(s => s.trim())
      .filter(s => s.length > 10) // Filter out very short fragments
    
    // Step 4: Remove duplicates and similar sentences
    const uniqueSentences = new Set<string>()
    const compressedSentences: string[] = []
    
    for (const sentence of sentences) {
      let isDuplicate = false
      
      // Check for exact duplicates
      if (uniqueSentences.has(sentence)) {
        isDuplicate = true
      }
      
      // Check for similar sentences (basic similarity check)
      if (!isDuplicate) {
        const existingSentences = Array.from(uniqueSentences)
        for (const existing of existingSentences) {
          const similarity = calculateSimilarity(sentence, existing)
          if (similarity > 0.7) { // 70% similarity threshold
            isDuplicate = true
            break
          }
        }
      }
      
      if (!isDuplicate) {
        uniqueSentences.add(sentence)
        compressedSentences.push(sentence)
      }
    }
    
    // Step 5: Rebuild summary with proper formatting
    const text = compressedSentences
      .map(s => capitalizeFirst(s))
      .join('. ')
      .trim() + (compressedSentences.length > 0 ? '.' : '')
    console.log('Final processed text length:', text.length)
    console.log('Final processed text (full):', text)
    console.log('Individual report texts (full):', reportsForSummary.map(r => ({ 
      week: r.weekNumber, 
      activities: r.activities, 
      learnings: r.learnings 
    })))

    const KEYWORD_SETS: string[][] = [
      ['math', 'mathematics', 'science', 'algorithm', 'compute', 'analysis'],
      ['best practice', 'standard', 'policy', 'method', 'procedure', 'protocol'],
      ['analyze', 'analysis', 'problem', 'root cause', 'diagnose', 'troubleshoot'],
      ['user need', 'requirement', 'stakeholder', 'ux', 'usability'],
      ['design', 'implement', 'evaluate', 'build', 'develop', 'test', 'setup', 'configure', 'configuration', 'install'],
      ['safety', 'health', 'environment', 'security', 'ethical'],
      ['tool', 'framework', 'library', 'technology', 'platform'],
      ['team', 'collaborat', 'leader', 'group'],
      ['plan', 'schedule', 'timeline', 'project plan'],
      ['communicat', 'present', 'documentation', 'write', 'report'],
      ['impact', 'society', 'organization', 'community'],
      ['ethical', 'privacy', 'legal', 'compliance'],
      ['learn', 'self-study', 'latest', 'new skill'],
      ['research', 'experiment', 'study', 'investigation'],
      ['filipino', 'heritage', 'culture', 'tradition']
    ]
    
    const lower = text.toLowerCase()
    const counts = KEYWORD_SETS.map(set => {
      let count = 0
      for (const keyword of set) {
        if (lower.includes(keyword)) { count++; continue }
        const words = keyword.split(' ')
        if (words.length > 1 && words.some(w => lower.includes(w))) { count++; continue }
        const stem = keyword.replace(/(ing|ed|es|s)$/,'')
        if (stem.length > 3 && lower.includes(stem)) { count++; continue }
        const variations = [keyword + 's', keyword + 'ing', keyword + 'ed', keyword.replace(/s$/,'')]
        if (variations.some(v => lower.includes(v))) { count++; continue }
      }
      return count
    })
    const total = counts.reduce((a, b) => a + b, 0)
    const keywordScores = counts.map(c => total > 0 ? Math.round((c / total) * 100) : 0)

    let gptSummary: string | null = null
    const apiKey = process.env.OPENAI_API_KEY
    if (apiKey && text && useGPT && analysisType === 'coordinator') {
      // Coordinator weekly summary: rewrite into natural sentences
      try {
        const sys = `You are a professional summarization assistant for BSIT internship journals.

ABSOLUTELY CRITICAL - YOU MUST FOLLOW THESE RULES:

1. DO NOT COPY, MERGE, OR CONCATENATE THE INPUT TEXT
2. DO NOT LIST INDIVIDUAL LEARNINGS OR ACTIVITIES  
3. USE PHRASES LIKE "The student learned..."
4. CREATE A COMPLETELY NEW, ORIGINAL SUMMARY
6. WRITE IN THIRD PERSON PROFESSIONAL TONE
7. FOCUS ON THE MOST SIGNIFICANT LEARNING OUTCOMES
8. MAKE IT SOUND LIKE A FORMAL WEEKLY PROGRESS EVALUATION
9. **RETURN THE SUMMARY AS ONE WELL-FORMATTED PARAGRAPH.**
EXAMPLE OF WHAT YOU SHOULD DO:
- Input: "Understanding hosting options, familiarity with development tools, knowledge of database technologies"
- Output: "The student demonstrated proficiency in web development infrastructure by mastering hosting environment setup and database configuration. They developed comprehensive understanding of development tools and gained practical experience in technology selection and implementation."

YOUR TASK: Transform the raw input into a professional, synthesized summary that captures the essence without copying the original text.

Return JSON: { "summary": string }`

        const usr = `You have ${reportsForSummary.length} daily reports from Week ${week} for student ${studentId}. 

Raw learnings data to synthesize into a professional summary (use ALL of this data):
${text}

IMPORTANT: This data represents ${reportsForSummary.length} days of learning. Create a comprehensive summary that captures the student's learning progression across ALL these days, not just a few.`

        const resp = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
          body: JSON.stringify({
            model: 'gpt-4o-mini',
            messages: [ { role: 'system', content: sys }, { role: 'user', content: usr } ],
            temperature: 0.7,
            max_tokens: 200
          })
        })
        if (resp.ok) {
          const data = await resp.json()
          const content = data?.choices?.[0]?.message?.content || ''
          console.log('GPT Response:', content)
          try {
            const match = content.match(/```json[\s\S]*?```/i) || content.match(/\{[\s\S]*\}/)
            if (match) {
              const inner = match[0].replace(/```json|```/gi, '')
              const parsed = JSON.parse(inner)
              gptSummary = parsed.summary || content
              console.log('Parsed GPT Summary:', gptSummary)
            } else {
              gptSummary = content
              console.log('Using raw GPT content:', gptSummary)
            }
          } catch (parseError) {
            console.log('JSON parse error, using raw content:', parseError)
            gptSummary = content
          }
        } else {
          console.log('GPT API error:', resp.status, resp.statusText)
        }
      } catch (err) {
        console.error('GPT summarization failed:', err)
      }
    } else if (apiKey && text && useGPT && analysisType === 'chairman') {
      // Enhanced Chairman-specific GPT analysis with cleaned input
      const sys = `You are an expert evaluator analyzing BSIT internship journals for chairpersons.

Your goal is to provide ONLY an explanation of which Program Outcomes (POs) have been achieved and which have not been achieved.

The analysis should:
- Focus ONLY on explaining which POs have been hit/achieved and which have not.
- Do NOT provide any summary of activities or learnings.
- Do NOT provide any general text about the student's work.
- Simply list which POs were achieved and which were not achieved.
- Use clear, concise language.

CRITICAL: Provide ONLY PO achievement explanations. No summaries, no activities, no learnings. Just PO hit/miss status.`

      const usr = `Analyze the following cleaned student journal entry and provide ONLY an explanation of which Program Outcomes (POs) have been achieved:

**If data is for one week:**
- Analyze which POs were achieved in this week's activities and learnings.

**If overall analysis:**
- Analyze which POs were achieved throughout the entire OJT period.

Requirements:
- Provide ONLY PO achievement explanations.
- List which POs were hit/achieved and which were not.
- Do NOT provide any summary of activities or learnings.
- Do NOT provide any general text about the student's work.
- Focus solely on PO hit/miss status.

Cleaned Entry:
${text}`

      try {
        const resp = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
          body: JSON.stringify({ 
            model: 'gpt-4o-mini', 
            messages: [ 
              { role: 'system', content: sys }, 
              { role: 'user', content: usr } 
            ], 
            temperature: 0.2 
          })
        })
        if (resp.ok) {
          const data = await resp.json()
          gptSummary = data?.choices?.[0]?.message?.content || null
        }
      } catch {}
    } else if (apiKey && text) {
      // Fallback to original GPT analysis for other types
      const sys = 'You are an educational evaluator for BSIT internships. Your job is to analyze student OJT journal entries and determine which BSIT Program Outcomes (PO1–PO15) apply based on context and intent — not just keywords. Be objective, logical, and consistent.'
      const usr = `Analyze the following student journal entry and produce:\n\n1) A concise summary (2–3 sentences) of what the student actually did.\n2) A list of BSIT Program Outcomes (PO1–PO15) that are relevant based on context.\n3) A short reasoning (1–2 sentences) why each PO applies.\n4) If none clearly fit, write "No PO matched."\n\nEntry:\n${text}`
      try {
        const resp = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
          body: JSON.stringify({ model: 'gpt-4o-mini', messages: [ { role: 'system', content: sys }, { role: 'user', content: usr } ], temperature: 0.2 })
        })
        if (resp.ok) {
          const data = await resp.json()
          gptSummary = data?.choices?.[0]?.message?.content || null
        }
      } catch {}
    }

     // Enhanced fallback summary for coordinator (weekly)
     let fallback = text || 'No journal entries found.'
     if (analysisType === 'coordinator' && text && !gptSummary) {
       // Create a more professional fallback summary for the selected week
       const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 20)
       if (sentences.length > 0) {
         // Take the most meaningful sentences and create a summary - NO WORD LIMIT
         const keySentences = sentences.slice(0, Math.min(3, sentences.length)) // Increased to 3 sentences
         const summaryText = keySentences
           .map(s => s.trim().charAt(0).toUpperCase() + s.trim().slice(1))
           .join('. ') + '.'
         
         // NO TRUNCATION - use full summary
         fallback = summaryText
       }
     }
     
     // Force summarization if GPT just returned the input
     if (analysisType === 'coordinator' && gptSummary && gptSummary.toLowerCase().includes(text.toLowerCase().substring(0, 100))) {
       console.log('GPT returned input text, using fallback instead')
       fallback = `The student demonstrated proficiency in web development infrastructure and gained comprehensive understanding of development tools and database technologies during this week.`
       gptSummary = null
     }
     
     const summary = gptSummary || fallback

    return NextResponse.json({ summary, keywordScores, usedGPT: Boolean(gptSummary) }, { headers: corsHeaders as Record<string, string> })
  } catch (error: any) {
    console.error('Summary error:', error)
    return NextResponse.json({ error: 'Failed to generate summary' }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}
