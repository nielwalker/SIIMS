#!/usr/bin/env node
// Cross-platform dev runner that respects PORT env var

const { spawn } = require('child_process')

const port = process.env.PORT || process.env.NEXT_PORT || '3000'

// Use shell=true for Windows compatibility (avoids spawn EINVAL with .cmd)
const child = spawn(`npx next dev -p ${port}`, {
  stdio: 'inherit',
  shell: true,
  env: { ...process.env, PORT: String(port) }
})

child.on('exit', code => process.exit(code ?? 0))


