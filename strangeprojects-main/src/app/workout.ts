import { WorkoutExercise } from "./workout_exercise";

export interface Workout{
    id: number;
    name: string;
    exercise_order:string;
    rating:number;
    global:boolean;
    public:boolean;
    exercises:WorkoutExercise[];
}