<!DOCTYPE html>
<html>
<head>
  <title>ICA25</title>
  <meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
<script type="importmap">
  {
    "imports": {
      "three": "https://cdn.jsdelivr.net/npm/three@0.172.0/build/three.module.js",
         "gsap": "https://cdn.jsdelivr.net/npm/gsap@3.12.2/+esm",
      "cannon-es": "https://cdn.jsdelivr.net/npm/cannon-es@0.20.0/dist/cannon-es.js"
    }
  }
</script>    
</head>
<body style="overflow:hidden; font-family:sans-serif">
<div style="position:fixed; top:1vh; right:1vw; background-color:white; color:#222; border:1px solid #222; padding:5px; z-index:99; text-align:center">
<b>Information</b><br><br>
<div id="info"></div>
<br>
</div>

<div id="headdiv" style="position:fixed; top:3px; left:0px; width:100vw; color:yellow; font-weight:bold; text-align:center; font-size:2em; z-index:99"></div>
<div style="position:fixed; bottom:0px; left:0px; width:100vw; color:white; background-color:transparent" id="debug"></div>
<script type="module">
import * as THREE from 'three';
import * as CANNON from "cannon-es";
  import * as BufferGeometryUtils from 'https://cdn.jsdelivr.net/npm/three@0.172.0/examples/jsm/utils/BufferGeometryUtils.js';

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
    
    return height/7;
  }
}

const terrain = new TerrainGenerator(Math.random()*100);

const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(
  45, window.innerWidth / window.innerHeight, 0.1, 2000);
camera.position.set(6, 36, 73);
camera.lookAt(0, 0, 0);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setClearColor('#66B3FF');
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
document.body.appendChild(renderer.domElement);

let tcounter = 0;
let dcounter=0, ccounter=0;
let goodbad=0;  // 0 = good, 1 = bad
let gods=['Agnostic','Gemini','Claude','Amazon-Nova','GPT'];
let gcol=[0,60,300,240];
let glast = ['','','','',''];
const size = 128;
const segments = 512;
let me =0;
let cme = null;
window.cme = cme;
let savedir=0;
let trackme = 0;
let gamepause = 0;

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

const stem = new THREE.CylinderGeometry(0.04, 0.04, 1, 6);
stem.translate(0, 0.5, 0);
const stem2 = new THREE.CylinderGeometry(0.04,0.04,0.7071,6);
stem2.rotateX(Math.PI/4);
stem2.translate(0,1.25,0.25);
const stem3 = new THREE.ConeGeometry(0.2,0.5,8,1,true,0,6.283185);
stem3.translate(0,0.2,0);
const leaf1 = new THREE.SphereGeometry(0.2, 8, 8);
leaf1.scale(1.5, 0.15, 0.5);            // flatten vertically
leaf1.rotateZ(-Math.PI / 4);          // tilt upward to the left
leaf1.translate(-0.2,1.2,0);

const leaf2 = new THREE.SphereGeometry(0.2, 8, 8);
leaf2.scale(1.5, 0.15, 0.5);
leaf2.rotateZ(Math.PI / 4);           // tilt upward to the right
leaf2.translate(0.2, 1.2, 0);
const center = new THREE.SphereGeometry(0.15, 8, 8);
center.translate(0, 1.6, 0.6);
const petalCount = 12;
const petal = new THREE.ConeGeometry(0.05, 0.3, 6);

petal.rotateZ(Math.PI / 2);
petal.translate(0.15, 0, 0); 
const petalGeos = [];
for (let i = 0; i < petalCount; i++) {
  const a = (i / petalCount) * Math.PI * 2;
  const g = petal.clone();

  // rotate around Y to make a ring
  g.applyMatrix4(
    new THREE.Matrix4()
      .makeRotationY(a)
      
  );
 
  petalGeos.push(g);
}
const mergedPetals = BufferGeometryUtils.mergeGeometries(petalGeos);
mergedPetals.applyMatrix4(new THREE.Matrix4().makeRotationX(Math.PI / 2.2));
mergedPetals.applyMatrix4(new THREE.Matrix4().makeTranslation(0, 1.6, 0.65));
const merged = BufferGeometryUtils.mergeGeometries([stem, stem2, stem3, leaf1, leaf2, center, mergedPetals]);
function tagGeometry(geometry, tagValue) {
  const count = geometry.attributes.position.count;
  const tags = new Float32Array(count).fill(tagValue);
  geometry.setAttribute('isPetal', new THREE.InstancedBufferAttribute(tags, 1));
  return geometry;
}
const stemGeoTagged = tagGeometry(stem, 0.0);
const stem2GeoTagged = tagGeometry(stem2, 0.0);
const stem3GeoTagged = tagGeometry(stem3, 0.0);
const leaf1GeoTagged = tagGeometry(leaf1, 0.0);
const leaf2GeoTagged = tagGeometry(leaf2, 0.0);

const headGeoTagged = tagGeometry(center, 2.0);
const petalGeoTagged = tagGeometry(mergedPetals, 1.0);
const sunflowerGeo = BufferGeometryUtils.mergeGeometries([
  stemGeoTagged,
  stem2GeoTagged,
  stem3GeoTagged,
  leaf1GeoTagged,
  leaf2GeoTagged,
  headGeoTagged,
  petalGeoTagged
]);
const sunflowerMat = new THREE.ShaderMaterial({
  uniforms: {
    stemColor: { value: new THREE.Color('#5baa5b') } // base green
  },
  vertexShader: `
    attribute float isPetal;
    varying float vIsPetal;
    varying vec3 vColor;
    void main() {
      vIsPetal = isPetal;
      vColor = instanceColor; // vertex color (for later use)
      gl_Position = projectionMatrix * modelViewMatrix * instanceMatrix * vec4(position, 1.0);
    }
  `,
  fragmentShader: `
    uniform vec3 stemColor;
    varying float vIsPetal;
    varying vec3 vColor;
    void main() {
      vec3 col = mix(stemColor, mix(vColor,vColor * 0.8,step(1.5,vIsPetal)), step(0.5, vIsPetal));
      gl_FragColor = vec4(col, 1.0);
    }
  `,
});
const count = 2900;
const sunmesh = new THREE.InstancedMesh(sunflowerGeo, sunflowerMat, count);
sunmesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);

const dummy = new THREE.Object3D();
for (let i = 0; i < count; i++) {
  let a = Math.random()*6.283;
  let v = Math.sqrt(Math.random())*63.5;
  let x = v * Math.cos(a);
  let z = v * Math.sin(a);
  dummy.scale.set(0.42,0.42,0.42);
  dummy.position.set(x,terrain.getHeight(x,z)-0.1,z);
  dummy.updateMatrix();
  sunmesh.setMatrixAt(i, dummy.matrix);
  const color = new THREE.Color();
  color.setHSL(0.7+Math.sin(terrain.getHeight(x*.3,z*.32)*2)*0.25,0.75,0.7);
  sunmesh.setColorAt(i, color);
}
sunmesh.instanceColor.needsUpdate = true;
scene.add(sunmesh);

const skyGeo = new THREE.CylinderGeometry(64, 64, 200, 64, 1, true);
skyGeo.scale(-1, 1, 1); // Flip normals inward

// Simple cloud shader
const skyMat = new THREE.ShaderMaterial({
  side: THREE.DoubleSide,
  uniforms: {
    time: { value: 0.0 },
  },
  vertexShader: `
    varying vec2 vUv;
    void main() {
      vUv = uv;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
    }
  `,
  fragmentShader: `
    uniform float time;
    varying vec2 vUv;

    // simple 2D noise
    float hash(vec2 p) {
      return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453);
    }
    float noise(vec2 p) {
      vec2 i = floor(p);
      vec2 f = fract(p);
      float a = hash(i);
      float b = hash(i + vec2(1.0, 0.0));
      float c = hash(i + vec2(0.0, 1.0));
      float d = hash(i + vec2(1.0, 1.0));
      vec2 u = f*f*(3.0-2.0*f);
      return mix(a, b, u.x) + (c - a)*u.y*(1.0 - u.x) + (d - b)*u.x*u.y;
    }

    void main() {
      vec2 uv = (vUv.x < 0.5 ? vUv : vec2(1.0-vUv.x,vUv.y)) * vec2(20.0, 24.0); // repeat clouds horizontally
      uv.x += time * 0.2;             // drift clouds

      float n = (noise(uv) + noise(uv.yx))*0.5;
      float clouds = smoothstep(0.6, 0.9, n);
      vec3 sky = mix(vec3(0.4,0.7,1.), vec3(1.0), clouds);

      gl_FragColor = vec4(sky, 1.0);
    }
  `
});

const sky = new THREE.Mesh(skyGeo, skyMat);
scene.add(sky);


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
  constructor(size = 1, x = 0, z = 0, faith = 1) {
    super();
    
    this.creatureSize = size;
    this.canvas = null;
    this.bodyTexture = null;
    this.bodyMaterial = null;
    this.staticMaterials = {};
    this.position.set(x,0,z);
    this.facedir = 0;
    this.number= ++ccounter;
    this.createMaterials();
    this.createCreature();
    this.scale.setScalar(size);
    this.lockedTarget = 0;
    this.currentProg = null;
    this.health = 100;
    this.faith = faith;
    this.devotion = Math.random()*100;
    this.mode = 0;
    this.decision=0;
    this.myEgg = null;
    this.birthdate = Date.now();
    const cylinderShape = new CANNON.Cylinder(0.2, 0.6, 1.3, 16);
    this.halo.material.color = new THREE.Color(hsvToRgb(gcol[this.faith],1,1,0));
    this.body = new CANNON.Body({ mass: 5 });
    this.body.addShape(cylinderShape);
    this.body.position.set(x, 0, z);
    this.body.angularDamping = 0.9;
    this.body.linearDamping = 0.5;
    world.addBody(this.body);
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
    
    this.bodyMaterial = new THREE.MeshStandardMaterial({
  map: this.bodyTexture,
  emissiveMap: this.bodyTexture,
  emissive: new THREE.Color(0xffffff),
  emissiveIntensity: 1.3,
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
    const haloMaterial = new THREE.MeshStandardMaterial({color: 0xFFFFFF});
    this.halo = new THREE.Mesh(haloGeometry, haloMaterial);
    this.halo.rotation.x = Math.PI / 2; 
    this.halo.position.y = 1.6;
    this.add(this.halo);
    
    this.addFaceFeatures(headMesh);
    

    const armGeometry = new THREE.CylinderGeometry(0.08, 0.08, 0.8, 6);
    armGeometry.translate(0,-0.45,0);
    
    this.leftArm = new THREE.Mesh(armGeometry, this.staticMaterials.limbs);
    this.leftArm.position.set(-0.2, 0.2, 0);
    this.leftArm.rotation.z = -Math.PI * 0.25;
    this.leftArm.name = 'leftArm';
    this.leftArm.castShadow = true;
    this.add(this.leftArm);
    
    this.rightArm = new THREE.Mesh(armGeometry, this.staticMaterials.limbs);
    this.rightArm.position.set(0.2, 0.2, 0);
    this.rightArm.rotation.z = Math.PI * 0.25;
    this.rightArm.name = 'rightArm';
    this.rightArm.castShadow = true;

    this.add(this.rightArm);
    
    // Hands (small spheres) - attached to arms
    const handGeometry = new THREE.SphereGeometry(0.12, 8, 6);
    
    const leftHand = new THREE.Mesh(handGeometry, this.staticMaterials.hands);
    leftHand.position.set(0, -0.92, 0); 
    leftHand.name = 'leftHand';
    leftHand.castShadow = true;

    this.leftArm.add(leftHand); // Make hand a child of arm
    
    const rightHand = new THREE.Mesh(handGeometry, this.staticMaterials.hands);
    rightHand.position.set(0, -0.92, 0); 
    rightHand.name = 'rightHand';
    rightHand.castShadow = true;

    this.rightArm.add(rightHand); // Make hand a child of arm
    
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
    const dx = x - this.body.position.x;
    const dz = z - this.body.position.z;
    savedir = Math.atan2(dz,dx);
    this.body.position.x += speed * Math.cos(savedir);
    this.body.position.z += speed * Math.sin(savedir);
    savedir = Math.atan2(dx,dz);
 
  }

  moveAway(x, z, speed) {
    const dx = x - this.body.position.x;
    const dz = z - this.body.position.z;
    let ang = Math.atan2(dz,dx);
    this.body.position.x -= speed * Math.cos(ang);
    this.body.position.z -= speed * Math.sin(ang);
    savedir = Math.atan2(dx,dz) + Math.PI;
  }

}

let creatures = [];
for (me=0; me<6; me++) {
 let c=new SpriteCreature(1.4, Math.random()*40-20, Math.random()*40 - 20, 1+Math.floor(me/2));
 creatures.push(c); 
 c.castShadow = true;
 scene.add(c);
}

const gbox = new THREE.BoxGeometry(1, 1, 1);

const mbox = new THREE.MeshStandardMaterial({
  color: 0xD3AF37,
  side: THREE.DoubleSide
});

const mbox2 = new THREE.MeshStandardMaterial({
  color: 0x6A452C,
  side: THREE.DoubleSide
});

const mbox3 = new THREE.MeshStandardMaterial({
  color: 0xEEEEFF,
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
     this.temple =0;
     this.number = ++tcounter;
     this.restingplace = [0,0,0];
     this.goresting = 0;
     this.birthdate = Date.now();
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
  restitution: 0.5
});
world.addContactMaterial(contactMaterial2);

const eggGeometry = new THREE.SphereGeometry(1, 32, 48);
const eggpositionAttribute = eggGeometry.attributes.position;
for (let i = 0; i < eggpositionAttribute.count; i++) {
  const y = eggpositionAttribute.getY(i);
  if (y > 0)  eggpositionAttribute.setY(i, y * 1.5);
  else eggpositionAttribute.setY(i, y * 0.9);
}
eggGeometry.computeVertexNormals();

var keydown=[0,0,0,0,0,0,0,0,0,0,0,0];
                      
let twopi = Math.PI * 2;
let ninety = Math.PI * 0.5;

var theta = -1.63;
var stheta = theta;
var left=0, ltop=0, mousepressed=0;
var yang = -0.27;
var syang = yang;
var spd = 0.6, spd2=0.25;
var wiw = window.innerWidth;
var wih = window.innerHeight;
var overscroll = 0;

function sqpot(o1,o2) {
  let dx = o2.x-o1.x;
  let dz = o2.z-o1.z;
  return dx*dx + dz*dz;
}

function nearest(limit) {
  let mn = limit;
  let index=-1;
  for (var e=0; e< creatures.length; e++) 
  if (!(e==me)) {
    let dst=sqpot(creatures[e].body.position,cme.body.position);
    if (dst<mn) {
      index=e;
      mn=dst;
    }
  }
  return index;
}

window.evade = function evade() {
  let ind =-1;
  let mn = 100;
  dangers.forEach((target,key) => {
    let dx = target.box.position.x - creatures[me].body.position.x;
    let dz = target.box.position.z - creatures[me].body.position.z;
    let dst=sqpot(target.box.position,cme.body.position);
    if (dst<mn) {
      ind=key;
      mn=dst;
    }
  });
  if (ind>=0) {
    let t = dangers.get(ind);
    cme.moveAway(t.body.position.x,t.body.position.z,0.1);
    cme.facedir = savedir;
  }
  else {
   ind = nearest(25);
   if (ind>=0) {
    cme.moveAway(creatures[ind].body.position.x,creatures[ind].body.position.z,0.1);
    cme.facedir = savedir;
   }
  }
}

window.pursue = function pursue() {
  if (!cme.lockedTarget) cme.lockedTarget=findTarget(0);
  if (cme.lockedTarget) {
    let t = targets.get(cme.lockedTarget);
    if (t === undefined) 
       cme.lockedTarget=0;
    else {
      cme.moveToward(t.box.position.x,t.box.position.z,0.1);
      if (targets.get(cme.lockedTarget).captured>0) cme.lockedTarget=0;
      else {
         cme.facedir = savedir;
         if (sqpot(t.box.position,cme.body.position)<1) 
         if (t.box.position.y - cme.body.position.y < 2) {
          cme.health+=20;
          t.captured=1;
          cme.lockedTarget=0;
          t.box.material = mbox2;
          cme.birthdate=Date.now();
          cme.currentProg = null;
         }
      }
    }
  }
}

window.findblock = function findblock() {
  if (!cme.lockedTarget) cme.lockedTarget=findTarget(1);
  if (cme.lockedTarget) {
    let t = targets.get(cme.lockedTarget);
    if (t === undefined) 
       cme.lockedTarget=0;
    else {
      cme.moveToward(t.box.position.x,t.box.position.z,0.1);
      if (targets.get(cme.lockedTarget).captured>1) cme.lockedTarget=0;
      else {
         cme.facedir = savedir;
         if (sqpot(t.box.position,cme.body.position)<1) {
          t.captured=2;
          t.box.material = mbox3;
          world.removeBody(t.body);
          cme.currentProg = placeblock;
         }
      }
    }
  }
  else cme.currentProg = null;
}

function placeblock() {
  let tmp = Temples[cme.faith];
  cme.moveToward(tmp.position.x,tmp.position.z,0.1);
  cme.facedir = savedir;
  if (cme.lockedTarget > 0) {
    let t = targets.get(cme.lockedTarget);
    try {
      t.body.position.set(cme.body.position.x + 0.5, cme.body.position.y, cme.body.position.z + 0.5);
      let h = sqpot(cme.body.position,tmp.position);
      if (h<81) {
        t.captured=3;
        cme.currentProg = null;
        let index = tmp.level;
        let lvl = Math.floor(index/9);
        index-=lvl*9;
        let yy = Math.floor(index/3);
        index-=yy*3;
        let x = tmp.position.x-1+index;
        let z = tmp.position.z-1+yy;
        let y = terrain.getHeight(tmp.position.x,tmp.position.z)+3+lvl;
        tmp.level++;
        t.body.position.y+=5;
        const p0 = new CANNON.Vec3(t.body.position.x,t.body.position.y,t.body.position.z);
        const pT = new CANNON.Vec3(x,y,z);
        const dx = pT.x - p0.x;
        const dz = pT.z - p0.z;
        const dy = pT.y - p0.y;
        const d = Math.sqrt(dx * dx + dz * dz);
        const angle = Math.PI/4;
        const denom = 2 * Math.cos(angle) ** 2 * (d * Math.tan(angle) - dy);
        const v0 = Math.sqrt((9.82 * d * d) / denom);
        const dir = new CANNON.Vec3(dx / d, 0, dz / d);
        const vel = new CANNON.Vec3(v0 * Math.cos(angle) * dir.x,v0 * Math.sin(angle),v0 * Math.cos(angle) * dir.z);
        world.addBody(t.body);
        t.body.velocity.copy(vel);
        t.goresting=1;
        t.restingplace = [x,y,z];    
        if(tmp.level>=27) t.temple = cme.faith;
        t.birthdate=Date.now();
      }
    } catch (error) {
      console.error('Error setting target body position:', error);
      console.error('t.body:', t);
    }
  }
  else cme.currentProg = null;
}

function findTarget(lvl) {
  let mn = 9999999;
  let index=0;
  targets.forEach((target, key) => {
   if (target.captured == lvl) {
    let dst=sqpot(target.box.position,cme.body.position);
    if (dst<mn) {
      index=key;
      mn=dst;
    }
   }
  });
  return index;
}

window.haverest = function haverest() {
}
 
window.fight = function fight() {
  let tt = (Date.now() - tstart) * 0.01;
  cme.leftArm.rotation.x = tt;
  cme.rightArm.rotation.x = tt + Math.PI;
  if (creatures[tnearest[4]]) {
  creatures[tnearest[4]].health-=0.025;
  cme.facedir = Math.atan2(creatures[tnearest[4]].position.x - cme.position.x, creatures[tnearest[4]].position.z - cme.position.z);
  }
  else cme.currentProg = null;
  cme.health-=0.01;
}

window.cry = function cry() {
   let tt = (Date.now() - tstart) * 0.01;
   cme.leftArm.rotation.z = Math.PI * 0.7 + Math.sin(tt)*0.23;
   cme.rightArm.rotation.z = Math.PI * 1.3 - Math.sin(tt)*0.23;
}

const heartShape = new THREE.Shape();
heartShape.moveTo(25, 25);
heartShape.bezierCurveTo(25, 25, 20, 0, 0, 0);
heartShape.bezierCurveTo(-30, 0, -30, 35, -30, 35);
heartShape.bezierCurveTo(-30, 55, -10, 77, 25, 95);
heartShape.bezierCurveTo(60, 77, 80, 55, 80, 35);
heartShape.bezierCurveTo(80, 35, 80, 0, 50, 0);
heartShape.bezierCurveTo(35, 0, 25, 25, 25, 25);

const extrudeSettings = {
  steps: 2,   
  depth: 10,   
  bevelEnabled: false
};

const heartgeometry = new THREE.ExtrudeGeometry(heartShape, extrudeSettings);

window.mercy = function mercy() {
   let dst=999999;
   let index = -1;
   for (let j=0; j<creatures.length; j++) 
   if (!(j == cme)) 
   if (cme.faith == creatures[j].faith)
   if (sqpot(cme.body.position,creatures[j].body.position)<225)
   if (creatures[j].health < dst) {
       dst=creatures[j].health;
       index=j;
   }
   if (index>=0) {
    if (cme.myEgg === null) {
      const heartcol =  new THREE.Color(hsvToRgb(gcol[cme.faith],0.35,0.8,0));
      const heartmaterial = new THREE.MeshPhongMaterial({
      color: heartcol, 
      flatShading: true 
      });
     const heartMesh = new THREE.Mesh(heartgeometry, heartmaterial);
     heartMesh.scale.setScalar(0.01);
     heartMesh.rotation.z = Math.PI;
     scene.add(heartMesh);
     cme.myEgg = heartMesh;
    }
    let dist = ((Date.now() - tstart) % 1000) / 1000;
    cme.myEgg.position.x = cme.body.position.x + (creatures[index].body.position.x - cme.body.position.x)*dist;
    cme.myEgg.position.z = cme.body.position.z + (creatures[index].body.position.z - cme.body.position.z)*dist;
    cme.myEgg.position.y = terrain.getHeight(cme.myEgg.position.x,cme.myEgg.position.z)+5;
    cme.health-=0.066;
    creatures[index].health+=0.05;
    if ((cme.health<40) || (creatures[index].health>30)) cme.currentProg=null;  
   }
   else cme.currentProg = null;
}

window.brood = function brood() {
  if (cme.myEgg === null) {
    let eggCol = new THREE.Color(hsvToRgb(gcol[cme.faith],0.25,0.9,0));
    const material = new THREE.MeshStandardMaterial({ color: eggCol, roughness: 0.7, metalness: 0 });
    let ce = new THREE.Mesh(eggGeometry, material);
    ce.position.set(cme.body.position.x,terrain.getHeight(cme.body.position.x,cme.body.position.z)+0.5,cme.body.position.z);
    cme.birthdate=Date.now();
    scene.add(ce);
    cme.myEgg = ce;
  }
  let scl = 0.5 + (Date.now() - cme.birthdate)/10000;
  cme.myEgg.scale.setScalar(scl);
  if (scl>1.5) {
    cme.currentProg = null;
    let c = new SpriteCreature(1.4,cme.body.position.x+1, cme.body.position.z+1,cme.faith);
    c.health=80;
    creatures.push(c);
    c.castShadow = true;
    scene.add(c);

  }  
}

let def=` 
  let cme = myself();
  if (NearestBomb() && NearestBomb().distance< 3.5) return evade;
  if (myself().health<20) return (tnearestmin[0]>16 ? cry : pursue);
  if (tnearestmin[4]<25) {
    if (Math.random()<0.25) return evade;
    if (Math.random()>0.75) return fight;
  }
  if (tnearestmin[0]<225) 
  if (Math.random()>Math.sqrt(tnearestmin[0])/20) return pursue;
  if (cme.health>40) 
    if (cme.health > tnearestmin[5] * 1.5) 
      return mercy;
  if (tnearestmin[1]<900) 
  if (cme.health>30)
  if (Date.now()-cme.birthdate>1500)
     return findblock;
  if (cme.health>101) if (Math.random()>0.75) return brood;
  if (Math.random()>0.8) return pursue;
  if (Math.random()<0.25) return findblock;
  return haverest;`;

let funcs = [];
funcs.push(new Function(def));

funcs.push(new Function('return fight; '));
funcs.push(new Function('console.log(NearestUnopenedBox()); if (NearestUnopenedBox()) return pursue; if (NearestFoe() && myself().health > NearestFoe().health * 1.5) return fight; if (NearestBomb()) return evade; if (NearestEmergency() && myself().health > 30) return mercy; if (NearestStackableBox()) return findblock; return haverest;'));


function getProgram() {
  cme=creatures[me];

  switch (cme.currentProg) {
    case brood: return brood; break; 
    case cry: if (Math.random()<0.85) return cry; break; 
    case evade: if (Math.random()<0.85) return evade; break; 
    case pursue: if (Math.random()<0.7) return pursue; break; 
    case fight: if (Math.random()<0.75) return fight; break; 
    case findblock: 
    case placeblock: if (cme.health>10) return cme.currentProg;  break;
  }
  try {
    const f = funcs[creatures[me].decision];
    if (typeof f === 'function') {
      return f();  // <-- CALL IT
    }
  } catch (e) {
    console.warn(e);
  }
  return haverest;
}

window.myself = function myself() {
  return creatures[me];
}

window.NearestBomb = function NearestBomb() {
  if (tnearestmin[2]>100) return null;
  let b = dangers.get(tnearest[2]).box;
  return {distance : Math.hypot(b.position.x - cme.position.x, b.position.z - cme.position.z), position: b.position};
}

window.NearestUnopenedBox = function NearestUnopenedBox() {
  if (tnearestmin[0]>100) return null;
  let b = targets.get(tnearest[0]).box;
  return {distance : Math.hypot(b.position.x - cme.position.x, b.position.z - cme.position.z), position: b.position};
}

window.NearestStackableBox = function NearestStackableBox() {
  if (tnearestmin[1]>100) return null;
  let b = targets.get(tnearest[1]).box;
  return {distance : Math.hypot(b.position.x - cme.position.x, b.position.z - cme.position.z), position: b.position};
}

window.NearestFriend = function NearestFriend() {
  if (tnearestmin[3]>100) return null;
  let b = creatures[tnearest[3]];
  return {distance : Math.hypot(b.position.x - cme.position.x, b.position.z - cme.position.z), position: b.position, health: b.health, faith: b.faith, devotion: b.devotion};
}

window.NearestFoe = function NearestFoe() {
  if (tnearestmin[4]>100) return null;
  let b = creatures[tnearest[4]];
  return {distance : Math.hypot(b.position.x - cme.position.x, b.position.z - cme.position.z), position: b.position, health: b.health, faith: b.faith, devotion: b.devotion};
}

window.NearestEmergency = function NearestEmergency() {
  if (tnearestmin[5]>100) return null;
  let b = creatures[tnearest[5]];
  return {distance : Math.hypot(b.position.x - cme.position.x, b.position.z - cme.position.z), position: b.position, health: b.health, faith: b.faith, devotion: b.devotion};
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
              console.log('pos='+creatures[i].body.position.x.toFixed(1)+','+creatures[i].body.position.z.toFixed(1)+' health='+creatures[i].health);

            };

           break;
         case 80: gamepause = 1-gamepause; break; 
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
    if (index !== -1) {
      trackme=creatures[index].number;
      console.log('index='+index+' number='+trackme);
    }
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

  var txt = 'Custom';
  if (creatures[a].currentProg) 
  switch (creatures[a].currentProg) {
    case pursue: txt='Pursue'; break;
    case evade: txt='Evade'; break;
    case haverest: txt='Rest'; break;
    case cry : txt='Cry'; break;
    case mercy: txt='Mercy'; break;
    case brood: txt='Brood'; break;
    case fight: txt='Fight'; break;
    case findblock: txt='Find Block'; break;
    case placeblock: txt='Place Block'; break;
  }
  if (creatures[a].health<=0.1) txt='EternalRest';
  document.getElementById('info').innerHTML='Creature #'+creatures[a].number+'<br><br>Current Function:<br>'+txt+'<br><br>Health: '+ creatures[a].health.toFixed(1)+'<br><br>Faith: '+gods[creatures[a].faith];
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

let Temples = [[]];

for (let e=1; e<4; e++) {
  let ang = e*twopi/3 + Math.random();
  let dst = 25+Math.random()*25;
  let x = dst * Math.cos(ang);
  let z = dst * Math.sin(ang);
  let y = terrain.getHeight(x,z)+3.5;
  Temples.push({position: { x: x, z: z}, level: 0});
  const tboxg = new THREE.BoxGeometry(3,10,3);
  const tcolor = new THREE.Color(hsvToRgb(gcol[e],1,1,0));
  const tbasem = new THREE.MeshStandardMaterial({color: tcolor, side: THREE.DoubleSide});
  const tboxm = new THREE.MeshBasicMaterial({ color: tcolor, wireframe:true});
  const edges = new THREE.EdgesGeometry(tboxg);
  const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: tcolor}));
  scene.add(line);
  const tbase = new THREE.Mesh(tboxg,tbasem);
  tbase.position.set(x,y-6,z);
  scene.add(tbase); 
  line.position.set(x,y,z);
  const troofg = new THREE.BufferGeometry();
  const vertices = new Float32Array([-1.5,5,-1.5, 1.5, 5, -1.5, 1.5, 5, 1.5, -1.5, 5, 1.5, 0,7, 0]);
  troofg.setAttribute('position',new THREE.BufferAttribute(vertices,3));
  troofg.setIndex([0,1,4,1,2,4,2,3,4,3,0,4]);
  troofg.computeVertexNormals();
  const troofmesh = new THREE.Mesh(troofg,tboxm);
  troofmesh.position.set(x,y,z);
  scene.add(troofmesh);
  
}
  
let tstart = Date.now();
let tme=0;
let tnearest=[];
let tnearestmin=[];
let gamewon=0;
window.tnearest = tnearest;
window.tnearestmin = tnearestmin;
window.creatures = creatures;

function animate() {
 if ((!gamewon) && (!gamepause)) {
  world.step(1/60);
  skyMat.uniforms.time.value += 0.01;
  let now = Date.now();
  let tt = (now - tstart) * 0.01;
  tnearest = [-1,-1,-1,-1,-1,-1];   // food box, resource box, danger mine, friend, foe, crying
  tnearestmin = [99999999,99999999,9999999,9999999,9999999,9999999];
  
  targets.forEach((target,key) => {
    target.box.position.copy(target.body.position);
    target.box.quaternion.copy(target.body.quaternion);
    if (Math.hypot(target.body.position.x,target.body.position.z)>63.5) {
        world.removeBody(target.body);
        scene.remove(target.box);
        targets.delete(key);
    }
    if (target.captured<2) {
      if (now - target.birthdate > 45000) {
        world.removeBody(target.body);
        scene.remove(target.box);
        targets.delete(key);
      }
      let dst = sqpot(target.body.position,creatures[tme].position);
      let cap = target.captured;
      if (target.body.position.y<15)
      if (dst<tnearestmin[cap]) {
         tnearestmin[cap]=dst;
         tnearest[cap]=key;
      }
    } else {
      if (target.goresting>0) {
        let h = Math.hypot(target.body.position.x - target.restingplace[0],target.body.position.y-target.restingplace[1],target.body.position.z-target.restingplace[2]);
        if (target.goresting<2) if (now - target.birthdate>3300) h=3;
        if (h<3.5) {
          if (target.goresting<2) {
            target.body.velocity.set(0,0,0);
            target.body.angularVelocity.set(0,0,0);
            target.body.type = CANNON.Body.STATIC;
            world.removeBody(target.body);
            world.addBody(target.body);
            target.birthdate=Date.now();
            target.goresting=2;
          }
          if (target.goresting<3) {
            let elap = Math.min(2001,now - target.birthdate);
            let fct=0.03*(1+elap/500); 
            target.body.position.x += (target.restingplace[0]-target.body.position.x)*fct;  
            target.body.position.y += (target.restingplace[1]-target.body.position.y)*fct;
            target.body.position.z += (target.restingplace[2]-target.body.position.z)*fct;
            const targetQuat = new CANNON.Quaternion();
            targetQuat.setFromEuler(0, 0, 0);    
            target.body.quaternion.slerp(targetQuat, elap/2000, target.body.quaternion);
            if ((elap>=2000) && (h<0.01)) {
               target.goresting=3;
               if (!gamewon) if (target.temple) {
                 gamewon=target.temple;
                 const winColor = new THREE.Color(hsvToRgb(gcol[gamewon],0.8,0.5,0));
                 sky.material = new THREE.MeshStandardMaterial({color: winColor, emissive: winColor, emissiveIntensity: 1.3});
                 let d = document.getElementById('headdiv');
                 d.style.color = 'white';
                 d.style.opacity=1;
                 d.innerHTML=gods[gamewon]+' Wins!!!!';
               }
            }
          }
        }
      }
    }
  });
  dangers.forEach((target,key) => {
     target.box.position.copy(target.body.position);
     target.box.quaternion.copy(target.body.quaternion);
     target.strength-=0.002;
     if (target.strength>0) {
       target.box.material.opacity = target.strength;
       let dst=sqpot(target.body.position,creatures[tme].position);
       if (dst<tnearestmin[2]) {
          tnearestmin[2]=dst;
          tnearest[2]=key;
       } 
     }
     else {
       scene.remove(target.box);
       world.removeBody(target.body);
       dangers.delete(key);
     }
  });

 

  const dummy = new THREE.Object3D();
  for (let i = 0; i < sunmesh.count; i++) {
    sunmesh.getMatrixAt(i, dummy.matrix);
    dummy.position.setFromMatrixPosition(dummy.matrix);
    dummy.rotation.set(0, 0, 0);
    dummy.scale.set(1, 1, 1);
    const angle = 0.4*Math.sin((now-tstart)/1400);
    dummy.rotation.y = angle;
    dummy.updateMatrix();
    sunmesh.setMatrixAt(i, dummy.matrix);
  }
  sunmesh.instanceMatrix.needsUpdate = true;

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
     for (let e=0; e<creatures.length; e++)
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
       if (ce.mode ==0)
       if (h<3) {
          ce.body.type = CANNON.Body.STATIC;
          ce.mode=2;
          ce.birthdate = Date.now(); 
          ce.body.position.x = r.beam.position.x;
          ce.body.position.z = r.beam.position.z;
          if (!(ce.myEgg === null)) {
            scene.remove(ce.myEgg);
            ce.myEgg.material.dispose();
            ce.myEgg = null;
          }
          ce.currentProg = null;
       }
     }
   }
  }
  if (takeout>=0) {
    scene.remove(revlights[takeout].beam);
    revlights[takeout].beam.material.dispose();
    revlights[takeout].beam.geometry.dispose();
    revlights.splice(takeout,1);
  }

  for(me=0; me<creatures.length; me++) {
    cme = creatures[me];
    if (!(cme.currentProg === null)) 
    if (cme.mode ==0) cme.currentProg();
    cme.body.position.x = envclamp(cme.body.position.x);
    cme.body.position.z = envclamp(cme.body.position.z);
    cme.body.position.y=1.2 * cme.creatureSize + terrain.getHeight(cme.body.position.x,cme.body.position.z);
    if (!(cme.myEgg === null)) 
    if (cme.currentProg == brood) {
      cme.body.position.x = cme.myEgg.position.x;
      cme.body.position.z = cme.myEgg.position.z;
      cme.body.position.y+= cme.myEgg.scale.x * 0.75 + 0.1;
      cme.health -= 0.02;
    }
    let life = now - cme.birthdate;
    switch (cme.mode) {
     case 1:
      cme.body.position.y+= life/2000 -2;
      if (life>4000) {
        cme.mode=0;
        cme.body.type = CANNON.Body.DYNAMIC;
      }
      break;
    case 2 :
      cme.body.position.y+= life/100;
      cme.scale.setScalar(cme.creatureSize * (1-life/4500));
      cme.body.quaternion.setFromEuler(life/500, 0, life / 800, "XYZ");
      if (life>3000) {
        cme.mode=3;
        cme.birthdate = now;
        cme.decision = 2;
      }
      break;
    case 3 : 
      cme.body.position.y+= 30;

      if (life>1000) {
        cme.mode=4;
        cme.body.quaternion.setFromEuler(0, 0, 0, "XYZ");
      }
      break;
   case 4 : 
      let ht = 4 * Math.pow(life/1000,2);
      cme.body.position.y+= 30 - ht;
      cme.scale.setScalar(cme.creatureSize* ht/30);

      if (ht>=30) {
       cme.body.type = CANNON.Body.DYNAMIC;
       cme.scale.setScalar(cme.creatureSize);
       cme.mode=0;      
      }
    }
    cme.body.angularVelocity.x = 0;
    cme.body.angularVelocity.z = 0;
    if (cme.mode < 2) {
      const up = new CANNON.Vec3(0,1,0);
      const forward = new CANNON.Vec3(0,0,1);
      const cDir = cme.body.quaternion.vmult(forward);
      const cYaw = Math.atan2(cDir.x, cDir.z);
      let diff = cme.facedir - cYaw;
      if (diff > Math.PI) diff -= 2 * Math.PI;
      if (diff < -Math.PI) diff += 2 * Math.PI;
      const step = Math.sign(diff) * Math.min(Math.abs(diff), 0.15);
      const qStep = new CANNON.Quaternion();
      qStep.setFromAxisAngle(up, step);
      cme.body.quaternion = qStep.mult(cme.body.quaternion);
      const qUpright = new CANNON.Quaternion();
      const euler = new CANNON.Vec3();
      cme.body.quaternion.toEuler(euler);
      qUpright.setFromEuler(0, euler.y, 0);
      cme.body.quaternion.copy(qUpright);
      if (!(me == tme)) {
         let dst = sqpot(cme.body.position,creatures[tme].body.position);
         let ff = (cme.faith == creatures[tme].faith ? 3 : 4);
         if (dst<tnearestmin[ff]) {
            tnearestmin[ff]=dst;
            tnearest[ff]=me;
            
         }
         if (dst < 225) 
         if (cme.faith == creatures[tme].faith) 
          if (cme.currentProg === cry) 
            if (cme.health<tnearestmin[5]) 
               tnearestmin[5]=cme.health;
         
      }
    }
    cme.position.copy(cme.body.position);
    cme.quaternion.copy(cme.body.quaternion);
   
   dangers.forEach((danger,key) => {
      if (Math.abs(cme.position.x - danger.box.position.x)<3)
      if (Math.abs(cme.position.y - danger.box.position.y)<3)
      if (Math.abs(cme.position.z - danger.box.position.z)<3) {
         var dst = 1+Math.hypot(cme.position.x - danger.box.position.x,cme.position.z - danger.box.position.z);
         cme.health -= 6 * danger.strength / dst;
      }
   });
   cme.health-=0.02;


   if (cme.health<=0) {
      if (!(cme.myEgg === null)) {
        scene.remove(cme.myEgg);
        cme.myEgg.material.dispose();
        cme.myEgg = null;
      }
      scene.remove(cme);
      world.removeBody(cme.body);
      if (cme.currentProg === placeblock) 
      if (cme.lockedTarget>0) {
         let t = targets.get(cme.lockedTarget);
         t.captured=1;
         t.box.material = mbox2;
      }    
   }
  }

  me=tme;
  cme=creatures[me];
  if (cme.number == trackme) showCreature(tme);
  if (cme.mode ==0) {
     let newFunc = getProgram();
     if (!(newFunc === cme.currentProg)) {
        cme.leftArm.rotation.x=0;
        cme.rightArm.rotation.x=0;
        cme.leftArm.rotation.z=-Math.PI * 0.25;
        cme.rightArm.rotation.z=Math.PI * 0.25;

        if (cme.currentProg === placeblock) 
        if (cme.lockedTarget>0) {
          let t = targets.get(cme.lockedTarget);
          t.captured=1;
          t.box.material = mbox2;
        }    
        cme.lockedTarget=0;
        if (!(cme.myEgg === null)) {
          scene.remove(cme.myEgg);
          cme.myEgg.material.dispose();
          cme.myEgg = null;
        }
        cme.currentProg = newFunc;
     }
  } 
  if (++tme>=creatures.length) {
     creatures=creatures.filter(entity => entity.health > 0);
     tme=0;
  }
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

   const camd = Math.hypot(camera.position.x,camera.position.z);
   if (camd>63.5) {
      const ang = Math.atan2(camera.position.z,camera.position.x);
      camera.position.x = 63.5 * Math.cos(ang);
      camera.position.z = 63.5 * Math.sin(ang);
   }
   const grnd = 0.5+terrain.getHeight(camera.position.x,camera.position.z);
   if (camera.position.y<grnd) camera.position.y = grnd;
   camera.lookAt(camera.position.x + 0.15*Math.cos(theta)*Math.cos(yang),camera.position.y + 0.15*Math.sin(yang),camera.position.z + 0.15*Math.sin(theta)*Math.cos(yang));

  requestAnimationFrame(animate);
  renderer.render(scene, camera);
}

var xmlhttp;
var uptoAI=1;

function askGod()
  {  if (++uptoAI > 3) uptoAI=1;

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
      if (!gamewon) setTimeout(askGod,5000);
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
// console.log('1 => '+v);
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
// console.log('2 => '+v);

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
// console.log('3 => '+v);
     var j  = JSON.parse(v);
     if (j.success === true) 
       doGodsWork(3,JSON.parse(j.message));  
  }
}

function doGodsWork(god,m) {
   if (!gamewon) {
     if (Math.random()<0.05) {
       m.action = 'Immaculate';
       m.x /=2;
       m.y /=2;
     }
        headdisplay(gods[god]+' plays '+m.action,god);
        glast[god]=m.action;
        if (m.action == 'Revelation') {
            revlights.push(new RevLight(god,m.x,m.y));
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
        if (m.action== 'Immaculate') {
          let c = new SpriteCreature(1.4,m.x,m.y,god);
          c.mode=1; 
          c.body.type = CANNON.Body.STATIC;
          creatures.push(c);
          me = creatures.length-1;  
          c.castShadow = true;
          scene.add(c);
        }
//        console.log(gods[god]+' => '+JSON.stringify(m));
  }
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
  if (!gamewon)
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
