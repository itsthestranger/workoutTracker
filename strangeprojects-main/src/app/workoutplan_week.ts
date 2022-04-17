import { WorkoutplanDay } from "./workoutplan_day";

export interface WorkoutplanWeek{
    id:number;
    deload:boolean;
    days:WorkoutplanDay[];
}