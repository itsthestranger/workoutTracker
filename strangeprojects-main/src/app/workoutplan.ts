import { WorkoutplanWeek } from "./workoutplan_week";

export enum Target{
    none = 0,
    hypertrophy = 1,
    strength = 2,
    endurance = 3,
}

export interface Workoutplan{
    id:number;
    name:string;
    duration:number;
    target:Target;
    note:string;
    weeks:WorkoutplanWeek[];
}