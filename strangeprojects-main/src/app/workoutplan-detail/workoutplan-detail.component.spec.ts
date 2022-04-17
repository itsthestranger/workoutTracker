import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WorkoutplanDetailComponent } from './workoutplan-detail.component';

describe('WorkoutplanDetailComponent', () => {
  let component: WorkoutplanDetailComponent;
  let fixture: ComponentFixture<WorkoutplanDetailComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WorkoutplanDetailComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(WorkoutplanDetailComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
