// GROQ/GPT HANDLER
// SONNET 4.5

import { promptText } from "../prompting/promptLogic";

const gods = ['Agnostic', 'Gemini', 'Claude', 'Grok', 'GPT'];

export async function askGroq(creaturesData, lastAction) {
    try {
        // Get API key from backend
        const configResponse = await fetch(`/api/god4?creatures=${encodeURIComponent(creaturesData)}&lastAction=${encodeURIComponent(lastAction || '')}`);
        const config = await configResponse.json();
        const GROQ_API_KEY = config.apiKey;

        const creatures = parseCreatures(creaturesData);

        let prompt = promptText;

        if (lastAction) {
            prompt += ` Your last move was ${lastAction} and you cannot play the same action twice in a row, so you must choose a different action this time.`;
        }

        prompt += ` The creatures currently alive are :- ${JSON.stringify(creatures)}`;

        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${GROQ_API_KEY}`
            },
            body: JSON.stringify({
                model: 'openai/gpt-oss-20b',
                messages: [{
                    role: 'user',
                    content: prompt
                }],
                temperature: 1
            })
        });

        const data = await response.json();
        const text = data.choices[0].message.content;

        return {
            success: true,
            message: text
        };
    } catch (error) {
        console.error('Groq error:', error);
        return { success: false };
    }
}

function parseCreatures(dataString) {
    if (!dataString) return [];
    return dataString.split('^').map(creature => {
        const [x, y, health, faith] = creature.split('~');
        return {
            x: parseFloat(x),
            y: parseFloat(y),
            health: parseFloat(health),
            faith: gods[parseInt(faith)]
        };
    });
}