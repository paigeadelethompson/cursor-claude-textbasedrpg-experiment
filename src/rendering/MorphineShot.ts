import { GLProgram } from './GLProgram';
import { Model } from './Model';

export class MorphineShot {
    private program: GLProgram;
    private model: Model;
    private liquidLevel: number = 1.0;

    constructor(gl: WebGL2RenderingContext) {
        this.program = new GLProgram(gl, 
            'shaders/morphine_shot.vert', 
            'shaders/morphine_shot.frag'
        );
        
        this.model = new Model(gl, {
            // Syringe body (cylinder)
            vertices: [
                // ... vertex data for cylinder and plunger ...
            ],
            normals: [
                // ... normal data ...
            ],
            texCoords: [
                // ... texture coordinates ...
            ],
            indices: [
                // ... index data ...
            ]
        });
    }

    public render(
        gl: WebGL2RenderingContext, 
        viewMatrix: mat4,
        projectionMatrix: mat4,
        lightPosition: vec3
    ): void {
        this.program.use();
        
        // Update uniforms
        this.program.setMatrix4('viewMatrix', viewMatrix);
        this.program.setMatrix4('projectionMatrix', projectionMatrix);
        this.program.setVector3('lightPosition', lightPosition);
        this.program.setFloat('liquidLevel', this.liquidLevel);
        
        // Add subtle rotation
        const modelMatrix = mat4.create();
        mat4.rotate(
            modelMatrix,
            modelMatrix,
            performance.now() / 1000,
            [0, 1, 0]
        );
        
        this.program.setMatrix4('modelMatrix', modelMatrix);
        
        // Render the model
        this.model.render(gl);
    }

    public setLiquidLevel(level: number): void {
        this.liquidLevel = Math.max(0, Math.min(1, level));
    }
} 