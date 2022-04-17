import { Component, OnInit } from '@angular/core';
import { TrackerService } from '../tracker.service';
import { WorkoutTracker } from "../workout_tracker";
import { Exercise } from "../exercise";
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from "../app.service";
import { WorkoutTrackerExercise } from '../workout_tracker_exercise';




@Component({
  selector: 'app-tracked-workouts',
  templateUrl: './tracked-workouts.component.html',
  styleUrls: ['./tracked-workouts.component.css']
})
export class TrackedWorkoutsComponent implements OnInit {

  tracked_workouts:WorkoutTracker[] = [];

  constructor(
    private trackerService:TrackerService,
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private appService:AppService
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
      this.getTrackedWorkouts();
    }
  }

  padNumber(num: number, size: number) {
    var s = "0000" + num;
    return s.substr(s.length-size);
}

  calcDuration(start: Date, end: Date): string{
    let startDate = new Date(start);
    let endDate = new Date(end);
    
    let duration = "";
    let difference = endDate.getTime() - startDate.getTime();
    let days = Math.floor(difference / (1000 * 60 * 60 * 24));
    let hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    let minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    let seconds = Math.floor((difference % (1000 * 60)) / 1000);

    duration = this.padNumber(hours, 2) + ":" + this.padNumber(minutes, 2) + ":" + this.padNumber(seconds, 2);
    return duration;
  }

  getTrackedWorkouts(){
    this.trackerService.getTrackedWorkouts().subscribe(workouts => 
      {
        this.tracked_workouts = workouts;
        this.tracked_workouts.sort((a, b) => (a.end < b.end) ? 1 : -1);
      })
  }

  getTrackerExercises(tracker_id:number):WorkoutTrackerExercise[]{
    let exercises:WorkoutTrackerExercise[] = [];
    let tracker = this.tracked_workouts.find((element) => element.id == tracker_id);
    if(tracker){
      tracker.workout.exercises.forEach((exercise) => {
        let tracker_exercises = tracker?.tracker_sets.filter((x) => x.exercise_id == exercise.exercise.id);
        if(tracker_exercises && tracker_exercises.length > 0){
          exercises.push({exercise:exercise.exercise, exercise_sets:tracker_exercises});
        }
      });
    }
    return exercises;
  }
}
