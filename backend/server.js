import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { god1Handler } from './api/god1.js';
import { god2Handler } from './api/god2.js';
import { god3Handler } from './api/god3.js';
import { god4Handler } from './api/god4.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use(express.static('.')); // Serve file statici (index.html, etc)

// API routes
app.get('/api/god1', god1Handler);
app.get('/api/god2', god2Handler);
app.get('/api/god3', god3Handler);
app.get('/api/god4', god4Handler);

app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});