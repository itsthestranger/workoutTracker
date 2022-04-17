import { Component, OnInit } from '@angular/core';
import { TrackerService } from '../tracker.service';
import { WorkoutTracker } from "../workout_tracker";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { Location } from "@angular/common";
import { Subscription, timer } from 'rxjs';
import { map } from 'rxjs/operators';
import { WorkoutExercise } from "../workout_exercise";
import { WorkoutTrackerExercise } from "../workout_tracker_exercise";

@Component({
  selector: 'app-workout-tracker',
  templateUrl: './workout-tracker.component.html',
  styleUrls: ['./workout-tracker.component.css']
})
export class WorkoutTrackerComponent implements OnInit {
  tracker?:WorkoutTracker;
  exercises:WorkoutTrackerExercise[] = [];
  
  duration_tracker: string = "";

  paused = false;

  timer_subscription:Subscription = new Subscription();
  

  constructor(
    private trackerService:TrackerService,
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
      this.getTracker();
    }
  }


  getTracker(){
    const id = Number(this.route.snapshot.paramMap.get('id'));
    const workoutplan_id = Number(this.route.snapshot.paramMap.get('workoutplan_id'));
    this.trackerService.getTracker().subscribe((tracker) => {
      console.log(`tracker started id: ${tracker.id}`)
      this.tracker = tracker;

      let exercises_tmp:WorkoutTrackerExercise[] = [];
    
      if(this.tracker){
        this.tracker.workout.exercises.forEach((exercise) => {
          let tracker_exercises = this.tracker?.tracker_sets.filter((x) => x.exercise_id == exercise.exercise.id);
          if(tracker_exercises && tracker_exercises.length > 0){
            exercises_tmp.push({exercise:exercise.exercise, exercise_sets:tracker_exercises});
          }
        });
      }
      this.exercises = exercises_tmp;

      this.trackerTimer();
    });
    
  }

  trackerTimer():void{
    if(this.tracker){
      console.log("start tracker ....")
      let startDate = new Date(this.tracker.start);
      this.timer_subscription = timer(1000,1000)
        .pipe(
          map((x: number) => {
            const nextDate = new Date(startDate.getTime());
            nextDate.setSeconds(nextDate.getSeconds() + x);
            return nextDate;
          })
        )
        .subscribe(date => {
          var difference = date.getTime() - startDate.getTime();
          var days = Math.floor(difference / (1000 * 60 * 60 * 24));
          var hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
          var seconds = Math.floor((difference % (1000 * 60)) / 1000);
      
          this.duration_tracker = hours + ":" + minutes + ":" + seconds;
        });


    }  
  }
/*
  getExercise(id:number):WorkoutExercise{
    let index = this.exercises.findIndex(exercise => exercise.exercise.id === id);
    if(index){
      return this.exercises[index];
    }
    return this.exercises[0];
  }*/

  toggleTimer():void{
    if(this.paused){
      this.paused = false;
    }else{
      this.paused = true;
    }
  }

  stopTracker():void{
    if(this.tracker){
      this.trackerService.stopTracker(this.tracker).subscribe(); 
    }

  }
    
}