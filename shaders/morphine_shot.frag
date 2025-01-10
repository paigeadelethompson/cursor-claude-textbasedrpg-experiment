#version 300 es
precision highp float;

in vec2 vTexCoord;
in vec3 vNormal;
in vec3 vFragPos;

uniform vec3 lightPosition;
uniform vec3 viewPosition;
uniform sampler2D liquidTexture;
uniform float liquidLevel; // 0.0 to 1.0

out vec4 fragColor;

void main() {
    // Glass material properties
    vec3 glassColor = vec3(0.9, 0.9, 0.95);
    float glassMetallic = 0.9;
    float glassRoughness = 0.1;
    
    // Liquid material properties
    vec3 liquidColor = vec3(0.8, 0.8, 0.9); // Slightly blue tint
    float liquidOpacity = 0.7;
    
    // Calculate liquid level cutoff
    float liquidCutoff = mix(0.1, 0.9, liquidLevel);
    bool isLiquid = vTexCoord.y < liquidCutoff;
    
    // Lighting calculations
    vec3 N = normalize(vNormal);
    vec3 L = normalize(lightPosition - vFragPos);
    vec3 V = normalize(viewPosition - vFragPos);
    vec3 H = normalize(L + V);
    
    // Fresnel effect for glass
    float fresnel = pow(1.0 - max(dot(N, V), 0.0), 5.0);
    
    // Combine materials based on liquid level
    vec3 color = isLiquid ? liquidColor : glassColor;
    float alpha = isLiquid ? liquidOpacity : (0.5 + fresnel * 0.3);
    
    // PBR lighting
    float NdotL = max(dot(N, L), 0.0);
    float NdotH = max(dot(N, H), 0.0);
    
    vec3 specular = vec3(pow(NdotH, 32.0)) * glassMetallic;
    vec3 diffuse = color * NdotL * (1.0 - glassMetallic);
    
    // Add subtle refraction effect
    if (!isLiquid) {
        vec2 refractionOffset = N.xy * 0.02;
        color += texture(liquidTexture, vTexCoord + refractionOffset).rgb * 0.1;
    }
    
    fragColor = vec4(diffuse + specular, alpha);
} 