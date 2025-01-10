#version 300 es
precision highp float;

in vec3 position;
in vec3 normal;
in vec2 texCoord;

uniform mat4 modelMatrix;
uniform mat4 viewMatrix;
uniform mat4 projectionMatrix;
uniform float pumpPressure; // 0.0 to 1.0
uniform float time;

out vec2 vTexCoord;
out vec3 vNormal;
out vec3 vFragPos;
out float vPressure;

void main() {
    vTexCoord = texCoord;
    vNormal = mat3(transpose(inverse(modelMatrix))) * normal;
    vFragPos = vec3(modelMatrix * vec4(position, 1.0));
    vPressure = pumpPressure;
    
    // Add subtle expansion based on pressure
    float expansion = 1.0 + pumpPressure * 0.1;
    vec3 expandedPosition = position * expansion;
    
    // Add slight wobble for rubber parts
    float wobble = sin(position.y * 5.0 + time) * 0.01 * pumpPressure;
    vec3 wobbledPosition = expandedPosition + vec3(wobble, 0.0, wobble);
    
    gl_Position = projectionMatrix * viewMatrix * modelMatrix * vec4(wobbledPosition, 1.0);
} 