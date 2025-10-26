# ICA25 Origins - Repository Analysis

## Project Overview

**ICA25 Origins** is an experimental AI competition game where different Large Language Models (LLMs) compete as deities trying to gain followers among simulated creatures in a 3D environment.

## Repository Structure

```
ica25-origins/
├── index.html              # Main game interface (33KB)
├── keyst.png              # Control key reference image (29KB)
├── ajaxgod1.php           # Gemini AI integration
├── ajaxgod2.php           # Claude AI integration
├── ajaxgod3.php           # Random AI (Grok placeholder)
├── ajaxgod4.php           # GPT integration
├── Game_Mechanics_-_MVP.txt           # Detailed game design v1
├── Game_Mechanics_-_MVP_v2_-_No_Scheme.txt  # Simplified game design v2
└── README.md              # Basic access check
```

## Technology Stack

### Frontend
- **Three.js** (v0.172.0) - 3D graphics rendering
- **Cannon-es** (v0.20.0) - Physics engine
- **GSAP** (v3.12.2) - Animation library
- Vanilla JavaScript (ES6 modules)

### Backend
- **PHP** - Server-side API endpoints for AI integrations
- **cURL** - HTTP requests to AI APIs

### AI Models
1. **Gemini 2.0 Flash** (Google)
2. **Claude 3 Opus** (Anthropic)
3. **Random AI** (placeholder for Grok)
4. **GPT** (OpenAI/Groq)

## Game Concept

### Core Gameplay
The game is a real-time strategy simulation where:
- AI models act as competing deities
- Creatures populate a 3D terrain (128x128 grid, -64 to +64 coordinates)
- Each AI tries to gain followers through strategic actions
- Creatures have health, faith, breed capability, and devotion attributes

### Deity Actions (AI Moves)
Each AI can perform one of three actions per turn:

1. **Revelation** - Shine light at coordinates to convert nearby Agnostic creatures
2. **Bless** - Spawn resources (targets/food) to boost creature health in an area
3. **Curse** - Spawn dangers that damage creatures in an area

**Rule**: Same action cannot be repeated consecutively

### Creature System

#### Attributes
- **Health (HP)**: 30-40 at spawn, max 100, dies at ≤0
- **Faith**: Agnostic → Gemini/Claude/Grok/GPT (one-time conversion)
- **Breed**: 0-100 (fertility)
- **Devotion**: 0-100 (religious dedication)

#### Behaviors
- **Pursue**: Hunt for resource targets
- **Evade**: Flee from nearby creatures
- **Rest**: Idle state

#### Visual Features
- Humanoid 3D models with:
  - Spherical head with eyes and mouth
  - Cylindrical body with customizable texture
  - Articulated limbs (arms/legs with hands/feet)
  - Glowing halo displaying faith color
  - Size scales with growth

### Environment

#### Terrain
- Procedurally generated using Perlin-style noise
- Height-mapped plane with physics collision
- Green grass material with flat shading
- Dynamic shadows and lighting

#### Physics
- Gravity: -9.82 m/s²
- Collision detection between creatures, targets, and dangers
- Dynamic objects (targets/dangers) drop from height and bounce
- Terrain collision with heightfield shape

#### Resources
- **Targets** (cubes): Increase creature health and size when collected
- **Dangers** (tetrahedrons): Deal damage based on proximity
- Both despawn when leaving boundary or expiring

## Technical Implementation

### 3D Rendering (index.html)

#### Scene Setup
```javascript
- Camera: PerspectiveCamera (75° FOV)
- Renderer: WebGL with antialiasing
- Shadows: PCF soft shadows enabled
- Background: #3B3B5F (purple-blue)
```

#### Lighting
- Ambient light (0.3 intensity)
- Directional light with shadows

#### Controls
- **W/S**: Move forward/backward
- **A/D**: Rotate camera left/right
- **Q/Z**: Rise/drop camera
- **Mouse drag**: Rotate view
- **Scroll**: Zoom in/out
- **Double-click**: Spawn target/danger
- **Spacebar**: Toggle target/danger spawn mode

### AI Integration Architecture

#### Request Flow
```
1. Frontend collects creature states every 5 seconds
2. JavaScript sends AJAX request to PHP endpoint
3. PHP formats prompt with game state
4. PHP calls respective AI API
5. AI returns JSON action
6. PHP validates and forwards response
7. Frontend executes action in game
```

#### Creature State Format
```
"x~y~health~faithIndex" separated by ^
Example: "12.5~-3.2~85~2^-5.1~8.7~42~0"
```

#### AI Prompt Structure (Common Template)
```
You are competing against other LLMs in a highly competitive game...
- Board: -64 to +64 in X and Y
- Your followers have faith = '[AIName]'
- Agnostics are unconverted
- Rival AIs are threats
Actions: Revelation/Bless/Curse
Response: JSON only, format {"action":"...", "x":..., "y":...}
Cannot repeat last action: [lastAction]
Current creatures: [JSON array]
```

### Color Coding (HSV System)
```javascript
gods = ['Agnostic', 'Gemini', 'Claude', 'Grok', 'GPT']
gcol = [0, 30, 120, 210, 300]  // Hue values
// 0=Red, 30=Orange, 120=Green, 210=Blue, 300=Magenta
```

### Game Loop
```
1. Physics simulation (60 FPS)
2. Update creature positions/states
3. Check collisions (targets, dangers)
4. Execute creature AI behaviors
5. Apply health decay
6. Render 3D scene
7. Every 5s: Request AI moves sequentially
8. Apply AI effects (revelation/bless/curse)
```

## Planned Features (from Game Mechanics docs)

### Extended Gameplay (Not Yet Implemented)
- **Church Construction**: First to 1000 HP donated wins
- **Breeding System**: Creatures reproduce (requires 70 HP, costs 35-50 HP)
- **Build Action**: Donate HP to church (risk of death at high devotion)
- **Gather Action**: Collect food to restore HP
- **Deity Modifiers**:
  - God 1: +25% health, -30% breed, health-focused
  - God 2: -20% health, +30% breed, reproduction-focused
  - God 3: Neutral/balanced

### Constraints (Planned)
- Min HP to survive: 20
- Min HP to gather: 20
- Max HP cap: 100
- Min HP to breed: 70
- Min HP to build: 70
- Cooldowns: 5-8 minutes after actions

## Current Implementation Status

### ✅ Implemented
- 3D environment with procedural terrain
- Creature rendering with humanoid models
- Physics simulation (gravity, collisions)
- Camera controls and mouse interaction
- AI API integrations (4 endpoints)
- Revelation effect (light beam + conversion)
- Bless effect (spawn targets)
- Curse effect (spawn dangers)
- Health system and damage
- Size scaling on resource collection
- Faith color indicators (halos)
- Action validation (no repeats)

### ⚠️ Partially Implemented
- Creature behaviors (pursue/evade/rest logic exists but simplified)
- Health decay (basic implementation)
- Target acquisition system

### ❌ Not Yet Implemented
- Church construction and victory condition
- Breeding mechanics
- Build/donation system
- Gather action with food resources
- Cooldown timers
- Deity-specific stat modifiers
- Breed and Devotion attribute effects
- Action probability system
- Offspring generation

## Security & Configuration Notes

### API Keys
All API keys in PHP files are placeholder/empty:
```php
$key='';  // Gemini key
'x-api-key: sk-ant-api03-AAA'  // Claude key (invalid)
$api_key='gsk_';  // Groq key
```
⚠️ **These need to be configured with valid keys for live deployment**

### PHP Issues
- `ajaxgod2.php` and `ajaxgod4.php` reference undefined `$gods` array (should be defined like in ajaxgod1.php)
- No input sanitization
- SSL verification disabled (`CURLOPT_SSL_VERIFYPEER => false`)
- File write operations without error handling

## Code Quality Observations

### Strengths
- Clean 3D graphics implementation
- Efficient physics integration
- Modular creature class design
- Flexible terrain generation
- Good visual feedback (halos, colors, shadows)

### Areas for Improvement
- Code organization (entire game in single HTML file)
- Missing error handling in AJAX responses
- No fallback for API failures
- Hardcoded values throughout
- Limited comments/documentation
- PHP code duplication between endpoints
- No server-side validation of AI responses

## Potential Enhancements

### Short-term
1. Fix undefined `$gods` variable in PHP files
2. Add proper API key configuration
3. Implement error handling for API calls
4. Add loading indicators during AI thinking
5. Display follower count for each deity
6. Add game state persistence

### Medium-term
1. Implement full breeding system
2. Add church construction mechanics
3. Create deity-specific visual effects
4. Add particle effects for actions
5. Implement statistics tracking
6. Create replay/spectator mode

### Long-term
1. Separate into modular JS files
2. Add multiplayer support
3. Create tournament system
4. Implement machine learning for creature behavior
5. Add custom deity creation
6. Build comprehensive analytics dashboard

## Game Balance Analysis

### Current State
- **Random AI (ajaxgod3.php)** has no strategic disadvantage since:
  - No resource scarcity
  - All actions equally valuable
  - No positioning strategy required
  
### Competitive Dynamics
- **Gemini**: Needs multi-line JSON parsing (currently fragile)
- **Claude**: Most reliable response format
- **Random**: Consistent but non-strategic
- **GPT**: Uses Groq API for faster responses

### Strategic Considerations
- Revelation near (0,0) reaches most creatures
- Blessing creates competition for resources
- Cursing risks collateral damage to own followers
- Timing matters: sequential AI turns create reactive gameplay

## Conclusion

**ICA25 Origins** is an ambitious experimental project that successfully creates a framework for AI-vs-AI competition in a simulated ecosystem. The current MVP focuses on the core conversion mechanics with simple blessing/cursing, while a more complex breeding and church-building system is documented but not yet implemented.

The project demonstrates:
- ✅ Real-time 3D simulation with physics
- ✅ Multi-AI integration architecture
- ✅ Engaging visual feedback
- ✅ Strategic decision-making framework

To reach the full vision outlined in the game design documents, significant development work remains on the breeding, building, and economic systems.

---

**Last Updated**: Based on repository state as of analysis date  
**Status**: Prototype/MVP stage  
**Playability**: Functional but incomplete game loop
