/**
 * Chandila Web Development – Photorealistic 3D Rotating Earth Globe
 * Built with Three.js (WebGL), NASA Blue Marble atmospheric textures,
 * dynamic cloud layer, Fresnel atmospheric rim glow, Colombo HQ beacon,
 * and international data connection arcs.
 */
(function () {
    const canvas = document.getElementById('earthGlobeCanvas');
    if (!canvas) return;

    // Verify Three.js availability
    if (typeof THREE === 'undefined') {
        console.warn('Three.js library is required for photorealistic 3D Earth.');
        return;
    }

    const container = canvas.parentElement;
    const heroSection = canvas.closest('.hero-section') || container;

    // Resolve base assets path dynamically
    const scriptTag = document.querySelector('script[src*="earth_globe.js"]');
    const rootUrl = scriptTag ? scriptTag.src.replace(/assets\/js\/earth_globe\.js.*$/, '') : '';
    const textureEarthUrl = rootUrl + 'assets/images/earth_atmos_2048.jpg';
    const textureCloudsUrl = rootUrl + 'assets/images/earth_clouds_1024.png';
    const textureSpecularUrl = rootUrl + 'assets/images/earth_specular_2048.jpg';

    // 1. Scene, Camera, Renderer Setup
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(40, 1, 0.1, 1000);
    camera.position.z = 2.85;

    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            alpha: true,
            antialias: true,
            powerPreference: 'high-performance'
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.15;
    } catch (e) {
        console.error('WebGL initialization failed for Earth globe:', e);
        return;
    }

    // Master Globe Pivot (handles Earth's realistic axial tilt & mouse parallax)
    const globePivot = new THREE.Group();
    // Earth's real axial obliquity: 23.44°
    globePivot.rotation.z = -23.44 * (Math.PI / 180);
    globePivot.rotation.x = 9 * (Math.PI / 180); // Natural orbital tilt
    scene.add(globePivot);

    const earthGroup = new THREE.Group();
    globePivot.add(earthGroup);

    // Initial rotation so South Asia / Sri Lanka faces forward on initial view
    earthGroup.rotation.y = -1.25;

    // 2. Lighting Setup for Realistic Sunlight & Space Aesthetics
    // Soft daylight ambient light to keep night side clear on white theme
    const ambientLight = new THREE.AmbientLight(0xf1f5f9, 1.15);
    scene.add(ambientLight);

    // Main Solar Directional Light: Positioned top-left to illuminate the continents
    const sunLight = new THREE.DirectionalLight(0xffffff, 1.25);
    sunLight.position.set(6, 3, 5);
    scene.add(sunLight);

    // Subtle blue rim back-light for atmospheric depth
    const rimLight = new THREE.DirectionalLight(0x38bdf8, 0.4);
    rimLight.position.set(-6, -2, -3);
    scene.add(rimLight);

    // 3. Texture Loading & Earth Mesh
    const textureLoader = new THREE.TextureLoader();
    let earthMesh, cloudsMesh, glowMesh, outerHaloMesh;

    // Albedo / Land & Ocean Map
    const earthTexture = textureLoader.load(textureEarthUrl, function () {
        renderer.render(scene, camera);
    });
    earthTexture.generateMipmaps = true;
    earthTexture.minFilter = THREE.LinearMipmapLinearFilter;

    // Specular Map (ocean water reflects sunlight, continents stay matte)
    const specularTexture = textureLoader.load(textureSpecularUrl);

    // Earth Sphere Geometry
    const earthGeo = new THREE.SphereGeometry(1.0, 64, 64);
    const earthMat = new THREE.MeshPhongMaterial({
        map: earthTexture,
        specularMap: specularTexture,
        specular: new THREE.Color(0x334466),
        shininess: 25,
        emissive: new THREE.Color(0x0e1b38),
        emissiveIntensity: 0.1
    });
    earthMesh = new THREE.Mesh(earthGeo, earthMat);
    earthGroup.add(earthMesh);

    // 4. Photorealistic Swirling Cloud Layer
    const cloudsTexture = textureLoader.load(textureCloudsUrl);
    const cloudsGeo = new THREE.SphereGeometry(1.014, 64, 64);
    const cloudsMat = new THREE.MeshPhongMaterial({
        map: cloudsTexture,
        transparent: true,
        opacity: 0.70,
        blending: THREE.NormalBlending,
        depthWrite: false
    });
    cloudsMesh = new THREE.Mesh(cloudsGeo, cloudsMat);
    earthGroup.add(cloudsMesh);

    // 5. NASA Atmospheric Limb Glow (Fresnel Limb Shader)
    // Mathematically calculates grazing angle for soft glowing cyan-blue rim
    const atmosphereVertexShader = `
        varying vec3 vNormal;
        void main() {
            vNormal = normalize(normalMatrix * normal);
            gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
        }
    `;
    const atmosphereFragmentShader = `
        varying vec3 vNormal;
        void main() {
            // Silhouette grazing intensity: highest at the horizon edges, transparent in center
            float intensity = pow(1.0 - abs(dot(vNormal, vec3(0.0, 0.0, 1.0))), 2.4);
            intensity = clamp(intensity, 0.0, 1.0);
            // Atmospheric hue (cyan-blue)
            gl_FragColor = vec4(0.24, 0.65, 1.0, 1.0) * intensity * 1.6;
        }
    `;
    const atmosphereGeo = new THREE.SphereGeometry(1.03, 64, 64);
    const atmosphereMat = new THREE.ShaderMaterial({
        vertexShader: atmosphereVertexShader,
        fragmentShader: atmosphereFragmentShader,
        blending: THREE.AdditiveBlending,
        side: THREE.BackSide,
        transparent: true
    });
    glowMesh = new THREE.Mesh(atmosphereGeo, atmosphereMat);
    globePivot.add(glowMesh);

    // Outer Soft Corona Halo
    const outerHaloGeo = new THREE.SphereGeometry(1.06, 48, 48);
    const outerHaloMat = new THREE.ShaderMaterial({
        vertexShader: atmosphereVertexShader,
        fragmentShader: `
            varying vec3 vNormal;
            void main() {
                float intensity = pow(0.7 - dot(vNormal, vec3(0.0, 0.0, 1.0)), 3.0);
                gl_FragColor = vec4(0.3, 0.7, 1.0, 0.35) * intensity;
            }
        `,
        blending: THREE.AdditiveBlending,
        side: THREE.BackSide,
        transparent: true
    });
    outerHaloMesh = new THREE.Mesh(outerHaloGeo, outerHaloMat);
    globePivot.add(outerHaloMesh);

    // 6. Coordinates Helper: Convert (Lat, Lon) to 3D Sphere Position
    function latLonToVector3(lat, lon, radius) {
        const phi = (90 - lat) * (Math.PI / 180);
        const theta = (lon + 180) * (Math.PI / 180);
        const x = -(radius * Math.sin(phi) * Math.cos(theta));
        const z = radius * Math.sin(phi) * Math.sin(theta);
        const y = radius * Math.cos(phi);
        return new THREE.Vector3(x, y, z);
    }

    // 7. Colombo, Sri Lanka Hub (HQ Beacon)
    const colomboCoords = { lat: 6.9271, lon: 79.8612 };
    const colomboPos = latLonToVector3(colomboCoords.lat, colomboCoords.lon, 1.008);

    const pinGroup = new THREE.Group();
    pinGroup.position.copy(colomboPos);
    pinGroup.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), colomboPos.clone().normalize());

    // Golden Beacon Core Point
    const pinCoreGeo = new THREE.SphereGeometry(0.018, 16, 16);
    const pinCoreMat = new THREE.MeshBasicMaterial({ color: 0xf59e0b });
    const pinCore = new THREE.Mesh(pinCoreGeo, pinCoreMat);
    pinGroup.add(pinCore);

    // Pulsing Outer Radar Wave Ring
    const pulseRingGeo = new THREE.RingGeometry(0.02, 0.042, 32);
    const pulseRingMat = new THREE.MeshBasicMaterial({
        color: 0xfbbf24,
        side: THREE.DoubleSide,
        transparent: true,
        opacity: 0.9
    });
    const pulseRing = new THREE.Mesh(pulseRingGeo, pulseRingMat);
    pulseRing.rotation.x = Math.PI / 2;
    pinGroup.add(pulseRing);

    earthGroup.add(pinGroup);

    // 8. International Connection Arcs (15+ Countries Served)
    const globalHubs = [
        { name: 'London, UK', lat: 51.5074, lon: -0.1278 },
        { name: 'Dubai, UAE', lat: 25.2048, lon: 55.2708 },
        { name: 'Singapore, SG', lat: 1.3521, lon: 103.8198 },
        { name: 'Melbourne, AU', lat: -37.8136, lon: 144.9631 },
        { name: 'Tokyo, JP', lat: 35.6762, lon: 139.6503 },
        { name: 'New York, US', lat: 40.7128, lon: -74.0060 },
        { name: 'Toronto, CA', lat: 43.6532, lon: -79.3832 }
    ];

    const arcGroup = new THREE.Group();
    const arcParticles = [];

    function createCurvedArc(p1, p2, elevation = 0.22) {
        const mid = p1.clone().add(p2).multiplyScalar(0.5);
        const midLength = mid.length();
        mid.normalize().multiplyScalar(midLength + elevation);

        const curve = new THREE.QuadraticBezierCurve3(p1, mid, p2);
        const points = curve.getPoints(45);
        const geometry = new THREE.BufferGeometry().setFromPoints(points);

        const material = new THREE.LineBasicMaterial({
            color: 0x38bdf8,
            transparent: true,
            opacity: 0.42,
            linewidth: 1
        });
        const line = new THREE.Line(geometry, material);
        arcGroup.add(line);

        // Traveling data light packet
        const particleGeo = new THREE.SphereGeometry(0.011, 8, 8);
        const particleMat = new THREE.MeshBasicMaterial({ color: 0xfbbf24 });
        const particle = new THREE.Mesh(particleGeo, particleMat);
        arcGroup.add(particle);

        arcParticles.push({
            mesh: particle,
            curve: curve,
            progress: Math.random()
        });

        // Destination node dot
        const hubGeo = new THREE.SphereGeometry(0.012, 12, 12);
        const hubMat = new THREE.MeshBasicMaterial({ color: 0x60a5fa });
        const hubDot = new THREE.Mesh(hubGeo, hubMat);
        hubDot.position.copy(p2);
        arcGroup.add(hubDot);
    }

    globalHubs.forEach(hub => {
        const hubPos = latLonToVector3(hub.lat, hub.lon, 1.008);
        createCurvedArc(colomboPos, hubPos);
    });

    earthGroup.add(arcGroup);

    // 9. Responsive Layout & Sizing
    function handleResize() {
        if (!container) return;
        const rect = container.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;

        if (width === 0 || height === 0) return;

        camera.aspect = width / height;
        camera.updateProjectionMatrix();

        renderer.setSize(width, height, false);

        // Responsive positioning:
        // Position Earth prominently in the center-right backdrop
        // Left text is 100% protected by the seamless white luminous halo
        const winWidth = window.innerWidth;
        if (winWidth >= 1200) {
            globePivot.position.x = 0.58;
            globePivot.position.y = 0.05;
            globePivot.scale.set(0.96, 0.96, 0.96);
        } else if (winWidth >= 992) {
            globePivot.position.x = 0.48;
            globePivot.position.y = 0.02;
            globePivot.scale.set(0.86, 0.86, 0.86);
        } else if (winWidth >= 768) {
            globePivot.position.x = 0.28;
            globePivot.position.y = -0.15;
            globePivot.scale.set(0.75, 0.75, 0.75);
        } else if (winWidth >= 576) {
            globePivot.position.x = 0.0;
            globePivot.position.y = -0.22;
            globePivot.scale.set(0.65, 0.65, 0.65);
        } else {
            globePivot.position.x = 0.0;
            globePivot.position.y = -0.28;
            globePivot.scale.set(0.56, 0.56, 0.56);
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize();

    // 10. Subtle Interactive Mouse Parallax
    let targetRotationX = 0;
    let targetRotationY = 0;
    let mouseX = 0;
    let mouseY = 0;

    if (heroSection) {
        heroSection.addEventListener('mousemove', function (e) {
            const rect = heroSection.getBoundingClientRect();
            mouseX = ((e.clientX - rect.left) / rect.width) * 2 - 1;
            mouseY = -(((e.clientY - rect.top) / rect.height) * 2 - 1);
            targetRotationY = mouseX * 0.22;
            targetRotationX = mouseY * 0.16;
        });

        heroSection.addEventListener('mouseleave', function () {
            targetRotationX = 0;
            targetRotationY = 0;
        });
    }

    // 11. Intersection Observer (Saves 100% GPU/battery when scrolled away)
    let isVisible = true;
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                isVisible = entry.isIntersecting;
            });
        }, { threshold: 0.05 });
        observer.observe(heroSection);
    }

    // 12. 60 FPS Render Loop
    let pulseScale = 1.0;
    let pulseOpacity = 0.9;

    function animate() {
        requestAnimationFrame(animate);

        if (!isVisible) return;

        // Realistic continuous axial Earth rotation (~0.0013 rad/frame)
        earthGroup.rotation.y += 0.0013;

        // Dynamic cloud layer drifts independently (troposphere atmospheric wind)
        if (cloudsMesh) {
            cloudsMesh.rotation.y += 0.00035;
        }

        // Radar pulse wave expansion for Colombo HQ
        pulseScale += 0.024;
        pulseOpacity -= 0.014;
        if (pulseScale > 2.7) {
            pulseScale = 1.0;
            pulseOpacity = 0.9;
        }
        pulseRing.scale.set(pulseScale, pulseScale, pulseScale);
        pulseRingMat.opacity = Math.max(0, pulseOpacity);

        // Update data pulse particles along curved flight arcs
        arcParticles.forEach(p => {
            p.progress += 0.0075;
            if (p.progress > 1) p.progress = 0;
            const pt = p.curve.getPoint(p.progress);
            p.mesh.position.copy(pt);
        });

        // Eased mouse parallax response
        globePivot.rotation.y += (targetRotationY - globePivot.rotation.y) * 0.05;
        globePivot.rotation.x += ((9 * Math.PI / 180 + targetRotationX) - globePivot.rotation.x) * 0.05;

        renderer.render(scene, camera);
    }

    animate();
})();
