<!DOCTYPE html>
<html>
<head>
  <title>ICA25</title>
  <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
<script type="importmap">
  {
    "imports": {
      "three": "https://cdn.jsdelivr.net/npm/three@0.172.0/build/three.module.js",
      "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.172.0/examples/jsm/",
      "gsap": "https://cdn.jsdelivr.net/npm/gsap@3.12.2/+esm",
      "cannon-es": "https://cdn.jsdelivr.net/npm/cannon-es@0.20.0/dist/cannon-es.js"
    }
  }
</script>    
</head>
<body style="overflow:hidden; font-family:sans-serif">
<div style="position:fixed; top:1vh; left:1vw; width:6vw; background-color:transparent; z-index:10">
<img src="keyst.png" style="width:100%">
</div>
<div style="position:fixed; top:1vh; right:1vw; background-color:white; color:#222; border:1px solid #222; padding:5px; z-index:99; text-align:center">
<b>Information</b><br><br>
<div id="info"></div>
<br>
</div>

<div id="headdiv" style="position:fixed; top:3px; left:0px; width:100vw; color:yellow; font-weight:bold; text-align:center; font-size:2em; z-index:99"></div>
<script type="module">
import * as THREE from 'three';
import * as CANNON from "cannon-es";

const world = new CANNON.World({
  gravity: new CANNON.Vec3(0, -9.82, 0)
});

class TerrainGenerator {
  constructor(seed = 12345) {
    this.seed = seed;
  }

  hash(x, z) {
    let h = (x * 374761393 + z * 668265263 + this.seed) % 2147483647;
    h = (h ^ (h >> 13)) * 1274126177;
    return ((h ^ (h >> 16)) % 2147483647) / 2147483647;
  }

  // Smooth interpolation between values
  smoothstep(t) {
    return t * t * (3 - 2 * t);
  }

  // 2D noise function
  noise(x, z) {
    const ix = Math.floor(x);
    const iz = Math.floor(z);
    const fx = x - ix;
    const fz = z - iz;

    // Get corner values
    const a = this.hash(ix, iz);
    const b = this.hash(ix + 1, iz);
    const c = this.hash(ix, iz + 1);
    const d = this.hash(ix + 1, iz + 1);

    // Interpolate
    const i1 = a + this.smoothstep(fx) * (b - a);
    const i2 = c + this.smoothstep(fx) * (d - c);
    return i1 + this.smoothstep(fz) * (i2 - i1);
  }

  getHeight(x, z) {
    let height = 0;
    let amplitude = 100;  // Max height variation
    let frequency = 0.01; // How spread out features are
    
    // Layer multiple octaves for natural terrain
    for (let i = 0; i < 4; i++) {
      height += this.noise(x * frequency, z * frequency) * amplitude;
      amplitude *= 0.5;  // Each octave contributes less
      frequency *= 2;    // Each octave has finer detail
    }
    
    return height/6;
  }
}

const terrain = new TerrainGenerator(Math.random()*100);

const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(
  75, window.innerWidth / window.innerHeight, 0.1, 2000);
camera.position.set(6, 35.3, 50.8);
camera.lookAt(0, 0, 0);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setClearColor('#3B3B5F');
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
document.body.appendChild(renderer.domElement);

let tcounter = 0;
let dcounter=0;
let goodbad=0;  // 0 = good, 1 = bad
let gods=['Agnostic','Gemini','Claude','Grok','GPT'];
let gcol=[0,30,120,210,300];
let glast = ['','','','',''];
const size = 128;
const segments = 512;
const geometry = new THREE.PlaneGeometry(size, size, segments, segments);
geometry.rotateX(-Math.PI / 2); // rotate plane horizontal (XZ plane)

const positionAttribute = geometry.attributes.position;

for (let i = 0; i < positionAttribute.count; i++) {
  const x = positionAttribute.getX(i);
  const z = positionAttribute.getZ(i);
  positionAttribute.setY(i,terrain.getHeight(x, z));
}
geometry.computeBoundingSphere();
geometry.attributes.position.needsUpdate = true;

geometry.computeVertexNormals();

const material = new THREE.MeshStandardMaterial({
  color: 0x55bb2f,
  wireframe: false,
  flatShading: true,
side: THREE.DoubleSide
});

const ground = new THREE.Mesh(geometry, material);
scene.add(ground);
ground.receiveShadow = true;

const ambientLight = new THREE.AmbientLight(0xffffff, 0.3);
scene.add(ambientLight);

const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
directionalLight.position.set(50, 100, 50);
scene.add(directionalLight);
directionalLight.castShadow = true;

class RevLight {
 constructor(god,x,y) {
   this.god=god;
   const beamColor = new THREE.Color(0xffffff);
   const rgb = hsvToRgb(gcol[god], 0.9, 1, 1);
   beamColor.setRGB(rgb[0], rgb[1], rgb[2]);
     const geom = new THREE.ConeGeometry(5, 40, 32, 1, true); 
   const mat = new THREE.MeshBasicMaterial({
      color: beamColor,
      transparent: true,
      opacity: 0.25,
      depthWrite: false,
      side: THREE.DoubleSide,
      blending: THREE.AdditiveBlending
   });
   this.beam = new THREE.Mesh(geom, mat);
   this.beam.position.set(x, 30, y);
  
   this.intensity=0.5;
   this.startTime = Date.now();
   scene.add(this.beam);
 }
}

let revlights = [];
const elementSize = size / segments;
const heightMatrix = [];

for (let i = 0; i <= segments; i++) { // rows (z direction)
  const row = [];
  for (let j = 0; j <= segments; j++) { // columns (x direction)
    const idx = (segments-j) * (segments + 1) + i;
    row.push(positionAttribute.getY(idx));
  }
  heightMatrix.push(row);
}

const shape = new CANNON.Heightfield(heightMatrix, {
  elementSize: elementSize
});
const pgbody = new CANNON.Body({ mass: 0 }); // static body
pgbody.addShape(shape);

pgbody.position.set(-size / 2, 0, size / 2);    // adjust for centering/rotation
pgbody.quaternion.setFromEuler(-Math.PI / 2, 0, 0, "XYZ"); // rotate to XZ plane

world.addBody(pgbody);

class SpriteCreature extends THREE.Group {
  constructor(size = 1, x = 0, z = 0) {
    super();
    
    this.creatureSize = size;
    this.canvas = null;
    this.bodyTexture = null;
    this.bodyMaterial = null;
    this.staticMaterials = {};
    this.position.set(x,0,z);
    this.createMaterials();
    this.createCreature();
    this.scale.setScalar(size);
    this.cooldown = 0;
    this.lockedTarget = 0;
    this.currentProg = pursue;
    this.health = 100;
    this.faith = 0;
    
  }
  
  createMaterials() {
     this.canvas = document.createElement('canvas');
    this.canvas.width = 256;
    this.canvas.height = 256;
    
    const ctx = this.canvas.getContext('2d');
    this.drawBodyTexture(ctx);
    
     this.bodyTexture = new THREE.CanvasTexture(this.canvas);
    this.bodyTexture.generateMipmaps = false;
    this.bodyTexture.minFilter = THREE.LinearFilter;
    this.bodyTexture.magFilter = THREE.LinearFilter;
    
    this.bodyMaterial = new THREE.MeshLambertMaterial({
      map: this.bodyTexture,
      transparent: true,
      side: THREE.DoubleSide
    });
    
     this.staticMaterials = {
      head: new THREE.MeshLambertMaterial({
        color: 0xffb347, // Orange
        side: THREE.DoubleSide
      }),
      limbs: new THREE.MeshLambertMaterial({
        color: 0x8e44ad, // Purple
        side: THREE.DoubleSide
      }),
      hands: new THREE.MeshLambertMaterial({
        color: 0xffb347, // Same as head
        side: THREE.DoubleSide
      }),
      feet: new THREE.MeshLambertMaterial({
        color: 0x8e44ad, // Same as limbs
        side: THREE.DoubleSide
      })
    };
  }
  
  drawBodyTexture(ctx) {
     ctx.clearRect(0, 0, 256, 256);
     ctx.fillStyle='white';
     ctx.fillRect(0,0,256,256);     
   if (this.bodyTexture) 
      this.bodyTexture.needsUpdate = true;
  }
  
  createCreature() {
    const bodyGeometry = new THREE.CylinderGeometry(0.2, 0.6, 1.3, 32);
    const bodyMesh = new THREE.Mesh(bodyGeometry, this.bodyMaterial);
    bodyMesh.position.set(0, 0, 0);
    bodyMesh.name = 'body';
    bodyMesh.castShadow = true;
    this.add(bodyMesh);
    
    const headGeometry = new THREE.SphereGeometry(0.5, 12, 8);
    const headMesh = new THREE.Mesh(headGeometry, this.staticMaterials.head);
    headMesh.position.set(0, 1.1, 0);
    headMesh.name = 'head';
    headMesh.castShadow = true;
    this.add(headMesh);

    const haloGeometry = new THREE.TorusGeometry(0.4,0.05);
    const haloMaterial = new THREE.MeshStandardMaterial({
      color: 0xffff00, 
      transparent: true,
      opacity: 0
    });
    this.halo = new THREE.Mesh(haloGeometry, haloMaterial);
    this.halo.rotation.x = Math.PI / 2; 
    this.halo.position.y = 1.6;
    this.add(this.halo);
    
    this.addFaceFeatures(headMesh);
    

    const armGeometry = new THREE.CylinderGeometry(0.08, 0.08, 0.8, 6);
    
    const leftArm = new THREE.Mesh(armGeometry, this.staticMaterials.limbs);
    leftArm.position.set(-0.8, 0.2, 0);
    leftArm.rotation.z = -Math.PI * 0.4;
    leftArm.name = 'leftArm';
    leftArm.castShadow = true;
    this.add(leftArm);
    
    const rightArm = new THREE.Mesh(armGeometry, this.staticMaterials.limbs);
    rightArm.position.set(0.8, 0.2, 0);
    rightArm.rotation.z = Math.PI * 0.4;
    rightArm.name = 'rightArm';
    rightArm.castShadow = true;

    this.add(rightArm);
    
    // Hands (small spheres) - attached to arms
    const handGeometry = new THREE.SphereGeometry(0.12, 8, 6);
    
    const leftHand = new THREE.Mesh(handGeometry, this.staticMaterials.hands);
    leftHand.position.set(0, -0.5, 0); // Relative to arm center
    leftHand.name = 'leftHand';
    leftHand.castShadow = true;

    leftArm.add(leftHand); // Make hand a child of arm
    
    const rightHand = new THREE.Mesh(handGeometry, this.staticMaterials.hands);
    rightHand.position.set(0, -0.5, 0); // Relative to arm center
    rightHand.name = 'rightHand';
    rightHand.castShadow = true;

    rightArm.add(rightHand); // Make hand a child of arm
    
    // Legs (thin cylinders) - SOLID COLOR
    const legGeometry = new THREE.CylinderGeometry(0.1, 0.08, 1.0, 6);
    
    const leftLeg = new THREE.Mesh(legGeometry, this.staticMaterials.limbs);
    leftLeg.position.set(-0.3, -0.9, 0);
    leftLeg.name = 'leftLeg';
    leftLeg.castShadow = true;
    this.add(leftLeg);
    
    const rightLeg = new THREE.Mesh(legGeometry, this.staticMaterials.limbs);
    rightLeg.position.set(0.3, -0.9, 0);
    rightLeg.name = 'rightLeg';
    rightLeg.castShadow = true;
    this.add(rightLeg);
    
    // Feet (small spheres) - attached to legs
    const footGeometry = new THREE.SphereGeometry(0.15, 8, 6);
    
    const leftFoot = new THREE.Mesh(footGeometry, this.staticMaterials.feet);
    leftFoot.position.set(0, -0.6, 0); // Relative to leg center
    leftFoot.name = 'leftFoot';
    leftFoot.castShadow = true;
    leftLeg.add(leftFoot); // Make foot a child of leg
    
    const rightFoot = new THREE.Mesh(footGeometry, this.staticMaterials.feet);
    rightFoot.position.set(0, -0.6, 0); // Relative to leg center
    rightFoot.name = 'rightFoot';
    rightFoot.castShadow = true;
    rightLeg.add(rightFoot); // Make foot a child of leg
  }
  
  addFaceFeatures(headMesh) {
    const eyeGeometry = new THREE.SphereGeometry(0.05, 6, 4);
    const eyeMaterial = new THREE.MeshLambertMaterial({ color: 0x2c3e50, side: THREE.DoubleSide });
    
    const leftEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
    leftEye.position.set(-0.15, 0.15, 0.4);
    headMesh.add(leftEye);
    
    const rightEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
    rightEye.position.set(0.15, 0.15, 0.4);
    headMesh.add(rightEye);
    
    // Eye highlights
    const highlightGeometry = new THREE.SphereGeometry(0.02, 4, 3);
    const highlightMaterial = new THREE.MeshLambertMaterial({ color: 0xffffff, side: THREE.DoubleSide });
    
    const leftHighlight = new THREE.Mesh(highlightGeometry, highlightMaterial);
    leftHighlight.position.set(-0.12, 0.18, 0.42);
    headMesh.add(leftHighlight);
    
    const rightHighlight = new THREE.Mesh(highlightGeometry, highlightMaterial);
    rightHighlight.position.set(0.18, 0.18, 0.42);
    headMesh.add(rightHighlight);
    
    // Mouth (small cylinder)
    const mouthGeometry = new THREE.CylinderGeometry(0.03, 0.03, 0.15, 6);
    const mouthMaterial = new THREE.MeshLambertMaterial({ color: 0x2c3e50, side: THREE.DoubleSide });
    const mouth = new THREE.Mesh(mouthGeometry, mouthMaterial);
    mouth.position.set(0, 0.05, 0.4);
    mouth.rotation.z = Math.PI / 2;
    headMesh.add(mouth);
  }
  
  // Method to update only the body texture (clothing)
  updateBodyTexture() {
    const ctx = this.canvas.getContext('2d');
    this.drawBodyTexture(ctx);
  }
  
  // Method to draw custom clothing/patterns on the body
  drawCustomBodyPattern(drawFunction) {
    const ctx = this.canvas.getContext('2d');
    ctx.clearRect(0, 0, 256, 256);
    
    // Call the custom drawing function with the context
    drawFunction(ctx);
    
    this.bodyTexture.needsUpdate = true;
  }
  
  setPartColor(partName, color) {
    if (this.staticMaterials[partName]) {
      this.staticMaterials[partName].color.setHex(color);
    }
  }
  
  setSize(newSize) {
    this.creatureSize = newSize;
    this.scale.setScalar(newSize);
  }
  
   dispose() {
    this.traverse(child => {
      if (child.geometry) {
        child.geometry.dispose();
      }
    });
    
    Object.values(this.staticMaterials).forEach(material => {
      material.dispose();
    });
    
    if (this.bodyMaterial) {
      this.bodyMaterial.dispose();
    }
    
    if (this.bodyTexture) {
      this.bodyTexture.dispose();
    }
  }

  moveToward(x, z, speed) {
    const dx = x - this.position.x;
    const dz = z - this.position.z;
    let ang = Math.atan2(dz,dx);
    this.position.x += speed * Math.cos(ang);
    this.position.z += speed * Math.sin(ang);
  }

  moveAway(x, z, speed) {
    const dx = x - this.position.x;
    const dz = z - this.position.z;
    let ang = Math.atan2(dz,dx);
    this.position.x -= speed * Math.cos(ang);
    this.position.z -= speed * Math.sin(ang);
  }

}

let creatures = [];
let me;
for (me=0; me<5; me++) {
 let c=new SpriteCreature(1.0, Math.random()*10-5, Math.random()*8 - 4);
 creatures.push(c); 
 c.currentProg = getProgram();
 c.castShadow = true;
 scene.add(c);
}


const gbox = new THREE.BoxGeometry(1, 1, 1);
const mbox = new THREE.MeshStandardMaterial({
  color: 0x44aa88,
  side: THREE.DoubleSide
});

const pgroundMaterial = new CANNON.Material('ground');
const pboxMaterial = new CANNON.Material('box');
const dboxMaterial = new CANNON.Material('danger');

class Target {
   constructor(x,y) {
     this.box = new THREE.Mesh(gbox,mbox);
     this.box.castShadow = true;
     this.box.receiveShadow = true;
     this.box.position.set(x,35,y);
     this.number = ++tcounter;
     this.captured=0;
     scene.add(this.box);
     const halfExtents = new CANNON.Vec3(0.5, 0.5, 0.5); // Adjust if your box is different size
     this.body = new CANNON.Body({
       mass: 10, // make it dynamic
       shape: new CANNON.Box(halfExtents)
     });
     this.body.position.set(x, 35, y);
     this.body.material = pboxMaterial;
     world.addBody(this.body);


   }
}

pgbody.material = pgroundMaterial;

const contactMaterial = new CANNON.ContactMaterial(pgroundMaterial, pboxMaterial, {
  friction: 0.1,
  restitution: 0.5
});
world.addContactMaterial(contactMaterial);

const gdan = new THREE.TetrahedronGeometry(1, 0);
const mdan = new THREE.MeshStandardMaterial({
  color: 0xff0000,
  emissive: 0x330000,
  emissiveIntensity: 0.5,
  metalness: 0.3,
  roughness: 0.4,
  side: THREE.DoubleSide,
  transparent: true,
  opacity:1
});

const positions = gdan.attributes.position.array;
const rawVerts = [];
for (let i = 0; i < positions.length; i += 3) {
  rawVerts.push(new CANNON.Vec3(positions[i], positions[i+1], positions[i+2]));
}
const uniqueVerts = [];
const vertMap = [];
const threshold = 1e-6; // tolerance for floating-point comparison

function isEqual(v1, v2) {
  return v1.distanceTo(v2) < threshold;
}

rawVerts.forEach((v, idx) => {
  let foundIndex = uniqueVerts.findIndex(u => isEqual(u, v));
  if (foundIndex === -1) {
    uniqueVerts.push(v);
    vertMap[idx] = uniqueVerts.length - 1;
  } else {
    vertMap[idx] = foundIndex;
  }
});
const faces = [];
for (let i = 0; i < rawVerts.length; i += 3) {
  faces.push([
    vertMap[i],
    vertMap[i+1],
    vertMap[i+2]
  ]);
}

const tetraShape = new CANNON.ConvexPolyhedron({ vertices: uniqueVerts, faces });

class Danger {
   constructor(x,y) {
     this.box = new THREE.Mesh(gdan,mdan);
     this.box.castShadow = true;
     this.box.receiveShadow = true;
     this.box.position.set(x,35,y);
     this.number = ++dcounter;
     this.strength = 1;
     this.body = new CANNON.Body({mass: 10});
     this.body.addShape(tetraShape);
     this.body.position.set(x, 35, y);
     this.body.material = dboxMaterial;
 
     world.addBody(this.body);
     scene.add(this.box);
   }
}

let targets=new Map();
let dangers =new Map();
for (let e=0; e<3; e++) {
  let t = new Target(Math.random()*40-20,Math.random()*40-20);
  targets.set(t.number,t);
}

const contactMaterial2 = new CANNON.ContactMaterial(pgroundMaterial, dboxMaterial, {
  friction: 0.1,
  restitution: 0.5 // increase for bouncier impacts
});
world.addContactMaterial(contactMaterial2);

var keydown=[0,0,0,0,0,0,0,0,0,0,0,0];
                      
let twopi = Math.PI * 2;
let ninety = Math.PI * 0.5;

var theta = -1.6802522945531422;
var stheta = theta;
var left=0, ltop=0, mousepressed=0;
var yang = -0.6043825866906077;
var syang = yang;
var spd = 0.5, spd2=0.2;
var wiw = window.innerWidth;
var wih = window.innerHeight;
var overscroll = 0;

function nearest(limit) {
  let mn = limit*limit;
  let index=-1;
  for (var e=0; e< creatures.length; e++) 
  if (!(e==me)) {
    let dst=(creatures[e].position.x - creatures[me].position.x)*(creatures[e].position.x - creatures[me].position.x)+(creatures[e].position.z - creatures[me].position.z)*(creatures[e].position.z - creatures[me].position.z);
    if (dst<mn) {
      index=e;
      mn=dst;
    }
  }
  return index;
}

function evade() {
  let ind = nearest(5);
  if (ind>=0) {
    creatures[me].moveAway(creatures[ind].position.x,creatures[ind].position.z,0.1);
  }
}

function pursue() {
  if (!creatures[me].lockedTarget) creatures[me].lockedTarget=findTarget();
  if (creatures[me].lockedTarget) {
    let t = targets.get(creatures[me].lockedTarget);
    if (t === undefined) 
       creatures[me].lockedTarget=0;
    else {
    creatures[me].moveToward(t.box.position.x,t.box.position.z,0.1);
    creatures[me].rotation.y = Math.atan2(t.box.position.z - creatures[me].position.z,t.box.position.x - creatures[me].position.x);
    if (targets.get(creatures[me].lockedTarget).captured>0) creatures[me].lockedTarget=0;
    }
  }
}

function findTarget() {
  let mn = 9999999;
  let index=0;
  targets.forEach((target, key) => {
   if (target.captured<0.5) {
    let dst=(target.box.position.x - creatures[me].position.x)*(target.box.position.x - creatures[me].position.x)+(target.box.position.z - creatures[me].position.z)*(target.box.position.z - creatures[me].position.z);
    if (dst<mn) {
      index=key;
      mn=dst;
    }
   }
  });
  return index;
}

function haverest() {
}
 
function getProgram() {
  let r = Math.random();
  creatures[me].cooldown=250+Math.random()*200;
  if (creatures[me].health<25) r+=0.4;
  if (r<0.2) {
     creatures[me].cooldown = 200;
     return haverest;
  }
  if (r<0.5) return evade;
  return pursue;
}

const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

renderer.domElement.addEventListener('dblclick', function(event) {
  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
  raycaster.setFromCamera(mouse, camera);
  const intersects = raycaster.intersectObject(ground);
  if (intersects.length > 0) {
    const p = intersects[0].point;
    if (goodbad) {
      let t = new Danger(p.x,p.z);
      dangers.set(t.number,t);
    }
    else {
      let t = new Target(p.x,p.z);
      targets.set(t.number,t);
    }
  }
});

document.addEventListener('keydown', (e) => {    
    let keynum=0;
    if(window.event) keynum = e.keyCode;
    else keynum = e.which;
    switch (keynum) {
         case 65 : keydown[0]=1; break;
         case 68 : keydown[1]=1; break;
         case 87 : keydown[2]=1; break;
         case 83 : keydown[3]=1; break;
         case 81 : keydown[4]=1; break;
         case 90 : keydown[5]=1; break;
         case 32 : goodbad = 1-goodbad; break;
         case 84 :
            for (let i=0; i<creatures.length; i++) {
              console.log('pos='+creatures[i].position.x.toFixed(1)+','+creatures[i].position.z.toFixed(1)+' health='+creatures[i].healath);

            };

           break;
         default : console.log(camera.position);
                   console.log('theta='+theta);
                   console.log('yang='+yang);
       }
 },false);

document.addEventListener('keyup', (e) => {    
    let keynum=0;
    if(window.event) keynum = e.keyCode;
    else keynum = e.which;
    switch (keynum) {
         case 65 : keydown[0]=0; break;
         case 68 : keydown[1]=0; break;
         case 32 :
         case 87 : keydown[2]=0; break;
         case 83 : keydown[3]=0; break;
         case 81 : keydown[4]=0; break;
         case 90 : keydown[5]=0; break;
       }
 },false);


   if (document.addEventListener) {
        // For modern browsers
        renderer.domElement.addEventListener("wheel", handleScroll);
    } else {
        // For old IE browsers
        renderer.domElement.attachEvent("onmousewheel", handleScroll);
    }

   function handleScroll(event) {
      var delta = event.deltaY || event.detail || event.wheelDelta;
      if (delta<0) overscroll=-1;
      else overscroll=1;
   }

renderer.domElement.addEventListener('mousedown', (event) => {
  var e = event ? event : window.event;
  e.preventDefault();
  left = e.clientX;
  ltop = e.clientY;
  stheta = theta;
  syang = yang;
  document.addEventListener('mousemove', handleDrag);
  document.addEventListener('mouseup', stopDragging);
  document.addEventListener('mouseleave', stopDragging);
  mouse.x = (left / window.innerWidth) * 2 - 1;
  mouse.y = -(ltop / window.innerHeight) * 2 + 1;
  raycaster.setFromCamera(mouse, camera);
  const intersects = raycaster.intersectObjects(creatures, true);
  if (intersects.length > 0) {
    const hit = intersects[0].object;
    let root = hit;
    while (root.parent && !(root instanceof SpriteCreature)) root = root.parent;
    const index = creatures.indexOf(root);
    if (index !== -1) showCreature(index);
  }

 },false);


function stopDragging(event) {
    document.removeEventListener('mousemove', handleDrag);
    document.removeEventListener('mouseup', stopDragging);
    document.removeEventListener('mouseleave', stopDragging);

    if (Math.abs(theta-stheta)<0.001)
     if (Math.abs(yang-syang)<0.001) {
        var mouse = {
        x: (event.clientX / renderer.domElement.clientWidth) * 2 - 1,
        y: -(event.clientY / renderer.domElement.clientHeight) * 2 + 1,
      };
    }
}

function handleDrag(event) {
   var e = event ? event : window.event;
   e.preventDefault();
   var x= e.clientX - left;
   var y= e.clientY - ltop;
   theta = stheta + (x/wiw) * Math.PI;
   yang = syang - (y/wih) * ninety;
}

function showCreature(a) {
  var txt;
  switch (creatures[a].currentProg) {
    case pursue: txt='Pursue'; break;
    case evade: txt='Evade'; break;
    case haverest: txt='Rest'; break;
    default: txt='Custom';
  }
  document.getElementById('info').innerHTML='Creature #'+a+'<br><br>Current Function:<br>'+txt+'<br><br>Health: '+ creatures[a].health.toFixed(1)+'<br><br>Faith: '+gods[creatures[a].faith];
}

function hsvToRgb(h, s, v, raw) {
    let c = v * s;
    let x = c * (1 - Math.abs((h / 60) % 2 - 1));
    let m = v - c;
    let r = 0, g = 0, b = 0;

    if (h >= 0 && h < 60) { r = c; g = x; b = 0; }
    else if (h < 120) { r = x; g = c; b = 0; }
    else if (h < 180) { r = 0; g = c; b = x; }
    else if (h < 240) { r = 0; g = x; b = c; }
    else if (h < 300) { r = x; g = 0; b = c; }
    else { r = c; g = 0; b = x; }

  if (raw) {
     r+=m;
     g+=m;
     b+=m;
     return [r.toFixed(3),g.toFixed(3),b.toFixed(3)];
  }

    r =  Math.round((r + m) * 255);
    g = Math.round((g + m) * 255);
    b = Math.round((b + m) * 255);

    return `rgb(${r},${g},${b})`;
}


function envclamp(a) {
 if (a>63.5) return 63.5;
 if (a<-63.5) return -63.5;
 return a;
}

function animate() {
  world.step(1/60);
  targets.forEach((target,key) => {
     target.box.position.copy(target.body.position);
     target.box.quaternion.copy(target.body.quaternion);
     if (Math.abs(target.body.position.x)>64) target.captured=1;
     if (Math.abs(target.body.position.z)>64) target.captured=1;
  });
  dangers.forEach((target,key) => {
     target.box.position.copy(target.body.position);
     target.box.quaternion.copy(target.body.quaternion);
     target.strength-=0.002;
     if (target.strength>0) 
       target.box.material.opacity = target.strength;
     else {
       scene.remove(target.box);
       dangers.delete(key);
     }
  });

  let now = Date.now();
  let rs = revlights.length;
  let takeout=-1;
  for (let i=0;  i<rs; i++) {
   let r = revlights[i];
   let el = (now - r.startTime) * 0.001;
   if (el<0.5) r.intensity = 0.5 + el*2;
   else r.intensity = 1 - (el-0.5)*0.33;
   r.beam.material.opacity = r.intensity;
   if (r.intensity<=0) 
      takeout=i;
   else {
     let fac = r.intensity/30;
     for (let e=0; e<5; e++)
     if (creatures[e].health>0) { 
       let ce = creatures[e];
       let h = Math.hypot(r.beam.position.x - ce.position.x, r.beam.position.z - ce.position.z);
       if (h<5) {
          fac = r.intensity * (6-h) / 150;
          if (ce.faith != r.god) 
          if (Math.random()<fac) {
            ce.faith = r.god;
            ce.halo.material.opacity=1;
            ce.halo.material.color = new THREE.Color(hsvToRgb(gcol[r.god],1,1,0));
          }
          let ctx = ce.canvas.getContext('2d');
          let ang = (now/478) % 6.283;
          let dst = Math.pow(Math.sin(now/333),2);
          let x = 127 + Math.round(160 * dst * Math.cos(ang));
          let y = 127 + Math.round(160 * dst * Math.sin(ang));
          if ((x>=0) && (x<256) && (y>=0) && (y<256)) {
            let rgb = hsvToRgb(gcol[r.god],1,1,1);
            let a  = 100*(1-Math.pow(h/5,3));
            ctx.beginPath();
            ctx.fillStyle='rgba('+Math.round(rgb[0]*255)+','+Math.round(rgb[1]*255)+','+Math.round(rgb[2]*255)+','+a+')';
            ctx.arc(x,y,3*(5-h),0,twopi);
            ctx.closePath();
            ctx.fill();
            ce.bodyTexture.needsUpdate = true;
          }
       }
     }
   }
  }
  if (takeout>=0) {
    scene.remove(revlights[takeout].beam);
    revlights.splice(takeout,1);
  }
  for(me=0; me<creatures.length; me++) 
  if (creatures[me].health>0) {
    let c = creatures[me];
    c.currentProg();
    c.position.x = envclamp(c.position.x);
    c.position.z = envclamp(c.position.z);

    c.position.y=1.2 * c.creatureSize + terrain.getHeight(c.position.x,c.position.z);
    targets.forEach((target, key) => {
     if (!target.captured) {
      if (Math.abs(c.position.x - target.box.position.x)<1)
      if (Math.abs(c.position.y - target.box.position.y)<2)
      if (Math.abs(c.position.z - target.box.position.z)<1) {
        c.creatureSize *=1.1;
        c.health+=20;
        target.captured=1;
        c.setSize(c.creatureSize);
        c.lockedTarget=0;
        c.currentProg = getProgram();
        const startPos = target.box.position;
        const targetPos = new THREE.Vector3(0, 0, 0); // center of environment
        const horizontalDir = new THREE.Vector3(-startPos.x, 0, -startPos.z).normalize();
        const horizontalDistance = new THREE.Vector3(startPos.x, 0, startPos.z).length();
        const verticalSpeed = 10;
        const horizontalSpeed = horizontalDistance / 2; // can tune this
        const impulse = new CANNON.Vec3(horizontalDir.x * horizontalSpeed,verticalSpeed,horizontalDir.z * horizontalSpeed);
        target.body.velocity.set(impulse.x, impulse.y, impulse.z);
      }
     }
     
   });
   dangers.forEach((danger,key) => {
      if (Math.abs(c.position.x - danger.box.position.x)<3)
      if (Math.abs(c.position.y - danger.box.position.y)<3)
      if (Math.abs(c.position.z - danger.box.position.z)<3) {
         var dst = 1+Math.hypot(c.position.x - danger.box.position.x,c.position.y - danger.box.position.y);
         c.health -= 6 * danger.strength / dst;
      }
   });
   if (--c.cooldown<=0) c.currentProg = getProgram();
   c.health-=0.03 * Math.sqrt(c.creatureSize);
   if (c.health<=0) scene.remove(c);
//   else c.drawCustomBodyPattern(healthcol);
  }
   if (keydown[1]) {
     theta+=0.015;
   }
   if (keydown[0]) {
      theta-=0.015;
   }
   if (keydown[2])  {
      camera.position.z+=spd2*Math.sin(theta) * Math.cos(yang);
      camera.position.x+=spd2*Math.cos(theta) * Math.cos(yang);
      camera.position.y+=spd2*Math.sin(yang);
   }
   if (overscroll<0) {
      camera.position.z+=spd*Math.sin(theta) * Math.cos(yang);
      camera.position.x+=spd*Math.cos(theta) * Math.cos(yang);
      camera.position.y+=spd*Math.sin(yang);
   }
   if (keydown[3]) {
      camera.position.z-=spd2*Math.sin(theta) * Math.cos(yang);
      camera.position.x-=spd2*Math.cos(theta) * Math.cos(yang);
      camera.position.y-=spd2 * Math.sin(yang);
   }
   if (overscroll>0) {
      camera.position.z-=spd*Math.sin(theta) * Math.cos(yang);
      camera.position.x-=spd*Math.cos(theta) * Math.cos(yang);
      camera.position.y-=spd*Math.sin(yang);
   }
   overscroll=0;

   if (keydown[4]) {
      camera.position.y+=spd2;
   }

   if (keydown[5]) {
      camera.position.y-=spd2;
   }

   camera.lookAt(camera.position.x + 0.15*Math.cos(theta)*Math.cos(yang),camera.position.y + 0.15*Math.sin(yang),camera.position.z + 0.15*Math.sin(theta)*Math.cos(yang));

  requestAnimationFrame(animate);
  renderer.render(scene, camera);
}

var xmlhttp;
var uptoAI=3;

function askGod()
  {  if (++uptoAI > 4) uptoAI=1;

     let l = creatures.length;
     let summ=[];
     for (let i=0; i<l; i++) {
       let m = creatures[i]; 
       if (m.health>0) 
         summ.push(""+m.position.x.toFixed(2)+"~"+m.position.z.toFixed(2)+"~"+m.health.toFixed(2)+'~'+m.faith);
     }
      xmlhttp=GetXmlHttpObject();
      var url="ajaxgod"+uptoAI+".php?lst="+glast[uptoAI]+"&s="+summ.join('^');
      switch (uptoAI) {
        case 1 : xmlhttp.onreadystatechange=stateChanged1; break;
        case 2 : xmlhttp.onreadystatechange=stateChanged2; break;
        case 3 : xmlhttp.onreadystatechange=stateChanged3; break;
        case 4 : xmlhttp.onreadystatechange=stateChanged4; break;
      }
      xmlhttp.open("GET",url,true);
      xmlhttp.send(null);
      setTimeout(askGod,5000);
  }

function GetXmlHttpObject()
{
if (window.XMLHttpRequest)

  return new XMLHttpRequest();
if (window.ActiveXObject)
  return new ActiveXObject("Microsoft.XMLHTTP");
return null;
}

function stateChanged1()
{
if (xmlhttp.readyState==4)
  {
     var v=xmlhttp.responseText;
     var j  = JSON.parse(v);
     if (j.success === true) 
       doGodsWork(1,JSON.parse(j.message));  
  }
}

function stateChanged2()
{
if (xmlhttp.readyState==4)
  {
     var v=xmlhttp.responseText;
     var j  = JSON.parse(v);
     if (j.success === true) 
       doGodsWork(2,JSON.parse(j.message));  
  }
}

function stateChanged3()
{
if (xmlhttp.readyState==4)
  {
     var v=xmlhttp.responseText;
     var j  = JSON.parse(v);
     if (j.success === true) 
       doGodsWork(3,JSON.parse(j.message));  
  }
}

function stateChanged4()
{
if (xmlhttp.readyState==4)
  {
     var v=xmlhttp.responseText;
     var j  = JSON.parse(v);
     if (j.success === true) 
       doGodsWork(4,JSON.parse(j.message));  
  }
}

function doGodsWork(god,m) {
        headdisplay(gods[god]+' plays '+m.action,god);
        glast[god]=m.action;
        if (m.action == 'Revelation') {
            revlights.push(new RevLight(god,m.x,m.y));
            console.log(revlights);
        }
        if (m.action == 'Bless') {
          for (let i=0; i<3; i++) {
           let t = new Target(m.x + Math.random()-0.5,m.y+Math.random()-0.5);
           targets.set(t.number,t);
          }
        }
        if (m.action== 'Curse') {
          let t = new Danger(m.x,m.y);
          dangers.set(t.number,t);
        } 
        console.log(gods[god]+' => '+JSON.stringify(m));
 
}

var headop=2;

function headdisplay(s,g) {
  let d = document.getElementById('headdiv');
  d.style.color = hsvToRgb(gcol[g],1,1,0);
  d.innerHTML=s;
  d.style.opacity=1;
  headop=2;
  setTimeout(lowhead,50);
}

function lowhead() {
  headop-=0.02;
  if (headop>=0) {
     document.getElementById('headdiv').style.opacity= Math.min(1,headop);
     setTimeout(lowhead,50);
  }
}
  
setTimeout(askGod,2000);

animate();

window.addEventListener('resize', () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();

  renderer.setSize(window.innerWidth, window.innerHeight);
});

 
</script>
</body>
</html>
