import { WorkoutTrackerSet } from "./workout_tracker_set";

export interface ExerciseHistorySet{
    tracker_set:WorkoutTrackerSet;
    tracker_workout_name: string;
    tracker_workoutplan_name:string;
    tracker_date:Date;
  }