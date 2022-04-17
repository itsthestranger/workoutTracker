import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ActiveWorkoutplanOverviewComponent } from './active-workoutplan-overview.component';

describe('ActiveWorkoutplanOverviewComponent', () => {
  let component: ActiveWorkoutplanOverviewComponent;
  let fixture: ComponentFixture<ActiveWorkoutplanOverviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ActiveWorkoutplanOverviewComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ActiveWorkoutplanOverviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
