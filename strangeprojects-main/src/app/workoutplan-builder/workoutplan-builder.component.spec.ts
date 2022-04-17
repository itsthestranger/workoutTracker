import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WorkoutplanBuilderComponent } from './workoutplan-builder.component';

describe('WorkoutplanBuilderComponent', () => {
  let component: WorkoutplanBuilderComponent;
  let fixture: ComponentFixture<WorkoutplanBuilderComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WorkoutplanBuilderComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(WorkoutplanBuilderComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
