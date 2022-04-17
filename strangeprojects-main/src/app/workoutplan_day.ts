import { Workout } from "./workout";

export enum Day{
    monday = 0,
    tuesday = 1,
    wednesday = 2,
    thursday = 3,
    friday = 4,
    saturday = 5,
    sunday = 6
}

export interface WorkoutplanDay{
    id:number;
    day:Day;
    note:string;
    workout:Workout;
}