import { Client } from 'pg';

const connectionString = 'postgresql://neondb_owner:npg_dXIJQk5vVwY8@ep-withered-cell-azwm4gqa-pooler.c-3.ap-southeast-1.aws.neon.tech/neondb?sslmode=require';

async function main() {
  console.log('Testing connection to Neon PostgreSQL...');
  const client = new Client({
    connectionString,
    ssl: {
      rejectUnauthorized: false
    }
  });

  try {
    await client.connect();
    console.log('Successfully connected to Neon PostgreSQL!');
    const res = await client.query('SELECT version(), current_database(), current_user;');
    console.log('Database details:', res.rows[0]);

    // Check existing tables in public schema
    const tablesRes = await client.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      ORDER BY table_name;
    `);
    console.log('Existing tables in public schema:', tablesRes.rows.map(r => r.table_name));

    await client.end();
  } catch (err) {
    console.error('Connection failed:', err);
    process.exit(1);
  }
}

main();
