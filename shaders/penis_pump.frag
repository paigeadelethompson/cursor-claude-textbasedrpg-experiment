#version 300 es
precision highp float;

in vec2 vTexCoord;
in vec3 vNormal;
in vec3 vFragPos;
in float vPressure;

uniform vec3 lightPosition;
uniform vec3 viewPosition;
uniform float time;
uniform sampler2D pumpTexture;

out vec4 fragColor;

void main() {
    // Material properties
    vec3 plasticColor = vec3(0.9, 0.9, 0.95); // Clear plastic
    vec3 rubberColor = vec3(0.2, 0.2, 0.2); // Black rubber
    float metallic = 0.3;
    float roughness = 0.5;
    
    // Lighting calculations
    vec3 N = normalize(vNormal);
    vec3 L = normalize(lightPosition - vFragPos);
    vec3 V = normalize(viewPosition - vFragPos);
    vec3 H = normalize(L + V);
    
    // Fresnel effect for plastic
    float fresnel = pow(1.0 - max(dot(N, V), 0.0), 5.0);
    
    // Combine materials based on position
    vec3 color = mix(rubberColor, plasticColor, vTexCoord.y);
    float alpha = 0.8 + fresnel * 0.2;
    
    // PBR lighting
    float NdotL = max(dot(N, L), 0.0);
    float NdotH = max(dot(N, H), 0.0);
    
    vec3 specular = vec3(pow(NdotH, 16.0)) * metallic;
    vec3 diffuse = color * NdotL * (1.0 - metallic);
    
    // Add pressure-based effects
    float pressureGlow = pow(vPressure, 2.0);
    vec3 pressureColor = mix(vec3(0.0), vec3(0.2), pressureGlow);
    
    // Combine all effects
    vec3 finalColor = diffuse + specular + pressureColor;
    
    fragColor = vec4(finalColor, alpha);
} 