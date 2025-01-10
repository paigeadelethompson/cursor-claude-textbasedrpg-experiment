import { Pool } from 'pg'

const pool = new Pool({
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '26257'), // CockroachDB default port
  database: process.env.DB_NAME || 'defaultdb',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  ssl: process.env.DB_SSL === 'true' ? {
    rejectUnauthorized: false
  } : false
})

export default pool 