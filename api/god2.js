export default async function handler(req, res) {
    const { creatures, lastAction } = req.query;

    res.json({
        apiKey: process.env.ANTHROPIC_API_KEY,
        creatures,
        lastAction
    });
}