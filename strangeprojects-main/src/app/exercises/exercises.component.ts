import { Component, OnInit } from '@angular/core';
import {Exercise} from '../exercise';
import { ExerciseService } from "../exercise.service";
import { Bodypart } from "../bodypart";
import { Equipment } from "../equipment";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from "../app.service";
import { Observable, Subject } from "rxjs";
import { debounceTime, distinctUntilChanged, switchMap } from "rxjs/operators";

@Component({
  selector: 'app-exercises',
  templateUrl: './exercises.component.html',
  styleUrls: ['./exercises.component.css']
})
export class ExercisesComponent implements OnInit {
  exercises: Exercise[] = [];
  bodyparts: Bodypart[] = [];
  equipments: Equipment[] = [];

  filtered_exercises: Exercise[] = [];
  bodyparts_filter: Bodypart[] = [];
  equipments_filter: Equipment[] = [];

  selected_bodypart: number = 1;
  selected_equipment: number = 1;

  selected_bodypart_filter: number = -1;
  selected_equipment_filter: number = -1;

  selected_rating_filter: number = -1;

  displayFilter:boolean = false;
  displayCreate:boolean = false;

  search_exercises$!: Observable<Exercise[]>;
  private searchTerms = new Subject<string>();
  searchActive:boolean=false;

  constructor(
    private exerciseService: ExerciseService,
    private accountService: AccountService,
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
      this.getExercises();
      this.getBodyparts();
      this.getEquipment();
    }
  }

  getExercises(): void{
    this.exerciseService.getExercises().subscribe((exercises) => {
      //console.log(exercises)
      this.exercises = exercises;
      this.filtered_exercises = exercises;});
  }

  getBodyparts():void{
    this.exerciseService.getBodyparts().subscribe((bodyparts) => {
      this.bodyparts = bodyparts;
      this.bodyparts_filter = bodyparts;
      this.bodyparts_filter.push({id:-1,name:"no Filter"});
    });
  }
  getEquipment():void{
    this.exerciseService.getEquipment().subscribe((equipment) => {
      this.equipments = equipment;
      this.equipments_filter = equipment;
      this.equipments_filter.push({id:-1,name:"no Filter"});
    });
  }

  getBodypartById(id:number):Bodypart{
    let index = this.bodyparts.findIndex(h => h.id === id);
    if(index < 0){
      return this.bodyparts[0];
    }
    return this.bodyparts[index];
  }
  getEquipmentById(id:number):Equipment{
    let index = this.equipments.findIndex(h => h.id === id);
    if(index < 0){
      return this.equipments[0];
    }
    return this.equipments[index];
  }
  add(name: string, bodypart_id:number, equipment_id:number, note:string): void{
    name = name.trim();
    if (!name){
      return;
    }
    console.log(`add exercise called with: ${name}, ${bodypart_id}, ${equipment_id}, ${note}`)
    let bodypart = this.getBodypartById(bodypart_id);
    let equipment = this.getEquipmentById(equipment_id);

    this.exerciseService.addExercise({name, bodypart, equipment, note} as Exercise)
      .subscribe(exercise => {this.exercises.push(exercise);this.displayCreate = false;});
  }

  delete(exercise: Exercise): void {
    this.exercises = this.exercises.filter(h => h !== exercise);
    this.exerciseService.deleteExercise(exercise.id).subscribe();
  }

  filterExercises(){
    console.log(`sel bodypart: ${this.selected_bodypart_filter}, sel equipment: ${this.selected_equipment_filter}`)
    let filtered_exer: Exercise[] = [];
    this.exercises.forEach((exercise) => {
      if(this.selected_bodypart_filter == -1 && this.selected_equipment_filter == -1){
        //no filter on both -> add all exercises
        filtered_exer.push(exercise);
      }
      else if(this.selected_equipment_filter == -1){
        if(exercise.bodypart.id == this.selected_bodypart_filter){
          filtered_exer.push(exercise);
        }
      }
      else if(this.selected_bodypart_filter == -1){
        if(exercise.equipment.id == this.selected_equipment_filter){
          filtered_exer.push(exercise);
        }
      }
      else{
        if(exercise.bodypart.id == this.selected_bodypart_filter && exercise.equipment.id == this.selected_equipment_filter){
          filtered_exer.push(exercise);
        }
      }
    });
    console.log(filtered_exer);
    this.filtered_exercises = filtered_exer;
    this.displayFilter = false;
  }

  toggleFilter(){
    if(this.displayFilter){
      this.displayFilter = false;
    }else{
      this.displayFilter = true;
    }
  }
  toggleCreate(){
    if(this.displayCreate){
      this.displayCreate = false;
    }else{
      this.displayCreate = true;
    }
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
  
}
