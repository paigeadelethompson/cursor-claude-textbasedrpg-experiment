#version 300 es
precision highp float;

in vec3 position;
in vec3 normal;
in vec2 texCoord;

uniform mat4 modelMatrix;
uniform mat4 viewMatrix;
uniform mat4 projectionMatrix;
uniform vec3 lightPosition;

out vec2 vTexCoord;
out vec3 vNormal;
out vec3 vFragPos;

void main() {
    vTexCoord = texCoord;
    vNormal = mat3(transpose(inverse(modelMatrix))) * normal;
    vFragPos = vec3(modelMatrix * vec4(position, 1.0));
    
    // Add subtle floating animation
    float floatOffset = sin(position.y + time * 2.0) * 0.05;
    vec3 animatedPosition = position + vec3(0.0, floatOffset, 0.0);
    
    gl_Position = projectionMatrix * viewMatrix * modelMatrix * vec4(animatedPosition, 1.0);
} 