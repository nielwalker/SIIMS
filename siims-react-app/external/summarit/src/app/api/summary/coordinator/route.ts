import { NextRequest, NextResponse } from 'next/server'
import { getReports } from '../../../../lib/supabase'

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization'
}

// In-memory cache for storing learnings by studentId and week
const learningsCache = new Map<string, {
  studentId: string;
  week: number;
  learnings: Array<any>;
  lastUpdated: number;
}>()

const CACHE_EXPIRY = 5 * 60 * 1000
function getCacheKey(studentId: string, week: number): string { return `${studentId}-${week}` }
function cleanExpiredCache() {
  const now = Date.now()
  const entries = Array.from(learningsCache.entries())
  for (const [key, value] of entries) {
    if (now - value.lastUpdated > CACHE_EXPIRY) learningsCache.delete(key)
  }
}

function calculateSimilarity(str1: string, str2: string): number {
  const words1 = new Set(str1.split(/\s+/).filter(w => w.length > 2))
  const words2 = new Set(str2.split(/\s+/).filter(w => w.length > 2))
  const words1Array = Array.from(words1)
  const words2Array = Array.from(words2)
  const intersection = new Set(words1Array.filter(x => words2.has(x)))
  const union = new Set([...words1Array, ...words2Array])
  return union.size === 0 ? 0 : intersection.size / union.size
}
function capitalizeFirst(str: string): string { return str.charAt(0).toUpperCase() + str.slice(1) }

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: corsHeaders as Record<string, string> })
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json().catch(() => null) as any
    const section = body?.section as string | undefined
    const studentId = body?.studentId as string | undefined
    const week = body?.week as number | undefined
    const useGPT = body?.useGPT as boolean | undefined

    console.log('Coordinator summary request:', { section, studentId, week })

    cleanExpiredCache()
    const cacheKey = getCacheKey(studentId || '', week || 1)
    const cached = learningsCache.get(cacheKey)

    let reportsForSummary: any[] = []
    if (cached && (Date.now() - cached.lastUpdated < CACHE_EXPIRY)) {
      reportsForSummary = cached.learnings
      console.log('Using cached coordinator reports:', reportsForSummary.length)
    } else {
      const reports = await getReports(section, studentId)
      const filtered = Array.isArray(reports) ? reports.filter(r => !week || Number(r.weekNumber || 1) === Number(week)) : []
      reportsForSummary = filtered
      if (studentId && week) learningsCache.set(cacheKey, { studentId, week, learnings: reportsForSummary, lastUpdated: Date.now() })
    }

    // Build learnings-only text, deduplicate similar sentences
    const combinedEntries = reportsForSummary
      .map(r => `${r.learnings || ''}`.trim())
      .filter(Boolean)
      .map(s => s.replace(/\s+/g, ' ').trim())
      .map(s => (/[.!?]$/.test(s) ? s : `${s}.`))

    const rawText = combinedEntries.join(' ').trim()

    const normalizedText = rawText.toLowerCase()
      .replace(/[^\w\s.,!?]/g, '')
      .replace(/\s+/g, ' ')
      .trim()

    const sentences = normalizedText.split(/[.!?]+/).map(s => s.trim()).filter(s => s.length > 10)
    const unique = new Set<string>()
    const compressed: string[] = []
    for (const s of sentences) {
      let dup = false
      if (unique.has(s)) dup = true
      if (!dup) {
        const arr = Array.from(unique)
        for (const ex of arr) { if (calculateSimilarity(s, ex) > 0.7) { dup = true; break } }
      }
      if (!dup) { unique.add(s); compressed.push(s) }
    }

    const text = compressed.map(s => capitalizeFirst(s)).join('. ').trim() + (compressed.length > 0 ? '.' : '')

    let gptSummary: string | null = null
    const apiKey = process.env.OPENAI_API_KEY
    if (apiKey && useGPT && text) {
      try {
        const sys = `You are a professional summarization assistant for BSIT internship journals.
Your job is to read multiple daily learnings for one week and generate a single, cohesive paragraph summary.
- Summarize the **core learnings**, skills developed, and key activities in a natural, academic tone.
- Avoid repeating phrases or listing daily entries.
- The output must sound like a human-written weekly summary (2-3 sentences).
Return only JSON in the format: { "summary": "..." }`

        const usr = `You have ${reportsForSummary.length} daily reports from Week ${week} for student ${studentId}.
Here are all the learnings for this week:
${text}`

        const resp = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${apiKey}` },
          body: JSON.stringify({
            model: 'gpt-4o-mini',
            messages: [
              { role: 'system', content: sys },
              { role: 'user', content: usr }
            ],
            temperature: 0.6,
            max_tokens: 200
          })
        })
        if (resp.ok) {
          const data = await resp.json()
          const content = data?.choices?.[0]?.message?.content || ''
          const match = content.match(/```json[\s\S]*?```/i) || content.match(/\{[\s\S]*\}/)
          if (match) {
            const inner = match[0].replace(/```json|```/gi, '')
            const parsed = JSON.parse(inner)
            gptSummary = parsed.summary || content
          } else {
            gptSummary = content
          }
        }
      } catch (e) { console.error('Coordinator GPT error:', e) }
    }

    const summary = gptSummary || text || 'No journal entries found.'
    return NextResponse.json({ summary }, { headers: corsHeaders as Record<string, string> })
  } catch (err: any) {
    console.error('Coordinator summary error:', err)
    return NextResponse.json({ error: 'Failed to generate summary' }, { status: 500, headers: corsHeaders as Record<string, string> })
  }
}
