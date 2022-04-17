import { Component, OnInit } from '@angular/core';
import { Workout } from '../workout';
import { WorkoutService } from "../workout.service";
import { Exercise } from "../exercise";
import { Observable, Subject } from "rxjs";
import { debounceTime, distinctUntilChanged, switchMap } from "rxjs/operators";
import { ExerciseService } from "../exercise.service";
import { WorkoutExercise } from '../workout_exercise';
import { CheckedExercise } from "../checkedExercise";
import { Bodypart } from "../bodypart";
import { Equipment } from "../equipment";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from "../app.service";
import { TrackerService } from '../tracker.service';


@Component({
  selector: 'app-workouts',
  templateUrl: './workouts.component.html',
  styleUrls: ['./workouts.component.css']
})

export class WorkoutsComponent implements OnInit {

  workouts: Workout[] = [];
  exercises: CheckedExercise[] = [];
  checkedExercises: CheckedExercise[] = [];
  displayCreate:boolean=false;

  filtered_exercises: CheckedExercise[] = [];
  bodyparts_filter: Bodypart[] = [];
  equipments_filter: Equipment[] = [];

  selected_bodypart: number = 1;
  selected_equipment: number = 1;

  selected_bodypart_filter: number = -1;
  selected_equipment_filter: number = -1;

  filterActive:boolean=false;
  exerciseSearchActive:boolean=false;

  search_exercises$!: Observable<Exercise[]>;
  private searchTerms = new Subject<string>();

  workoutSearchActive:boolean=false;

  search_workouts$!: Observable<Workout[]>;
  private WorkoutSearchTerms = new Subject<string>();

  constructor(
    private workoutService:WorkoutService,
    private exerciseService:ExerciseService,
    private accountService: AccountService,
    private trackerService: TrackerService,
    private route: ActivatedRoute,
    private router: Router,
    private appService: AppService
    ) {
      this.appService.loggedInChange.subscribe(value => {
        if(!value){
          console.log("not logged in...");
          this.router.navigate(["/login"]);
        }
      });
     }

  ngOnInit(): void {
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });
      this.getWorkouts();
      this.getExercises();
      this.getCheckedExercises();
      this.getEquipment();
      this.getBodyparts();
    }
  }

  getWorkouts(){
    this.workoutService.getWorkouts().subscribe((workouts) => {
      console.log(workouts);
      if(workouts){
        this.workouts = workouts;
      }
    });
  }
  getExercises(){
    this.exerciseService.getExercises().subscribe((exercises) => {
      exercises.forEach(exercise => {
        let sets = 0;
        let workoutExercise = {exercise, sets};
        let checked = false;
        let display = true;
        this.exercises.push({workoutExercise, checked, display});
      });
      this.filtered_exercises = this.exercises;
    }
    );
  }
  getBodyparts():void{
    this.exerciseService.getBodyparts().subscribe((bodyparts) => {
      this.bodyparts_filter = bodyparts;
      this.bodyparts_filter.push({id:-1,name:"no Filter"});
    });
  }
  getEquipment():void{
    this.exerciseService.getEquipment().subscribe((equipment) => {
      this.equipments_filter = equipment;
      this.equipments_filter.push({id:-1,name:"no Filter"});
    });
  }

  getCheckedExercises(){
    this.checkedExercises = this.exercises.filter(elem => elem.checked);
  }

  add(name: string): void{
    name = name.trim();
    if (!name){
      return;
    }
    console.log(`add workout called with: ${name}`)
    let exercises:WorkoutExercise[] = [];
    let exercise_order:string = "";
    this.checkedExercises.forEach(exercise => {
      exercises.push(exercise.workoutExercise);
      exercise_order = exercise_order + exercise.workoutExercise.exercise.id + ",";
    });
    exercise_order = exercise_order.slice(0,-1);

    this.workoutService.addWorkout({name, exercise_order, exercises} as Workout)
      .subscribe(workout => {this.workouts.push(workout);this.displayCreate = false;});

    this.exercises.forEach(exercise => {
      exercise.checked = false;
      exercise.workoutExercise.sets = 0;
    });

  }

  delete(workout: Workout): void {
    this.workouts = this.workouts.filter(h => h !== workout);
    this.workoutService.deleteWorkout(workout.id).subscribe();
  }

  toggleCreate(){
    if(this.displayCreate){
      this.displayCreate = false;
    }else{
      this.displayCreate = true;
    }
  }

  /*filterBodypartNEquipment(reset:boolean){
    console.log(`sel bodypart: ${this.selected_bodypart_filter}, sel equipment: ${this.selected_equipment_filter}`)
    if(reset){
      this.exercises.forEach((exercise) => {
        exercise.display = true;
      })
      this.filterActive = false;
    }else{
      this.exercises.forEach((exercise) => {      
        if(this.selected_bodypart_filter == -1 && this.selected_equipment_filter == -1){
          //no filter on both -> add all exercises
          exercise.display = true;
        }
        else if(this.selected_equipment_filter == -1){
          if(exercise.workoutExercise.exercise.bodypart.id != this.selected_bodypart_filter){
            exercise.display = false;
          }
        }
        else if(this.selected_bodypart_filter == -1){
          if(exercise.workoutExercise.exercise.equipment.id != this.selected_equipment_filter){
            exercise.display = false;
          }
        }
        else{
          if(exercise.workoutExercise.exercise.bodypart.id != this.selected_bodypart_filter && exercise.workoutExercise.exercise.equipment.id != this.selected_equipment_filter){
            exercise.display = false;
          }
        }

      });
      this.filterActive = true;
    }
  }*/

  filterBodypartNEquipment(){
    console.log(`sel bodypart: ${this.selected_bodypart_filter}, sel equipment: ${this.selected_equipment_filter}`)
    
    this.exercises.forEach((exercise) => {
      if(this.selected_bodypart_filter == -1 && this.selected_equipment_filter == -1){
        exercise.display = true;
      }else if(exercise.workoutExercise.exercise.bodypart.id == this.selected_bodypart_filter && this.selected_equipment_filter == -1){
        exercise.display = true;
      }else if(exercise.workoutExercise.exercise.equipment.id == this.selected_equipment_filter && this.selected_bodypart_filter == -1){
        exercise.display = true;
      }else if(exercise.workoutExercise.exercise.bodypart.id == this.selected_bodypart_filter && exercise.workoutExercise.exercise.equipment.id == this.selected_equipment_filter){
        exercise.display = true
      }else{
        exercise.display = false;
      }
      

    });

    
  }

  search(term: string): void{
    this.searchTerms.next(term);
  }

  exerciseSearchFunction(): void {
    
    this.search_exercises$ = this.searchTerms.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((term: string) => this.exerciseService.searchExercises(term)),
    );

  }

  workoutSearchFunction(): void {
    
    this.search_workouts$ = this.searchTerms.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((term: string) => this.workoutService.searchWorkout(term)),
    );

  }

  getExercise(id:number):CheckedExercise{
    let exercise = this.exercises.find(x => x.workoutExercise.exercise.id == id);
    if (exercise){
      return exercise;
    }else{
      return this.exercises[0];
    }
  }

  updateExerciseCheck(id:number, sets:string){
    this.exercises.forEach((exercise) => {if(exercise.workoutExercise.exercise.id == id){
      if(exercise.checked){
        exercise.checked = false;
        exercise.workoutExercise.sets = parseInt(sets);
      }else{
        exercise.checked = true;
        exercise.workoutExercise.sets = parseInt(sets);
      }
    }})
    this.getCheckedExercises();
    console.log(this.checkedExercises)
  }

  activateExerciseSearch(activate:boolean){
    if(activate){
      this.exerciseSearchFunction();
      this.exerciseSearchActive = true;
    }else{
      this.exerciseSearchActive = false;
    }
  }
  activateWorkoutSearch(activate:boolean){
    if(activate){
      this.workoutSearchFunction();
      this.workoutSearchActive = true;
    }else{
      this.workoutSearchActive = false;
    }
  }

  trackWorkout(workout_id:number, workoutplan_id:number,deload:boolean){
    this.trackerService.startTracker(workout_id, workoutplan_id,deload).subscribe((ret) => this.router.navigate(["/workout_tracker/" + workout_id + "/" + workoutplan_id]));
  }
  
}
