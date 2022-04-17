import { Component, ElementRef, OnInit  } from '@angular/core';
import { AccountService } from "../account.service";
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from "../app.service";
import { WorkoutplanTracker } from '../workoutplan_tracker';
import { TrackerService } from '../tracker.service';
import { WorkoutTracker } from '../workout_tracker';
import { Workout } from '../workout';
import { ValueConverter } from '@angular/compiler/src/render3/view/template';
import { WorkoutTrackerExercise } from "../workout_tracker_exercise";

export interface WorkoutplanDisplay{
  tracker: boolean,
  workout: WorkoutTracker|Workout,
  hover:boolean,
  deload:boolean
}

@Component({
  selector: 'app-active-workoutplan',
  templateUrl: './active-workoutplan.component.html',
  styleUrls: ['./active-workoutplan.component.css']
})

export class ActiveWorkoutplanComponent implements OnInit {

  workoutplan_tracker?: WorkoutplanTracker;

  current_day: Date = new Date();

  workoutplan_workouts?: (WorkoutTracker|Workout)[] = [];

  workoutplan_display: WorkoutplanDisplay[] = [];

  todaysWorkout?: WorkoutplanDisplay;
  tomorrowsWorkout?:WorkoutplanDisplay;

  tracker_display?:WorkoutTracker;
  display_tracker:boolean=false;

  constructor(
    private accountService: AccountService,
    private route: ActivatedRoute,
    private router: Router,
    private appService: AppService,
    private trackerService: TrackerService
  ) { }

  asWorkout(value:WorkoutTracker|Workout): Workout{return value as Workout;}
  asWorkoutTracker(value:WorkoutTracker|Workout): WorkoutTracker{return value as WorkoutTracker;}

  ngOnInit(): void {
    if(!localStorage.getItem('token')){
      this.router.navigate(["/login"]);
    }else{
      this.accountService.isLoggedIn().subscribe(ret => {
        if(!ret.loggedIn){
          this.router.navigate(["/login"]);
        }
      });
      this.getWorkoutplanTracker();
      
    }
  }

  getWorkoutplanTracker(){
    this.trackerService.getWorkoutplanTracker().subscribe(plan => {
      this.workoutplan_tracker = plan;
      this.workoutplan_tracker.workoutplan.weeks.forEach(week => {
        week.days.sort((a, b) => (a.day > b.day) ? 1 : -1);
      });
      console.log(plan);
      this.getWorkoutplanWorkouts();
    });
  }

  getWorkoutplanWorkouts(){
    //iterate through days of workoutplan -> track either workoutTracker or workout to workoutplan_workouts array

    if (this.workoutplan_tracker){
      let startDate = this.getStart();
      let endDate = new Date(startDate!);
      endDate.setDate(endDate.getDate() + this.workoutplan_tracker?.workoutplan.duration * 7)
      //console.log(`start date: ${startDate}, end date: ${endDate}`);
      let currentDate = new Date(startDate!)
      //console.log("current date: "+ currentDate);
      //while (currentDate !== endDate) {
      for (let index = 0; index < this.workoutplan_tracker?.workoutplan.duration * 7; index++) {
        let daysOfCurrMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();
        let diff = (currentDate.getDate() - startDate!.getDate()) + (currentDate.getMonth() - startDate!.getMonth()) * daysOfCurrMonth;
        let currWeek = Math.floor(diff/7);
        //console.log("currDay: " + (index % 7) + "currWeek: " + currWeek + ", diff: " + diff + ", daysOfCurrMonth: " + daysOfCurrMonth);
        this.workoutplan_workouts?.push(this.workoutplan_tracker.workoutplan.weeks[currWeek].days[index % 7].workout);
        this.workoutplan_display.push({tracker:false, workout:this.workoutplan_tracker.workoutplan.weeks[currWeek].days[index % 7].workout,hover:false,deload:this.workoutplan_tracker.workoutplan.weeks[currWeek].deload})
        //console.log(this.workoutplan_workouts?.length)
        for (let j = 0; j < this.workoutplan_tracker.tracked_workouts.length; j++) {
          const tracked_workout = this.workoutplan_tracker.tracked_workouts[j];
          const tracked_workout_startDate = new Date(tracked_workout.start);
          if(tracked_workout_startDate.getDate() === currentDate.getDate() && tracked_workout_startDate.getMonth() === currentDate.getMonth()){
            //remove added workout to add tracked workout instead
            this.workoutplan_workouts?.pop();
            this.workoutplan_workouts?.push(tracked_workout);
            this.workoutplan_display.pop();
            this.workoutplan_display.push({tracker:true, workout:tracked_workout,hover:false,deload:this.workoutplan_tracker.workoutplan.weeks[currWeek].deload})

            break;
          }
          
        }
        currentDate.setDate(currentDate.getDate() + 1);
      }

      //console.log(this.workoutplan_workouts)
      //this.workoutplan_workouts?.forEach(workout => {if((workout as Workout).name){this.workoutplan_display.push({tracker:false, workout,hover:false});}else{this.workoutplan_display.push({tracker:true, workout,hover:false});}});
      //console.log(this.workoutplan_display);
      this.getTodaysWorkout();
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

  showTracker(tracker:WorkoutTracker){
    if(!this.display_tracker){
      this.tracker_display = tracker;
      this.display_tracker = true;
    }else{
      this.display_tracker = false;
    }
  }

  getTrackerExercises(tracker:WorkoutTracker):WorkoutTrackerExercise[]{
    let exercises:WorkoutTrackerExercise[] = [];
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

  getTodaysWorkout(){
    for (let i = 0; i < this.workoutplan_display.length; i++) {
      if(this.isTodaysWorkout(i)){
        console.log("fetched today's workout")
        this.todaysWorkout = this.workoutplan_display[i];
        if(this.todaysWorkout.tracker || this.todaysWorkout.workout.id === 1){
          this.tomorrowsWorkout = this.workoutplan_display[i + 1];
        }
      }
      
    }
    console.log(this.todaysWorkout);
  }

  getStart(){
    if(this.workoutplan_tracker)
      return new Date(this.workoutplan_tracker?.start)
    return;
  }

  getCurrentWeek(){
    if(this.workoutplan_tracker){
      for (let index = 0; index < this.workoutplan_tracker?.workoutplan.weeks.length; index++) {
        //const element = array[index];
        
      }
    }
  }

  isTodaysWorkout(i:number):boolean{
    //i = i + 1;
    let calcDate = new Date(this.getStart()!);
    calcDate.setDate(calcDate.getDate() + i)
    //console.log(`curr: ${this.current_day.getDate()} calc: ${calcDate.getDate()}`)
    if(this.current_day.getFullYear() == calcDate.getFullYear() && this.current_day.getMonth() == calcDate.getMonth() && this.current_day.getDate() == calcDate.getDate()){
      return true;
    }
    return false;
  }

  getDay(i:number){
    let calcDate = new Date(this.getStart()!);
    calcDate.setDate(calcDate.getDate() + i)
    return calcDate;
  }

  validDay(i:number):boolean{
    let calcDate = new Date(this.getStart()!);
    calcDate.setDate(calcDate.getDate() + i)
    //console.log(`curr: ${this.current_day.getDate()} calc: ${calcDate.getDate()}`)
    if(calcDate >= this.current_day || (calcDate.getDate() === this.current_day.getDate() && calcDate.getMonth() === this.current_day.getMonth())){
      return true;
    }
    return false;
  }

  show(index:number){
    this.workoutplan_display[index].hover = true;
  }
  hide(index:number){
    this.workoutplan_display[index].hover = false;
  }


  trackWorkout(workout_id:number, workoutplan_id:number, deload:boolean){
    this.trackerService.startTracker(workout_id, workoutplan_id, deload).subscribe((ret) => this.router.navigate(["/workout_tracker/" + workout_id + "/" + deload]));
  }

}
