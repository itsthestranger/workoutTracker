import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule, ReactiveFormsModule } from "@angular/forms";
import { HttpClientModule } from "@angular/common/http";
import { AppComponent } from './app.component';
import { ExercisesComponent } from './exercises/exercises.component';
import { ExerciseDetailComponent } from './exercise-detail/exercise-detail.component';
import { AppRoutingModule } from './app-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ExerciseSearchComponent } from './exercise-search/exercise-search.component';
import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './register/register.component';
import { WorkoutsComponent } from './workouts/workouts.component';
import { WorkoutDetailComponent } from './workout-detail/workout-detail.component';
import { WorkoutTrackerComponent } from './workout-tracker/workout-tracker.component';
import { TrackedWorkoutsComponent } from './tracked-workouts/tracked-workouts.component';
import { WorkoutSearchComponent } from './workout-search/workout-search.component';
import { WorkoutplansComponent } from './workoutplans/workoutplans.component';
import { WorkoutplanBuilderComponent } from './workoutplan-builder/workoutplan-builder.component';
import { WorkoutplanDetailComponent } from './workoutplan-detail/workoutplan-detail.component';
import { UserProfileComponent } from './user-profile/user-profile.component';
import { ChangePasswordComponent } from './change-password/change-password.component';
import { UserSearchComponent } from './user-search/user-search.component';
import { ActiveWorkoutplanComponent } from './active-workoutplan/active-workoutplan.component';
import { PageNotFoundComponent } from './page-not-found/page-not-found.component';
import { ActiveWorkoutplanOverviewComponent } from './active-workoutplan-overview/active-workoutplan-overview.component';
import { AboutComponent } from './about/about.component';

@NgModule({
  declarations: [
    AppComponent,
    ExercisesComponent,
    ExerciseDetailComponent,
    DashboardComponent,
    ExerciseSearchComponent,
    LoginComponent,
    RegisterComponent,
    WorkoutsComponent,
    WorkoutDetailComponent,
    WorkoutTrackerComponent,
    TrackedWorkoutsComponent,
    WorkoutSearchComponent,
    WorkoutplansComponent,
    WorkoutplanBuilderComponent,
    WorkoutplanDetailComponent,
    UserProfileComponent,
    ChangePasswordComponent,
    UserSearchComponent,
    ActiveWorkoutplanComponent,
    PageNotFoundComponent,
    ActiveWorkoutplanOverviewComponent,
    AboutComponent
  ],
  imports: [
    BrowserModule,
    FormsModule,
    ReactiveFormsModule,
    AppRoutingModule,
    HttpClientModule
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
