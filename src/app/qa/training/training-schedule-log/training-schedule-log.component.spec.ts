import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrainingScheduleLogComponent } from './training-schedule-log.component';

describe('TrainingScheduleLogComponent', () => {
  let component: TrainingScheduleLogComponent;
  let fixture: ComponentFixture<TrainingScheduleLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrainingScheduleLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainingScheduleLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
