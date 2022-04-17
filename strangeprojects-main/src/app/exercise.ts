import { Bodypart } from "./bodypart";
import { Equipment } from "./equipment";
import { TrackingUnit } from "./tracking_unit";

export interface Exercise{
    id: number;
    name: string;
    bodypart:Bodypart;
    equipment:Equipment;
    tracking_unit:TrackingUnit;
    rating:number;
    note:string;
    global:boolean;
    public:boolean;
    rep_range:string;
    weight_step_size:number;
    description:string|null;
    visual_path:string|null;
}