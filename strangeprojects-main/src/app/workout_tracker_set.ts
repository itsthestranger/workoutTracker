export interface WorkoutTrackerSet{
    id: number;
    tracker_id: number;
    workout_id:number;
    exercise_id:number;
    reps:number;
    weight:number;
    feeling:number;
    recent_reps:number|null;
    recent_weight:number|null;

}