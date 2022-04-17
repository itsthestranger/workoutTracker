import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ActiveWorkoutplanComponent } from './active-workoutplan.component';

describe('ActiveWorkoutplanComponent', () => {
  let component: ActiveWorkoutplanComponent;
  let fixture: ComponentFixture<ActiveWorkoutplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ActiveWorkoutplanComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ActiveWorkoutplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
