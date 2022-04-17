import { WorkoutTrackerSet } from "./workout_tracker_set";
import { Workout } from "./workout";

export interface WorkoutTracker{
    id: number;
    workout: Workout;
    start:Date;
    end:Date;
    workoutplan_id:number|null;
    tracker_sets:WorkoutTrackerSet[];
}