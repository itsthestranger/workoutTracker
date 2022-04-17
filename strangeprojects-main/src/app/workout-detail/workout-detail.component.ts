import { Component, OnInit } from '@angular/core';
import { Workout } from "../workout";
import { WorkoutService } from "../workout.service";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";
import { WorkoutExercise } from '../workout_exercise';
import { CheckedExercise } from "../checkedExercise";
import { ExerciseService } from '../exercise.service';
import { Observable, Subject } from "rxjs";
import { debounceTime, distinctUntilChanged, switchMap } from "rxjs/operators";
import { Exercise } from "../exercise";
import { Equipment } from "../equipment";
import { Bodypart } from "../bodypart";

@Component({
  selector: 'app-workout-detail',
  templateUrl: './workout-detail.component.html',
  styleUrls: ['./workout-detail.component.css']
})
export class WorkoutDetailComponent implements OnInit {

  workout?: Workout;

  add_exercises = false;
  exercises: CheckedExercise[] = [];

  filtered_exercises: CheckedExercise[] = [];
  bodyparts_filter: Bodypart[] = [];
  equipments_filter: Equipment[] = [];

  selected_bodypart: number = 1;
  selected_equipment: number = 1;

  selected_bodypart_filter: number = -1;
  selected_equipment_filter: number = -1;

  filterActive:boolean=false;
  searchActive:boolean=false;

  search_exercises$!: Observable<Exercise[]>;
  private searchTerms = new Subject<string>();

  edit:boolean=false;

  constructor(
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private workoutService: WorkoutService,
    private exerciseService: ExerciseService,
    private location: Location
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
      this.getWorkout();
      this.getEquipment();
      this.getBodyparts();
    }
  }


  getWorkout(){
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.workoutService.getWorkout(id).subscribe((workout) => {
      console.log(`workout received ${workout.name}`)
      this.workout = workout
    });
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

  delete(exercise: WorkoutExercise): void {
    if(this.workout){
      this.workout.exercises = this.workout.exercises.filter(h => h !== exercise);
      let exercise_order = this.workout.exercise_order.split(",");
      exercise_order = exercise_order.filter(elem => elem != exercise.exercise.id.toString());
      this.workout.exercise_order = exercise_order.join(",");
      this.workoutService.deleteExercise(this.workout.id,exercise.exercise.id).subscribe();
    }
  }

  addExercises(){
    if (this.workout){
      if(this.add_exercises){
        let checked_exercises = this.exercises.filter(elem => elem.checked);
        let new_exercise_order:string = this.workout.exercise_order;
        checked_exercises.forEach(element => {
          this.workout?.exercises.push(element.workoutExercise);
          new_exercise_order = new_exercise_order + "," + element.workoutExercise.exercise.id;
        });
        this.workout.exercise_order = new_exercise_order;
      }
      this.exercises.forEach((exercise) => exercise.checked = false);
      this.selected_bodypart_filter = -1;
      this.selected_equipment_filter = -1;
      console.log(`workout: ${this.workout}`);
    }
  }

  save(): void{
    if (this.workout){
      /*if(this.add_exercises){
        let checked_exercises = this.exercises.filter(elem => elem.checked);
        let new_exercise_order:string = this.workout.exercise_order;
        checked_exercises.forEach(element => {
          this.workout?.exercises.push(element.workoutExercise);
          new_exercise_order = new_exercise_order + "," + element.workoutExercise.exercise.id;
        });
        this.workout.exercise_order = new_exercise_order;
      }*/
      console.log(`workout: ${this.workout}`);
      this.workoutService.updateWorkout(this.workout).subscribe((ret) => this.goBack());
      this.add_exercises = false;
    }
  }
  duplicate(): void{
    if (this.workout){
      console.log(`workout: ${this.workout}`);
      this.workoutService.duplicateWorkout(this.workout).subscribe(() => this.goBack());
    }
  }

  goBack(): void{
    this.location.back();
  }

  displayExercises():void{
    if (!this.add_exercises){
      if(this.exercises.length < 1){
        this.exerciseService.getExercises().subscribe((exercises) => {
          exercises.forEach(exercise => {
            let sets = 0;
            let workoutExercise = {exercise, sets};
            let checked = false;
            let display = true;
            this.exercises.push({workoutExercise, checked, display});
          });
        }
        );
      }else{
        this.exercises.forEach(exercise => {
          exercise.checked = false;
          exercise.workoutExercise.sets = 0;
        });
      }
      this.add_exercises = true;
    }else{
      this.add_exercises = false;
    }
    
  }

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

  searchFunction(): void {
    
    this.search_exercises$ = this.searchTerms.pipe(
      debounceTime(300),
      distinctUntilChanged(),
      switchMap((term: string) => this.exerciseService.searchExercises(term)),
    );

  }

  activateSearch(activate:boolean){
    if(activate){
      this.searchFunction();
      this.searchActive = true;
    }else{
      this.searchActive = false;
    }
  }

  getExercise(id:number):CheckedExercise{
    let exercise = this.exercises.find(x => x.workoutExercise.exercise.id == id);
    if (exercise){
      return exercise;
    }else{
      return this.exercises[0];
    }
  }

  toggleEdit(){
    if(this.edit){
      this.edit = false;
    }else{
      this.edit = true;
    }
  }
}
