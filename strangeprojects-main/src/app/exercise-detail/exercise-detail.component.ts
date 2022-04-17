import { Component, OnInit, Input } from '@angular/core';
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";
import { ExerciseHistorySet } from "../exercise_history_set";
import { ExerciseService } from "../exercise.service";
import { Exercise } from "../exercise";
import { Bodypart } from "../bodypart";
import { Equipment } from "../equipment";
import { WorkoutTrackerSet } from '../workout_tracker_set';
//import { FileServiceService } from '../file-service.service';

interface ExerciseHistorySets{
  exerciseHistorySets: WorkoutTrackerSet[];
  exerciseTrackerDate: Date;
  exerciseWorkoutName: string;
  exerciseWorkoutplanName:string|null;
}

@Component({
  selector: 'app-exercise-detail',
  templateUrl: './exercise-detail.component.html',
  styleUrls: ['./exercise-detail.component.css']
})


export class ExerciseDetailComponent implements OnInit {

  exercise?: Exercise;

  bodyparts: Bodypart[] = [];
  equipments: Equipment[] = [];

  editing:boolean=false;

  showHistory:boolean=true;

  exerciseHistory: ExerciseHistorySet[] = [];
  exerciseHistorySets: ExerciseHistorySets[] = [];


  fileToUpload: File | null = null;

  constructor(
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private exerciseService: ExerciseService,
    private location: Location,
    //private fileService: FileServiceService
  ) { }

  ngOnInit(): void {
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });
      this.getExercise();
      this.getBodyparts();
      this.getEquipment();
      this.getExerciseHistory();
    }
  }

  filteredBodyparts():Bodypart[]{
    return this.bodyparts.filter(elem => elem.id != this.exercise?.bodypart.id);
  }
  filteredEquipments():Equipment[]{
    return this.equipments.filter(elem => elem.id != this.exercise?.equipment.id);
  }

  getBodyparts():void{
    this.exerciseService.getBodyparts().subscribe((bodyparts) => {
      this.bodyparts = bodyparts;
    });
  }
  getEquipment():void{
    this.exerciseService.getEquipment().subscribe((equipment) => {
      this.equipments = equipment;
    });
  }
  getExercise(): void{
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.exerciseService.getExercise(id)
      .subscribe((exercise) => {
        console.log(`exercise received ${exercise.name}`)
        this.exercise = exercise;
      }
      );
    
  }

  getExerciseHistory():void{
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.exerciseService.getExerciseHistory(id)
      .subscribe((exerciseHistory) => {
        console.log(`exerciseHistory received ${exerciseHistory}`);
        if(exerciseHistory){
          let tmp = exerciseHistory;
          while(tmp.length){
            let tmp_ = exerciseHistory.filter(e => e.tracker_date === tmp[0].tracker_date);
            let trackerSets: WorkoutTrackerSet[] = [];
            tmp_.forEach((e) => trackerSets.push(e.tracker_set));
            let historySet: ExerciseHistorySets = {exerciseHistorySets:trackerSets,exerciseTrackerDate:tmp_[0].tracker_date,exerciseWorkoutName:tmp_[0].tracker_workout_name,exerciseWorkoutplanName:tmp_[0].tracker_workoutplan_name};
            this.exerciseHistorySets.push(historySet);
            tmp = tmp.filter(e => e.tracker_date != tmp_[0].tracker_date);
          }
        }
      }
      );
  }

  save(): void{
    if (this.exercise){
      console.log(`exercise: ${this.exercise}`);
      this.exerciseService.updateExercise(this.exercise).subscribe(() => this.goBack());
    }
  }

  duplicate(): void{
    if (this.exercise){
      console.log(`exercise: ${this.exercise}`);
      this.exerciseService.duplicateExercise(this.exercise).subscribe(() => this.goBack());
    }
  }

  edit(){
    this.editing = true;
  }
  cancel(){
    this.editing = false;
  }

  goBack(): void{
    this.location.back();
  }

  flipDataSwitch(fromHistory:boolean){
    if(fromHistory){
      if(!this.showHistory){
        this.showHistory = true;
      }
    }else{
      if(this.showHistory){
        this.showHistory = false;
      }
    }
  }

  handleFileInput(event:Event) {
    const target= event.target as HTMLInputElement;
    const file: File = (target.files as FileList)[0];
    this.fileToUpload = file;
    
  }

  uploadImage(){
    /*if(this.fileToUpload && this.exercise){
      //upload image to server via service
      console.log(this.fileToUpload);
      this.fileService.uploadExerciseVisual(this.fileToUpload, this.exercise?.id).subscribe();
    }*/
  }

}
