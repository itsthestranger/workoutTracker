import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { ExercisesComponent } from "./exercises/exercises.component";
import { WorkoutsComponent } from "./workouts/workouts.component";
import { DashboardComponent } from "./dashboard/dashboard.component";
import { ExerciseDetailComponent } from "./exercise-detail/exercise-detail.component";
import { WorkoutDetailComponent } from "./workout-detail/workout-detail.component";
import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './register/register.component';
import { WorkoutTrackerComponent } from "./workout-tracker/workout-tracker.component";
import { TrackedWorkoutsComponent } from "./tracked-workouts/tracked-workouts.component";
import { WorkoutplansComponent } from './workoutplans/workoutplans.component';
import { WorkoutplanBuilderComponent } from './workoutplan-builder/workoutplan-builder.component';
import { WorkoutplanDetailComponent } from './workoutplan-detail/workoutplan-detail.component';
import { UserProfileComponent } from './user-profile/user-profile.component';
import { ChangePasswordComponent } from './change-password/change-password.component';
import { PageNotFoundComponent } from './page-not-found/page-not-found.component';
import { ActiveWorkoutplanOverviewComponent } from './active-workoutplan-overview/active-workoutplan-overview.component';
import { AboutComponent } from './about/about.component';


const routes: Routes = [
  {path: '', redirectTo: '/about', pathMatch: 'full'},
  
  {path: 'about', component: AboutComponent},
  {path: 'exercises', component: ExercisesComponent},
  {path: 'workouts', component: WorkoutsComponent},
  {path: 'tracked_workouts', component: TrackedWorkoutsComponent},
  {path: 'workoutplans', component: WorkoutplansComponent},
  {path: 'workoutplan_builder/:id', component: WorkoutplanBuilderComponent},
  {path: 'dashboard', component: DashboardComponent},
  {path: 'exercise_detail/:id', component: ExerciseDetailComponent},
  {path: 'workout_detail/:id', component: WorkoutDetailComponent},
  {path: 'workoutplan_detail/:id', component: WorkoutplanDetailComponent},
  {path: 'workout_tracker/:id/:workoutplan_id', component: WorkoutTrackerComponent},
  {path: 'active_workoutplan', component: ActiveWorkoutplanOverviewComponent},
  {path: 'login', component: LoginComponent},
  //{path: 'register', component: RegisterComponent},
  {path: 'user_profile/:id', component: UserProfileComponent},
  {path: 'change_password', component: ChangePasswordComponent},
  {path: '**', pathMatch: 'full', component: PageNotFoundComponent}
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
