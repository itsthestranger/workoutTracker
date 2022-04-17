import { Workoutplan } from "./workoutplan";
import { WorkoutTracker } from "./workout_tracker";

export interface WorkoutplanTracker{
    id: number;
    workoutplan: Workoutplan;
    start:Date;
    end:Date;
    active:number;
    tracked_workouts:WorkoutTracker[];
}