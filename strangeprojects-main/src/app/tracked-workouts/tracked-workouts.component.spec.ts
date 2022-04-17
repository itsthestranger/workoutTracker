import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrackedWorkoutsComponent } from './tracked-workouts.component';

describe('TrackedWorkoutsComponent', () => {
  let component: TrackedWorkoutsComponent;
  let fixture: ComponentFixture<TrackedWorkoutsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrackedWorkoutsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TrackedWorkoutsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
