import { WorkoutExercise } from "./workout_exercise";

export interface CheckedExercise{
    workoutExercise: WorkoutExercise;
    checked:boolean;
    display:boolean;
  }