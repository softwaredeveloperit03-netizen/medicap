import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TrainingAttendanceLogComponent } from './training-attendance-log.component';

describe('TrainingAttendanceLogComponent', () => {
  let component: TrainingAttendanceLogComponent;
  let fixture: ComponentFixture<TrainingAttendanceLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TrainingAttendanceLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TrainingAttendanceLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
