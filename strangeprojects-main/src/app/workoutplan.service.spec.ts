import { TestBed } from '@angular/core/testing';

import { WorkoutplanService } from './workoutplan.service';

describe('WorkoutplanService', () => {
  let service: WorkoutplanService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(WorkoutplanService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
