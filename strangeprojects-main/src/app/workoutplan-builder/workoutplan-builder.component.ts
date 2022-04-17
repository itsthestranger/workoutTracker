import { Component, OnInit } from '@angular/core';
import { Workout } from '../workout';
import { WorkoutService } from '../workout.service';
import { Workoutplan } from "../workoutplan";
import { WorkoutplanService } from '../workoutplan.service';
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";

@Component({
  selector: 'app-workoutplan-builder',
  templateUrl: './workoutplan-builder.component.html',
  styleUrls: ['./workoutplan-builder.component.css']
})
export class WorkoutplanBuilderComponent implements OnInit {

  workoutplan?:Workoutplan;
  workouts:Workout[] = [];
  initial:boolean = false;

  constructor(
    private workoutplanService: WorkoutplanService,
    private workoutService: WorkoutService,
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
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
      this.getWorkouts();
      this.getWorkoutplanBuilder();
    }
  }

  getWorkoutplanBuilder(){
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if(id == 0){
      this.initial = true;
      this.workoutplanService.buildWorkoutplan().subscribe(workoutplan => {
        this.workoutplan = workoutplan;
        this.workoutplan.weeks.forEach(week => {
          week.days.sort((a, b) => (a.day > b.day) ? 1 : -1);
        });
      });
    }else{
      this.initial = false;
      this.workoutplanService.editWorkoutplan(id).subscribe(workoutplan => {
        this.workoutplan = workoutplan;
        this.workoutplan.weeks.forEach(week => {
          week.days.sort((a, b) => (a.day > b.day) ? 1 : -1);
        });
      });
    }
  }

  getWorkouts(){
    this.workoutService.getAllWorkouts().subscribe(workouts => this.workouts = workouts);
  }

  updateWorkoutplan(){
    if (this.workoutplan){
      console.log(this.workoutplan);
      this.workoutplanService.updateWorkoutplan(this.workoutplan).subscribe((ret) => this.location.back());
    }
  }

  addWeek(){
    if (this.workoutplan){
      this.workoutplanService.addWeek(this.workoutplan.id).subscribe(week => {
        this.workoutplan?.weeks.push(week);
      });
      this.workoutplan.duration += 1;
    }

  }
  removeWeek(){
    if (this.workoutplan){
      this.workoutplan.weeks.pop();
      this.workoutplanService.removeWeek(this.workoutplan.id).subscribe();
      this.workoutplan.duration -= 1;
    }
  }

  setDeload(week_id:number, set:boolean){
    if(this.workoutplan){
      this.workoutplan.weeks.find(x => x.id == week_id)!.deload = set;
    }
  }

  goBack(): void{
    this.location.back();
  }

  compareWorkouts(w1: Workout, w2: Workout): boolean {
    return w1 && w2 ? w1.id === w2.id : w1 === w2;
}
}
