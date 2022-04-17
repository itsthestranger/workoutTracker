import { Exercise } from "./exercise";
import { WorkoutTrackerSet } from './workout_tracker_set';

export interface WorkoutTrackerExercise{
    exercise:Exercise,
    exercise_sets:WorkoutTrackerSet[]
  }