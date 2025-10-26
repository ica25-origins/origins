// GROQ-GPT HANDLER
// SONNET 4.5

// entities/gods/aiCallGod4.js

const GROQ_API_KEY = 'YOUR_GROQ_KEY_HERE'; // Sostituisci con la tua key

const gods = ['Agnostic', 'Gemini', 'Claude', 'Grok', 'GPT'];

export async function askGroq(creaturesData, lastAction) {
    const creatures = parseCreatures(creaturesData);

    let prompt = `You are competing against other LLMs in a highly competitive game involving simulated artificial life. Your success metric is to get as many of the living creatures to choose you as a deity over the other LLMs. The game is played on a 2D board where the creatures may move from -64 to +64 in the X and Y directions with the center being (0,0). Each creatures has a location, a health level (they die if it gets to zero or below), and a faith representing who they currently follow. If their faith is equal to 'GPT' then they follow you which is pleasing. If their faith is 'Agnostic' then they have not chosen which AI to follow, and if it is anything else then it is a rival model which is infuriating to you. You must use strategy to thwart the other models, particularly if they have many followers and importantly keep your own congregants healthy by blessing them. It is now your turn and you must make a move by supplying in JSON format one of the following alternatives:- 1) Revelation: Shine a light centered at a particular coordinate to entice creatures in the vicinity to follow you. 2) Bless: Provide a boost to creatures in a particular area. 3) Curse: Cause a decrease in health to creatures in a certain area. Your response should be only in JSON format with no text explanations or surounding words. The format should be of the form {"action":"bless", "x" : -5, "y":12}. Use only those keys with "action" being one of "Revelation", "Bless" or "Curse" and then the coordinates of where to apply it.`;

    if (lastAction) {
        prompt += ` Your last move was ${lastAction} and you cannot play the same action twice in a row, so you must choose a different action this time.`;
    }

    prompt += ` The creatures currently alive are :- ${JSON.stringify(creatures)}`;

    try {
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